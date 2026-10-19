<?php
declare(strict_types=1);
require_once __DIR__ . '/php/security_headers.php';
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
require_once __DIR__ . '/php/session_manager.php';
require_once __DIR__ . '/php/csrf.php';
require_once __DIR__ . '/php/frontend.php';
$message = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validate_csrf_token($csrf)) $error = 'This reset could not be completed. Request a new link if needed.';
    else {
        $token = isset($_POST['reset_token']) && is_string($_POST['reset_token']) ? trim($_POST['reset_token']) : '';
        $password = isset($_POST['new_password']) && is_string($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        if ($password !== $confirm) $error = 'The new passwords do not match.';
        else {
            try {
                require_once __DIR__ . '/config/db.php';
                require_once __DIR__ . '/php/password_reset.php';
                if (strlen($password) <= 1024 && bushisa_reset_consume($pdo,$token,$password)) $message = 'If the reset proof was valid, your password has been changed. Sign in with your new password.';
                else $error = 'This reset could not be completed. Request a new link if needed.';
            } catch (Throwable) { $error = 'This reset could not be completed. Request a new link if needed.'; }
        }
    }
}
$csrfToken = generate_csrf_token();
render_auth_start('Choose a new password',$csrfToken);
?>
<section class="auth-card" aria-labelledby="reset-password-title">
    <span class="brand-mark" aria-hidden="true">B</span>
    <h1 id="reset-password-title">Choose a new password</h1>
    <p class="auth-card__lead">Enter the one-time key from your email, then choose a new password.</p>
    <?php render_flash($error,$message); ?>
    <form class="form-grid" method="post" action="reset_password.php" autocomplete="off" data-server-form>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <label class="field"><span>One-time reset key</span><input id="reset-token" type="text" name="reset_token" maxlength="64" inputmode="text" autocomplete="off" autocapitalize="none" spellcheck="false" required></label>
        <label class="field"><span>New password</span><input type="password" name="new_password" autocomplete="new-password" maxlength="1024" required></label>
        <label class="field"><span>Confirm new password</span><input type="password" name="confirm_password" autocomplete="new-password" maxlength="1024" required></label>
        <button class="button" type="submit">Change password</button>
    </form>
    <p class="auth-switch"><a href="login.php">Return to sign in</a></p>
</section>
<?php render_auth_end(['js/password-reset-token.js']);
