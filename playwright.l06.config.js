const path = require('node:path');
const crypto = require('node:crypto');
const { defineConfig } = require('@playwright/test');
const { disposableDatabaseSettings } = require('./tests/support/disposable-database');

const db = disposableDatabaseSettings('L06 browser acceptance');
if (!['127.0.0.1', 'localhost', '::1'].includes(db.host) || !/^bushisa_test_q11_/.test(db.name)) {
  throw new Error('L06 browser acceptance requires a loopback Q11 disposable database.');
}
for (const flag of ['BUSHISA_Q11_SYNTHETIC', 'BUSHISA_Q11_QUEUE_FAKE', 'BUSHISA_PASSWORD_RESET_SYNTHETIC', 'PASSWORD_RESET_ENABLED']) {
  if (process.env[flag] !== 'true') throw new Error(`Explicit synthetic flag required: ${flag}`);
}
if (process.env.MAIL_PROVIDER !== 'fake' || process.env.MAIL_TRANSPORT !== 'fake') throw new Error('Fake mail is required.');
for (const setting of ['PASSWORD_RESET_RESEND_COOLDOWN_SECONDS', 'PASSWORD_RESET_MAX_REQUESTS_PER_HOUR', 'PASSWORD_RESET_MAX_REQUESTS_PER_DAY']) {
  if (!/^[1-9][0-9]*$/.test(process.env[setting] || '')) throw new Error(`Explicit test throttle required: ${setting}`);
}
const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8196';
const url = new URL(baseURL);
if (url.protocol !== 'http:' || url.hostname !== '127.0.0.1' || url.pathname !== '/' || url.search || url.hash) throw new Error('L06 owned loopback URL required.');
const runDir = process.env.L06_BROWSER_RUN_DIR || path.join(__dirname, '.tmp', `l06-browser-${Date.now()}-${crypto.randomBytes(4).toString('hex')}`);
Object.assign(process.env, {
  APP_BASE_URL: baseURL,
  MAIL_OUTBOX_KEY: process.env.MAIL_OUTBOX_KEY || crypto.randomBytes(32).toString('base64'),
  SIGNUP_CODE_HMAC_KEY: process.env.SIGNUP_CODE_HMAC_KEY || crypto.randomBytes(32).toString('hex'),
  MAIL_FROM_ADDRESS: 'synthetic@example.invalid',
  SESSION_COOKIE_SECURE: 'false',
  L06_BROWSER_RUN_DIR: runDir,
});
module.exports = defineConfig({
  testDir: './tests/browser', testMatch: 'l06-password-reset.spec.js',
  workers: 1, fullyParallel: false, timeout: 60000,
  reporter: [['./tests/support/l06_browser_reporter.js']],
  // Proofs appear in DOM and the intended POST. Never record those artifacts.
  use: { baseURL, trace: 'off', screenshot: 'off', video: 'off' },
  webServer: {
    command: 'node tests/support/l06_browser_server.js',
    url: `${baseURL}/css/style.css`, reuseExistingServer: false,
    env: process.env, timeout: 30000,
  },
});
