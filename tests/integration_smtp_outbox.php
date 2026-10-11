<?php

declare(strict_types=1);

if (getenv('BUSHISA_TEST_DATABASE') !== 'true' || getenv('APP_ENV') === 'production') {
    throw new RuntimeException('Run this suite only against an explicitly selected synthetic test database.');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/signup_verification.php';

function check_smtp(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function smtp_test_user(PDO $pdo): array
{
    $student = 'n025' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(97, 122));
    $email = $student . '@students.nust.ac.zw';
    $insert = $pdo->prepare(
        'INSERT INTO users (nust_student_id, email, password_hash, is_verified, is_active, account_status, session_version)
         VALUES (:student, :email, :password, 0, 1, "pending_email", 0)'
    );
    $insert->execute([':student' => $student, ':email' => $email, ':password' => password_hash('Synthetic-test-only', PASSWORD_DEFAULT)]);

    return [(int) $pdo->lastInsertId(), $email];
}

function smtp_test_mail(PDO $pdo, string $email): array
{
    $query = $pdo->prepare('SELECT * FROM mail_outbox WHERE recipient_email = :email ORDER BY id DESC LIMIT 1');
    $query->execute([':email' => $email]);

    return $query->fetch(PDO::FETCH_ASSOC);
}

function smtp_pending_account(PDO $pdo, int $userId): bool
{
    $query = $pdo->prepare('SELECT is_verified, account_status FROM users WHERE id = :id');
    $query->execute([':id' => $userId]);
    $user = $query->fetch();

    return (int) $user['is_verified'] === 0 && $user['account_status'] === 'pending_email';
}

ensure_operational_schema($pdo);
putenv('MAIL_PROVIDER=smtp');
putenv('SMTP_USERNAME=synthetic-user');
putenv('SMTP_PASSWORD=synthetic-password');
putenv('SMTP_ENCRYPTION=tls');
putenv('MAIL_FROM_ADDRESS=donotreply@bushisa.me');
putenv('MAIL_REPLY_TO');
putenv('MAIL_OUTBOX_KEY=' . base64_encode(random_bytes(32)));
putenv('SIGNUP_CODE_HMAC_KEY=' . bin2hex(random_bytes(32)));

[$userId, $email] = smtp_test_user($pdo);
$code = bushisa_issue_signup_code($pdo, $userId, $email);
$row = smtp_test_mail($pdo, $email);
check_smtp($row['status'] === 'pending' && str_starts_with($row['body'], 'v1.'), 'SMTP must enqueue ciphertext');
check_smtp(!str_contains($row['body'], $code), 'outbox must not retain the plaintext code');
$accepted = 0;
$result = bushisa_dispatch_mail($pdo, 10, function (array $payload) use (&$accepted, $email, $code, $pdo): void {
    check_smtp($payload['recipient'] === $email && str_contains($payload['body'], $code), 'only the intended student receives the code');
    $settings = bushisa_database_settings();
    $other = new PDO('mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $settings['name'],
        $settings['user'], $settings['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $duplicate = bushisa_dispatch_mail($other, 10, fn () => throw new RuntimeException('Concurrent sender must not run'));
    check_smtp(array_sum($duplicate) === 0, 'another worker must respect the database mutex');
    $accepted++;
});
check_smtp($accepted === 1 && $result['sent'] === 1, 'one queued message should be accepted');
$row = smtp_test_mail($pdo, $email);
check_smtp($row['status'] === 'sent' && $row['body'] === null && $row['payload_erased_at'] !== null, 'accepted payloads must be erased');
check_smtp(smtp_pending_account($pdo, $userId), 'SMTP acceptance must never approve an account');
check_smtp(bushisa_verify_signup_code($pdo, $userId, $code)['success'], 'the received current code should still verify');
check_smtp(!bushisa_verify_signup_code($pdo, $userId, $code)['success'], 'a consumed code must not replay');

[$retryUser, $retryEmail] = smtp_test_user($pdo);
bushisa_issue_signup_code($pdo, $retryUser, $retryEmail);
$before = smtp_test_mail($pdo, $retryEmail);
$result = bushisa_dispatch_mail($pdo, 10, fn () => throw new BushisaMailTransportException('synthetic_timeout', true));
$after = smtp_test_mail($pdo, $retryEmail);
check_smtp($result['retried'] === 1 && $after['status'] === 'pending' && (int) $after['attempts'] === 1, 'transient errors must retry');
check_smtp($after['expires_at'] === $before['expires_at'] && $after['body'] !== null, 'retry must preserve expiry and encrypted payload');
check_smtp(smtp_pending_account($pdo, $retryUser), 'transport errors must leave the account pending');
$pdo->exec('UPDATE mail_outbox SET next_attempt_at = UTC_TIMESTAMP() WHERE id = ' . (int) $after['id']);
check_smtp(bushisa_dispatch_mail($pdo, 10, fn () => null)['sent'] === 1, 'a due retry should be dispatched');

[$oldUser, $oldEmail] = smtp_test_user($pdo);
$oldCode = bushisa_issue_signup_code($pdo, $oldUser, $oldEmail);
$oldRow = smtp_test_mail($pdo, $oldEmail);
do {
    $newCode = bushisa_issue_signup_code($pdo, $oldUser, $oldEmail);
} while ($newCode === $oldCode);
$old = $pdo->query('SELECT status, body FROM mail_outbox WHERE id = ' . (int) $oldRow['id'])->fetch();
check_smtp($old['status'] === 'failed' && $old['body'] === null, 'resend must cancel and erase obsolete queued mail');
// Delayed cleanup of an old generation must not erase the new generation.
bushisa_terminalize_signup_code($pdo, (int) $oldRow['approval_code_id'], $oldUser, $oldEmail, false);
check_smtp(smtp_test_mail($pdo, $oldEmail)['body'] !== null, 'old generation cleanup must preserve current mail');
check_smtp(!bushisa_verify_signup_code($pdo, $oldUser, $oldCode)['success'], 'superseded codes must fail');
$sentCode = '';
bushisa_dispatch_mail($pdo, 10, function (array $payload) use (&$sentCode): void { $sentCode = $payload['body']; });
check_smtp(str_contains($sentCode, $newCode), 'only the current generation should be sent');

[$earlyUser, $earlyEmail] = smtp_test_user($pdo);
$earlyCode = bushisa_issue_signup_code($pdo, $earlyUser, $earlyEmail);
check_smtp(bushisa_verify_signup_code($pdo, $earlyUser, $earlyCode)['success'], 'code entry should approve, independently of transport status');
check_smtp(smtp_test_mail($pdo, $earlyEmail)['body'] === null, 'verification must erase an unsent encrypted payload');
check_smtp(bushisa_dispatch_mail($pdo, 10, fn () => throw new RuntimeException('Consumed code must not be sent'))['sent'] === 0,
    'consumed codes must not be sent');

[$expiredUser, $expiredEmail] = smtp_test_user($pdo);
bushisa_issue_signup_code($pdo, $expiredUser, $expiredEmail);
$expired = smtp_test_mail($pdo, $expiredEmail);
$pdo->exec('UPDATE mail_outbox SET expires_at = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND) WHERE id = ' . (int) $expired['id']);
$result = bushisa_dispatch_mail($pdo, 10, fn () => throw new RuntimeException('Expired code must not be sent'));
check_smtp($result['discarded'] === 1 && smtp_test_mail($pdo, $expiredEmail)['body'] === null, 'expired payloads must be discarded');

[$failedUser, $failedEmail] = smtp_test_user($pdo);
bushisa_issue_signup_code($pdo, $failedUser, $failedEmail);
$result = bushisa_dispatch_mail($pdo, 10, fn () => throw new BushisaMailTransportException('synthetic_rejection', false));
check_smtp($result['failed'] === 1 && smtp_test_mail($pdo, $failedEmail)['body'] === null, 'permanent rejection must erase the payload');
check_smtp(smtp_pending_account($pdo, $failedUser), 'rejection must never approve the account');

[$badUser, $badEmail] = smtp_test_user($pdo);
bushisa_issue_signup_code($pdo, $badUser, $badEmail);
$bad = smtp_test_mail($pdo, $badEmail);
$tamper = $pdo->prepare('UPDATE mail_outbox SET body = :body WHERE id = :id');
$tamper->execute([':body' => 'v1.' . base64_encode(random_bytes(60)), ':id' => $bad['id']]);
$result = bushisa_dispatch_mail($pdo, 10, fn () => throw new RuntimeException('Tampered payload must not be sent'));
check_smtp($result['failed'] === 1 && smtp_test_mail($pdo, $badEmail)['body'] === null, 'tampered payloads must fail closed');

[$rollbackUser, $rollbackEmail] = smtp_test_user($pdo);
$rollbackCode = bushisa_issue_signup_code($pdo, $rollbackUser, $rollbackEmail);
$rollback = smtp_test_mail($pdo, $rollbackEmail);
putenv('SMTP_PASSWORD');
$rejected = false;
try {
    bushisa_issue_signup_code($pdo, $rollbackUser, $rollbackEmail);
} catch (RuntimeException $exception) {
    $rejected = true;
}
check_smtp($rejected && !$pdo->inTransaction(), 'failed issuance must roll back its transaction');
check_smtp(smtp_test_mail($pdo, $rollbackEmail)['id'] === $rollback['id'], 'failed resend must preserve the old queue item');
check_smtp(bushisa_verify_signup_code($pdo, $rollbackUser, $rollbackCode)['success'], 'failed resend must preserve the previous active code');

fwrite(STDOUT, "integration_smtp_outbox PASS\n");
