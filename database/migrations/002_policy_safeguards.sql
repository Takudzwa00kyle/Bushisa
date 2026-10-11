-- Bushisa migration 002: proposed D04/D05 technical safeguards.
-- These structures support synthetic-development verification and independent
-- protective controls while D04/D05 policy choices remain pending.

ALTER TABLE matches
    ADD COLUMN match_status ENUM('active', 'blocked', 'unmatched', 'deleted_cleanup') NOT NULL DEFAULT 'active',
    ADD COLUMN closed_at TIMESTAMP NULL,
    ADD COLUMN closed_reason VARCHAR(60) NULL,
    ADD COLUMN closed_by INT UNSIGNED NULL,
    ADD INDEX idx_matches_status (match_status, closed_at);

ALTER TABLE message_requests
    MODIFY status ENUM('pending', 'approved', 'declined', 'cancelled', 'expired', 'revoked') NOT NULL DEFAULT 'pending',
    ADD COLUMN closed_at TIMESTAMP NULL,
    ADD COLUMN expires_at TIMESTAMP NULL,
    ADD COLUMN purge_due_at TIMESTAMP NULL,
    ADD COLUMN generation_key VARCHAR(80) NOT NULL DEFAULT 'legacy_v0',
    ADD INDEX idx_message_requests_expiry (status, expires_at),
    ADD INDEX idx_message_requests_purge (purge_due_at);

ALTER TABLE messages
    MODIFY match_id INT UNSIGNED NULL,
    ADD COLUMN evidence_purge_due_at TIMESTAMP NULL,
    ADD COLUMN ordinary_body_purged_at TIMESTAMP NULL,
    ADD COLUMN unsent_reason VARCHAR(80) NULL,
    ADD INDEX idx_messages_purge (evidence_purge_due_at),
    ADD INDEX idx_messages_unsent (unsent_at);

ALTER TABLE student_email_approval_codes
    MODIFY code_hash CHAR(64) NULL,
    ADD COLUMN terminal_at TIMESTAMP NULL,
    ADD COLUMN purged_at TIMESTAMP NULL,
    ADD INDEX idx_signup_codes_purge (terminal_at, purged_at);

ALTER TABLE mail_outbox
    MODIFY body TEXT NULL,
    ADD COLUMN expires_at TIMESTAMP NULL,
    ADD COLUMN payload_erased_at TIMESTAMP NULL,
    ADD COLUMN code_bearing TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN metadata_json JSON NULL,
    ADD INDEX idx_mail_outbox_cleanup (provider, code_bearing, expires_at, payload_erased_at);

ALTER TABLE confessions
    ADD COLUMN deleted_at TIMESTAMP NULL,
    ADD COLUMN deleted_by INT UNSIGNED NULL,
    ADD COLUMN purge_due_at TIMESTAMP NULL,
    ADD INDEX idx_confessions_deleted (deleted_at, purge_due_at);

ALTER TABLE confession_references
    ADD COLUMN deleted_at TIMESTAMP NULL,
    ADD COLUMN deleted_by INT UNSIGNED NULL;

CREATE TABLE IF NOT EXISTS moderation_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    target_type VARCHAR(40) NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    reported_user_id INT UNSIGNED NULL,
    status ENUM('pending', 'reviewed', 'dismissed', 'action_taken') NOT NULL DEFAULT 'pending',
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    final_resolution_at TIMESTAMP NULL,
    evidence_purge_due_at TIMESTAMP NULL,
    INDEX idx_moderation_cases_status (status, created_at),
    INDEX idx_moderation_cases_target (target_type, target_id),
    INDEX idx_moderation_cases_purge (evidence_purge_due_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moderation_case_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    assigned_user_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    UNIQUE KEY uq_case_assignment (case_id, assigned_user_id),
    INDEX idx_case_assignment_user (assigned_user_id, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moderation_evidence (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evidence_access_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    evidence_id BIGINT UNSIGNED NULL,
    viewer_user_id INT UNSIGNED NOT NULL,
    access_reason VARCHAR(255) NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evidence_access_case (case_id, accessed_at),
    INDEX idx_evidence_access_viewer (viewer_user_id, accessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cleanup_outbox (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cleanup_type VARCHAR(60) NOT NULL,
    subject_type VARCHAR(60) NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    payload_json JSON NULL,
    status ENUM('pending', 'running', 'done', 'failed') NOT NULL DEFAULT 'pending',
    run_after TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY uq_cleanup_item (cleanup_type, subject_type, subject_id),
    INDEX idx_cleanup_due (status, run_after)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS protective_replay_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(60) NOT NULL,
    subject_type VARCHAR(60) NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    payload_json JSON NOT NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    replayed_at TIMESTAMP NULL,
    UNIQUE KEY uq_replay_event (event_type, subject_type, subject_id, occurred_at),
    INDEX idx_replay_subject (subject_type, subject_id),
    INDEX idx_replay_pending (replayed_at, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('002_policy_safeguards');
