<?php

declare(strict_types=1);

require_once __DIR__ . '/age_verification.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/operational.php';
require_once __DIR__ . '/policy_safeguards.php';
require_once __DIR__ . '/mail_delivery.php';

function bushisa_student_email_domain_allowlist(): array
{
    $configured = getenv('STUDENT_EMAIL_DOMAIN_ALLOWLIST');
    $domains = $configured !== false && trim($configured) !== ''
        ? array_map('trim', explode(',', strtolower($configured)))
        : ['students.nust.ac.zw'];

    return array_values(array_filter(array_unique($domains), static fn (string $domain): bool => $domain !== ''));
}

function bushisa_student_id_is_valid(string $studentId): bool
{
    if (!preg_match('/^n\d{8}[a-z]$/', $studentId)) {
        return false;
    }

    $year = 2000 + (int) substr($studentId, 1, 3);
    $currentYear = (int) (new DateTimeImmutable('now'))->format('Y');

    return $year >= 2000 && $year <= ($currentYear + 1);
}

function bushisa_student_email_is_valid(string $email, string $studentId): bool
{
    $parts = explode('@', strtolower(trim($email)), 2);
    if (count($parts) !== 2 || $parts[0] !== $studentId) {
        return false;
    }

    return in_array($parts[1], bushisa_student_email_domain_allowlist(), true);
}

function bushisa_signup_code_length(): int
{
    return max(6, min(10, (int) (getenv('SIGNUP_CODE_LENGTH') ?: 6)));
}

function bushisa_signup_code_ttl_seconds(): int
{
    return max(60, min(3600, (int) (getenv('SIGNUP_CODE_TTL_SECONDS') ?: 600)));
}

function bushisa_signup_code_max_attempts(): int
{
    return max(1, min(20, (int) (getenv('SIGNUP_CODE_MAX_ATTEMPTS') ?: 5)));
}

function bushisa_signup_code_secret(): string
{
    $configured = getenv('SIGNUP_CODE_HMAC_KEY');
    if (strtolower((string) (getenv('APP_ENV') ?: 'development')) === 'production'
        && ($configured === false || strlen($configured) < 32 || $configured === 'replace-with-secret-key-material'
            || stripos($configured, '@Microsoft.KeyVault(') === 0)) {
        throw new RuntimeException('A separate production signup-code HMAC key is required.');
    }
    if ($configured !== false && $configured !== '') {
        return $configured;
    }

    return 'bushisa-local-development-signup-code-key';
}

function bushisa_hash_signup_code(int $userId, string $email, string $code): string
{
    return hash_hmac('sha256', $userId . '|' . strtolower($email) . '|' . $code, bushisa_signup_code_secret());
}

function bushisa_generate_signup_code(): string
{
    $length = bushisa_signup_code_length();
    $max = (10 ** $length) - 1;

    return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
}

function bushisa_issue_signup_code(PDO $pdo, int $userId, string $email): string
{
    ensure_operational_schema($pdo);

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $owner = $pdo->prepare('SELECT email, is_verified, account_status FROM users WHERE id = :id FOR UPDATE');
        $owner->execute([':id' => $userId]);
        $user = $owner->fetch(PDO::FETCH_ASSOC);
        if (!is_array($user) || strtolower($user['email']) !== strtolower($email)) {
            throw new RuntimeException('An approval challenge requires the account email.');
        }
        if (bushisa_mail_provider() === 'smtp'
            && ((int) $user['is_verified'] !== 0 || $user['account_status'] !== 'pending_email')) {
            throw new RuntimeException('This account does not need student email approval.');
        }

        $code = bushisa_generate_signup_code();
        $hash = bushisa_hash_signup_code($userId, $email, $code);
        $ttl = bushisa_signup_code_ttl_seconds();

        $expireExisting = $pdo->prepare(
            'UPDATE student_email_approval_codes
             SET consumed_at = COALESCE(consumed_at, UTC_TIMESTAMP()),
                 terminal_at = COALESCE(terminal_at, UTC_TIMESTAMP()),
                 code_hash = NULL,
                 purged_at = COALESCE(purged_at, UTC_TIMESTAMP())
             WHERE user_id = :user_id
               AND consumed_at IS NULL'
        );
        $expireExisting->execute([':user_id' => $userId]);

        $cancelMail = $pdo->prepare(
            'UPDATE mail_outbox m
             INNER JOIN student_email_approval_codes c ON c.id = m.approval_code_id
             SET m.status = CASE WHEN m.status = "pending" THEN "failed" ELSE m.status END,
                 m.body = NULL, m.payload_erased_at = COALESCE(m.payload_erased_at, UTC_TIMESTAMP()),
                 m.next_attempt_at = NULL, m.metadata_json = JSON_OBJECT("result", "superseded")
             WHERE c.user_id = :user_id AND c.consumed_at IS NOT NULL
               AND m.code_bearing = 1 AND m.payload_erased_at IS NULL'
        );
        $cancelMail->execute([':user_id' => $userId]);

        $insertCode = $pdo->prepare(
            'INSERT INTO student_email_approval_codes (user_id, email, code_hash, code_version, expires_at, attempts, created_at)
             VALUES (:user_id, :email, :code_hash, "v1", DATE_ADD(NOW(), INTERVAL :ttl SECOND), 0, NOW())'
        );
        $insertCode->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertCode->bindValue(':email', strtolower($email), PDO::PARAM_STR);
        $insertCode->bindValue(':code_hash', $hash, PDO::PARAM_STR);
        $insertCode->bindValue(':ttl', $ttl, PDO::PARAM_INT);
        $insertCode->execute();
        $codeId = (int) $pdo->lastInsertId();

        bushisa_queue_signup_email($pdo, $email, $code, $ttl, $codeId);

        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $code;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function bushisa_mail_provider(): string
{
    $provider = strtolower(trim((string) (getenv('MAIL_PROVIDER') ?: 'fake')));
    $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'development'));
    if ($appEnv === 'production' && $provider === 'fake') {
        throw new RuntimeException('Fake mail is disabled in production.');
    }

    return $provider;
}

function bushisa_queue_signup_email(PDO $pdo, string $email, string $code, int $ttlSeconds, ?int $codeId = null): void
{
    $provider = bushisa_mail_provider();
    if ($provider === 'fake') {
        bushisa_queue_fake_signup_email($pdo, $email, $code, $ttlSeconds, $codeId);
        return;
    }
    if ($provider === 'smtp' && $codeId !== null && $codeId > 0) {
        bushisa_queue_smtp_signup_email($pdo, $codeId, $email, $code);
        return;
    }
    throw new RuntimeException('A supported mail provider and approval challenge are required.');
}

function bushisa_queue_fake_signup_email(PDO $pdo, string $email, string $code, int $ttlSeconds, ?int $codeId = null): void
{
    $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'verification@bushisa.local';
    $subject = 'Your Bushisa approval code';
    $minutes = (int) ceil($ttlSeconds / 60);
    $body = "From: {$fromAddress}\nYour Bushisa approval code is {$code}. It expires in {$minutes} minutes.";

    $statement = $pdo->prepare(
        'INSERT INTO mail_outbox (recipient_email, subject, body, provider, status, created_at, expires_at, code_bearing, metadata_json, approval_code_id)
         VALUES (:recipient_email, :subject, :body, "fake", "sent", UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL :ttl SECOND), 1, :metadata_json, :code_id)'
    );
    $statement->bindValue(':recipient_email', strtolower($email), PDO::PARAM_STR);
    $statement->bindValue(':subject', $subject, PDO::PARAM_STR);
    $statement->bindValue(':body', $body, PDO::PARAM_STR);
    $statement->bindValue(':ttl', $ttlSeconds, PDO::PARAM_INT);
    $statement->bindValue(':code_id', $codeId, $codeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $statement->bindValue(':metadata_json', json_encode([
        'synthetic_development_only' => true,
        'policy_status' => 'proposed',
    ], JSON_UNESCAPED_SLASHES), PDO::PARAM_STR);
    $statement->execute();
    bushisa_enqueue_cleanup($pdo, 'synthetic_mail_cleanup', 'user', 0, [
        'email' => strtolower($email),
        'policy_status' => 'proposed',
    ]);
}

function bushisa_terminalize_signup_code(PDO $pdo, int $codeId, int $userId, string $email, bool $consume): void
{
    $statement = $pdo->prepare(
        'UPDATE student_email_approval_codes
         SET consumed_at = CASE WHEN :consume = 1 THEN COALESCE(consumed_at, UTC_TIMESTAMP()) ELSE consumed_at END,
             terminal_at = COALESCE(terminal_at, UTC_TIMESTAMP()),
             code_hash = NULL,
             purged_at = COALESCE(purged_at, UTC_TIMESTAMP())
         WHERE id = :id'
    );
    $statement->execute([
        ':consume' => $consume ? 1 : 0,
        ':id' => $codeId,
    ]);

    bushisa_erase_terminal_signup_material($pdo, $userId, $email);
}

function bushisa_cleanup_expired_signup_codes(PDO $pdo): int
{
    ensure_operational_schema($pdo);

    $expired = $pdo->query(
        'SELECT id, user_id, email
         FROM student_email_approval_codes
         WHERE consumed_at IS NULL
           AND expires_at <= UTC_TIMESTAMP()
           AND (terminal_at IS NULL OR code_hash IS NOT NULL)'
    );
    $rows = $expired !== false ? ($expired->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    foreach ($rows as $row) {
        bushisa_terminalize_signup_code($pdo, (int) $row['id'], (int) $row['user_id'], (string) $row['email'], false);
    }

    return count($rows);
}

function bushisa_verify_signup_code(PDO $pdo, int $userId, string $code): array
{
    ensure_operational_schema($pdo);

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $owner = $pdo->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE');
        $owner->execute([':id' => $userId]);
        $result = bushisa_verify_signup_code_locked($pdo, $userId, $code);
        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $result;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function bushisa_verify_signup_code_locked(PDO $pdo, int $userId, string $code): array
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if ($code === '') {
        return ['success' => false, 'error' => 'Enter the approval code from your student email.'];
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM student_email_approval_codes
         WHERE user_id = :user_id
           AND consumed_at IS NULL
           AND terminal_at IS NULL
           AND code_hash IS NOT NULL
         ORDER BY id DESC
         LIMIT 1 FOR UPDATE'
    );
    $statement->execute([':user_id' => $userId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return ['success' => false, 'error' => 'No active approval code is available. Request a new code.'];
    }

    if ((int) $row['attempts'] >= bushisa_signup_code_max_attempts()) {
        bushisa_terminalize_signup_code($pdo, (int) $row['id'], $userId, (string) $row['email'], false);
        return ['success' => false, 'error' => 'This approval code has too many failed attempts. Request a new code.'];
    }

    $expiresAt = new DateTimeImmutable((string) $row['expires_at'], new DateTimeZone('UTC'));
    if ($expiresAt < new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
        bushisa_terminalize_signup_code($pdo, (int) $row['id'], $userId, (string) $row['email'], false);
        return ['success' => false, 'error' => 'This approval code has expired. Request a new code.'];
    }

    $expected = (string) $row['code_hash'];
    $actual = bushisa_hash_signup_code($userId, (string) $row['email'], $code);
    if (!hash_equals($expected, $actual)) {
        $increment = $pdo->prepare('UPDATE student_email_approval_codes SET attempts = attempts + 1 WHERE id = :id');
        $increment->execute([':id' => (int) $row['id']]);
        if (((int) $row['attempts'] + 1) >= bushisa_signup_code_max_attempts()) {
            bushisa_terminalize_signup_code($pdo, (int) $row['id'], $userId, (string) $row['email'], false);
        }

        return ['success' => false, 'error' => 'The approval code is incorrect.'];
    }

    $consume = $pdo->prepare('UPDATE student_email_approval_codes SET consumed_at = NOW() WHERE id = :id');
    $consume->execute([':id' => (int) $row['id']]);

    $approve = $pdo->prepare(
        'UPDATE users
         SET is_verified = 1,
             account_status = "pending_onboarding",
             session_version = session_version + 1
         WHERE id = :user_id'
    );
    $approve->execute([':user_id' => $userId]);

    bushisa_terminalize_signup_code($pdo, (int) $row['id'], $userId, (string) $row['email'], true);

    return ['success' => true, 'error' => null];
}

function bushisa_complete_adult_onboarding(PDO $pdo, int $userId, string $dateOfBirth): array
{
    $age = verify_age($dateOfBirth);
    if (!$age['is_valid']) {
        $status = ($age['age'] !== null && $age['age'] < 18) ? 'underage_blocked' : 'pending_onboarding';
        $statement = $pdo->prepare(
            'UPDATE users
             SET account_status = :status,
                 session_version = session_version + 1
             WHERE id = :user_id'
        );
        $statement->execute([
            ':status' => $status,
            ':user_id' => $userId,
        ]);

        return ['success' => false, 'error' => $age['error'] ?? 'Age verification failed.'];
    }

    $statement = $pdo->prepare(
        'UPDATE profiles
         SET date_of_birth = :date_of_birth
         WHERE user_id = :user_id'
    );
    $statement->execute([
        ':date_of_birth' => $dateOfBirth,
        ':user_id' => $userId,
    ]);

    $activate = $pdo->prepare(
        'UPDATE users
         SET account_status = "active",
             is_active = 1,
             session_version = session_version + 1
         WHERE id = :user_id'
    );
    $activate->execute([':user_id' => $userId]);

    return ['success' => true, 'error' => null];
}
