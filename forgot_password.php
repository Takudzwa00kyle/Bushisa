<?php
declare(strict_types=1);
require_once __DIR__ . '/php/security_headers.php';
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
require_once __DIR__ . '/php/session_manager.php';
require_once __DIR__ . '/php/csrf.php';
require_once __DIR__ . '/php/sanitize.php';
require_once __DIR__ . '/php/frontend.php';
$message = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validate_csrf_token($submittedToken)) $error = 'Please refresh the form and try again.';
    else {
        try {
            require_once __DIR__ . '/config/db.php';
            require_once __DIR__ . '/php/password_reset.php';
            bushisa_reset_request($pdo, isset($_POST['email']) && is_string($_POST['email']) ? substr($_POST['email'],0,512) : '');
            $message = 'If the address is eligible, instructions will be sent. Check your inbox shortly.';
        } catch (Throwable) {
            $message = 'If the address is eligible, instructions will be sent. Check your inbox shortly.';
        }
    }
}
$csrfToken = generate_csrf_token();
render_auth_start('Forgot password', $csrfToken);
?>
<section class="auth-card" aria-labelledby="reset-request-title">
    <span class="brand-mark" aria-hidden="true">B</span>
    <h1 id="reset-request-title">Reset your password</h1>
    <p class="auth-card__lead">Enter your Bushisa account email. If it is eligible, we’ll send a one-time reset link.</p>
    <?php render_flash($error, $message); ?>
    <form class="form-grid" method="post" action="forgot_password.php" autocomplete="on" data-server-form>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <label class="field"><span>Account email</span><input type="email" name="email" maxlength="254" autocomplete="email" required></label>
        <button class="button" type="submit">Send reset instructions</button>
    </form>
    <p class="auth-switch"><a href="login.php">Return to sign in</a></p>
</section>
<?php render_auth_end();
