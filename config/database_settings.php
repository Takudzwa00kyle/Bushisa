<?php

declare(strict_types=1);

function bushisa_database_environment(array $names, string $fallback = ''): string
{
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $fallback;
}

function bushisa_database_settings(): array
{
    $production = strtolower((string) (getenv('APP_ENV') ?: 'development')) === 'production';
    $settings = [
        'host' => bushisa_database_environment(['DB_HOST', 'AZURE_MYSQL_HOST'], $production ? '' : '127.0.0.1'),
        'port' => bushisa_database_environment(['DB_PORT', 'AZURE_MYSQL_PORT'], '3306'),
        'name' => bushisa_database_environment(['DB_NAME', 'AZURE_MYSQL_DBNAME'], $production ? '' : 'bushisa_db'),
        'user' => bushisa_database_environment(['DB_USER', 'AZURE_MYSQL_USERNAME'], $production ? '' : 'root'),
        'password' => bushisa_database_environment(['DB_PASSWORD', 'DB_PASS', 'AZURE_MYSQL_PASSWORD']),
        'ssl_ca' => bushisa_database_environment(['DB_SSL_CA']),
    ];

    if ($production && ($settings['host'] === '' || $settings['name'] === '' || $settings['user'] === ''
        || strtolower($settings['user']) === 'root' || $settings['password'] === '')) {
        throw new RuntimeException('Database configuration is incomplete.');
    }
    if (!ctype_digit($settings['port']) || (int) $settings['port'] < 1 || (int) $settings['port'] > 65535) {
        throw new RuntimeException('Database port is invalid.');
    }
    foreach (['host', 'name'] as $field) {
        if (preg_match('/[;\x00-\x1F]/', $settings[$field])) {
            throw new RuntimeException('Database configuration is invalid.');
        }
    }

    $settings['require_tls'] = $production || $settings['ssl_ca'] !== ''
        || str_ends_with(strtolower($settings['host']), '.mysql.database.azure.com');
    if ($settings['require_tls']) {
        if ($settings['ssl_ca'] === '') {
            $settings['ssl_ca'] = '/etc/ssl/certs/ca-certificates.crt';
        }
        if (!is_file($settings['ssl_ca']) || !is_readable($settings['ssl_ca'])) {
            throw new RuntimeException('DB_SSL_CA must point to a readable trusted CA bundle.');
        }
    }

    return $settings;
}
