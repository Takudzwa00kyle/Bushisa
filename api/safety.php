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
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/policy_safeguards.php';
require_once __DIR__ . '/../php/http_test_barrier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody !== false ? $rawBody : '', true);
$payload = is_array($payload) ? $payload : $_POST;

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
    ? clean_enum($payload['action'], ['block', 'unblock', 'report'])
    : null;

if ($action === 'block' || $action === 'unblock') {
    $targetUserId = clean_int($payload['user_id'] ?? null);
    if ($targetUserId === null || $targetUserId <= 0 || $targetUserId === $currentUserId) {
        json_response(['error' => 'Invalid user'], 400);
    }

    if ($action === 'block') {
        try {
            $pdo->beginTransaction();
            bushisa_http_test_barrier('chat_relationship_mutation_before_update');
            $statement = $pdo->prepare(
                'INSERT IGNORE INTO blocks (blocker_id, blocked_id, created_at)
                 VALUES (:blocker_id, :blocked_id, NOW())'
            );
            $statement->execute([
                ':blocker_id' => $currentUserId,
                ':blocked_id' => $targetUserId,
            ]);

            bushisa_close_match_between($pdo, $currentUserId, $targetUserId, 'blocked');

            log_action($pdo, $currentUserId, 'block_user:' . $targetUserId, $_SERVER['REMOTE_ADDR'] ?? null);
            $pdo->commit();
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            log_to_file('Block failed for user ' . $currentUserId . ': ' . $throwable->getMessage(), 'ERROR');
            json_response(['error' => 'Unable to block user'], 500);
        }
    } else {
        $statement = $pdo->prepare(
            'DELETE FROM blocks
             WHERE blocker_id = :blocker_id AND blocked_id = :blocked_id'
        );
        $statement->execute([
            ':blocker_id' => $currentUserId,
            ':blocked_id' => $targetUserId,
        ]);

        log_action($pdo, $currentUserId, 'unblock_user:' . $targetUserId, $_SERVER['REMOTE_ADDR'] ?? null);
    }

    json_response(['success' => true, 'data' => null]);
}

if ($action === 'report') {
    $targetType = isset($payload['target_type']) && is_string($payload['target_type'])
        ? clean_enum($payload['target_type'], ['user', 'photo', 'message', 'match', 'confession', 'confession_reference', 'conversation'])
        : null;
    $targetId = clean_int($payload['target_id'] ?? null);
    $reportedUserId = clean_int($payload['reported_user_id'] ?? null);
    $reason = isset($payload['reason']) && is_string($payload['reason']) ? clean($payload['reason']) : '';
    $allowedReasons = ['harassment', 'hate_speech', 'explicit_content', 'spam', 'catfish_impersonation', 'other'];

    if ($targetType === null || $targetId === null || $targetId <= 0 || $reason === '') {
        json_response(['error' => 'Invalid report'], 400);
    }

    if (!in_array($reason, $allowedReasons, true)) {
        json_response(['error' => 'Invalid report reason'], 400);
    }

    if ($targetType === 'user') {
        $reportedUserId = $targetId;
    }

    if ($targetType === 'confession') {
        $confessionColumns = operational_confession_columns($pdo);
        $ownerStatement = $pdo->prepare(
            sprintf(
                'SELECT %1$s AS author_id FROM confessions WHERE id = :target_id LIMIT 1',
                $confessionColumns['author']
            )
        );
        $ownerStatement->execute([':target_id' => $targetId]);
        $ownerId = $ownerStatement->fetchColumn();
        $reportedUserId = $ownerId !== false && (int) $ownerId > 0 ? (int) $ownerId : null;
        if ($reportedUserId === null) {
            json_response(['error' => 'Invalid report target'], 403);
        }
    }

    if ($targetType === 'message') {
        $matchColumns = operational_match_columns($pdo);
        $ownerStatement = $pdo->prepare(
            sprintf(
                'SELECT msg.sender_id
                 FROM messages msg
                 JOIN matches m ON m.id = msg.match_id
                 WHERE msg.id = :target_id
                   AND (%1$s = :current_user_a_id OR %2$s = :current_user_b_id)
                 LIMIT 1',
                $matchColumns['user_a'],
                $matchColumns['user_b']
            )
        );
        $ownerStatement->execute([
            ':target_id' => $targetId,
            ':current_user_a_id' => $currentUserId,
            ':current_user_b_id' => $currentUserId,
        ]);
        $ownerId = $ownerStatement->fetchColumn();
        $reportedUserId = $ownerId !== false && (int) $ownerId > 0 ? (int) $ownerId : null;
        if ($reportedUserId === null) {
            json_response(['error' => 'Invalid report target'], 403);
        }
    }

    if ($reportedUserId === $currentUserId) {
        json_response(['error' => 'You cannot report yourself'], 400);
    }

    $reportedUserNullable = operational_column_nullable($pdo, 'reports', 'reported_user_id');

    if ($reportedUserId === null && !$reportedUserNullable) {
        json_response(['error' => 'This report target needs a user-based report contract first'], 501);
    }

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'INSERT INTO reports (reporter_id, reported_user_id, confession_id, target_type, target_id, reason, status, created_at)
             VALUES (:reporter_id, :reported_user_id, :confession_id, :target_type, :target_id, :reason, "pending", UTC_TIMESTAMP())'
        );
        $statement->execute([
            ':reporter_id' => $currentUserId,
            ':reported_user_id' => $reportedUserId,
            ':confession_id' => $targetType === 'confession' ? $targetId : null,
            ':target_type' => $targetType,
            ':target_id' => $targetId,
            ':reason' => $reason,
        ]);

        $caseId = bushisa_create_moderation_case($pdo, $currentUserId, $targetType, $targetId, $reportedUserId, $reason);
        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        log_to_file('Safety report failed for user ' . $currentUserId . ': ' . $throwable->getMessage(), 'ERROR');
        json_response(['error' => 'Unable to submit report'], 500);
    }

    log_action($pdo, $currentUserId, 'report_' . $targetType . ':' . $targetId, $_SERVER['REMOTE_ADDR'] ?? null);
    json_response(['success' => true, 'data' => ['status' => 'pending', 'case_id' => $caseId]]);
}

json_response(['error' => 'Invalid safety action'], 400);
