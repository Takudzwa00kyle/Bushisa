<?php
declare(strict_types=1);

namespace Bushisa\Evidence;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/report_intake.php';

final class Failure extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $status = 409)
    { parent::__construct($reason); }
}

function query(PDO $db, string $sql, array $values = []): \PDOStatement
{ $q=$db->prepare($sql); $q->execute($values); return $q; }
function add(string $time, string $interval): string
{ return (new DateTimeImmutable($time,new DateTimeZone('UTC')))->modify($interval)->format('Y-m-d H:i:s.u'); }
function identity(string ...$parts): string { return hash('sha256',implode("\0",$parts)); }

/** No configurable PHP include or client-selected provider. There is not yet a
 * qualified operational MFA/notice integration. Only the closed synthetic
 * adapter is wired; setting a session boolean never establishes authority. */
function synthetic_enabled(): bool
{
    return getenv('APP_ENV')==='development' && getenv('APP_DEBUG')==='false'
        && getenv('BUSHISA_ALLOW_RUNTIME_DDL')==='false' && getenv('BUSHISA_TEST_DATABASE')==='true'
        && getenv('BUSHISA_L03A_SYNTHETIC')==='true'
        && in_array(getenv('DB_HOST'),['127.0.0.1','localhost','::1'],true)
        && preg_match('/\Abushisa_test_l03a_[a-z0-9_]+\z/D',(string)getenv('DB_NAME'))===1
        && ctype_digit((string)getenv('DB_PORT')) && (int)getenv('DB_PORT')>0
        && (int)getenv('DB_PORT')<=65535 && is_string(getenv('DB_USER')) && getenv('DB_USER')!==''
        && getenv('MAIL_TRANSPORT')==='fake' && getenv('MAIL_PROVIDER')==='fake';
}
function synthetic_guard(): void
{ if (!synthetic_enabled()) { throw new Failure('SYNTHETIC_DESTINATION_REFUSED',503); } }

/** Test clock is fixture-owned SQL state, not an API/CLI input. Production
 * always uses the database's UTC clock. No trigger API accepts a timestamp. */
function now(PDO $db): string
{
    if (synthetic_enabled()) {
        $v=query($db,'SELECT clock_at FROM l03a_fixture_clock WHERE singleton=1')->fetchColumn();
        if (!is_string($v)) { throw new Failure('FIXTURE_CLOCK_MISSING',503); }
        return $v;
    }
    return \bushisa_report_now($db);
}

final class Context
{
    private function __construct(public readonly int $actor, private readonly int $version, private readonly string $proof) {}
    public static function fromServerSession(array $session): self
    {
        // Must be called with server-loaded session state, never request JSON.
        if (!synthetic_enabled()) { throw new Failure('AUTHORITY_INTEGRATION_UNAVAILABLE',503); }
        if (!is_int($session['user_id']??null) || !is_int($session['session_version']??null)
            || !is_string($session['evidence_authority_assertion']??null)
            || !preg_match('/\A[a-f0-9]{64}\z/D',$session['evidence_authority_assertion'])) {
            throw new Failure('TRUSTED_AUTHORITY_REQUIRED',403);
        }
        return new self($session['user_id'],$session['session_version'],identity($session['evidence_authority_assertion']));
    }
    public function authorize(PDO $db, int $case, string $capability): array
    {
        synthetic_guard();
        return verify_proof($db,$this->proof,$this->actor,$this->version,$case,$capability);
    }
    public function proofHash(): string { return $this->proof; }
}

function verify_proof(PDO $db, string $proof, int $actor, ?int $version, int $case, string $capability): array
{
    $r=query($db,'SELECT p.*,u.role,u.session_version,u.is_active,u.is_suspended,u.account_status
        FROM l03a_fixture_authority p JOIN users u ON u.id=p.actor_id
        WHERE p.proof_hash=? AND p.actor_id=? AND p.case_id=? FOR SHARE',[$proof,$actor,$case])->fetch(PDO::FETCH_ASSOC);
    $at=now($db);
    if (!$r || $r['revoked_at']!==null || $r['expires_at']<=$at
        || (int)$r['session_version']!==(int)$r['bound_version']
        || ($version!==null && (int)$r['session_version']!==$version)
        || !(int)$r['is_active'] || (int)$r['is_suspended'] || $r['account_status']!=='active'
        || !in_array($r['role'],['moderator','admin'],true)
        || !in_array($capability,json_decode($r['capabilities_json'],true,16,JSON_THROW_ON_ERROR),true)) {
        throw new Failure('CURRENT_AUTHORITY_REQUIRED',403);
    }
    if (!query($db,'SELECT 1 FROM moderation_case_assignments WHERE case_id=? AND assigned_user_id=? AND revoked_at IS NULL FOR SHARE',[$case,$actor])->fetchColumn()) {
        throw new Failure('CURRENT_ASSIGNMENT_REQUIRED',403);
    }
    return $r;
}

/** Same lock/order as report/pair mutations. Retry only engine transaction
 * failures, never arbitrary exceptions. Each attempt fully revalidates state. */
function transaction(PDO $db, callable $f): mixed
{
    for ($i=0;;$i++) {
        try { return \bushisa_report_transaction($db,$f); }
        catch (PDOException $e) {
            if (!in_array((int)($e->errorInfo[1]??0),[1205,1213],true) || $i>=2) { throw $e; }
        }
    }
}
function fault(string $point): void
{
    if (PHP_SAPI==='cli' && synthetic_enabled() && getenv('BUSHISA_L03A_FAULT')===$point) {
        throw new Failure('SYNTHETIC_FAILURE_'.$point,503);
    }
}
function alert(PDO $db, int $case, ?string $item, string $reason, ?string $deadline=null): void
{
    query($db,'INSERT IGNORE INTO evidence_lifecycle_alerts
        (alert_id,item_key,case_id,reason_code,deadline_at,observed_at) VALUES (?,?,?,?,?,?)',
        [identity('alert',(string)$case,$item??'', $reason,$deadline??''),$item,$case,$reason,$deadline,now($db)]);
}
function audit(PDO $db,int $case,?int $actor,?string $item,string $action,string $code,string $event): void
{
    $at=now($db);
    query($db,'INSERT INTO evidence_lifecycle_events
        (event_id,case_id,actor_id,item_key,action_name,outcome_code,occurred_at,audit_expires_at) VALUES (?,?,?,?,?,?,?,?)',
        [$event,$case,$actor,$item,$action,$code,$at,add($at,'+365 days')]);
}

/** Called inside intake's transaction. No retrospective certification. Existing
 * final_resolution_at is a review prerequisite, not a trusted trigger. */
function register_case(PDO $db,int $case): bool
{
    if (!\operational_table_exists($db,'evidence_case_clocks')) { return false; }
    $r=query($db,'SELECT intake_contract_version,final_resolution_at FROM moderation_cases WHERE id=? FOR UPDATE',[$case])->fetch(PDO::FETCH_ASSOC);
    if (!$r || (int)$r['intake_contract_version']!==1) { return false; }
    $reason=$r['final_resolution_at']===null?'MISSING_EFFECTIVE_NOTICE':'UNTRUSTED_HISTORICAL_FINAL';
    query($db,'INSERT IGNORE INTO evidence_case_clocks (case_id,domain,state,unresolved_code,observed_at) VALUES (?,?,"missing_notice",?,?)',
        [$case,synthetic_enabled()?'synthetic':'new_intake',$reason,now($db)]);
    $c=query($db,'SELECT * FROM evidence_case_clocks WHERE case_id=? FOR UPDATE',[$case])->fetch(PDO::FETCH_ASSOC);
    if ($c['notice_at']===null && $r['final_resolution_at']!==null && $c['final_at']===null) {
        query($db,'UPDATE evidence_case_clocks SET unresolved_code="UNTRUSTED_HISTORICAL_FINAL" WHERE case_id=?',[$case]);
        $c['unresolved_code']='UNTRUSTED_HISTORICAL_FINAL';
    }
    if ($c['state']==='missing_notice') { alert($db,$case,null,$c['unresolved_code']); }
    foreach (['evidence'=>'SELECT id,evidence_excerpt AS body,evidence_hash AS fingerprint,purged_at FROM moderation_evidence WHERE case_id=?',
        'submission'=>'SELECT s.id,s.explanation AS body,s.information_hash AS fingerprint,s.purged_at FROM report_submissions s JOIN report_incidents i ON i.id=s.incident_id WHERE i.case_id=?'] as $kind=>$sql) {
        foreach (query($db,$sql,[$case])->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if ($r['purged_at']!==null) { continue; }
            $key=identity('text-item-v1',$kind,(string)$r['id']);
            query($db,'INSERT IGNORE INTO evidence_lifecycle_items
                (item_key,case_id,item_kind,source_id,source_hash,ordinary_expires_at,purge_due_at,state,observed_at)
                VALUES (?,?,?,?,?,?,?,?,?)',[$key,$case,$kind,$r['id'],source_hash($r),$c['content_expires_at'],$c['content_expires_at'],
                    $c['content_expires_at']===null?'awaiting_anchor':'pending',now($db)]);
        }
    }
    if ($c['final_at']!==null) { propagate_deadline($db,$c); }
    return true;
}
function source_hash(array $r): string { return identity((string)($r['body']??''),(string)($r['fingerprint']??'')); }
function source(PDO $db,array $item): array|false
{
    return query($db,$item['item_kind']==='evidence'
        ?'SELECT evidence_excerpt AS body,evidence_hash AS fingerprint,purged_at FROM moderation_evidence WHERE id=? AND case_id=? FOR UPDATE'
        :'SELECT s.explanation AS body,s.information_hash AS fingerprint,s.purged_at FROM report_submissions s JOIN report_incidents i ON i.id=s.incident_id WHERE s.id=? AND i.case_id=? FOR UPDATE',
        [$item['source_id'],$item['case_id']])->fetch(PDO::FETCH_ASSOC);
}
function propagate_deadline(PDO $db,array $c): void
{
    $due=$c['content_expires_at']; $case=(int)$c['case_id'];
    // NULL -> immutable deadline only; no routine job/edit/hold can move it.
    query($db,'UPDATE evidence_lifecycle_items SET ordinary_expires_at=?,purge_due_at=?,state="pending",version=version+1
        WHERE case_id=? AND ordinary_expires_at IS NULL AND purged_at IS NULL',[$due,$due,$case]);
    query($db,'UPDATE report_incidents SET expires_at=COALESCE(expires_at,?) WHERE case_id=?',[$due,$case]);
    query($db,'UPDATE report_submissions s JOIN report_incidents i ON i.id=s.incident_id SET s.expires_at=COALESCE(s.expires_at,?) WHERE i.case_id=?',[$due,$case]);
    query($db,'UPDATE moderation_evidence SET purge_due_at=COALESCE(purge_due_at,?) WHERE case_id=?',[$due,$case]);
    query($db,'UPDATE moderation_cases SET evidence_purge_due_at=COALESCE(evidence_purge_due_at,?) WHERE id=?',[$due,$case]);
}
function finalize_locked(PDO $db,int $case): array
{
    $c=query($db,'SELECT * FROM evidence_case_clocks WHERE case_id=? FOR UPDATE',[$case])->fetch(PDO::FETCH_ASSOC);
    if (!$c) { throw new Failure('NEW_INTAKE_CASE_REQUIRED',404); }
    if ($c['final_at']!==null) { return $c; }
    $at=now($db);
    if ($c['notice_at']===null || $c['appeal_until']===null) {
        if ($c['notice_at']!==null || $c['appeal_until']!==null) {
            query($db,'UPDATE evidence_case_clocks SET state="resolution_review",unresolved_code="MISSING_APPEAL_BOUNDARY" WHERE case_id=?',[$case]);
            alert($db,$case,null,'MISSING_APPEAL_BOUNDARY');
        }
        return $c;
    }
    if ($c['notice_event']===null || $c['decision_reviewer_id']===null || !in_array($c['resolution_class'],['ordinary','unrelated','accidental'],true)
        || $c['appeal_until']!==add($c['notice_at'],'+14 days')
        || ($c['appeal_received_at']!==null && ($c['appeal_received_at']<$c['notice_at'] || $c['appeal_received_at']>=$c['appeal_until']))
        || ($c['appeal_disposed_at']!==null && ($c['appeal_received_at']===null || $c['appeal_disposed_at']<$c['appeal_received_at'] || $c['appeal_reviewer_id']===null
            || $c['appeal_reviewer_id']===$c['decision_reviewer_id']))) {
        query($db,'UPDATE evidence_case_clocks SET state="resolution_review",unresolved_code="CONTRADICTORY_ANCHORS" WHERE case_id=?',[$case]);
        alert($db,$case,null,'CONTRADICTORY_ANCHORS');
        return query($db,'SELECT * FROM evidence_case_clocks WHERE case_id=?',[$case])->fetch(PDO::FETCH_ASSOC);
    }
    if ($c['appeal_received_at']!==null && $c['appeal_disposed_at']===null) { return $c; }
    if ($at<$c['appeal_until']) { return $c; }
    $final=max($c['appeal_until'],$c['appeal_disposed_at']??$c['appeal_until']);
    if ($final>$at) { throw new Failure('CONTRADICTORY_RESOLUTION_ANCHORS'); }
    $due=add($final,in_array($c['resolution_class'],['unrelated','accidental'],true)?'+30 days':'+180 days');
    query($db,'UPDATE evidence_case_clocks SET state="final",final_at=?,content_expires_at=?,decision_expires_at=?,version=version+1,unresolved_code=NULL WHERE case_id=? AND final_at IS NULL',
        [$final,$due,add($final,'+365 days'),$case]);
    query($db,'UPDATE moderation_cases SET final_resolution_at=?,report_resolution_class=? WHERE id=?',[$final,$c['resolution_class'],$case]);
    $c=query($db,'SELECT * FROM evidence_case_clocks WHERE case_id=?',[$case])->fetch(PDO::FETCH_ASSOC);
    propagate_deadline($db,$c);
    query($db,'UPDATE evidence_lifecycle_alerts SET resolved_at=? WHERE case_id=? AND item_key IS NULL AND resolved_at IS NULL',[$at,$case]);
    return $c;
}
function active_holds(PDO $db,array $item): array
{
    return query($db,'SELECT h.* FROM evidence_lifecycle_holds h WHERE h.item_key=? AND h.source_revision=? AND h.state="active" AND h.expires_at>? AND h.released_at IS NULL
        AND NOT EXISTS (SELECT 1 FROM evidence_recovery_overrides o WHERE o.item_key=h.item_key AND o.source_revision=h.source_revision AND o.action_name IN ("release","purge") AND h.grant_sequence_id IS NOT NULL AND o.sequence_id>=h.grant_sequence_id)
        ORDER BY h.hold_id FOR UPDATE',
        [$item['item_key'],$item['source_revision'],now($db)])->fetchAll(PDO::FETCH_ASSOC);
}
function item_locked(PDO $db,int $case,string $key,int $version): array
{
    $i=query($db,'SELECT * FROM evidence_lifecycle_items WHERE item_key=? AND case_id=? FOR UPDATE',[$key,$case])->fetch(PDO::FETCH_ASSOC);
    if (!$i || (int)$i['source_revision']!==$version || $i['purged_at']!==null) { throw new Failure('ITEM_UNAVAILABLE',404); }
    $s=source($db,$i);
    if (!$s || $s['purged_at']!==null || !hash_equals((string)$i['source_hash'],source_hash($s))) { throw new Failure('SOURCE_CHANGED'); }
    return $i;
}
function recovery_event(PDO $db,array $i,string $action,string $event,?string $until=null): int
{
    if ($action!=='purge') {
        $active=active_holds($db,$i);
        if ($active) { $action='hold'; $until=max(array_column($active,'expires_at')); }
        else {
            $action='release';
            $end=query($db,'SELECT MAX(LEAST(expires_at,COALESCE(released_at,expires_at))) FROM evidence_lifecycle_holds WHERE item_key=? AND source_revision=? AND granted_at IS NOT NULL',[$i['item_key'],$i['source_revision']])->fetchColumn();
            $until=is_string($end)?$end:$until;
        }
    }
    // Allocate above the independent frontier even after a stale SQL restore.
    // Global report serialization protects this small allocator; no runtime DDL.
    $floor=(int)query($db,'SELECT COALESCE(MAX(sequence_id),0) FROM evidence_recovery_outbox')->fetchColumn();
    $external=query($db,'SELECT required_watermark FROM evidence_recovery_state WHERE singleton=1 FOR UPDATE')->fetchColumn();
    $seq=max($floor,(int)$external)+1;
    query($db,'INSERT INTO evidence_recovery_outbox (sequence_id,event_id,item_key,source_revision,action_name,effective_at,ordinary_expires_at,preserve_until) VALUES (?,?,?,?,?,?,?,?)',
        [$seq,$event,$i['item_key'],$i['source_revision'],$action,now($db),$i['ordinary_expires_at'],$until]);
    // A later authorized transition supersedes a replayed projection. It is
    // still unpublished until the independent adapter acknowledges it.
    query($db,'UPDATE evidence_recovery_overrides SET sequence_id=?,action_name=?,preserve_until=?,effective_at=? WHERE item_key=? AND source_revision=?',
        [$seq,$action,$until,now($db),$i['item_key'],$i['source_revision']]);
    query($db,'INSERT INTO evidence_recovery_state (singleton,required_watermark,ready) VALUES (1,?,0)
        ON DUPLICATE KEY UPDATE required_watermark=GREATEST(required_watermark,?),ready=0',[$seq,$seq]);
    return $seq;
}

/** Strict request contract. Idempotency checks follow current authorization;
 * result receipts contain no private text. A different intent cannot reuse key. */
function act(PDO $db,Context $ctx,int $case,string $key,string $action,array $args=[]): array
{
    if ($case<1 || !preg_match('/\A[a-zA-Z0-9_-]{16,96}\z/D',$key)) { throw new Failure('INVALID_OPERATION',422); }
    $fields=['notice'=>['classification'],'appeal_received'=>[],'appeal_decided'=>['classification'], 'finalize'=>[],
        'urgent_hold'=>['item','revision','hours','reason'],'propose_hold'=>['item','revision','hours','reason'],
        'approve_hold'=>['hold'],'review_hold'=>['hold'],'request_release'=>['hold'],'release_hold'=>['hold']];
    if (!isset($fields[$action]) || array_diff(array_keys($args),$fields[$action]) || array_diff($fields[$action],array_keys($args))) {
        throw new Failure('UNSUPPORTED_OR_UNTRUSTED_FIELDS',422);
    }
    $cap=match($action) {'notice'=>'effective_notice','appeal_received'=>'appeal_intake','appeal_decided'=>'appeal_review',
        'propose_hold','request_release','review_hold'=>'safety_lead','approve_hold','release_hold'=>'privacy_approve',default=>'assigned_review'};
    ksort($args); $intent=identity($action,(string)$case,json_encode($args,JSON_THROW_ON_ERROR)); $kh=identity('op',$key);
    $result=transaction($db,function() use($db,$ctx,$case,$key,$action,$args,$cap,$intent,$kh): array {
        $ctx->authorize($db,$case,$cap);
        if (!register_case($db,$case)) { throw new Failure('NEW_INTAKE_CASE_REQUIRED',404); }
        $old=query($db,'SELECT * FROM evidence_lifecycle_operations WHERE actor_id=? AND key_hash=? FOR UPDATE',[$ctx->actor,$kh])->fetch(PDO::FETCH_ASSOC);
        if ($old) {
            if (!hash_equals($old['intent_hash'],$intent)) { throw new Failure('IDEMPOTENCY_CONFLICT'); }
            return json_decode($old['result_json'],true,16,JSON_THROW_ON_ERROR);
        }
        $at=now($db); $event=identity('operation',(string)$ctx->actor,$kh); $item=null;
        $c=query($db,'SELECT * FROM evidence_case_clocks WHERE case_id=? FOR UPDATE',[$case])->fetch(PDO::FETCH_ASSOC);
        if ($action==='notice') {
            if (!in_array($args['classification'],['ordinary','unrelated','accidental'],true)) { throw new Failure('INVALID_CLASSIFICATION',422); }
            if ($c['notice_at']!==null || $c['unresolved_code']==='UNTRUSTED_HISTORICAL_FINAL') { throw new Failure('RESOLUTION_RECONCILIATION_REQUIRED'); }
            query($db,'UPDATE evidence_case_clocks SET state="appeal_window",notice_at=?,notice_event=?,decision_reviewer_id=?,resolution_class=?,appeal_until=?,unresolved_code=NULL,version=version+1 WHERE case_id=?',
                [$at,$event,$ctx->actor,$args['classification'],add($at,'+14 days'),$case]);
        } elseif ($action==='appeal_received') {
            if ($c['notice_at']===null || $at>=$c['appeal_until'] || $c['final_at']!==null) { throw new Failure('APPEAL_WINDOW_CLOSED'); }
            if ($c['appeal_received_at']!==null) { throw new Failure('APPEAL_ALREADY_PENDING'); }
            query($db,'UPDATE evidence_case_clocks SET state="appeal_pending",appeal_received_at=?,version=version+1 WHERE case_id=?',[$at,$case]);
        } elseif ($action==='appeal_decided') {
            if (!in_array($args['classification'],['ordinary','unrelated','accidental'],true)) { throw new Failure('INVALID_CLASSIFICATION',422); }
            if ($c['appeal_received_at']===null || $c['appeal_disposed_at']!==null || $c['final_at']!==null) { throw new Failure('TIMELY_PENDING_APPEAL_REQUIRED'); }
            if ((int)$c['decision_reviewer_id']===$ctx->actor) { throw new Failure('INDEPENDENT_APPEAL_REVIEWER_REQUIRED',403); }
            query($db,'UPDATE evidence_case_clocks SET appeal_reviewer_id=?,appeal_disposed_at=?,resolution_class=?,state="appeal_window",version=version+1 WHERE case_id=?',[$ctx->actor,$at,$args['classification'],$case]);
        } elseif ($action==='finalize') {
            $c=finalize_locked($db,$case);
            if ($c['final_at']===null) { throw new Failure('TRUSTED_FINAL_RESOLUTION_PENDING'); }
        } elseif (in_array($action,['urgent_hold','propose_hold'],true)) {
            if (!is_string($args['item']) || !preg_match('/\A[a-f0-9]{64}\z/D',$args['item']) || !is_int($args['revision'])
                || !is_int($args['hours']) || $args['hours']<1 || $args['hours']>($action==='urgent_hold'?24:720)
                || !in_array($args['reason'],['CASE_CONTEXT','SAFETY_CASE','APPEAL_CONTEXT','IMMEDIATE_RISK'],true)) { throw new Failure('INVALID_HOLD_SCOPE',422); }
            $i=item_locked($db,$case,$args['item'],$args['revision']); $item=$i['item_key'];
            if ($i['item_kind']!=='evidence') { throw new Failure('EVIDENCE_ITEM_REQUIRED',422); }
            if ($action==='urgent_hold' && query($db,'SELECT 1 FROM evidence_lifecycle_holds WHERE item_key=? AND source_revision=? AND kind="urgent" LIMIT 1 FOR UPDATE',[$item,$i['source_revision']])->fetchColumn()) {
                throw new Failure('JOINT_AUTHORIZATION_REQUIRED');
            }
            if ($i['ordinary_expires_at']!==null && $at>=$i['ordinary_expires_at'] && active_holds($db,$i)===[]) { throw new Failure('ORDINARY_DEADLINE_PASSED'); }
            $urgent=$action==='urgent_hold'; $expires=add($at,'+'.$args['hours'].' hours');
            query($db,'INSERT INTO evidence_lifecycle_holds (hold_id,case_id,item_key,source_revision,kind,state,requested_hours,reason_code,created_by,lead_id,lead_proof_hash,granted_at,expires_at,review_due_at,created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$event,$case,$item,$i['source_revision'],$urgent?'urgent':'joint',$urgent?'active':'proposed',$args['hours'],$args['reason'],$ctx->actor,
                $urgent?null:$ctx->actor,$urgent?null:$ctx->proofHash(),$urgent?$at:null,$urgent?$expires:null,$urgent?min($expires,add($at,'+14 days')):null,$at]);
            if ($urgent) {
                $seq=recovery_event($db,$i,'hold',$event,$expires);
                query($db,'UPDATE evidence_lifecycle_holds SET grant_sequence_id=? WHERE hold_id=?',[$seq,$event]);
            }
        } else {
            if (!is_string($args['hold']) || !preg_match('/\A[a-f0-9]{64}\z/D',$args['hold'])) { throw new Failure('INVALID_HOLD',422); }
            $h=query($db,'SELECT * FROM evidence_lifecycle_holds WHERE hold_id=? AND case_id=? FOR UPDATE',[$args['hold'],$case])->fetch(PDO::FETCH_ASSOC);
            if (!$h) { throw new Failure('HOLD_UNAVAILABLE',404); }
            $i=item_locked($db,$case,$h['item_key'],(int)$h['source_revision']); $item=$i['item_key'];
            if ($action==='approve_hold') {
                if ($h['state']!=='proposed' || $ctx->actor===(int)$h['lead_id']) { throw new Failure('INDEPENDENT_JOINT_APPROVAL_REQUIRED',403); }
                verify_proof($db,$h['lead_proof_hash'],(int)$h['lead_id'],null,$case,'safety_lead');
                if ($i['ordinary_expires_at']!==null && $at>=$i['ordinary_expires_at'] && active_holds($db,$i)===[]) { throw new Failure('ORDINARY_DEADLINE_PASSED'); }
                $expires=add($at,'+'.$h['requested_hours'].' hours');
                query($db,'UPDATE evidence_lifecycle_holds SET state="active",privacy_id=?,granted_at=?,expires_at=?,review_due_at=?,version=version+1 WHERE hold_id=?',
                    [$ctx->actor,$at,$expires,min($expires,add($at,'+14 days')),$h['hold_id']]);
                $seq=recovery_event($db,$i,'hold',$event,$expires);
                query($db,'UPDATE evidence_lifecycle_holds SET grant_sequence_id=? WHERE hold_id=?',[$seq,$h['hold_id']]);
            } elseif ($action==='review_hold') {
                if ($h['state']!=='active' || $h['expires_at']<=$at || $h['released_at']!==null) { throw new Failure('ACTIVE_HOLD_REQUIRED'); }
                query($db,'UPDATE evidence_lifecycle_holds SET reviewed_at=?,review_due_at=?,version=version+1 WHERE hold_id=?',[$at,min($h['expires_at'],add($at,'+14 days')),$h['hold_id']]);
            } elseif ($action==='request_release') {
                if ($h['state']!=='active' || $h['release_requested_by']!==null) { throw new Failure('HOLD_NOT_RELEASABLE'); }
                query($db,'UPDATE evidence_lifecycle_holds SET release_requested_by=?,release_proof_hash=?,version=version+1 WHERE hold_id=?',[$ctx->actor,$ctx->proofHash(),$h['hold_id']]);
            } else {
                if ($h['state']!=='active' || $h['release_requested_by']===null || $ctx->actor===(int)$h['release_requested_by']) { throw new Failure('JOINT_RELEASE_REQUIRED',403); }
                verify_proof($db,$h['release_proof_hash'],(int)$h['release_requested_by'],null,$case,'safety_lead');
                query($db,'UPDATE evidence_lifecycle_holds SET released_at=?,state="released",version=version+1 WHERE hold_id=?',[$at,$h['hold_id']]);
                // A late review cannot postpone an already-expired hold's end.
                $effective=min($at,$h['expires_at']);
                recovery_event($db,$i,'release',$event,$effective);
                refresh_obligation($db,$i);
            }
        }
        $c=finalize_locked($db,$case);
        $result=['operation'=>$event,'case'=>$case,'action'=>$action,'status'=>'recorded','final_at'=>$c['final_at'],'content_expires_at'=>$c['content_expires_at']];
        if (in_array($action,['urgent_hold','propose_hold'],true)) { $result['hold']=$event; }
        audit($db,$case,$ctx->actor,$item,$action,'RECORDED',$event);
        query($db,'INSERT INTO evidence_lifecycle_operations (actor_id,key_hash,intent_hash,case_id,result_json,created_at) VALUES (?,?,?,?,?,?)',
            [$ctx->actor,$kh,$intent,$case,json_encode($result,JSON_THROW_ON_ERROR),$at]);
        fault('before_commit'); return $result;
    });
    fault('after_commit'); return $result;
}

function legacy_hold(PDO $db,array $i): bool
{
    return $i['item_kind']==='evidence' && (bool)query($db,'SELECT 1 FROM report_evidence_holds WHERE case_id=? AND evidence_id=? AND starts_at<=? AND expires_at>? AND released_at IS NULL LIMIT 1 FOR SHARE',
        [$i['case_id'],$i['source_id'],now($db),now($db)])->fetchColumn();
}
/** Computes effective deadline from immutable ordinary due and actual last hold
 * end. Active holds do not invent an indefinite ordinary retention period. */
function refresh_obligation(PDO $db,array $i): array
{
    $due=$i['ordinary_expires_at']; $at=now($db); $state='pending'; $reason='ORDINARY_EXPIRY';
    $holds=active_holds($db,$i);
    $override=query($db,'SELECT * FROM evidence_recovery_overrides WHERE item_key=? AND source_revision=? FOR UPDATE',[$i['item_key'],$i['source_revision']])->fetch(PDO::FETCH_ASSOC);
    $overrideHeld=$override && $override['action_name']==='hold' && $override['preserve_until']>$at;
    if ($due===null) { $state='unresolved'; $reason='MISSING_FINAL_RESOLUTION'; }
    elseif ($holds || $overrideHeld || legacy_hold($db,$i)) { $state='held'; $reason='SCOPED_HOLD'; }
    else {
        $end=query($db,'SELECT MAX(LEAST(expires_at,COALESCE(released_at,expires_at))) FROM evidence_lifecycle_holds WHERE item_key=? AND source_revision=? AND granted_at IS NOT NULL',[$i['item_key'],$i['source_revision']])->fetchColumn();
        if ($override && $override['preserve_until']!==null) { $end=max($end?:'',$override['preserve_until']); }
        if (is_string($end) && $end!=='' && $end>=$due) { $due=add($end,'+24 hours'); $reason='HOLD_ENDED'; }
    }
    $id=identity('purge-v1',$i['item_key'],(string)$i['source_revision']);
    query($db,'INSERT INTO evidence_purge_obligations (obligation_id,item_key,case_id,original_due_at,purge_due_at,state,reason_code,source_revision)
        VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE original_due_at=COALESCE(original_due_at,?),purge_due_at=IF(state="completed",purge_due_at,?),reason_code=IF(state="completed",reason_code,?),state=IF(state="completed",state,?)',
        [$id,$i['item_key'],$i['case_id'],$i['ordinary_expires_at'],$due,$state,$reason,$i['source_revision'],$i['ordinary_expires_at'],$due,$reason,$state]);
    query($db,'UPDATE evidence_lifecycle_items SET purge_due_at=? WHERE item_key=? AND purged_at IS NULL',[$due,$i['item_key']]);
    if ($state==='unresolved') { alert($db,(int)$i['case_id'],$i['item_key'],$reason); }
    if ($state==='pending' && $due!==null && $at>=$due) { alert($db,(int)$i['case_id'],$i['item_key'],'PURGE_DEADLINE',$due); }
    foreach ($holds as $h) {
        if ($h['review_due_at']<=$at) { alert($db,(int)$i['case_id'],$i['item_key'],'HOLD_REVIEW_OVERDUE',$h['review_due_at']); }
    }
    if (legacy_hold($db,$i)) { alert($db,(int)$i['case_id'],$i['item_key'],'UNCERTIFIED_LEGACY_HOLD'); }
    return ['id'=>$id,'state'=>$state,'due'=>$due];
}

function purge_locked(PDO $db,string $key): array
{
    $i=query($db,'SELECT * FROM evidence_lifecycle_items WHERE item_key=? FOR UPDATE',[$key])->fetch(PDO::FETCH_ASSOC);
    if (!$i) { throw new Failure('ITEM_UNAVAILABLE',404); }
    if ($i['purged_at']!==null) { return ['item'=>$key,'status'=>'already_completed','count'=>0]; }
    $c=finalize_locked($db,(int)$i['case_id']);
    $i=query($db,'SELECT * FROM evidence_lifecycle_items WHERE item_key=? FOR UPDATE',[$key])->fetch(PDO::FETCH_ASSOC);
    $o=refresh_obligation($db,$i); $at=now($db);
    if ($o['state']!=='pending' || $i['ordinary_expires_at']===null || $at<$i['ordinary_expires_at']) {
        return ['item'=>$key,'status'=>$o['state'],'count'=>0,'deadline'=>$o['due']];
    }
    // Purge immediately when eligible; +24h is an outer obligation, not a grace
    // period of ordinary read access. Recheck exact source copy/version.
    $s=source($db,$i);
    if (!$s || $s['purged_at']!==null || !hash_equals((string)$i['source_hash'],source_hash($s))) {
        alert($db,(int)$i['case_id'],$key,'SOURCE_CHANGED');
        return ['item'=>$key,'status'=>'source_changed','count'=>0];
    }
    fault('before_text');
    if ($i['item_kind']==='evidence') {
        query($db,'UPDATE moderation_evidence SET evidence_excerpt=NULL,evidence_hash=NULL,purged_at=? WHERE id=? AND case_id=? AND purged_at IS NULL',[$at,$i['source_id'],$i['case_id']]);
    } else {
        query($db,'UPDATE report_submissions SET explanation=NULL,information_hash=?,purged_at=? WHERE id=? AND purged_at IS NULL',
            [identity('expired',(string)$i['source_id']),$at,$i['source_id']]);
        query($db,'UPDATE report_operations SET intent_hash=NULL WHERE submission_id=?',[$i['source_id']]);
    }
    fault('after_text');
    query($db,'UPDATE evidence_lifecycle_items SET source_hash=NULL,purged_at=?,state="purged",version=version+1 WHERE item_key=?',[$at,$key]);
    query($db,'UPDATE evidence_purge_obligations SET state="completed",completed_at=? WHERE obligation_id=?',[$at,$o['id']]);
    fault('after_obligation');
    recovery_event($db,$i,'purge',$o['id']);
    audit($db,(int)$i['case_id'],null,$key,'text_purge','COMPLETED',$o['id']);
    query($db,'UPDATE evidence_lifecycle_alerts SET resolved_at=? WHERE item_key=? AND resolved_at IS NULL',[$at,$key]);
    fault('before_commit');
    return ['item'=>$key,'status'=>'completed','count'=>1,'category'=>$i['item_kind'],'deadline'=>$o['due'],'completed_at'=>$at,'obligation'=>$o['id']];
}

/** Bounded scheduler interface. Existing caller may already own the shared
 * transaction; standalone workers use one transaction per item. No media,
 * legacy-case, session, probation, pair/import or generic metadata deletion. */
function cleanup(PDO $db,int $limit=25): array
{
    if ($limit<1 || $limit>100) { throw new Failure('INVALID_BATCH_SIZE',422); }
    $owned=$db->inTransaction(); $run=fn(callable $f)=>$owned?$f():transaction($db,$f);
    $keys=$run(function() use($db,$limit): array {
        foreach(query($db,'SELECT c.id FROM moderation_cases c LEFT JOIN evidence_case_clocks k ON k.case_id=c.id
            WHERE c.intake_contract_version=1 AND k.case_id IS NULL ORDER BY c.id LIMIT '.$limit)->fetchAll(PDO::FETCH_COLUMN) as $case) { register_case($db,(int)$case); }
        query($db,'INSERT IGNORE INTO evidence_cleanup_cursor (singleton) VALUES (1)');
        $after=query($db,'SELECT last_item_key FROM evidence_cleanup_cursor WHERE singleton=1 FOR UPDATE')->fetchColumn();
        $keys=query($db,'SELECT item_key FROM evidence_lifecycle_items WHERE purged_at IS NULL AND item_key>? ORDER BY item_key LIMIT '.$limit,[$after?:''])->fetchAll(PDO::FETCH_COLUMN);
        if (!$keys) { $keys=query($db,'SELECT item_key FROM evidence_lifecycle_items WHERE purged_at IS NULL ORDER BY item_key LIMIT '.$limit)->fetchAll(PDO::FETCH_COLUMN); }
        // This is a scan cursor, never a claim that these items were erased.
        if ($keys) { query($db,'UPDATE evidence_cleanup_cursor SET last_item_key=? WHERE singleton=1',[$keys[array_key_last($keys)]]); }
        return $keys;
    });
    $out=['intake_copies_purged'=>0,'text_evidence_purged'=>0,'processed'=>0,'failures'=>0,'watermark'=>null,'items'=>[]];
    foreach($keys as $key) {
        try { $r=$run(fn()=>purge_locked($db,$key)); }
        catch (Throwable $e) {
            if ($owned) { throw $e; }
            $out['failures']++; $out['items'][]=['item'=>$key,'status'=>'failed','error'=>$e instanceof Failure?$e->reason:'TRANSACTION_FAILED']; continue;
        }
        $out['processed']++; $out['watermark']=$key; $out['items'][]=$r;
        if ($r['status']==='completed') { $out[$r['category']==='submission'?'intake_copies_purged':'text_evidence_purged']++; }
    }
    return $out;
}

/** Explicit scoped held-evidence route. No ordinary profile/chat grants. */
function held_view(PDO $db,Context $ctx,int $case,string $key,int $revision,string $purpose): ?string
{
    if (!in_array($purpose,['CASE_EVIDENCE_REVIEW','APPEAL_REVIEW','HOLD_NECESSITY_REVIEW'],true)) { throw new Failure('SCOPED_PURPOSE_REQUIRED',422); }
    return transaction($db,function() use($db,$ctx,$case,$key,$revision,$purpose): ?string {
        $ctx->authorize($db,$case,'assigned_review'); recovery_gate($db);
        $i=item_locked($db,$case,$key,$revision);
        if ($i['item_kind']!=='evidence' || active_holds($db,$i)===[]) { throw new Failure('ACTIVE_ITEM_HOLD_REQUIRED',403); }
        audit($db,$case,$ctx->actor,$key,'held_view',$purpose,bin2hex(random_bytes(32)));
        return source($db,$i)['body'];
    });
}

/** Read/dispatch release interface. The trusted provider's head must be checked
 * on every restored release, not accepted from a request/restored DB. */
function recovery_gate(PDO $db): void
{
    $r=query($db,'SELECT * FROM evidence_recovery_state WHERE singleton=1')->fetch(PDO::FETCH_ASSOC);
    if (!$r || !(int)$r['ready'] || (int)$r['applied_watermark']<(int)$r['required_watermark']) { throw new Failure('INDEPENDENT_RECOVERY_NOT_READY',503); }
    if (!synthetic_enabled()) { throw new Failure('INDEPENDENT_JOURNAL_UNAVAILABLE',503); }
    require_once __DIR__.'/evidence_recovery.php';
    $head=synthetic_journal_head();
    if ($head['watermark']!==(int)$r['applied_watermark'] || !hash_equals($head['digest'],(string)$r['head_digest'])
        || !hash_equals($head['namespace'],(string)$r['journal_namespace'])) { throw new Failure('RECOVERY_CONTINUITY_REQUIRED',503); }
}

/** Pure bound calculation: caller must supply a complete verified copy inventory.
 * Proposed copy settings are inputs, never silently approved defaults. */
function recovery_bound(string $event,?string $verifiedPurge,array $copyExpiries,bool $inventoryComplete,?string $protectUntil=null): array
{
    if (!$inventoryComplete || $verifiedPurge===null || in_array(null,$copyExpiries,true)) { return ['status'=>'UNVERIFIED_COPY_INVENTORY','retain_until'=>null]; }
    $latest=max(array_merge([$event,$verifiedPurge],$copyExpiries));
    $until=add($latest,'+7 days');
    return ['status'=>'CALCULATED_NOT_DURABILITY_PROOF','retain_until'=>max($until,$protectUntil??$until)];
}
