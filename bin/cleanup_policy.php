<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Production recovery/authority adapters and the broader cleanup release gate
// remain unavailable. Refuse before loading database configuration. The regular
// mail WebJob uses cleanup_mail.php instead; this is not an activation switch.
if (strtolower((string) (getenv('APP_ENV') ?: 'production')) !== 'development') {
    fwrite(STDERR, "POLICY_CLEANUP_GATED: production lifecycle cleanup is not activated.\n");
    exit(78);
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/signup_verification.php';
require_once __DIR__ . '/../php/report_intake.php';
require_once __DIR__ . '/../php/pair_history.php';

ensure_operational_schema($pdo);

$signupExpiry = bushisa_cleanup_expired_signup_attempts($pdo);
$expiredCodes = bushisa_cleanup_expired_signup_codes($pdo);
$reportCleanup = bushisa_report_transaction($pdo, fn(): array => bushisa_report_cleanup($pdo));
$pairCleanup = bushisa_pair_ready($pdo) ? bushisa_report_transaction($pdo,fn():array=>bushisa_pair_cleanup($pdo)) : [];

$expiredMail = bushisa_signup_cleanup_mail($pdo);
$resetMailCleanup = ['payloads'=>0,'challenge_hashes'=>0,'metadata'=>0,'rate_events'=>0];
require_once __DIR__ . '/../php/password_reset_mail_jobs.php';
if (bushisa_reset_schema_ready($pdo)) $resetMailCleanup = bushisa_reset_cleanup($pdo);
// Q11 account-data obligations remain pending until a bounded live-cleanup
// consumer verifies its work. Merely reaching run_after proves no erasure.

fwrite(STDOUT, json_encode([
    'expired_codes_terminalized' => $expiredCodes,
    'signup_expiry' => $signupExpiry,
    'code_bearing_payloads_erased' => $expiredMail,
    'password_reset_cleanup' => $resetMailCleanup,
    'report_intake_copies_purged' => $reportCleanup['intake_copies_purged'],
    'report_text_evidence_purged' => $reportCleanup['text_evidence_purged'] ?? 0,
    'pair_cleanup' => $pairCleanup,
], JSON_UNESCAPED_SLASHES) . PHP_EOL);

