const { spawnSync } = require('node:child_process');

const clearedKeys = [
  'APP_ENV',
  'APP_DEBUG',
  'BUSHISA_ALLOW_RUNTIME_DDL',
  'BUSHISA_TEST_DATABASE',
  'BUSHISA_ALLOW_REMOTE_TEST_DATABASE',
  'BUSHISA_EXTERNAL_TEST_SERVER',
  'BUSHISA_ALLOW_EXTERNAL_TEST_SERVER',
  'MAIL_PROVIDER',
  'PLAYWRIGHT_BASE_URL',
  'DB_HOST',
  'DB_PORT',
  'DB_NAME',
  'DB_USER',
  'DB_PASSWORD',
  'DB_PASS',
  'AZURE_MYSQL_HOST',
  'AZURE_MYSQL_PORT',
  'AZURE_MYSQL_DBNAME',
  'AZURE_MYSQL_USERNAME',
  'AZURE_MYSQL_PASSWORD',
];

function childEnv(overrides = {}) {
  const env = { ...process.env };
  for (const key of clearedKeys) {
    delete env[key];
  }
  return { ...env, ...overrides };
}

function loadConfig(overrides = {}) {
  return spawnSync(process.execPath, ['-e', 'require("./playwright.config.js");'], {
    cwd: process.cwd(),
    env: childEnv(overrides),
    encoding: 'utf8',
  });
}

function expectFailure(name, overrides, expectedText) {
  const result = loadConfig(overrides);
  if (result.status === 0) {
    throw new Error(`${name} unexpectedly passed`);
  }
  const output = `${result.stdout}\n${result.stderr}`;
  if (!output.includes(expectedText)) {
    throw new Error(`${name} failed with unexpected output: ${output}`);
  }
}

function expectPass(name, overrides) {
  const result = loadConfig(overrides);
  if (result.status !== 0) {
    throw new Error(`${name} unexpectedly failed: ${result.stdout}\n${result.stderr}`);
  }
}

const valid = {
  APP_ENV: 'development',
  APP_DEBUG: 'false',
  BUSHISA_ALLOW_RUNTIME_DDL: 'false',
  BUSHISA_TEST_DATABASE: 'true',
  MAIL_PROVIDER: 'fake',
  DB_HOST: '127.0.0.1',
  DB_PORT: '3306',
  DB_NAME: 'bushisa_test_unit',
  DB_USER: 'bushisa_test_unit',
  PLAYWRIGHT_BASE_URL: 'http://127.0.0.1:8097',
};

expectFailure('missing opt-in', {}, 'BUSHISA_TEST_DATABASE=true is required.');
expectFailure('production env', { ...valid, APP_ENV: 'production' }, 'APP_ENV must be development.');
expectFailure('missing database name', { ...valid, DB_NAME: '' }, 'DB_NAME or AZURE_MYSQL_DBNAME must explicitly name the target database.');
expectFailure('ambiguous database name', { ...valid, DB_NAME: 'bushisa_db' }, 'Database name must be a clearly disposable Bushisa test target.');
expectFailure('debug mode', { ...valid, APP_DEBUG: 'true' }, 'APP_DEBUG=false is required.');
expectFailure('runtime DDL', { ...valid, BUSHISA_ALLOW_RUNTIME_DDL: 'true' }, 'BUSHISA_ALLOW_RUNTIME_DDL=false is required.');
expectFailure('real mail provider', { ...valid, MAIL_PROVIDER: 'smtp' }, 'MAIL_PROVIDER=fake');
expectFailure('external server without opt-in', {
  ...valid,
  BUSHISA_EXTERNAL_TEST_SERVER: 'true',
  PLAYWRIGHT_BASE_URL: 'http://192.0.2.10:8097',
}, 'BUSHISA_ALLOW_EXTERNAL_TEST_SERVER=true');
expectPass('valid loopback browser harness', valid);

console.log('unit_playwright_config_guard PASS');
