<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/signup_verification.php';
require_once __DIR__ . '/../config/database_settings.php';

function check_mail(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function reject_mail(callable $action, string $message): void
{
    try {
        $action();
    } catch (Throwable $exception) {
        return;
    }
    throw new RuntimeException($message);
}

putenv('MAIL_OUTBOX_KEY=' . base64_encode(random_bytes(32)));
$payload = ['recipient' => 'synthetic@students.nust.ac.zw', 'body' => 'Code 001234'];
$encrypted = bushisa_encrypt_mail_payload($payload, 42, $payload['recipient']);
check_mail(!str_contains($encrypted, '001234'), 'outbox must not contain plaintext codes');
check_mail(bushisa_decrypt_mail_payload($encrypted, 42, $payload['recipient']) === $payload, 'payload must round trip');
reject_mail(fn () => bushisa_decrypt_mail_payload($encrypted, 43, $payload['recipient']), 'ciphertext must bind to the challenge');
reject_mail(fn () => bushisa_decrypt_mail_payload($encrypted, 42, 'other@students.nust.ac.zw'), 'ciphertext must bind to the recipient');
$raw = base64_decode(substr($encrypted, 3), true);
$raw[30] = chr(ord($raw[30]) ^ 1);
reject_mail(fn () => bushisa_decrypt_mail_payload('v1.' . base64_encode($raw), 42, $payload['recipient']), 'tampering must fail');
putenv('MAIL_OUTBOX_KEY=invalid');
reject_mail(fn () => bushisa_mail_outbox_key(), 'invalid encryption keys must fail closed');

putenv('APP_ENV=production');
putenv('MAIL_PROVIDER=fake');
reject_mail(fn () => bushisa_mail_provider(), 'production must reject fake delivery');
putenv('SIGNUP_CODE_HMAC_KEY');
reject_mail(fn () => bushisa_signup_code_secret(), 'production must reject the development HMAC fallback');
putenv('SIGNUP_CODE_HMAC_KEY=replace-with-secret-key-material');
reject_mail(fn () => bushisa_signup_code_secret(), 'production must reject the example HMAC key');
putenv('SIGNUP_CODE_HMAC_KEY=@Microsoft.KeyVault(SecretUri=https://synthetic.vault.azure.net/secrets/hmac)');
reject_mail(fn () => bushisa_signup_code_secret(), 'unresolved Key Vault references must not become predictable HMAC keys');

putenv('SMTP_USERNAME=synthetic-user');
putenv('SMTP_PASSWORD=synthetic-password');
putenv('MAIL_FROM_ADDRESS=donotreply@bushisa.me');
putenv('MAIL_REPLY_TO');
putenv('SMTP_ENCRYPTION=none');
reject_mail(fn () => bushisa_smtp_settings(), 'SMTP must require STARTTLS');
putenv('SMTP_ENCRYPTION=tls');
putenv('SMTP_PASSWORD=@Microsoft.KeyVault(SecretUri=https://synthetic.vault.azure.net/secrets/smtp)');
reject_mail(fn () => bushisa_smtp_settings(), 'unresolved SMTP secret references must fail before enqueueing');
putenv('SMTP_PASSWORD=synthetic-password');
$mailer = bushisa_create_smtp_mailer(bushisa_smtp_settings());
check_mail($mailer->SMTPDebug === 0, 'SMTP debug must not expose credentials or message bodies');
check_mail($mailer->SMTPSecure === 'tls', 'mailer must use explicit STARTTLS');
check_mail($mailer->SMTPOptions['ssl']['verify_peer'] === true, 'TLS must verify the server chain');
check_mail($mailer->SMTPOptions['ssl']['verify_peer_name'] === true, 'TLS must verify the server name');
check_mail($mailer->SMTPOptions['ssl']['allow_self_signed'] === false, 'self-signed certificates must be rejected');
check_mail(count($mailer->getToAddresses()) === 0 && count($mailer->getCcAddresses()) === 0
    && count($mailer->getBccAddresses()) === 0, 'mailer must not add other recipients');

foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_PASS', 'DB_SSL_CA',
    'AZURE_MYSQL_HOST', 'AZURE_MYSQL_PORT', 'AZURE_MYSQL_DBNAME', 'AZURE_MYSQL_USERNAME', 'AZURE_MYSQL_PASSWORD'] as $name) {
    putenv($name);
}
// This settings-only test never opens a database connection. Use a public test
// CA fixture so the checks do not depend on the host's system CA bundle path.
putenv('DB_SSL_CA=' . __DIR__ . '/fixtures/mail_configuration_ca.pem');
putenv('AZURE_MYSQL_HOST=synthetic.mysql.database.azure.com');
putenv('AZURE_MYSQL_DBNAME=synthetic_db');
putenv('AZURE_MYSQL_USERNAME=synthetic_runtime');
putenv('AZURE_MYSQL_PASSWORD=synthetic-password');
$database = bushisa_database_settings();
check_mail($database['name'] === 'synthetic_db' && $database['user'] === 'synthetic_runtime', 'Azure-generated settings must map to PDO');
check_mail($database['require_tls'] === true, 'Azure connections must require TLS');
putenv('DB_NAME=explicit_db');
check_mail(bushisa_database_settings()['name'] === 'explicit_db', 'explicit DB settings must take precedence');
putenv('DB_USER=root');
reject_mail(fn () => bushisa_database_settings(), 'production must reject root');
putenv('DB_USER=synthetic_runtime');
putenv('DB_SSL_CA=/missing/ca.pem');
reject_mail(fn () => bushisa_database_settings(), 'missing CA bundles must not disable verification');

fwrite(STDOUT, "unit_mail_configuration PASS\n");
