const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { test, expect } = require('@playwright/test');
const { disposableDatabaseSettings } = require('../support/disposable-database');
const root = path.resolve(__dirname, '../..');
const oldPassword = 'Synthet1c!Fixture';
const newPassword = 'BrowserReset2!Safe';
const proofs = [];
let environment;

function cli(file, input) {
  disposableDatabaseSettings('L06 rendered fixture');
  const r = spawnSync('php', [file], { cwd: root, env: process.env, encoding: 'utf8', input: input === undefined ? '' : JSON.stringify(input) });
  if (r.status !== 0) throw new Error(`L06 fixture command failed: ${path.basename(file)}`);
  return input === undefined ? r.stdout : JSON.parse(r.stdout);
}
function fixture(action, extra = {}) { return cli('tests/support/l06_browser_fixture.php', { action, ...extra }); }
function same(before, after) { expect(JSON.stringify(before) === JSON.stringify(after), 'Account/proof state must remain unchanged').toBe(true); }
async function request(page, user) {
  // Explicit synthetic cooldown is one second; real settings remain pending.
  await page.waitForTimeout(1100);
  await page.goto('/forgot_password.php');
  await page.getByLabel('Account email').fill(user.email);
  await page.getByRole('button', { name: 'Send reset instructions' }).click();
  await expect(page.getByRole('status')).toContainText('If the address is eligible');
}
async function issue(page, user) {
  await request(page, user);
  const { token } = fixture('dispatch', { id: user.id });
  if (!/^[a-f0-9]{64}$/.test(token)) throw new Error('Synthetic mail did not provide proof');
  proofs.push(token); return token;
}
async function openProof(page, token) {
  // Replace any browser error containing the navigation URL with a fixed message.
  try { await page.goto(`${process.env.APP_BASE_URL}/reset_password.php#token=${token}`); }
  catch { throw new Error('L06 reset navigation failed'); }
}
async function login(page, user, password = oldPassword) {
  await page.goto('/login.php');
  await page.getByLabel('Student email').fill(user.email);
  await page.getByLabel('Password', { exact: true }).fill(password);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/discover\.php$/);
}
async function fillPassword(page, token) {
  if (token) await page.getByLabel('One-time reset key').fill(token);
  await page.getByLabel('New password', { exact: true }).fill(newPassword);
  await page.getByLabel('Confirm new password').fill(newPassword);
}
test.describe.serial('L06 rendered recovery', () => {
  test.beforeAll(() => {
    cli('tests/bootstrap_database.php'); cli('bin/migrate.php'); cli('bin/migrate.php');
    environment = fixture('setup');
  });

  test('L06-B01 rendered requests are neutral and preserve signup mail purpose', async ({ page, browser }, info) => {
    info.annotations.push({ type: 'environment', description: JSON.stringify({ ...environment, chromium: browser.version() }) });
    const active = fixture('create'); const pending = fixture('create', { pending: true });
    const signupBefore = fixture('stats').signup;
    const messages = [];
    for (const user of [active, pending, { email: 'absent-l06@example.invalid' }]) {
      await request(page, user); messages.push(await page.getByRole('status').innerText());
    }
    expect(new Set(messages).size).toBe(1);
    const token = fixture('dispatch', { id: active.id }).token; proofs.push(token);
    expect(fixture('stats').signup).toBe(signupBefore);
    const pendingState = fixture('snapshot', { id: pending.id });
    expect(pendingState.status).toBe('pending_email'); expect(pendingState.verified).toBe(0);
  });

  test('L06-B02 fragment autofill clears URL, GET is inert, storage and telemetry exclude proof', async ({ page }) => {
    const user = fixture('create'); const token = await issue(page, user);
    const before = fixture('snapshot', { id: user.id }); const urls = []; const consoles = [];
    page.on('request', r => urls.push(r.url())); page.on('console', m => consoles.push(m.text()));
    const response = await page.goto('/reset_password.php');
    expect(response.headers()['cache-control']).toContain('no-store');
    expect(response.headers()['referrer-policy']).toBe('no-referrer');
    await openProof(page, token);
    await expect.poll(async () => (await page.getByLabel('One-time reset key').inputValue()) === token).toBe(true);
    expect(page.url().includes(token) || new URL(page.url()).hash !== '').toBe(false);
    same(before, fixture('snapshot', { id: user.id }));
    const stored = await page.evaluate(async proof => {
      const values = [...Object.values(localStorage), ...Object.values(sessionStorage), document.cookie];
      const databases = globalThis.indexedDB?.databases ? await indexedDB.databases() : [];
      const cacheNames = globalThis.caches ? await caches.keys() : [];
      return { found: values.some(v => v.includes(proof)), databases: databases.length, caches: cacheNames.length, historyState: history.state };
    }, token);
    expect(stored.found).toBe(false); expect(stored.databases).toBe(0); expect(stored.caches).toBe(0); expect(stored.historyState).toBe(null);
    expect((await page.context().cookies()).some(cookie => cookie.value.includes(token))).toBe(false);
    expect(urls.some(u => u.includes(token))).toBe(false);
    expect(urls.some(u => new URL(u).origin !== new URL(process.env.APP_BASE_URL).origin)).toBe(false);
    expect(consoles.some(m => m.includes(token))).toBe(false);
  });

  test('L06-B03 manual form without JavaScript rejects CSRF, invalid and expired proof', async ({ browser, page }) => {
    const user = fixture('create'); const token = await issue(page, user); const before = fixture('snapshot', { id: user.id });
    const context = await browser.newContext({ baseURL: process.env.APP_BASE_URL, javaScriptEnabled: false }); const manual = await context.newPage();
    await manual.goto(`${process.env.APP_BASE_URL}/reset_password.php`);
    await expect(manual.getByRole('heading', { name: 'Choose a new password' })).toBeVisible();
    await manual.getByLabel('One-time reset key').focus(); await manual.keyboard.press('Tab');
    await expect(manual.getByLabel('New password', { exact: true })).toBeFocused();
    const denied = await context.request.post(`${process.env.APP_BASE_URL}/reset_password.php`, { form: { csrf_token: 'invalid', reset_token: token, new_password: newPassword, confirm_password: newPassword } });
    expect((await denied.text()).includes('This reset could not be completed')).toBe(true); same(before, fixture('snapshot', { id: user.id }));
    await fillPassword(manual, 'f'.repeat(64)); await manual.getByRole('button', { name: 'Change password' }).click();
    await expect(manual.getByRole('alert')).toContainText('This reset could not be completed');
    same(before, fixture('snapshot', { id: user.id }));
    fixture('expire', { token }); const expiredBefore=fixture('snapshot', { id: user.id }); await fillPassword(manual, token); await manual.getByRole('button', { name: 'Change password' }).click();
    await expect(manual.getByRole('alert')).toContainText('This reset could not be completed');
    same(expiredBefore, fixture('snapshot', { id: user.id })); await context.close();
  });

  test('L06-B04 explicit reset revokes two sessions, returns to sign-in, rejects used proof', async ({ browser, page }) => {
    const user = fixture('create'); const first = await browser.newContext({ baseURL: process.env.APP_BASE_URL }); const second = await browser.newContext({ baseURL: process.env.APP_BASE_URL });
    const a = await first.newPage(); const b = await second.newPage(); await login(a, user); await login(b, user);
    for (const context of [first, second]) expect((await context.request.get(`${process.env.APP_BASE_URL}/api/notifications.php`)).status()).toBe(200);
    const token = await issue(page, user); const requests = [];
    page.on('request', r => requests.push({ url: r.url(), method: r.method(), body: r.postData() || '' }));
    await openProof(page, token); await expect.poll(async () => (await page.getByLabel('One-time reset key').inputValue()) === token).toBe(true);
    await fillPassword(page); await page.getByRole('button', { name: 'Change password' }).click();
    await expect(page.getByRole('status')).toContainText('password has been changed');
    expect((await page.request.get('/api/notifications.php', { maxRedirects: 0 })).status()).toBe(401);
    expect(requests.every(r => !r.url.includes(token) && (!r.body.includes(token) || (r.method === 'POST' && new URL(r.url).pathname === '/reset_password.php')))).toBe(true);
    for (const context of [first, second]) {
      const api = await context.request.get(`${process.env.APP_BASE_URL}/api/notifications.php`, { maxRedirects: 0 });
      expect(api.status()).toBe(401);
      const protectedPage = await context.newPage(); await protectedPage.goto(`${process.env.APP_BASE_URL}/discover.php`);
      await expect(protectedPage).toHaveURL(/\/login\.php/);
    }
    await page.getByRole('link', { name: 'Return to sign in' }).click(); await expect(page).toHaveURL(/\/login\.php/);
    await login(page, user, newPassword);
    const recovery = await browser.newContext({ baseURL: process.env.APP_BASE_URL }); const retry = await recovery.newPage();
    await retry.goto(`${process.env.APP_BASE_URL}/reset_password.php`); await fillPassword(retry, token);
    await retry.getByRole('button', { name: 'Change password' }).click(); await expect(retry.getByRole('alert')).toContainText('This reset could not be completed');
    expect(fixture('stats').notices).toBe(1);
    await recovery.close(); await first.close(); await second.close();
  });

  test('L06-B05 no-JavaScript scanner visits are inert and manual key completes reset', async ({ browser, page }) => {
    const user = fixture('create'); const token = await issue(page, user); const before = fixture('snapshot', { id: user.id });
    const context = await browser.newContext({ baseURL: process.env.APP_BASE_URL, javaScriptEnabled: false }); const manual = await context.newPage();
    await openProof(manual, token); try { await manual.reload(); } catch { throw new Error('L06 scanner reload failed'); }
    const head = await context.request.head(`${process.env.APP_BASE_URL}/reset_password.php`); expect(head.status()).toBe(200);
    same(before, fixture('snapshot', { id: user.id }));
    await manual.goto(`${process.env.APP_BASE_URL}/reset_password.php`); await fillPassword(manual, token);
    await manual.getByRole('button', { name: 'Change password' }).focus(); await manual.keyboard.press('Enter');
    await expect(manual.getByRole('status')).toContainText('password has been changed');
    await manual.getByRole('link', { name: 'Return to sign in' }).click(); await expect(manual).toHaveURL(/\/login\.php/);
    await login(manual, user, newPassword); await context.close();
  });

  test('L06-B06 controlled HTTP diagnostics and code-free records contain no proof', () => {
    expect(fixture('diagnostics', { tokens: proofs }).proof_found).toBe(false);
  });
});
