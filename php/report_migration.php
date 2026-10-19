<?php
declare(strict_types=1);

/** Called by the controlled CLI migration runner, never runtime schema DDL. */
function bushisa_migrate_report_revisions(PDO $pdo): void
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Report revision migration is CLI-only');
    }
    if (!operational_column_exists($pdo, 'moderation_cases', 'report_resolution_class')) {
        $pdo->exec('ALTER TABLE moderation_cases ADD COLUMN report_resolution_class VARCHAR(24) NULL');
    }
    if (!operational_column_exists($pdo, 'moderation_cases', 'intake_contract_version')) {
        $pdo->exec('ALTER TABLE moderation_cases ADD COLUMN intake_contract_version SMALLINT NULL');
    }
    if (!operational_column_exists($pdo, 'messages', 'report_segment_key')) {
        $pdo->exec('ALTER TABLE messages ADD COLUMN report_segment_key CHAR(64) NULL');
    }
    if (!operational_column_exists($pdo, 'moderation_evidence', 'report_source_version')) {
        $pdo->exec('ALTER TABLE moderation_evidence ADD COLUMN report_source_version CHAR(64) NULL');
    }
    foreach (['messages', 'confessions'] as $table) {
        if (!operational_column_exists($pdo, $table, 'report_revision')) {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN report_revision BIGINT UNSIGNED NOT NULL DEFAULT 1');
        }
        $body = $table === 'messages' ? operational_message_columns($pdo)['body'] : operational_confession_columns($pdo)['body'];
        $author = $table === 'messages' ? 'sender_id' : operational_confession_columns($pdo)['author'];
        $name = 'bushisa_' . $table . '_report_revision';
        $exists = $pdo->prepare('SELECT 1 FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = ?');
        $exists->execute([$name]);
        if (!$exists->fetchColumn()) {
            $pdo->exec('CREATE TRIGGER ' . $name . ' BEFORE UPDATE ON ' . $table . ' FOR EACH ROW
                SET NEW.report_revision = OLD.report_revision +
                    IF(NOT (NEW.' . $body . ' <=> OLD.' . $body . ') OR NOT (NEW.' . $author . ' <=> OLD.' . $author . '), 1, 0)');
        }
    }
}
