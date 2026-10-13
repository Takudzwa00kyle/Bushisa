<?php

declare(strict_types=1);

require_once __DIR__ . '/support/disposable_database.php';

bushisa_require_disposable_test_database('policy_safeguards_p0_flows');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/policy_safeguards.php';
require_once __DIR__ . '/../php/block_handler.php';
require_once __DIR__ . '/../php/anonymiser.php';
require_once __DIR__ . '/../php/password_policy.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/signup_verification.php';

function fail_policy_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assert_policy(bool $condition, string $message): void
{
    if (!$condition) {
        fail_policy_test($message);
    }
}

function policy_student_id(): string
{
    return 'n025' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(97, 122));
}

function policy_create_user(PDO $pdo, string $gender, string $preferredGender, string $role = 'user'): int
{
    $studentId = policy_student_id();
    $email = $studentId . '@students.nust.ac.zw';
    $handle = 'policy_' . bin2hex(random_bytes(4));

    $user = $pdo->prepare(
        'INSERT INTO users (nust_student_id, email, password_hash, role, is_verified, is_suspended, is_active, account_status, session_version)
         VALUES (:student_id, :email, :password_hash, :role, 1, 0, 1, "active", 0)'
    );
    $user->execute([
        ':student_id' => $studentId,
        ':email' => $email,
        ':password_hash' => hash_password('Passw0rd!'),
        ':role' => $role,
    ]);
    $userId = (int) $pdo->lastInsertId();

    $profile = $pdo->prepare(
        'INSERT INTO profiles (user_id, display_name, anon_handle, bio, gender, date_of_birth, faculty, year_of_study, profile_photo_path)
         VALUES (:user_id, :display_name, :anon_handle, "", :gender, "2000-01-01", "Applied Sciences", "Part 2", NULL)'
    );
    $profile->execute([
        ':user_id' => $userId,
        ':display_name' => 'Policy ' . $userId,
        ':anon_handle' => $handle,
        ':gender' => $gender,
    ]);

    if ($role === 'user') {
        $preferences = $pdo->prepare(
            'INSERT INTO preferences (user_id, preferred_gender, age_range_min, age_range_max, preferred_faculty)
             VALUES (:user_id, :preferred_gender, 18, 35, "Any")'
        );
        $preferences->execute([
            ':user_id' => $userId,
            ':preferred_gender' => $preferredGender,
        ]);
    }

    return $userId;
}

function policy_create_match(PDO $pdo, int $leftUserId, int $rightUserId): int
{
    $matchColumns = operational_match_columns($pdo);
    $insert = $pdo->prepare(
        sprintf(
            'INSERT INTO matches (%1$s, %2$s, %3$s)
             VALUES (:user_a, :user_b, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), match_status = "active", closed_at = NULL, closed_reason = NULL',
            $matchColumns['user_a'],
            $matchColumns['user_b'],
            $matchColumns['created_at']
        )
    );
    $insert->execute([
        ':user_a' => min($leftUserId, $rightUserId),
        ':user_b' => max($leftUserId, $rightUserId),
    ]);

    return (int) $pdo->lastInsertId();
}

ensure_operational_schema($pdo);

$leftUserId = policy_create_user($pdo, 'male', 'female');
$rightUserId = policy_create_user($pdo, 'female', 'male');
$matchId = policy_create_match($pdo, $leftUserId, $rightUserId);

$request = $pdo->prepare(
    'INSERT INTO message_requests (match_id, requester_id, recipient_id, status, requested_at, responded_at)
     VALUES (:match_id, :requester_id, :recipient_id, "approved", UTC_TIMESTAMP(), UTC_TIMESTAMP())'
);
$request->execute([
    ':match_id' => $matchId,
    ':requester_id' => $leftUserId,
    ':recipient_id' => $rightUserId,
]);

$message = $pdo->prepare('INSERT INTO messages (match_id, sender_id, body, sent_at, is_read) VALUES (:match_id, :sender_id, "case evidence body", UTC_TIMESTAMP(), 0)');
$message->execute([':match_id' => $matchId, ':sender_id' => $leftUserId]);
$messageId = (int) $pdo->lastInsertId();

$caseId = bushisa_create_moderation_case($pdo, $rightUserId, 'message', $messageId, $leftUserId, 'harassment');
$normalViewerId = policy_create_user($pdo, 'male', 'female');
$moderatorId = policy_create_user($pdo, 'female', 'male', 'moderator');
$adminId = policy_create_user($pdo, 'female', 'male', 'admin');

assert_policy(bushisa_case_evidence_for_viewer($pdo, $caseId, $normalViewerId, 'test') === [], 'ordinary users must not read case evidence');
assert_policy(bushisa_case_evidence_for_viewer($pdo, $caseId, $moderatorId, 'unassigned') === [], 'unassigned moderators must not read case evidence');

$assignment = $pdo->prepare(
    'INSERT INTO moderation_case_assignments (case_id, assigned_user_id, assigned_by, assigned_at)
     VALUES (:case_id, :assigned_user_id, :assigned_by, UTC_TIMESTAMP())'
);
$assignment->execute([
    ':case_id' => $caseId,
    ':assigned_user_id' => $moderatorId,
    ':assigned_by' => $adminId,
]);
$evidence = bushisa_case_evidence_for_viewer($pdo, $caseId, $moderatorId, 'assigned synthetic regression');
assert_policy(count($evidence) === 1, 'assigned moderator should read exactly scoped evidence');
assert_policy((string) $evidence[0]['evidence_excerpt'] === 'case evidence body', 'evidence snapshot should preserve reported message body');

assert_policy(block_user($pdo, $rightUserId, $leftUserId), 'block should complete through shared close path');
assert_policy(operational_get_match($pdo, $matchId, $leftUserId) === null, 'closed match should be unavailable to ordinary match lookup');

$rawMatch = $pdo->prepare('SELECT match_status FROM matches WHERE id = :match_id LIMIT 1');
$rawMatch->execute([':match_id' => $matchId]);
assert_policy((string) $rawMatch->fetchColumn() === 'blocked', 'block should close rather than cascade-delete match row');

$messageExists = $pdo->prepare('SELECT body FROM messages WHERE id = :message_id LIMIT 1');
$messageExists->execute([':message_id' => $messageId]);
assert_policy((string) $messageExists->fetchColumn() === 'case evidence body', 'block must not cascade-delete protected message row');

$revoked = $pdo->prepare('SELECT status FROM message_requests WHERE match_id = :match_id ORDER BY id DESC LIMIT 1');
$revoked->execute([':match_id' => $matchId]);
assert_policy((string) $revoked->fetchColumn() === 'revoked', 'block should revoke approved chat grant');

$deleteUserId = policy_create_user($pdo, 'male', 'female');
$otherUserId = policy_create_user($pdo, 'female', 'male');
$deleteMatchId = policy_create_match($pdo, $deleteUserId, $otherUserId);
$confession = $pdo->prepare('INSERT INTO confessions (author_id, body, is_flagged, created_at) VALUES (:author_id, "delete me publicly", 0, UTC_TIMESTAMP())');
$confession->execute([':author_id' => $deleteUserId]);
$confessionId = (int) $pdo->lastInsertId();
$message->execute([':match_id' => $deleteMatchId, ':sender_id' => $deleteUserId]);
$deleteMessageId = (int) $pdo->lastInsertId();

assert_policy(anonymise_user($pdo, $deleteUserId, $deleteUserId), 'account deletion should complete');

$closedDeletedMatch = $pdo->prepare('SELECT match_status FROM matches WHERE id = :match_id LIMIT 1');
$closedDeletedMatch->execute([':match_id' => $deleteMatchId]);
assert_policy((string) $closedDeletedMatch->fetchColumn() === 'deleted_cleanup', 'either-account deletion should close relationship cleanup');

$deleteMessage = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE id = :message_id');
$deleteMessage->execute([':message_id' => $deleteMessageId]);
assert_policy((int) $deleteMessage->fetchColumn() === 1, 'account deletion must not cascade-delete retained message row');

$deletedConfession = $pdo->prepare('SELECT deleted_at, body FROM confessions WHERE id = :confession_id LIMIT 1');
$deletedConfession->execute([':confession_id' => $confessionId]);
$deletedConfessionRow = $deletedConfession->fetch(PDO::FETCH_ASSOC);
assert_policy(is_array($deletedConfessionRow) && (string) $deletedConfessionRow['deleted_at'] !== '', 'account deletion should resolve owned confession before unlink');
assert_policy((string) $deletedConfessionRow['body'] === '', 'deleted public confession body should be erased from ordinary storage');

$replay = $pdo->prepare('SELECT COUNT(*) FROM protective_replay_events WHERE subject_type = "user" AND subject_id = :user_id AND event_type = "account_deleted"');
$replay->execute([':user_id' => $deleteUserId]);
assert_policy((int) $replay->fetchColumn() >= 1, 'account deletion should write independent replay ledger event');

$cleanup = $pdo->prepare('SELECT COUNT(*) FROM cleanup_outbox WHERE subject_type = "user" AND subject_id = :user_id');
$cleanup->execute([':user_id' => $deleteUserId]);
assert_policy((int) $cleanup->fetchColumn() >= 1, 'account deletion should enqueue idempotent cleanup rows');

$mailUserId = policy_create_user($pdo, 'male', 'female');
$mailUser = find_user_by_id($pdo, $mailUserId);
$mailEmail = (string) $mailUser['email'];
$code = bushisa_issue_signup_code($pdo, $mailUserId, $mailEmail);
$mailBody = $pdo->prepare('SELECT body FROM mail_outbox WHERE recipient_email = :email ORDER BY id DESC LIMIT 1');
$mailBody->execute([':email' => $mailEmail]);
assert_policy(str_contains((string) $mailBody->fetchColumn(), $code), 'synthetic fake mail should expose code before terminal state only');
assert_policy(bushisa_verify_signup_code($pdo, $mailUserId, $code)['success'] === true, 'signup code should verify');
$mailBody->execute([':email' => $mailEmail]);
assert_policy($mailBody->fetchColumn() === null, 'terminal signup verification should erase code-bearing fake mail payload');
$codeHash = $pdo->prepare('SELECT code_hash FROM student_email_approval_codes WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
$codeHash->execute([':user_id' => $mailUserId]);
assert_policy($codeHash->fetchColumn() === null, 'terminal signup verification should erase verifier hash');

$restore = $pdo->prepare(
    'INSERT INTO restore_replay_runs (restore_label, ledger_available, ledger_complete, traffic_reopen_allowed, checked_at, evidence_json)
     VALUES ("synthetic-missing-ledger", 0, 0, 0, UTC_TIMESTAMP(), JSON_OBJECT("policy_status", "proposed"))'
);
$restore->execute();
$restoreCheck = $pdo->query('SELECT traffic_reopen_allowed FROM restore_replay_runs WHERE restore_label = "synthetic-missing-ledger" ORDER BY id DESC LIMIT 1');
assert_policy((int) $restoreCheck->fetchColumn() === 0, 'missing replay evidence must fail closed for synthetic restore');

fwrite(STDOUT, "policy_safeguards_p0_flows PASS\n");
