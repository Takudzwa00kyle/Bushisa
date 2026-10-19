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
    throw new Error('Prompt 02 chat overlap checks require BUSHISA_TEST_HTTP_OVERLAP=true.');
  }
  if (process.platform === 'win32' && process.env.BUSHISA_EXTERNAL_TEST_SERVER !== 'true') {
    throw new Error('Prompt 02 chat overlap checks require an isolated multi-worker Linux server, not native Windows php -S.');
  }
}

function mysqlArgs(database = true) {
  const settings = disposableDatabaseSettings('Prompt 02 chat overlap checks');
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
  const settings = disposableDatabaseSettings('Prompt 02 chat overlap checks');
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

async function prepareIntent(page, pathName, body) {
  if (pathName === '/api/message_requests.php' || (pathName === '/api/safety.php' && ['block', 'unblock'].includes(body.action))) {
    const query = body.match_id ? `match_id=${body.match_id}` : `user_id=${body.user_id}`;
    const current = await page.evaluate(async url => { const response=await fetch(url,{headers:{Accept:'application/json'}});return {status:response.status,body:await response.json()}; }, `/api/pairs.php?${query}`);
    body = { ...body, pair_version: current.status === 200 ? current.body.data.pair_version : 0,
      idempotency_key: require('node:crypto').randomBytes(32).toString('hex') };
  }
  return body;
}

async function api(page, pathName, csrfToken, body, barrier = null, pointExpected = 2, prepared = false) {
  if (!prepared) body = await prepareIntent(page, pathName, body);
  return page.evaluate(
    async ({ pathName: url, csrfToken: token, body: payload, barrier, pointExpected }) => {
      const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
      };
      if (barrier !== null) {
        headers['X-Bushisa-Test-Barrier'] = barrier;
        headers['X-Bushisa-Test-Barrier-Expected'] = String(pointExpected);
        headers['X-Bushisa-Test-Barrier-Timeout-Ms'] = '10000';
      }
      const startedAt = performance.now();
      const response = await fetch(url, {
        method: 'POST',
        headers,
        body: JSON.stringify(payload),
      });
      const completedAt = performance.now();
      let parsed = null;
      try {
        parsed = await response.json();
      } catch {
        parsed = null;
      }
      return { status: response.status, body: parsed, startedAt, completedAt };
    },
    { pathName, csrfToken, body, barrier, pointExpected }
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
    .map((name) => JSON.parse(fs.readFileSync(path.join(directory, name), 'utf8')))
    .map((participant) => ({
      pid: participant.pid,
      point: participant.point,
      entered_at: participant.entered_at,
      uri: participant.uri,
    }))
    .sort((left, right) => String(left.entered_at).localeCompare(String(right.entered_at)));
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

test.describe.serial('Prompt 02 chat HTTP overlap checks', () => {
  test.setTimeout(180_000);

  test.beforeEach(() => {
    requireOverlapRuntime();
    resetDatabase();
  });

  test('P02-RACE-CHAT-01 overlapping approve and revoke enforce versions and refreshed revoke removes the grant', async ({ browser }, testInfo) => {
    const requesterStudent = await createActiveStudent(browser, 'RaceReq', 'male');
    const recipientStudent = await createActiveStudent(browser, 'RaceRec', 'female');
    const matchId = createMatch(requesterStudent.id, recipientStudent.id);
    const requester = await loginForApi(browser, requesterStudent);
    const recipient = await loginForApi(browser, recipientStudent);

    const request = await api(requester.page, '/api/message_requests.php', requester.csrfToken, {
      match_id: matchId,
      action: 'request',
    });
    expect(request.status).toBe(200);

    const barrierId = `chat-request-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
    // Complete read preparation before either POST waits at the rendezvous.
    // Otherwise php -S may queue the peer's GET behind the waiting POST.
    const approveIntent = await prepareIntent(recipient.page, '/api/message_requests.php', { match_id: matchId, action: 'approve' });
    const revokeIntent = await prepareIntent(requester.page, '/api/message_requests.php', { match_id: matchId, action: 'revoke' });
    const [approve, revoke] = await Promise.all([
      api(recipient.page, '/api/message_requests.php', recipient.csrfToken, approveIntent, barrierId, 2, true),
      api(requester.page, '/api/message_requests.php', requester.csrfToken, revokeIntent, barrierId, 2, true),
    ]);

    expect([200, 409]).toContain(approve.status);
    expect([200, 409]).toContain(revoke.status);
    expect([approve.status, revoke.status].filter(status => status === 200)).toHaveLength(1);
    if (revoke.status === 409) {
      const refreshedRevoke = await api(requester.page, '/api/message_requests.php', requester.csrfToken, { match_id: matchId, action: 'revoke' });
      expect(refreshedRevoke.status).toBe(200);
    }
    expect(queryValue(`SELECT status FROM message_requests WHERE match_id = ${matchId} ORDER BY id DESC LIMIT 1`)).toBe('revoked');
    expect(Number(queryValue(`SELECT COUNT(*) FROM notifications WHERE deep_link = 'chat.php?match_id=${matchId}'`))).toBe(0);

    const participants = barrierParticipants(barrierId, 'message_request_before_mutation');
    expect(participants).toHaveLength(2);
    expect(new Set(participants.map((participant) => participant.pid)).size).toBe(2);
    await testInfo.attach('chat-request-overlap-barrier', {
      body: JSON.stringify({ barrierId, participants, approve, revoke }, null, 2),
      contentType: 'application/json',
    });
    console.log(`HTTP chat request barrier ${barrierId} participants=${participants.map((participant) => participant.pid).join(',')}`);

    await requester.context.close();
    await recipient.context.close();
  });

  test('P02-RACE-CHAT-02 overlapping send and block close the match and prevent later messages', async ({ browser }, testInfo) => {
    const senderStudent = await createActiveStudent(browser, 'RaceSend', 'male');
    const blockerStudent = await createActiveStudent(browser, 'RaceBlock', 'female');
    const matchId = createMatch(senderStudent.id, blockerStudent.id);
    const sender = await loginForApi(browser, senderStudent);
    const blocker = await loginForApi(browser, blockerStudent);
    await approveChat(sender, blocker, matchId);

    const barrierId = `chat-block-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
    const blockIntent = await prepareIntent(blocker.page, '/api/safety.php', { action: 'block', user_id: senderStudent.id });
    const [send, block] = await Promise.all([
      api(sender.page, '/api/messages.php', sender.csrfToken, {
        match_id: matchId,
        body: 'overlap send body',
      }, barrierId),
      api(blocker.page, '/api/safety.php', blocker.csrfToken, blockIntent, barrierId, 2, true),
    ]);

    expect([200, 403]).toContain(send.status);
    expect(block.status).toBe(200);
    expect(queryValue(`SELECT match_status FROM matches WHERE id = ${matchId}`)).toBe('blocked');
    expect(queryValue(`SELECT status FROM message_requests WHERE match_id = ${matchId} ORDER BY id DESC LIMIT 1`)).toBe('approved');
    expect(Number(queryValue(`SELECT chat_suppressed FROM pair_policy_history WHERE match_id = ${matchId}`))).toBe(1);
    expect(Number(queryValue(`SELECT COUNT(*) FROM protective_replay_events WHERE event_type = 'match_closed' AND subject_id = ${matchId}`))).toBe(1);
    expect(Number(queryValue(`SELECT COUNT(*) FROM messages WHERE match_id = ${matchId}`))).toBeLessThanOrEqual(1);
    expect(Number(queryValue(`SELECT COUNT(*) FROM notifications WHERE deep_link = 'chat.php?match_id=${matchId}'`))).toBe(0);

    const laterSend = await api(sender.page, '/api/messages.php', sender.csrfToken, {
      match_id: matchId,
      body: 'later forbidden body',
    });
    expect(laterSend.status).toBe(403);
    expect(Number(queryValue(`SELECT COUNT(*) FROM messages WHERE match_id = ${matchId}`))).toBeLessThanOrEqual(1);

    const participants = barrierParticipants(barrierId, 'chat_relationship_mutation_before_update');
    expect(participants).toHaveLength(2);
    expect(new Set(participants.map((participant) => participant.pid)).size).toBe(2);
    await testInfo.attach('chat-block-overlap-barrier', {
      body: JSON.stringify({ barrierId, participants, send, block }, null, 2),
      contentType: 'application/json',
    });
    console.log(`HTTP chat block barrier ${barrierId} participants=${participants.map((participant) => participant.pid).join(',')}`);

    await sender.context.close();
    await blocker.context.close();
  });
});
