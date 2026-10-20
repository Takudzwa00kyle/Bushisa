-- L01b: additive text-report intake. No ordinary rows are deleted or rewritten.
-- Open case data expires from final resolution under D05, not creation +24 months.
CREATE TABLE IF NOT EXISTS report_incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    anchor_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    receipt_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    case_id BIGINT UNSIGNED NOT NULL,
    key_id VARCHAR(40) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NULL,
    UNIQUE KEY uq_report_incident (reporter_id, anchor_hash),
    UNIQUE KEY uq_report_receipt (receipt_id),
    INDEX idx_report_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    incident_id BIGINT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    reported_actor_id INT UNSIGNED NULL,
    information_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    reason_code VARCHAR(60) NOT NULL,
    explanation TEXT NULL,
    is_new_report TINYINT(1) NOT NULL,
    received_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NULL,
    purged_at DATETIME(6) NULL,
    UNIQUE KEY uq_report_submission_ref (submission_ref),
    UNIQUE KEY uq_report_information (incident_id, information_hash),
    INDEX idx_report_submission_case (incident_id, received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    key_id VARCHAR(40) NOT NULL,
    action_name VARCHAR(40) NOT NULL,
    contract_version SMALLINT NOT NULL,
    intent_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    submission_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NULL,
    UNIQUE KEY uq_report_operation (reporter_id, key_hash),
    INDEX idx_report_operation_submission (submission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_probation_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT UNSIGNED NOT NULL,
    trigger_submission_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_report_probation_trigger (trigger_submission_id),
    INDEX idx_report_actor_period (actor_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_block_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    block_id INT UNSIGNED NOT NULL,
    case_id BIGINT UNSIGNED NOT NULL,
    submission_id BIGINT UNSIGNED NOT NULL,
    attached_at DATETIME(6) NOT NULL,
    cleared_by INT UNSIGNED NULL,
    cleared_at DATETIME(6) NULL,
    UNIQUE KEY uq_report_block_submission (block_id, submission_id),
    INDEX idx_report_block_uncleared (block_id, cleared_at),
    INDEX idx_report_block_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_action_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT UNSIGNED NOT NULL,
    case_id BIGINT UNSIGNED NULL,
    action_name VARCHAR(40) NOT NULL,
    outcome_code VARCHAR(40) NOT NULL,
    occurred_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    INDEX idx_report_audit_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content-free links never join evidence permissions or renew either case.
CREATE TABLE IF NOT EXISTS report_case_links (
    from_case_id BIGINT UNSIGNED NOT NULL,
    to_case_id BIGINT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (from_case_id, to_case_id, reporter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reserved scoped interface for L03's separately reviewed hold workflow.
-- L01b exposes no hold-grant route. No routine job renews these instants.
CREATE TABLE IF NOT EXISTS report_evidence_holds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id BIGINT UNSIGNED NOT NULL,
    evidence_id BIGINT UNSIGNED NOT NULL,
    authorized_record_ref VARCHAR(80) NOT NULL,
    starts_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    released_at DATETIME(6) NULL,
    INDEX idx_report_hold_item (evidence_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Successful request events, independent of the mutable current-request row.
CREATE TABLE IF NOT EXISTS message_request_sends (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    request_id INT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL,
    INDEX idx_request_sends_sender (requester_id, created_at),
    INDEX idx_request_sends_recipient (recipient_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pair_request_counts (
    requester_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    request_count INT UNSIGNED NOT NULL,
    history_verified_at DATETIME(6) NULL,
    PRIMARY KEY (requester_id, recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pair_decline_restrictions (
    requester_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    source_match_id INT UNSIGNED NOT NULL,
    declined_at DATETIME(6) NULL,
    cleared_at DATETIME(6) NULL,
    PRIMARY KEY (requester_id, recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preserve the currently known sends on upgrades. Missing older history is an
-- explicit L02 release gate; this is not a claim to reconstruct purged history.
INSERT INTO message_request_sends (requester_id, recipient_id, request_id, created_at)
SELECT requester_id, recipient_id, id, requested_at FROM message_requests mr
WHERE NOT EXISTS (SELECT 1 FROM message_request_sends s WHERE s.request_id = mr.id);

INSERT INTO pair_request_counts (requester_id, recipient_id, request_count)
SELECT requester_id, recipient_id, COUNT(*) FROM message_request_sends GROUP BY requester_id, recipient_id
ON DUPLICATE KEY UPDATE request_count = GREATEST(request_count, VALUES(request_count));

-- No synthetic decline timestamp when legacy attribution time is unavailable.
INSERT IGNORE INTO pair_decline_restrictions (requester_id, recipient_id, source_match_id, declined_at)
SELECT requester_id, recipient_id, match_id, responded_at FROM message_requests WHERE status = 'declined';
