<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/q11_fixture.php';
require_once __DIR__.'/../../php/session_copy_cleanup.php';
function sc_runtime(PDO $root,string $database): array
{
    $c=q11_worker_user($root,$database);$db=bushisa_quote_disposable_identifier($database);
    foreach(['session_copy_cleanup_jobs','session_copy_purge_items','session_copy_purge_receipts','session_copy_recovery','session_copy_cleanup_alerts'] as $table) { $root->exec("GRANT INSERT,UPDATE ON $db.$table TO ".$c['account']); }
    return $c;
}
function sc_q(PDO $p,string $sql,array $args=[]): PDOStatement { return bushisa_signup_query($p,$sql,$args); }
function sc_event(PDO $p,array $u): string
{
    $c=sc_q($p,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
    $key=sc_q($p,'SELECT signup_attempt_key FROM users WHERE id=?',[$u['id']])->fetchColumn();
    $due=$c['first_email_approved_at']===null?$c['unverified_expires_at']:$c['onboarding_expires_at'];$stage=$c['first_email_approved_at']===null?'pending_email':'pending_onboarding';
    $receipt=hash('sha256','signup-expiry-v1|'.$u['id'].'|'.$stage.'|'.$due);
    return hash('sha256','session_copies_v1|'.$receipt.'|'.$key);
}
function sc_expire(PDO $root,PDO $db,array $u): array
{
    $c=sc_q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
    q11_clock($root,$c['first_email_approved_at']===null?$c['unverified_expires_at']:$c['onboarding_expires_at']);
    $r=bushisa_signup_check_account($db,$u['id']);
    if($r['state']!=='expired')throw new RuntimeException('Synthetic Q11 expiry failed');
    return sc_q($root,'SELECT * FROM session_copy_obligations WHERE account_id=? ORDER BY effective_at DESC',[$u['id']])->fetch();
}
