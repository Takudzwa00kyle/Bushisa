const path = require('node:path');

const allowedDatabaseName = /^bushisa_(?:test|ci|tmp|synthetic|p0)(?:_[a-z0-9]+)*$/i;

function disposableDatabaseSettings(caller) {
  const errors = [];
  const appEnv = String(process.env.APP_ENV || 'development').toLowerCase();
  if (appEnv === 'production') {
    errors.push('APP_ENV must not be production.');
  }
  if (process.env.BUSHISA_TEST_DATABASE !== 'true') {
    errors.push('BUSHISA_TEST_DATABASE=true is required.');
  }

  const explicitName = process.env.DB_NAME || process.env.AZURE_MYSQL_DBNAME || '';
  if (!explicitName) {
    errors.push('DB_NAME or AZURE_MYSQL_DBNAME must explicitly name the target database.');
  }
  if (explicitName && !allowedDatabaseName.test(explicitName)) {
    errors.push('Database name must be a clearly disposable Bushisa test target.');
  }

  const host = process.env.DB_HOST || process.env.AZURE_MYSQL_HOST || '127.0.0.1';
  const localHosts = new Set(['127.0.0.1', 'localhost', '::1']);
  if (!localHosts.has(String(host).toLowerCase()) && process.env.BUSHISA_ALLOW_REMOTE_TEST_DATABASE !== 'true') {
    errors.push('Remote test databases require BUSHISA_ALLOW_REMOTE_TEST_DATABASE=true.');
  }

  if (errors.length > 0) {
    throw new Error(`${caller} requires a disposable database: ${errors.join(' ')}`);
  }

  return {
    host,
    port: process.env.DB_PORT || process.env.AZURE_MYSQL_PORT || '3306',
    user: process.env.DB_USER || process.env.AZURE_MYSQL_USERNAME || 'root',
    password: process.env.DB_PASSWORD || process.env.DB_PASS || process.env.AZURE_MYSQL_PASSWORD || '',
    name: explicitName,
  };
}

function quoteIdentifier(identifier) {
  if (!/^[A-Za-z0-9_]+$/.test(identifier)) {
    throw new Error('Disposable database identifier is invalid.');
  }
  return `\`${identifier}\``;
}

function schemaForDisposableDatabase(schema, databaseName) {
  const quoted = quoteIdentifier(databaseName);
  let rewritten = schema.replace(
    /CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?[A-Za-z0-9_]+`?\s+CHARACTER\s+SET\s+utf8mb4\s+COLLATE\s+utf8mb4_unicode_ci\s*;/i,
    `CREATE DATABASE IF NOT EXISTS ${quoted} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
  );
  rewritten = rewritten.replace(/USE\s+`?[A-Za-z0-9_]+`?\s*;/i, `USE ${quoted};`);

  for (const match of rewritten.matchAll(/(?:CREATE\s+DATABASE(?:\s+IF\s+NOT\s+EXISTS)?|USE)\s+`?([A-Za-z0-9_]+)`?/gi)) {
    if (match[1].toLowerCase() !== databaseName.toLowerCase()) {
      throw new Error('Bootstrap schema selects an unrelated database.');
    }
  }
  for (const match of rewritten.matchAll(/DROP\s+DATABASE(?:\s+IF\s+EXISTS)?\s+`?([A-Za-z0-9_]+)`?/gi)) {
    if (match[1].toLowerCase() !== databaseName.toLowerCase()) {
      throw new Error('Bootstrap schema drops an unrelated database.');
    }
  }

  return rewritten;
}

function redactedCommand(command, args) {
  const safeArgs = args.map((arg) => {
    if (arg === '--password' || arg.startsWith('--password=')) {
      return '--password=<redacted>';
    }
    return arg;
  });
  return `${path.basename(command)} ${safeArgs.join(' ')}`;
}

module.exports = {
  disposableDatabaseSettings,
  quoteIdentifier,
  schemaForDisposableDatabase,
  redactedCommand,
};
