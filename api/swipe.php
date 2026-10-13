<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/rate_limit.php';
require_once __DIR__ . '/../php/logger.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/matching/service.php';
require_once __DIR__ . '/../php/http_test_barrier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();

$currentUserId = (int) $_SESSION['user_id'];
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody !== false ? $rawBody : '', true);

if (!is_array($payload)) {
    json_response(['error' => 'Invalid request body'], 400);
}

$submittedToken = '';
if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
} elseif (isset($payload['csrf_token']) && is_string($payload['csrf_token'])) {
    $submittedToken = $payload['csrf_token'];
}

if ($submittedToken === '' || !validate_csrf_token($submittedToken)) {
    json_response(['error' => 'Invalid CSRF token'], 403);
}

ensure_operational_schema($pdo);

$targetId = clean_int($payload['target_id'] ?? ($payload['swiped_id'] ?? null));
$direction = isset($payload['direction']) && is_string($payload['direction']) ? clean_enum($payload['direction'], ['like', 'pass']) : null;

if ($targetId === null || $targetId <= 0 || $direction === null) {
    json_response(['error' => 'Invalid swipe payload'], 400);
}

$rateLimitKey = (string) $currentUserId;
if (!check_rate_limit($pdo, 'swipe', $rateLimitKey, 100, 3600)) {
    json_response(['error' => 'Rate limit exceeded'], 429);
}

if ($targetId === $currentUserId) {
    json_response(['error' => 'Cannot swipe yourself'], 400);
}

if (operational_is_blocked($pdo, $currentUserId, $targetId)) {
    json_response(['error' => 'This profile is unavailable'], 403);
}

$suspendedSelect = operational_column_exists($pdo, 'users', 'is_suspended') ? 'COALESCE(is_suspended, 0) AS is_suspended' : '0 AS is_suspended';
$activeSelect = operational_column_exists($pdo, 'users', 'is_active') ? 'COALESCE(is_active, 1) AS is_active' : '1 AS is_active';
$verifiedSelect = operational_column_exists($pdo, 'users', 'is_verified') ? 'COALESCE(is_verified, 0) AS is_verified' : '1 AS is_verified';
$targetStatement = $pdo->prepare(
    sprintf(
        'SELECT id, %1$s, %2$s, %3$s FROM users WHERE id = :id LIMIT 1',
        $suspendedSelect,
        $activeSelect,
        $verifiedSelect
    )
);
$targetStatement->execute([':id' => $targetId]);
$targetUser = $targetStatement->fetch(PDO::FETCH_ASSOC);

if ($targetUser === false
    || ((int) ($targetUser['is_suspended'] ?? 0)) === 1
    || ((int) ($targetUser['is_active'] ?? 1)) !== 1
    || ((int) ($targetUser['is_verified'] ?? 1)) !== 1
) {
    json_response(['error' => 'Target user is unavailable'], 404);
}

$viewerProfile = get_profile($pdo, $currentUserId);
$viewerPreferences = get_preferences($pdo, $currentUserId);
$targetProfile = get_profile($pdo, $targetId);
$targetPreferences = get_preferences($pdo, $targetId);
if ($viewerProfile === null || $viewerPreferences === null || $targetProfile === null || $targetPreferences === null) {
    json_response(['error' => 'Both users need complete profiles and preferences before matching'], 403);
}

$answersByUser = matching_load_answers($pdo, [$currentUserId, $targetId]);
if (!matching_pair_is_eligible(
    $viewerProfile,
    $viewerPreferences,
    $targetProfile,
    $targetPreferences,
    $answersByUser[$currentUserId] ?? [],
    $answersByUser[$targetId] ?? []
)) {
    json_response(['error' => 'This profile is no longer eligible for your matching preferences'], 403);
}

$swipeColumns = operational_swipe_columns($pdo);
$matchColumns = operational_match_columns($pdo);

$existingSwipe = $pdo->prepare(
    sprintf(
        'SELECT id FROM swipes WHERE swiper_id = :swiper_id AND %1$s = :target_id LIMIT 1',
        $swipeColumns['target']
    )
);
$existingSwipe->execute([
    ':swiper_id' => $currentUserId,
    ':target_id' => $targetId,
]);

if ($existingSwipe->fetchColumn() !== false) {
    json_response(['error' => 'You have already swiped on this user'], 409);
}

$response = ['match' => false, 'matched' => false];
$matchLockName = null;

function bushisa_swipe_match_lock_name(int $currentUserId, int $targetId): string
{
    $userA = min($currentUserId, $targetId);
    $userB = max($currentUserId, $targetId);

    return 'bushisa_match:' . $userA . ':' . $userB;
}

function bushisa_acquire_swipe_match_lock(PDO $pdo, string $lockName): void
{
    $statement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 5)');
    $statement->execute([':lock_name' => $lockName]);
    if ((int) $statement->fetchColumn() !== 1) {
        throw new RuntimeException('Unable to acquire reciprocal match lock.');
    }
}

function bushisa_release_swipe_match_lock(PDO $pdo, ?string $lockName): void
{
    if ($lockName === null) {
        return;
    }

    $statement = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
    $statement->execute([':lock_name' => $lockName]);
}

try {
    $pdo->beginTransaction();
    bushisa_http_test_barrier('swipe_after_transaction_begin');

    if ($direction === 'like') {
        $matchLockName = bushisa_swipe_match_lock_name($currentUserId, $targetId);
        bushisa_acquire_swipe_match_lock($pdo, $matchLockName);
    }

    record_attempt($pdo, 'swipe', $rateLimitKey);

    $insertSwipe = $pdo->prepare(
        sprintf(
            'INSERT IGNORE INTO swipes (swiper_id, %1$s, %2$s, created_at)
             VALUES (:swiper_id, :target_id, :direction, NOW())',
            $swipeColumns['target'],
            $swipeColumns['direction']
        )
    );
    $insertSwipe->execute([
        ':swiper_id' => $currentUserId,
        ':target_id' => $targetId,
        ':direction' => $direction,
    ]);
    if ($insertSwipe->rowCount() !== 1) {
        $pdo->rollBack();
        json_response(['error' => 'You have already swiped on this user'], 409);
    }

    if ($direction === 'like') {
        $reverseSwipe = $pdo->prepare(
            sprintf(
            'SELECT id FROM swipes
             WHERE swiper_id = :target_id
               AND %1$s = :current_user_id
               AND %2$s = "like"
             LIMIT 1',
            $swipeColumns['target'],
            $swipeColumns['direction']
            )
        );
        $reverseSwipe->execute([
            ':target_id' => $targetId,
            ':current_user_id' => $currentUserId,
        ]);

        if ($reverseSwipe->fetchColumn() !== false) {
            $userA = min($currentUserId, $targetId);
            $userB = max($currentUserId, $targetId);

            $existingMatch = $pdo->prepare(
                sprintf(
                    'SELECT id FROM matches
                     WHERE (%1$s = :forward_user_a AND %2$s = :forward_user_b)
                        OR (%1$s = :reverse_user_b AND %2$s = :reverse_user_a)
                     LIMIT 1',
                    $matchColumns['user_a'],
                    $matchColumns['user_b']
                )
            );
            $existingMatch->execute([
                ':forward_user_a' => $userA,
                ':forward_user_b' => $userB,
                ':reverse_user_b' => $userB,
                ':reverse_user_a' => $userA,
            ]);
            $matchId = $existingMatch->fetchColumn();

            if ($matchId === false) {
                $insertMatch = $pdo->prepare(
                    sprintf(
                        'INSERT INTO matches (%1$s, %2$s, %3$s)
                         VALUES (:user_a_id, :user_b_id, NOW())
                         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
                        $matchColumns['user_a'],
                        $matchColumns['user_b'],
                        $matchColumns['created_at']
                    )
                );
                $insertMatch->execute([
                    ':user_a_id' => $userA,
                    ':user_b_id' => $userB,
                ]);
                $matchId = (int) $pdo->lastInsertId();
            } else {
                $matchId = (int) $matchId;
            }

            operational_write_notification(
                $pdo,
                $targetId,
                'match',
                'New match',
                'You have a new established match on Bushisa.',
                'matches.php',
                'match:' . $matchId . ':' . $targetId
            );
            operational_write_notification(
                $pdo,
                $currentUserId,
                'match',
                'New match',
                'You have a new established match on Bushisa.',
                'matches.php',
                'match:' . $matchId . ':' . $currentUserId
            );

            $response = [
                'match' => true,
                'matched' => true,
                'match_id' => $matchId,
            ];
        }
    }

    $pdo->commit();
    bushisa_release_swipe_match_lock($pdo, $matchLockName);
    $matchLockName = null;
    log_action($pdo, $currentUserId, 'swipe_' . $direction, $_SERVER['REMOTE_ADDR'] ?? null);
    log_to_file('User ' . $currentUserId . ' swiped ' . $direction . ' on ' . $targetId, 'INFO');
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    bushisa_release_swipe_match_lock($pdo, $matchLockName);

    log_to_file('Swipe failed for user ' . $currentUserId . ': ' . $throwable->getMessage(), 'ERROR');
    json_response(['error' => 'Unable to process swipe'], 500);
}

json_response($response);
