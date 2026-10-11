-- Bushisa migration 001: operational contract tables and non-destructive account fields.
-- Run with a migration database user before deploying application code.
-- Prefer `php bin/migrate.php`; it is idempotent across partially upgraded schemas.
-- This SQL file is valid for a first run against the current base schema.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(80) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN role ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user' AFTER password_hash,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_suspended,
    ADD COLUMN account_status ENUM('active', 'pending_email', 'pending_onboarding', 'underage_blocked', 'suspended', 'deleted') NOT NULL DEFAULT 'active' AFTER is_active,
    ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER account_status,
    ADD COLUMN deleted_at TIMESTAMP NULL AFTER created_at;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    identifier VARCHAR(100) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lookup (action, identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user_time (user_id, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE audit_log MODIFY ip_address VARCHAR(45) NULL;

CREATE TABLE IF NOT EXISTS blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT UNSIGNED NOT NULL,
    blocked_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_block_pair (blocker_id, blocked_id),
    INDEX idx_blocks_blocker (blocker_id),
    INDEX idx_blocks_blocked (blocked_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id INT UNSIGNED NOT NULL,
    requester_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'declined', 'revoked') NOT NULL DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    UNIQUE KEY uq_message_request_pair (match_id, requester_id, recipient_id),
    INDEX idx_message_request_match_status (match_id, status),
    INDEX idx_message_request_recipient_status (recipient_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT UNSIGNED NOT NULL,
    category VARCHAR(50) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS confession_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    confession_id INT UNSIGNED NOT NULL,
    viewer_hash CHAR(64) NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_confession_view (confession_id, viewer_hash),
    INDEX idx_confession_views_count (confession_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS confession_references (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    confession_id INT UNSIGNED NOT NULL,
    referenced_confession_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_confession_reference (confession_id, referenced_confession_id),
    INDEX idx_confession_reference_target (referenced_confession_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profile_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    slot ENUM('profile', 'discovery_1', 'discovery_2') NOT NULL,
    path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(80) NOT NULL,
    byte_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_profile_image_slot (user_id, slot),
    INDEX idx_profile_images_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS find_match_answers (
    user_id INT UNSIGNED NOT NULL,
    question_key VARCHAR(60) NOT NULL,
    catalog_version VARCHAR(40) NOT NULL DEFAULT 'legacy_v0',
    answer_value VARCHAR(120) NOT NULL,
    answer_json JSON NULL,
    importance TINYINT UNSIGNED NOT NULL DEFAULT 2,
    fit_mode VARCHAR(30) NOT NULL DEFAULT 'similar',
    accepted_options_json JSON NULL,
    needs_review TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, question_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_email_approval_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    code_hash CHAR(64) NOT NULL,
    code_version VARCHAR(40) NOT NULL DEFAULT 'v1',
    expires_at TIMESTAMP NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    consumed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_signup_codes_user (user_id, consumed_at, expires_at),
    INDEX idx_signup_codes_email (email, consumed_at, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_outbox (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    provider VARCHAR(40) NOT NULL DEFAULT 'fake',
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_mail_outbox_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE messages
    ADD COLUMN unsent_at TIMESTAMP NULL,
    ADD COLUMN unsent_by INT UNSIGNED NULL,
    ADD COLUMN moderation_body TEXT NULL;

ALTER TABLE reports
    MODIFY reported_user_id INT UNSIGNED NULL,
    ADD COLUMN target_type VARCHAR(40) NOT NULL DEFAULT 'user',
    ADD COLUMN target_id INT UNSIGNED NULL,
    ADD COLUMN confession_id INT UNSIGNED NULL;

INSERT IGNORE INTO schema_migrations (version) VALUES ('001_operational_contract');
