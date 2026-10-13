<?php

declare(strict_types=1);

require_once __DIR__ . '/support/disposable_database.php';

bushisa_require_disposable_test_database('overlap_db_p0_flows');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/password_policy.php';
require_once __DIR__ . '/../php/db.php';

function fail_overlap_db(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assert_overlap_db(bool $condition, string $message): void
{
    if (!$condition) {
        fail_overlap_db($message);
    }
}

function overlap_student_id(): string
{
    return 'n025' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(97, 122));
}

function overlap_create_user(PDO $pdo, string $gender, string $preferredGender): int
{
    $studentId = overlap_student_id();
    $email = $studentId . '@students.nust.ac.zw';
    $handle = 'overlap_' . bin2hex(random_bytes(4));

    $user = $pdo->prepare(
        'INSERT INTO users (nust_student_id, email, password_hash, role, is_verified, is_suspended, is_active, account_status, session_version)
         VALUES (:student_id, :email, :password_hash, "user", 1, 0, 1, "active", 0)'
    );
    $user->execute([
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
        ':display_name' => 'Overlap ' . $userId,
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

/**
 * @return array<int, array<string, mixed>>
 */
function overlap_run_workers(string $mode, int $leftUserId, int $rightUserId): array
{
    $barrierDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bushisa-race-' . bin2hex(random_bytes(6));
    if (!mkdir($barrierDir) && !is_dir($barrierDir)) {
        fail_overlap_db('could not create race barrier directory');
    }

    $worker = __DIR__ . '/workers/db_race_worker.php';
    $processes = [];
    $pipesByWorker = [];
    foreach (['left', 'right'] as $workerName) {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open(
            [PHP_BINARY, $worker, $mode, (string) $leftUserId, (string) $rightUserId, $barrierDir, $workerName],
            $descriptorSpec,
            $pipes
        );
        if (!is_resource($process)) {
            fail_overlap_db('could not start race worker');
        }

        fclose($pipes[0]);
        $processes[$workerName] = $process;
        $pipesByWorker[$workerName] = $pipes;
    }

    $results = [];
    foreach ($processes as $workerName => $process) {
        $stdout = stream_get_contents($pipesByWorker[$workerName][1]);
        $stderr = stream_get_contents($pipesByWorker[$workerName][2]);
        fclose($pipesByWorker[$workerName][1]);
        fclose($pipesByWorker[$workerName][2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            fail_overlap_db("race worker {$workerName} failed: " . trim((string) $stderr));
        }

        $decoded = json_decode(trim((string) $stdout), true);
        if (!is_array($decoded)) {
            fail_overlap_db("race worker {$workerName} returned invalid JSON: " . trim((string) $stdout));
        }

        $results[] = $decoded;
    }

    foreach (glob($barrierDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($barrierDir);

    return $results;
}

ensure_operational_schema($pdo);

$leftUserId = overlap_create_user($pdo, 'male', 'female');
$rightUserId = overlap_create_user($pdo, 'female', 'male');

$matchResults = overlap_run_workers('match', $leftUserId, $rightUserId);
assert_overlap_db(count($matchResults) === 2, 'both match workers should return a result');
foreach ($matchResults as $result) {
    assert_overlap_db(($result['ok'] ?? false) === true, 'match worker should complete successfully');
    assert_overlap_db((int) ($result['match_id'] ?? 0) > 0, 'match worker should return a match id');
}

$matchColumns = operational_match_columns($pdo);
$matchCount = $pdo->prepare(
    sprintf(
        'SELECT COUNT(*)
         FROM matches
         WHERE (%1$s = :user_a AND %2$s = :user_b)
            OR (%1$s = :reverse_user_b AND %2$s = :reverse_user_a)',
        $matchColumns['user_a'],
        $matchColumns['user_b']
    )
);
$matchCount->execute([
    ':user_a' => min($leftUserId, $rightUserId),
    ':user_b' => max($leftUserId, $rightUserId),
    ':reverse_user_b' => max($leftUserId, $rightUserId),
    ':reverse_user_a' => min($leftUserId, $rightUserId),
]);
assert_overlap_db((int) $matchCount->fetchColumn() === 1, 'overlapping match writers should leave exactly one match row');

$swipeResults = overlap_run_workers('duplicate_swipe', $leftUserId, $rightUserId);
$insertedCount = 0;
foreach ($swipeResults as $result) {
    assert_overlap_db(($result['ok'] ?? false) === true, 'duplicate swipe worker should complete successfully');
    if (($result['inserted'] ?? false) === true) {
        $insertedCount++;
    }
}
assert_overlap_db($insertedCount === 1, 'exactly one overlapping duplicate swipe should insert');

$swipeColumns = operational_swipe_columns($pdo);
$swipeCount = $pdo->prepare(
    sprintf(
        'SELECT COUNT(*) FROM swipes WHERE swiper_id = :swiper_id AND %1$s = :target_id',
        $swipeColumns['target']
    )
);
$swipeCount->execute([
    ':swiper_id' => $leftUserId,
    ':target_id' => $rightUserId,
]);
assert_overlap_db((int) $swipeCount->fetchColumn() === 1, 'overlapping duplicate swipes should leave exactly one swipe row');

fwrite(STDOUT, "overlap_db_p0_flows PASS\n");
