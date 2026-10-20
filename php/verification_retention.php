<?php
declare(strict_types=1);
namespace Bushisa\VerificationRetention;
use PDO;
require_once __DIR__.'/signup_lifecycle.php';

final class Failure extends \RuntimeException
{ public function __construct(public readonly string $reason) { parent::__construct($reason); } }
function q(PDO $p,string $sql,array $args=[]): \PDOStatement { return \bushisa_signup_query($p,$sql,$args); }
function now(PDO $p): string { return \bushisa_signup_now($p); }
function time(string $at,string $offset='+0 seconds'): string { return \bushisa_signup_time($at,$offset); }
function tx(PDO $p,callable $f): mixed { return \bushisa_signup_transaction($p,$f); }
function ready(PDO $p): bool { return \operational_table_exists($p,'verification_retention'); }
function synthetic(): bool { return \bushisa_q11_synthetic_enabled() && getenv('BUSHISA_Q22_SYNTHETIC')==='true'; }
function worker_guard(): void
{ if (PHP_SAPI!=='cli' || getenv('BUSHISA_Q22_CLEANUP_ENABLED')!=='true') { throw new Failure('Q22_CONSUMER_DISABLED'); } }
function provider(): void
{ if (!synthetic()) { throw new Failure('RECOVERY_PROVIDER_REQUIRED'); } }
function fault(string $point): void
{
    if (!synthetic() || PHP_SAPI!=='cli') { return; }
    if (getenv('BUSHISA_Q22_CRASH')===$point) { exit(77); }
    if (getenv('BUSHISA_Q22_FAULT')===$point) { throw new Failure('SYNTHETIC_Q22_FAILURE'); }
}

/** Only actual issuance/queue transactions call these writers. No backfill. */
function register_code(PDO $p,int $id): void
{
    if (!ready($p)) { return; }
    if (!$p->inTransaction()) { throw new \LogicException('Retention identity needs issuance transaction'); }
    $c=q($p,'SELECT c.*,u.signup_attempt_key,u.created_at AS account_created FROM student_email_approval_codes c JOIN users u ON u.id=c.user_id WHERE c.id=?',[$id])->fetch(PDO::FETCH_ASSOC);
    if (!$c || $c['retention_key']!==null) { throw new Failure('NEW_CHALLENGE_REQUIRED'); }
    $key=bin2hex(random_bytes(32));
    q($p,'UPDATE student_email_approval_codes SET retention_key=? WHERE id=? AND retention_key IS NULL',[$key,$id]);
    q($p,'INSERT INTO verification_retention (record_key,kind,record_id,account_id,account_key,account_created_at,code_id,code_key,source_created_at,source_expires_at,next_check_at) VALUES (?,"challenge",?,?,?,?,?,?,?,?,?)',
        [$key,$id,$c['user_id'],$c['signup_attempt_key'],time($c['account_created']),$id,$key,time($c['created_at']),time($c['expires_at']),time($c['expires_at'])]);
}
function register_mail(PDO $p,int $id,int $code): void
{
    if (!ready($p)) { return; }
    if (!$p->inTransaction()) { throw new \LogicException('Retention identity needs queue transaction'); }
    $m=q($p,'SELECT m.*,c.retention_key AS code_key,c.user_id,u.signup_attempt_key,u.created_at AS account_created,j.queued_at,j.useful_until,j.state FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id JOIN users u ON u.id=c.user_id JOIN signup_mail_jobs j ON j.mail_id=m.id WHERE m.id=? AND c.id=?',[$id,$code])->fetch(PDO::FETCH_ASSOC);
    // An old producer's generation is not silently made trustworthy by a send.
    if (!$m || $m['code_key']===null) { return; }
    $key=bin2hex(random_bytes(32));
    q($p,'UPDATE mail_outbox SET retention_key=? WHERE id=? AND retention_key IS NULL',[$key,$id]);
    q($p,'INSERT INTO verification_retention (record_key,kind,record_id,account_id,account_key,account_created_at,code_id,code_key,source_created_at,source_expires_at,payload_due_at,next_check_at) VALUES (?,"mail",?,?,?,?,?,?,?,?,?,?)',
        [$key,$id,$m['user_id'],$m['signup_attempt_key'],time($m['account_created']),$code,$m['code_key'],time($m['created_at']),time($m['expires_at']),$m['useful_until'],$m['useful_until']]);
    if ($m['state']==='fixture_delivered') {
        // Development fixture readback is intentionally separate from transport.
        // Its original synthetic acceptance event still anchors retention.
        terminal_mail($p,$id,'fixture_accepted',now($p));
    }
}
function anchor(PDO $p,array $r,string $event,string $kind): void
{
    if ($r['terminal_at']!==null || $r['purged_at']!==null) { return; }
    $at=min(time($event),$r['source_expires_at']);
    $due=time($at,$r['kind']==='challenge'?'+24 hours':'+7 days');
    $payload=$r['kind']==='mail'?min((string)$r['payload_due_at'],$at):null;
    q($p,'UPDATE verification_retention SET terminal_at=?,terminal_kind=?,purge_due_at=?,payload_due_at=?,next_check_at=?,version=version+1 WHERE record_key=? AND terminal_at IS NULL',
        [$at,$kind,$due,$payload,$at,$r['record_key']]);
}
function terminal_code(PDO $p,int $id,string $kind,string $at): void
{
    if (!ready($p)) { return; }
    $r=q($p,'SELECT * FROM verification_retention WHERE kind="challenge" AND record_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        anchor($p,$r,$at,$kind);
        $erased=q($p,'SELECT purged_at FROM student_email_approval_codes WHERE id=? AND code_hash IS NULL',[$id])->fetchColumn();
        if ($erased) { q($p,'UPDATE verification_retention SET secret_erased_at=COALESCE(secret_erased_at,?) WHERE record_key=?',[time($erased),$r['record_key']]); }
    }
}
function terminal_mail(PDO $p,int $id,string $kind,string $at): void
{
    if (!ready($p)) { return; }
    $r=q($p,'SELECT * FROM verification_retention WHERE kind="mail" AND record_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        anchor($p,$r,$at,$kind);
        $erased=q($p,'SELECT payload_erased_at FROM mail_outbox WHERE id=? AND body IS NULL',[$id])->fetchColumn();
        if ($erased) { q($p,'UPDATE verification_retention SET secret_erased_at=COALESCE(secret_erased_at,?) WHERE record_key=?',[time($erased),$r['record_key']]); }
    }
}
function health(PDO $p): void
{
    $s=$p->query('CALL bushisa_verification_schema_health()');$h=$s->fetch(PDO::FETCH_ASSOC);while($s->nextRowset()){}$s->closeCursor();
    if (!$h || $h['contract']!=='verification-retention-v1' || (int)$h['unknown_columns'] || (int)$h['incoming_fks'] || (int)$h['unknown_triggers'] || (int)$h['identity_guards']!==2) { throw new Failure('SCHEMA_REVIEW_REQUIRED'); }
}
function alert(PDO $p,?string $key,string $reason,?string $due=null,string $identity=''): void
{
    q($p,'INSERT IGNORE INTO verification_cleanup_alerts (alert_id,record_key,reason_code,deadline_at,observed_at) VALUES (?,?,?,?,?)',
        [hash('sha256','q22|'.$key.'|'.$reason.'|'.$due.'|'.$identity),$key,$reason,$due,now($p)]);
}
/** Account -> exact code -> exact mail/job -> retention, matching Q11 order. */
function locked(PDO $p,string $key): array
{
    $hint=q($p,'SELECT * FROM verification_retention WHERE record_key=?',[$key])->fetch(PDO::FETCH_ASSOC);
    if (!$hint) { throw new Failure('RECORD_UNKNOWN'); }
    $u=q($p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$hint['account_id']])->fetch(PDO::FETCH_ASSOC);
    $c=q($p,'SELECT * FROM student_email_approval_codes WHERE id=? FOR UPDATE',[$hint['code_id']])->fetch(PDO::FETCH_ASSOC);
    $m=$j=null;
    if ($hint['kind']==='mail') {
        $m=q($p,'SELECT * FROM mail_outbox WHERE id=? FOR UPDATE',[$hint['record_id']])->fetch(PDO::FETCH_ASSOC);
        $j=q($p,'SELECT * FROM signup_mail_jobs WHERE mail_id=? FOR UPDATE',[$hint['record_id']])->fetch(PDO::FETCH_ASSOC);
    }
    $r=q($p,'SELECT * FROM verification_retention WHERE record_key=? FOR UPDATE',[$key])->fetch(PDO::FETCH_ASSOC);
    return compact('u','c','m','j','r');
}
function validate(array $a): void
{
    ['u'=>$u,'c'=>$c,'m'=>$m,'j'=>$j,'r'=>$r]=$a;
    if (!$u || !$r['account_key'] || !is_string($u['signup_attempt_key']) || !hash_equals($r['account_key'],$u['signup_attempt_key']) || $r['account_created_at']!==time($u['created_at'])) { throw new Failure('ACCOUNT_GENERATION_REQUIRED'); }
    if (!$c || !is_string($c['retention_key']) || !hash_equals($r['code_key'],$c['retention_key']) || (int)$c['user_id']!==(int)$r['account_id']) { throw new Failure('CHALLENGE_GENERATION_CHANGED'); }
    $s=$r['kind']==='challenge'?$c:$m;
    if (!$s || !is_string($s['retention_key']) || !hash_equals($r['record_key'],$s['retention_key']) || time($s['created_at'])!==$r['source_created_at'] || time($s['expires_at'])!==$r['source_expires_at']) { throw new Failure('RECORD_GENERATION_CHANGED'); }
    if ($r['kind']==='mail' && (!$j || (int)$m['code_bearing']!==1 || (int)$m['approval_code_id']!==(int)$r['code_id'] || (int)$j['code_id']!==(int)$r['code_id'] || (int)$j['account_id']!==(int)$r['account_id'] || time($j['queued_at'])!==$r['source_created_at'] || $j['useful_until']!==$r['source_expires_at'])) { throw new Failure('MAIL_BINDING_REQUIRED'); }
    if ($r['terminal_at']!==null && ($r['purge_due_at']!==time($r['terminal_at'],$r['kind']==='challenge'?'+24 hours':'+7 days') || $r['terminal_at']>$r['source_expires_at'])) { throw new Failure('RETENTION_CLOCK_CONFLICT'); }
}
function intent(PDO $p,array $r,string $action,string $due): string
{
    $id=hash('sha256','q22-v1|'.$r['record_key'].'|'.$action);
    q($p,'INSERT IGNORE INTO verification_recovery_obligations (instruction_id,record_key,action_name,effective_at) VALUES (?,?,?,?)',[$id,$r['record_key'],$action,$due]);
    return $id;
}
function receipt(PDO $p,array $r,string $action,string $due,string $id): void
{
    q($p,'INSERT IGNORE INTO verification_cleanup_receipts (receipt_id,record_key,action_name,deadline_at,completed_at) VALUES (?,?,?,?,?)',[$id,$r['record_key'],$action,$due,now($p)]);
    q($p,'UPDATE verification_recovery_obligations SET verified_purge_at=COALESCE(verified_purge_at,?) WHERE instruction_id=?',[now($p),$id]);
}
function perform(PDO $p,string $key): string
{
    $a=locked($p,$key);$r=$a['r'];$at=now($p);
    if ($r['purged_at']!==null) { return 'complete'; }
    validate($a);health($p);provider();
    if ($r['terminal_at']===null && $at>=$r['source_expires_at']) {
        $earlier=$r['kind']==='challenge'
            ?($a['c']['consumed_at']!==null || ($a['c']['terminal_at']!==null && time($a['c']['terminal_at'])<$r['source_expires_at']))
            :(($a['j']['terminal_at']!==null && $a['j']['terminal_at']<$r['source_expires_at']) || ($a['j']['accepted_at']!==null && $a['j']['accepted_at']<$r['source_expires_at']));
        if ($earlier) {
            // Missing earlier-event provenance must not extend retention to a
            // later expiry, nor keep an already-expired redeemable payload.
            // Repair secrets using the known original expiry only; leave the
            // earlier detail/metadata clock explicitly unresolved for review.
            $action=$r['kind']==='challenge'?'challenge_secret':'mail_payload';
            $id=intent($p,$r,$action,$r['source_expires_at']);
            if ($r['kind']==='challenge') {
                q($p,'UPDATE student_email_approval_codes SET code_hash=NULL,terminal_at=COALESCE(terminal_at,?),purged_at=COALESCE(purged_at,?) WHERE id=? AND retention_key=?',[$r['source_expires_at'],$at,$r['record_id'],$key]);
            } else {
                q($p,'UPDATE mail_outbox SET body=NULL,payload_erased_at=COALESCE(payload_erased_at,?),next_attempt_at=NULL,recipient_email="erased@example.invalid",subject="signup_verification",metadata_json=NULL WHERE id=? AND retention_key=?',[$at,$r['record_id'],$key]);
            }
            receipt($p,$r,$action,$r['source_expires_at'],$id);
            q($p,'UPDATE verification_retention SET secret_erased_at=COALESCE(secret_erased_at,?),error_code="TRUSTED_TERMINAL_EVENT_REQUIRED",next_check_at=? WHERE record_key=?',[$at,time($at,'+60 seconds'),$key]);
            alert($p,$key,'TRUSTED_TERMINAL_EVENT_REQUIRED',$r['source_expires_at']);return 'blocked';
        }
        // Dispatch expiry is not evidence that an in-flight carrier finished.
        anchor($p,$r,$r['source_expires_at'],$r['kind']==='mail'?'dispatch_expiry':'challenge_expiry');
        $r=q($p,'SELECT * FROM verification_retention WHERE record_key=?',[$key])->fetch(PDO::FETCH_ASSOC);
    }
    if ($r['terminal_at']===null) { return 'active'; }
    $due=$r['purge_due_at'];
    if ($at>$due) { alert($p,$key,'Q22_CLEANUP_OVERDUE',$due); }
    if ($r['kind']==='challenge') {
        $secretId=intent($p,$r,'challenge_secret',$r['terminal_at']);
        q($p,'UPDATE student_email_approval_codes SET code_hash=NULL,terminal_at=COALESCE(terminal_at,?),purged_at=COALESCE(purged_at,?) WHERE id=? AND retention_key=?',[$r['terminal_at'],$at,$r['record_id'],$key]);
        receipt($p,$r,'challenge_secret',$r['terminal_at'],$secretId);
        q($p,'UPDATE verification_retention SET secret_erased_at=COALESCE(secret_erased_at,?) WHERE record_key=?',[$at,$key]);
        if ($at<$due) {
            q($p,'UPDATE verification_retention SET next_check_at=?,error_code=NULL WHERE record_key=?',[$due,$key]);return 'secret_erased';
        }
        $id=intent($p,$r,'challenge_details',$due);fault('after_intent');
        q($p,'UPDATE student_email_approval_codes SET email="erased@example.invalid",attempts=0,code_version="erased_v1",consumed_at=NULL WHERE id=? AND retention_key=?',[$r['record_id'],$key]);
        fault('after_erasure');receipt($p,$r,'challenge_details',$due,$id);
    } else {
        if ($r['payload_due_at']===null || $r['payload_due_at']>$r['terminal_at']) { throw new Failure('RETENTION_CLOCK_CONFLICT'); }
        $id=intent($p,$r,'mail_payload',$r['payload_due_at']);
        q($p,'UPDATE mail_outbox SET body=NULL,payload_erased_at=COALESCE(payload_erased_at,?),next_attempt_at=NULL,recipient_email="erased@example.invalid",subject="signup_verification",metadata_json=NULL WHERE id=? AND retention_key=?',[$at,$r['record_id'],$key]);
        $j=$a['j'];
        if (in_array($j['state'],['ready','claimed'],true)) {
            q($p,'UPDATE signup_mail_jobs SET state="cancelled",cancel_requested_at=COALESCE(cancel_requested_at,?),terminal_at=COALESCE(terminal_at,?),metadata_expires_at=COALESCE(metadata_expires_at,?),reason_code="q22_dispatch_expired",version=version+1 WHERE mail_id=?',[$r['terminal_at'],$r['terminal_at'],$due,$r['record_id']]);
            q($p,'UPDATE mail_outbox SET status="failed" WHERE id=?',[$r['record_id']]);
        } elseif (in_array($j['state'],['inflight','uncertain'],true)) {
            q($p,'UPDATE signup_mail_jobs SET cancel_requested_at=COALESCE(cancel_requested_at,?),reason_code="q22_outcome_unresolved",version=version+1 WHERE mail_id=?',[$r['terminal_at'],$r['record_id']]);
            alert($p,$key,'CARRIER_OUTCOME_UNRESOLVED',$r['payload_due_at']);
        }
        receipt($p,$r,'mail_payload',$r['payload_due_at'],$id);
        q($p,'UPDATE verification_retention SET secret_erased_at=COALESCE(secret_erased_at,?) WHERE record_key=?',[$at,$key]);
        if (!in_array($j['state'],['ready','claimed','inflight','uncertain','accepted','accepted_after_cancel','cancelled','failed','fixture_delivered'],true)) {
            // A malformed/unknown state cannot prolong a redeemable payload.
            // It also cannot become fabricated proof of terminal delivery.
            alert($p,$key,'DELIVERY_STATE_REVIEW_REQUIRED',$r['payload_due_at']);
            q($p,'UPDATE verification_retention SET error_code="DELIVERY_STATE_REVIEW_REQUIRED",next_check_at=? WHERE record_key=?',[time($at,'+60 seconds'),$key]);
            return 'blocked';
        }
        if ($at<$due) { q($p,'UPDATE verification_retention SET next_check_at=?,error_code=NULL WHERE record_key=?',[$due,$key]);return 'payload_erased'; }
        // No attempt detail or original body is copied into this result receipt.
        $unresolved=in_array($j['state'],['inflight','uncertain'],true);
        $verifiable=$unresolved && $j['started_at']!==null && is_string($j['claim_hash']) && preg_match('/\A[a-f0-9]{64}\z/D',$j['claim_hash']);
        $id=intent($p,$r,'mail_metadata',$due);fault('after_intent');
        if ($unresolved) { q($p,'INSERT INTO verification_late_results (mail_id,record_key,claim_hash,result_state,observed_at) VALUES (?,?,?,?,?)',[$r['record_id'],$key,$verifiable?$j['claim_hash']:null,$verifiable?'awaiting_carrier':'review_required',$at]); }
        q($p,'DELETE FROM signup_mail_jobs WHERE mail_id=?',[$r['record_id']]);
        q($p,'DELETE FROM mail_outbox WHERE id=? AND retention_key=?',[$r['record_id'],$key]);
        fault('after_erasure');receipt($p,$r,'mail_metadata',$due,$id);
    }
    q($p,'UPDATE verification_retention SET purged_at=?,next_check_at=?,error_code=NULL,version=version+1 WHERE record_key=?',[$at,$at,$key]);
    fault('before_commit');return 'complete';
}
function process(PDO $p,string $key): string
{
    worker_guard();
    if ($p->inTransaction()) { throw new Failure('Q22_TRANSACTION_OWNERSHIP_REQUIRED'); }
    if (!preg_match('/\A[a-f0-9]{64}\z/D',$key)) { throw new Failure('RECORD_KEY_INVALID'); }
    \bushisa_signup_test_point($p,'q22_mixed');
    try {
        $result=tx($p,fn()=>perform($p,$key));fault('after_commit');return $result;
    } catch (\Throwable $e) {
        $reason=$e instanceof Failure?$e->reason:'Q22_TRANSACTION_FAILED';
        tx($p,function()use($p,$key,$reason):void {
            $r=q($p,'SELECT * FROM verification_retention WHERE record_key=? FOR UPDATE',[$key])->fetch(PDO::FETCH_ASSOC);
            if (!$r || $r['purged_at']!==null) { return; }
            q($p,'UPDATE verification_retention SET error_code=?,next_check_at=? WHERE record_key=?',[$reason,time(now($p),'+60 seconds'),$key]);
            alert($p,$key,$reason,$r['purge_due_at']??$r['source_expires_at']);
        });return 'blocked';
    }
}
function run(PDO $p,int $limit=25): array
{
    worker_guard();
    if ($limit<1 || $limit>50) { throw new Failure('BATCH_LIMIT_INVALID'); }
    if (!ready($p)) { throw new Failure('Q22_MIGRATION_REQUIRED'); }
    $at=now($p);$counts=['complete'=>0,'secret_erased'=>0,'payload_erased'=>0,'active'=>0,'blocked'=>0];
    $rs=q($p,'SELECT record_key FROM verification_retention WHERE purged_at IS NULL AND next_check_at<=? ORDER BY next_check_at,record_key LIMIT '.$limit,[$at])->fetchAll(PDO::FETCH_COLUMN);
    foreach($rs as $key) { $counts[process($p,$key)]++; }
    foreach(q($p,'SELECT record_key,purge_due_at,payload_due_at,secret_erased_at FROM verification_retention WHERE purged_at IS NULL AND (purge_due_at<? OR (payload_due_at<? AND secret_erased_at IS NULL)) ORDER BY next_check_at LIMIT '.$limit,[$at,$at])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        alert($p,$r['record_key'],'Q22_CLEANUP_OVERDUE',$r['secret_erased_at']===null?($r['payload_due_at']??$r['purge_due_at']):$r['purge_due_at']);
    }
    // Unknown legacy events are review obligations, never inferred approvals.
    foreach(['challenge'=>['student_email_approval_codes','id'],'mail'=>['mail_outbox','id']] as $kind=>[$table,$id]) {
        $scope=$kind==='mail'?' AND t.code_bearing=1':'';
        foreach(q($p,"SELECT t.$id FROM $table t LEFT JOIN verification_retention r ON r.kind=? AND r.record_id=t.$id LEFT JOIN verification_cleanup_alerts a ON a.alert_id=SHA2(CONCAT('q22||LEGACY_ANCHOR_REQUIRED||',?,':',t.$id),256) WHERE r.record_key IS NULL AND a.alert_id IS NULL$scope ORDER BY t.$id LIMIT ".$limit,[$kind,$kind])->fetchAll(PDO::FETCH_COLUMN) as $v) { alert($p,null,'LEGACY_ANCHOR_REQUIRED',null,$kind.':'.$v); }
    }
    return $counts;
}
/** Receipt-only late acknowledgments. Never recreate an outbox/challenge/grant. */
function late_result(PDO $p,int $mail,string $token,?\Throwable $error): ?string
{
    if (!ready($p)) { return null; }
    // This identity predates transport. Under REPEATABLE READ a transaction
    // waiting on users may not see the newly committed late-result receipt in
    // its old snapshot; obtain that receipt with a current locking read below.
    $hint=q($p,'SELECT account_id FROM verification_retention WHERE kind="mail" AND record_id=?',[$mail])->fetchColumn();
    if (!$hint) { return null; }
    q($p,'SELECT id FROM users WHERE id=? FOR UPDATE',[$hint]);
    $l=q($p,'SELECT * FROM verification_late_results WHERE mail_id=? FOR UPDATE',[$mail])->fetch(PDO::FETCH_ASSOC);
    if (!$l || $l['claim_hash']===null || !hash_equals($l['claim_hash'],hash('sha256',$token)) || $l['result_state']!=='awaiting_carrier') { return 'discarded'; }
    $unknown=$error!==null && (!$error instanceof \BushisaMailTransportException || $error->uncertain);
    $state=$error===null?'accepted_after_purge':($unknown?'unresolved':'not_accepted');
    q($p,'UPDATE verification_late_results SET result_state=?,claim_hash=NULL,observed_at=?,version=version+1 WHERE mail_id=?',[$state,now($p),$mail]);
    return $error===null?'sent':'failed';
}
function status(PDO $p,string $key): array
{
    $r=q($p,'SELECT kind,terminal_kind,terminal_at,payload_due_at,purge_due_at,secret_erased_at,purged_at,error_code FROM verification_retention WHERE record_key=?',[$key])->fetch(PDO::FETCH_ASSOC);
    return $r?:[];
}
function recovery_instructions(PDO $p,int $limit=50): array
{
    return q($p,'SELECT instruction_id,record_key,action_name,effective_at,verified_purge_at,publication_state,copy_inventory,retain_until FROM verification_recovery_obligations ORDER BY instruction_id LIMIT '.max(1,min(100,$limit)))->fetchAll(PDO::FETCH_ASSOC);
}
