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
  const settings = disposableDatabaseSettings('P0 rendered workflow reset');
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
  const settings = disposableDatabaseSettings('P0 rendered workflow reset');
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

function makeStudent() {
  const suffix = Math.floor(Math.random() * 100000).toString().padStart(5, '0');
  const letter = String.fromCharCode(97 + Math.floor(Math.random() * 26));
  const studentId = `n025${suffix}${letter}`;
  return {
    studentId,
    email: `${studentId}@students.nust.ac.zw`,
    displayName: `Test ${suffix}`,
    handle: `p0_${suffix}_${letter}`,
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

test.describe.serial('P0 rendered workflows', () => {
  let activeStudent;

  test.beforeAll(() => {
    resetDatabase();
  });

  test('registration, code entry, DOB onboarding, and login/logout render correctly', async ({ page }) => {
    activeStudent = makeStudent();
    await registerStudent(page, activeStudent);
    await approveEmail(page, activeStudent);
    await login(page, activeStudent);
    await completeAdultOnboarding(page);

    await expect(page.getByRole('heading', { name: 'Bushisa' })).toBeVisible();
    await page.goto('/logout.php');
    await expect(page).toHaveURL(/\/login\.php$/);
    await expect(page.getByRole('heading', { name: 'Welcome back to Bushisa' })).toBeVisible();
  });

  test('pending accounts are restricted to email approval or DOB onboarding', async ({ page }) => {
    const pendingEmailStudent = makeStudent();
    await registerStudent(page, pendingEmailStudent);
    await login(page, pendingEmailStudent);
    await expect(page).toHaveURL(/\/verify_email\.php$/);
    await page.goto('/discover.php');
    await expect(page).toHaveURL(/\/verify_email\.php$/);
    await page.goto('/logout.php');
    await expect(page).toHaveURL(/\/login\.php$/);

    const pendingDobStudent = makeStudent();
    await registerStudent(page, pendingDobStudent);
    await approveEmail(page, pendingDobStudent);
    await login(page, pendingDobStudent);
    await expect(page).toHaveURL(/\/onboarding\.php$/);
    await page.goto('/confessions.php');
    await expect(page).toHaveURL(/\/onboarding\.php$/);
  });

  test('confession timestamps use persisted UTC values after post and reload', async ({ page }) => {
    if (!activeStudent) {
      activeStudent = makeStudent();
      await registerStudent(page, activeStudent);
      await approveEmail(page, activeStudent);
      await login(page, activeStudent);
      await completeAdultOnboarding(page);
    } else {
      await login(page, activeStudent);
    }

    const body = `Rendered timestamp check ${Date.now()}`;
    await page.goto('/confessions.php');
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    expect(csrfToken).toBeTruthy();
    const postResult = await page.evaluate(
      async ({ token, body }) => {
        const response = await fetch('/api/confessions.php', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-Token': token,
          },
          body: JSON.stringify({ action: 'create', body }),
        });
        return { status: response.status, payload: await response.json() };
      },
      { token: csrfToken, body }
    );
    expect(postResult.status).toBe(200);
    expect(postResult.payload.success).toBe(true);
    const confessionId = queryValue(
      `SELECT id FROM confessions WHERE body = ${sqlString(body)} ORDER BY id DESC LIMIT 1`
    );

    await page.goto('/confessions.php');
    const card = page.locator('.confession-card').filter({ hasText: body }).first();
    await expect(card).toBeVisible();
    const time = card.locator('time');
    await expect(time).toHaveAttribute('datetime', /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00/);

    run('mysql', [
      ...mysqlArgs(true),
      '--execute',
      `UPDATE confessions SET created_at = UTC_TIMESTAMP() - INTERVAL 2 HOUR WHERE id = ${Number(confessionId)}`,
    ]);

    await page.goto('/confessions.php');
    const updatedCard = page.locator('.confession-card').filter({ hasText: body }).first();
    await expect(updatedCard.locator('time')).toContainText(/[1-6] hours ago|1 hour ago/);
    await expect(updatedCard.locator('time')).not.toContainText('Just now');
  });
});
