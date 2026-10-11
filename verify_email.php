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

ensure_operational_schema($pdo);

$error = null;
$success = null;
$email = clean_email((string) ($_GET['email'] ?? $_POST['email'] ?? ($_SESSION['email'] ?? '')));
$csrfToken = generate_csrf_token();

function bushisa_verification_user(PDO $pdo, string $email): ?array
{
    if (isset($_SESSION['user_id'])) {
        return find_user_by_id($pdo, (int) $_SESSION['user_id']);
    }

    if ($email === '') {
        return null;
    }

    return find_user_by_email($pdo, $email);
}

$user = bushisa_verification_user($pdo, $email);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($submittedToken === '' || !validate_csrf_token($submittedToken)) {
        $error = 'Invalid form submission. Please try again.';
    } elseif ($user === null) {
        $error = 'No pending account was found for that email.';
    } else {
        $action = isset($_POST['action']) && is_string($_POST['action']) ? clean_enum($_POST['action'], ['verify', 'resend']) : 'verify';
        if ($action === 'resend') {
            bushisa_issue_signup_code($pdo, (int) $user['id'], (string) $user['email']);
            $success = 'A new approval code was sent to your student email.';
        } else {
            $code = isset($_POST['approval_code']) && is_string($_POST['approval_code']) ? $_POST['approval_code'] : '';
            $result = bushisa_verify_signup_code($pdo, (int) $user['id'], $code);
            if (!empty($result['success'])) {
                if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $user['id']) {
                    $_SESSION['session_version'] = (int) ($_SESSION['session_version'] ?? 0) + 1;
                    redirect('onboarding.php');
                }

                $success = 'Student email approved. Sign in to complete adult onboarding.';
            } else {
                $error = is_string($result['error'] ?? null) ? $result['error'] : 'Approval failed.';
            }
        }
    }
}

render_auth_start('Approve student email', $csrfToken);
?>
<section class="auth-card" aria-labelledby="verify-title">
    <span class="brand-mark" aria-hidden="true">B</span>
    <h1 id="verify-title">Approve student email</h1>
    <p class="auth-card__lead">Enter the approval code sent to your NUST student mailbox.</p>

    <?php render_flash($error, $success); ?>

    <form class="form-grid" method="post" action="verify_email.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="verify">

        <label class="field">
            <span>Student email</span>
            <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
        </label>

        <label class="field">
            <span>Approval code</span>
            <input type="text" name="approval_code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required>
        </label>

        <button class="button" type="submit">Approve email</button>
    </form>

    <form method="post" action="verify_email.php" class="card-actions">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="resend">
        <input type="hidden" name="email" value="<?= e($email) ?>">
        <button class="button-secondary" type="submit">Send a new code</button>
        <a class="button-secondary" href="login.php">Sign in</a>
    </form>
</section>
<?php
render_auth_end();
