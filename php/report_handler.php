<?php

declare(strict_types=1);

require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/sanitize.php';

/**
 * Submit a report against another user.
 *
 * @param PDO $pdo
 * @param int $reporter_id
 * @param int $reported_user_id
 * @param string $reason
 * @param string|null $evidence_path
 * @return array{success: bool, error: string|null}
 */
function submit_report(PDO $pdo, int $reporter_id, int $reported_user_id, string $reason, ?string $evidence_path = null): array
{
    // Legacy ID-only intake cannot prove authorized exposure or deduplicate
    // intent. No callers may bypass the shared text-report contract.
    return ['success' => false, 'error' => 'Use authorized report intake with a report context and submission key.'];
}

/**
 * Get reports filed by a given user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return array<int, array<string, mixed>>
 */
function get_reports_by_user(PDO $pdo, int $user_id): array
{
    $statement = $pdo->prepare(
        'SELECT id, reporter_id, reported_user_id, reason, evidence_path, status, created_at
         FROM reports
         WHERE reporter_id = :user_id
         ORDER BY created_at DESC, id DESC'
    );
    $statement->execute([':user_id' => $user_id]);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Get reports filed against a given user.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @return array<int, array<string, mixed>>
 */
function get_reports_against_user(PDO $pdo, int $user_id): array
{
    $statement = $pdo->prepare(
        'SELECT id, reporter_id, reported_user_id, reason, evidence_path, status, created_at
         FROM reports
         WHERE reported_user_id = :user_id
         ORDER BY created_at DESC, id DESC'
    );
    $statement->execute([':user_id' => $user_id]);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Update a report status.
 *
 * @param PDO $pdo
 * @param int $report_id
 * @param string $new_status
 * @return bool
 */
function update_report_status(PDO $pdo, int $report_id, string $new_status): bool
{
    $allowedStatuses = ['reviewed', 'dismissed', 'action_taken'];
    if (!in_array($new_status, $allowedStatuses, true)) {
        return false;
    }

    $statement = $pdo->prepare('UPDATE reports SET status = :status WHERE id = :id');
    return $statement->execute([
        ':status' => $new_status,
        ':id' => $report_id,
    ]);
}
