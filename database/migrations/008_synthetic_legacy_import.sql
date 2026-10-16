-- L02d: inert until a separately guarded synthetic fixture authorizes a destination.
-- No production marker, account genesis, pair certification or access grant.
CREATE TABLE IF NOT EXISTS synthetic_import_destination (
    singleton TINYINT UNSIGNED PRIMARY KEY,
    incarnation VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    nonce_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    server_uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS synthetic_import_pairs (
    pair_ref VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_low INT UNSIGNED NOT NULL,
    user_high INT UNSIGNED NOT NULL,
    match_id INT UNSIGNED NOT NULL,
    binding_version BIGINT UNSIGNED NOT NULL,
    snapshot_json MEDIUMTEXT NOT NULL,
    snapshot_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    guard_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    committed_receipts BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stored_facts BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stored_keys BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stored_equivalences BIGINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_synthetic_original_pair (user_low, user_high)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS synthetic_import_facts (
    fact_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    incarnation VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pair_ref VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    identity_json TEXT NOT NULL,
    payload_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    event_json TEXT NOT NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    INDEX idx_synthetic_fact_pair (pair_ref)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS synthetic_import_equivalences (
    pair_ref VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    left_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    right_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    proof_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    PRIMARY KEY (pair_ref, left_id, right_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS synthetic_import_receipts (
    receipt_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    incarnation VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pair_ref VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    bundle_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    intent_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    before_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    after_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    result_json TEXT NOT NULL,
    result_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    committed_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_synthetic_pair_bundle (incarnation, pair_ref, bundle_digest)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS synthetic_import_keys (
    incarnation VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pair_ref VARCHAR(96) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    intent_digest CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    receipt_ref CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    PRIMARY KEY (incarnation, pair_ref, key_hash)
) ENGINE=InnoDB;
