<?php
declare(strict_types=1);
require_once __DIR__.'/signup_lifecycle.php';

/** Producer is in the account/challenge transaction. Useful delivery time is
 * immutable and never substituted for the challenge or account expiry. */
function bushisa_signup_register_mail_job(PDO $pdo,int $mail,int $code,string $state='ready'): void
{
    if (!$pdo->inTransaction()) { throw new LogicException('Mail job requires the challenge transaction'); }
    $r=bushisa_signup_query($pdo,'SELECT m.created_at,m.expires_at,c.user_id,c.expires_at AS code_expires,l.unverified_expires_at
        FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id
        JOIN signup_lifecycle l ON l.account_id=c.user_id WHERE m.id=? AND c.id=?',[$mail,$code])->fetch(PDO::FETCH_ASSOC);
    if (!$r || $r['unverified_expires_at']===null) { throw new RuntimeException('Tracked signup and challenge required for mail'); }
    $at=bushisa_signup_now($pdo);
    $queued=bushisa_signup_time($r['created_at']);
    $until=min(bushisa_signup_time($queued,'+5 minutes'),bushisa_signup_time($r['code_expires'],'-60 seconds'),$r['unverified_expires_at'],bushisa_signup_time($r['expires_at']));
    bushisa_signup_query($pdo,'INSERT INTO signup_mail_jobs (mail_id,account_id,code_id,queued_at,useful_until,state) VALUES (?,?,?,?,?,?)',[$mail,$r['user_id'],$code,$queued,$until,$state]);
    bushisa_signup_query($pdo,'UPDATE mail_outbox SET expires_at=? WHERE id=?',[$until,$mail]);
    require_once __DIR__.'/verification_retention.php';
    \Bushisa\VerificationRetention\register_mail($pdo,$mail,$code);
}
function bushisa_queue_q11_fake_email(PDO $pdo,int $codeId,string $email,string $code): void
{
    bushisa_q11_synthetic_guard();
    $now=substr(bushisa_signup_now($pdo),0,19); $c=bushisa_signup_query($pdo,'SELECT expires_at FROM student_email_approval_codes WHERE id=?',[$codeId])->fetchColumn();
    $payload=['recipient'=>strtolower($email),'from_address'=>'synthetic@example.invalid','subject'=>'Synthetic Bushisa approval','body'=>'Your Bushisa approval code is '.$code.'.'];
    bushisa_signup_query($pdo,'INSERT INTO mail_outbox (recipient_email,subject,body,provider,status,created_at,expires_at,code_bearing,approval_code_id,next_attempt_at,metadata_json)
        VALUES (?,?,?,"fake","pending",?,?,1,?,?,JSON_OBJECT("synthetic_development_only",true))',
        [strtolower($email),$payload['subject'],bushisa_encrypt_mail_payload($payload,$codeId,strtolower($email)),$now,$c,$codeId,$now]);
    bushisa_signup_register_mail_job($pdo,(int)$pdo->lastInsertId(),$codeId);
}
/** Caller holds the account row. This does not invalidate other generations. */
function bushisa_signup_mail_terminal(PDO $pdo,array $m,string $state,string $reason,bool $accepted=false): void
{
    $at=bushisa_signup_now($pdo);
    bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET state=?,terminal_at=COALESCE(terminal_at,?),accepted_at=IF(?=1,COALESCE(accepted_at,?),accepted_at),metadata_expires_at=COALESCE(metadata_expires_at,?),reason_code=?,version=version+1 WHERE mail_id=?',
        [$state,$at,$accepted?1:0,$at,bushisa_signup_time($at,'+7 days'),$reason,$m['id']]);
    bushisa_signup_query($pdo,'UPDATE mail_outbox SET status=?,body=NULL,payload_erased_at=COALESCE(payload_erased_at,?),sent_at=IF(?=1,COALESCE(sent_at,?),sent_at),next_attempt_at=NULL,metadata_json=JSON_OBJECT("result",?) WHERE id=?',
        [$accepted?'sent':'failed',$at,$accepted?1:0,$at,$reason,$m['id']]);
    require_once __DIR__.'/verification_retention.php';
    \Bushisa\VerificationRetention\terminal_mail($pdo,(int)$m['id'],$state,$at);
}
function bushisa_signup_mail_rows_locked(PDO $pdo,int $mail): ?array
{
    $hint=bushisa_signup_query($pdo,'SELECT c.user_id FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id WHERE m.id=?',[$mail])->fetchColumn();
    if (!$hint) { return null; }
    // Mail workers do not need password hashes or unrelated profile identity.
    $u=bushisa_signup_query($pdo,'SELECT id,email,created_at,is_verified,is_suspended,is_active,account_status,deleted_at,session_version,signup_attempt_key FROM users WHERE id=? FOR UPDATE',[$hint])->fetch(PDO::FETCH_ASSOC);
    if (!$u) { return null; }
    $expiry=bushisa_signup_expire_locked($pdo,$u);
    $c=bushisa_signup_query($pdo,'SELECT c.* FROM student_email_approval_codes c JOIN mail_outbox m ON m.approval_code_id=c.id WHERE m.id=? FOR UPDATE',[$mail])->fetch(PDO::FETCH_ASSOC);
    $m=bushisa_signup_query($pdo,'SELECT * FROM mail_outbox WHERE id=? FOR UPDATE',[$mail])->fetch(PDO::FETCH_ASSOC);
    $j=bushisa_signup_query($pdo,'SELECT * FROM signup_mail_jobs WHERE mail_id=? FOR UPDATE',[$mail])->fetch(PDO::FETCH_ASSOC);
    if (!$c || !$m || !$j || (int)$c['user_id']!==(int)$u['id'] || (int)$j['account_id']!==(int)$u['id'] || (int)$j['code_id']!==(int)$c['id']) { return null; }
    $at=bushisa_signup_now($pdo);
    $eligible=$expiry['state']==='live' && bushisa_signup_user_live($u) && $u['account_status']==='pending_email' && (int)$u['is_verified']===0
        && bushisa_signup_time($c['expires_at'])>$at && $c['consumed_at']===null && $c['terminal_at']===null && $c['code_hash']!==null
        && (int)$c['attempts']<bushisa_signup_code_max_attempts() && $j['useful_until']>$at && $m['expires_at']!==null && bushisa_signup_time($m['expires_at'])>$at
        && $m['status']==='pending' && $m['body']!==null && $m['payload_erased_at']===null && $j['cancel_requested_at']===null
        && strtolower($c['email'])===$m['recipient_email'] && strtolower($u['email'])===$m['recipient_email'];
    return compact('u','c','m','j','eligible','at');
}
function bushisa_signup_claim_mail(PDO $pdo,int $id,string $token): ?array
{
    return bushisa_signup_transaction($pdo,function()use($pdo,$id,$token):?array {
        $r=bushisa_signup_mail_rows_locked($pdo,$id);
        if (!$r) {
            // Untracked legacy jobs are not promoted into a verified delivery
            // generation. Their code-bearing payload is not useful to dispatch.
            $m=bushisa_signup_query($pdo,'SELECT * FROM mail_outbox WHERE id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
            if ($m && $m['status']==='pending') { bushisa_finish_mail($pdo,$id,'failed','untracked_delivery'); }
            return null;
        }
        ['m'=>$m,'j'=>$j,'at'=>$at]=$r;
        if (in_array($j['state'],['accepted','accepted_after_cancel','failed','cancelled','uncertain','fixture_delivered'],true)) { return null; }
        if ($j['state']==='inflight') {
            if ($j['claim_until']!==null && $at>=$j['claim_until']) { bushisa_signup_mail_terminal($pdo,$m,'uncertain','dispatch_outcome_unknown'); }
            elseif (!$r['eligible']) {
                bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET cancel_requested_at=COALESCE(cancel_requested_at,?),reason_code="expired_inflight" WHERE mail_id=?',[$at,$id]);
                bushisa_signup_query($pdo,'UPDATE mail_outbox SET body=NULL,payload_erased_at=COALESCE(payload_erased_at,?),next_attempt_at=NULL WHERE id=?',[$at,$id]);
            }
            return null;
        }
        if ($j['state']==='claimed' && $j['claim_until']>$at) { return null; }
        if (!$r['eligible']) { bushisa_signup_mail_terminal($pdo,$m,'cancelled','obsolete_challenge'); return null; }
        if ((int)$m['attempts']>=5) { bushisa_signup_mail_terminal($pdo,$m,'failed','retry_limit'); return null; }
        bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET state="claimed",claim_hash=?,claimed_at=?,claim_until=?,version=version+1 WHERE mail_id=?',
            [hash('sha256',$token),$at,bushisa_signup_time($at,'+60 seconds'),$id]);
        bushisa_signup_query($pdo,'UPDATE mail_outbox SET attempts=attempts+1 WHERE id=?',[$id]);
        return ['mail_id'=>$id];
    });
}
function bushisa_signup_begin_delivery(PDO $pdo,int $id,string $token): ?array
{
    return bushisa_signup_transaction($pdo,function()use($pdo,$id,$token):?array {
        $r=bushisa_signup_mail_rows_locked($pdo,$id);
        if (!$r) { return null; }
        ['m'=>$m,'j'=>$j,'c'=>$c,'at'=>$at]=$r;
        if ($j['state']!=='claimed' || !hash_equals((string)$j['claim_hash'],hash('sha256',$token))) { return null; }
        if (!$r['eligible'] || $j['claim_until']<=$at) { bushisa_signup_mail_terminal($pdo,$m,'cancelled','obsolete_claim'); return null; }
        try { $payload=bushisa_decrypt_mail_payload($m['body'],(int)$c['id'],$m['recipient_email']); }
        catch(Throwable) { bushisa_signup_mail_terminal($pdo,$m,'failed','payload_authentication_failed'); return null; }
        if (($payload['recipient']??null)!==$m['recipient_email']) { bushisa_signup_mail_terminal($pdo,$m,'failed','payload_recipient_mismatch'); return null; }
        bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET state="inflight",started_at=?,version=version+1 WHERE mail_id=?',[$at,$id]);
        return ['payload'=>$payload,'code_id'=>(int)$c['id']];
    });
}
/** A successful carrier response records acceptance only, even after cancel.
 * Unknown outcomes/crashed in-flight jobs never automatically resend. */
function bushisa_signup_finish_delivery(PDO $pdo,int $id,string $token,?Throwable $error): string
{
    return bushisa_signup_transaction($pdo,function()use($pdo,$id,$token,$error):string {
        $r=bushisa_signup_mail_rows_locked($pdo,$id);
        if (!$r) {
            require_once __DIR__.'/verification_retention.php';
            $late=\Bushisa\VerificationRetention\late_result($pdo,$id,$token,$error);
            if ($late!==null) { return $late; }
            throw new RuntimeException('Delivery receipt scope unavailable');
        }
        ['m'=>$m,'j'=>$j,'at'=>$at]=$r;
        if (!hash_equals((string)$j['claim_hash'],hash('sha256',$token)) || !in_array($j['state'],['inflight','uncertain'],true)) { return 'discarded'; }
        if ($error===null) {
            $cancelled=$j['cancel_requested_at']!==null || !$r['eligible'];
            bushisa_signup_mail_terminal($pdo,$m,$cancelled?'accepted_after_cancel':'accepted',$cancelled?'smtp_accepted_after_cancel':'smtp_accepted',true);
            return 'sent';
        }
        $uncertain=!$error instanceof BushisaMailTransportException || $error->uncertain;
        if ($uncertain) { bushisa_signup_mail_terminal($pdo,$m,'uncertain','dispatch_outcome_unknown'); return 'failed'; }
        $delay=min(300,30*(2**min(4,(int)$m['attempts']-1)));
        if ($error->retryable && (int)$m['attempts']<5 && $r['eligible'] && bushisa_signup_time($at,'+'.$delay.' seconds')<$j['useful_until']) {
            bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET state="ready",claim_hash=NULL,claimed_at=NULL,claim_until=NULL,started_at=NULL,reason_code="transport_retry",version=version+1 WHERE mail_id=?',[$id]);
            bushisa_signup_query($pdo,'UPDATE mail_outbox SET next_attempt_at=?,metadata_json=JSON_OBJECT("result","transport_retry") WHERE id=?',[bushisa_signup_time($at,'+'.$delay.' seconds'),$id]);
            return 'retried';
        }
        bushisa_signup_mail_terminal($pdo,$m,'failed','transport_failed'); return 'failed';
    });
}
function bushisa_dispatch_signup_jobs(PDO $pdo,int $limit,?callable $transport): array
{
    if ($pdo->inTransaction()) { throw new RuntimeException('Dispatch must run outside a transaction'); }
    bushisa_signup_require_schema($pdo);
    if ($transport===null && (getenv('MAIL_TRANSPORT')==='fake' || bushisa_mail_provider()!=='smtp')) { throw new RuntimeException('A fake carrier is required for synthetic dispatch'); }
    $counts=['sent'=>0,'retried'=>0,'discarded'=>0,'failed'=>0];
    $lockName='bushisa-mail-'.substr(hash('sha256',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),0,32);
    if ((int)bushisa_signup_query($pdo,'SELECT GET_LOCK(?,0)',[$lockName])->fetchColumn()!==1) { return $counts; }
    try {
        $now=bushisa_signup_now($pdo);
        $provider=bushisa_q11_synthetic_enabled()?'fake':'smtp';
        $ids=bushisa_signup_query($pdo,'SELECT m.id FROM mail_outbox m LEFT JOIN signup_mail_jobs j ON j.mail_id=m.id
            WHERE m.provider=? AND (m.status="pending" OR j.state IN ("claimed","inflight"))
            AND (m.next_attempt_at IS NULL OR m.next_attempt_at<=? OR m.expires_at<=? OR j.claim_until<=?) ORDER BY m.id LIMIT '.max(1,min(30,$limit)),[$provider,$now,$now,$now])->fetchAll(PDO::FETCH_COLUMN);
        foreach($ids as $id) {
            $token=bin2hex(random_bytes(32));
            if (!bushisa_signup_claim_mail($pdo,(int)$id,$token)) { $counts['discarded']++; continue; }
            bushisa_signup_test_point($pdo,'q11_claimed');
            $delivery=bushisa_signup_begin_delivery($pdo,(int)$id,$token);
            if (!$delivery) {
                $state=bushisa_signup_query($pdo,'SELECT state FROM signup_mail_jobs WHERE mail_id=?',[$id])->fetchColumn();
                $counts[$state==='failed'?'failed':'discarded']++; continue;
            }
            $error=null;
            try { ($transport??'bushisa_send_smtp_payload')($delivery['payload'],(int)$id,$delivery['code_id']); }
            catch(Throwable $e) { $error=$e; }
            unset($delivery); // No payload in receipts/diagnostics.
            $result=bushisa_signup_finish_delivery($pdo,(int)$id,$token,$error);
            $counts[$result]++;
        }
    } finally { bushisa_signup_query($pdo,'SELECT RELEASE_LOCK(?)',[$lockName]); }
    return $counts;
}

/** Cleanup never dispatches or reclaims work. Account-first locks serialize with
 * issuance/verification; in-flight cancellation records an uncertain boundary. */
function bushisa_signup_cleanup_mail(PDO $pdo,int $limit=100): int
{
    bushisa_signup_require_schema($pdo);
    $now=bushisa_signup_now($pdo); $count=0;
    $ids=bushisa_signup_query($pdo,'SELECT m.id FROM mail_outbox m LEFT JOIN signup_mail_jobs j ON j.mail_id=m.id
        WHERE m.code_bearing=1 AND (m.body IS NOT NULL OR (j.state="inflight" AND j.claim_until<=?))
        AND (m.expires_at IS NULL OR m.expires_at<=? OR j.claim_until<=?) ORDER BY m.id LIMIT '.max(1,min(100,$limit)),[$now,$now,$now])->fetchAll(PDO::FETCH_COLUMN);
    foreach($ids as $id) {
        $count+=bushisa_signup_transaction($pdo,function()use($pdo,$id):int {
            $r=bushisa_signup_mail_rows_locked($pdo,(int)$id);
            if (!$r) {
                $m=bushisa_signup_query($pdo,'SELECT * FROM mail_outbox WHERE id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
                if ($m && $m['body']!==null && ($m['expires_at']===null || bushisa_signup_time($m['expires_at'])<=bushisa_signup_now($pdo))) {
                    bushisa_finish_mail($pdo,(int)$id,$m['status']==='sent'?'sent':'failed','untracked_expired_payload'); return 1;
                }
                return 0;
            }
            ['m'=>$m,'j'=>$j,'at'=>$at]=$r;
            if ($j['state']==='inflight') {
                if ($j['claim_until']<=$at) { bushisa_signup_mail_terminal($pdo,$m,'uncertain','dispatch_outcome_unknown'); return 1; }
                if (!$r['eligible']) {
                    bushisa_signup_query($pdo,'UPDATE signup_mail_jobs SET cancel_requested_at=COALESCE(cancel_requested_at,?),reason_code="expired_inflight" WHERE mail_id=?',[$at,$id]);
                    bushisa_signup_query($pdo,'UPDATE mail_outbox SET body=NULL,payload_erased_at=COALESCE(payload_erased_at,?),next_attempt_at=NULL WHERE id=?',[$at,$id]); return 1;
                }
                return 0;
            }
            if (!$r['eligible'] && $m['body']!==null) {
                if ($j['state']==='fixture_delivered') {
                    // The fake fixture was already accepted. Erase without
                    // rewriting that observation as a failed delivery.
                    bushisa_signup_mail_terminal($pdo,$m,'fixture_delivered','fixture_payload_expired',true);
                } else { bushisa_signup_mail_terminal($pdo,$m,'cancelled','obsolete_challenge'); }
                return 1;
            }
            return 0;
        });
    }
    return $count;
}
