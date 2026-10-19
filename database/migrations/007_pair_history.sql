-- L02a. Additive ledger; no certification or fabricated legacy events.
-- Deployment must establish that every pair writer uses this contract before
-- recording a writer epoch. Merely applying a migration proves no such cutover.
CREATE TABLE IF NOT EXISTS pair_ledger_epoch (
    singleton TINYINT UNSIGNED PRIMARY KEY,
    activated_at DATETIME(6) NULL,
    writer_reference VARCHAR(120) NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO pair_ledger_epoch (singleton) VALUES (1);

CREATE TABLE IF NOT EXISTS pair_account_genesis (
    account_id INT UNSIGNED PRIMARY KEY,
    recorded_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pair_policy_history (
    user_low INT UNSIGNED NOT NULL,
    user_high INT UNSIGNED NOT NULL,
    match_id INT UNSIGNED NULL,
    history_state VARCHAR(32) NOT NULL,
    genesis_account_id INT UNSIGNED NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0,
    generation INT UNSIGNED NOT NULL DEFAULT 0,
    current_request_event_key CHAR(64) NULL,
    ending_count INT UNSIGNED NOT NULL DEFAULT 0,
    chat_suppressed TINYINT NOT NULL DEFAULT 0,
    last_ended_by INT UNSIGNED NULL,
    last_ended_at DATETIME(6) NULL,
    low_unmatched TINYINT NOT NULL DEFAULT 0,
    high_unmatched TINYINT NOT NULL DEFAULT 0,
    low_restore_at DATETIME(6) NULL,
    high_restore_at DATETIME(6) NULL,
    unmatched_at DATETIME(6) NULL,
    last_restored_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (user_low, user_high),
    UNIQUE KEY uq_pair_history_match (match_id)
) ENGINE=InnoDB;

-- One immutable creation identity per successful request. Later status/deadlines
-- are projections of that identity, never an invented prior request.
CREATE TABLE IF NOT EXISTS pair_request_events (
    event_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    match_id INT UNSIGNED NOT NULL,
    generation INT UNSIGNED NOT NULL,
    request_id INT UNSIGNED NOT NULL,
    requester_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    requested_at DATETIME(6) NOT NULL,
    request_expires_at DATETIME(6) NULL,
    status VARCHAR(16) NOT NULL,
    closed_at DATETIME(6) NULL,
    purge_due_at DATETIME(6) NULL,
    provenance VARCHAR(24) NOT NULL,
    INDEX idx_pair_event_match (match_id, requested_at),
    INDEX idx_pair_event_purge (purge_due_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pair_chat_segments (
    segment_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    match_id INT UNSIGNED NOT NULL,
    opened_at DATETIME(6) NOT NULL,
    grant_source VARCHAR(24) NOT NULL,
    request_event_key CHAR(64) NULL,
    ended_at DATETIME(6) NULL,
    ended_by INT UNSIGNED NULL,
    read_until DATETIME(6) NULL,
    INDEX idx_pair_segment_match (match_id, ended_at)
) ENGINE=InnoDB;

-- Bounded, content-free retry receipts. After expiry an old pair version still
-- cannot mutate the current state. L03 owns physical expiry/recovery workers.
CREATE TABLE IF NOT EXISTS pair_policy_operations (
    actor_id INT UNSIGNED NOT NULL,
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    intent_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    match_id INT UNSIGNED NULL,
    resulting_version BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    PRIMARY KEY (actor_id, key_hash),
    INDEX idx_pair_operation_expiry (expires_at)
) ENGINE=InnoDB;
