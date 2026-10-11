<?php

declare(strict_types=1);

// A worker is a CLI command, never a publicly callable email endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../php/signup_verification.php';

try {
    if (bushisa_mail_provider() !== 'smtp') {
        throw new RuntimeException('SMTP is not configured.');
    }
    bushisa_mail_outbox_key();
    bushisa_create_smtp_mailer(bushisa_smtp_settings());
    require __DIR__ . '/../config/db.php';
    ensure_operational_schema($pdo);
    $counts = bushisa_dispatch_mail($pdo);
    fwrite(STDOUT, json_encode($counts, JSON_THROW_ON_ERROR) . PHP_EOL);
    exit($counts['failed'] > 0 ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(STDERR, "Mail dispatch failed. Check SMTP configuration, dependencies, migrations and database access.\n");
    exit(1);
}
