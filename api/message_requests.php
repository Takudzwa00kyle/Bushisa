<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/pair_history.php';
require_once __DIR__ . '/../php/http_test_barrier.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    json_response(['error' => 'Method not allowed'], 405);
}
require_login();
ensure_operational_schema($pdo);
$actor = (int) $_SESSION['user_id'];
$version = (int) $_SESSION['session_version'];
$payload = $_GET;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    $payload = is_array($payload) ? $payload : $_POST;
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['csrf_token'] ?? '';
    if (!is_string($token) || !validate_csrf_token($token)) {
        json_response(['error' => 'Invalid CSRF token'], 403);
    }
}
$matchId = clean_int($payload['match_id'] ?? $_GET['match_id'] ?? null);
if ($matchId === null || $matchId < 1) {
    json_response(['error' => 'Invalid match ID'], 400);
}
$action = $_SERVER['REQUEST_METHOD'] === 'GET' ? 'read' : ($payload['action'] ?? '');
if (!in_array($action, ['read', 'request', 'approve', 'decline', 'cancel', 'revoke'], true)) {
    json_response(['error' => 'Invalid request action'], 400);
}
session_write_close();
try {
    if ($action !== 'read') {
        bushisa_http_test_barrier('message_request_before_mutation');
    }
    $state = bushisa_pair_request($pdo,$actor,$version,$matchId,$action,$payload);
    json_response(['success' => true, 'data' => $state]);
} catch (BushisaReportError $e) {
    json_response(['error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    log_to_file('Message request failed: ' . get_class($e), 'ERROR');
    json_response(['error' => 'Unable to update message permission'], 503);
}
