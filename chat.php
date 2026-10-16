<?php

declare(strict_types=1);

require_once __DIR__ . '/php/security_headers.php';
require_once __DIR__ . '/php/session_manager.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php/csrf.php';
require_once __DIR__ . '/php/sanitize.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/rate_limit.php';
require_once __DIR__ . '/php/logger.php';
require_once __DIR__ . '/php/function.php';
require_once __DIR__ . '/php/frontend.php';
require_once __DIR__ . '/php/operational.php';
require_once __DIR__ . '/php/pair_messages.php';

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$error = null;
$success = null;
$csrfToken = null;
$messages = [];
$other_user = null;

$matchId = clean_int($_GET['match_id'] ?? null);
if ($matchId === null || $matchId <= 0) {
    redirect('matches.php');
}

$sessionVersion=(int)$_SESSION['session_version'];
try {
    $permissionState=bushisa_pair_request($pdo,$currentUserId,$sessionVersion,$matchId,'read');
    $other_user=bushisa_pair_chat_profile($pdo,$currentUserId,$sessionVersion,$matchId);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $token=$_POST['csrf_token']??'';
        if (!is_string($token)||!validate_csrf_token($token)) { throw new BushisaReportError(403,'Invalid form submission'); }
        $map=['request_chat'=>'request','approve_chat'=>'approve','decline_chat'=>'decline','revoke_chat'=>'revoke'];
        $formAction=$_POST['action']??'';
        if (isset($map[$formAction])) {
            $intent=['action'=>$map[$formAction],'match_id'=>$matchId,'pair_version'=>(int)($_POST['pair_version']??-1),'idempotency_key'=>$_POST['idempotency_key']??''];
            bushisa_pair_request($pdo,$currentUserId,$sessionVersion,$matchId,$map[$formAction],$intent);
        } elseif ($formAction==='send_message') {
            bushisa_pair_messages($pdo,$currentUserId,$sessionVersion,$matchId,'send',['body'=>$_POST['body']??'']);
        } else { throw new BushisaReportError(400,'Invalid conversation action'); }
        $success='Conversation updated.';
    }
    $permissionState=bushisa_pair_request($pdo,$currentUserId,$sessionVersion,$matchId,'read');
    $chatApproved=operational_has_message_approval($pdo,$matchId);
    try { $messages=bushisa_pair_messages($pdo,$currentUserId,$sessionVersion,$matchId,'read'); }
    catch (BushisaReportError $e) { if ($e->status!==403) { throw $e; } $messages=[]; }
} catch (BushisaReportError $e) {
    $error=$e->getMessage(); $messages=[]; $chatApproved=false;
    if (!isset($other_user) || !is_array($other_user)) { redirect('matches.php'); }
}
$csrfToken = generate_csrf_token();

render_page_shell_start('chat', (string) ($other_user['display_name'] ?? 'Conversation'), $csrfToken, $currentUserId, false, 'Approved conversation state');
?>
<?php render_flash($error, $success); ?>
<?php render_service_under_development('Chat', 'Service under development. Request, approval, revoke and message-send controls are paused in the UI until the safety and overlap checks are up to date. This message will be revoked once this service is up to date on the next push.'); ?>

<section class="message-card">
    <div class="chat-header">
        <a class="button-secondary" href="matches.php">Back</a>
        <div class="profile-summary">
            <?= render_avatar($other_user['profile_photo_path'] ?? null, (string) ($other_user['display_name'] ?? 'Match')) ?>
            <div>
                <h2><?= e($other_user['display_name'] ?? 'Match') ?></h2>
                <p class="muted"><?= e($other_user['faculty'] ?? 'NUST student') ?></p>
            </div>
        </div>
        <a class="button-secondary" href="community_guidelines.php">Safety</a>
    </div>
    <p class="field-help">A match is not chat permission. Messages are available only after a recipient approves the request.</p>
</section>

<section class="message-card chat-thread" aria-labelledby="thread-title">
    <h2 class="sr-only" id="thread-title">Conversation messages</h2>
    <?php if (!$chatApproved): ?>
        <div class="empty-state">
            <?php if ($permissionState['status'] === 'none' || $permissionState['status'] === 'declined' || $permissionState['status'] === 'revoked'): ?>
                <h2>Ask before chatting</h2>
                <p>Send a message request first. The other person can approve or decline without pressure.</p>
                <form method="post" action="chat.php?match_id=<?= e($matchId) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="pair_version" value="<?= e($permissionState['pair']['pair_version'] ?? -1) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(32))) ?>">
                    <input type="hidden" name="action" value="request_chat">
                    <button class="button" type="submit" disabled title="Service under development">Request message permission</button>
                </form>
            <?php elseif ($permissionState['status'] === 'pending' && $permissionState['is_requester']): ?>
                <h2>Request pending</h2>
                <p>Your match has not approved chat permission yet.</p>
                <form method="post" action="chat.php?match_id=<?= e($matchId) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="pair_version" value="<?= e($permissionState['pair']['pair_version'] ?? -1) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(32))) ?>">
                    <input type="hidden" name="action" value="revoke_chat">
                    <button class="button-secondary" type="submit" disabled title="Service under development">Cancel request</button>
                </form>
            <?php elseif ($permissionState['status'] === 'pending' && $permissionState['is_recipient']): ?>
                <h2>Message request</h2>
                <p>This established match wants permission to chat. Approving enables this conversation.</p>
                <div class="card-actions">
                    <form method="post" action="chat.php?match_id=<?= e($matchId) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="pair_version" value="<?= e($permissionState['pair']['pair_version'] ?? -1) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(32))) ?>">
                        <input type="hidden" name="action" value="approve_chat">
                        <button class="button" type="submit" disabled title="Service under development">Approve</button>
                    </form>
                    <form method="post" action="chat.php?match_id=<?= e($matchId) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="pair_version" value="<?= e($permissionState['pair']['pair_version'] ?? -1) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(32))) ?>">
                        <input type="hidden" name="action" value="decline_chat">
                        <button class="button-secondary" type="submit" disabled title="Service under development">Decline</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="message-list">
        <?php if ($messages === []): ?>
            <div class="empty-state">
                <h2>No messages yet</h2>
                <p>Start only when both people are comfortable. Connection is not consent to chat.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php $mine = (int) ($message['sender_id'] ?? 0) === $currentUserId; ?>
                <article class="message-bubble<?= $mine ? ' message-bubble--mine' : '' ?>">
                    <p><?= e($message['body'] ?? '') ?></p>
                    <time class="message-time" datetime="<?= e($message['sent_at'] ?? '') ?>"><?= e(format_time_ago((string) ($message['sent_at'] ?? ''))) ?></time>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form class="chat-composer" method="post" action="chat.php?match_id=<?= e($matchId) ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="pair_version" value="<?= e($permissionState['pair']['pair_version'] ?? -1) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(32))) ?>">
        <input type="hidden" name="action" value="send_message">
        <label class="sr-only" for="message-body">Message</label>
        <textarea id="message-body" name="body" rows="2" maxlength="1000" required placeholder="Service under development..." disabled></textarea>
        <button class="chat-composer__send" type="submit" disabled title="Service under development">Send</button>
    </form>
    <?php endif; ?>
</section>
<?php
render_page_shell_end();
