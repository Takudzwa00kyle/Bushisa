const { defineConfig } = require('@playwright/test');
const crypto = require('node:crypto');
const { disposableDatabaseSettings } = require('./tests/support/disposable-database');

function requireFakeMail() {
  if (process.env.MAIL_PROVIDER !== 'fake') {
    throw new Error('Playwright browser harness requires MAIL_PROVIDER=fake.');
  }
}

function requireOwnedBaseUrl(baseURL, externalServer) {
  const parsed = new URL(baseURL);
  const localHosts = new Set(['127.0.0.1', 'localhost', '[::1]']);
  if (parsed.protocol !== 'http:') {
    throw new Error('Playwright browser harness requires an HTTP test URL.');
  }
  if (!localHosts.has(parsed.hostname) && !externalServer) {
    throw new Error('Playwright browser harness default server must use a loopback host.');
  }
  if (externalServer && process.env.BUSHISA_ALLOW_EXTERNAL_TEST_SERVER !== 'true') {
    throw new Error('External Playwright test servers require BUSHISA_ALLOW_EXTERNAL_TEST_SERVER=true.');
  }

  return parsed;
}

const settings = disposableDatabaseSettings('Playwright browser harness');
requireFakeMail();

const externalServer = process.env.BUSHISA_EXTERNAL_TEST_SERVER === 'true';
const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8097';
const parsedBaseURL = requireOwnedBaseUrl(baseURL, externalServer);
const serverEnv = {
  ...process.env,
  APP_ENV: 'development',
  APP_DEBUG: 'false',
  BUSHISA_ALLOW_RUNTIME_DDL: 'false',
  BUSHISA_TEST_DATABASE: 'true',
  MAIL_PROVIDER: 'fake',
  DB_HOST: settings.host,
  DB_PORT: settings.port,
  DB_NAME: settings.name,
  DB_USER: settings.user,
  DB_PASSWORD: settings.password,
  DB_PASS: '',
  REPORT_CONTEXT_KEYS: process.env.REPORT_CONTEXT_KEYS || JSON.stringify({ synthetic: crypto.randomBytes(32).toString('base64') }),
  REPORT_CONTEXT_ACTIVE_KEY: 'synthetic',
};
if (process.env.BUSHISA_TEST_HTTP_OVERLAP === 'true') {
  if (process.platform === 'win32' && !externalServer) {
    throw new Error('BUSHISA_TEST_HTTP_OVERLAP requires an isolated multi-worker Linux server or BUSHISA_EXTERNAL_TEST_SERVER=true.');
  }
  serverEnv.BUSHISA_TEST_HTTP_OVERLAP = 'true';
  serverEnv.PHP_CLI_SERVER_WORKERS = process.env.PHP_CLI_SERVER_WORKERS || '4';
}

const config = {
  testDir: './tests/browser',
  fullyParallel: false,
  workers: 1,
  timeout: 60_000,
  use: {
    baseURL,
    trace: 'retain-on-failure',
  },
};

if (!externalServer) {
  config.webServer = {
    command: `php -S ${parsedBaseURL.hostname}:${parsedBaseURL.port || '80'} -t .`,
    url: new URL('/css/style.css', baseURL).toString(),
    reuseExistingServer: false,
    timeout: 120_000,
    env: serverEnv,
  };
}

Object.assign(process.env, serverEnv);

module.exports = defineConfig(config);
