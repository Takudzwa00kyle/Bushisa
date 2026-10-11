-- Bushisa migration 003: independent restore replay rehearsal records.
-- D04/D05 remain pending; these tables only record synthetic-development and
-- operator-approved restore checks without approving production policy values.

CREATE TABLE IF NOT EXISTS restore_replay_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restore_label VARCHAR(120) NOT NULL,
    ledger_available TINYINT(1) NOT NULL DEFAULT 0,
    ledger_complete TINYINT(1) NOT NULL DEFAULT 0,
    traffic_reopen_allowed TINYINT(1) NOT NULL DEFAULT 0,
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    evidence_json JSON NULL,
    INDEX idx_restore_replay_checked (checked_at),
    INDEX idx_restore_replay_allowed (traffic_reopen_allowed, checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('003_restore_replay_safeguards');
