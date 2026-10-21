-- Synthetic new-writer scope only. No backfill, FK, cascade or payload storage.
-- The CLI migration helper adds nullable v2 source/manifest fields separately.
CREATE TABLE IF NOT EXISTS session_copy_coverage (
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    namespace_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    account_id INT NOT NULL,
    registered_at DATETIME(6) NOT NULL,
    root_version CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    writer_contract VARCHAR(32) NOT NULL,
    recorded_at DATETIME(6) NOT NULL,
    sealed_at DATETIME(6) NULL,
    PRIMARY KEY (account_key,namespace_id),
    KEY coverage_account (account_id,account_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_cleanup_jobs (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    namespace_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    root_version CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    stage VARCHAR(16) NOT NULL DEFAULT 'seal',
    state VARCHAR(16) NOT NULL DEFAULT 'queued',
    cursor_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    claim_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    claim_version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    claim_until DATETIME(6) NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_attempt_at DATETIME(6) NULL,
    error_code VARCHAR(64) NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NULL,
    KEY cleanup_work (state,next_attempt_at,claim_until)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_purge_items (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    copy_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    binding_epoch INT UNSIGNED NOT NULL,
    object_generation CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expected_identity CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    state VARCHAR(16) NOT NULL DEFAULT 'pending',
    intent_at DATETIME(6) NULL,
    verified_at DATETIME(6) NULL,
    PRIMARY KEY (event_ref,copy_ref)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_exclusions (
    copy_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    source_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    reason_code VARCHAR(48) NOT NULL,
    recorded_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_purge_receipts (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    source_receipt CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    component VARCHAR(32) NOT NULL,
    scope_version INT UNSIGNED NOT NULL,
    namespace_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    root_version CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    copy_count INT UNSIGNED NOT NULL,
    effective_at DATETIME(6) NOT NULL,
    purge_due_at DATETIME(6) NOT NULL,
    verified_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_recovery (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    account_id INT NOT NULL,
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    auth_floor INT UNSIGNED NOT NULL,
    action_name VARCHAR(32) NOT NULL,
    effective_at DATETIME(6) NOT NULL,
    purge_due_at DATETIME(6) NOT NULL,
    fenced_at DATETIME(6) NOT NULL,
    live_purged_at DATETIME(6) NULL,
    publication_state VARCHAR(24) NOT NULL DEFAULT 'unpublished',
    copy_inventory VARCHAR(24) NOT NULL DEFAULT 'unverified',
    retain_until DATETIME(6) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_cleanup_alerts (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    reason_code VARCHAR(64) NOT NULL,
    deadline_at DATETIME(6) NULL,
    first_at DATETIME(6) NOT NULL,
    escalate_at DATETIME(6) NULL,
    PRIMARY KEY (event_ref,reason_code)
) ENGINE=InnoDB;
