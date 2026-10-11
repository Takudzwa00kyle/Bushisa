-- Additive SMTP outbox migration; existing fake-mail rows are preserved.
-- Terminal code cleanup must be able to erase the verifier.
ALTER TABLE student_email_approval_codes MODIFY code_hash CHAR(64) NULL;
ALTER TABLE mail_outbox
    ADD COLUMN approval_code_id BIGINT UNSIGNED NULL,
    ADD COLUMN attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN next_attempt_at TIMESTAMP NULL,
    ADD INDEX idx_mail_outbox_dispatch (provider, status, next_attempt_at, expires_at);
