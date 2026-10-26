<?php

declare(strict_types=1);

require_once __DIR__ . '/operational.php';
require_once __DIR__ . '/logger.php';

function bushisa_policy_proposed_purge_due_sql(string $category): string
{
    return match ($category) {
        'message_request' => 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)',
        'closed_conversation' => 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 90 DAY)',
        'case_evidence' => 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 MONTH)',
        'synthetic_mail' => 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY)',
        'account_cleanup' => 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)',
        default => 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)',
    };
}

function bushisa_enqueue_cleanup(PDO $pdo, string $cleanupType, string $subjectType, int $subjectId, array $payload = []): void
{
    ensure_operational_schema($pdo);

    $statement = $pdo->prepare(
        'INSERT INTO cleanup_outbox (cleanup_type, subject_type, subject_id, payload_json, status, run_after, created_at)
         VALUES (:cleanup_type, :subject_type, :subject_id, :payload_json, "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE
            payload_json = COALESCE(VALUES(payload_json), payload_json),
            status = CASE WHEN status = "done" THEN status ELSE "pending" END,
            run_after = CASE WHEN status = "done" THEN run_after ELSE LEAST(run_after, UTC_TIMESTAMP()) END,
            last_error = NULL'
    );
    $statement->execute([
        ':cleanup_type' => $cleanupType,
        ':subject_type' => $subjectType,
        ':subject_id' => $subjectId,
        ':payload_json' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
}

function bushisa_record_protective_event(PDO $pdo, string $eventType, string $subjectType, int $subjectId, ?int $actorUserId, array $payload): void
{
    ensure_operational_schema($pdo);

    $statement = $pdo->prepare(
        'INSERT INTO protective_replay_events (event_type, subject_type, subject_id, actor_user_id, payload_json, occurred_at)
         VALUES (:event_type, :subject_type, :subject_id, :actor_user_id, :payload_json, UTC_TIMESTAMP(6))'
    );
    $statement->execute([
        ':event_type' => $eventType,
        ':subject_type' => $subjectType,
        ':subject_id' => $subjectId,
        ':actor_user_id' => $actorUserId,
        ':payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
}

function bushisa_account_is_chat_eligible(PDO $pdo, int $userId): bool
{
    $statement = $pdo->prepare(
        'SELECT u.id, u.is_verified, u.is_suspended, u.is_active, u.account_status, u.deleted_at, p.date_of_birth
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         WHERE u.id = :user_id
         LIMIT 1'
    );
    $statement->execute([':user_id' => $userId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return false;
    }

    if ((int) ($row['is_verified'] ?? 0) !== 1
        || (int) ($row['is_suspended'] ?? 0) === 1
        || (int) ($row['is_active'] ?? 1) !== 1
        || in_array((string) ($row['account_status'] ?? 'active'), ['pending_email', 'pending_onboarding', 'underage_blocked', 'suspended', 'deleted'], true)
        || ($row['deleted_at'] !== null && (string) $row['deleted_at'] !== '')
    ) {
        return false;
    }

    $dob = (string) ($row['date_of_birth'] ?? '');
    if ($dob === '') {
        return false;
    }

    try {
        $birthDate = new DateTimeImmutable($dob, new DateTimeZone('UTC'));
        $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    } catch (Throwable) {
        return false;
    }

    return $birthDate <= $today->modify('-18 years');
}

/**
 * @return array{ok: bool, error: string|null, status: int, match: array<string, mixed>|null, other_user_id: int|null}
 */
function bushisa_authorize_match_action(PDO $pdo, int $matchId, int $currentUserId, bool $requireApprovedChat, bool $lock = false, bool $allowReadOnly = false): array
{
    ensure_operational_schema($pdo);

    $columns = operational_match_columns($pdo);
    $statusFilter = operational_column_exists($pdo, 'matches', 'match_status')
        ? ($requireApprovedChat ? ' AND match_status IN ("active", "unmatched")' : ' AND match_status = "active"')
        : '';
    $lockSql = $lock ? ' FOR UPDATE' : '';

    $statement = $pdo->prepare(
        sprintf(
            'SELECT id, %1$s AS user_a_id, %2$s AS user_b_id
             FROM matches
             WHERE id = :match_id
               AND (%1$s = :current_user_a_id OR %2$s = :current_user_b_id)
               %3$s
             LIMIT 1%4$s',
            $columns['user_a'],
            $columns['user_b'],
            $statusFilter,
            $lockSql
        )
    );
    $statement->execute([
        ':match_id' => $matchId,
        ':current_user_a_id' => $currentUserId,
        ':current_user_b_id' => $currentUserId,
    ]);

    $match = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($match)) {
        return ['ok' => false, 'error' => 'Match not found', 'status' => 403, 'match' => null, 'other_user_id' => null];
    }

    $otherUserId = operational_other_user_id($match, $currentUserId);
    if (!bushisa_account_is_chat_eligible($pdo, $currentUserId) || !bushisa_account_is_chat_eligible($pdo, $otherUserId)) {
        return ['ok' => false, 'error' => 'This match is unavailable', 'status' => 403, 'match' => null, 'other_user_id' => null];
    }

    if (operational_is_blocked($pdo, $currentUserId, $otherUserId)) {
        return ['ok' => false, 'error' => 'This match is unavailable', 'status' => 403, 'match' => null, 'other_user_id' => null];
    }

    if ($requireApprovedChat && !operational_has_message_approval($pdo, $matchId)) {
        $readable=false;
        if ($allowReadOnly && operational_table_exists($pdo,'pair_chat_segments')) {
            $history=$pdo->prepare('SELECT 1 FROM pair_chat_segments WHERE match_id=? AND ended_at IS NOT NULL AND read_until>UTC_TIMESTAMP(6) LIMIT 1');
            $history->execute([$matchId]); $readable=(bool)$history->fetchColumn();
        }
        if (!$readable) { return ['ok' => false, 'error' => 'Chat permission has not been approved', 'status' => 403, 'match' => null, 'other_user_id' => null]; }
    }

    return ['ok' => true, 'error' => null, 'status' => 200, 'match' => $match, 'other_user_id' => $otherUserId];
}

function bushisa_revoke_match_requests(PDO $pdo, int $matchId, string $reason): void
{
    $statement = $pdo->prepare(
        'UPDATE message_requests
         SET status = CASE WHEN status = "pending" THEN "revoked" WHEN status = "approved" THEN "revoked" ELSE status END,
             revoked_at = CASE WHEN status IN ("pending", "approved") THEN UTC_TIMESTAMP() ELSE revoked_at END,
             closed_at = COALESCE(closed_at, UTC_TIMESTAMP()),
             purge_due_at = COALESCE(purge_due_at, ' . bushisa_policy_proposed_purge_due_sql('message_request') . ')
         WHERE match_id = :match_id'
    );
    $statement->execute([':match_id' => $matchId]);
    bushisa_suppress_chat_notifications_for_match($pdo, $matchId);

    bushisa_enqueue_cleanup($pdo, 'message_request_cleanup', 'match', $matchId, [
        'policy_status' => 'proposed',
        'reason' => $reason,
    ]);
}

function bushisa_suppress_chat_notifications_for_match(PDO $pdo, int $matchId): void
{
    ensure_operational_schema($pdo);

    $delete = $pdo->prepare(
        'DELETE FROM notifications
         WHERE (category = "message_request" OR category = "message_request_response")
           AND (deep_link = :deep_link
             OR dedupe_key LIKE :request_dedupe
             OR dedupe_key LIKE :response_dedupe)'
    );
    $delete->execute([
        ':deep_link' => 'chat.php?match_id=' . $matchId,
        ':request_dedupe' => 'message_request:' . $matchId . ':%',
        ':response_dedupe' => 'message_request_response:' . $matchId . ':%',
    ]);
}

function bushisa_close_match_between(PDO $pdo, int $actorUserId, int $otherUserId, string $reason): void
{
    ensure_operational_schema($pdo);
    require_once __DIR__ . '/pair_history.php';
    if (bushisa_pair_ready($pdo)) {
        bushisa_pair_close_relationship($pdo,$actorUserId,$otherUserId,$reason);
        return;
    }

    $columns = operational_match_columns($pdo);
    $statement = $pdo->prepare(
        sprintf(
            'SELECT id
             FROM matches
             WHERE (%1$s = :actor_a AND %2$s = :other_b)
                OR (%1$s = :other_a AND %2$s = :actor_b)
             FOR UPDATE',
            $columns['user_a'],
            $columns['user_b']
        )
    );
    $statement->execute([
        ':actor_a' => $actorUserId,
        ':other_b' => $otherUserId,
        ':other_a' => $otherUserId,
        ':actor_b' => $actorUserId,
    ]);
    $matchIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN) ?: []);

    foreach ($matchIds as $matchId) {
        if (operational_column_exists($pdo, 'matches', 'match_status')) {
            $status = $reason === 'deleted_cleanup' ? 'deleted_cleanup' : ($reason === 'unmatched' ? 'unmatched' : 'blocked');
            $close = $pdo->prepare(
                'UPDATE matches
                 SET match_status = CASE WHEN match_status = "active" THEN :status ELSE match_status END,
                     closed_at = COALESCE(closed_at, UTC_TIMESTAMP()),
                     closed_reason = COALESCE(closed_reason, :reason),
                     closed_by = COALESCE(closed_by, :actor_user_id)
                 WHERE id = :match_id'
            );
            $close->execute([
                ':status' => $status,
                ':reason' => $reason,
                ':actor_user_id' => $actorUserId,
                ':match_id' => $matchId,
            ]);
        }

        if ($reason !== 'unmatched') {
            bushisa_revoke_match_requests($pdo, $matchId, $reason);
            bushisa_enqueue_cleanup($pdo, 'closed_conversation_cleanup', 'match', $matchId, [
                'policy_status' => 'proposed',
                'purge_due_sql' => 'closed_conversation_plus_90_days',
                'reason' => $reason,
            ]);
        }
        bushisa_record_protective_event($pdo, 'match_closed', 'match', $matchId, $actorUserId, [
            'reason' => $reason,
            'other_user_id' => $otherUserId,
            'policy_status' => 'proposed',
        ]);
    }

    $swipeColumns = operational_swipe_columns($pdo);
    $deleteSwipes = $pdo->prepare(
        sprintf(
            'DELETE FROM swipes
             WHERE (swiper_id = :actor_swiper AND %1$s = :other_target)
                OR (swiper_id = :other_swiper AND %1$s = :actor_target)',
            $swipeColumns['target']
        )
    );
    $deleteSwipes->execute([
        ':actor_swiper' => $actorUserId,
        ':other_target' => $otherUserId,
        ':other_swiper' => $otherUserId,
        ':actor_target' => $actorUserId,
    ]);
}

function bushisa_create_moderation_case(PDO $pdo, int $reporterId, string $targetType, int $targetId, ?int $reportedUserId, string $reason): int
{
    ensure_operational_schema($pdo);

    $case = $pdo->prepare(
        'INSERT INTO moderation_cases (reporter_id, target_type, target_id, reported_user_id, reason, status, created_at, evidence_purge_due_at)
         VALUES (:reporter_id, :target_type, :target_id, :reported_user_id, :reason, "pending", UTC_TIMESTAMP(), ' . bushisa_policy_proposed_purge_due_sql('case_evidence') . ')'
    );
    $case->execute([
        ':reporter_id' => $reporterId,
        ':target_type' => $targetType,
        ':target_id' => $targetId,
        ':reported_user_id' => $reportedUserId,
        ':reason' => $reason,
    ]);
    $caseId = (int) $pdo->lastInsertId();

    bushisa_capture_case_evidence($pdo, $caseId, $targetType, $targetId, $reportedUserId);
    bushisa_record_protective_event($pdo, 'moderation_case_opened', 'moderation_case', $caseId, $reporterId, [
        'target_type' => $targetType,
        'target_id' => $targetId,
        'reported_user_id' => $reportedUserId,
        'policy_status' => 'proposed',
    ]);

    return $caseId;
}

function bushisa_capture_case_evidence(PDO $pdo, int $caseId, string $targetType, int $targetId, ?int $reportedUserId): void
{
    $excerpt = null;
    $sourceType = $targetType;
    $sourceId = $targetId;
    $subjectUserId = $reportedUserId;

    if ($targetType === 'message') {
        $columns = operational_message_columns($pdo);
        $statement = $pdo->prepare(
            sprintf('SELECT sender_id, %1$s AS body FROM messages WHERE id = :target_id LIMIT 1', $columns['body'])
        );
        $statement->execute([':target_id' => $targetId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            $subjectUserId = (int) $row['sender_id'];
            $excerpt = (string) ($row['body'] ?? '');
        }
    } elseif ($targetType === 'confession') {
        $columns = operational_confession_columns($pdo);
        $statement = $pdo->prepare(
            sprintf('SELECT %1$s AS author_id, %2$s AS body FROM confessions WHERE id = :target_id LIMIT 1', $columns['author'], $columns['body'])
        );
        $statement->execute([':target_id' => $targetId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            $subjectUserId = isset($row['author_id']) ? (int) $row['author_id'] : $reportedUserId;
            $excerpt = (string) ($row['body'] ?? '');
        }
    }

    if ($excerpt !== null) {
        $excerpt = function_exists('mb_substr') ? mb_substr($excerpt, 0, 1000) : substr($excerpt, 0, 1000);
    }

    $insert = $pdo->prepare(
        'INSERT INTO moderation_evidence (case_id, source_type, source_id, subject_user_id, evidence_excerpt, evidence_hash, captured_at, purge_due_at)
         VALUES (:case_id, :source_type, :source_id, :subject_user_id, :evidence_excerpt, :evidence_hash, UTC_TIMESTAMP(), ' . bushisa_policy_proposed_purge_due_sql('case_evidence') . ')'
    );
    $insert->execute([
        ':case_id' => $caseId,
        ':source_type' => $sourceType,
        ':source_id' => $sourceId,
        ':subject_user_id' => $subjectUserId,
        ':evidence_excerpt' => $excerpt,
        ':evidence_hash' => $excerpt === null ? null : hash('sha256', $excerpt),
    ]);
}

function bushisa_case_evidence_for_viewer(PDO $pdo, int $caseId, int $viewerUserId, string $reason): array
{
    // New intake never inherits the legacy administrator/reason bypass.
    if (operational_column_exists($pdo, 'moderation_cases', 'intake_contract_version')) {
        $newCase = $pdo->prepare('SELECT 1 FROM moderation_cases WHERE id = ? AND intake_contract_version = 1 LIMIT 1');
        $newCase->execute([$caseId]);
        if ($newCase->fetchColumn()) {
            require_once __DIR__ . '/report_intake.php';
            return bushisa_report_review_evidence($pdo, $caseId, $viewerUserId, $_SESSION ?? []);
        }
    }
    $roleStatement = $pdo->prepare('SELECT role FROM users WHERE id = :viewer_user_id LIMIT 1');
    $roleStatement->execute([':viewer_user_id' => $viewerUserId]);
    $role = (string) ($roleStatement->fetchColumn() ?: '');
    if (!in_array($role, ['moderator', 'admin'], true)) {
        return [];
    }

    $assignment = $pdo->prepare(
        'SELECT 1
         FROM moderation_case_assignments
         WHERE case_id = :case_id
           AND assigned_user_id = :viewer_user_id
           AND revoked_at IS NULL
         LIMIT 1'
    );
    $assignment->execute([
        ':case_id' => $caseId,
        ':viewer_user_id' => $viewerUserId,
    ]);

    $hasAssignment = $assignment->fetchColumn() !== false;
    $hasAuditedException = $role === 'admin' && trim($reason) !== '';
    if (!$hasAssignment && !$hasAuditedException) {
        return [];
    }

    $audit = $pdo->prepare(
        'INSERT INTO evidence_access_audit (case_id, evidence_id, viewer_user_id, access_reason, accessed_at)
         VALUES (:case_id, NULL, :viewer_user_id, :access_reason, UTC_TIMESTAMP())'
    );
    $audit->execute([
        ':case_id' => $caseId,
        ':viewer_user_id' => $viewerUserId,
        ':access_reason' => $hasAssignment ? $reason : 'audited_exception:' . $reason,
    ]);

    $evidence = $pdo->prepare(
        'SELECT id, source_type, source_id, subject_user_id, evidence_excerpt, evidence_hash, captured_at, purge_due_at
         FROM moderation_evidence
         WHERE case_id = :case_id
           AND purged_at IS NULL
         ORDER BY id ASC'
    );
    $evidence->execute([':case_id' => $caseId]);

    return $evidence->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function bushisa_prepare_account_deletion(PDO $pdo, int $userId, int $initiatorId): void
{
    ensure_operational_schema($pdo);
    require_once __DIR__ . '/password_reset.php';
    bushisa_reset_cancel_account($pdo, $userId);

    bushisa_record_protective_event($pdo, 'account_deleted', 'user', $userId, $initiatorId, [
        'policy_status' => 'proposed',
        'covers' => ['account', 'sessions', 'relationships', 'confessions', 'media', 'questionnaire', 'consent'],
    ]);

    $matchColumns = operational_match_columns($pdo);
    $matches = $pdo->prepare(
        sprintf(
            'SELECT id, %1$s AS user_a_id, %2$s AS user_b_id
             FROM matches
             WHERE %1$s = :user_a OR %2$s = :user_b
             FOR UPDATE',
            $matchColumns['user_a'],
            $matchColumns['user_b']
        )
    );
    $matches->execute([
        ':user_a' => $userId,
        ':user_b' => $userId,
    ]);
    foreach ($matches->fetchAll(PDO::FETCH_ASSOC) ?: [] as $match) {
        $otherUserId = operational_other_user_id($match, $userId);
        bushisa_close_match_between($pdo, $userId, $otherUserId, 'deleted_cleanup');
    }

    $swipeColumns = operational_swipe_columns($pdo);
    $deleteSwipes = $pdo->prepare(
        sprintf('DELETE FROM swipes WHERE swiper_id = :user_id OR %1$s = :target_user_id', $swipeColumns['target'])
    );
    $deleteSwipes->execute([
        ':user_id' => $userId,
        ':target_user_id' => $userId,
    ]);

    if (operational_table_exists($pdo, 'find_match_answers')) {
        $deleteAnswers = $pdo->prepare('DELETE FROM find_match_answers WHERE user_id = :user_id');
        $deleteAnswers->execute([':user_id' => $userId]);
    }

    if (operational_table_exists($pdo, 'profile_images')) {
        $images = $pdo->prepare('SELECT id, path FROM profile_images WHERE user_id = :user_id');
        $images->execute([':user_id' => $userId]);
        foreach ($images->fetchAll(PDO::FETCH_ASSOC) ?: [] as $image) {
            bushisa_enqueue_cleanup($pdo, 'media_delete', 'profile_image', (int) $image['id'], [
                'path' => (string) ($image['path'] ?? ''),
                'policy_status' => 'proposed',
            ]);
        }
        $deleteImages = $pdo->prepare('DELETE FROM profile_images WHERE user_id = :user_id');
        $deleteImages->execute([':user_id' => $userId]);
    }

    bushisa_soft_delete_owned_confessions($pdo, $userId, $initiatorId);
    bushisa_enqueue_cleanup($pdo, 'account_field_cleanup', 'user', $userId, [
        'policy_status' => 'proposed',
        'utc_deadline' => 'deleted_at_plus_30_days',
        'inventory' => ['credentials', 'dob', 'questionnaires', 'preferences', 'media', 'caches', 'logs', 'future_location_data'],
    ]);
}

function bushisa_soft_delete_owned_confessions(PDO $pdo, int $userId, int $initiatorId): void
{
    if (!operational_table_exists($pdo, 'confessions')) {
        return;
    }

    $columns = operational_confession_columns($pdo);
    $ownerColumn = $columns['author'];
    $bodyColumn = $columns['body'];

    $select = $pdo->prepare(
        sprintf('SELECT id, %1$s AS body FROM confessions WHERE %2$s = :user_id AND (deleted_at IS NULL OR CAST(deleted_at AS CHAR) = "")', $bodyColumn, $ownerColumn)
    );
    $select->execute([':user_id' => $userId]);

    foreach ($select->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $confessionId = (int) $row['id'];
        bushisa_record_protective_event($pdo, 'confession_deleted', 'confession', $confessionId, $initiatorId, [
            'user_id' => $userId,
            'policy_status' => 'proposed',
        ]);
        bushisa_enqueue_cleanup($pdo, 'confession_cleanup', 'confession', $confessionId, [
            'policy_status' => 'proposed',
        ]);
    }

    $update = $pdo->prepare(
        sprintf(
            'UPDATE confessions
             SET deleted_at = COALESCE(deleted_at, UTC_TIMESTAMP()),
                 deleted_by = COALESCE(deleted_by, :deleted_by),
                 purge_due_at = COALESCE(purge_due_at, %1$s),
                 is_flagged = 1,
                 %2$s = ""
             WHERE %3$s = :user_id',
            bushisa_policy_proposed_purge_due_sql('account_cleanup'),
            $bodyColumn,
            $ownerColumn
        )
    );
    $update->execute([
        ':deleted_by' => $initiatorId,
        ':user_id' => $userId,
    ]);

    if (operational_table_exists($pdo, 'confession_references')) {
        $markReferences = $pdo->prepare(
            'UPDATE confession_references cr
             LEFT JOIN confessions owned
                ON owned.id = cr.confession_id OR owned.id = cr.referenced_confession_id
             SET cr.deleted_at = COALESCE(cr.deleted_at, UTC_TIMESTAMP()),
                 cr.deleted_by = COALESCE(cr.deleted_by, :deleted_by)
             WHERE owned.' . $ownerColumn . ' = :user_id'
        );
        $markReferences->execute([
            ':deleted_by' => $initiatorId,
            ':user_id' => $userId,
        ]);
    }
}

function bushisa_erase_terminal_signup_material(PDO $pdo, int $userId, string $email): void
{
    require_once __DIR__.'/signup_lifecycle.php';
    bushisa_signup_require_schema($pdo);
    bushisa_signup_transaction($pdo,function()use($pdo,$userId):void {
        bushisa_signup_query($pdo,'SELECT id FROM users WHERE id=? FOR UPDATE',[$userId])->fetchColumn();
        // Identity is the account/code binding, not a caller-supplied address.
        bushisa_signup_cancel_material_locked($pdo,$userId,'terminal_challenge');
    });
}
