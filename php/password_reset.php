<?php
declare(strict_types=1);

require_once __DIR__ . '/mail_delivery.php';
require_once __DIR__ . '/password_policy.php';
require_once __DIR__ . '/age_verification.php';
require_once __DIR__ . '/sanitize.php';

const BUSHISA_RESET_TTL = 900;
const BUSHISA_RESET_QUEUE_MAX = 300;

function bushisa_reset_enabled(): bool
{
    $sender = strtolower(trim((string) (getenv('MAIL_FROM_ADDRESS') ?: '')));
    $senderReady = strtolower((string) (getenv('APP_ENV') ?: 'development')) !== 'production' || $sender === 'donotreply@bushisa.me';
    return filter_var(getenv('PASSWORD_RESET_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL)
        && bushisa_reset_throttle_config() !== null && $senderReady;
}

/** No proposed throttle value is silently adopted. Missing values fail closed. */
function bushisa_reset_throttle_config(): ?array
{
    $cooldown = getenv('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS');
    $hour = getenv('PASSWORD_RESET_MAX_REQUESTS_PER_HOUR');
    $day = getenv('PASSWORD_RESET_MAX_REQUESTS_PER_DAY');
    foreach ([$cooldown, $hour, $day] as $value) {
        if ($value === false || $value === '' || !ctype_digit((string) $value) || (int) $value < 1) return null;
    }
    if ((int) $cooldown > 86400 || (int) $hour > 100 || (int) $day > 1000) return null;
    return ['cooldown' => (int) $cooldown, 'hour' => (int) $hour, 'day' => (int) $day];
}

function bushisa_reset_schema_ready(PDO $pdo): bool
{
    foreach (['password_reset_state','password_reset_challenges','password_reset_mail_jobs','password_reset_rate_events','password_reset_rate_subjects'] as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
        $stmt->execute([$table]);
        if ((int) $stmt->fetchColumn() !== 1) return false;
    }
    return true;
}

function bushisa_reset_now(PDO $pdo): string
{
    return (string) $pdo->query('SELECT DATE_FORMAT(UTC_TIMESTAMP(6),"%Y-%m-%d %H:%i:%s.%f")')->fetchColumn();
}

function bushisa_reset_time(string $at, string $modify): string
{
    return (new DateTimeImmutable($at, new DateTimeZone('UTC')))->modify($modify)->format('Y-m-d H:i:s.u');
}

function bushisa_reset_transaction(PDO $pdo, callable $work): mixed
{
    if ($pdo->inTransaction()) throw new LogicException('Password-reset transaction nesting is not supported');
    $pdo->beginTransaction();
    try { $result = $work(); $pdo->commit(); return $result; }
    catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function bushisa_reset_hash(string $purpose, string $value): string
{
    return hash_hmac('sha256', $purpose . "\0" . strtolower($value), bushisa_mail_outbox_key());
}

function bushisa_reset_account(PDO $pdo, string $email, bool $lock = false): ?array
{
    $sql = 'SELECT u.id,u.email,u.password_hash,u.is_verified,u.is_active,u.is_suspended,u.account_status,u.deleted_at,u.session_version,u.signup_attempt_key,p.date_of_birth
            FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE LOWER(u.email)=LOWER(?) LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
    $stmt = $pdo->prepare($sql); $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function bushisa_reset_eligible(?array $u): bool
{
    if (!$u || !is_string($u['signup_attempt_key'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/', $u['signup_attempt_key'])
        || !is_string($u['email'] ?? null) || !filter_var($u['email'], FILTER_VALIDATE_EMAIL)
        || (int) $u['is_verified'] !== 1 || (int) $u['is_active'] !== 1 || (int) $u['is_suspended'] !== 0
        || $u['account_status'] !== 'active' || $u['deleted_at'] !== null
        || !is_string($u['date_of_birth'] ?? null) || $u['date_of_birth'] === '') return false;
    try { $age = verify_age((string) $u['date_of_birth']); }
    catch (Throwable) { return false; }
    return ($age['is_valid'] ?? false) === true && is_int($age['age'] ?? null) && $age['age'] >= 18;
}

function bushisa_reset_trusted_origin(): ?string
{
    $raw = trim((string) (getenv('APP_BASE_URL') ?: ''));
    $parts = parse_url($raw);
    if (!is_array($parts) || !isset($parts['scheme'],$parts['host']) || array_intersect(['user','pass','query','fragment'], array_keys($parts)) !== []) return null;
    $synthetic = filter_var(getenv('BUSHISA_PASSWORD_RESET_SYNTHETIC') ?: 'false', FILTER_VALIDATE_BOOL)
        && function_exists('bushisa_q11_synthetic_enabled') && bushisa_q11_synthetic_enabled();
    if (strtolower($parts['scheme']) !== 'https' && !$synthetic) return null;
    if (!in_array(strtolower($parts['scheme']), ['https','http'], true) || strpbrk($raw, "\r\n\\") !== false) return null;
    if (!$synthetic && (strtolower($parts['host'])!=='bushisa.me' || (isset($parts['port']) && (int)$parts['port']!==443))) return null;
    return rtrim($raw, '/');
}

function bushisa_reset_encrypt(array $payload, string $mailId, string $purpose, string $challenge, string $generation, string $recipient): string
{
    $iv = random_bytes(12); $tag = '';
    $aad = 'Bushisa/' . $purpose . '/v1|' . $mailId . '|' . $challenge . '|' . $generation . '|' . strtolower($recipient);
    $cipher = openssl_encrypt(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'aes-256-gcm', bushisa_mail_outbox_key(), OPENSSL_RAW_DATA, $iv, $tag, $aad, 16);
    if ($cipher === false) throw new RuntimeException('Reset mail encryption failed');
    return 'r1.' . base64_encode($iv . $tag . $cipher);
}

function bushisa_reset_decrypt(string $envelope, string $mailId, string $purpose, string $challenge, string $generation, string $recipient): array
{
    $raw = str_starts_with($envelope, 'r1.') ? base64_decode(substr($envelope, 3), true) : false;
    if ($raw === false || strlen($raw) <= 28) throw new RuntimeException('Reset mail envelope invalid');
    $aad = 'Bushisa/' . $purpose . '/v1|' . $mailId . '|' . $challenge . '|' . $generation . '|' . strtolower($recipient);
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', bushisa_mail_outbox_key(), OPENSSL_RAW_DATA, substr($raw,0,12), substr($raw,12,16), $aad);
    if ($plain === false) throw new RuntimeException('Reset mail authentication failed');
    $payload = json_decode($plain, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) throw new RuntimeException('Reset mail payload invalid');
    return $payload;
}

function bushisa_reset_cancel_mail_locked(PDO $pdo, int $account, ?string $challengeId, string $now): void
{
    $sql = 'UPDATE password_reset_mail_jobs SET cancel_requested_at=COALESCE(cancel_requested_at,?), version=version+1,
            encrypted_payload=NULL,
            terminal_at=IF(state IN ("ready","claimed"),COALESCE(terminal_at,?),terminal_at),payload_purge_due_at=COALESCE(payload_purge_due_at,?),next_attempt_at=NULL,
            reason_code=IF(state IN ("ready","claimed"),"superseded_or_consumed","cancellation_requested"),
            account_id=IF(state IN ("ready","claimed"),NULL,account_id),account_generation=IF(state IN ("ready","claimed"),NULL,account_generation),recipient_hash=IF(state IN ("ready","claimed"),NULL,recipient_hash),challenge_id=IF(state IN ("ready","claimed"),NULL,challenge_id),claim_hash=IF(state IN ("ready","claimed"),NULL,claim_hash),claim_until=IF(state IN ("ready","claimed"),NULL,claim_until),
            state=IF(state IN ("ready","claimed"),"cancelled",state)
            WHERE account_id=? AND purpose="password_reset" AND state IN ("ready","claimed","inflight")';
    $args = [$now,$now,$now,$account];
    if ($challengeId !== null) { $sql .= ' AND challenge_id=?'; $args[] = $challengeId; }
    $pdo->prepare($sql)->execute($args);
}

/** Called only inside the account-deletion transaction; in-flight SMTP cannot be recalled. */
function bushisa_reset_cancel_account(PDO $pdo,int $account): void
{
    if (!$pdo->inTransaction() || !bushisa_reset_schema_ready($pdo)) return;
    $now=bushisa_reset_now($pdo);
    $pdo->prepare('UPDATE password_reset_challenges SET invalidated_at=COALESCE(invalidated_at,?),token_hash=NULL,secret_purge_due_at=?,version=version+1 WHERE account_id=? AND token_hash IS NOT NULL')
        ->execute([$now,$now,$account]);
    $pdo->prepare('UPDATE password_reset_mail_jobs SET cancel_requested_at=COALESCE(cancel_requested_at,?),encrypted_payload=NULL,payload_purge_due_at=?,
        terminal_at=IF(state IN ("ready","claimed"),COALESCE(terminal_at,?),terminal_at),
        reason_code=IF(state IN ("ready","claimed"),"account_restricted","cancellation_requested"),
        account_id=IF(state IN ("ready","claimed"),NULL,account_id),account_generation=IF(state IN ("ready","claimed"),NULL,account_generation),recipient_hash=IF(state IN ("ready","claimed"),NULL,recipient_hash),challenge_id=IF(state IN ("ready","claimed"),NULL,challenge_id),
        state=IF(state IN ("ready","claimed"),"cancelled",state),next_attempt_at=NULL,version=version+1
        WHERE account_id=? AND state IN ("ready","claimed","inflight")')
        ->execute([$now,$now,$now,$account]);
    $pdo->prepare('DELETE FROM password_reset_state WHERE account_id=?')->execute([$account]);
}

/** Existence-neutral endpoint service. It never accepts a caller-selected recipient. */
function bushisa_reset_request(PDO $pdo, string $submittedEmail): void
{
    if (!bushisa_reset_enabled() || !bushisa_reset_schema_ready($pdo) || bushisa_reset_trusted_origin() === null) return;
    $email = clean_email($submittedEmail);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $email = 'invalid-' . hash('sha256', substr($submittedEmail,0,512)) . '@invalid.example';
    $cfg = bushisa_reset_throttle_config(); if ($cfg === null) return;
    $origin = bushisa_reset_trusted_origin();
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $subjects = [bushisa_reset_hash('email', $email), bushisa_reset_hash('ip', $ip)];
    bushisa_reset_transaction($pdo, function () use ($pdo,$email,$cfg,$origin,$subjects): void {
        foreach ($subjects as $subject) {
            $pdo->prepare('INSERT INTO password_reset_rate_subjects(subject_hash,touched_at) VALUES(?,UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE touched_at=VALUES(touched_at)')->execute([$subject]);
        }
        foreach ($subjects as $subject) $pdo->prepare('SELECT subject_hash FROM password_reset_rate_subjects WHERE subject_hash=? FOR UPDATE')->execute([$subject]);
        $now = bushisa_reset_now($pdo);
        $rateLimited = false;
        foreach ($subjects as $subject) {
            $s = $pdo->prepare('SELECT MAX(occurred_at) last_at, SUM(occurred_at>=UTC_TIMESTAMP(6)-INTERVAL 1 HOUR) hour_count, SUM(occurred_at>=UTC_TIMESTAMP(6)-INTERVAL 1 DAY) day_count FROM password_reset_rate_events WHERE subject_hash=?');
            $s->execute([$subject]); $r=$s->fetch(PDO::FETCH_ASSOC) ?: [];
            $last = $r['last_at'] ?? null;
            if (($last && strtotime($last . ' UTC') > strtotime($now . ' UTC') - $cfg['cooldown']) || (int)($r['hour_count']??0)>=$cfg['hour'] || (int)($r['day_count']??0)>=$cfg['day']) $rateLimited=true;
            $event = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_reset_rate_events(event_id,subject_hash,occurred_at,expires_at) VALUES(?,?,?,?)')->execute([$event,$subject,$now,bushisa_reset_time($now,'+48 hours')]);
        }
        if ($rateLimited) return;
        $u = bushisa_reset_account($pdo,$email,true);
        if (!bushisa_reset_eligible($u)) return;
        $recipient = strtolower((string)$u['email']); $generation=(string)$u['signup_attempt_key']; $account=(int)$u['id'];
        $st=$pdo->prepare('INSERT INTO password_reset_state(account_id,account_generation,issuance_generation,updated_at) VALUES(?,?,0,?) ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)');
        $st->execute([$account,$generation,$now]);
        $st=$pdo->prepare('SELECT * FROM password_reset_state WHERE account_id=? FOR UPDATE'); $st->execute([$account]); $state=$st->fetch(PDO::FETCH_ASSOC);
        if (!$state || !hash_equals((string)$state['account_generation'],$generation)) {
            $pdo->prepare('UPDATE password_reset_state SET account_generation=?,issuance_generation=issuance_generation+1,updated_at=? WHERE account_id=?')->execute([$generation,$now,$account]);
        }
        $st=$pdo->prepare('SELECT issuance_generation FROM password_reset_state WHERE account_id=? FOR UPDATE'); $st->execute([$account]); $seq=(int)$st->fetchColumn()+1;
        $pdo->prepare('UPDATE password_reset_challenges SET invalidated_at=COALESCE(invalidated_at,?),secret_purge_due_at=LEAST(secret_purge_due_at,?),version=version+1 WHERE account_id=? AND token_hash IS NOT NULL AND consumed_at IS NULL AND invalidated_at IS NULL')->execute([$now,bushisa_reset_time($now,'+24 hours'),$account]);
        bushisa_reset_cancel_mail_locked($pdo,$account,null,$now);
        $pdo->prepare('UPDATE password_reset_state SET account_generation=?,issuance_generation=?,updated_at=? WHERE account_id=?')->execute([$generation,$seq,$now,$account]);
        $token=bin2hex(random_bytes(32)); $challenge=bin2hex(random_bytes(32)); $mail=bin2hex(random_bytes(32));
        $expires=bushisa_reset_time($now,'+15 minutes'); $purge=bushisa_reset_time($expires,'+24 hours');
        $pdo->prepare('INSERT INTO password_reset_challenges(challenge_id,account_id,account_generation,recipient_hash,issuance_generation,issued_session_version,token_hash,issued_at,expires_at,secret_purge_due_at) VALUES(?,?,?,?,?,?,?,?,?,?)')
            ->execute([$challenge,$account,$generation,bushisa_reset_hash('recipient',$recipient),$seq,(int)$u['session_version'],hash('sha256',$token),$now,$expires,$purge]);
        $mailDeadline=min(bushisa_reset_time($now,'+5 minutes'),bushisa_reset_time($expires,'-60 seconds'));
        if (strtotime($mailDeadline.' UTC')<=strtotime($now.' UTC')) throw new RuntimeException('Reset mail has no useful dispatch window');
        $from=trim((string)(getenv('MAIL_FROM_ADDRESS')?:''));
        if (!filter_var($from,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Reset sender is unavailable');
        $resetUrl=$origin.'/reset_password.php#token='.$token;
        $payload=['recipient'=>$recipient,'from_address'=>$from,'subject'=>'Reset your Bushisa password','body'=>"Use this secure link to reset your Bushisa password:\n\n{$resetUrl}\n\nYou can also enter this one-time key on the reset page:\n{$token}\n\nThe key expires in 15 minutes. If you did not request a reset, ignore this email."];
        $envelope=bushisa_reset_encrypt($payload,$mail,'password_reset',$challenge,$generation,$recipient);
        $pdo->prepare('INSERT INTO password_reset_mail_jobs(mail_id,challenge_id,account_id,account_generation,purpose,recipient_hash,encrypted_payload,queued_at,useful_until,state,next_attempt_at) VALUES(?,?,?,?,"password_reset",?,?,?,?,"ready",?)')
            ->execute([$mail,$challenge,$account,$generation,bushisa_reset_hash('recipient',$recipient),$envelope,$now,$mailDeadline,$now]);
    });
}

/** GET/scanner-safe: only the explicit CSRF-protected POST consumes proof. */
function bushisa_reset_consume(PDO $pdo, string $token, string $newPassword): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $token) || !bushisa_reset_schema_ready($pdo)) return false;
    if (validate_account_password($newPassword) !== []) return false;
    $hash=hash('sha256',$token);
    $lookup=$pdo->prepare('SELECT account_id FROM password_reset_challenges WHERE token_hash=? LIMIT 1'); $lookup->execute([$hash]); $account=(int)$lookup->fetchColumn();
    if ($account<1) return false;
    $newHash=hash_password($newPassword);
    return bushisa_reset_transaction($pdo,function()use($pdo,$hash,$account,$newHash):bool {
        $uStmt=$pdo->prepare('SELECT u.id,u.email,u.is_verified,u.is_active,u.is_suspended,u.account_status,u.deleted_at,u.session_version,u.signup_attempt_key,p.date_of_birth FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? FOR UPDATE');
        $uStmt->execute([$account]); $u=$uStmt->fetch(PDO::FETCH_ASSOC);
        $cStmt=$pdo->prepare('SELECT * FROM password_reset_challenges WHERE token_hash=? FOR UPDATE'); $cStmt->execute([$hash]); $c=$cStmt->fetch(PDO::FETCH_ASSOC);
        if (!$c || !$u || (int)$c['account_id']!==$account || !bushisa_reset_eligible($u)
            || !hash_equals((string)$c['account_generation'],(string)$u['signup_attempt_key'])
            || !hash_equals((string)$c['recipient_hash'],bushisa_reset_hash('recipient',(string)$u['email']))
            || (int)$c['issued_session_version']!==(int)$u['session_version']
            || $c['consumed_at']!==null || $c['invalidated_at']!==null
        || (string)$c['expires_at']<=bushisa_reset_now($pdo)) return false;
        $st=$pdo->prepare('SELECT account_generation,issuance_generation FROM password_reset_state WHERE account_id=? FOR UPDATE'); $st->execute([$account]); $state=$st->fetch(PDO::FETCH_ASSOC);
        if (!$state || !hash_equals((string)$state['account_generation'],(string)$c['account_generation']) || (int)$state['issuance_generation']!==(int)$c['issuance_generation']) return false;
        $now=bushisa_reset_now($pdo);
        $upd=$pdo->prepare('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=? AND session_version=? AND account_status="active" AND is_verified=1 AND is_active=1 AND is_suspended=0 AND deleted_at IS NULL');
        $upd->execute([$newHash,$account,(int)$u['session_version']]); if ($upd->rowCount()!==1) return false;
        $pdo->prepare('UPDATE password_reset_challenges SET consumed_at=?,invalidated_at=COALESCE(invalidated_at,?),token_hash=NULL,secret_purge_due_at=?,version=version+1 WHERE account_id=? AND token_hash IS NOT NULL')->execute([$now,$now,bushisa_reset_time($now,'+24 hours'),$account]);
        bushisa_reset_cancel_mail_locked($pdo,$account,null,$now);
        // A code-free notification is enqueued after the password change in this transaction.
        $mail=bin2hex(random_bytes(32)); $recipient=strtolower((string)$u['email']);
        $from=trim((string)(getenv('MAIL_FROM_ADDRESS')?:''));
        if (filter_var($from,FILTER_VALIDATE_EMAIL)) {
            $payload=['recipient'=>$recipient,'from_address'=>$from,'subject'=>'Your Bushisa password was changed','body'=>"Your Bushisa password was changed. If you did not make this change, request another password reset and secure the email account associated with Bushisa. Do not reply to this message."];
            $envelope=bushisa_reset_encrypt($payload,$mail,'password_changed',(string)$c['challenge_id'],(string)$c['account_generation'],$recipient);
            $deadline=bushisa_reset_time($now,'+5 minutes');
            $pdo->prepare('INSERT INTO password_reset_mail_jobs(mail_id,challenge_id,account_id,account_generation,purpose,recipient_hash,encrypted_payload,queued_at,useful_until,state,next_attempt_at) VALUES(?,?,?, ?,"password_changed",?,?,?,?,"ready",?)')
                ->execute([$mail,$c['challenge_id'],$account,$c['account_generation'],bushisa_reset_hash('recipient',$recipient),$envelope,$now,$deadline,$now]);
        }
        return true;
    });
}
