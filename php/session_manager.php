<?php

declare(strict_types=1);

const BUSHISA_SESSION_TIMEOUT = 1800;

function bushisa_cookie_secure(): bool
{
    $override = getenv('SESSION_COOKIE_SECURE');
    if ($override !== false && $override !== '') {
        return filter_var($override, FILTER_VALIDATE_BOOL);
    }

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
}

/**
 * Configure secure session cookie parameters.
 *
 * @return array<string, mixed>
 */
function bushisa_session_cookie_params(): array
{
    return [
        'lifetime' => BUSHISA_SESSION_TIMEOUT,
        'path' => '/',
        'domain' => '',
        'secure' => bushisa_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

/**
 * Refresh the session cookie expiry and inactivity timestamp.
 *
 * @return void
 */
function refresh_session_expiry(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION['last_activity'] = time();

    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
        'expires' => time() + BUSHISA_SESSION_TIMEOUT,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => bushisa_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

/**
 * Start the session with secure defaults.
 *
 * @return void
 */
function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_secure', bushisa_cookie_secure() ? '1' : '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    session_set_cookie_params(bushisa_session_cookie_params());
    session_start();

    if (!isset($_SESSION['last_activity'])) {
        refresh_session_expiry();
        return;
    }

    $lastActivity = (int) $_SESSION['last_activity'];
    if ($lastActivity > 0 && (time() - $lastActivity) > BUSHISA_SESSION_TIMEOUT) {
        destroy_session();
        return;
    }

    refresh_session_expiry();
}

/**
 * Regenerate the session identifier after authentication or privilege changes.
 *
 * @return void
 */
function regenerate_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        refresh_session_expiry();
    }
}

/**
 * Destroy the current session and remove the session cookie.
 *
 * @return void
 */
function destroy_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => bushisa_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    session_destroy();
}

function bushisa_request_expects_json(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

    return str_contains($accept, 'application/json')
        || str_contains($contentType, 'application/json')
        || str_contains(str_replace('\\', '/', $scriptName), '/api/');
}

function bushisa_auth_fail(int $statusCode = 401, string $message = 'Authentication required.'): void
{
    destroy_session();

    if (bushisa_request_expects_json()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: /login.php');
    exit;
}

/**
 * @return array<string, mixed>|null
 */
function bushisa_current_account(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * @param array<string, mixed> $account
 */
function bushisa_account_can_use_session(array $account): bool
{
    if (array_key_exists('is_suspended', $account) && (int) $account['is_suspended'] === 1) {
        return false;
    }

    if (array_key_exists('is_active', $account) && (int) $account['is_active'] !== 1) {
        return false;
    }

    if (array_key_exists('deleted_at', $account) && $account['deleted_at'] !== null && (string) $account['deleted_at'] !== '') {
        return false;
    }

    if (array_key_exists('account_status', $account) && in_array((string) $account['account_status'], ['underage_blocked', 'suspended', 'deleted'], true)) {
        return false;
    }

    return true;
}

function bushisa_current_script_basename(): string
{
    return basename(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')));
}

function bushisa_gate_pending_account(string $status): void
{
    $script = bushisa_current_script_basename();

    if ($status === 'pending_email' && !in_array($script, ['verify_email.php', 'logout.php'], true)) {
        if (bushisa_request_expects_json()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Approve your student email before continuing.', 'redirect' => 'verify_email.php'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: /verify_email.php');
        exit;
    }

    if ($status === 'pending_onboarding' && !in_array($script, ['onboarding.php', 'logout.php'], true)) {
        if (bushisa_request_expects_json()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Complete adult onboarding before continuing.', 'redirect' => 'onboarding.php'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: /onboarding.php');
        exit;
    }
}

/**
 * Ensure the user is authenticated before continuing.
 *
 * @return void
 */
function require_login(): void
{
    global $pdo;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    if (!isset($_SESSION['user_id'])) {
        bushisa_auth_fail(401, 'Authentication required.');
    }

    if (!$pdo instanceof PDO) {
        bushisa_auth_fail(500, 'Authentication service unavailable.');
    }

    $userId = (int) $_SESSION['user_id'];
    $account = bushisa_current_account($pdo, $userId);
    if ($account === null || !bushisa_account_can_use_session($account)) {
        bushisa_auth_fail(401, 'Your session is no longer valid.');
    }

    $storedVersion = array_key_exists('session_version', $account) ? (int) $account['session_version'] : 0;
    $sessionVersion = isset($_SESSION['session_version']) ? (int) $_SESSION['session_version'] : 0;
    if ($sessionVersion !== $storedVersion) {
        bushisa_auth_fail(401, 'Your session is no longer valid.');
    }

    $_SESSION['role'] = isset($account['role']) && is_string($account['role']) && $account['role'] !== '' ? $account['role'] : 'user';
    $_SESSION['email'] = isset($account['email']) && is_string($account['email']) ? $account['email'] : ($_SESSION['email'] ?? '');

    bushisa_gate_pending_account((string) ($account['account_status'] ?? 'active'));
    refresh_session_expiry();
}

start_secure_session();
