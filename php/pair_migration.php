<?php
declare(strict_types=1);

/** Controlled forward migration only. Snapshot known state, never attest it. */
function bushisa_migrate_pair_history(PDO $pdo): void
{
    if (PHP_SAPI !== 'cli') { throw new RuntimeException('CLI migration only'); }
    // Widen precision without changing existing instants. The canonical server
    // receipt, seven-day expiry and twelve-hour cooldown must agree exactly.
    foreach (['requested_at','responded_at','revoked_at','closed_at','expires_at','purge_due_at'] as $column) {
        $precision=$pdo->prepare('SELECT datetime_precision FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name="message_requests" AND column_name=?');
        $precision->execute([$column]);
        if ((int)$precision->fetchColumn()!==6) {
            $pdo->exec('ALTER TABLE message_requests MODIFY COLUMN '.$column.' TIMESTAMP(6) '.($column==='requested_at'?'NOT NULL DEFAULT CURRENT_TIMESTAMP(6)':'NULL DEFAULT NULL'));
        }
    }
    $precision=$pdo->query('SELECT datetime_precision FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name="messages" AND column_name="evidence_purge_due_at"')->fetchColumn();
    if ((int)$precision!==6) { $pdo->exec('ALTER TABLE messages MODIFY COLUMN evidence_purge_due_at TIMESTAMP(6) NULL DEFAULT NULL'); }
    $mc = operational_match_columns($pdo);
    $rows = $pdo->query(sprintf('SELECT id, %s AS a, %s AS b FROM matches ORDER BY id', $mc['user_a'], $mc['user_b']));
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO pair_policy_history
            (user_low,user_high,match_id,history_state,generation,created_at)
            VALUES (?, ?, ?, "legacy_incomplete", 1, UTC_TIMESTAMP(6))');
        $stmt->execute([min((int)$row['a'],(int)$row['b']), max((int)$row['a'],(int)$row['b']), $row['id']]);
    }
    // A replayed partially applied migration must never call an old account new.
    $exists = $pdo->query("SELECT 1 FROM information_schema.triggers WHERE trigger_schema=DATABASE() AND trigger_name='bushisa_pair_account_genesis'")->fetchColumn();
    if (!$exists) {
        $pdo->exec('CREATE TRIGGER bushisa_pair_account_genesis AFTER INSERT ON users FOR EACH ROW
            INSERT INTO pair_account_genesis (account_id,recorded_at)
            SELECT NEW.id,UTC_TIMESTAMP(6) FROM pair_ledger_epoch WHERE singleton=1 AND activated_at IS NOT NULL');
    }
    if (!operational_column_exists($pdo,'pair_request_counts','retry_not_before')) {
        $pdo->exec('ALTER TABLE pair_request_counts ADD COLUMN retry_not_before DATETIME(6) NULL');
    }
}
