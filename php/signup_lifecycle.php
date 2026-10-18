<?php
declare(strict_types=1);

require_once __DIR__.'/operational.php';

function bushisa_q11_synthetic_enabled(): bool
{
    return getenv('APP_ENV')==='development' && getenv('APP_DEBUG')==='false'
        && getenv('BUSHISA_TEST_DATABASE')==='true' && getenv('BUSHISA_ALLOW_RUNTIME_DDL')==='false'
        && getenv('BUSHISA_Q11_SYNTHETIC')==='true' && getenv('MAIL_PROVIDER')==='fake' && getenv('MAIL_TRANSPORT')==='fake'
        && in_array(getenv('DB_HOST'),['127.0.0.1','localhost','::1'],true)
        && preg_match('/\Abushisa_test_q11_[a-z0-9_]+\z/D',(string)getenv('DB_NAME'))===1
        && ctype_digit((string)getenv('DB_PORT')) && (int)getenv('DB_PORT')>0 && (int)getenv('DB_PORT')<=65535
        && is_string(getenv('DB_USER')) && getenv('DB_USER')!=='';
}
function bushisa_q11_synthetic_guard(): void
{ if (!bushisa_q11_synthetic_enabled()) { throw new RuntimeException('Q11 synthetic destination refused'); } }
function bushisa_signup_query(PDO $pdo,string $sql,array $args=[]): PDOStatement
{ $q=$pdo->prepare($sql); $q->execute($args); return $q; }
function bushisa_signup_now(PDO $pdo): string
{
    if (bushisa_q11_synthetic_enabled()) {
        $at=bushisa_signup_query($pdo,'SELECT clock_at FROM q11_fixture_clock WHERE singleton=1')->fetchColumn();
        if (!is_string($at)) { throw new RuntimeException('Synthetic clock unavailable'); } return $at;
    }
    return (string)$pdo->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn();
}
function bushisa_signup_time(string $at,string $offset='+0 seconds'): string
{ return (new DateTimeImmutable($at,new DateTimeZone('UTC')))->modify($offset)->format('Y-m-d H:i:s.u'); }
function bushisa_signup_ready(PDO $pdo): bool
{ return operational_table_exists($pdo,'signup_lifecycle'); }
function bushisa_signup_require_schema(PDO $pdo): void
{ if (!bushisa_signup_ready($pdo)) { throw new RuntimeException('Signup lifecycle migration required'); } }
function bushisa_signup_transaction(PDO $pdo,callable $work): mixed
{
    if ($pdo->inTransaction()) { return $work(); }
    for($attempt=0;;$attempt++) {
        $pdo->beginTransaction();
        try { $r=$work(); $pdo->commit(); return $r; }
        catch(Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if (!$e instanceof PDOException || !in_array((int)($e->errorInfo[1]??0),[1205,1213],true) || $attempt>=2) { throw $e; }
        }
    }
}
function bushisa_signup_test_point(PDO $pdo,string $point): void
{
    static $entered=[];
    if (!bushisa_q11_synthetic_enabled()) {
        if (isset($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER'])) { throw new RuntimeException('Q11 test controls unavailable'); }
        return;
    }
    if (($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']??'')!==$point) { return; }
    if (isset($entered[$point])) { return; }
    $entered[$point]=true;
    require_once __DIR__.'/http_test_barrier.php';
    bushisa_http_test_barrier($point,$pdo);
}
function bushisa_signup_fault(string $point): void
{
    if (PHP_SAPI==='cli' && bushisa_q11_synthetic_enabled() && getenv('BUSHISA_Q11_FAULT')===$point) {
        throw new RuntimeException('Synthetic Q11 failure: '.$point);
    }
}
function bushisa_signup_alert(PDO $pdo,int $id,string $reason,?string $due=null): void
{
    bushisa_signup_query($pdo,'INSERT IGNORE INTO signup_lifecycle_alerts (alert_id,account_id,reason_code,deadline_at,observed_at) VALUES (?,?,?,?,?)',
        [hash('sha256',$id.'|'.$reason.'|'.$due),$id,$reason,$due,bushisa_signup_now($pdo)]);
}

/** Call only in the transaction that just inserted this new pending account.
 * The input is the server-persisted creation event, never a request timestamp.
 * Nothing in migration, resend, login or cleanup calls this as legacy backfill. */
function bushisa_signup_record_creation(PDO $pdo,int $id): void
{
    bushisa_signup_require_schema($pdo);
    if (!$pdo->inTransaction()) { throw new LogicException('Creation anchor requires the creation transaction'); }
    $u=bushisa_signup_query($pdo,'SELECT * FROM users WHERE id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    if (!$u || $u['account_status']!=='pending_email' || (int)$u['is_verified']!==0) { throw new RuntimeException('New pending account required'); }
    if (bushisa_signup_query($pdo,'SELECT 1 FROM signup_lifecycle WHERE account_id=?',[$id])->fetchColumn()) { throw new RuntimeException('Creation anchor already recorded'); }
    $created=bushisa_signup_time($u['created_at']);
    bushisa_signup_query($pdo,'INSERT INTO signup_lifecycle (account_id,registered_at,unverified_expires_at,anchor_status,observed_at) VALUES (?,?,?,"recorded_creation",?)',
        [$id,$created,bushisa_signup_time($created,'+72 hours'),bushisa_signup_now($pdo)]);
    if (operational_table_exists($pdo,'signup_attempt_identities')) {
        require_once __DIR__.'/signup_attempt_erasure.php';
        \Bushisa\AttemptErasure\bind_creation($pdo,$id);
    }
}
function bushisa_signup_user_live(array $u): bool
{
    return (int)$u['is_active']===1 && !(int)$u['is_suspended'] && $u['deleted_at']===null
        && in_array($u['account_status'],['pending_email','pending_onboarding','active'],true);
}
function bushisa_signup_stage_due(array $u,array $clock): ?string
{
    // A partially populated or contradictory ledger is not a trusted clock.
    if ($clock['registered_at']===null || $clock['unverified_expires_at']===null
        || $clock['unverified_expires_at']!==bushisa_signup_time($clock['registered_at'],'+72 hours')
        || !in_array($clock['anchor_status'],['recorded_creation','recorded_email_approval'],true)) { return null; }
    if ($u['account_status']==='pending_onboarding' && ($clock['anchor_status']!=='recorded_email_approval'
        || $clock['first_email_approved_at']===null || $clock['onboarding_expires_at']===null
        || $clock['onboarding_expires_at']!==bushisa_signup_time($clock['first_email_approved_at'],'+7 days'))) { return null; }
    return match($u['account_status']) {
        'pending_email'=>(int)$u['is_verified']===0?$clock['unverified_expires_at']:null,
        'pending_onboarding'=>(int)$u['is_verified']===1?$clock['onboarding_expires_at']:null,
        default=>null,
    };
}

/** All callers lock users first, then lifecycle, code IDs, mail IDs/job IDs.
 * Cancel only terminal code generations unless the account itself is expiring.
 * An in-flight send may still be accepted: cancellation is not SMTP recall. */
function bushisa_signup_cancel_material_locked(PDO $pdo,int $id,string $reason,bool $all=false): array
{
    $at=bushisa_signup_now($pdo); $codes=0; $mails=0;
    $rows=bushisa_signup_query($pdo,'SELECT * FROM student_email_approval_codes WHERE user_id=? ORDER BY id FOR UPDATE',[$id])->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $c) {
        if (!$all && $c['consumed_at']===null && $c['terminal_at']===null && $c['expires_at']>$at) { continue; }
        $q=bushisa_signup_query($pdo,'UPDATE student_email_approval_codes SET code_hash=NULL,terminal_at=COALESCE(terminal_at,?),purged_at=COALESCE(purged_at,?) WHERE id=? AND (code_hash IS NOT NULL OR terminal_at IS NULL OR purged_at IS NULL)',[$at,$at,$c['id']]);
        $codes+=$q->rowCount();
        $mail=bushisa_signup_query($pdo,'SELECT * FROM mail_outbox WHERE approval_code_id=? ORDER BY id FOR UPDATE',[$c['id']])->fetchAll(PDO::FETCH_ASSOC);
        foreach($mail as $m) {
            $j=bushisa_signup_query($pdo,'SELECT * FROM signup_mail_jobs WHERE mail_id=? FOR UPDATE',[$m['id']])->fetch(PDO::FETCH_ASSOC);
            $inflight=$j && in_array($j['state'],['inflight','uncertain'],true);
            if ($j && in_array($j['state'],['ready','claimed','inflight','uncertain','fixture_delivered'],true)) {
                bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET state=?,cancel_requested_at=COALESCE(cancel_requested_at,?),terminal_at=IF(?=1,terminal_at,COALESCE(terminal_at,?)),metadata_expires_at=IF(?=1,metadata_expires_at,COALESCE(metadata_expires_at,?)),reason_code=?,version=version+1 WHERE mail_id=?',
                    [$inflight?$j['state']:'cancelled',$at,$inflight?1:0,$at,$inflight?1:0,bushisa_signup_time($at,'+7 days'),$reason,$m['id']]);
            }
            $q=bushisa_signup_query($pdo,'UPDATE mail_outbox SET body=NULL,payload_erased_at=COALESCE(payload_erased_at,?),next_attempt_at=NULL,
                status=CASE WHEN status="pending" AND ?=0 THEN "failed" ELSE status END,metadata_json=JSON_OBJECT("result",?)
                WHERE id=? AND (body IS NOT NULL OR payload_erased_at IS NULL)',[$at,$inflight?1:0,$inflight?'cancellation_inflight':$reason,$m['id']]);
            $mails+=$q->rowCount();
        }
    }
    return ['challenges'=>$codes,'mail'=>$mails];
}

/** Current account row is already locked. Missing history is an explicit review
 * prerequisite, not a fabricated clock or automatic deletion permission. */
function bushisa_signup_expire_locked(PDO $pdo,array $u): array
{
    $id=(int)$u['id'];
    if (!in_array($u['account_status'],['pending_email','pending_onboarding'],true)) { return ['state'=>'not_pending']; }
    bushisa_signup_require_schema($pdo);
    $at=bushisa_signup_now($pdo);
    $c=bushisa_signup_query($pdo,'SELECT * FROM signup_lifecycle WHERE account_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        bushisa_signup_query($pdo,'INSERT INTO signup_lifecycle (account_id,anchor_status,observed_at) VALUES (?,"legacy_anchor_unverified",?)',[$id,$at]);
        $c=bushisa_signup_query($pdo,'SELECT * FROM signup_lifecycle WHERE account_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    }
    $due=bushisa_signup_stage_due($u,$c);
    if ($due===null || ($u['account_status']==='pending_onboarding' && $c['first_email_approved_at']===null)) {
        bushisa_signup_alert($pdo,$id,'SIGNUP_ANCHOR_REVIEW_REQUIRED'); return ['state'=>'anchor_unknown'];
    }
    if ($at<$due) { return ['state'=>'live','expires_at'=>$due]; }
    if (!bushisa_signup_user_live($u)) { return ['state'=>'restricted']; }
    $counts=bushisa_signup_cancel_material_locked($pdo,$id,'account_expired',true);
    bushisa_signup_fault('after_cancellation');
    $purgeDue=bushisa_signup_time($due,'+24 hours');
    bushisa_signup_query($pdo,'UPDATE users SET account_status="deleted",is_active=0,is_verified=0,session_version=session_version+1,deleted_at=COALESCE(deleted_at,?) WHERE id=?',[$at,$id]);
    bushisa_signup_query($pdo,'UPDATE signup_lifecycle SET expired_stage=?,expired_at=?,disabled_at=?,cleanup_due_at=?,version=version+1 WHERE account_id=? AND expired_at IS NULL',[$u['account_status'],$due,$at,$purgeDue,$id]);
    $receipt=hash('sha256','signup-expiry-v1|'.$id.'|'.$u['account_status'].'|'.$due);
    bushisa_signup_query($pdo,'INSERT INTO signup_expiry_obligations (receipt_id,account_id,stage,expires_at,denied_at,live_purge_due_at,account_version,challenges_invalidated,mail_copies_erased) VALUES (?,?,?,?,?,?,?,?,?)',
        [$receipt,$id,$u['account_status'],$due,$at,$purgeDue,(int)$u['session_version']+1,$counts['challenges'],$counts['mail']]);
    if (operational_table_exists($pdo,'signup_attempt_erasure_jobs')) {
        require_once __DIR__.'/signup_attempt_erasure.php';
        \Bushisa\AttemptErasure\enqueue($pdo,$id,$receipt);
    }
    if ($at>=$purgeDue) { bushisa_signup_alert($pdo,$id,'SIGNUP_LIVE_CLEANUP_OVERDUE',$purgeDue); }
    bushisa_signup_fault('before_commit');
    return ['state'=>'expired','receipt'=>$receipt,'expires_at'=>$due,'live_purge_due_at'=>$purgeDue];
}
function bushisa_signup_check_account(PDO $pdo,int $id): array
{
    return bushisa_signup_transaction($pdo,function()use($pdo,$id):array {
        $u=bushisa_signup_query($pdo,'SELECT * FROM users WHERE id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
        if (!$u) { return ['state'=>'absent']; }
        return bushisa_signup_expire_locked($pdo,$u);
    });
}
function bushisa_signup_expiry_message(string $state): string
{
    return $state==='anchor_unknown'?'This signup needs review before it can continue.'
        :'This signup has expired or is unavailable. A fresh signup after cleanup must complete email approval and adult onboarding again.';
}
function bushisa_cleanup_expired_signup_attempts(PDO $pdo,int $limit=25): array
{
    bushisa_signup_require_schema($pdo);
    if ($limit<1 || $limit>100) { throw new InvalidArgumentException('Invalid signup batch size'); }
    $at=bushisa_signup_now($pdo);
    // Unknown anchors are observed separately, never inferred from challenge use.
    $ids=bushisa_signup_query($pdo,'SELECT u.id FROM users u LEFT JOIN signup_lifecycle l ON l.account_id=u.id
        WHERE u.account_status IN ("pending_email","pending_onboarding") AND u.is_active=1 AND u.is_suspended=0 AND u.deleted_at IS NULL
        AND ((u.account_status="pending_email" AND l.unverified_expires_at<=?) OR (u.account_status="pending_onboarding" AND l.onboarding_expires_at<=?)
            OR (NOT EXISTS (SELECT 1 FROM signup_lifecycle_alerts a WHERE a.account_id=u.id AND a.reason_code="SIGNUP_ANCHOR_REVIEW_REQUIRED")
                AND (l.account_id IS NULL OR (u.account_status="pending_email" AND l.unverified_expires_at IS NULL)
                    OR (u.account_status="pending_onboarding" AND (l.onboarding_expires_at IS NULL OR l.first_email_approved_at IS NULL)))))
        ORDER BY l.account_id IS NULL,u.id LIMIT '.$limit,[$at,$at])->fetchAll(PDO::FETCH_COLUMN);
    $counts=['expired'=>0,'anchor_unknown'=>0,'skipped'=>0,'failed'=>0];
    foreach($ids as $id) {
        try { $r=bushisa_signup_check_account($pdo,(int)$id); $key=in_array($r['state'],['expired','anchor_unknown'],true)?$r['state']:'skipped'; $counts[$key]++; }
        catch(Throwable) { $counts['failed']++; }
    }
    foreach(bushisa_signup_query($pdo,'SELECT account_id,live_purge_due_at FROM signup_expiry_obligations WHERE cleanup_state="pending_consumer" AND live_purge_due_at<=? ORDER BY live_purge_due_at LIMIT '.$limit,[$at])->fetchAll(PDO::FETCH_ASSOC) as $o) {
        bushisa_signup_alert($pdo,(int)$o['account_id'],'SIGNUP_LIVE_CLEANUP_OVERDUE',$o['live_purge_due_at']);
    }
    return $counts;
}
function bushisa_signup_deadline(PDO $pdo,int $id): ?string
{
    if (!bushisa_signup_ready($pdo)) { return null; }
    $r=bushisa_signup_query($pdo,'SELECT u.account_status,u.is_verified,l.* FROM users u JOIN signup_lifecycle l ON l.account_id=u.id WHERE u.id=?',[$id])->fetch(PDO::FETCH_ASSOC);
    return !$r?null:bushisa_signup_stage_due($r,$r);
}
