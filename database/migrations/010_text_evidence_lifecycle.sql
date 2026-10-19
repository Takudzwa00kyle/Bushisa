-- L03a: additive, no historic timestamps, authority grants, backfill or activation.
CREATE TABLE IF NOT EXISTS evidence_case_clocks (
    case_id BIGINT UNSIGNED PRIMARY KEY,
    domain VARCHAR(16) NOT NULL,
    state VARCHAR(32) NOT NULL DEFAULT 'missing_notice',
    notice_at DATETIME(6) NULL,
    notice_event CHAR(64) NULL,
    decision_reviewer_id INT UNSIGNED NULL,
    resolution_class VARCHAR(24) NULL,
    appeal_until DATETIME(6) NULL,
    appeal_received_at DATETIME(6) NULL,
    appeal_reviewer_id INT UNSIGNED NULL,
    appeal_disposed_at DATETIME(6) NULL,
    final_at DATETIME(6) NULL,
    content_expires_at DATETIME(6) NULL,
    decision_expires_at DATETIME(6) NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    unresolved_code VARCHAR(48) NULL,
    observed_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_lifecycle_items (
    item_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    item_kind VARCHAR(16) NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    source_hash CHAR(64) NULL,
    source_revision BIGINT UNSIGNED NOT NULL DEFAULT 1,
    ordinary_expires_at DATETIME(6) NULL,
    purge_due_at DATETIME(6) NULL,
    state VARCHAR(32) NOT NULL DEFAULT 'awaiting_anchor',
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    observed_at DATETIME(6) NOT NULL,
    purged_at DATETIME(6) NULL,
    UNIQUE KEY uq_lifecycle_source (item_kind,source_id),
    INDEX idx_lifecycle_due (state,purge_due_at),
    INDEX idx_lifecycle_case (case_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_lifecycle_holds (
    hold_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    item_key CHAR(64) NOT NULL,
    source_revision BIGINT UNSIGNED NOT NULL,
    kind VARCHAR(16) NOT NULL,
    state VARCHAR(24) NOT NULL,
    requested_hours INT UNSIGNED NOT NULL,
    reason_code VARCHAR(40) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    lead_id INT UNSIGNED NULL,
    lead_proof_hash CHAR(64) NULL,
    privacy_id INT UNSIGNED NULL,
    granted_at DATETIME(6) NULL,
    grant_sequence_id BIGINT UNSIGNED NULL,
    expires_at DATETIME(6) NULL,
    review_due_at DATETIME(6) NULL,
    reviewed_at DATETIME(6) NULL,
    release_requested_by INT UNSIGNED NULL,
    release_proof_hash CHAR(64) NULL,
    released_at DATETIME(6) NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL,
    INDEX idx_lifecycle_hold_item (item_key,state,expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_lifecycle_operations (
    actor_id INT UNSIGNED NOT NULL,
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    intent_hash CHAR(64) NOT NULL,
    case_id BIGINT UNSIGNED NOT NULL,
    result_json TEXT NOT NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (actor_id,key_hash)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_lifecycle_events (
    event_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NULL,
    item_key CHAR(64) NULL,
    action_name VARCHAR(40) NOT NULL,
    outcome_code VARCHAR(48) NOT NULL,
    occurred_at DATETIME(6) NOT NULL,
    audit_expires_at DATETIME(6) NOT NULL,
    INDEX idx_lifecycle_audit_expiry (audit_expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_purge_obligations (
    obligation_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    item_key CHAR(64) NOT NULL,
    case_id BIGINT UNSIGNED NOT NULL,
    original_due_at DATETIME(6) NULL,
    purge_due_at DATETIME(6) NULL,
    state VARCHAR(32) NOT NULL,
    reason_code VARCHAR(48) NOT NULL,
    completed_at DATETIME(6) NULL,
    source_revision BIGINT UNSIGNED NOT NULL,
    INDEX idx_evidence_obligation_due (state,purge_due_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_lifecycle_alerts (
    alert_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    item_key CHAR(64) NULL,
    case_id BIGINT UNSIGNED NOT NULL,
    reason_code VARCHAR(48) NOT NULL,
    deadline_at DATETIME(6) NULL,
    observed_at DATETIME(6) NOT NULL,
    resolved_at DATETIME(6) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_recovery_outbox (
    sequence_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    item_key CHAR(64) NOT NULL,
    source_revision BIGINT UNSIGNED NOT NULL,
    action_name VARCHAR(32) NOT NULL,
    effective_at DATETIME(6) NOT NULL,
    ordinary_expires_at DATETIME(6) NULL,
    preserve_until DATETIME(6) NULL,
    published_at DATETIME(6) NULL,
    retain_until DATETIME(6) NULL,
    copy_inventory_state VARCHAR(24) NOT NULL DEFAULT 'unverified'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_recovery_state (
    singleton TINYINT UNSIGNED PRIMARY KEY,
    journal_namespace CHAR(64) NULL,
    required_watermark BIGINT UNSIGNED NOT NULL DEFAULT 0,
    applied_watermark BIGINT UNSIGNED NOT NULL DEFAULT 0,
    head_digest CHAR(64) NULL,
    ready TINYINT(1) NOT NULL DEFAULT 0,
    verified_at DATETIME(6) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_recovery_overrides (
    item_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    source_revision BIGINT UNSIGNED NOT NULL,
    sequence_id BIGINT UNSIGNED NOT NULL,
    action_name VARCHAR(32) NOT NULL,
    preserve_until DATETIME(6) NULL,
    effective_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evidence_cleanup_cursor (
    singleton TINYINT UNSIGNED PRIMARY KEY,
    last_item_key CHAR(64) NULL
) ENGINE=InnoDB;
