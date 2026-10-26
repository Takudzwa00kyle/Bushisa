-- Q11. Additive; no historical backfill, account activation or production schedule.
CREATE TABLE IF NOT EXISTS signup_lifecycle (
    account_id INT UNSIGNED PRIMARY KEY,
    registered_at DATETIME(6) NULL,
    unverified_expires_at DATETIME(6) NULL,
    first_email_approved_at DATETIME(6) NULL,
    onboarding_expires_at DATETIME(6) NULL,
    onboarding_completed_at DATETIME(6) NULL,
    expired_stage VARCHAR(24) NULL,
    expired_at DATETIME(6) NULL,
    disabled_at DATETIME(6) NULL,
    cleanup_due_at DATETIME(6) NULL,
    anchor_status VARCHAR(48) NOT NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    observed_at DATETIME(6) NOT NULL,
    INDEX idx_signup_unverified (unverified_expires_at),
    INDEX idx_signup_onboarding (onboarding_expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS signup_expiry_obligations (
    receipt_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL UNIQUE,
    stage VARCHAR(24) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    denied_at DATETIME(6) NOT NULL,
    live_purge_due_at DATETIME(6) NOT NULL,
    account_version BIGINT UNSIGNED NOT NULL,
    challenges_invalidated INT UNSIGNED NOT NULL,
    mail_copies_erased INT UNSIGNED NOT NULL,
    cleanup_state VARCHAR(32) NOT NULL DEFAULT 'pending_consumer',
    publication_state VARCHAR(32) NOT NULL DEFAULT 'unpublished',
    retain_until DATETIME(6) NULL,
    INDEX idx_signup_obligation (cleanup_state,live_purge_due_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS signup_mail_jobs (
    mail_id BIGINT UNSIGNED PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    code_id BIGINT UNSIGNED NOT NULL,
    queued_at DATETIME(6) NOT NULL,
    useful_until DATETIME(6) NOT NULL,
    state VARCHAR(32) NOT NULL,
    claim_hash CHAR(64) NULL,
    claimed_at DATETIME(6) NULL,
    claim_until DATETIME(6) NULL,
    started_at DATETIME(6) NULL,
    cancel_requested_at DATETIME(6) NULL,
    terminal_at DATETIME(6) NULL,
    accepted_at DATETIME(6) NULL,
    metadata_expires_at DATETIME(6) NULL,
    reason_code VARCHAR(48) NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_signup_job_account (account_id,code_id),
    INDEX idx_signup_job_state (state,useful_until)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS signup_lifecycle_alerts (
    alert_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    reason_code VARCHAR(48) NOT NULL,
    deadline_at DATETIME(6) NULL,
    observed_at DATETIME(6) NOT NULL,
    resolved_at DATETIME(6) NULL
) ENGINE=InnoDB;
