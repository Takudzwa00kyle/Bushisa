<?php

declare(strict_types=1);

/**
 * Operational contract helpers for feature tables that are required by the
 * product spec but absent from the original project schema.
 */

function operational_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?
         LIMIT 1'
    );

    $stmt->execute([$table]);

    return $stmt->fetchColumn() !== false;
}

function operational_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1'
    );
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn() !== false;
}

function operational_column_nullable(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT is_nullable
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1'
    );
    $stmt->execute([$table, $column]);
    $value = $stmt->fetchColumn();

    return is_string($value) && strtoupper($value) === 'YES';
}

function operational_runtime_ddl_allowed(): bool
{
    $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'development'));
    $explicit = getenv('BUSHISA_ALLOW_RUNTIME_DDL');
    if ($explicit !== false && $explicit !== '') {
        return filter_var($explicit, FILTER_VALIDATE_BOOL);
    }

    return $appEnv !== 'production';
}

function operational_match_columns(PDO $pdo): array
{
    return [
        'user_a' => operational_column_exists($pdo, 'matches', 'user_a_id') ? 'user_a_id' : 'user_one_id',
        'user_b' => operational_column_exists($pdo, 'matches', 'user_b_id') ? 'user_b_id' : 'user_two_id',
        'created_at' => operational_column_exists($pdo, 'matches', 'matched_at') ? 'matched_at' : 'created_at',
    ];
}

function operational_message_columns(PDO $pdo): array
{
    return [
        'body' => operational_column_exists($pdo, 'messages', 'body') ? 'body' : 'message_text',
        'created_at' => operational_column_exists($pdo, 'messages', 'sent_at') ? 'sent_at' : 'created_at',
    ];
}

function operational_swipe_columns(PDO $pdo): array
{
    return [
        'target' => operational_column_exists($pdo, 'swipes', 'swiped_id') ? 'swiped_id' : 'target_id',
        'direction' => operational_column_exists($pdo, 'swipes', 'direction') ? 'direction' : 'action',
    ];
}

function operational_confession_columns(PDO $pdo): array
{
    return [
        'author' => operational_column_exists($pdo, 'confessions', 'author_id') ? 'author_id' : 'user_id',
        'body' => operational_column_exists($pdo, 'confessions', 'body') ? 'body' : 'content',
    ];
}

function operational_create_policy_safeguard_tables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS moderation_cases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT UNSIGNED NOT NULL,
            target_type VARCHAR(40) NOT NULL,
            target_id BIGINT UNSIGNED NOT NULL,
            reported_user_id INT UNSIGNED NULL,
            status ENUM("pending", "reviewed", "dismissed", "action_taken") NOT NULL DEFAULT "pending",
            reason VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            final_resolution_at TIMESTAMP NULL,
            evidence_purge_due_at TIMESTAMP NULL,
            INDEX idx_moderation_cases_status (status, created_at),
            INDEX idx_moderation_cases_target (target_type, target_id),
            INDEX idx_moderation_cases_purge (evidence_purge_due_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS moderation_case_assignments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            case_id BIGINT UNSIGNED NOT NULL,
            assigned_user_id INT UNSIGNED NOT NULL,
            assigned_by INT UNSIGNED NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            revoked_at TIMESTAMP NULL,
            UNIQUE KEY uq_case_assignment (case_id, assigned_user_id),
            INDEX idx_case_assignment_user (assigned_user_id, revoked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS moderation_evidence (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            case_id BIGINT UNSIGNED NOT NULL,
            source_type VARCHAR(40) NOT NULL,
            source_id BIGINT UNSIGNED NOT NULL,
            subject_user_id INT UNSIGNED NULL,
            evidence_excerpt TEXT NULL,
            evidence_hash CHAR(64) NULL,
            captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            purge_due_at TIMESTAMP NULL,
            purged_at TIMESTAMP NULL,
            INDEX idx_evidence_case (case_id, purged_at),
            INDEX idx_evidence_source (source_type, source_id),
            INDEX idx_evidence_purge (purge_due_at, purged_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS evidence_access_audit (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            case_id BIGINT UNSIGNED NOT NULL,
            evidence_id BIGINT UNSIGNED NULL,
            viewer_user_id INT UNSIGNED NOT NULL,
            access_reason VARCHAR(255) NOT NULL,
            accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_evidence_access_case (case_id, accessed_at),
            INDEX idx_evidence_access_viewer (viewer_user_id, accessed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS cleanup_outbox (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cleanup_type VARCHAR(60) NOT NULL,
            subject_type VARCHAR(60) NOT NULL,
            subject_id BIGINT UNSIGNED NOT NULL,
            payload_json JSON NULL,
            status ENUM("pending", "running", "done", "failed") NOT NULL DEFAULT "pending",
            run_after TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_error VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            UNIQUE KEY uq_cleanup_item (cleanup_type, subject_type, subject_id),
            INDEX idx_cleanup_due (status, run_after)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS protective_replay_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(60) NOT NULL,
            subject_type VARCHAR(60) NOT NULL,
            subject_id BIGINT UNSIGNED NOT NULL,
            actor_user_id INT UNSIGNED NULL,
            payload_json JSON NOT NULL,
            occurred_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            replayed_at TIMESTAMP(6) NULL,
            UNIQUE KEY uq_replay_event (event_type, subject_type, subject_id, occurred_at),
            INDEX idx_replay_subject (subject_type, subject_id),
            INDEX idx_replay_pending (replayed_at, occurred_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    if (operational_table_exists($pdo, 'protective_replay_events')) {
        $pdo->exec('ALTER TABLE protective_replay_events MODIFY occurred_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)');
        $pdo->exec('ALTER TABLE protective_replay_events MODIFY replayed_at TIMESTAMP(6) NULL');
    }
}

function ensure_operational_schema(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    if (!operational_runtime_ddl_allowed()) {
        foreach (['rate_limits', 'audit_log', 'blocks', 'message_requests', 'notifications', 'notification_preferences', 'confession_views', 'confession_references', 'profile_images', 'find_match_answers', 'student_email_approval_codes', 'mail_outbox', 'moderation_cases', 'moderation_case_assignments', 'moderation_evidence', 'evidence_access_audit', 'cleanup_outbox', 'protective_replay_events'] as $requiredTable) {
            if (!operational_table_exists($pdo, $requiredTable)) {
                throw new RuntimeException('Operational schema is not migrated: missing table ' . $requiredTable);
            }
        }
        foreach (['approval_code_id', 'attempts', 'next_attempt_at'] as $mailColumn) {
            if (!operational_column_exists($pdo, 'mail_outbox', $mailColumn)) {
                throw new RuntimeException('Operational schema requires migration 004_smtp_outbox.');
            }
        }

        $ensured = true;
        return;
    }

    if (operational_table_exists($pdo, 'users')) {
        if (!operational_column_exists($pdo, 'users', 'role')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN role ENUM("user", "moderator", "admin") NOT NULL DEFAULT "user" AFTER password_hash');
        }
        if (!operational_column_exists($pdo, 'users', 'is_active')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_suspended');
        }
        if (!operational_column_exists($pdo, 'users', 'account_status')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN account_status ENUM("active", "pending_email", "pending_onboarding", "underage_blocked", "suspended", "deleted") NOT NULL DEFAULT "active" AFTER is_active');
        }
        if (!operational_column_exists($pdo, 'users', 'session_version')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER account_status');
        }
        if (!operational_column_exists($pdo, 'users', 'deleted_at')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL AFTER created_at');
        }
    }

    if (operational_table_exists($pdo, 'matches')) {
        if (!operational_column_exists($pdo, 'matches', 'match_status')) {
            $pdo->exec('ALTER TABLE matches ADD COLUMN match_status ENUM("active", "blocked", "unmatched", "deleted_cleanup") NOT NULL DEFAULT "active"');
        }
        if (!operational_column_exists($pdo, 'matches', 'closed_at')) {
            $pdo->exec('ALTER TABLE matches ADD COLUMN closed_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'matches', 'closed_reason')) {
            $pdo->exec('ALTER TABLE matches ADD COLUMN closed_reason VARCHAR(60) NULL');
        }
        if (!operational_column_exists($pdo, 'matches', 'closed_by')) {
            $pdo->exec('ALTER TABLE matches ADD COLUMN closed_by INT UNSIGNED NULL');
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS rate_limits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            identifier VARCHAR(100) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lookup (action, identifier, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_audit_user_time (user_id, timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blocks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            blocker_id INT UNSIGNED NOT NULL,
            blocked_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_block_pair (blocker_id, blocked_id),
            INDEX idx_blocks_blocker (blocker_id),
            INDEX idx_blocks_blocked (blocked_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS message_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            match_id INT UNSIGNED NOT NULL,
            requester_id INT UNSIGNED NOT NULL,
            recipient_id INT UNSIGNED NOT NULL,
            status ENUM("pending", "approved", "declined", "cancelled", "expired", "revoked") NOT NULL DEFAULT "pending",
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            responded_at TIMESTAMP NULL,
            revoked_at TIMESTAMP NULL,
            closed_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            purge_due_at TIMESTAMP NULL,
            generation_key VARCHAR(80) NOT NULL DEFAULT "legacy_v0",
            UNIQUE KEY uq_message_request_pair (match_id, requester_id, recipient_id),
            INDEX idx_message_request_match_status (match_id, status),
            INDEX idx_message_request_recipient_status (recipient_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipient_id INT UNSIGNED NOT NULL,
            category VARCHAR(50) NOT NULL,
            title VARCHAR(160) NOT NULL,
            body VARCHAR(500) NOT NULL,
            deep_link VARCHAR(255) NULL,
            dedupe_key VARCHAR(190) NULL,
            payload_json JSON NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_notification_dedupe (recipient_id, dedupe_key),
            INDEX idx_notifications_recipient (recipient_id, read_at, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notification_preferences (
            user_id INT UNSIGNED NOT NULL,
            category VARCHAR(50) NOT NULL,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS confession_views (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            confession_id INT UNSIGNED NOT NULL,
            viewer_hash CHAR(64) NOT NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_confession_view (confession_id, viewer_hash),
            INDEX idx_confession_views_count (confession_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS confession_references (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            confession_id INT UNSIGNED NOT NULL,
            referenced_confession_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_confession_reference (confession_id, referenced_confession_id),
            INDEX idx_confession_reference_target (referenced_confession_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS profile_images (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            slot ENUM("profile", "discovery_1", "discovery_2") NOT NULL,
            path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(80) NOT NULL,
            byte_size INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_profile_image_slot (user_id, slot),
            INDEX idx_profile_images_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS find_match_answers (
            user_id INT UNSIGNED NOT NULL,
            question_key VARCHAR(60) NOT NULL,
            answer_value VARCHAR(120) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, question_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    if (operational_table_exists($pdo, 'audit_log') && operational_column_exists($pdo, 'audit_log', 'ip_address') && !operational_column_nullable($pdo, 'audit_log', 'ip_address')) {
        $pdo->exec('ALTER TABLE audit_log MODIFY ip_address VARCHAR(45) NULL');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS student_email_approval_codes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            code_hash CHAR(64) NULL,
            code_version VARCHAR(40) NOT NULL DEFAULT "v1",
            expires_at TIMESTAMP NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            consumed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_signup_codes_user (user_id, consumed_at, expires_at),
            INDEX idx_signup_codes_email (email, consumed_at, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS mail_outbox (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipient_email VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            body TEXT NOT NULL,
            provider VARCHAR(40) NOT NULL DEFAULT "fake",
            status ENUM("pending", "sent", "failed") NOT NULL DEFAULT "pending",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            payload_erased_at TIMESTAMP NULL,
            code_bearing TINYINT(1) NOT NULL DEFAULT 0,
            metadata_json JSON NULL,
            approval_code_id BIGINT UNSIGNED NULL,
            attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            next_attempt_at TIMESTAMP NULL,
            INDEX idx_mail_outbox_status (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (operational_table_exists($pdo, 'find_match_answers')) {
        if (!operational_column_exists($pdo, 'find_match_answers', 'catalog_version')) {
            $pdo->exec('ALTER TABLE find_match_answers ADD COLUMN catalog_version VARCHAR(40) NOT NULL DEFAULT "legacy_v0" AFTER question_key');
        }
        if (!operational_column_exists($pdo, 'find_match_answers', 'answer_json')) {
            $pdo->exec('ALTER TABLE find_match_answers ADD COLUMN answer_json JSON NULL AFTER answer_value');
        }
        if (!operational_column_exists($pdo, 'find_match_answers', 'importance')) {
            $pdo->exec('ALTER TABLE find_match_answers ADD COLUMN importance TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER answer_json');
        }
        if (!operational_column_exists($pdo, 'find_match_answers', 'fit_mode')) {
            $pdo->exec('ALTER TABLE find_match_answers ADD COLUMN fit_mode VARCHAR(30) NOT NULL DEFAULT "similar" AFTER importance');
        }
        if (!operational_column_exists($pdo, 'find_match_answers', 'accepted_options_json')) {
            $pdo->exec('ALTER TABLE find_match_answers ADD COLUMN accepted_options_json JSON NULL AFTER fit_mode');
        }
        if (!operational_column_exists($pdo, 'find_match_answers', 'needs_review')) {
            $pdo->exec('ALTER TABLE find_match_answers ADD COLUMN needs_review TINYINT(1) NOT NULL DEFAULT 0 AFTER accepted_options_json');
        }
    }

    if (operational_table_exists($pdo, 'messages')) {
        if (!operational_column_nullable($pdo, 'messages', 'match_id')) {
            try {
                $pdo->exec('ALTER TABLE messages MODIFY match_id INT UNSIGNED NULL');
            } catch (Throwable) {
                // Legacy foreign-key definitions may require an explicit migration.
            }
        }
        if (!operational_column_exists($pdo, 'messages', 'unsent_at')) {
            $pdo->exec('ALTER TABLE messages ADD COLUMN unsent_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'messages', 'unsent_by')) {
            $pdo->exec('ALTER TABLE messages ADD COLUMN unsent_by INT UNSIGNED NULL');
        }
        if (!operational_column_exists($pdo, 'messages', 'moderation_body')) {
            $bodyColumn = operational_message_columns($pdo)['body'];
            $pdo->exec('ALTER TABLE messages ADD COLUMN moderation_body TEXT NULL AFTER ' . $bodyColumn);
        }
        if (!operational_column_exists($pdo, 'messages', 'evidence_purge_due_at')) {
            $pdo->exec('ALTER TABLE messages ADD COLUMN evidence_purge_due_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'messages', 'ordinary_body_purged_at')) {
            $pdo->exec('ALTER TABLE messages ADD COLUMN ordinary_body_purged_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'messages', 'unsent_reason')) {
            $pdo->exec('ALTER TABLE messages ADD COLUMN unsent_reason VARCHAR(80) NULL');
        }
    }

    if (operational_table_exists($pdo, 'message_requests')) {
        if (!operational_column_exists($pdo, 'message_requests', 'closed_at')) {
            $pdo->exec('ALTER TABLE message_requests ADD COLUMN closed_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'message_requests', 'expires_at')) {
            $pdo->exec('ALTER TABLE message_requests ADD COLUMN expires_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'message_requests', 'purge_due_at')) {
            $pdo->exec('ALTER TABLE message_requests ADD COLUMN purge_due_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'message_requests', 'generation_key')) {
            $pdo->exec('ALTER TABLE message_requests ADD COLUMN generation_key VARCHAR(80) NOT NULL DEFAULT "legacy_v0"');
        }
    }

    if (operational_table_exists($pdo, 'student_email_approval_codes')) {
        if (!operational_column_nullable($pdo, 'student_email_approval_codes', 'code_hash')) {
            $pdo->exec('ALTER TABLE student_email_approval_codes MODIFY code_hash CHAR(64) NULL');
        }
        if (!operational_column_exists($pdo, 'student_email_approval_codes', 'terminal_at')) {
            $pdo->exec('ALTER TABLE student_email_approval_codes ADD COLUMN terminal_at TIMESTAMP NULL');
        }
        if (!operational_column_exists($pdo, 'student_email_approval_codes', 'purged_at')) {
            $pdo->exec('ALTER TABLE student_email_approval_codes ADD COLUMN purged_at TIMESTAMP NULL');
        }
    }

    if (operational_table_exists($pdo, 'mail_outbox')) {
        if (!operational_column_nullable($pdo, 'mail_outbox', 'body')) {
            $pdo->exec('ALTER TABLE mail_outbox MODIFY body TEXT NULL');
        }
        foreach ([
            'expires_at' => 'TIMESTAMP NULL',
            'payload_erased_at' => 'TIMESTAMP NULL',
            'code_bearing' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'metadata_json' => 'JSON NULL',
            'approval_code_id' => 'BIGINT UNSIGNED NULL',
            'attempts' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
            'next_attempt_at' => 'TIMESTAMP NULL',
        ] as $column => $definition) {
            if (!operational_column_exists($pdo, 'mail_outbox', $column)) {
                $pdo->exec('ALTER TABLE mail_outbox ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    if (operational_table_exists($pdo, 'confessions')) {
        foreach ([
            'deleted_at' => 'TIMESTAMP NULL',
            'deleted_by' => 'INT UNSIGNED NULL',
            'purge_due_at' => 'TIMESTAMP NULL',
        ] as $column => $definition) {
            if (!operational_column_exists($pdo, 'confessions', $column)) {
                $pdo->exec('ALTER TABLE confessions ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    if (operational_table_exists($pdo, 'confession_references')) {
        foreach ([
            'deleted_at' => 'TIMESTAMP NULL',
            'deleted_by' => 'INT UNSIGNED NULL',
        ] as $column => $definition) {
            if (!operational_column_exists($pdo, 'confession_references', $column)) {
                $pdo->exec('ALTER TABLE confession_references ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    operational_create_policy_safeguard_tables($pdo);

    if (operational_table_exists($pdo, 'reports')) {
        if (operational_column_exists($pdo, 'reports', 'reported_user_id') && !operational_column_nullable($pdo, 'reports', 'reported_user_id')) {
            $pdo->exec('ALTER TABLE reports MODIFY reported_user_id INT UNSIGNED NULL');
        }
        if (!operational_column_exists($pdo, 'reports', 'target_type')) {
            $pdo->exec('ALTER TABLE reports ADD COLUMN target_type VARCHAR(40) NOT NULL DEFAULT "user"');
        }
        if (!operational_column_exists($pdo, 'reports', 'target_id')) {
            $pdo->exec('ALTER TABLE reports ADD COLUMN target_id INT UNSIGNED NULL');
        }
        if (!operational_column_exists($pdo, 'reports', 'confession_id')) {
            $pdo->exec('ALTER TABLE reports ADD COLUMN confession_id INT UNSIGNED NULL');
        }
    }

    $ensured = true;
}

function operational_is_blocked(PDO $pdo, int $userA, int $userB): bool
{
    ensure_operational_schema($pdo);

    $statement = $pdo->prepare(
        'SELECT 1
         FROM blocks
         WHERE (blocker_id = :user_a AND blocked_id = :user_b)
            OR (blocker_id = :reverse_user_b AND blocked_id = :reverse_user_a)
         LIMIT 1'
    );
    $statement->execute([
        ':user_a' => $userA,
        ':user_b' => $userB,
        ':reverse_user_b' => $userB,
        ':reverse_user_a' => $userA,
    ]);

    return $statement->fetchColumn() !== false;
}

function operational_get_match(PDO $pdo, int $matchId, int $currentUserId): ?array
{
    $columns = operational_match_columns($pdo);
    $statusFilter = operational_column_exists($pdo, 'matches', 'match_status')
        ? ' AND match_status = "active"'
        : '';
    $statement = $pdo->prepare(
        sprintf(
            'SELECT id, %1$s AS user_a_id, %2$s AS user_b_id
             FROM matches
             WHERE id = :match_id
               AND (%1$s = :current_user_id OR %2$s = :other_current_user_id)
               %3$s
             LIMIT 1',
            $columns['user_a'],
            $columns['user_b'],
            $statusFilter
        )
    );
    $statement->execute([
        ':match_id' => $matchId,
        ':current_user_id' => $currentUserId,
        ':other_current_user_id' => $currentUserId,
    ]);

    $match = $statement->fetch(PDO::FETCH_ASSOC);
    return $match === false ? null : $match;
}

function operational_other_user_id(array $match, int $currentUserId): int
{
    return (int) ((int) $match['user_a_id'] === $currentUserId ? $match['user_b_id'] : $match['user_a_id']);
}

function operational_has_message_approval(PDO $pdo, int $matchId): bool
{
    ensure_operational_schema($pdo);
    if (operational_table_exists($pdo,'pair_policy_history')) {
        $mc=operational_match_columns($pdo);
        $cutoff=$pdo->prepare(sprintf('SELECT ph.ending_count,ph.chat_suppressed FROM pair_policy_history ph
            JOIN matches m ON ph.user_low=LEAST(m.%1$s,m.%2$s) AND ph.user_high=GREATEST(m.%1$s,m.%2$s)
            WHERE m.id=?',$mc['user_a'],$mc['user_b']));
        $cutoff->execute([$matchId]);
        $policy=$cutoff->fetch(PDO::FETCH_ASSOC);
        if (is_array($policy) && ((int)$policy['ending_count']>=2 || (int)$policy['chat_suppressed']===1)) { return false; }
        $grant=$pdo->prepare('SELECT 1 FROM pair_chat_segments WHERE match_id=? AND ended_at IS NULL LIMIT 1');
        $grant->execute([$matchId]);
        if ($grant->fetchColumn()) { return true; }
    }

    $statement = $pdo->prepare(
        'SELECT 1
         FROM message_requests mr
         JOIN matches m ON m.id = mr.match_id
         WHERE mr.match_id = :match_id
           AND mr.status = "approved"
           AND mr.revoked_at IS NULL
           AND mr.id = (SELECT MAX(current_request.id) FROM message_requests current_request WHERE current_request.match_id=mr.match_id)
           AND (' . (operational_column_exists($pdo, 'matches', 'match_status') ? 'm.match_status IN ("active", "unmatched")' : '1 = 1') . ')
         LIMIT 1'
    );
    $statement->execute([':match_id' => $matchId]);

    return $statement->fetchColumn() !== false;
}

function operational_message_request_status(PDO $pdo, int $matchId, int $currentUserId, int $otherUserId): array
{
    ensure_operational_schema($pdo);

    $statement = $pdo->prepare(
        'SELECT *
         FROM message_requests
         WHERE match_id = :match_id
           AND ((requester_id = :current_user_id AND recipient_id = :other_user_id)
             OR (requester_id = :reverse_other_user_id AND recipient_id = :reverse_current_user_id))
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute([
        ':match_id' => $matchId,
        ':current_user_id' => $currentUserId,
        ':other_user_id' => $otherUserId,
        ':reverse_other_user_id' => $otherUserId,
        ':reverse_current_user_id' => $currentUserId,
    ]);

    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return [
            'status' => 'none',
            'is_requester' => false,
            'is_recipient' => false,
            'row' => null,
        ];
    }

    return [
        'status' => (string) $row['status'],
        'is_requester' => (int) $row['requester_id'] === $currentUserId,
        'is_recipient' => (int) $row['recipient_id'] === $currentUserId,
        'row' => $row,
    ];
}

function operational_write_notification(PDO $pdo, int $recipientId, string $category, string $title, string $body, ?string $deepLink = null, ?string $dedupeKey = null, array $payload = []): void
{
    ensure_operational_schema($pdo);

    $preference = $pdo->prepare(
        'SELECT is_enabled
         FROM notification_preferences
         WHERE user_id = :user_id AND category = :category
         LIMIT 1'
    );
    $preference->execute([
        ':user_id' => $recipientId,
        ':category' => $category,
    ]);
    $enabled = $preference->fetchColumn();
    if ($enabled !== false && (int) $enabled !== 1) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO notifications (recipient_id, category, title, body, deep_link, dedupe_key, payload_json, created_at)
         VALUES (:recipient_id, :category, :title, :body, :deep_link, :dedupe_key, :payload_json, NOW())
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            body = VALUES(body),
            deep_link = VALUES(deep_link),
            payload_json = VALUES(payload_json),
            created_at = NOW(),
            read_at = NULL'
    );
    $statement->execute([
        ':recipient_id' => $recipientId,
        ':category' => $category,
        ':title' => $title,
        ':body' => $body,
        ':deep_link' => $deepLink,
        ':dedupe_key' => $dedupeKey,
        ':payload_json' => $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
}

function operational_viewer_hash(int $viewerId): string
{
    return hash('sha256', 'user:' . $viewerId);
}

