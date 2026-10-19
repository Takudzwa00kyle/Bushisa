const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { test, expect } = require('@playwright/test');
const {
  disposableDatabaseSettings,
  quoteIdentifier,
  redactedCommand,
  schemaForDisposableDatabase,
} = require('../support/disposable-database');

const repoRoot = path.resolve(__dirname, '..', '..');
const password = 'Passw0rd!';

function requireOverlapRuntime() {
  if (process.env.BUSHISA_TEST_HTTP_OVERLAP !== 'true') {
    throw new Error('Prompt 02 HTTP overlap checks require BUSHISA_TEST_HTTP_OVERLAP=true.');
  }
  if (process.platform === 'win32' && process.env.BUSHISA_EXTERNAL_TEST_SERVER !== 'true') {
    throw new Error('Prompt 02 HTTP overlap checks require an isolated multi-worker Linux server, not native Windows php -S.');
  }
}

function mysqlArgs(database = true) {
  const settings = disposableDatabaseSettings('Prompt 02 HTTP overlap checks');
  const args = [
    '--protocol=TCP',
    `--host=${settings.host}`,
    `--port=${settings.port}`,
    `--user=${settings.user}`,
  ];
  if (settings.password !== '') {
    args.push(`--password=${settings.password}`);
  }
  if (database) {
    args.push(settings.name);
  }
  return args;
}

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: repoRoot,
    env: process.env,
    encoding: 'utf8',
    ...options,
  });
  if (result.status !== 0) {
    throw new Error(`${redactedCommand(command, args)} failed\nSTDOUT:\n${result.stdout}\nSTDERR:\n${result.stderr}`);
  }
  return result.stdout;
}

function resetDatabase() {
  const settings = disposableDatabaseSettings('Prompt 02 HTTP overlap checks');
  const schema = schemaForDisposableDatabase(fs.readFileSync(path.join(repoRoot, 'database', 'bushisa.sql'), 'utf8'), settings.name);
  run('mysql', mysqlArgs(false), {
    input: `DROP DATABASE IF EXISTS ${quoteIdentifier(settings.name)};\n`,
  });
  run('mysql', mysqlArgs(false), { input: schema });
  run('php', ['bin/migrate.php']);
  run('php', ['tests/support/activate_pair_ledger.php', '--writer-ref=synthetic:browser-regressions']);
}

function sqlString(value) {
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "''")}'`;
}

function queryValue(sql) {
  return run('mysql', [...mysqlArgs(true), '--batch', '--raw', '--skip-column-names', '--execute', sql]).trim();
}

function makeStudent(gender) {
  const suffix = Math.floor(Math.random() * 100000).toString().padStart(5, '0');
  const letter = String.fromCharCode(97 + Math.floor(Math.random() * 26));
  const studentId = `n025${suffix}${letter}`;
  return {
    studentId,
    email: `${studentId}@students.nust.ac.zw`,
    displayName: `Overlap ${suffix}`,
    handle: `p02h_${suffix}_${letter}`,
    gender,
  };
}

async function registerStudent(page, student) {
  await page.goto('/register.php');
  await page.getByLabel('NUST student number').fill(student.studentId);
  await page.getByLabel('Student email').fill(student.email);
  await page.getByLabel('Password').fill(password);
  await page.getByLabel('Display name').fill(student.displayName);
  await page.getByLabel('Anonymous handle').fill(student.handle);
  await page.getByLabel('Gender').selectOption(student.gender);
  await page.getByLabel('Faculty').selectOption('Applied Sciences');
  await page.getByLabel('Year of study').selectOption('Part 2');
  await page.getByRole('button', { name: 'Create account' }).click();
  await expect(page.getByRole('status')).toContainText('Account created. Approval mail has been queued.');
}

function approvalCodeFor(email) {
  const body = queryValue(
    `SELECT body FROM mail_outbox WHERE recipient_email = ${sqlString(email)} ORDER BY id DESC LIMIT 1`
  );
  const match = body.match(/approval code is ([0-9]{6,10})/);
  if (!match) {
    throw new Error(`No approval code found for ${email}.`);
  }
  return match[1];
}

async function approveEmail(page, student) {
  const code = approvalCodeFor(student.email);
  await page.goto(`/verify_email.php?email=${encodeURIComponent(student.email)}`);
  await page.getByLabel('Approval code').fill(code);
  await page.getByRole('button', { name: 'Approve email' }).click();
  await expect(page.getByText('Student email approved. Sign in to complete adult onboarding.')).toBeVisible();
}

async function login(page, student) {
  await page.goto('/login.php');
  await page.getByLabel('Student email').fill(student.email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: 'Sign in' }).click();
}

async function completeAdultOnboarding(page) {
  await expect(page).toHaveURL(/\/onboarding\.php$/);
  await page.getByLabel('Date of birth').fill('2000-01-01');
  await page.getByRole('button', { name: 'Complete onboarding' }).click();
  await expect(page).toHaveURL(/\/discover\.php$/);
}

async function createActiveStudent(browser, gender) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const student = makeStudent(gender);
  await registerStudent(page, student);
  await approveEmail(page, student);
  await login(page, student);
  await completeAdultOnboarding(page);
  await context.close();
  return {
    ...student,
    id: Number(queryValue(`SELECT id FROM users WHERE email = ${sqlString(student.email)} LIMIT 1`)),
  };
}

async function loginForApi(browser, student) {
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page, student);
  await expect(page).toHaveURL(/\/discover\.php$/);
  const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  if (!csrfToken) {
    throw new Error('No CSRF token rendered for API request.');
  }
  return { context, page, csrfToken };
}

async function overlappingSwipe(page, csrfToken, targetId, barrierId) {
  const current = await page.evaluate(async target => { const response=await fetch(`/api/pairs.php?user_id=${target}`,{headers:{Accept:'application/json'}});return response.json(); }, targetId);
  const intent = { pair_version: current.data.pair_version, idempotency_key: require('node:crypto').randomBytes(32).toString('hex') };
  return page.evaluate(
    async ({ csrfToken: token, targetId: target, barrierId: barrier, intent }) => {
      const startedAt = performance.now();
      const barrierHeaders = barrier === null ? {} : { 'X-Bushisa-Test-Barrier': barrier, 'X-Bushisa-Test-Barrier-Expected': '2', 'X-Bushisa-Test-Barrier-Timeout-Ms': '10000' };
      const response = await fetch('/api/swipe.php', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
          ...barrierHeaders,
        },
        body: JSON.stringify({ target_id: target, direction: 'like', ...intent }),
      });
      const completedAt = performance.now();
      return {
        status: response.status,
        body: await response.json(),
        startedAt,
        completedAt,
      };
    },
    { csrfToken, targetId, barrierId, intent }
  );
}

function barrierParticipants(barrierId, point) {
  const safeKey = `${barrierId}_${point}`.replace(/[^A-Za-z0-9_.-]/g, '_');
  const directory = path.join(os.tmpdir(), 'bushisa-http-barriers');
  if (!fs.existsSync(directory)) {
    return [];
  }

  return fs.readdirSync(directory)
    .filter((name) => name.startsWith(`${safeKey}-`) && name.endsWith('.json'))
    .map((name) => {
      const participant = JSON.parse(fs.readFileSync(path.join(directory, name), 'utf8'));
      return {
        pid: participant.pid,
        point: participant.point,
        entered_at: participant.entered_at,
        uri: participant.uri,
      };
    })
    .sort((left, right) => String(left.entered_at).localeCompare(String(right.entered_at)));
}

test.describe.serial('Prompt 02 HTTP overlap checks', () => {
  test.setTimeout(180_000);

  test.beforeAll(() => {
    requireOverlapRuntime();
    resetDatabase();
  });

  test('overlapping reciprocal swipe requests produce one established match and no duplicate consent', async ({ browser }, testInfo) => {
    let lastAttempt = null;

    for (let attempt = 1; attempt <= 3; attempt += 1) {
      const firstStudent = await createActiveStudent(browser, 'male');
      const secondStudent = await createActiveStudent(browser, 'female');
      const first = await loginForApi(browser, firstStudent);
      const second = await loginForApi(browser, secondStudent);
      const barrierId = `swipe-${Date.now()}-${Math.floor(Math.random() * 100000)}`;

      const [firstResult, secondResult] = await Promise.all([
        overlappingSwipe(first.page, first.csrfToken, secondStudent.id, barrierId),
        overlappingSwipe(second.page, second.csrfToken, firstStudent.id, barrierId),
      ]);

      const participants = barrierParticipants(barrierId, 'swipe_after_transaction_begin');
      const attemptEvidence = { attempt, barrierId, participants, firstResult, secondResult, firstStudent, secondStudent };
      lastAttempt = attemptEvidence;

      if (participants.length < 2 || new Set(participants.map((participant) => participant.pid)).size < 2) {
        await testInfo.attach(`http-overlap-barrier-miss-${attempt}`, {
          body: JSON.stringify(attemptEvidence, null, 2),
          contentType: 'application/json',
        });
        await first.context.close(); await second.context.close();
        continue;
      }

      expect([firstResult.status, secondResult.status].sort()).toEqual([200, 409]);
      const refreshed = firstResult.status === 409
        ? await overlappingSwipe(first.page, first.csrfToken, secondStudent.id, null)
        : await overlappingSwipe(second.page, second.csrfToken, firstStudent.id, null);
      expect(refreshed.status).toBe(200);
      expect([firstResult.body.match, secondResult.body.match, refreshed.body.match].filter(Boolean)).toHaveLength(1);

      await testInfo.attach('http-overlap-barrier', {
        body: JSON.stringify({
          barrierId,
          participants,
          firstTiming: {
            startedAt: firstResult.startedAt,
            completedAt: firstResult.completedAt,
          },
          secondTiming: {
            startedAt: secondResult.startedAt,
            completedAt: secondResult.completedAt,
          },
        }, null, 2),
        contentType: 'application/json',
      });
      console.log(`HTTP overlap barrier ${barrierId} participants=${participants.map((participant) => participant.pid).join(',')}`);

      const swipes = Number(queryValue(
        `SELECT COUNT(*) FROM swipes
         WHERE (swiper_id = ${firstStudent.id} AND swiped_id = ${secondStudent.id})
            OR (swiper_id = ${secondStudent.id} AND swiped_id = ${firstStudent.id})`
      ));
      const matches = Number(queryValue(
        `SELECT COUNT(*) FROM matches
         WHERE user_a_id = ${Math.min(firstStudent.id, secondStudent.id)}
           AND user_b_id = ${Math.max(firstStudent.id, secondStudent.id)}
           AND match_status = 'active'`
      ));
      const approvedRequests = Number(queryValue(
        `SELECT COUNT(*) FROM message_requests
         WHERE status = 'approved'`
      ));

      expect(swipes).toBe(2);
      expect(matches).toBe(1);
      expect(approvedRequests).toBe(0);
      expect(Math.max(firstResult.completedAt, secondResult.completedAt)).toBeGreaterThan(
        Math.min(firstResult.startedAt, secondResult.startedAt)
      );
      await first.context.close(); await second.context.close();
      return;
    }

    throw new Error(`HTTP overlap barrier did not observe two distinct workers after retries: ${JSON.stringify(lastAttempt)}`);
  });
});
