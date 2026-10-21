-- Additive, purpose-separated password recovery. No legacy backfill.
CREATE TABLE IF NOT EXISTS password_reset_state (
    account_id INT UNSIGNED PRIMARY KEY,
    account_generation CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    issuance_generation BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME(6) NOT NULL,
    INDEX idx_reset_generation (account_generation)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_challenges (
    challenge_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    account_generation CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    recipient_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    issuance_generation BIGINT UNSIGNED NOT NULL,
    issued_session_version INT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    issued_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    consumed_at DATETIME(6) NULL,
    invalidated_at DATETIME(6) NULL,
    secret_purge_due_at DATETIME(6) NOT NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_reset_generation (account_id, issuance_generation),
    UNIQUE KEY uq_reset_token (token_hash),
    INDEX idx_reset_cleanup (secret_purge_due_at, token_hash),
    INDEX idx_reset_current (account_id, account_generation, issuance_generation, expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_mail_jobs (
    mail_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    challenge_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    account_id INT UNSIGNED NULL,
    account_generation CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    purpose ENUM('password_reset','password_changed') NOT NULL,
    recipient_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    encrypted_payload MEDIUMTEXT NULL,
    queued_at DATETIME(6) NOT NULL,
    useful_until DATETIME(6) NOT NULL,
    state ENUM('ready','claimed','inflight','accepted','accepted_after_cancel','failed','cancelled','uncertain') NOT NULL,
    reason_code VARCHAR(48) NULL,
    claim_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    claim_until DATETIME(6) NULL,
    cancel_requested_at DATETIME(6) NULL,
    terminal_at DATETIME(6) NULL,
    payload_purge_due_at DATETIME(6) NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME(6) NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_reset_mail_due (state, next_attempt_at, useful_until),
    INDEX idx_reset_mail_cleanup (payload_purge_due_at, state)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_rate_events (
    event_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    subject_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    occurred_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    INDEX idx_reset_rate_window (subject_hash, occurred_at),
    INDEX idx_reset_rate_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_rate_subjects (
    subject_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    touched_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;
