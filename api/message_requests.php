<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/rate_limit.php';
require_once __DIR__ . '/../php/logger.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/policy_safeguards.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$matchId = clean_int($_GET['match_id'] ?? $_POST['match_id'] ?? null);

$payload = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody !== false ? $rawBody : '', true);
    $payload = is_array($decoded) ? $decoded : $_POST;
    $matchId = clean_int($payload['match_id'] ?? $matchId);
}

if ($matchId === null || $matchId <= 0) {
    json_response(['error' => 'Invalid match ID'], 400);
}

$authorization = bushisa_authorize_match_action($pdo, $matchId, $currentUserId, false);
if (!$authorization['ok']) {
    json_response(['error' => $authorization['error'] ?? 'This match is unavailable'], $authorization['status']);
}
$otherUserId = (int) $authorization['other_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response([
        'success' => true,
        'data' => operational_message_request_status($pdo, $matchId, $currentUserId, $otherUserId),
    ]);
}

$submittedToken = '';
if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
} elseif (isset($payload['csrf_token']) && is_string($payload['csrf_token'])) {
    $submittedToken = $payload['csrf_token'];
}

if ($submittedToken === '' || !validate_csrf_token($submittedToken)) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

$action = isset($payload['action']) && is_string($payload['action'])
    ? clean_enum($payload['action'], ['request', 'approve', 'decline', 'cancel', 'revoke'])
    : null;

if ($action === null) {
    json_response(['error' => 'Invalid request action'], 400);
}

if (!check_rate_limit($pdo, 'message_request', (string) $currentUserId, 20, 3600)) {
    json_response(['error' => 'Rate limit exceeded'], 429);
}

$expirePending = $pdo->prepare(
    'UPDATE message_requests
     SET status = "expired",
         closed_at = COALESCE(closed_at, expires_at),
         purge_due_at = COALESCE(purge_due_at, ' . bushisa_policy_proposed_purge_due_sql('message_request') . ')
     WHERE match_id = :match_id
       AND status = "pending"
       AND expires_at IS NOT NULL
       AND expires_at <= UTC_TIMESTAMP()'
);
$expirePending->execute([':match_id' => $matchId]);

$state = operational_message_request_status($pdo, $matchId, $currentUserId, $otherUserId);

try {
    record_attempt($pdo, 'message_request', (string) $currentUserId);

    if ($action === 'request') {
        if ($state['status'] === 'approved') {
            json_response(['success' => true, 'data' => $state]);
        }
        if ($state['status'] === 'pending') {
            json_response(['error' => 'A pending request already exists for this match'], 409);
        }
        if (is_array($state['row']) && in_array($state['status'], ['declined', 'cancelled', 'expired'], true)) {
            $minimumColumn = $state['status'] === 'declined'
                ? 'DATE_ADD(COALESCE(responded_at, requested_at), INTERVAL 30 DAY)'
                : 'DATE_ADD(COALESCE(closed_at, requested_at), INTERVAL 24 HOUR)';
            $cooldown = $pdo->prepare(
                'SELECT CASE WHEN UTC_TIMESTAMP() < ' . $minimumColumn . ' THEN 1 ELSE 0 END
                 FROM message_requests
                 WHERE id = :id'
            );
            $cooldown->execute([':id' => (int) $state['row']['id']]);
            if ((int) $cooldown->fetchColumn() === 1) {
                json_response(['error' => 'Re-request cooldown is still active under the proposed D04 rules'], 409);
            }
        }

        $statement = $pdo->prepare(
            'INSERT INTO message_requests (match_id, requester_id, recipient_id, status, requested_at, expires_at, generation_key)
             VALUES (:match_id, :requester_id, :recipient_id, "pending", UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), :generation_key)
             ON DUPLICATE KEY UPDATE
                status = CASE WHEN status IN ("revoked", "declined", "cancelled", "expired") THEN "pending" ELSE status END,
                requested_at = CASE WHEN status IN ("revoked", "declined", "cancelled", "expired") THEN UTC_TIMESTAMP() ELSE requested_at END,
                responded_at = CASE WHEN status IN ("revoked", "declined", "cancelled", "expired") THEN NULL ELSE responded_at END,
                revoked_at = CASE WHEN status IN ("revoked", "declined", "cancelled", "expired") THEN NULL ELSE revoked_at END,
                closed_at = CASE WHEN status IN ("revoked", "declined", "cancelled", "expired") THEN NULL ELSE closed_at END,
                expires_at = CASE WHEN status IN ("revoked", "declined", "cancelled", "expired") THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY) ELSE expires_at END,
                generation_key = VALUES(generation_key)'
        );
        $statement->execute([
            ':match_id' => $matchId,
            ':requester_id' => $currentUserId,
            ':recipient_id' => $otherUserId,
            ':generation_key' => 'match:' . $matchId,
        ]);

        operational_write_notification(
            $pdo,
            $otherUserId,
            'message_request',
            'Message request',
            'An established match requested permission to chat.',
            'chat.php?match_id=' . $matchId,
            'message_request:' . $matchId . ':' . $otherUserId
        );
    } elseif ($action === 'approve' || $action === 'decline') {
        if (!$state['is_recipient'] || $state['status'] !== 'pending') {
            json_response(['error' => 'No pending request to respond to'], 409);
        }

        $nextStatus = $action === 'approve' ? 'approved' : 'declined';
        $statement = $pdo->prepare(
            'UPDATE message_requests
             SET status = :status,
                 responded_at = UTC_TIMESTAMP(),
                 closed_at = CASE WHEN :status_for_close = "declined" THEN UTC_TIMESTAMP() ELSE closed_at END,
                 purge_due_at = CASE WHEN :status_for_purge = "declined" THEN ' . bushisa_policy_proposed_purge_due_sql('message_request') . ' ELSE purge_due_at END,
                 revoked_at = NULL
             WHERE match_id = :match_id
               AND recipient_id = :current_user_id
               AND requester_id = :other_user_id
               AND status = "pending"'
        );
        $statement->execute([
            ':status' => $nextStatus,
            ':status_for_close' => $nextStatus,
            ':status_for_purge' => $nextStatus,
            ':match_id' => $matchId,
            ':current_user_id' => $currentUserId,
            ':other_user_id' => $otherUserId,
        ]);

        operational_write_notification(
            $pdo,
            $otherUserId,
            'message_request',
            $nextStatus === 'approved' ? 'Message request approved' : 'Message request declined',
            $nextStatus === 'approved'
                ? 'Your match approved chat permission.'
                : 'Your match declined chat permission.',
            'chat.php?match_id=' . $matchId,
            'message_request_response:' . $matchId . ':' . $otherUserId
        );
    } elseif ($action === 'cancel') {
        if (!$state['is_requester'] || $state['status'] !== 'pending') {
            json_response(['error' => 'No pending request to cancel'], 409);
        }

        $statement = $pdo->prepare(
            'UPDATE message_requests
             SET status = "cancelled",
                 closed_at = UTC_TIMESTAMP(),
                 purge_due_at = COALESCE(purge_due_at, ' . bushisa_policy_proposed_purge_due_sql('message_request') . ')
             WHERE match_id = :match_id
               AND requester_id = :current_user_id
               AND recipient_id = :other_user_id
               AND status = "pending"'
        );
        $statement->execute([
            ':match_id' => $matchId,
            ':current_user_id' => $currentUserId,
            ':other_user_id' => $otherUserId,
        ]);
    } elseif ($action === 'revoke') {
        if ($state['status'] === 'none') {
            json_response(['error' => 'No message permission exists'], 409);
        }

        $statement = $pdo->prepare(
            'UPDATE message_requests
             SET status = "revoked",
                 revoked_at = UTC_TIMESTAMP(),
                 closed_at = COALESCE(closed_at, UTC_TIMESTAMP()),
                 purge_due_at = COALESCE(purge_due_at, ' . bushisa_policy_proposed_purge_due_sql('message_request') . ')
             WHERE match_id = :match_id
               AND ((requester_id = :current_requester_user_id AND recipient_id = :current_recipient_user_id)
                 OR (requester_id = :reverse_requester_user_id AND recipient_id = :reverse_recipient_user_id))
               AND status IN ("pending", "approved")'
        );
        $statement->execute([
            ':match_id' => $matchId,
            ':current_requester_user_id' => $currentUserId,
            ':current_recipient_user_id' => $otherUserId,
            ':reverse_requester_user_id' => $otherUserId,
            ':reverse_recipient_user_id' => $currentUserId,
        ]);
    }

    log_action($pdo, $currentUserId, 'message_request_' . $action . ':' . $matchId, $_SERVER['REMOTE_ADDR'] ?? null);

    json_response([
        'success' => true,
        'data' => operational_message_request_status($pdo, $matchId, $currentUserId, $otherUserId),
    ]);
} catch (Throwable $throwable) {
    log_to_file('Message request action failed for user ' . $currentUserId . ': ' . $throwable->getMessage(), 'ERROR');
    json_response(['error' => 'Unable to update message permission'], 500);
}

