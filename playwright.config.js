const { defineConfig } = require('@playwright/test');

const dbEnv = {
  APP_ENV: process.env.APP_ENV || 'development',
  APP_DEBUG: process.env.APP_DEBUG || 'true',
  BUSHISA_ALLOW_RUNTIME_DDL: process.env.BUSHISA_ALLOW_RUNTIME_DDL || 'true',
  MAIL_PROVIDER: process.env.MAIL_PROVIDER || 'fake',
  DB_HOST: process.env.DB_HOST || '127.0.0.1',
  DB_PORT: process.env.DB_PORT || '3317',
  DB_NAME: process.env.DB_NAME || 'bushisa_db',
  DB_USER: process.env.DB_USER || 'root',
  DB_PASSWORD: process.env.DB_PASSWORD || '',
  DB_PASS: process.env.DB_PASS || '',
};

Object.assign(process.env, dbEnv);

module.exports = defineConfig({
  testDir: './tests/browser',
  fullyParallel: false,
  workers: 1,
  timeout: 60_000,
  use: {
    baseURL: 'http://127.0.0.1:8097',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'php -S 127.0.0.1:8097 -t .',
    url: 'http://127.0.0.1:8097/login.php',
    reuseExistingServer: false,
    timeout: 120_000,
  },
});

