<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Only the pre-existing signup challenge/payload hygiene belongs to the
// transactional-mail schedule. No accounts, ordinary chats, evidence, pair
// ledgers, files, metadata recovery receipts or sessions are purged here.
require_once __DIR__ . '/../php/signup_verification.php';
try {
    if (bushisa_mail_provider() !== 'smtp' || getenv('MAIL_TRANSPORT') === 'fake') {
        throw new RuntimeException('SMTP worker only');
    }
    require __DIR__ . '/../config/db.php';
    ensure_operational_schema($pdo);
    $expired = bushisa_cleanup_expired_signup_codes($pdo);
    $payloads = bushisa_signup_cleanup_mail($pdo);
    fwrite(STDOUT, json_encode([
        'expired_codes_terminalized' => $expired,
        'code_bearing_payloads_erased' => $payloads,
        'policy_cleanup' => 'gated',
    ], JSON_THROW_ON_ERROR) . PHP_EOL);
} catch (Throwable) {
    fwrite(STDERR, "MAIL_CLEANUP_FAILED: check migrations, configuration and database access.\n");
    exit(1);
}
