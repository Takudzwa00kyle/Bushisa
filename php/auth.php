<?php

declare(strict_types=1);

require_once __DIR__ . '/signup_verification.php';

/**
 * Attempt to authenticate a user by email and password.
 *
 * @param PDO $pdo
 * @param string $email
 * @param string $password
 * @param string|null $identifier
 * @return array{success: bool, error: string|null, redirect: string|null}
 */
function attempt_login(PDO $pdo, string $email, string $password, ?string $identifier = null): array
{
    $rateLimitKey = $identifier ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if (!check_rate_limit($pdo, 'login', $rateLimitKey, 5, 900)) {
        log_to_file('Login rate limit exceeded for ' . $rateLimitKey, 'WARN');

        return [
            'success' => false,
            'error' => 'Too many login attempts. Please try again later.',
            'redirect' => null,
        ];
    }

    if ($email === '') {
        record_attempt($pdo, 'login', $rateLimitKey);

        return [
            'success' => false,
            'error' => 'Please enter a valid email address.',
            'redirect' => null,
        ];
    }

    $user = find_user_by_email($pdo, $email);
    if ($user === null) {
        record_attempt($pdo, 'login', $rateLimitKey);

        return [
            'success' => false,
            'error' => 'Invalid email or password.',
            'redirect' => null,
        ];
    }

    if (is_user_suspended($pdo, (int) $user['id'])) {
        log_to_file('Suspended user login denied for user_id ' . (int) $user['id'], 'WARN');

        return [
            'success' => false,
            'error' => 'Your account is suspended.',
            'redirect' => null,
        ];
    }

    if ((array_key_exists('is_active', $user) && (int) $user['is_active'] !== 1)
        || (array_key_exists('deleted_at', $user) && $user['deleted_at'] !== null && (string) $user['deleted_at'] !== '')
        || (array_key_exists('account_status', $user) && in_array((string) $user['account_status'], ['underage_blocked', 'suspended', 'deleted'], true))
    ) {
        log_to_file('Inactive user login denied for user_id ' . (int) $user['id'], 'WARN');

        return [
            'success' => false,
            'error' => 'Your account is not available.',
            'redirect' => null,
        ];
    }

    $passwordHash = isset($user['password_hash']) && is_string($user['password_hash']) ? $user['password_hash'] : '';
    if ($passwordHash === '' || !verify_password($password, $passwordHash)) {
        record_attempt($pdo, 'login', $rateLimitKey);

        return [
            'success' => false,
            'error' => 'Invalid email or password.',
            'redirect' => null,
        ];
    }

    if (in_array($user['account_status'] ?? '', ['pending_email', 'pending_onboarding'], true)) {
        $expiry = bushisa_signup_check_account($pdo, (int) $user['id']);
        if ($expiry['state'] !== 'live') {
            return ['success' => false, 'error' => bushisa_signup_expiry_message($expiry['state']), 'redirect' => null];
        }
        $user = find_user_by_email($pdo, $email);
        if ($user === null || !bushisa_signup_user_live($user)) {
            return ['success' => false, 'error' => 'Your account is not available.', 'redirect' => null];
        }
    }

    clear_attempts($pdo, 'login', $rateLimitKey);
    regenerate_session();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = isset($user['role']) && is_string($user['role']) && $user['role'] !== '' ? $user['role'] : 'user';
    $_SESSION['email'] = $user['email'] ?? $email;
    $_SESSION['session_version'] = isset($user['session_version']) ? (int) $user['session_version'] : 0;

    log_action($pdo, (int) $user['id'], 'login', $rateLimitKey);
    log_to_file('User ' . (int) $user['id'] . ' logged in', 'INFO');

    return [
        'success' => true,
        'error' => null,
        'redirect' => bushisa_post_login_redirect($user),
    ];
}

/**
 * @param array<string, mixed> $user
 */
function bushisa_post_login_redirect(array $user): string
{
    $status = (string) ($user['account_status'] ?? 'active');
    if ($status === 'pending_email' || (array_key_exists('is_verified', $user) && (int) $user['is_verified'] !== 1)) {
        return 'verify_email.php';
    }

    if ($status === 'pending_onboarding') {
        return 'onboarding.php';
    }

    return 'discover.php';
}

/**
 * Register a new Bushisa user.
 *
 * @param PDO $pdo
 * @param array<string, mixed> $data
 * @return array{success: bool, error: string|null, message: string|null, redirect: string|null}
 */
function register_user(PDO $pdo, array $data): array
{
    $studentId = strtolower(trim(str_replace("\0", '', (string) ($data['nust_student_id'] ?? ''))));
    $email = clean_email((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $displayName = trim(str_replace("\0", '', (string) ($data['display_name'] ?? '')));
    $anonHandle = ltrim(trim(str_replace("\0", '', (string) ($data['anon_handle'] ?? ''))), '@');
    $gender = strtolower(trim(str_replace("\0", '', (string) ($data['gender'] ?? ''))));
    $faculty = trim(str_replace("\0", '', (string) ($data['faculty'] ?? '')));
    $yearOfStudy = trim(str_replace("\0", '', (string) ($data['year_of_study'] ?? '')));

    if ($studentId === '' || !bushisa_student_id_is_valid($studentId)) {
        return [
            'success' => false,
            'error' => 'Please enter a valid NUST student number such as n02531234f.',
            'message' => null,
            'redirect' => null,
        ];
    }

    if ($email === '' || !bushisa_student_email_is_valid($email, $studentId)) {
        return [
            'success' => false,
            'error' => 'Use the student email that matches your NUST student number.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $passwordErrors = validate_password($password);
    if ($password !== '' && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $passwordErrors[] = 'Password must contain at least one special character.';
    }

    if ($passwordErrors !== []) {
        return [
            'success' => false,
            'error' => implode(' ', $passwordErrors),
            'message' => null,
            'redirect' => null,
        ];
    }

    if ($displayName === '' || !preg_match('/^[A-Za-z0-9 ]{2,30}$/', $displayName)) {
        return [
            'success' => false,
            'error' => 'Display name must be 2 to 30 characters and use only letters, numbers, and spaces.',
            'message' => null,
            'redirect' => null,
        ];
    }

    if ($anonHandle === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $anonHandle)) {
        return [
            'success' => false,
            'error' => 'Please choose a valid anonymous handle.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $gender = clean_enum($gender, ['male', 'female']) ?? '';
    if ($gender === '') {
        return [
            'success' => false,
            'error' => 'Please select a valid gender.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $faculty = clean_enum($faculty, [
        'Applied Sciences',
        'Built Environment',
        'Communication & Information Science',
        'Commerce & Law',
        'Industrial Technology',
        'Medicine',
        'Science & Technology Education',
    ]) ?? '';
    if ($faculty === '') {
        return [
            'success' => false,
            'error' => 'Please select a valid faculty.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $yearOfStudy = clean_enum($yearOfStudy, ['Part 1', 'Part 2', 'Part 3', 'Part 4', 'Postgrad']) ?? '';
    if ($yearOfStudy === '') {
        return [
            'success' => false,
            'error' => 'Please select a valid year of study.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $existingUser = $pdo->prepare('SELECT id FROM users WHERE email = :email OR LOWER(nust_student_id) = :student_id LIMIT 1');
    $existingUser->execute([
        ':email' => $email,
        ':student_id' => $studentId,
    ]);

    if ($existingUser->fetchColumn() !== false) {
        return [
            'success' => false,
            'error' => 'An account with that email or student ID already exists.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $existingHandle = $pdo->prepare('SELECT user_id FROM profiles WHERE anon_handle = :anon_handle LIMIT 1');
    $existingHandle->execute([':anon_handle' => $anonHandle]);

    if ($existingHandle->fetchColumn() !== false) {
        return [
            'success' => false,
            'error' => 'That anonymous handle is already taken.',
            'message' => null,
            'redirect' => null,
        ];
    }

    $preferredGender = $gender === 'male' ? 'female' : 'male';
    $passwordHash = hash_password($password);

    try {
        $pdo->beginTransaction();

        $userStatement = $pdo->prepare(
            'INSERT INTO users (nust_student_id, email, password_hash, is_verified, is_suspended, is_active, account_status, session_version) VALUES (:student_id, :email, :password_hash, 0, 0, 1, "pending_email", 0)'
        );
        $userStatement->execute([
            ':student_id' => $studentId,
            ':email' => $email,
            ':password_hash' => $passwordHash,
        ]);

        $userId = (int) $pdo->lastInsertId();
        bushisa_signup_record_creation($pdo, $userId);

        $profileStatement = $pdo->prepare(
            'INSERT INTO profiles (user_id, display_name, anon_handle, bio, gender, date_of_birth, faculty, year_of_study, profile_photo_path) VALUES (:user_id, :display_name, :anon_handle, :bio, :gender, NULL, :faculty, :year_of_study, NULL)'
        );
        $profileStatement->execute([
            ':user_id' => $userId,
            ':display_name' => $displayName,
            ':anon_handle' => $anonHandle,
            ':bio' => '',
            ':gender' => $gender,
            ':faculty' => $faculty,
            ':year_of_study' => $yearOfStudy,
        ]);

        $preferencesStatement = $pdo->prepare(
            'INSERT INTO preferences (user_id, preferred_gender, age_range_min, age_range_max, preferred_faculty) VALUES (:user_id, :preferred_gender, 18, 30, :preferred_faculty)'
        );
        $preferencesStatement->execute([
            ':user_id' => $userId,
            ':preferred_gender' => $preferredGender,
            ':preferred_faculty' => 'Any',
        ]);

        bushisa_issue_signup_code($pdo, $userId, $email);

        $pdo->commit();

        log_action($pdo, $userId, 'register', $_SERVER['REMOTE_ADDR'] ?? null);
        log_to_file('User ' . $userId . ' registered', 'INFO');

        return [
            'success' => true,
            'error' => null,
            'message' => 'Account created. Approval mail has been queued. Approve your student email within 72 hours; resending a code does not extend that deadline.',
            'redirect' => 'verify_email.php?email=' . rawurlencode($email),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        log_to_file('Registration failed; check database and mail configuration.', 'ERROR');

        return [
            'success' => false,
            'error' => 'Registration failed. Please try again.',
            'message' => null,
            'redirect' => null,
        ];
    }
}
