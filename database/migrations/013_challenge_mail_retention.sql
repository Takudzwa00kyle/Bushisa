-- Q22: new writer-bound records only. No legacy anchor backfill or scheduling.
CREATE TABLE IF NOT EXISTS verification_retention (
    record_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    kind VARCHAR(16) NOT NULL,
    record_id BIGINT UNSIGNED NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    account_created_at DATETIME(6) NOT NULL,
    code_id BIGINT UNSIGNED NOT NULL,
    code_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source_created_at DATETIME(6) NOT NULL,
    source_expires_at DATETIME(6) NOT NULL,
    terminal_at DATETIME(6) NULL,
    terminal_kind VARCHAR(32) NULL,
    payload_due_at DATETIME(6) NULL,
    purge_due_at DATETIME(6) NULL,
    secret_erased_at DATETIME(6) NULL,
    purged_at DATETIME(6) NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    next_check_at DATETIME(6) NOT NULL,
    error_code VARCHAR(48) NULL,
    UNIQUE KEY uq_verification_record (kind,record_id),
    INDEX idx_verification_due (next_check_at,purged_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verification_cleanup_receipts (
    receipt_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    record_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    action_name VARCHAR(32) NOT NULL,
    deadline_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_verification_action (record_key,action_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verification_recovery_obligations (
    instruction_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    record_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    action_name VARCHAR(32) NOT NULL,
    effective_at DATETIME(6) NOT NULL,
    verified_purge_at DATETIME(6) NULL,
    publication_state VARCHAR(24) NOT NULL DEFAULT 'unpublished',
    copy_inventory VARCHAR(24) NOT NULL DEFAULT 'unverified',
    retain_until DATETIME(6) NULL,
    UNIQUE KEY uq_verification_recovery (record_key,action_name)
) ENGINE=InnoDB;

-- Only an unresolved carrier's claim verifier survives ordinary metadata purge.
-- This cannot authorize delivery, expose content or change account permissions.
CREATE TABLE IF NOT EXISTS verification_late_results (
    mail_id BIGINT UNSIGNED PRIMARY KEY,
    record_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    claim_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    result_state VARCHAR(32) NOT NULL,
    observed_at DATETIME(6) NOT NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verification_cleanup_alerts (
    alert_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    record_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    reason_code VARCHAR(48) NOT NULL,
    deadline_at DATETIME(6) NULL,
    observed_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;
