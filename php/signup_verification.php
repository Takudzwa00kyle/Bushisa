<?php

declare(strict_types=1);

require_once __DIR__ . '/age_verification.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/operational.php';
require_once __DIR__ . '/policy_safeguards.php';
require_once __DIR__ . '/mail_delivery.php';
require_once __DIR__ . '/signup_lifecycle.php';
require_once __DIR__ . '/verification_retention.php';

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
    bushisa_signup_require_schema($pdo);
    bushisa_signup_test_point($pdo,'q11_mixed');
    $result=bushisa_signup_transaction($pdo,function()use($pdo,$userId,$email):array {
        $owner = $pdo->prepare('SELECT * FROM users WHERE id = :id FOR UPDATE');
        $owner->execute([':id' => $userId]);
        $user = $owner->fetch(PDO::FETCH_ASSOC);
        if (!is_array($user) || strtolower($user['email']) !== strtolower($email)) {
            throw new RuntimeException('An approval challenge requires the account email.');
        }
        $expiry=bushisa_signup_expire_locked($pdo,$user);
        if (!bushisa_signup_user_live($user) || $expiry['state']!=='live' || (int)$user['is_verified']!==0 || $user['account_status']!=='pending_email') {
            return ['error'=>bushisa_signup_expiry_message($expiry['state'])];
        }

        $code = bushisa_generate_signup_code();
        $hash = bushisa_hash_signup_code($userId, $email, $code);
        $ttl = bushisa_signup_code_ttl_seconds();

        $now=bushisa_signup_now($pdo);
        $expireExisting = $pdo->prepare(
            'UPDATE student_email_approval_codes
             SET consumed_at = COALESCE(consumed_at, :used),
                 terminal_at = COALESCE(terminal_at, :terminal),
                 code_hash = NULL,
                 purged_at = COALESCE(purged_at, :purged)
             WHERE user_id = :user_id
               AND consumed_at IS NULL AND terminal_at IS NULL'
        );
        $expireExisting->execute([':user_id' => $userId,':used'=>$now,':terminal'=>$now,':purged'=>$now]);
        bushisa_signup_cancel_material_locked($pdo,$userId,'superseded');

        $insertCode = $pdo->prepare(
            'INSERT INTO student_email_approval_codes (user_id, email, code_hash, code_version, expires_at, attempts, created_at)
             VALUES (:user_id, :email, :code_hash, "v1", :expires, 0, :issued)'
        );
        $insertCode->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertCode->bindValue(':email', strtolower($email), PDO::PARAM_STR);
        $insertCode->bindValue(':code_hash', $hash, PDO::PARAM_STR);
        // Preserve the existing seconds precision/TTL settings. No new Q22
        // default or resend allowance is adopted by Q11.
        $issued=substr($now,0,19);
        $insertCode->bindValue(':issued',$issued,PDO::PARAM_STR);
        $insertCode->bindValue(':expires',bushisa_signup_time($issued,'+'.$ttl.' seconds'),PDO::PARAM_STR);
        $insertCode->execute();
        $codeId = (int) $pdo->lastInsertId();
        \Bushisa\VerificationRetention\register_code($pdo,$codeId);

        bushisa_queue_signup_email($pdo, $email, $code, $ttl, $codeId);
        $finalExpiry=bushisa_signup_expire_locked($pdo,$user);
        if ($finalExpiry['state']!=='live') { return ['error'=>bushisa_signup_expiry_message($finalExpiry['state'])]; }

        return ['code'=>$code];
    });
    // Business refusal follows commit so lazy expiry is not accidentally undone.
    if (isset($result['error'])) { throw new RuntimeException($result['error']); }
    return $result['code'];
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
    if (bushisa_q11_synthetic_enabled() && getenv('BUSHISA_Q11_QUEUE_FAKE')==='true' && $codeId!==null) {
        bushisa_queue_q11_fake_email($pdo,$codeId,$email,$code); return;
    }
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
    if ($codeId!==null) { bushisa_signup_register_mail_job($pdo,(int)$pdo->lastInsertId(),$codeId,'fixture_delivered'); }
    bushisa_enqueue_cleanup($pdo, 'synthetic_mail_cleanup', 'user', 0, [
        'email' => strtolower($email),
        'policy_status' => 'proposed',
    ]);
}

function bushisa_terminalize_signup_code(PDO $pdo, int $codeId, int $userId, string $email, bool $consume): void
{
    bushisa_signup_transaction($pdo,function()use($pdo,$codeId,$userId,$email,$consume):void {
    bushisa_signup_query($pdo,'SELECT id FROM users WHERE id=? FOR UPDATE',[$userId])->fetchColumn();
    $at=bushisa_signup_now($pdo);
    $statement = $pdo->prepare(
        'UPDATE student_email_approval_codes
         SET consumed_at = CASE WHEN :consume = 1 AND terminal_at IS NULL AND code_hash IS NOT NULL THEN COALESCE(consumed_at, :consumed) ELSE consumed_at END,
             terminal_at = COALESCE(terminal_at, :terminal),
             code_hash = NULL,
             purged_at = COALESCE(purged_at, :purged)
         WHERE id = :id AND user_id=:owner'
    );
    $statement->execute([
        ':consume' => $consume ? 1 : 0,
        ':id' => $codeId,
        ':owner'=>$userId,':consumed'=>$at,':terminal'=>$at,':purged'=>$at,
    ]);

    bushisa_signup_cancel_material_locked($pdo,$userId,'terminal_challenge');
    });
}

function bushisa_cleanup_expired_signup_codes(PDO $pdo): int
{
    ensure_operational_schema($pdo);

    $expired = $pdo->prepare(
        'SELECT id, user_id, email
         FROM student_email_approval_codes
         WHERE consumed_at IS NULL
           AND expires_at <= ?
           AND (terminal_at IS NULL OR code_hash IS NOT NULL) ORDER BY id LIMIT 100'
    );
    $expired->execute([bushisa_signup_now($pdo)]);
    $rows = $expired->fetchAll(PDO::FETCH_ASSOC);
    $count=0;
    foreach ($rows as $row) {
        $count+=bushisa_signup_transaction($pdo,function()use($pdo,$row):int {
            bushisa_signup_query($pdo,'SELECT id FROM users WHERE id=? FOR UPDATE',[$row['user_id']])->fetchColumn();
            $fresh=bushisa_signup_query($pdo,'SELECT * FROM student_email_approval_codes WHERE id=? FOR UPDATE',[$row['id']])->fetch(PDO::FETCH_ASSOC);
            if ($fresh && $fresh['consumed_at']===null && ($fresh['terminal_at']===null || $fresh['code_hash']!==null) && bushisa_signup_time($fresh['expires_at'])<=bushisa_signup_now($pdo)) {
                bushisa_terminalize_signup_code($pdo,(int)$row['id'],(int)$row['user_id'],(string)$row['email'],false);
                return 1;
            }
            return 0;
        });
    }

    return $count;
}

function bushisa_verify_signup_code(PDO $pdo, int $userId, string $code,?int $sessionVersion=null): array
{
    ensure_operational_schema($pdo);

    bushisa_signup_require_schema($pdo);
    bushisa_signup_test_point($pdo,'q11_mixed');
    return bushisa_signup_transaction($pdo,function()use($pdo,$userId,$code,$sessionVersion):array {
        $owner = $pdo->prepare('SELECT * FROM users WHERE id = :id FOR UPDATE');
        $owner->execute([':id' => $userId]);
        $u=$owner->fetch(PDO::FETCH_ASSOC);
        if (!$u || ($sessionVersion!==null && (int)$u['session_version']!==$sessionVersion)) { return ['success'=>false,'error'=>'This account or session is unavailable.']; }
        $expiry=bushisa_signup_expire_locked($pdo,$u);
        if ($expiry['state']!=='live' || !bushisa_signup_user_live($u) || $u['account_status']!=='pending_email' || (int)$u['is_verified']!==0) {
            return ['success'=>false,'error'=>bushisa_signup_expiry_message($expiry['state'])];
        }
        return bushisa_verify_signup_code_locked($pdo,$userId,$code);
    });
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

    if (bushisa_signup_time((string)$row['expires_at'])<=bushisa_signup_now($pdo)) {
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

    $user=bushisa_signup_query($pdo,'SELECT * FROM users WHERE id=? FOR UPDATE',[$userId])->fetch(PDO::FETCH_ASSOC);
    $expiry=bushisa_signup_expire_locked($pdo,$user);
    if ($expiry['state']!=='live') { return ['success'=>false,'error'=>bushisa_signup_expiry_message($expiry['state'])]; }
    $at=bushisa_signup_now($pdo);
    if ($at>=$expiry['expires_at']) {
        bushisa_signup_expire_locked($pdo,$user);
        return ['success'=>false,'error'=>bushisa_signup_expiry_message('expired')];
    }
    bushisa_signup_query($pdo,'UPDATE signup_lifecycle SET first_email_approved_at=COALESCE(first_email_approved_at,?),onboarding_expires_at=COALESCE(onboarding_expires_at,?),anchor_status="recorded_email_approval",version=version+1 WHERE account_id=?',
        [$at,bushisa_signup_time($at,'+7 days'),$userId]);

    $approve = $pdo->prepare(
        'UPDATE users
         SET is_verified = 1,
             account_status = "pending_onboarding",
             session_version = session_version + 1
         WHERE id = :user_id'
    );
    $approve->execute([':user_id' => $userId]);

    bushisa_terminalize_signup_code($pdo, (int) $row['id'], $userId, (string) $row['email'], true);

    require_once __DIR__.'/managed_session.php';
    \Bushisa\ManagedSession\transition_locked($pdo,$userId,(int)$user['session_version'],(int)$user['session_version']+1,'email_approval');

    return ['success' => true, 'error' => null,'session_version'=>(int)$user['session_version']+1];
}

function bushisa_complete_adult_onboarding(PDO $pdo, int $userId, string $dateOfBirth,?int $sessionVersion=null): array
{
    bushisa_signup_require_schema($pdo);
    bushisa_signup_test_point($pdo,'q11_mixed');
    return bushisa_signup_transaction($pdo,function()use($pdo,$userId,$dateOfBirth,$sessionVersion):array {
    $u=bushisa_signup_query($pdo,'SELECT * FROM users WHERE id=? FOR UPDATE',[$userId])->fetch(PDO::FETCH_ASSOC);
    if (!$u || ($sessionVersion!==null && (int)$u['session_version']!==$sessionVersion)) { return ['success'=>false,'error'=>'This account or session is unavailable.']; }
    $expiry=bushisa_signup_expire_locked($pdo,$u);
    if ($expiry['state']!=='live' || !bushisa_signup_user_live($u) || $u['account_status']!=='pending_onboarding' || (int)$u['is_verified']!==1) {
        return ['success'=>false,'error'=>bushisa_signup_expiry_message($expiry['state'])];
    }
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
        if ($status==='underage_blocked') { bushisa_signup_cancel_material_locked($pdo,$userId,'eligibility_rejected',true); }

        return ['success' => false, 'error' => $age['error'] ?? 'Age verification failed.'];
    }

    // Lock the profile before the final deadline check as well. Waiting for a
    // profile writer must not permit activation using an earlier clock read.
    if (!bushisa_signup_query($pdo,'SELECT user_id FROM profiles WHERE user_id=? FOR UPDATE',[$userId])->fetchColumn()) {
        return ['success'=>false,'error'=>'This signup is unavailable.'];
    }
    $currentExpiry=bushisa_signup_expire_locked($pdo,$u);
    $completedAt=bushisa_signup_now($pdo);
    if ($currentExpiry['state']!=='live' || $completedAt>=$currentExpiry['expires_at']) {
        bushisa_signup_expire_locked($pdo,$u);
        return ['success'=>false,'error'=>bushisa_signup_expiry_message('expired')];
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
    bushisa_signup_query($pdo,'UPDATE signup_lifecycle SET onboarding_completed_at=COALESCE(onboarding_completed_at,?),version=version+1 WHERE account_id=?',[$completedAt,$userId]);

    require_once __DIR__.'/managed_session.php';
    \Bushisa\ManagedSession\transition_locked($pdo,$userId,(int)$u['session_version'],(int)$u['session_version']+1,'adult_onboarding');

    return ['success' => true, 'error' => null,'session_version'=>(int)$u['session_version']+1];
    });
}
