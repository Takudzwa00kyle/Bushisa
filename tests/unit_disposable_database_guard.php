<?php

declare(strict_types=1);

require_once __DIR__ . '/support/disposable_database.php';

function check_guard(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function reject_guard(callable $action, string $message): void
{
    try {
        $action();
    } catch (Throwable $exception) {
        return;
    }

    throw new RuntimeException($message);
}

foreach (['APP_ENV', 'BUSHISA_TEST_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
    'DB_PASS', 'AZURE_MYSQL_HOST', 'AZURE_MYSQL_DBNAME', 'AZURE_MYSQL_USERNAME', 'AZURE_MYSQL_PASSWORD',
    'BUSHISA_ALLOW_REMOTE_TEST_DATABASE'] as $name) {
    putenv($name);
}

reject_guard(
    fn () => bushisa_require_disposable_test_database('unit guard'),
    'missing explicit opt-in and database must fail'
);

putenv('APP_ENV=production');
putenv('BUSHISA_TEST_DATABASE=true');
putenv('DB_NAME=bushisa_test_unit');
reject_guard(
    fn () => bushisa_require_disposable_test_database('unit guard'),
    'production environment must fail even with test opt-in'
);

putenv('APP_ENV=development');
putenv('DB_NAME=bushisa_db');
reject_guard(
    fn () => bushisa_require_disposable_test_database('unit guard'),
    'ambiguous default database name must fail'
);

putenv('DB_NAME=bushisa_test_unit');
putenv('DB_HOST=example.mysql.database.azure.com');
reject_guard(
    fn () => bushisa_require_disposable_test_database('unit guard'),
    'remote host must require a second explicit opt-in'
);

putenv('DB_HOST=127.0.0.1');
$settings = bushisa_require_disposable_test_database('unit guard');
check_guard($settings['name'] === 'bushisa_test_unit', 'valid disposable database should pass');

$rewritten = bushisa_schema_for_disposable_database(
    "CREATE DATABASE IF NOT EXISTS bushisa_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE bushisa_db;\n",
    'bushisa_test_unit'
);
check_guard(str_contains($rewritten, 'CREATE DATABASE IF NOT EXISTS `bushisa_test_unit`'), 'schema create target should be rewritten');
check_guard(str_contains($rewritten, 'USE `bushisa_test_unit`;'), 'schema use target should be rewritten');

reject_guard(
    fn () => bushisa_schema_for_disposable_database('DROP DATABASE production_db;', 'bushisa_test_unit'),
    'schema must not be able to drop another database'
);

fwrite(STDOUT, "unit_disposable_database_guard PASS\n");
