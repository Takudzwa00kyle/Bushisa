-- Q11 attempt-only consumer. No legacy binding/backfill and no user DELETE.
-- The controlled migration helper adds the nullable generation column and
-- creates the immutable-key trigger/read-only schema-health routine.
CREATE TABLE IF NOT EXISTS signup_attempt_identities (
    account_id INT UNSIGNED PRIMARY KEY,
    attempt_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    registered_at DATETIME(6) NOT NULL,
    created_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS signup_attempt_erasure_jobs (
    receipt_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    attempt_key CHAR(64) NULL,
    registered_at DATETIME(6) NULL,
    account_version BIGINT UNSIGNED NOT NULL,
    clock_version BIGINT UNSIGNED NULL,
    expires_at DATETIME(6) NOT NULL,
    due_at DATETIME(6) NOT NULL,
    stage VARCHAR(24) NOT NULL DEFAULT 'core',
    state VARCHAR(24) NOT NULL DEFAULT 'queued',
    claim_hash CHAR(64) NULL,
    claim_until DATETIME(6) NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME(6) NULL,
    cursor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    counts_json JSON NOT NULL,
    error_code VARCHAR(64) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NULL,
    INDEX idx_attempt_erasure_work (state,next_attempt_at,due_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS signup_attempt_erasure_objects (
    receipt_id CHAR(64) NOT NULL,
    object_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    locator VARCHAR(190) NOT NULL,
    expected_version CHAR(64) NULL,
    state VARCHAR(24) NOT NULL DEFAULT 'pending',
    intent_at DATETIME(6) NULL,
    erased_at DATETIME(6) NULL,
    PRIMARY KEY (receipt_id,object_key)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS signup_attempt_erasure_receipts (
    receipt_id CHAR(64) NOT NULL,
    component VARCHAR(24) NOT NULL,
    attempt_key CHAR(64) NOT NULL,
    scope_version INT UNSIGNED NOT NULL,
    counts_json JSON NOT NULL,
    completed_at DATETIME(6) NOT NULL,
    PRIMARY KEY (receipt_id,component)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS signup_attempt_erasure_recovery (
    receipt_id CHAR(64) PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    attempt_key CHAR(64) NOT NULL,
    account_version BIGINT UNSIGNED NOT NULL,
    action_name VARCHAR(32) NOT NULL,
    effective_at DATETIME(6) NOT NULL,
    due_at DATETIME(6) NOT NULL,
    database_purged_at DATETIME(6) NULL,
    live_purged_at DATETIME(6) NULL,
    publication_state VARCHAR(24) NOT NULL DEFAULT 'unpublished',
    copy_inventory_state VARCHAR(24) NOT NULL DEFAULT 'unverified',
    retain_until DATETIME(6) NULL
) ENGINE=InnoDB;
