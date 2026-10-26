<?php

declare(strict_types=1);

require_once __DIR__ . '/operational.php';
require_once __DIR__ . '/policy_safeguards.php';

/*
CREATE TABLE IF NOT EXISTS blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT UNSIGNED NOT NULL,
    blocked_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_block_pair (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
*/

/**
 * Block another user and remove any mutual match.
 *
 * @param PDO $pdo
 * @param int $blocker_id
 * @param int $blocked_id
 * @return bool
 */
function block_user(PDO $pdo, int $blocker_id, int $blocked_id, ?int $sessionVersion = null, ?array $intent = null): bool
{
    require_once __DIR__ . '/report_intake.php';
    require_once __DIR__ . '/pair_history.php';
    return bushisa_report_transaction($pdo, function () use ($pdo, $blocker_id, $blocked_id, $sessionVersion, $intent): bool {
        if ($blocker_id === $blocked_id || !bushisa_report_actor_ok($pdo, $blocker_id, $sessionVersion)) {
            throw new BushisaReportError(403, 'This action is unavailable');
        }
        $pair=bushisa_pair_ensure($pdo,$blocker_id,$blocked_id);
        $work=function() use($pdo,$blocker_id,$blocked_id):array {
            $statement = $pdo->prepare('INSERT IGNORE INTO blocks (blocker_id, blocked_id) SELECT ?, id FROM users WHERE id = ?');
            $statement->execute([$blocker_id, $blocked_id]);
            bushisa_pair_close_relationship($pdo,$blocker_id,$blocked_id,'blocked',false);
            return [];
        };
        if ($intent!==null) { bushisa_pair_operation($pdo,$blocker_id,$pair,$intent,$work); }
        else { $work(); bushisa_pair_bump($pdo,$pair); }
        return true;
    });
}

/** Only the creator can remove their own cleared personal block. */
function unblock_user(PDO $pdo, int $blocker_id, int $blocked_id, ?int $sessionVersion = null, ?array $intent = null): bool
{
    require_once __DIR__ . '/report_intake.php';
    require_once __DIR__ . '/pair_history.php';
    return bushisa_report_transaction($pdo, function () use ($pdo, $blocker_id, $blocked_id, $sessionVersion, $intent): bool {
        if (!bushisa_report_actor_ok($pdo, $blocker_id, $sessionVersion)) {
            throw new BushisaReportError(403, 'This action is unavailable');
        }
        $pair=bushisa_pair_ensure($pdo,$blocker_id,$blocked_id);
        $work=function() use($pdo,$blocker_id,$blocked_id,$intent):array {
        $statement = $pdo->prepare('SELECT id FROM blocks WHERE blocker_id = ? AND blocked_id = ? FOR UPDATE');
        $statement->execute([$blocker_id, $blocked_id]);
        $block = $statement->fetchColumn();
        if ($block === false) {
            return []; // No other person's block is changed, no grant on replay.
        }
        if ($intent!==null && (int)($intent['block_id']??0)!==(int)$block) { throw new BushisaReportError(409,'Block state changed; refresh before choosing again'); }
        bushisa_report_guard_unblock($pdo, (int) $block);
        $statement = $pdo->prepare('DELETE FROM blocks WHERE id = ? AND blocker_id = ?');
        $statement->execute([(int) $block, $blocker_id]);
        bushisa_pair_unblock_grant($pdo,$blocker_id,$blocked_id);
        return [];
        };
        if ($intent!==null) { bushisa_pair_operation($pdo,$blocker_id,$pair,$intent,$work); }
        else { $work(); bushisa_pair_bump($pdo,$pair); }
        return true;
    });
}

/**
 * Determine whether either user has blocked the other.
 *
 * @param PDO $pdo
 * @param int $user_a
 * @param int $user_b
 * @return bool
 */
function is_blocked(PDO $pdo, int $user_a, int $user_b): bool
{
    ensure_operational_schema($pdo);
    $statement = $pdo->prepare(
        'SELECT 1 FROM blocks
         WHERE (blocker_id = :user_a AND blocked_id = :user_b)
            OR (blocker_id = :reverse_user_b AND blocked_id = :reverse_user_a)
         LIMIT 1'
    );
    $statement->execute([
        ':user_a' => $user_a,
        ':user_b' => $user_b,
        ':reverse_user_b' => $user_b,
        ':reverse_user_a' => $user_a,
    ]);

    return (bool) $statement->fetchColumn();
}

/**
 * Get all user IDs that the given user has blocked or that have blocked the user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return array<int, int>
 */
function get_blocked_user_ids(PDO $pdo, int $user_id): array
{
    ensure_operational_schema($pdo);
    $statement = $pdo->prepare(
        'SELECT blocker_id, blocked_id FROM blocks
         WHERE blocker_id = :blocker_user_id OR blocked_id = :blocked_user_id'
    );
    $statement->execute([
        ':blocker_user_id' => $user_id,
        ':blocked_user_id' => $user_id,
    ]);

    $blockedIds = [];
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if (!is_array($row)) {
            continue;
        }
        $blockerId = isset($row['blocker_id']) ? (int) $row['blocker_id'] : 0;
        $blockedId = isset($row['blocked_id']) ? (int) $row['blocked_id'] : 0;

        if ($blockerId > 0 && $blockerId !== $user_id) {
            $blockedIds[$blockerId] = $blockerId;
        }
        if ($blockedId > 0 && $blockedId !== $user_id) {
            $blockedIds[$blockedId] = $blockedId;
        }
    }
    return array_values($blockedIds);
}
