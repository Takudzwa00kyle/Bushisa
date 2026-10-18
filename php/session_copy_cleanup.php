<?php
declare(strict_types=1);
namespace Bushisa\SessionCopies;

use PDO;
use RuntimeException;
use Throwable;
use Bushisa\ManagedSession\Store;

require_once __DIR__.'/managed_session.php';

final class Failure extends RuntimeException
{ public function __construct(public readonly string $reason) { parent::__construct($reason); } }
function guard(): array
{
    if(PHP_SAPI!=='cli' || getenv('BUSHISA_SESSION_CLEANUP_ENABLED')!=='true') { throw new Failure('SESSION_CONSUMER_DISABLED'); }
    return \Bushisa\ManagedSession\configuration();
}
function connection(): PDO { guard();return \Bushisa\ManagedSession\connection(); }
function q(PDO $p,string $sql,array $args=[]): \PDOStatement { return \bushisa_signup_query($p,$sql,$args); }
function now(PDO $p): string { return \bushisa_signup_now($p); }
function stamp(string $at,string $offset='+0 seconds'): string { return \bushisa_signup_time($at,$offset); }
function key_ok(string $v): bool { return preg_match('/\A[a-f0-9]{64}\z/D',$v)===1; }
function enter(PDO $p): array
{
    $config=guard();
    if($p->inTransaction()) { throw new Failure('SESSION_OUTER_TRANSACTION_REFUSED'); }
    if($p->query('SELECT DATABASE()')->fetchColumn()!==getenv('DB_NAME')) { throw new Failure('SESSION_CONNECTION_REFUSED'); }
    $p->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');return $config;
}
function tx(PDO $p,callable $f): mixed { return \bushisa_signup_transaction($p,$f); }
function fault(string $point): void
{
    guard();
    if(getenv('BUSHISA_SESSION_CLEANUP_CRASH')===$point) { exit(77); }
    if(getenv('BUSHISA_SESSION_CLEANUP_FAIL')===$point) { throw new Failure('SESSION_SYNTHETIC_STORAGE_FAILURE'); }
}
/** Coordination order: account -> original Q11/identity -> coverage -> job ->
 * binding -> manifest/item -> bounded store fence. All entry paths share the
 * account prefix with Q11, the ordinary eraser and every managed writer. */
function locked(PDO $p,string $event): ?array
{
    $hint=q($p,'SELECT account_id FROM session_copy_obligations WHERE event_ref=?',[$event])->fetchColumn();if(!$hint)return null;
    $u=q($p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$hint])->fetch();
    $c=q($p,'SELECT * FROM signup_lifecycle WHERE account_id=? FOR UPDATE',[$hint])->fetch();
    $o=q($p,'SELECT * FROM session_copy_obligations WHERE event_ref=? FOR UPDATE',[$event])->fetch();
    $source=q($p,'SELECT * FROM signup_expiry_obligations WHERE receipt_id=? FOR UPDATE',[$o['source_receipt']])->fetch();
    $identity=q($p,'SELECT * FROM signup_attempt_identities WHERE account_id=? FOR UPDATE',[$hint])->fetch();
    $coverage=q($p,'SELECT * FROM session_copy_coverage WHERE account_key=? AND namespace_id=? FOR UPDATE',[$o['account_key'],getenv('BUSHISA_SESSION_NAMESPACE')])->fetch();
    $j=q($p,'SELECT * FROM session_copy_cleanup_jobs WHERE event_ref=? FOR UPDATE',[$event])->fetch();
    return compact('u','c','o','source','identity','coverage','j');
}
function validate(PDO $p,array $r,array $config): void
{
    ['u'=>$u,'c'=>$c,'o'=>$o,'source'=>$s,'identity'=>$i,'coverage'=>$coverage,'j'=>$j]=$r;
    if(!$u || !$c || !$s || !$i || $o['account_version']===null || $o['clock_version']===null || $o['registered_at']===null) { throw new Failure('SESSION_Q11_ANCHOR_REQUIRED'); }
    if($o['policy_ref']!=='Q11' || $o['component']!=='session_copies_v1' || !key_ok($o['account_key'])
        || $u['signup_attempt_key']!==$o['account_key'] || $i['attempt_key']!==$o['account_key']) { throw new Failure('SESSION_GENERATION_CHANGED'); }
    if($o['registered_at']!==$i['registered_at'] || $o['registered_at']!==$c['registered_at'] || $o['registered_at']!==stamp($u['created_at'])) { throw new Failure('SESSION_Q11_IDENTITY_CHANGED'); }
    if($u['account_status']!=='deleted' || (int)$u['is_active'] || (int)$u['is_verified'] || (int)$u['is_suspended'] || $u['role']!=='user' || $c['onboarding_completed_at']!==null) { throw new Failure('SESSION_ATTEMPT_NOT_ELIGIBLE'); }
    if((int)$u['session_version']!==(int)$o['account_version'] || (int)$s['account_version']!==(int)$o['account_version'] || (int)$c['version']!==(int)$o['clock_version']) { throw new Failure('SESSION_Q11_VERSION_CHANGED'); }
    $before=$u;$before['account_status']=$s['stage'];$before['is_verified']=$s['stage']==='pending_onboarding'?1:0;
    $due=\bushisa_signup_stage_due($before,$c);
    if(!in_array($s['stage'],['pending_email','pending_onboarding'],true) || !$due || $due!==$o['effective_at'] || $due!==$s['expires_at'] || $due!==$c['expired_at']
        || $c['expired_stage']!==$s['stage'] || $s['denied_at']!==$c['disabled_at'] || $u['deleted_at']===null
        || (int)$s['account_id']!==(int)$o['account_id'] || $o['purge_due_at']!==stamp($due,'+24 hours') || $o['purge_due_at']!==$s['live_purge_due_at'] || $o['purge_due_at']!==$c['cleanup_due_at']
        || !hash_equals($o['source_receipt'],hash('sha256','signup-expiry-v1|'.$o['account_id'].'|'.$s['stage'].'|'.$due))
        || !hash_equals($o['event_ref'],hash('sha256','session_copies_v1|'.$o['source_receipt'].'|'.$o['account_key']))) { throw new Failure('SESSION_Q11_EVENT_CONFLICT'); }
    if(!$coverage || $coverage['writer_contract']!=='synthetic_bound_v2' || (int)$coverage['account_id']!==(int)$o['account_id'] || $coverage['registered_at']!==$o['registered_at']) { throw new Failure('SESSION_COVERAGE_REQUIRED'); }
    $store=new Store($config);
    if($coverage['namespace_id']!==$config['namespace'] || !hash_equals($coverage['root_version'],$store->version())
        || ($j && ($j['namespace_id']!==$config['namespace'] || $j['root_version']!==$coverage['root_version']))) { throw new Failure('SESSION_STORE_COVERAGE_CHANGED'); }
    if(q($p,'SELECT 1 FROM session_bindings WHERE account_id=? AND account_key IS NULL LIMIT 1',[$o['account_id']])->fetchColumn()
        || q($p,'SELECT 1 FROM session_copy_manifests m LEFT JOIN session_bindings b ON b.session_ref=m.session_ref WHERE m.account_key=? AND (b.session_ref IS NULL OR b.account_key IS NULL OR b.account_id IS NULL OR m.account_id IS NULL OR b.account_key<>? OR b.account_id<>? OR m.account_id<>?) LIMIT 1',[$o['account_key'],$o['account_key'],$o['account_id'],$o['account_id']])->fetchColumn()) { throw new Failure('SESSION_MANIFEST_COVERAGE_REQUIRED'); }
}
function alert(PDO $p,array $o,string $reason): void
{
    $at=now($p);$due=$o['purge_due_at']??null;
    q($p,'INSERT IGNORE INTO session_copy_cleanup_alerts (event_ref,reason_code,deadline_at,first_at,escalate_at) VALUES (?,?,?,?,?)',[$o['event_ref'],$reason,$due,$at,$due?stamp($due,'+24 hours'):null]);
}
function overdue(PDO $p,array $o): void
{ if(now($p)>$o['purge_due_at']) { alert($p,$o,'SESSION_Q11_CLEANUP_OVERDUE'); } }
function enqueue_locked(PDO $p,array $r,array $config): void
{
    if($r['j'])return;
    $state='queued';$reason=null;
    try { validate($p,$r,$config); } catch(Throwable $e) { $state='blocked';$reason=reason($e); }
    q($p,'INSERT INTO session_copy_cleanup_jobs (event_ref,namespace_id,root_version,state,error_code,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',
        [$r['o']['event_ref'],$config['namespace'],$r['coverage']['root_version']??null,$state,$reason,now($p),now($p)]);
    if($reason)alert($p,$r['o'],$reason);
}
function reason(Throwable $e): string
{ return $e instanceof RuntimeException && preg_match('/\ASESSION_[A-Z_]+\z/D',$e->getMessage())?$e->getMessage():'SESSION_CLEANUP_TRANSACTION_FAILED'; }
/** No ambient transaction: an intent must actually commit before physical I/O. */
function claim(PDO $p,string $event,string $token): bool
{
    $config=enter($p);if(!key_ok($event) || !key_ok($token))throw new Failure('SESSION_CLAIM_INPUT_REFUSED');
    \bushisa_signup_test_point($p,'session_cleanup');
    return tx($p,function()use($p,$event,$token,$config) {
        $r=locked($p,$event);if(!$r)return false;enqueue_locked($p,$r,$config);$r=locked($p,$event);$j=$r['j'];
        if($j['state']==='complete' || $j['state']==='blocked')return false;
        if(($j['state']==='claimed' && $j['claim_until']>now($p)) || ($j['next_attempt_at']!==null && $j['next_attempt_at']>now($p)))return false;
        try { validate($p,$r,$config); } catch(Throwable $e) { fail_locked($p,$r,reason($e),true);return false; }
        // Expiry denies access at effective_at; physical copy removal is due
        // only at the independently approved expiry + 24h boundary. A worker
        // that arrives early sleeps against that immutable deadline instead
        // of turning a valid future obligation into a permanent conflict.
        if(now($p)<$r['o']['purge_due_at']) {
            q($p,'UPDATE session_copy_cleanup_jobs SET state="waiting",claim_hash=NULL,claim_until=NULL,next_attempt_at=?,error_code=NULL,updated_at=? WHERE event_ref=?',
                [$r['o']['purge_due_at'],now($p),$event]);return false;
        }
        overdue($p,$r['o']);
        q($p,'UPDATE session_copy_cleanup_jobs SET state="claimed",claim_hash=?,claim_version=claim_version+1,claim_until=?,next_attempt_at=NULL,attempts=attempts+1,error_code=NULL,updated_at=? WHERE event_ref=?',
            [hash('sha256',$token),stamp(now($p),'+60 seconds'),now($p),$event]);return true;
    });
}
function owned(array $r,string $token): bool
{ return $r['j'] && $r['j']['state']==='claimed' && is_string($r['j']['claim_hash']) && hash_equals($r['j']['claim_hash'],hash('sha256',$token)); }
function live_claim(PDO $p,array $r,string $token): bool
{ return owned($r,$token) && $r['j']['claim_until']>now($p); }
/** Closed synthetic pause, never an operational authority or storage locator. */
function test_pause(PDO $p,string $point): void
{
    if(getenv('BUSHISA_SESSION_CLEANUP_GATE_AT')!==$point)return;
    guard();$path=(string)getenv('BUSHISA_SESSION_CLEANUP_GATE_PATH');$dir=realpath(dirname($path));$base=realpath(dirname(__DIR__).'/.tmp');
    if(!$dir||!$base||!str_starts_with(str_replace('\\','/',$dir),str_replace('\\','/',$base).'/sc-run-')||!preg_match('/\Agate-[a-f0-9]{32}\.json\z/D',basename($path))||is_link($dir)||is_link($path))throw new Failure('SESSION_TEST_GATE_REFUSED');
    require_once __DIR__.'/http_test_barrier.php';
    \bushisa_write_test_record($path,['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'point'=>$point]);
    $until=microtime(true)+15;
    while(!is_file($path.'.release')) { if(microtime(true)>$until)throw new Failure('SESSION_TEST_GATE_TIMEOUT');usleep(10000); }
}
function fail_locked(PDO $p,array $r,string $code,bool $blocked=false): void
{
    q($p,'UPDATE session_copy_cleanup_jobs SET state=?,claim_hash=NULL,claim_until=NULL,next_attempt_at=?,error_code=?,updated_at=? WHERE event_ref=?',
        [$blocked?'blocked':'waiting',$blocked?null:stamp(now($p),'+30 seconds'),$code,now($p),$r['o']['event_ref']]);
    alert($p,$r['o'],$code);overdue($p,$r['o']);
}
function seal(PDO $p,array $r,array $config): void
{
    $o=$r['o'];$j=$r['j'];
    q($p,'UPDATE session_copy_coverage SET sealed_at=COALESCE(sealed_at,?) WHERE account_key=? AND namespace_id=?',[now($p),$o['account_key'],$config['namespace']]);
    $rows=q($p,'SELECT * FROM session_bindings WHERE account_id=? AND account_key=? AND session_ref>? ORDER BY session_ref LIMIT 25 FOR UPDATE',[$o['account_id'],$o['account_key'],$j['cursor_ref']])->fetchAll();
    foreach($rows as $b) {
        if(!key_ok($b['session_ref']) || $b['namespace_id']!==$config['namespace'] || (int)$b['key_version']!==1 || $b['auth_version']===null || (int)$b['auth_version']>=(int)$o['account_version']) { throw new Failure('SESSION_BINDING_COVERAGE_REQUIRED'); }
        q($p,'UPDATE session_bindings SET state="sealed",revision=revision+1,retired_at=COALESCE(retired_at,?),reason_code="Q11_COPY_FENCE" WHERE session_ref=? AND state<>"sealed"',[now($p),$b['session_ref']]);
        q($p,'UPDATE session_handoffs SET state="fenced" WHERE session_ref=? AND account_key=? AND state="pending"',[$b['session_ref'],$o['account_key']]);
    }
    q($p,'INSERT IGNORE INTO session_copy_recovery (event_ref,account_id,account_key,auth_floor,action_name,effective_at,purge_due_at,fenced_at) VALUES (?,?,?,?,"close_generation_and_purge",?,?,?)',
        [$o['event_ref'],$o['account_id'],$o['account_key'],$o['account_version'],$o['effective_at'],$o['purge_due_at'],now($p)]);
    if($rows)q($p,'UPDATE session_copy_cleanup_jobs SET cursor_ref=? WHERE event_ref=?',[end($rows)['session_ref'],$o['event_ref']]);
    else q($p,'UPDATE session_copy_cleanup_jobs SET stage="capture",cursor_ref="" WHERE event_ref=?',[$o['event_ref']]);
}
function manifest(PDO $p,array $o,array $m,array $config): void
{
    if(!key_ok($m['copy_ref']) || !key_ok($m['session_ref']) || $m['adapter_version']!=='synthetic_local_v2' || $m['instance_id']!=='fixture_shared'
        || $m['namespace_id']!==$config['namespace'] || !is_string($m['object_generation']) || !key_ok($m['object_generation'])) { throw new Failure('SESSION_MANIFEST_COVERAGE_REQUIRED'); }
    if($m['scope_class']==='anonymous' && $m['account_id']===null && $m['account_key']===null && $m['auth_version']===null) { return; }
    if($m['scope_class']!=='account' || (int)$m['account_id']!==(int)$o['account_id'] || $m['account_key']!==$o['account_key'] || $m['auth_version']===null || (int)$m['auth_version']>=(int)$o['account_version']) { throw new Failure('SESSION_MANIFEST_IDENTITY_CONFLICT'); }
    $b=q($p,'SELECT * FROM session_bindings WHERE session_ref=? FOR UPDATE',[$m['session_ref']])->fetch();
    if(!$b || $b['state']!=='sealed' || (int)$b['account_id']!==(int)$o['account_id'] || $b['account_key']!==$o['account_key'] || $b['namespace_id']!==$config['namespace'] || (int)$m['binding_epoch']>(int)$b['binding_epoch']) { throw new Failure('SESSION_BINDING_NOT_FENCED'); }
    if(q($p,'SELECT 1 FROM session_copy_exclusions WHERE copy_ref=?',[$m['copy_ref']])->fetchColumn()) { throw new Failure('SESSION_EVIDENCE_REFERENCE_REVIEW'); }
}
function capture(PDO $p,array $r,array $config): void
{
    $o=$r['o'];$j=$r['j'];
    $rows=q($p,'SELECT m.* FROM session_copy_manifests m JOIN session_bindings b ON b.session_ref=m.session_ref WHERE b.account_id=? AND b.account_key=? AND m.copy_ref>? ORDER BY m.copy_ref LIMIT 25 FOR UPDATE',[$o['account_id'],$o['account_key'],$j['cursor_ref']])->fetchAll();
    foreach($rows as $m) {
        manifest($p,$o,$m,$config);
        if($m['scope_class']==='anonymous')continue;
        q($p,'INSERT IGNORE INTO session_copy_purge_items (event_ref,copy_ref,session_ref,binding_epoch,object_generation) VALUES (?,?,?,?,?)',[$o['event_ref'],$m['copy_ref'],$m['session_ref'],$m['binding_epoch'],$m['object_generation']]);
    }
    if($rows)q($p,'UPDATE session_copy_cleanup_jobs SET cursor_ref=? WHERE event_ref=?',[end($rows)['copy_ref'],$o['event_ref']]);
    else q($p,'UPDATE session_copy_cleanup_jobs SET stage="erase",cursor_ref="" WHERE event_ref=?',[$o['event_ref']]);
}
function erase(PDO $p,array $r,array $config): void
{
    $o=$r['o'];$item=q($p,'SELECT * FROM session_copy_purge_items WHERE event_ref=? AND state<>"erased" ORDER BY copy_ref LIMIT 1 FOR UPDATE',[$o['event_ref']])->fetch();
    if(!$item) { q($p,'UPDATE session_copy_cleanup_jobs SET stage="verify",cursor_ref="" WHERE event_ref=?',[$o['event_ref']]);return; }
    $m=q($p,'SELECT * FROM session_copy_manifests WHERE copy_ref=? FOR UPDATE',[$item['copy_ref']])->fetch();
    if(!$m || $m['session_ref']!==$item['session_ref'] || $m['binding_epoch']!==$item['binding_epoch'] || $m['object_generation']!==$item['object_generation']) { throw new Failure('SESSION_PURGE_ITEM_CHANGED'); }
    manifest($p,$o,$m,$config);$store=new Store($config);
    if($item['state']==='pending') {
        $version=$store->inspect($item['copy_ref'],$item['object_generation']);
        q($p,'UPDATE session_copy_purge_items SET expected_identity=?,state="deleting",intent_at=? WHERE event_ref=? AND copy_ref=?',[$version,now($p),$o['event_ref'],$item['copy_ref']]);return;
    }
    if($item['state']!=='deleting' || $item['expected_identity']===null) { throw new Failure('SESSION_PURGE_INTENT_REQUIRED'); }
    fault('before_unlink');
    $store->erase($item['copy_ref'],$item['object_generation'],$item['expected_identity'],static function()use($p): void { test_pause($p,'before_unlink');fault('unlink_failure'); });
    fault('after_unlink');
    q($p,'UPDATE session_copy_purge_items SET state="erased",verified_at=COALESCE(verified_at,?) WHERE event_ref=? AND copy_ref=?',[now($p),$o['event_ref'],$item['copy_ref']]);
    q($p,'UPDATE session_copy_manifests SET state="erased",error_code=NULL WHERE copy_ref=?',[$item['copy_ref']]);
    q($p,'UPDATE session_bindings SET current_copy=NULL WHERE session_ref=? AND account_key=? AND current_copy=?',[$item['session_ref'],$o['account_key'],$item['copy_ref']]);
}
function verify(PDO $p,array $r,array $config): void
{
    $o=$r['o'];$j=$r['j'];
    if($r['coverage']['sealed_at']===null || q($p,'SELECT 1 FROM session_bindings WHERE account_id=? AND account_key=? AND state<>"sealed" LIMIT 1',[$o['account_id'],$o['account_key']])->fetchColumn()) { throw new Failure('SESSION_GENERATION_NOT_FENCED'); }
    if(q($p,'SELECT 1 FROM session_copy_purge_items WHERE event_ref=? AND state<>"erased" LIMIT 1',[$o['event_ref']])->fetchColumn()) { throw new Failure('SESSION_PURGE_INCOMPLETE'); }
    $rows=q($p,'SELECT m.* FROM session_copy_manifests m JOIN session_bindings b ON b.session_ref=m.session_ref WHERE b.account_id=? AND b.account_key=? AND m.copy_ref>? ORDER BY m.copy_ref LIMIT 25 FOR UPDATE',[$o['account_id'],$o['account_key'],$j['cursor_ref']])->fetchAll();
    $store=new Store($config);
    foreach($rows as $m) {
        manifest($p,$o,$m,$config);if($m['scope_class']==='anonymous')continue;
        $i=q($p,'SELECT * FROM session_copy_purge_items WHERE event_ref=? AND copy_ref=?',[$o['event_ref'],$m['copy_ref']])->fetch();
        if(!$i || $i['state']!=='erased' || $m['state']!=='erased' || $i['object_generation']!==$m['object_generation']) { throw new Failure('SESSION_PURGE_INCOMPLETE'); }
        if($store->inspect($m['copy_ref'],$m['object_generation'])!=='absent') { throw new Failure('SESSION_OBJECT_REAPPEARED'); }
    }
    if($rows) { q($p,'UPDATE session_copy_cleanup_jobs SET cursor_ref=? WHERE event_ref=?',[end($rows)['copy_ref'],$o['event_ref']]);return; }
    $count=(int)q($p,'SELECT COUNT(*) FROM session_copy_purge_items WHERE event_ref=?',[$o['event_ref']])->fetchColumn();
    q($p,'INSERT INTO session_copy_purge_receipts (event_ref,source_receipt,account_key,component,scope_version,namespace_id,root_version,copy_count,effective_at,purge_due_at,verified_at) VALUES (?,?,?,"session_copies_v1",2,?,?,?,?,?,?)',
        [$o['event_ref'],$o['source_receipt'],$o['account_key'],$config['namespace'],$r['coverage']['root_version'],$count,$o['effective_at'],$o['purge_due_at'],now($p)]);
    q($p,'UPDATE session_copy_recovery SET live_purged_at=COALESCE(live_purged_at,?) WHERE event_ref=?',[now($p),$o['event_ref']]);
    q($p,'UPDATE session_copy_obligations SET state="verified_synthetic_scope_v1" WHERE event_ref=?',[$o['event_ref']]);
    q($p,'UPDATE session_copy_cleanup_jobs SET stage="complete",state="complete",claim_hash=NULL,claim_until=NULL,completed_at=COALESCE(completed_at,?),updated_at=? WHERE event_ref=?',[now($p),now($p),$o['event_ref']]);
}
function step(PDO $p,string $event,string $token): string
{
    $config=enter($p);if(!key_ok($event)||!key_ok($token))throw new Failure('SESSION_CLAIM_INPUT_REFUSED');
    $result=tx($p,function()use($p,$event,$token,$config) {
        $r=locked($p,$event);if(!$r)return 'absent';if($r['j']['state']==='complete')return 'complete';
        if(!live_claim($p,$r,$token))return 'stale_claim';validate($p,$r,$config);overdue($p,$r['o']);
        match($r['j']['stage']) {'seal'=>seal($p,$r,$config),'capture'=>capture($p,$r,$config),'erase'=>erase($p,$r,$config),'verify'=>verify($p,$r,$config),default=>throw new Failure('SESSION_STAGE_UNKNOWN')};
        // A lease is not a file fence. If it elapsed during bounded I/O, SQL
        // acknowledgment rolls back and a later worker reconciles exact absence.
        if(!live_claim($p,$r,$token)) { throw new Failure('SESSION_CLAIM_EXPIRED'); }
        return $r['j']['stage'];
    });
    fault('after_step_commit');
    if($result==='verify' && q($p,'SELECT state FROM session_copy_cleanup_jobs WHERE event_ref=?',[$event])->fetchColumn()==='complete') { fault('after_complete_commit'); }
    if($result==='erase') { fault('after_purge_intent_commit'); }
    return $result;
}
function attempt(PDO $p,string $event,int $steps=25): string
{
    enter($p);if($steps<1||$steps>25||!key_ok($event))throw new Failure('SESSION_BATCH_LIMIT_REFUSED');
    $token=\Bushisa\ManagedSession\ref();
    if(!claim($p,$event,$token)) { return q($p,'SELECT state FROM session_copy_cleanup_jobs WHERE event_ref=?',[$event])->fetchColumn()==='complete'?'complete':'skipped'; }
    try {
        for($n=0;$n<$steps;$n++) {
            $s=step($p,$event,$token);if(in_array($s,['complete','absent','stale_claim'],true))return $s;
        }
        tx($p,function()use($p,$event,$token) { $r=locked($p,$event);if($r && owned($r,$token))q($p,'UPDATE session_copy_cleanup_jobs SET state="queued",claim_hash=NULL,claim_until=NULL,updated_at=? WHERE event_ref=?',[now($p),$event]); });return 'resumable';
    } catch(Throwable $e) {
        $code=reason($e);$retry=in_array($code,['SESSION_OBJECT_LOCKED','SESSION_OBJECT_UNAVAILABLE','SESSION_OBJECT_DELETE_FAILED','SESSION_STORE_UNAVAILABLE','SESSION_STORE_GUARD','SESSION_SYNTHETIC_STORAGE_FAILURE','SESSION_CLAIM_EXPIRED','SESSION_CLEANUP_TRANSACTION_FAILED'],true);
        try { tx($p,function()use($p,$event,$token,$code,$retry) { $r=locked($p,$event);if($r && owned($r,$token))fail_locked($p,$r,$code,!$retry); }); } catch(Throwable) { /* No false receipt on missing SQL grants. */ }
        return 'incomplete';
    }
}
function run(PDO $p,int $limit=10,int $steps=25): array
{
    enter($p);if($limit<1||$limit>25||$steps<1||$steps>25)throw new Failure('SESSION_BATCH_LIMIT_REFUSED');
    $rows=q($p,'SELECT o.event_ref FROM session_copy_obligations o LEFT JOIN session_copy_cleanup_jobs j ON j.event_ref=o.event_ref WHERE o.policy_ref="Q11" AND (j.event_ref IS NULL OR j.state="queued" OR (j.state="waiting" AND j.next_attempt_at<=?) OR (j.state="claimed" AND j.claim_until<=?)) ORDER BY o.purge_due_at,o.event_ref LIMIT '.$limit,[now($p),now($p)])->fetchAll(PDO::FETCH_COLUMN);
    $counts=[];foreach($rows as $event) { $s=attempt($p,$event,$steps);$counts[$s]=($counts[$s]??0)+1; }
    foreach(q($p,'SELECT o.* FROM session_copy_obligations o LEFT JOIN session_copy_cleanup_jobs j ON j.event_ref=o.event_ref WHERE o.purge_due_at<=? AND (j.event_ref IS NULL OR j.state<>"complete") ORDER BY o.purge_due_at LIMIT '.$limit,[now($p)])->fetchAll() as $o)tx($p,fn()=>overdue($p,$o));
    return $counts;
}
function status(PDO $p,string $event): array
{
    enter($p);if(!key_ok($event))throw new Failure('SESSION_EVENT_REFUSED');
    $job=q($p,'SELECT state,stage,error_code,created_at,completed_at FROM session_copy_cleanup_jobs WHERE event_ref=?',[$event])->fetch()?:null;
    $receipt=q($p,'SELECT component,scope_version,copy_count,effective_at,purge_due_at,verified_at FROM session_copy_purge_receipts WHERE event_ref=?',[$event])->fetch()?:null;
    return ['job'=>$job,'receipt'=>$receipt];
}
/** Same-DB read-only interface, NOT independent publication/durability. */
function recovery_instructions(PDO $p,string $after='',int $limit=25): array
{
    enter($p);if($limit<1||$limit>100||($after!==''&&!key_ok($after)))throw new Failure('SESSION_RECOVERY_BOUND_REFUSED');
    // The protected repair payload remains in the guarded database for a
    // separately authorized recovery integration. This operator-facing CLI
    // returns counts only; HMAC/resource references can remain personal data.
    $rows=q($p,'SELECT publication_state,copy_inventory,retain_until,live_purged_at FROM session_copy_recovery WHERE event_ref>? ORDER BY event_ref LIMIT '.$limit,[$after])->fetchAll();
    return ['count'=>count($rows),'unpublished'=>count(array_filter($rows,static fn($r)=>$r['publication_state']!=='published')),
        'unverified_inventory'=>count(array_filter($rows,static fn($r)=>$r['copy_inventory']!=='verified')),
        'live_purged'=>count(array_filter($rows,static fn($r)=>$r['live_purged_at']!==null))];
}
