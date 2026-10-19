const fs = require('node:fs');
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

function mysqlArgs(database = true) {
  const settings = disposableDatabaseSettings('Prompt 02 chat route checks');
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
  const settings = disposableDatabaseSettings('Prompt 02 chat route checks');
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

function executeSql(sql) {
  run('mysql', [...mysqlArgs(true), '--execute', sql]);
}

function makeStudent(prefix, gender) {
  const suffix = Math.floor(Math.random() * 100000).toString().padStart(5, '0');
  const letter = String.fromCharCode(97 + Math.floor(Math.random() * 26));
  const studentId = `n025${suffix}${letter}`;
  return {
    studentId,
    email: `${studentId}@students.nust.ac.zw`,
    displayName: `${prefix} ${suffix}`,
    handle: `${prefix.toLowerCase()}_${suffix}_${letter}`,
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

async function createActiveStudent(browser, prefix, gender) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const student = makeStudent(prefix, gender);
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

function createMatch(leftId, rightId) {
  return JSON.parse(run('php', ['tests/support/browser_pair_fixture.php', String(leftId), String(rightId)])).match_id;
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

async function api(page, pathName, csrfToken, body, extraHeaders = {}) {
  if (pathName === '/api/message_requests.php' || (pathName === '/api/safety.php' && ['block', 'unblock'].includes(body.action))) {
    const query = body.match_id ? `match_id=${body.match_id}` : `user_id=${body.user_id}`;
    const current = await getJson(page, `/api/pairs.php?${query}`);
    body = { ...body, pair_version: current.status === 200 ? current.body.data.pair_version : 0,
      idempotency_key: require('node:crypto').randomBytes(32).toString('hex') };
  }
  return page.evaluate(
    async ({ pathName: url, csrfToken: token, body: payload, extraHeaders: headers }) => {
      const requestHeaders = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...headers,
      };
      if (token !== null) {
        requestHeaders['X-CSRF-Token'] = token;
      }
      const response = await fetch(url, {
        method: 'POST',
        headers: requestHeaders,
        body: JSON.stringify(payload),
      });
      let parsed = null;
      try {
        parsed = await response.json();
      } catch {
        parsed = null;
      }
      return { status: response.status, body: parsed };
    },
    { pathName, csrfToken, body, extraHeaders }
  );
}

async function getJson(page, pathName) {
  return page.evaluate(async (url) => {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    return { status: response.status, body: await response.json() };
  }, pathName);
}

async function approveChat(first, second, matchId) {
  const request = await api(first.page, '/api/message_requests.php', first.csrfToken, {
    match_id: matchId,
    action: 'request',
  });
  expect(request.status).toBe(200);
  const approve = await api(second.page, '/api/message_requests.php', second.csrfToken, {
    match_id: matchId,
    action: 'approve',
  });
  expect(approve.status).toBe(200);
}

test.describe.serial('Prompt 02 chat route checks', () => {
  test.beforeEach(() => {
    resetDatabase();
  });

  test('P02-CHAT-01 request, approve, send, read, unsend and revoke use actual APIs with ownership and CSRF denial', async ({ browser }) => {
    const leftStudent = await createActiveStudent(browser, 'RouteA', 'male');
    const rightStudent = await createActiveStudent(browser, 'RouteB', 'female');
    const outsiderStudent = await createActiveStudent(browser, 'RouteC', 'male');
    const matchId = createMatch(leftStudent.id, rightStudent.id);

    const left = await loginForApi(browser, leftStudent);
    const right = await loginForApi(browser, rightStudent);
    const outsider = await loginForApi(browser, outsiderStudent);

    const missingCsrf = await api(left.page, '/api/message_requests.php', null, {
      match_id: matchId,
      action: 'request',
    });
    expect(missingCsrf.status).toBe(403);
    expect(Number(queryValue('SELECT COUNT(*) FROM message_requests'))).toBe(0);

    const request = await api(left.page, '/api/message_requests.php', left.csrfToken, {
      match_id: matchId,
      action: 'request',
    });
    expect(request.status).toBe(200);
    expect(request.body.data.status).toBe('pending');

    const prematureSend = await api(left.page, '/api/messages.php', left.csrfToken, {
      match_id: matchId,
      body: 'before approval',
    });
    expect(prematureSend.status).toBe(403);
    expect(Number(queryValue('SELECT COUNT(*) FROM messages'))).toBe(0);

    const forgedRequest = await api(outsider.page, '/api/message_requests.php', outsider.csrfToken, {
      match_id: matchId,
      action: 'approve',
    });
    expect(forgedRequest.status).toBe(404);
    expect(Number(queryValue('SELECT COUNT(*) FROM message_requests'))).toBe(1);

    const approve = await api(right.page, '/api/message_requests.php', right.csrfToken, {
      match_id: matchId,
      action: 'approve',
    });
    expect(approve.status).toBe(200);
    expect(approve.body.data.status).toBe('approved');

    const sent = await api(left.page, '/api/messages.php', left.csrfToken, {
      match_id: matchId,
      body: 'route message body',
    });
    expect(sent.status).toBe(200);
    const messageId = sent.body.data.id;

    const read = await getJson(right.page, `/api/messages.php?match_id=${matchId}&after_id=0`);
    expect(read.status).toBe(200);
    expect(read.body.data).toHaveLength(1);
    expect(Number(queryValue(`SELECT is_read FROM messages WHERE id = ${messageId}`))).toBe(1);

    const forgedReport = await api(outsider.page, '/api/safety.php', outsider.csrfToken, {
      action: 'report',
      target_type: 'message',
      target_id: messageId,
      reason: 'harassment',
      context: read.body.data[0].report_context,
      idempotency_key: require('node:crypto').randomBytes(32).toString('hex'),
    });
    expect(forgedReport.status).toBe(404);
    expect(Number(queryValue('SELECT COUNT(*) FROM reports'))).toBe(0);

    const report = await api(right.page, '/api/safety.php', right.csrfToken, {
      action: 'report',
      target_type: 'message',
      target_id: messageId,
      reason: 'harassment',
      context: read.body.data[0].report_context,
      idempotency_key: require('node:crypto').randomBytes(32).toString('hex'),
    });
    expect(report.status).toBe(200);
    expect(report.body.data.status).toBe('received');
    expect(Number(queryValue('SELECT COUNT(*) FROM reports'))).toBe(1);
    expect(queryValue('SELECT evidence_excerpt FROM moderation_evidence ORDER BY id DESC LIMIT 1')).toBe('route message body');

    const forgedUnsend = await api(right.page, '/api/messages.php', right.csrfToken, {
      match_id: matchId,
      action: 'unsend',
      message_id: messageId,
    });
    expect(forgedUnsend.status).toBe(403);
    expect(Number(queryValue(`SELECT COUNT(*) FROM messages WHERE id = ${messageId} AND unsent_at IS NULL`))).toBe(1);

    const unsent = await api(left.page, '/api/messages.php', left.csrfToken, {
      match_id: matchId,
      action: 'unsend',
      message_id: messageId,
    });
    expect(unsent.status).toBe(200);
    expect(Number(queryValue(`SELECT COUNT(*) FROM messages WHERE id = ${messageId} AND unsent_at IS NOT NULL AND body = ''`))).toBe(1);
    expect(queryValue('SELECT evidence_excerpt FROM moderation_evidence ORDER BY id DESC LIMIT 1')).toBe('route message body');

    const revoke = await api(right.page, '/api/message_requests.php', right.csrfToken, {
      match_id: matchId,
      action: 'revoke',
    });
    expect(revoke.status).toBe(200);
    expect(revoke.body.data.status).toBe('revoked');

    const deniedAfterRevoke = await api(left.page, '/api/messages.php', left.csrfToken, {
      match_id: matchId,
      body: 'after revoke',
    });
    expect(deniedAfterRevoke.status).toBe(403);
    expect(Number(queryValue('SELECT COUNT(*) FROM messages'))).toBe(1);

    await left.context.close();
    await right.context.close();
    await outsider.context.close();
  });

  test('P02-CHAT-02 block and account deletion close chat grants, preserve scoped evidence and revoke sessions', async ({ browser }) => {
    const leftStudent = await createActiveStudent(browser, 'BlockA', 'male');
    const rightStudent = await createActiveStudent(browser, 'BlockB', 'female');
    const matchId = createMatch(leftStudent.id, rightStudent.id);
    const left = await loginForApi(browser, leftStudent);
    const leftSecond = await loginForApi(browser, leftStudent);
    const right = await loginForApi(browser, rightStudent);
    await approveChat(left, right, matchId);

    const sent = await api(left.page, '/api/messages.php', left.csrfToken, {
      match_id: matchId,
      body: 'retained evidence body',
    });
    expect(sent.status).toBe(200);

    const badBlock = await api(right.page, '/api/safety.php', null, {
      action: 'block',
      user_id: leftStudent.id,
    });
    expect(badBlock.status).toBe(403);
    expect(Number(queryValue('SELECT COUNT(*) FROM blocks'))).toBe(0);

    const block = await api(right.page, '/api/safety.php', right.csrfToken, {
      action: 'block',
      user_id: leftStudent.id,
    });
    expect(block.status).toBe(200);
    expect(queryValue(`SELECT match_status FROM matches WHERE id = ${matchId}`)).toBe('blocked');
    expect(queryValue(`SELECT status FROM message_requests WHERE match_id = ${matchId} ORDER BY id DESC LIMIT 1`)).toBe('revoked');
    expect(Number(queryValue('SELECT COUNT(*) FROM messages'))).toBe(1);
    expect(Number(queryValue(`SELECT COUNT(*) FROM notifications WHERE deep_link = 'chat.php?match_id=${matchId}'`))).toBe(0);

    const deniedAfterBlock = await api(left.page, '/api/messages.php', left.csrfToken, {
      match_id: matchId,
      body: 'after block',
    });
    expect(deniedAfterBlock.status).toBe(403);
    expect(Number(queryValue('SELECT COUNT(*) FROM messages'))).toBe(1);

    const deleteResult = await api(left.page, '/api/account.php', left.csrfToken, {
      action: 'delete_account',
      password,
    });
    expect(deleteResult.status).toBe(200);
    expect(queryValue(`SELECT account_status FROM users WHERE id = ${leftStudent.id}`)).toBe('deleted');
    expect(Number(queryValue(`SELECT COUNT(*) FROM protective_replay_events WHERE event_type = 'match_closed' AND subject_id = ${matchId}`))).toBe(2);

    const staleSession = await getJson(leftSecond.page, '/api/discover.php');
    expect(staleSession.status).toBe(401);
    expect(Number(queryValue(`SELECT COUNT(*) FROM protective_replay_events WHERE event_type = 'account_deleted' AND subject_id = ${leftStudent.id}`))).toBe(1);

    await left.context.close();
    await leftSecond.context.close();
    await right.context.close();
  });

  test('P02-CHAT-03 role removal invalidates existing moderator page access in both sessions', async ({ browser }) => {
    const moderatorStudent = await createActiveStudent(browser, 'ModA', 'female');
    executeSql(`UPDATE users SET role = 'moderator' WHERE id = ${moderatorStudent.id}`);
    const first = await loginForApi(browser, moderatorStudent);
    const second = await loginForApi(browser, moderatorStudent);

    await first.page.goto('/admin/dashboard.php');
    await expect(first.page).toHaveURL(/\/admin\/dashboard\.php$/);
    await second.page.goto('/admin/reports.php');
    await expect(second.page).toHaveURL(/\/admin\/reports\.php$/);

    executeSql(`UPDATE users SET role = 'user' WHERE id = ${moderatorStudent.id}`);

    await first.page.goto('/admin/dashboard.php');
    await expect(first.page).toHaveURL(/\/discover\.php$/);
    await second.page.goto('/admin/reports.php');
    await expect(second.page).toHaveURL(/\/discover\.php$/);
    expect(Number(queryValue('SELECT COUNT(*) FROM reports'))).toBe(0);

    await first.context.close();
    await second.context.close();
  });
});
