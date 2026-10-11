<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../php/operational.php';

if ($argc < 6) {
    fwrite(STDERR, "Usage: php db_race_worker.php <mode> <left_user_id> <right_user_id> <barrier_dir> <worker_name>\n");
    exit(2);
}

$mode = (string) $argv[1];
$leftUserId = (int) $argv[2];
$rightUserId = (int) $argv[3];
$barrierDir = (string) $argv[4];
$workerName = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $argv[5]) ?: 'worker';

if (!is_dir($barrierDir)) {
    fwrite(STDERR, "Barrier directory does not exist.\n");
    exit(2);
}

ensure_operational_schema($pdo);
$pdo->exec('SET innodb_lock_wait_timeout = 10');

function db_race_wait_for_peer(string $barrierDir, string $workerName): void
{
    file_put_contents($barrierDir . DIRECTORY_SEPARATOR . $workerName . '.ready', (string) getmypid());
    $deadline = microtime(true) + 10.0;
    while (microtime(true) < $deadline) {
        $ready = glob($barrierDir . DIRECTORY_SEPARATOR . '*.ready') ?: [];
        if (count($ready) >= 2) {
            return;
        }

        usleep(50_000);
    }

    throw new RuntimeException('Timed out waiting for race peer.');
}

try {
    $pdo->beginTransaction();

    if ($mode === 'match') {
        $columns = operational_match_columns($pdo);
        $userA = min($leftUserId, $rightUserId);
        $userB = max($leftUserId, $rightUserId);

        $existing = $pdo->prepare(
            sprintf(
                'SELECT id FROM matches
                 WHERE (%1$s = :user_a AND %2$s = :user_b)
                    OR (%1$s = :reverse_user_b AND %2$s = :reverse_user_a)
                 LIMIT 1',
                $columns['user_a'],
                $columns['user_b']
            )
        );
        $existing->execute([
            ':user_a' => $userA,
            ':user_b' => $userB,
            ':reverse_user_b' => $userB,
            ':reverse_user_a' => $userA,
        ]);

        db_race_wait_for_peer($barrierDir, $workerName);

        $insert = $pdo->prepare(
            sprintf(
                'INSERT INTO matches (%1$s, %2$s, %3$s)
                 VALUES (:user_a, :user_b, NOW())
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
                $columns['user_a'],
                $columns['user_b'],
                $columns['created_at']
            )
        );
        $insert->execute([
            ':user_a' => $userA,
            ':user_b' => $userB,
        ]);
        $matchId = (int) $pdo->lastInsertId();
        $pdo->commit();
        echo json_encode(['ok' => true, 'mode' => $mode, 'worker' => $workerName, 'match_id' => $matchId], JSON_UNESCAPED_SLASHES) . "\n";
        exit(0);
    }

    if ($mode === 'duplicate_swipe') {
        $columns = operational_swipe_columns($pdo);

        $existing = $pdo->prepare(
            sprintf(
                'SELECT id FROM swipes WHERE swiper_id = :swiper_id AND %1$s = :target_id LIMIT 1',
                $columns['target']
            )
        );
        $existing->execute([
            ':swiper_id' => $leftUserId,
            ':target_id' => $rightUserId,
        ]);

        db_race_wait_for_peer($barrierDir, $workerName);

        $insert = $pdo->prepare(
            sprintf(
                'INSERT IGNORE INTO swipes (swiper_id, %1$s, %2$s, created_at)
                 VALUES (:swiper_id, :target_id, "like", NOW())',
                $columns['target'],
                $columns['direction']
            )
        );
        $insert->execute([
            ':swiper_id' => $leftUserId,
            ':target_id' => $rightUserId,
        ]);
        $inserted = $insert->rowCount() === 1;
        if ($inserted) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }

        echo json_encode(['ok' => true, 'mode' => $mode, 'worker' => $workerName, 'inserted' => $inserted], JSON_UNESCAPED_SLASHES) . "\n";
        exit(0);
    }

    throw new RuntimeException('Unsupported race mode: ' . $mode);
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, $throwable->getMessage() . "\n");
    exit(1);
}

