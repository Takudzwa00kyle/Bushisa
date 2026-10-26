<?php

declare(strict_types=1);

require_once __DIR__ . '/php/security_headers.php';
require_once __DIR__ . '/php/session_manager.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php/csrf.php';
require_once __DIR__ . '/php/sanitize.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/function.php';
require_once __DIR__ . '/php/frontend.php';
require_once __DIR__ . '/php/signup_verification.php';

require_login();

$userId = (int) $_SESSION['user_id'];
$user = find_user_by_id($pdo, $userId);
if ($user !== null && (string) ($user['account_status'] ?? 'active') === 'active') {
    redirect('discover.php');
}

$error = null;
$success = null;
$csrfToken = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($submittedToken === '' || !validate_csrf_token($submittedToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $dateOfBirth = isset($_POST['date_of_birth']) && is_string($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
        $oldVersion = (int) ($_SESSION['session_version'] ?? 0);
        session_write_close();
        $result = bushisa_complete_adult_onboarding($pdo, $userId, $dateOfBirth, $oldVersion);
        if (!empty($result['success'])) {
            bushisa_resume_transition_session($pdo, $userId, $oldVersion, (int) $result['session_version']);
            redirect('discover.php');
        }

        $error = is_string($result['error'] ?? null) ? $result['error'] : 'Adult onboarding failed.';
        $freshUser = find_user_by_id($pdo, $userId);
        if ($freshUser !== null && (string) ($freshUser['account_status'] ?? '') === 'underage_blocked') {
            start_secure_session();
            destroy_session();
        }
    }
}
$deadline = bushisa_signup_deadline($pdo, $userId);

render_auth_start('Adult onboarding', $csrfToken);
?>
<section class="auth-card" aria-labelledby="onboarding-title">
    <span class="brand-mark" aria-hidden="true">B</span>
    <h1 id="onboarding-title">Adult eligibility</h1>
    <p class="auth-card__lead">Bushisa discovery is restricted to students who are at least 18 years old.</p>
    <p>Finish within 7 days of your first successful email approval. Visits and retries do not extend this deadline.</p>
    <?php if ($deadline !== null): ?><p>Onboarding deadline: <?= e($deadline) ?> UTC.</p><?php endif; ?>
    <p>An expired signup is disabled and scheduled for cleanup. A fresh signup after cleanup must repeat email approval and adult onboarding.</p>

    <?php render_flash($error, $success); ?>

    <form class="form-grid" method="post" action="onboarding.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <label class="field">
            <span>Date of birth</span>
            <input type="date" name="date_of_birth" required>
            <p class="field-help">Your full date of birth is used for server-side eligibility and is not shown on public profile cards.</p>
        </label>

        <button class="button" type="submit">Complete onboarding</button>
    </form>
</section>
<?php
render_auth_end();
