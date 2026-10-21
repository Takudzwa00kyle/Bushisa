'use strict';
const fs = require('node:fs');
const path = require('node:path');
const https = require('node:https');
const http = require('node:http');
const root = fs.realpathSync(process.env.BUSHISA_STAGE_STORAGE_ROOT || '');
const run = process.env.BUSHISA_STAGE_RUN_ID;
if (process.env.BUSHISA_STAGE_LOCAL_PROXY !== 'true' || path.basename(root) !== `stage-${run}`
  || fs.readFileSync(path.join(root, '.bushisa-stage'), 'utf8') !== run
  || process.env.APP_BASE_URL !== `https://stage-${run}.example.test`) throw new Error('Local proxy refused');
// Test ingress only: no remote listener, proxy target, logging or captured bodies.
https.createServer({ key: fs.readFileSync(path.join(root, 'tls.key')), cert: fs.readFileSync(path.join(root, 'tls.crt')) }, (req, res) => {
  if (req.headers.host !== `stage-${run}.example.test` || /^\/(?:\.git|\.tmp|bin|tests|config|database|deploy|docs|vendor)(?:\/|$)/.test(req.url)) { res.writeHead(404); res.end(); return; }
  const upstream = http.request({ hostname: '127.0.0.1', port: 8197, method: req.method, path: req.url,
    headers: { ...req.headers, host: '127.0.0.1:8197' } }, result => { res.writeHead(result.statusCode, result.headers); result.pipe(res); });
  upstream.on('error', () => { res.writeHead(503); res.end(); }); req.pipe(upstream);
}).listen(443, '127.0.0.1');
