'use strict';

// Explicit runner, never a Playwright global setup that resets a target.
const path = require('node:path');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');
if (['DEBUG', 'PWDEBUG', 'DEBUG_FILE', 'NODE_OPTIONS'].some(key => process.env[key])
  || process.execArgv.some(arg => arg.startsWith('--inspect'))) {
  process.stdout.write('{"status":"BLOCKED","reason":"STAGE_DEBUG_INSTRUMENTATION"}\n'); process.exit(1);
}
const { chromium } = require('@playwright/test');
const root = path.resolve(__dirname, '..');

function need(ok, code) { if (!ok) throw new Error(code); }
function privateCommand(file, input, extraEnv = {}) {
  const nonce = crypto.randomBytes(32).toString('hex');
  const child = spawnSync(process.env.STAGE_PHP_BINARY || 'php', [path.join(root, file)], {
    cwd: root, env: { ...process.env, ...extraEnv, BUSHISA_STAGE_PRIVATE_PIPE: 'true', BUSHISA_STAGE_PIPE_NONCE: nonce },
    stdio: ['pipe', 'pipe', 'pipe', 'pipe'], encoding: 'utf8',
    input: JSON.stringify({ ...input, nonce }), timeout: 45000, maxBuffer: 65536,
  });
  if (child.status !== 0) {
    // Child stderr is never forwarded (nor are private stdout/pipe contents).
    const reason = /"reason":"(STAGE_[A-Z_]+)"/.exec(child.stderr || '');
    throw new Error(reason ? reason[1] : 'STAGE_PRIVATE_COMMAND_FAILED');
  }
  try { return { public: JSON.parse(child.stdout), private: JSON.parse(child.output[3]) }; }
  catch { throw new Error('STAGE_PRIVATE_FRAME_FAILED'); }
}

async function run() {
  const arg = process.argv[2];
  need(process.argv.length === 3 && ['--preflight', '--execute-local-fixture', '--execute-managed-stage'].includes(arg), 'STAGE_EXPLICIT_INTENT_REQUIRED');
  need(process.env.BUSHISA_STAGE_ROLE === 'runner' && process.env.BUSHISA_STAGE_INTENT === (arg === '--preflight' ? 'preflight' : 'execute'), 'STAGE_DRIVER_INTENT');
  const inspect = (action, account, extra = {}) => privateCommand('bin/stage_control.php', { action, account_id: account?.id, ...extra });
  const preflight = inspect('preflight');
  const { accounts, mode, origin, worker_user: workerUser } = preflight.private;
  if (arg === '--preflight') {
    process.stdout.write(JSON.stringify({ ...preflight.public, browser_executed: false }) + '\n'); return;
  }
  need(arg === (mode === 'managed' ? '--execute-managed-stage' : '--execute-local-fixture'), 'STAGE_EXECUTION_LANE_MISMATCH');
  need(preflight.public.reset_issuance_enabled === true, 'STAGE_SYNTHETIC_ISSUANCE_DISABLED');
  need(process.env.BUSHISA_STAGE_WORKER_DB_PASSWORD && process.env.BUSHISA_STAGE_FIXTURE_PASSWORD, 'STAGE_PRIVATE_CREDENTIALS_REQUIRED');
  const local = mode === 'local-fixture'; const args = [];
  if (local) {
    const pin = process.env.BUSHISA_STAGE_LOCAL_TLS_SPKI || '';
    need(/^[A-Za-z0-9+/]{43}=$/.test(pin), 'STAGE_LOCAL_CERT_PIN');
    args.push(`--host-resolver-rules=MAP ${new URL(origin).hostname} 127.0.0.1`, `--ignore-certificate-errors-spki-list=${pin}`, '--proxy-server=direct://', '--proxy-bypass-list=*');
  }
  const results = []; let browser; let current = 'SG-D00'; let proof; const contexts = [];
  const record = (id, ok) => { current = id; need(ok, id); results.push({ case: id, result: 'PASS' }); };
  const context = async (options = {}) => {
    const c = await browser.newContext({ baseURL: origin, ...options }); contexts.push(c);
    // Probe the actual HTTP authorization response without app.js's global
    // 401 navigation consuming the separate return-to-sign-in UI assertion.
    await c.addInitScript(() => Object.defineProperty(window, '__stageProbeFetch', { value: window.fetch.bind(window) }));
    // No trace/video/screenshots; refuse third-party requests before network I/O.
    await c.route('**/*', route => new URL(route.request().url()).origin === origin ? route.continue() : route.abort());
    return c;
  };
  const dispatch = (account) => {
    const jobs = inspect('jobs', account).private.jobs;
    need(jobs.length > 0, 'STAGE_EXPECTED_JOB_MISSING');
    const r = privateCommand('bin/dispatch_synthetic_stage_mail.php', { jobs }, {
      BUSHISA_STAGE_ROLE: 'worker', BUSHISA_STAGE_INTENT: 'dispatch', DB_USER: workerUser,
      DB_PASSWORD: process.env.BUSHISA_STAGE_WORKER_DB_PASSWORD,
    });
    need(r.public.counts.failed === 0, 'STAGE_CARRIER_FAILED'); return r.private.deliveries;
  };
  try {
    browser = await chromium.launch({ headless: true, args });
    const main = await context(); const page = await main.newPage();
    const active = accounts.find(a => a.account_status === 'active'); const pending = accounts.find(a => a.account_status === 'pending_email');
    need(active && pending, 'STAGE_FRESH_FIXTURES_REQUIRED');
    const request = async email => {
      await page.goto('/forgot_password.php'); await page.getByLabel('Account email').fill(email);
      await page.getByRole('button', { name: 'Send reset instructions' }).click();
      return page.getByRole('status').innerText();
    };
    const texts = [];
    current = 'SG-D01';
    for (const email of [active.email, pending.email, 'absent@students.example.invalid']) texts.push(await request(email));
    record('SG-D01', new Set(texts).size === 1);
    const deliveries = dispatch(active); proof = deliveries.find(d => d.purpose === 'password_reset')?.token;
    need(/^[a-f0-9]{64}$/.test(proof || ''), 'STAGE_PROOF_UNAVAILABLE');
    current = 'SG-D02';
    const before = inspect('snapshot', active).private;
    const response = await page.goto('/reset_password.php');
    await page.evaluate(async () => { await fetch('/reset_password.php', { method: 'HEAD' }); });
    record('SG-D02', JSON.stringify(before) === JSON.stringify(inspect('snapshot', active).private)
      && response.headers()['cache-control'].includes('no-store') && response.headers()['referrer-policy'] === 'no-referrer');
    current = 'SG-D03';
    const bad = await page.evaluate(async token => {
      const r = await fetch('/reset_password.php', { method: 'POST', body: new URLSearchParams({ csrf_token: 'invalid', reset_token: token, new_password: 'RejectedPassword2!', confirm_password: 'RejectedPassword2!' }) });
      return (await r.text()).includes('This reset could not be completed');
    }, proof);
    record('SG-D03', bad && JSON.stringify(before) === JSON.stringify(inspect('snapshot', active).private));
    current = 'SG-D04';
    const first = await context(); const second = await context();
    const pages = [];
    const login = async (p, password) => {
      await p.goto('/login.php'); await p.getByLabel('Student email').fill(active.email); await p.getByLabel('Password', { exact: true }).fill(password);
      await p.getByRole('button', { name: 'Sign in', exact: true }).click();
      need(new URL(p.url()).pathname === '/discover.php', 'STAGE_LOGIN_FAILED');
    };
    const api = p => p.evaluate(async () => (await window.__stageProbeFetch('/api/notifications.php', { redirect: 'manual' })).status);
    for (const c of [first, second]) { const p = await c.newPage(); pages.push(p); await login(p, process.env.BUSHISA_STAGE_FIXTURE_PASSWORD); need(await api(p) === 200, 'STAGE_PRE_RESET_ACCESS'); }
    let leaked = false;
    page.on('request', r => { if (r.url().includes(proof) || (r.postData() || '').includes(proof) && (r.method() !== 'POST' || new URL(r.url()).pathname !== '/reset_password.php')) leaked = true; });
    page.on('console', message => { if (message.text().includes(proof)) leaked = true; });
    await page.goto(`${origin}/reset_password.php#token=${proof}`);
    await page.getByLabel('One-time reset key').waitFor();
    await page.waitForFunction(() => !location.hash && document.querySelector('#reset-token').value.length === 64);
    const stored = await page.evaluate(token => [...Object.values(localStorage), ...Object.values(sessionStorage), document.cookie].some(v => v.includes(token)), proof);
    record('SG-D04', !stored && !page.url().includes(proof) && !leaked);
    current = 'SG-D05';
    const newPassword = `Stage2!${crypto.randomBytes(24).toString('hex')}`;
    await page.getByLabel('New password', { exact: true }).fill(newPassword); await page.getByLabel('Confirm new password').fill(newPassword);
    await page.getByRole('button', { name: 'Change password' }).click();
    record('SG-D05', (await page.getByRole('status').innerText()).includes('password has been changed') && await api(page) === 401);
    current = 'SG-D06';
    for (const p of pages) { need(await api(p) === 401, 'STAGE_API_REVOCATION'); await p.goto('/discover.php'); need(new URL(p.url()).pathname === '/login.php', 'STAGE_PAGE_REVOCATION'); }
    record('SG-D06', true);
    current = 'SG-D07';
    const manualContext = await context({ javaScriptEnabled: false }); const manual = await manualContext.newPage();
    await manual.goto('/reset_password.php'); await manual.getByLabel('One-time reset key').fill(proof);
    await manual.getByLabel('New password', { exact: true }).fill(newPassword); await manual.getByLabel('Confirm new password').fill(newPassword);
    await manual.getByRole('button', { name: 'Change password' }).click();
    need((await manual.getByRole('alert').innerText()).includes('This reset could not be completed'), 'STAGE_USED_PROOF');
    await page.getByRole('link', { name: 'Return to sign in' }).click(); await login(page, newPassword);
    record('SG-D07', !leaked && (await inspect('snapshot', active).private).session_version > before.session_version);
    // The pending signup has an independent encrypted envelope and challenge.
    current = 'SG-D08';
    const signup = dispatch(pending).find(d => d.purpose === 'signup');
    record('SG-D08', /^[0-9]{6,10}$/.test(signup?.code || '') && inspect('snapshot', pending).private.status === 'pending_email');
    current = 'SG-D09';
    const notices = dispatch(active);
    record('SG-D09', notices.some(d => d.purpose === 'password_changed') && notices.every(d => !d.token && !d.code));
    current = 'SG-D10';
    if (local) record('SG-D10', inspect('diagnostics', active, { proof }).private.proof_found === false);
    else results.push({ case: 'SG-D10', result: 'NOT RUN', reason: 'Actual managed diagnostic destinations require separate scoped evidence' });
    process.stdout.write(JSON.stringify({ status: 'PASS', execution: local ? 'local-fixture HTTPS proxy; NOT managed Azure' : 'managed-stage only; NOT production acceptance', environment: { ...preflight.public.environment, node: process.version, chromium: browser.version() }, cases: results, managed_roadmap_cases_changed: false }) + '\n');
  } catch (error) {
    // Playwright exceptions can embed a proof URL/DOM. Never publish them.
    const reason = /^STAGE_[A-Z_]+$/.test(error.message) ? error.message : 'STAGE_BROWSER_OPERATION';
    const sourceLine = /managed_stage_driver\.js:(\d+):\d+/.exec(error.stack || '')?.[1] || null;
    process.stdout.write(JSON.stringify({ status: 'FAIL', case: current, reason, source_line: sourceLine, cases: results }) + '\n'); process.exitCode = 1;
  } finally {
    proof = null; for (const c of contexts) await c.close().catch(() => {}); if (browser) await browser.close();
  }
}

run().catch(error => {
  const reason = /^STAGE_[A-Z_]+$/.test(error.message) ? error.message : 'STAGE_PREFLIGHT_FAILED';
  process.stdout.write(JSON.stringify({ status: 'BLOCKED', reason, browser_executed: false }) + '\n'); process.exitCode = 1;
});
