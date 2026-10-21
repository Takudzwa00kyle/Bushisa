-- New, explicitly mapped sessions only. No backfill, account cascade or payload.
CREATE TABLE IF NOT EXISTS session_bindings (
    session_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    sid_hmac CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    namespace_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    key_version SMALLINT UNSIGNED NOT NULL,
    account_id INT NULL,
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    auth_version INT UNSIGNED NULL,
    binding_epoch INT UNSIGNED NOT NULL DEFAULT 1,
    revision BIGINT UNSIGNED NOT NULL DEFAULT 0,
    state VARCHAR(16) NOT NULL,
    current_copy CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    created_at DATETIME(6) NOT NULL,
    retired_at DATETIME(6) NULL,
    reason_code VARCHAR(48) NULL,
    UNIQUE KEY session_lookup (namespace_id,key_version,sid_hmac),
    KEY session_account (account_id,account_key,state)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_manifests (
    copy_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    session_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    binding_epoch INT UNSIGNED NOT NULL,
    expected_revision BIGINT UNSIGNED NOT NULL,
    namespace_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    adapter_version VARCHAR(32) NOT NULL,
    instance_id VARCHAR(32) NOT NULL,
    state VARCHAR(16) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    published_at DATETIME(6) NULL,
    error_code VARCHAR(48) NULL,
    KEY session_copies (session_ref,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_lineage (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    old_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    new_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    event_type VARCHAR(24) NOT NULL,
    created_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_handoffs (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    session_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    old_version INT UNSIGNED NOT NULL,
    new_version INT UNSIGNED NOT NULL,
    expected_revision BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(24) NOT NULL,
    state VARCHAR(16) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    completed_at DATETIME(6) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS session_copy_obligations (
    event_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    account_id INT NOT NULL,
    account_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source_receipt CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    effective_at DATETIME(6) NOT NULL,
    purge_due_at DATETIME(6) NOT NULL,
    policy_ref VARCHAR(24) NOT NULL,
    component VARCHAR(32) NOT NULL DEFAULT 'session_copies_v1',
    state VARCHAR(32) NOT NULL DEFAULT 'pending_consumer',
    publication_state VARCHAR(24) NOT NULL DEFAULT 'unpublished',
    copy_inventory VARCHAR(24) NOT NULL DEFAULT 'unverified',
    retain_until DATETIME(6) NULL,
    UNIQUE KEY session_source (source_receipt)
) ENGINE=InnoDB;
