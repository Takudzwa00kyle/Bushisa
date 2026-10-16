<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/logger.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/report_intake.php';
require_once __DIR__ . '/../php/block_handler.php';
require_once __DIR__ . '/../php/http_test_barrier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
require_login();
ensure_operational_schema($pdo);
$raw = file_get_contents('php://input', false, null, 0, 262145);
if ($raw !== false && strlen($raw) > 262144) {
    json_response(['error' => 'Invalid report'], 400);
}
$decoded = json_decode($raw ?: '', true);
$payload = is_array($decoded) ? $decoded : $_POST;
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['csrf_token'] ?? '';
if (!is_string($token) || $token === '' || !validate_csrf_token($token)) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}
$actor = (int) $_SESSION['user_id'];
$auth = $_SESSION;
$version = (int) ($_SESSION['session_version'] ?? 0);
session_write_close(); // Same-account retries must reach distinct workers, not a PHP session mutex.
$action = $payload['action'] ?? '';
try {
    if (in_array($action, ['report', 'block_and_report', 'report_addendum'], true)) {
        if (isset($_SERVER['HTTP_IDEMPOTENCY_KEY'])) {
            if (isset($payload['idempotency_key']) && $payload['idempotency_key'] !== $_SERVER['HTTP_IDEMPOTENCY_KEY']) {
                throw new BushisaReportError(400, 'Invalid submission key');
            }
            $payload['idempotency_key'] = $_SERVER['HTTP_IDEMPOTENCY_KEY'];
        }
        $received = bushisa_report_now($pdo);
        bushisa_http_test_barrier('report_before_transaction');
        $data = bushisa_report_intake($pdo, $actor, $payload, $version, $received);
    } elseif ($action === 'report_receipt') {
        if (array_diff(array_keys($payload), ['action', 'receipt_id', 'csrf_token'])
            || !is_string($payload['receipt_id'] ?? null)) {
            throw new BushisaReportError(400, 'Invalid receipt request');
        }
        if (!preg_match('/\A[a-f0-9]{64}\z/', $payload['receipt_id'])) {
            throw new BushisaReportError(404);
        }
        $data = bushisa_report_own_status($pdo, $actor, $payload['receipt_id'], $version);
    } elseif ($action === 'clear_block_report' || $action === 'block_report_review') {
        if (array_diff(array_keys($payload), ['action', 'case_id', 'block_id', 'csrf_token', 'review_version'])) {
            throw new BushisaReportError(400, 'Invalid review request');
        }
        $case = clean_int($payload['case_id'] ?? null);
        $block = clean_int($payload['block_id'] ?? null);
        if ($case === null || $case < 1 || $block === null || $block < 1) {
            throw new BushisaReportError(400, 'Invalid review request');
        }
        if ($action === 'block_report_review') {
            $data = bushisa_report_block_review($pdo, $actor, $case, $block, $auth);
        } else {
            if (isset($payload['review_version']) && !is_string($payload['review_version'])) {
                throw new BushisaReportError(400, 'Invalid review version');
            }
            bushisa_report_clear_block($pdo, $actor, $case, $block, $auth, $payload['review_version'] ?? '');
            $data = ['status' => 'clearance_recorded'];
        }
    } elseif ($action === 'block' || $action === 'unblock') {
        $target = clean_int($payload['user_id'] ?? null);
        if ($target === null || $target < 1 || $target === $actor) {
            throw new BushisaReportError(400, 'Invalid user');
        }
        bushisa_http_test_barrier('chat_relationship_mutation_before_update');
        if ($action === 'block') {
            block_user($pdo, $actor, $target, $version);
        } else {
            unblock_user($pdo, $actor, $target, $version);
        }
        $data = null;
    } else {
        throw new BushisaReportError(400, 'Invalid safety action');
    }
    json_response(['success' => true, 'data' => $data]);
} catch (BushisaReportError $error) {
    json_response(['error' => $error->getMessage()], $error->status);
} catch (Throwable $error) {
    // No SQL values, report text, keys, proofs or identities in routine error logs.
    log_to_file('Safety operation failed: ' . get_class($error), 'ERROR');
    json_response(['error' => 'Unable to complete safety operation; retry with the same submission key'], 503);
}
