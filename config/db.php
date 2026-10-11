<?php
// config/db.php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'bushisa_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD');
if ($pass === false || $pass === '') {
    $pass = getenv('DB_PASS') ?: '';
}
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';
$appEnv = strtolower((string) (getenv('APP_ENV') ?: 'development'));
$appDebug = filter_var(getenv('APP_DEBUG') ?: ($appEnv === 'production' ? 'false' : 'true'), FILTER_VALIDATE_BOOL);

if ($appEnv === 'production' && ($user === 'root' || $pass === '')) {
    http_response_code(500);
    die('Database configuration is incomplete.');
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    if ($appDebug) {
        die("Database connection failed: " . $e->getMessage());
    }

    die('Database connection failed.');
}
