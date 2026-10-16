<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';

function migration_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(80) PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function migration_applied(PDO $pdo, string $version): bool
{
    migration_table($pdo);
    $statement = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
    $statement->execute([':version' => $version]);

    return $statement->fetchColumn() !== false;
}

function mark_migration_applied(PDO $pdo, string $version): void
{
    migration_table($pdo);
    $statement = $pdo->prepare('INSERT IGNORE INTO schema_migrations (version) VALUES (:version)');
    $statement->execute([':version' => $version]);
}

function apply_operational_contract_migration(PDO $pdo): void
{
    $previous = getenv('BUSHISA_ALLOW_RUNTIME_DDL');
    putenv('BUSHISA_ALLOW_RUNTIME_DDL=true');
    ensure_operational_schema($pdo);
    if ($previous === false) {
        putenv('BUSHISA_ALLOW_RUNTIME_DDL');
    } else {
        putenv('BUSHISA_ALLOW_RUNTIME_DDL=' . $previous);
    }
}

$migrationDir = __DIR__ . '/../database/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];
sort($files);
$through = getopt('', ['through:'])['through'] ?? null;
if ($through !== null && (!is_string($through) || !preg_match('/\A[0-9]{3}\z/', $through))) {
    throw new InvalidArgumentException('--through requires a three-digit migration prefix');
}

foreach ($files as $file) {
    $name = basename($file);
    if ($through !== null && substr($name, 0, 3) > $through) {
        continue;
    }
    $version = pathinfo($name, PATHINFO_FILENAME);

    if (migration_applied($pdo, $version)) {
        fwrite(STDOUT, "Skipping {$name}\n");
        continue;
    }

    if ($name === '001_operational_contract.sql') {
        fwrite(STDOUT, "Applying {$name}\n");
        apply_operational_contract_migration($pdo);
        mark_migration_applied($pdo, $version);
        continue;
    }

    if ($name === '002_policy_safeguards.sql') {
        fwrite(STDOUT, "Applying {$name}\n");
        apply_operational_contract_migration($pdo);
        mark_migration_applied($pdo, $version);
        continue;
    }

    // The runtime schema helper supports both a fresh install and upgrades.
    // Earlier migrations can already have added these columns during bootstrap.
    if ($name === '004_smtp_outbox.sql') {
        fwrite(STDOUT, "Applying {$name}\n");
        apply_operational_contract_migration($pdo);
        $index = $pdo->query("SHOW INDEX FROM mail_outbox WHERE Key_name = 'idx_mail_outbox_dispatch'");
        if ($index->fetch() === false) {
            $pdo->exec('CREATE INDEX idx_mail_outbox_dispatch ON mail_outbox (provider, status, next_attempt_at, expires_at)');
        }
        mark_migration_applied($pdo, $version);
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "Skipping empty migration {$name}\n");
        continue;
    }

    fwrite(STDOUT, "Applying {$name}\n");
    $pdo->exec($sql);
    if ($name === '006_report_intake.sql') {
        require_once __DIR__ . '/../php/report_migration.php';
        bushisa_migrate_report_revisions($pdo);
    }
    mark_migration_applied($pdo, $version);
}

fwrite(STDOUT, "Migrations complete\n");
