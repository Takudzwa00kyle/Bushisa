<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database_settings.php';

function bushisa_require_disposable_test_database(string $caller): array
{
    $errors = [];
    $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'development'));
    if ($appEnv === 'production') {
        $errors[] = 'APP_ENV must not be production.';
    }
    if (getenv('BUSHISA_TEST_DATABASE') !== 'true') {
        $errors[] = 'BUSHISA_TEST_DATABASE=true is required.';
    }

    $explicitName = getenv('DB_NAME');
    if ($explicitName === false || $explicitName === '') {
        $explicitName = getenv('AZURE_MYSQL_DBNAME');
    }
    if ($explicitName === false || $explicitName === '') {
        $errors[] = 'DB_NAME or AZURE_MYSQL_DBNAME must explicitly name the target database.';
        $explicitName = '';
    }

    $settings = bushisa_database_settings();
    $databaseName = (string) $settings['name'];
    if ($databaseName !== $explicitName) {
        $errors[] = 'Resolved database name must match the explicit test target.';
    }
    if (!preg_match('/\Abushisa_(?:test|ci|tmp|synthetic|p0)(?:_[a-z0-9]+)*\z/i', $databaseName)) {
        $errors[] = 'Database name must be a clearly disposable Bushisa test target.';
    }

    $host = strtolower(trim((string) $settings['host']));
    $localHosts = ['127.0.0.1', 'localhost', '::1'];
    if (!in_array($host, $localHosts, true) && getenv('BUSHISA_ALLOW_REMOTE_TEST_DATABASE') !== 'true') {
        $errors[] = 'Remote test databases require BUSHISA_ALLOW_REMOTE_TEST_DATABASE=true.';
    }

    if ($errors !== []) {
        throw new RuntimeException($caller . ' requires a disposable database: ' . implode(' ', $errors));
    }

    return $settings;
}

function bushisa_quote_disposable_identifier(string $identifier): string
{
    if (!preg_match('/\A[A-Za-z0-9_]+\z/', $identifier)) {
        throw new RuntimeException('Disposable database identifier is invalid.');
    }

    return '`' . $identifier . '`';
}

function bushisa_schema_for_disposable_database(string $schema, string $databaseName): string
{
    $quoted = bushisa_quote_disposable_identifier($databaseName);
    $schema = preg_replace(
        '/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?[A-Za-z0-9_]+`?\s+CHARACTER\s+SET\s+utf8mb4\s+COLLATE\s+utf8mb4_unicode_ci\s*;/i',
        'CREATE DATABASE IF NOT EXISTS ' . $quoted . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
        $schema
    ) ?? $schema;
    $schema = preg_replace('/USE\s+`?[A-Za-z0-9_]+`?\s*;/i', 'USE ' . $quoted . ';', $schema) ?? $schema;

    if (preg_match_all('/(?:CREATE\s+DATABASE(?:\s+IF\s+NOT\s+EXISTS)?|USE)\s+`?([A-Za-z0-9_]+)`?/i', $schema, $matches)) {
        foreach ($matches[1] as $selectedName) {
            if (strcasecmp($selectedName, $databaseName) !== 0) {
                throw new RuntimeException('Bootstrap schema selects an unrelated database.');
            }
        }
    }

    if (preg_match_all('/DROP\s+DATABASE(?:\s+IF\s+EXISTS)?\s+`?([A-Za-z0-9_]+)`?/i', $schema, $matches)) {
        foreach ($matches[1] as $selectedName) {
            if (strcasecmp($selectedName, $databaseName) !== 0) {
                throw new RuntimeException('Bootstrap schema drops an unrelated database.');
            }
        }
    }

    return $schema;
}

function bushisa_disposable_database_pdo_options(array $settings): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (($settings['require_tls'] ?? false) === true) {
        if (class_exists(\Pdo\Mysql::class)) {
            $options[\Pdo\Mysql::ATTR_SSL_CA] = $settings['ssl_ca'];
            $options[\Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = true;
        } else {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $settings['ssl_ca'];
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }
    }

    return $options;
}

function bushisa_bootstrap_disposable_database(string $schemaPath): void
{
    $settings = bushisa_require_disposable_test_database('Database bootstrap');
    $databaseName = (string) $settings['name'];
    $schema = file_get_contents($schemaPath);
    if ($schema === false) {
        throw new RuntimeException('Could not read bootstrap schema.');
    }
    $schema = bushisa_schema_for_disposable_database($schema, $databaseName);

    $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $settings['host'], $settings['port']);
    $pdo = new PDO($serverDsn, $settings['user'], $settings['password'], bushisa_disposable_database_pdo_options($settings));
    $quoted = bushisa_quote_disposable_identifier($databaseName);
    $pdo->exec('DROP DATABASE IF EXISTS ' . $quoted);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $quoted . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE ' . $quoted);
    $pdo->exec($schema);
}
