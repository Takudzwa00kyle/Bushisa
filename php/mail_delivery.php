<?php

declare(strict_types=1);

require_once __DIR__ . '/operational.php';

final class BushisaMailTransportException extends RuntimeException
{
    public function __construct(public readonly string $category, public readonly bool $retryable)
    {
        parent::__construct($category);
    }
}

function bushisa_mail_outbox_key(): string
{
    $key = base64_decode((string) (getenv('MAIL_OUTBOX_KEY') ?: ''), true);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('MAIL_OUTBOX_KEY must be the base64 encoding of 32 random bytes.');
    }

    return $key;
}

function bushisa_mail_context(int $codeId, string $recipient): string
{
    return 'Bushisa/signup-email/v1|' . $codeId . '|' . strtolower($recipient);
}

function bushisa_encrypt_mail_payload(array $payload, int $codeId, string $recipient): string
{
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt(
        json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        'aes-256-gcm', bushisa_mail_outbox_key(), OPENSSL_RAW_DATA, $iv, $tag,
        bushisa_mail_context($codeId, $recipient), 16
    );
    if ($encrypted === false) {
        throw new RuntimeException('Mail payload encryption failed.');
    }

    return 'v1.' . base64_encode($iv . $tag . $encrypted);
}

function bushisa_decrypt_mail_payload(string $envelope, int $codeId, string $recipient): array
{
    $raw = str_starts_with($envelope, 'v1.') ? base64_decode(substr($envelope, 3), true) : false;
    if ($raw === false || strlen($raw) <= 28) {
        throw new RuntimeException('Mail payload is invalid.');
    }
    $decrypted = openssl_decrypt(
        substr($raw, 28), 'aes-256-gcm', bushisa_mail_outbox_key(), OPENSSL_RAW_DATA,
        substr($raw, 0, 12), substr($raw, 12, 16), bushisa_mail_context($codeId, $recipient)
    );
    if ($decrypted === false) {
        throw new RuntimeException('Mail payload authentication failed.');
    }
    $payload = json_decode($decrypted, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) {
        throw new RuntimeException('Mail payload is invalid.');
    }

    return $payload;
}

function bushisa_smtp_settings(): array
{
    $settings = [
        'host' => trim((string) (getenv('SMTP_HOST') ?: 'smtp.azurecomm.net')),
        'port' => (string) (getenv('SMTP_PORT') ?: '587'),
        'encryption' => strtolower(trim((string) (getenv('SMTP_ENCRYPTION') ?: 'tls'))),
        'username' => (string) (getenv('SMTP_USERNAME') ?: ''),
        'password' => (string) (getenv('SMTP_PASSWORD') ?: ''),
        'from_address' => trim((string) (getenv('MAIL_FROM_ADDRESS') ?: '')),
        'from_name' => trim((string) (getenv('MAIL_FROM_NAME') ?: 'Bushisa')),
        'reply_to' => trim((string) (getenv('MAIL_REPLY_TO') ?: '')),
        'timeout' => max(1, min(30, (int) (getenv('SMTP_TIMEOUT_SECONDS') ?: 15))),
    ];
    if ($settings['username'] === '' || $settings['password'] === ''
        || stripos($settings['password'], '@Microsoft.KeyVault(') === 0) {
        throw new RuntimeException('SMTP credentials are incomplete.');
    }
    if (!preg_match('/^[a-zA-Z0-9.-]+$/', $settings['host'])
        || !ctype_digit($settings['port']) || (int) $settings['port'] < 1 || (int) $settings['port'] > 65535) {
        throw new RuntimeException('SMTP endpoint is invalid.');
    }
    if (!in_array($settings['encryption'], ['tls', 'starttls'], true)) {
        throw new RuntimeException('SMTP requires STARTTLS.');
    }
    if (!filter_var($settings['from_address'], FILTER_VALIDATE_EMAIL)
        || ($settings['reply_to'] !== '' && !filter_var($settings['reply_to'], FILTER_VALIDATE_EMAIL))
        || strpbrk($settings['from_name'], "\r\n") !== false) {
        throw new RuntimeException('Mail sender configuration is invalid.');
    }

    return $settings;
}

function bushisa_create_smtp_mailer(array $settings): \PHPMailer\PHPMailer\PHPMailer
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Install Composer dependencies before enabling SMTP.');
    }
    require_once $autoload;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $settings['host'];
    $mail->Port = (int) $settings['port'];
    $mail->SMTPAuth = true;
    $mail->AuthType = 'LOGIN';
    $mail->Username = $settings['username'];
    $mail->Password = $settings['password'];
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPDebug = 0;
    $mail->Timeout = $settings['timeout'];
    $mail->getSMTPInstance()->Timelimit = $settings['timeout'];
    $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
        $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
    }
    $mail->SMTPOptions = ['ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
        'crypto_method' => $crypto,
    ]];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($settings['from_address'], $settings['from_name']);
    if ($settings['reply_to'] !== '') {
        $mail->addReplyTo($settings['reply_to']);
    }

    return $mail;
}

function bushisa_send_smtp_payload(array $payload, int $outboxId, int $codeId): void
{
    $settings = bushisa_smtp_settings();
    if (($payload['from_address'] ?? null) !== $settings['from_address']) {
        throw new BushisaMailTransportException('sender_configuration_changed', false);
    }
    $mail = bushisa_create_smtp_mailer($settings);
    $mail->addAddress((string) $payload['recipient']);
    $mail->Subject = (string) $payload['subject'];
    $mail->Body = (string) $payload['body'];
    $domain = substr($settings['from_address'], (int) strpos($settings['from_address'], '@') + 1);
    $mail->MessageID = '<signup-' . $codeId . '-' . $outboxId . '@' . $domain . '>';

    try {
        $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $exception) {
        // Never log ErrorInfo/exception messages: SMTP diagnostics can contain
        // addresses, credentials, or the code-bearing message.
        $smtpCode = (int) ($mail->getSMTPInstance()->getError()['smtp_code'] ?? 0);
        $authentication = in_array($smtpCode, [530, 535], true);
        $retryable = $smtpCode === 0 || $smtpCode < 500 || $authentication;
        throw new BushisaMailTransportException(
            $authentication ? 'smtp_authentication_failed' : 'smtp_send_failed', $retryable
        );
    } finally {
        $mail->smtpClose();
    }
}

function bushisa_queue_smtp_signup_email(PDO $pdo, int $codeId, string $email, string $code): void
{
    $settings = bushisa_smtp_settings();
    // Check dependencies before accepting a signup that cannot be delivered.
    bushisa_create_smtp_mailer($settings);
    $challenge = $pdo->prepare('SELECT expires_at FROM student_email_approval_codes WHERE id = :id');
    $challenge->execute([':id' => $codeId]);
    $expiresAt = $challenge->fetchColumn();
    if (!is_string($expiresAt)) {
        throw new RuntimeException('An approval challenge is required for SMTP mail.');
    }
    $recipient = strtolower($email);
    $subject = 'Your Bushisa approval code';
    $payload = [
        'recipient' => $recipient,
        'from_address' => $settings['from_address'],
        'subject' => $subject,
        'body' => 'Your Bushisa approval code is ' . $code . '. It expires at ' . $expiresAt . " UTC.\n"
            . 'If you did not request this code, you can ignore this email.',
    ];
    $encrypted = bushisa_encrypt_mail_payload($payload, $codeId, $recipient);
    $insert = $pdo->prepare(
        'INSERT INTO mail_outbox
         (recipient_email, subject, body, provider, status, created_at, expires_at,
          code_bearing, approval_code_id, attempts, next_attempt_at, metadata_json)
         VALUES (:email, :subject, :body, "smtp", "pending", UTC_TIMESTAMP(), :expires_at,
                 1, :code_id, 0, UTC_TIMESTAMP(), JSON_OBJECT("payload_format", "aes-256-gcm-v1"))'
    );
    $insert->execute([
        ':email' => $recipient, ':subject' => $subject, ':body' => $encrypted,
        ':expires_at' => $expiresAt, ':code_id' => $codeId,
    ]);
}

function bushisa_finish_mail(PDO $pdo, int $id, string $status, string $reason): void
{
    $statement = $pdo->prepare(
        'UPDATE mail_outbox
         SET status = :status, body = NULL, payload_erased_at = UTC_TIMESTAMP(),
             sent_at = CASE WHEN :accepted = 1 THEN UTC_TIMESTAMP() ELSE sent_at END,
             metadata_json = JSON_OBJECT("result", :reason), next_attempt_at = NULL
         WHERE id = :id'
    );
    $statement->execute([
        ':status' => $status, ':accepted' => $status === 'sent' ? 1 : 0,
        ':reason' => $reason, ':id' => $id,
    ]);
}

/**
 * SMTP acceptance is not inbox delivery and never approves an account.
 * Call only after issuance commits. The user/challenge/outbox locks are held
 * through the bounded send so a resend cannot supersede a code mid-dispatch.
 * Transport is injectable for local tests, never through a public endpoint.
 */
function bushisa_dispatch_mail(PDO $pdo, int $limit = 10, ?callable $transport = null): array
{
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Dispatch must run outside an existing transaction.');
    }
    $counts = ['sent' => 0, 'retried' => 0, 'discarded' => 0, 'failed' => 0];
    $lockName = 'bushisa-mail-' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    $lock = $pdo->prepare('SELECT GET_LOCK(:name, 0)');
    $lock->execute([':name' => $lockName]);
    if ((int) $lock->fetchColumn() !== 1) {
        return $counts;
    }
    try {
        // Erase stale payloads even when their next retry is not due yet.
        $expired = $pdo->exec(
            'UPDATE mail_outbox SET status = "failed", body = NULL,
             payload_erased_at = UTC_TIMESTAMP(), next_attempt_at = NULL,
             metadata_json = JSON_OBJECT("result", "expired")
             WHERE provider = "smtp" AND status = "pending"
               AND (expires_at IS NULL OR expires_at <= UTC_TIMESTAMP())'
        );
        $counts['discarded'] += $expired;
        $candidates = $pdo->prepare(
            'SELECT id, approval_code_id FROM mail_outbox
             WHERE provider = "smtp" AND status = "pending"
               AND (next_attempt_at IS NULL OR next_attempt_at <= UTC_TIMESTAMP())
             ORDER BY id LIMIT :limit'
        );
        $candidates->bindValue(':limit', max(1, min(30, $limit)), PDO::PARAM_INT);
        $candidates->execute();
        foreach ($candidates->fetchAll() as $candidate) {
            $pdo->beginTransaction();
            try {
                $owner = $pdo->prepare('SELECT user_id FROM student_email_approval_codes WHERE id = :id');
                $owner->execute([':id' => $candidate['approval_code_id']]);
                $userId = (int) $owner->fetchColumn();
                $user = $pdo->prepare('SELECT email, is_verified, account_status FROM users WHERE id = :id FOR UPDATE');
                $user->execute([':id' => $userId]);
                $userRow = $user->fetch();
                $code = $pdo->prepare(
                    'SELECT *, expires_at > UTC_TIMESTAMP() AS live
                     FROM student_email_approval_codes WHERE id = :id FOR UPDATE'
                );
                $code->execute([':id' => $candidate['approval_code_id']]);
                $codeRow = $code->fetch();
                $outbox = $pdo->prepare(
                    'SELECT *, expires_at > UTC_TIMESTAMP() AS live FROM mail_outbox WHERE id = :id FOR UPDATE'
                );
                $outbox->execute([':id' => $candidate['id']]);
                $row = $outbox->fetch();
                if (!is_array($row) || $row['status'] !== 'pending') {
                    $pdo->commit();
                    continue;
                }
                if (!is_array($userRow) || !is_array($codeRow) || !(bool) $row['live'] || !(bool) $codeRow['live']
                    || (int) $userRow['is_verified'] !== 0 || $userRow['account_status'] !== 'pending_email'
                    || (int) $codeRow['attempts'] >= bushisa_signup_code_max_attempts()
                    || $codeRow['consumed_at'] !== null || $codeRow['terminal_at'] !== null || $codeRow['code_hash'] === null
                    || strtolower($userRow['email']) !== $row['recipient_email']
                    || strtolower($codeRow['email']) !== $row['recipient_email']
                    || $row['body'] === null || $row['payload_erased_at'] !== null) {
                    bushisa_finish_mail($pdo, (int) $row['id'], 'failed', 'obsolete_challenge');
                    $counts['discarded']++;
                    $pdo->commit();
                    continue;
                }
                $attempt = (int) $row['attempts'] + 1;
                if ($attempt > 5) {
                    bushisa_finish_mail($pdo, (int) $row['id'], 'failed', 'retry_limit');
                    $counts['failed']++;
                    $pdo->commit();
                    continue;
                }
                $updateAttempt = $pdo->prepare('UPDATE mail_outbox SET attempts = :attempt WHERE id = :id');
                $updateAttempt->execute([':attempt' => $attempt, ':id' => $row['id']]);
                try {
                    $payload = bushisa_decrypt_mail_payload((string) $row['body'], (int) $codeRow['id'], $row['recipient_email']);
                } catch (Throwable $exception) {
                    bushisa_finish_mail($pdo, (int) $row['id'], 'failed', 'payload_authentication_failed');
                    $counts['failed']++;
                    $pdo->commit();
                    continue;
                }
                if (($payload['recipient'] ?? null) !== $row['recipient_email']) {
                    bushisa_finish_mail($pdo, (int) $row['id'], 'failed', 'payload_recipient_mismatch');
                    $counts['failed']++;
                    $pdo->commit();
                    continue;
                }
                try {
                    ($transport ?? 'bushisa_send_smtp_payload')($payload, (int) $row['id'], (int) $codeRow['id']);
                    bushisa_finish_mail($pdo, (int) $row['id'], 'sent', 'smtp_accepted');
                    $counts['sent']++;
                } catch (Throwable $exception) {
                    $delay = min(300, 30 * (2 ** min(4, $attempt - 1)));
                    $canRetry = $exception instanceof BushisaMailTransportException && $exception->retryable && $attempt < 5
                        && strtotime((string) $row['expires_at'] . ' UTC') > time() + $delay;
                    $reason = $exception instanceof BushisaMailTransportException ? $exception->category : 'sender_configuration_failed';
                    if ($canRetry) {
                        $retry = $pdo->prepare(
                            'UPDATE mail_outbox SET next_attempt_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :delay SECOND),
                             metadata_json = JSON_OBJECT("result", :reason) WHERE id = :id'
                        );
                        $retry->bindValue(':delay', $delay, PDO::PARAM_INT);
                        $retry->bindValue(':reason', $reason, PDO::PARAM_STR);
                        $retry->bindValue(':id', (int) $row['id'], PDO::PARAM_INT);
                        $retry->execute();
                        $counts['retried']++;
                    } else {
                        bushisa_finish_mail($pdo, (int) $row['id'], 'failed', $reason);
                        $counts['failed']++;
                    }
                }
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(:name)');
        $release->execute([':name' => $lockName]);
    }

    return $counts;
}
