<?php

declare(strict_types=1);

require_once __DIR__ . '/support/disposable_database.php';

bushisa_require_disposable_test_database('integration_core_p0_flows');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/password_policy.php';
require_once __DIR__ . '/../php/logger.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/block_handler.php';
require_once __DIR__ . '/../php/anonymiser.php';
require_once __DIR__ . '/../php/matching/service.php';

function fail_core_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assert_core(bool $condition, string $message): void
{
    if (!$condition) {
        fail_core_test($message);
    }
}

function core_student_id(): string
{
    return 'n025' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(97, 122));
}

function create_active_fixture_user(PDO $pdo, string $gender, string $preferredGender): int
{
    $studentId = core_student_id();
    $email = $studentId . '@students.nust.ac.zw';
    $handle = 'core_' . bin2hex(random_bytes(4));

    $statement = $pdo->prepare(
        'INSERT INTO users (nust_student_id, email, password_hash, role, is_verified, is_suspended, is_active, account_status, session_version)
         VALUES (:student_id, :email, :password_hash, "user", 1, 0, 1, "active", 0)'
    );
    $statement->execute([
        ':student_id' => $studentId,
        ':email' => $email,
        ':password_hash' => hash_password('Passw0rd!'),
    ]);
    $userId = (int) $pdo->lastInsertId();

    $profile = $pdo->prepare(
        'INSERT INTO profiles (user_id, display_name, anon_handle, bio, gender, date_of_birth, faculty, year_of_study, profile_photo_path)
         VALUES (:user_id, :display_name, :anon_handle, "", :gender, "2000-01-01", "Applied Sciences", "Part 2", NULL)'
    );
    $profile->execute([
        ':user_id' => $userId,
        ':display_name' => 'Core ' . $userId,
        ':anon_handle' => $handle,
        ':gender' => $gender,
    ]);

    $preferences = $pdo->prepare(
        'INSERT INTO preferences (user_id, preferred_gender, age_range_min, age_range_max, preferred_faculty)
         VALUES (:user_id, :preferred_gender, 18, 35, "Any")'
    );
    $preferences->execute([
        ':user_id' => $userId,
        ':preferred_gender' => $preferredGender,
    ]);

    return $userId;
}

function create_match_from_mutual_likes(PDO $pdo, int $leftUserId, int $rightUserId): int
{
    $swipeColumns = operational_swipe_columns($pdo);
    $matchColumns = operational_match_columns($pdo);

    $insertSwipe = $pdo->prepare(
        sprintf(
            'INSERT INTO swipes (swiper_id, %1$s, %2$s, created_at)
             VALUES (:swiper_id, :target_id, "like", NOW())
             ON DUPLICATE KEY UPDATE %2$s = VALUES(%2$s)',
            $swipeColumns['target'],
            $swipeColumns['direction']
        )
    );
    $insertSwipe->execute([':swiper_id' => $leftUserId, ':target_id' => $rightUserId]);
    $insertSwipe->execute([':swiper_id' => $rightUserId, ':target_id' => $leftUserId]);

    $userA = min($leftUserId, $rightUserId);
    $userB = max($leftUserId, $rightUserId);
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

    return (int) $pdo->lastInsertId();
}

ensure_operational_schema($pdo);

$maleId = create_active_fixture_user($pdo, 'male', 'female');
$femaleId = create_active_fixture_user($pdo, 'female', 'male');

assert_core(matching_age_from_profile(['date_of_birth' => '2080-01-01']) === null, 'future DOB must not produce an adult age');

$candidates = matching_get_ranked_candidates($pdo, $maleId, ['limit' => 10]);
assert_core(($candidates['success'] ?? false) === true, 'candidate retrieval should succeed');
$results = $candidates['data']['results'] ?? [];
$candidateIds = array_map(static fn (array $row): int => (int) $row['user_id'], $results);
assert_core(in_array($femaleId, $candidateIds, true), 'reciprocal eligible candidate should be returned');
foreach ($results as $candidate) {
    assert_core(!array_key_exists('date_of_birth', $candidate), 'candidate DTO must not expose full DOB');
}

$matchId = create_match_from_mutual_likes($pdo, $maleId, $femaleId);
assert_core($matchId > 0, 'mutual likes should create a match');

$matchCount = $pdo->prepare(
    'SELECT COUNT(*)
     FROM matches
     WHERE (user_a_id = :a1 AND user_b_id = :b1)
        OR (user_a_id = :b2 AND user_b_id = :a2)'
);
$matchCount->execute([
    ':a1' => min($maleId, $femaleId),
    ':b1' => max($maleId, $femaleId),
    ':b2' => max($maleId, $femaleId),
    ':a2' => min($maleId, $femaleId),
]);
assert_core((int) $matchCount->fetchColumn() === 1, 'mutual likes should have exactly one match row');

$request = $pdo->prepare(
    'INSERT INTO message_requests (match_id, requester_id, recipient_id, status, requested_at, responded_at)
     VALUES (:match_id, :requester_id, :recipient_id, "approved", NOW(), NOW())'
);
$request->execute([
    ':match_id' => $matchId,
    ':requester_id' => $maleId,
    ':recipient_id' => $femaleId,
]);
assert_core(operational_has_message_approval($pdo, $matchId), 'approved message request should authorize chat');

$message = $pdo->prepare('INSERT INTO messages (match_id, sender_id, body, sent_at, is_read) VALUES (:match_id, :sender_id, "hello", NOW(), 0)');
$message->execute([':match_id' => $matchId, ':sender_id' => $maleId]);
$read = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE match_id = :match_id AND sender_id = :sender_id');
$read->execute([':match_id' => $matchId, ':sender_id' => $maleId]);
assert_core($read->rowCount() >= 1, 'message read update should affect sent message');

assert_core(block_user($pdo, $maleId, $femaleId), 'block operation should succeed');
assert_core(operational_is_blocked($pdo, $maleId, $femaleId), 'block should be visible to shared guard');
$deletedMatch = operational_get_match($pdo, $matchId, $maleId);
assert_core($deletedMatch === null, 'block should remove existing match from ordinary access');
$rawMatch = $pdo->prepare('SELECT match_status FROM matches WHERE id = :match_id LIMIT 1');
$rawMatch->execute([':match_id' => $matchId]);
assert_core((string) $rawMatch->fetchColumn() === 'blocked', 'block should close match row instead of deleting it');

$deleteUserId = create_active_fixture_user($pdo, 'male', 'female');
assert_core(anonymise_user($pdo, $deleteUserId, $deleteUserId), 'account anonymisation should complete');
$deletedUser = find_user_by_id($pdo, $deleteUserId);
assert_core(is_array($deletedUser), 'soft-deleted user row should remain for integrity');
assert_core((int) ($deletedUser['is_active'] ?? 1) === 0, 'deleted user should be inactive');
assert_core((string) ($deletedUser['account_status'] ?? '') === 'deleted', 'deleted user should carry deleted status');

fwrite(STDOUT, "integration_core_p0_flows PASS\n");
