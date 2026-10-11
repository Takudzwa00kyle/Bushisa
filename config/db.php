<?php

declare(strict_types=1);

require_once __DIR__ . '/database_settings.php';

$appEnv = strtolower((string) (getenv('APP_ENV') ?: 'development'));
$appDebug = filter_var(getenv('APP_DEBUG') ?: ($appEnv === 'production' ? 'false' : 'true'), FILTER_VALIDATE_BOOL);

try {
    $settings = bushisa_database_settings();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'], $settings['name']);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if ($settings['require_tls']) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $settings['ssl_ca'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO($dsn, $settings['user'], $settings['password'], $options);
    $pdo->exec("SET time_zone = '+00:00'");
    if ($settings['require_tls']) {
        $tls = $pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
        if (!is_array($tls) || empty($tls[1])) {
            throw new RuntimeException('An encrypted database connection is required.');
        }
    }
} catch (Throwable $exception) {
    if (PHP_SAPI === 'cli') {
        throw new RuntimeException('Database connection failed; check configuration and connectivity.');
    }
    http_response_code(500);
    if ($appDebug && $appEnv !== 'production') {
        die('Database connection failed: ' . $exception->getMessage());
    }
    die('Database connection failed. Please try again later.');
}
