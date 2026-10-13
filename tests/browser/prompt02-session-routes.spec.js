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
  const settings = disposableDatabaseSettings('Prompt 02 browser route checks');
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
  const settings = disposableDatabaseSettings('Prompt 02 browser route checks');
  const schema = schemaForDisposableDatabase(fs.readFileSync(path.join(repoRoot, 'database', 'bushisa.sql'), 'utf8'), settings.name);
  run('mysql', mysqlArgs(false), {
    input: `DROP DATABASE IF EXISTS ${quoteIdentifier(settings.name)};\n`,
  });
  run('mysql', mysqlArgs(false), { input: schema });
  run('php', ['bin/migrate.php']);
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

function makeStudent() {
  const suffix = Math.floor(Math.random() * 100000).toString().padStart(5, '0');
  const letter = String.fromCharCode(97 + Math.floor(Math.random() * 26));
  const studentId = `n025${suffix}${letter}`;
  return {
    studentId,
    email: `${studentId}@students.nust.ac.zw`,
    displayName: `Prompt ${suffix}`,
    handle: `p02_${suffix}_${letter}`,
  };
}

async function registerStudent(page, student) {
  await page.goto('/register.php');
  await page.getByLabel('NUST student number').fill(student.studentId);
  await page.getByLabel('Student email').fill(student.email);
  await page.getByLabel('Password').fill(password);
  await page.getByLabel('Display name').fill(student.displayName);
  await page.getByLabel('Anonymous handle').fill(student.handle);
  await page.getByLabel('Gender').selectOption('male');
  await page.getByLabel('Faculty').selectOption('Applied Sciences');
  await page.getByLabel('Year of study').selectOption('Part 2');
  await page.getByRole('button', { name: 'Create account' }).click();
  await expect(page.getByRole('status')).toContainText('Account created. Your approval email is on its way.');
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

async function createActiveStudent(browser) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const student = makeStudent();
  await registerStudent(page, student);
  await approveEmail(page, student);
  await login(page, student);
  await completeAdultOnboarding(page);
  await context.close();
  return student;
}

async function loginInNewContext(browser, student) {
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page, student);
  await expect(page).toHaveURL(/\/discover\.php$/);
  return { context, page };
}

test.describe.serial('Prompt 02 route and session checks', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('two independent sessions lose page and API access after account revocation', async ({ browser }) => {
    const student = await createActiveStudent(browser);
    const first = await loginInNewContext(browser, student);
    const second = await loginInNewContext(browser, student);

    await expect(first.page.getByRole('heading', { name: 'Bushisa' })).toBeVisible();
    await expect(second.page.getByRole('heading', { name: 'Bushisa' })).toBeVisible();

    executeSql(
      `UPDATE users
       SET is_suspended = 1,
           is_active = 0,
           account_status = 'suspended',
           session_version = session_version + 1
       WHERE email = ${sqlString(student.email)}`
    );

    await first.page.goto('/discover.php');
    await expect(first.page).toHaveURL(/\/login\.php$/);
    await expect(first.page.getByRole('heading', { name: 'Welcome back to Bushisa' })).toBeVisible();

    const apiStatus = await second.page.evaluate(async () => {
      const response = await fetch('/api/discover.php', {
        headers: {
          Accept: 'application/json',
        },
      });
      return response.status;
    });
    expect(apiStatus).toBe(401);

    const forbiddenSwipeWrites = Number(queryValue(
      `SELECT COUNT(*)
       FROM swipes s
       INNER JOIN users u ON u.id = s.swiper_id
       WHERE u.email = ${sqlString(student.email)}`
    ));
    expect(forbiddenSwipeWrites).toBe(0);

    await first.context.close();
    await second.context.close();
  });
});
