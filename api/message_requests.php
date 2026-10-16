<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/report_intake.php';
require_once __DIR__ . '/../php/http_test_barrier.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    json_response(['error' => 'Method not allowed'], 405);
}
require_login();
ensure_operational_schema($pdo);
$actor = (int) $_SESSION['user_id'];
$version = (int) $_SESSION['session_version'];
$payload = [];
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
    $state = bushisa_report_transaction($pdo, function () use ($pdo, $actor, $version, $matchId, $action): array {
        if (!bushisa_report_actor_ok($pdo, $actor, $version)) {
            throw new BushisaReportError(401, 'Your session is no longer valid');
        }
        $mc = operational_match_columns($pdo);
        $stmt = $pdo->prepare(sprintf('SELECT id, %1$s AS a, %2$s AS b, match_status FROM matches
            WHERE id = ? AND (%1$s = ? OR %2$s = ?) FOR UPDATE', $mc['user_a'], $mc['user_b']));
        $stmt->execute([$matchId, $actor, $actor]);
        $pair = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($pair)) {
            throw new BushisaReportError(404, 'Match unavailable');
        }
        $other = (int) ((int) $pair['a'] === $actor ? $pair['b'] : $pair['a']);
        // Own retained request metadata is reportable; this never grants profile/chat reads.
        if ($action === 'read') {
            $state = operational_message_request_status($pdo, $matchId, $actor, $other);
            $state['report_context'] = bushisa_report_context_for_response($pdo, $actor, 'match', $matchId);
            return $state;
        }
        if (!bushisa_account_is_chat_eligible($pdo, $actor) || !bushisa_account_is_chat_eligible($pdo, $other)
            || operational_is_blocked($pdo, $actor, $other)) {
            throw new BushisaReportError(403, 'This match is unavailable');
        }
        $stmt = $pdo->prepare('UPDATE message_requests SET status = "expired",
            closed_at = COALESCE(closed_at, expires_at), purge_due_at = COALESCE(purge_due_at, DATE_ADD(expires_at, INTERVAL 30 DAY))
            WHERE match_id = ? AND status = "pending" AND expires_at <= UTC_TIMESTAMP(6)');
        $stmt->execute([$matchId]);
        $state = operational_message_request_status($pdo, $matchId, $actor, $other);
        $row = $state['row'];
        if ($action === 'request') {
            if ($state['status'] === 'approved') {
                return $state;
            }
            if ($pair['match_status'] !== 'active') {
                throw new BushisaReportError(403, 'An eligible mutual match is required');
            }
            if ($state['status'] === 'pending') {
                throw new BushisaReportError(409, 'A pending request already exists');
            }
            $decline = $pdo->prepare('SELECT 1 FROM pair_decline_restrictions WHERE requester_id = ? AND recipient_id = ? AND cleared_at IS NULL');
            $decline->execute([$actor, $other]);
            if ($decline->fetchColumn()) {
                throw new BushisaReportError(409, 'This declined request direction is restricted');
            }
            if (is_array($row)) {
                if ($state['status'] === 'declined' && (int) $row['requester_id'] === $actor) {
                    throw new BushisaReportError(409, 'This declined request direction is restricted');
                }
                if (in_array($state['status'], ['cancelled', 'expired'], true)) {
                    $end = $state['status'] === 'expired' ? $row['expires_at'] : $row['closed_at'];
                    if ($end === null || bushisa_report_now($pdo) < bushisa_report_time($end)->modify('+12 hours')->format('Y-m-d H:i:s.u')) {
                        throw new BushisaReportError(409, 'The 12-hour request cooldown is active');
                    }
                }
                if ($state['status'] === 'revoked') {
                    // L02 must prove original ending actor/count/cutoff across rematches.
                    // Never fall back to the obsolete provisional unrestricted restart.
                    throw new BushisaReportError(409, 'Chat restart requires verified pair-history eligibility');
                }
            }
            if (!operational_table_exists($pdo, 'message_request_sends')) {
                throw new BushisaReportError(503, 'Request accounting migration is required');
            }
            $history = $pdo->prepare('SELECT request_count, history_verified_at FROM pair_request_counts
                WHERE requester_id = ? AND recipient_id = ? FOR UPDATE');
            $history->execute([$actor, $other]);
            $historyRow = $history->fetch(PDO::FETCH_ASSOC);
            if (!is_array($historyRow) || $historyRow['history_verified_at'] === null) {
                // A current mutable row/count cannot certify missing old sends,
                // declines or endings. L02 must establish actual provenance.
                throw new BushisaReportError(503, 'Pair history verification is required before new requests');
            }
            bushisa_report_request_allowance($pdo, $actor);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM message_request_sends WHERE recipient_id = ?
                AND created_at > DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 12 HOUR)');
            $stmt->execute([$other]);
            if ((int) $stmt->fetchColumn() >= 10) {
                throw new BushisaReportError(429, 'Recipient allowance reached');
            }
            if ((int) $historyRow['request_count'] >= 3) {
                throw new BushisaReportError(429, 'Lifetime pair allowance reached');
            }
            if (is_array($row)) {
                $stmt = $pdo->prepare('UPDATE message_requests SET requester_id = ?, recipient_id = ?, status = "pending",
                    requested_at = UTC_TIMESTAMP(6), expires_at = DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 7 DAY),
                    responded_at = NULL, revoked_at = NULL, closed_at = NULL, purge_due_at = NULL WHERE id = ?');
                $stmt->execute([$actor, $other, $row['id']]);
                $request = (int) $row['id'];
            } else {
                $stmt = $pdo->prepare('INSERT INTO message_requests (match_id, requester_id, recipient_id, status, requested_at, expires_at, generation_key)
                    VALUES (?, ?, ?, "pending", UTC_TIMESTAMP(6), DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 7 DAY), ?)');
                $stmt->execute([$matchId, $actor, $other, 'match:' . $matchId]);
                $request = (int) $pdo->lastInsertId();
            }
            bushisa_report_record_send($pdo, $actor, $other, $request);
            operational_write_notification($pdo, $other, 'message_request', 'Message request',
                'An established match requested permission to chat.', 'chat.php?match_id=' . $matchId,
                'message_request:' . $matchId . ':' . $other);
        } elseif ($action === 'approve' || $action === 'decline') {
            if (!$state['is_recipient'] || $state['status'] !== 'pending'
                || ($action === 'approve' && $pair['match_status'] !== 'active')) {
                throw new BushisaReportError(409, 'No eligible pending request to respond to');
            }
            $next = $action === 'approve' ? 'approved' : 'declined';
            $stmt = $pdo->prepare('UPDATE message_requests SET status = ?, responded_at = UTC_TIMESTAMP(6),
                closed_at = CASE WHEN ? = "declined" THEN UTC_TIMESTAMP(6) ELSE NULL END,
                purge_due_at = CASE WHEN ? = "declined" THEN DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 30 DAY) ELSE NULL END,
                revoked_at = NULL WHERE id = ? AND status = "pending"');
            $stmt->execute([$next, $next, $next, $row['id']]);
            if ($next === 'declined') {
                $decline = $pdo->prepare('INSERT INTO pair_decline_restrictions
                    (requester_id, recipient_id, source_match_id, declined_at, cleared_at) VALUES (?, ?, ?, UTC_TIMESTAMP(6), NULL)
                    ON DUPLICATE KEY UPDATE source_match_id = VALUES(source_match_id), declined_at = VALUES(declined_at), cleared_at = NULL');
                $decline->execute([$other, $actor, $matchId]);
            }
            operational_write_notification($pdo, $other, 'message_request',
                $next === 'approved' ? 'Message request approved' : 'Message request declined',
                $next === 'approved' ? 'Your match approved chat permission.' : 'Your match declined chat permission.',
                'chat.php?match_id=' . $matchId, 'message_request_response:' . $matchId . ':' . $other);
        } elseif ($action === 'cancel') {
            if (!$state['is_requester'] || $state['status'] !== 'pending') {
                throw new BushisaReportError(409, 'No pending request to cancel');
            }
            $stmt = $pdo->prepare('UPDATE message_requests SET status = "cancelled", closed_at = UTC_TIMESTAMP(6),
                purge_due_at = DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 30 DAY) WHERE id = ?');
            $stmt->execute([$row['id']]);
        } else {
            if (!in_array($state['status'], ['pending', 'approved'], true)) {
                throw new BushisaReportError(409, 'No active permission to revoke');
            }
            if ($state['status'] === 'approved' && $row['responded_at'] !== null) {
                $segment = hash('sha256', $matchId . ':' . $row['id'] . ':' . $row['responded_at']);
                $closeHistory = $pdo->prepare('UPDATE messages SET evidence_purge_due_at =
                    CASE WHEN evidence_purge_due_at IS NULL THEN DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 7 DAY)
                    ELSE LEAST(evidence_purge_due_at, DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 7 DAY)) END
                    WHERE match_id = ? AND report_segment_key = ?');
                $closeHistory->execute([$matchId, $segment]);
            }
            $stmt = $pdo->prepare('UPDATE message_requests SET status = "revoked", revoked_at = UTC_TIMESTAMP(6),
                closed_at = COALESCE(closed_at, UTC_TIMESTAMP(6)), purge_due_at = COALESCE(purge_due_at, DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 30 DAY))
                WHERE id = ?');
            $stmt->execute([$row['id']]);
            bushisa_suppress_chat_notifications_for_match($pdo, $matchId);
        }
        return operational_message_request_status($pdo, $matchId, $actor, $other);
    });
    json_response(['success' => true, 'data' => $state]);
} catch (BushisaReportError $e) {
    json_response(['error' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    log_to_file('Message request failed: ' . get_class($e), 'ERROR');
    json_response(['error' => 'Unable to update message permission'], 503);
}
