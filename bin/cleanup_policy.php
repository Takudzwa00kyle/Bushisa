<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/signup_verification.php';
require_once __DIR__ . '/../php/report_intake.php';

ensure_operational_schema($pdo);

$expiredCodes = bushisa_cleanup_expired_signup_codes($pdo);
$reportCleanup = bushisa_report_transaction($pdo, fn(): array => bushisa_report_cleanup($pdo));

$mail = $pdo->prepare(
    'UPDATE mail_outbox
     SET body = NULL,
         status = CASE WHEN status = "pending" THEN "failed" ELSE status END,
         next_attempt_at = NULL,
         payload_erased_at = COALESCE(payload_erased_at, UTC_TIMESTAMP()),
         metadata_json = JSON_OBJECT("policy_status", "proposed", "erased_reason", "expired_code_bearing_payload")
     WHERE code_bearing = 1
       AND payload_erased_at IS NULL
       AND (expires_at IS NULL OR expires_at <= UTC_TIMESTAMP())'
);
$mail->execute();

$cleanup = $pdo->prepare(
    'UPDATE cleanup_outbox
     SET status = "done",
         completed_at = COALESCE(completed_at, UTC_TIMESTAMP())
     WHERE status = "pending"
       AND run_after <= UTC_TIMESTAMP()
       AND cleanup_type IN ("signup_code_cleanup", "synthetic_mail_cleanup")'
);
$cleanup->execute();

fwrite(STDOUT, json_encode([
    'expired_codes_terminalized' => $expiredCodes,
    'code_bearing_payloads_erased' => $mail->rowCount(),
    'cleanup_rows_completed' => $cleanup->rowCount(),
    'report_intake_copies_purged' => $reportCleanup['intake_copies_purged'],
    'report_text_evidence_purged' => $reportCleanup['text_evidence_purged'] ?? 0,
], JSON_UNESCAPED_SLASHES) . PHP_EOL);

