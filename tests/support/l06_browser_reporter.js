const fs = require('node:fs');
const path = require('node:path');
class Reporter {
  constructor() { this.cases = []; }
  onTestEnd(test, result) {
    const id = test.title.split(' ')[0];
    this.cases.push({ id, title: test.title, result: result.status, duration_ms: result.duration,
      annotations: test.annotations.filter(a => a.type === 'environment') });
    // No locator contents, navigation URLs, request bodies, screenshots or traces.
    console.log(`${id} ${result.status}`);
    if (result.status !== 'passed') {
      const message = String(result.error?.message || 'Test did not pass').replace(/[a-f0-9]{64}/gi, '<redacted>');
      console.log(message.split('\n').slice(0, 5).join('\n'));
    }
  }
  onEnd(result) {
    const output = path.resolve(process.env.L06_BROWSER_REPORT || 'test-results/l06-browser.json');
    fs.mkdirSync(path.dirname(output), { recursive: true });
    fs.writeFileSync(output, JSON.stringify({ suite: 'L06 rendered browser acceptance', completed_at_utc: new Date().toISOString(),
      environment: { node: process.version, os: process.platform, playwright: require('@playwright/test/package.json').version },
      status: result.status, cases: this.cases }, null, 2) + '\n');
  }
}
module.exports = Reporter;
