<?php

declare(strict_types=1);

require_once __DIR__ . '/support/disposable_database.php';

bushisa_require_disposable_test_database('integration_p0_flows');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/password_policy.php';
require_once __DIR__ . '/../php/rate_limit.php';
require_once __DIR__ . '/../php/logger.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/signup_verification.php';
require_once __DIR__ . '/../php/auth.php';

function fail_test(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}

function assert_test(bool $condition, string $message): void
{
    if (!$condition) {
        fail_test($message);
    }
}

ensure_operational_schema($pdo);

function test_student_id(): string
{
    return 'n025' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(97, 122));
}

$studentId = test_student_id();
$email = $studentId . '@students.nust.ac.zw';

$result = register_user($pdo, [
    'nust_student_id' => $studentId,
    'email' => $email,
    'password' => 'Passw0rd!',
    'display_name' => 'Test User',
    'anon_handle' => 'test_user_' . random_int(1000, 9999),
    'gender' => 'male',
    'faculty' => 'Applied Sciences',
    'year_of_study' => 'Part 2',
]);

assert_test($result['success'] === true, 'registration should create a pending account: ' . (string) ($result['error'] ?? 'unknown error'));

$user = find_user_by_email($pdo, $email);
assert_test(is_array($user), 'registered user should exist');
assert_test((int) $user['is_verified'] === 0, 'new account must not be verified before code entry');
assert_test((string) $user['account_status'] === 'pending_email', 'new account should be pending email approval');

$mail = $pdo->prepare('SELECT body FROM mail_outbox WHERE recipient_email = :email ORDER BY id DESC LIMIT 1');
$mail->execute([':email' => $email]);
$body = (string) $mail->fetchColumn();
assert_test(preg_match('/approval code is ([0-9]{6,10})/', $body, $matches) === 1, 'fake mail should contain approval code');

$wrong = bushisa_verify_signup_code($pdo, (int) $user['id'], '000000');
assert_test($wrong['success'] === false, 'wrong approval code should fail');

$verified = bushisa_verify_signup_code($pdo, (int) $user['id'], $matches[1]);
assert_test($verified['success'] === true, 'correct approval code should pass');

$mail->execute([':email' => $email]);
assert_test($mail->fetchColumn() === null, 'terminal approval should erase synthetic code-bearing mail body');
$codeHash = $pdo->prepare('SELECT code_hash FROM student_email_approval_codes WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
$codeHash->execute([':user_id' => (int) $user['id']]);
assert_test($codeHash->fetchColumn() === null, 'terminal approval should erase verifier hash');

$user = find_user_by_email($pdo, $email);
assert_test((int) $user['is_verified'] === 1, 'code entry should mark account verified');
assert_test((string) $user['account_status'] === 'pending_onboarding', 'verified account should require DOB onboarding');

$underageStudentId = test_student_id();
$underageEmail = $underageStudentId . '@students.nust.ac.zw';
$underageResult = register_user($pdo, [
    'nust_student_id' => $underageStudentId,
    'email' => $underageEmail,
    'password' => 'Passw0rd!',
    'display_name' => 'Young User',
    'anon_handle' => 'young_user_' . random_int(1000, 9999),
    'gender' => 'female',
    'faculty' => 'Applied Sciences',
    'year_of_study' => 'Part 1',
]);
assert_test($underageResult['success'] === true, 'underage account can be created before DOB is known: ' . (string) ($underageResult['error'] ?? 'unknown error'));
$underageUser = find_user_by_email($pdo, $underageEmail);
assert_test(is_array($underageUser), 'underage fixture user should exist');
$underage = bushisa_complete_adult_onboarding($pdo, (int) $underageUser['id'], (new DateTimeImmutable('today'))->modify('-17 years')->format('Y-m-d'));
assert_test($underage['success'] === false, 'underage DOB should fail onboarding');
$underageUser = find_user_by_email($pdo, $underageEmail);
assert_test((string) $underageUser['account_status'] === 'underage_blocked', 'underage account should be permanently blocked');

$adult = bushisa_complete_adult_onboarding($pdo, (int) $user['id'], (new DateTimeImmutable('today'))->modify('-18 years')->format('Y-m-d'));
assert_test($adult['success'] === true, 'adult DOB should complete onboarding');
$user = find_user_by_email($pdo, $email);
assert_test((string) $user['account_status'] === 'active', 'adult account should become active');

fwrite(STDOUT, "integration_p0_flows PASS\n");
