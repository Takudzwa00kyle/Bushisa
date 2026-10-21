<?php
declare(strict_types=1);
namespace Bushisa\AttemptErasure;
use PDO;
require_once __DIR__.'/signup_lifecycle.php';

final class Failure extends \RuntimeException
{ public function __construct(public readonly string $reason) { parent::__construct($reason); } }
function q(PDO $p,string $sql,array $args=[]): \PDOStatement { return \bushisa_signup_query($p,$sql,$args); }
function now(PDO $p): string { return \bushisa_signup_now($p); }
function tx(PDO $p,callable $f): mixed { return \bushisa_signup_transaction($p,$f); }
function ready(PDO $p): bool { return \operational_table_exists($p,'signup_attempt_erasure_jobs'); }
function worker_guard(): void
{ if (PHP_SAPI!=='cli' || getenv('BUSHISA_ATTEMPT_ERASURE_ENABLED')!=='true') { throw new Failure('ATTEMPT_CONSUMER_DISABLED'); } }
function synthetic(): bool { return \bushisa_q11_synthetic_enabled() && getenv('BUSHISA_ATTEMPT_ERASURE_SYNTHETIC')==='true'; }
function fault(string $point): void
{
    if (!synthetic() || PHP_SAPI!=='cli')return;
    if (getenv('BUSHISA_ATTEMPT_CRASH')===$point)exit(77);
    if (getenv('BUSHISA_ATTEMPT_FAULT')===$point)throw new Failure('SYNTHETIC_STORAGE_FAILURE');
}
/** Only the actual new-registration transaction calls this. Never backfill. */
function bind_creation(PDO $p,int $id): void
{
    if (!$p->inTransaction())throw new \LogicException('Attempt binding needs creation transaction');
    $u=q($p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    if (!$u || $u['account_status']!=='pending_email' || $u['signup_attempt_key']!==null)throw new Failure('NEW_ATTEMPT_REQUIRED');
    $key=bin2hex(random_bytes(32));
    q($p,'UPDATE users SET signup_attempt_key=? WHERE id=? AND signup_attempt_key IS NULL',[$key,$id]);
    q($p,'INSERT INTO signup_attempt_identities (account_id,attempt_key,registered_at,created_at) VALUES (?,?,?,?)',[$id,$key,\bushisa_signup_time($u['created_at']),now($p)]);
}
function enqueue(PDO $p,int $id,string $receipt): void
{
    if (!$p->inTransaction())throw new \LogicException('Attempt job needs expiry transaction');
    $u=q($p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    $c=q($p,'SELECT * FROM signup_lifecycle WHERE account_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    $o=q($p,'SELECT * FROM signup_expiry_obligations WHERE receipt_id=? AND account_id=? FOR UPDATE',[$receipt,$id])->fetch(PDO::FETCH_ASSOC);
    if (!$o)return;
    $i=q($p,'SELECT * FROM signup_attempt_identities WHERE account_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
    $valid=$i && $u && $c && $u['signup_attempt_key']!==null && hash_equals($i['attempt_key'],$u['signup_attempt_key']) && $i['registered_at']===$c['registered_at'];
    q($p,'INSERT IGNORE INTO signup_attempt_erasure_jobs (receipt_id,account_id,attempt_key,registered_at,account_version,clock_version,expires_at,due_at,state,error_code,counts_json,created_at,updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,JSON_OBJECT(),?,?)',[$receipt,$id,$valid?$i['attempt_key']:null,$valid?$i['registered_at']:null,$o['account_version'],$c['version']??null,$o['expires_at'],$o['live_purge_due_at'],$valid?'queued':'blocked',$valid?null:'GENERATION_PROOF_REQUIRED',now($p),now($p)]);
}
function health(PDO $p): void
{
    $s=$p->query('CALL bushisa_attempt_schema_health()');$h=$s->fetch(PDO::FETCH_ASSOC);while($s->nextRowset()){}$s->closeCursor();
    if (!$h || $h['contract']!=='attempt-schema-v1' || (int)$h['unknown_columns'] || (int)$h['child_cascades'] || (int)$h['unknown_triggers'] || (int)$h['generation_guard']!==1)throw new Failure('SCHEMA_REVIEW_REQUIRED');
}
/** Account first, then original clocks/obligation/identity/job. No email lookup. */
function locked(PDO $p,string $receipt): ?array
{
    $hint=q($p,'SELECT account_id FROM signup_attempt_erasure_jobs WHERE receipt_id=?',[$receipt])->fetchColumn();if(!$hint)return null;
    $u=q($p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$hint])->fetch(PDO::FETCH_ASSOC);
    $c=q($p,'SELECT * FROM signup_lifecycle WHERE account_id=? FOR UPDATE',[$hint])->fetch(PDO::FETCH_ASSOC);
    $o=q($p,'SELECT * FROM signup_expiry_obligations WHERE receipt_id=? FOR UPDATE',[$receipt])->fetch(PDO::FETCH_ASSOC);
    $i=q($p,'SELECT * FROM signup_attempt_identities WHERE account_id=? FOR UPDATE',[$hint])->fetch(PDO::FETCH_ASSOC);
    $j=q($p,'SELECT * FROM signup_attempt_erasure_jobs WHERE receipt_id=? FOR UPDATE',[$receipt])->fetch(PDO::FETCH_ASSOC);
    return compact('u','c','o','i','j');
}
function validate(array $r): void
{
    ['u'=>$u,'c'=>$c,'o'=>$o,'i'=>$i,'j'=>$j]=$r;
    if (!$u || !$c || !$o || !$i || !$j['attempt_key'])throw new Failure('GENERATION_PROOF_REQUIRED');
    foreach([$u['signup_attempt_key'],$i['attempt_key']] as $key)if(!is_string($key)||!hash_equals($j['attempt_key'],$key))throw new Failure('ATTEMPT_GENERATION_CHANGED');
    if ($j['registered_at']!==$i['registered_at'] || $j['registered_at']!==$c['registered_at'] || $j['registered_at']!==\bushisa_signup_time($u['created_at']))throw new Failure('ATTEMPT_IDENTITY_CHANGED');
    if ($u['account_status']!=='deleted' || (int)$u['is_active'] || (int)$u['is_verified'] || (int)$u['is_suspended'] || $u['role']!=='user' || $c['onboarding_completed_at']!==null)throw new Failure('ACCOUNT_NOT_EXPIRED_ATTEMPT');
    if ((int)$u['session_version']!==(int)$j['account_version'] || (int)$o['account_version']!==(int)$j['account_version'] || (int)$c['version']!==(int)$j['clock_version'])throw new Failure('ATTEMPT_VERSION_CHANGED');
    if (!in_array($o['stage'],['pending_email','pending_onboarding'],true) || (int)$o['account_id']!==(int)$j['account_id'])throw new Failure('EXPIRY_PROOF_REQUIRED');
    $before=$u;$before['account_status']=$o['stage'];$before['is_verified']=$o['stage']==='pending_onboarding'?1:0;
    $due=\bushisa_signup_stage_due($before,$c);
    if(!hash_equals($j['receipt_id'],hash('sha256','signup-expiry-v1|'.$j['account_id'].'|'.$o['stage'].'|'.$o['expires_at'])))throw new Failure('EXPIRY_PROOF_REQUIRED');
    if (!$due || $due!==$j['expires_at'] || $due!==$o['expires_at'] || $due!==$c['expired_at'] || $c['expired_stage']!==$o['stage']
        || $o['denied_at']!==$c['disabled_at'] || $u['deleted_at']===null
        || $o['live_purge_due_at']!==\bushisa_signup_time($due,'+24 hours') || $j['due_at']!==$o['live_purge_due_at'] || $j['due_at']!==$c['cleanup_due_at'])throw new Failure('EXPIRY_PROOF_REQUIRED');
}
function mark(PDO $p,array $j,string $state,string $error): void
{
    q($p,'UPDATE signup_attempt_erasure_jobs SET state=?,error_code=?,claim_hash=NULL,claim_until=NULL,next_attempt_at=?,updated_at=? WHERE receipt_id=?',[$state,$error,$state==='waiting'?\bushisa_signup_time(now($p),'+30 seconds'):null,now($p),$j['receipt_id']]);
    \bushisa_signup_alert($p,(int)$j['account_id'],$error,$j['due_at']);
}
function claim(PDO $p,string $receipt,string $token): bool
{
    worker_guard();\bushisa_signup_test_point($p,'q11_mixed');return tx($p,function()use($p,$receipt,$token):bool {
        $r=locked($p,$receipt);if(!$r)return false;$j=$r['j'];$at=now($p);
        if (in_array($j['state'],['complete','blocked'],true) || ($j['state']==='claimed' && $j['claim_until']>$at) || ($j['next_attempt_at']!==null && $j['next_attempt_at']>$at))return false;
        try{validate($r);health($p);}catch(Failure $e){mark($p,$j,'blocked',$e->reason);return false;}
        if($at<$j['expires_at']){mark($p,$j,'blocked','EXPIRY_PROOF_REQUIRED');return false;}
        q($p,'UPDATE signup_attempt_erasure_jobs SET state="claimed",claim_hash=?,claim_until=?,attempts=attempts+1,error_code=NULL,updated_at=? WHERE receipt_id=?',
            [hash('sha256',$token),\bushisa_signup_time($at,'+60 seconds'),$at,$receipt]);return true;
    });
}
function locator(int $account,string $path): string
{
    if(strlen($path)>180 || !preg_match('~\Auploads/'.preg_quote((string)$account,'~').'/photo_[A-Za-z0-9_-]{1,64}(?:\.[A-Za-z0-9_-]{1,16}){1,2}\z~D',$path))throw new Failure('MEDIA_LOCATOR_REVIEW_REQUIRED');return $path;
}
function alias(array $j): string { return 'erased-'.substr($j['attempt_key'],0,40).'@example.invalid'; }
function core(PDO $p,array $r): void
{
    // Like L03a, the operational independent-recovery adapter is unavailable.
    // A deployment flag is not evidence of a durable protective instruction.
    if(!synthetic())throw new Failure('RECOVERY_PROVIDER_REQUIRED');
    ['j'=>$j,'u'=>$u]=$r;$id=(int)$j['account_id'];
    foreach(['swipes'=>['swiper_id','swiped_id'],'matches'=>['user_a_id','user_b_id'],'messages'=>['sender_id'],'confessions'=>['author_id'],'notifications'=>['recipient_id']] as $table=>$cols){
        if(q($p,'SELECT 1 FROM '.$table.' WHERE '.implode(' OR ',array_map(fn($c)=>$c.'=?',$cols)).' LIMIT 1',array_fill(0,count($cols),$id))->fetchColumn())throw new Failure('FEATURE_DATA_REVIEW_REQUIRED');
    }
    foreach(['find_match_answers','notification_preferences'] as $table)if(count(q($p,'SELECT user_id FROM '.$table.' WHERE user_id=? LIMIT 201',[$id])->fetchAll())>200)throw new Failure('ATTEMPT_SCOPE_LIMIT_EXCEEDED');
    if(q($p,'SELECT 1 FROM student_email_approval_codes WHERE user_id=? AND code_hash IS NOT NULL LIMIT 1',[$id])->fetchColumn()
        || q($p,'SELECT 1 FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id WHERE c.user_id=? AND m.body IS NOT NULL LIMIT 1',[$id])->fetchColumn())throw new Failure('CHALLENGE_CANCELLATION_REQUIRED');
    $paths=[];$profile=q($p,'SELECT profile_photo_path FROM profiles WHERE user_id=? FOR UPDATE',[$id])->fetchColumn();if(is_string($profile)&&$profile!=='')$paths[]=$profile;
    foreach(q($p,'SELECT path FROM profile_images WHERE user_id=? ORDER BY id LIMIT 5 FOR UPDATE',[$id])->fetchAll(PDO::FETCH_COLUMN) as $path)$paths[]=$path;
    if(count($paths)>4)throw new Failure('ATTEMPT_SCOPE_LIMIT_EXCEEDED');
    foreach(array_unique($paths) as $path){$path=locator($id,$path);q($p,'INSERT IGNORE INTO signup_attempt_erasure_objects (receipt_id,object_key,locator) VALUES (?,?,?)',[$j['receipt_id'],hash('sha256',$j['attempt_key'].'|'.$path),$path]);}
    // Persist the recovery intent BEFORE content changes, in the same SQL commit.
    q($p,'INSERT IGNORE INTO signup_attempt_erasure_recovery (receipt_id,account_id,attempt_key,account_version,action_name,effective_at,due_at) VALUES (?,?,?,?,"erase_attempt_v1",?,?)',[$j['receipt_id'],$id,$j['attempt_key'],$j['account_version'],$j['expires_at'],$j['due_at']]);
    fault('after_recovery_intent');$counts=[];
    foreach(['profiles','preferences','find_match_answers','notification_preferences','profile_images'] as $table)$counts[$table]=q($p,'DELETE FROM '.$table.' WHERE user_id=? LIMIT 200',[$id])->rowCount();
    q($p,'UPDATE users SET nust_student_id=?,email=?,password_hash="" WHERE id=?',['er_'.substr($j['attempt_key'],0,17),alias($j),$id]);
    fault('before_core_commit');
    q($p,'UPDATE signup_attempt_erasure_jobs SET stage="codes",cursor_id=0,counts_json=?,updated_at=? WHERE receipt_id=?',[json_encode($counts,JSON_THROW_ON_ERROR),now($p),$j['receipt_id']]);
}
function metadata(PDO $p,array $j): void
{
    $codes=$j['stage']==='codes';$id=(int)$j['account_id'];
    $rows=$codes?q($p,'SELECT id FROM student_email_approval_codes WHERE user_id=? AND id>? ORDER BY id LIMIT 100 FOR UPDATE',[$id,$j['cursor_id']])->fetchAll(PDO::FETCH_COLUMN):
        q($p,'SELECT m.id FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id WHERE c.user_id=? AND m.id>? ORDER BY m.id LIMIT 100 FOR UPDATE',[$id,$j['cursor_id']])->fetchAll(PDO::FETCH_COLUMN);
    if(!$rows){
        if($codes){q($p,'UPDATE signup_attempt_erasure_jobs SET stage="mail",cursor_id=0,updated_at=? WHERE receipt_id=?',[now($p),$j['receipt_id']]);return;}
        q($p,'UPDATE signup_attempt_erasure_recovery SET database_purged_at=COALESCE(database_purged_at,?) WHERE receipt_id=?',[now($p),$j['receipt_id']]);receipt($p,$j,'database');
        q($p,'UPDATE signup_attempt_erasure_jobs SET stage="objects",cursor_id=0,updated_at=? WHERE receipt_id=?',[now($p),$j['receipt_id']]);return;
    }
    foreach($rows as $row){
        if($codes)q($p,'UPDATE student_email_approval_codes SET email=? WHERE id=? AND user_id=?',[alias($j),$row,$id]);
        else q($p,'UPDATE mail_outbox SET recipient_email=?,subject="signup_verification",metadata_json=NULL WHERE id=?',[alias($j),$row]);
    }
    $counts=json_decode($j['counts_json'],true,16,JSON_THROW_ON_ERROR);$key=$codes?'challenge_addresses':'mail_addresses';$counts[$key]=($counts[$key]??0)+count($rows);
    q($p,'UPDATE signup_attempt_erasure_jobs SET cursor_id=?,counts_json=?,updated_at=? WHERE receipt_id=?',[max($rows),json_encode($counts,JSON_THROW_ON_ERROR),now($p),$j['receipt_id']]);
}
function receipt(PDO $p,array $j,string $component): void
{ q($p,'INSERT IGNORE INTO signup_attempt_erasure_receipts (receipt_id,component,attempt_key,scope_version,counts_json,completed_at) VALUES (?,?,?,1,?,?)',[$j['receipt_id'],$component,$j['attempt_key'],$j['counts_json'],now($p)]); }
/** Only a fixture storage provider is available. Real storage inventory/access,
 * immutable generations and conditional-delete provider need separate review. */
function object_path(string $locator): string
{
    if(!synthetic() || getenv('BUSHISA_ATTEMPT_STORAGE_DRIVER')!=='fixture')throw new Failure('STORAGE_ADAPTER_REQUIRED');
    $base=realpath(__DIR__.'/../.tmp');$root=realpath((string)getenv('BUSHISA_ATTEMPT_STORAGE_ROOT'));
    $norm=fn(string $s)=>str_replace('\\','/',$s);
    if(!$base||!$root||!str_starts_with($norm($root).'/',rtrim($norm($base),'/').'/')||!str_starts_with(basename($root),'qe-storage-'))throw new Failure('STORAGE_ROOT_REFUSED');
    $current=$root;
    foreach(explode('/',$locator) as $part){$current.=DIRECTORY_SEPARATOR.$part;if(is_link($current))throw new Failure('STORAGE_LINK_REFUSED');}
    return $current;
}
function object_version(string $path): ?string
{
    clearstatcache(true,$path);if(!file_exists($path))return null;
    if(!is_file($path)||is_link($path))throw new Failure('STORAGE_OBJECT_REFUSED');$s=@lstat($path);
    if(!$s || $s['nlink']!==1 || $s['size']>5*1024*1024)throw new Failure('STORAGE_OBJECT_REFUSED');
    $h=@hash_file('sha256',$path);if(!is_string($h))throw new Failure('STORAGE_READ_FAILED');
    return hash('sha256',implode('|',[$s['dev'],$s['ino'],$s['size'],$s['mtime'],$s['ctime'],$h]));
}
function objects(PDO $p,array $j): void
{
    $o=q($p,'SELECT * FROM signup_attempt_erasure_objects WHERE receipt_id=? AND state<>"erased" ORDER BY object_key LIMIT 1 FOR UPDATE',[$j['receipt_id']])->fetch(PDO::FETCH_ASSOC);
    if(!$o){q($p,'UPDATE signup_attempt_erasure_jobs SET stage="verify",updated_at=? WHERE receipt_id=?',[now($p),$j['receipt_id']]);return;}
    locator((int)$j['account_id'],$o['locator']);
    if(q($p,'SELECT 1 FROM reports WHERE evidence_path=? LIMIT 1',[$o['locator']])->fetchColumn())throw new Failure('EVIDENCE_OBJECT_PROTECTED');
    if(q($p,'SELECT 1 FROM profiles WHERE profile_photo_path=? LIMIT 1',[$o['locator']])->fetchColumn()||q($p,'SELECT 1 FROM profile_images WHERE path=? LIMIT 1',[$o['locator']])->fetchColumn())throw new Failure('OBJECT_REFERENCE_REVIEW_REQUIRED');
    $path=object_path($o['locator']);$version=object_version($path);
    if($o['state']==='pending'){
        q($p,'UPDATE signup_attempt_erasure_objects SET expected_version=?,state="deleting",intent_at=? WHERE receipt_id=? AND object_key=?',[$version??'absent',now($p),$j['receipt_id'],$o['object_key']]);return;
    }
    if($version!==null){
        if(!hash_equals($o['expected_version'],$version))throw new Failure('OBJECT_VERSION_CHANGED');
        fault('before_unlink');if(!@unlink($path))throw new Failure('STORAGE_DELETE_FAILED');fault('after_unlink');
    }
    if(object_version($path)!==null)throw new Failure('STORAGE_DELETE_UNVERIFIED');
    q($p,'UPDATE signup_attempt_erasure_objects SET state="erased",erased_at=COALESCE(erased_at,?) WHERE receipt_id=? AND object_key=?',[now($p),$j['receipt_id'],$o['object_key']]);
}
function verify(PDO $p,array $r): void
{
    $j=$r['j'];$id=(int)$j['account_id'];$u=$r['u'];
    if($u['email']!==alias($j)||$u['password_hash']!==''||$u['nust_student_id']!=='er_'.substr($j['attempt_key'],0,17))throw new Failure('DATABASE_ERASURE_UNVERIFIED');
    foreach(['profiles','preferences','find_match_answers','profile_images','notification_preferences'] as $table)if(q($p,'SELECT 1 FROM '.$table.' WHERE user_id=? LIMIT 1',[$id])->fetchColumn())throw new Failure('DATABASE_ERASURE_UNVERIFIED');
    if(q($p,'SELECT 1 FROM student_email_approval_codes WHERE user_id=? AND (email<>? OR code_hash IS NOT NULL) LIMIT 1',[$id,alias($j)])->fetchColumn()
        ||q($p,'SELECT 1 FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id WHERE c.user_id=? AND (m.recipient_email<>? OR m.body IS NOT NULL) LIMIT 1',[$id,alias($j)])->fetchColumn()
        ||q($p,'SELECT 1 FROM signup_attempt_erasure_objects WHERE receipt_id=? AND state<>"erased" LIMIT 1',[$j['receipt_id']])->fetchColumn())throw new Failure('DATABASE_ERASURE_UNVERIFIED');
    receipt($p,$j,'complete');
    q($p,'UPDATE signup_attempt_erasure_recovery SET live_purged_at=COALESCE(live_purged_at,?) WHERE receipt_id=?',[now($p),$j['receipt_id']]);
    q($p,'UPDATE signup_expiry_obligations SET cleanup_state="verified_live_scope_v1" WHERE receipt_id=?',[$j['receipt_id']]);
    q($p,'UPDATE signup_attempt_erasure_jobs SET state="complete",stage="complete",completed_at=COALESCE(completed_at,?),claim_hash=NULL,claim_until=NULL,error_code=NULL,updated_at=? WHERE receipt_id=?',[now($p),now($p),$j['receipt_id']]);
}
function step(PDO $p,string $receipt,string $token): string
{
    worker_guard();$result=tx($p,function()use($p,$receipt,$token):string{
        $r=locked($p,$receipt);if(!$r)return 'absent';$j=$r['j'];if($j['state']==='complete')return 'complete';
        if($j['state']!=='claimed'||!hash_equals((string)$j['claim_hash'],hash('sha256',$token))||$j['claim_until']<=now($p))return 'stale_claim';
        validate($r);health($p);
        if(!synthetic())throw new Failure('RECOVERY_PROVIDER_REQUIRED');
        match($j['stage']){'core'=>core($p,$r),'codes','mail'=>metadata($p,$j),'objects'=>objects($p,$j),'verify'=>verify($p,$r),default=>throw new Failure('UNKNOWN_ERASURE_STAGE')};
        return $j['stage'];
    });
    if($result==='verify')fault('after_completion_commit');
    fault('after_step_commit');return $result;
}
function attempt(PDO $p,string $receipt,int $steps=20): string
{
    worker_guard();if($steps<1||$steps>25)throw new Failure('INVALID_STEP_BOUND');$token=bin2hex(random_bytes(32));if(!claim($p,$receipt,$token))return 'skipped';
    try{
        for($n=0;$n<$steps;$n++){ $s=step($p,$receipt,$token);if(in_array($s,['complete','stale_claim','absent'],true))return $s; }
        tx($p,function()use($p,$receipt,$token){$r=locked($p,$receipt);if($r && hash_equals((string)$r['j']['claim_hash'],hash('sha256',$token)))q($p,'UPDATE signup_attempt_erasure_jobs SET state="queued",claim_hash=NULL,claim_until=NULL,updated_at=? WHERE receipt_id=?',[now($p),$receipt]);});return 'resumable';
    }catch(\Throwable $e){
        $reason=$e instanceof Failure?$e->reason:'ERASURE_DATABASE_FAILURE';
        tx($p,function()use($p,$receipt,$token,$reason){$r=locked($p,$receipt);if($r && hash_equals((string)$r['j']['claim_hash'],hash('sha256',$token)))mark($p,$r['j'],in_array($reason,['GENERATION_PROOF_REQUIRED','ATTEMPT_GENERATION_CHANGED','ATTEMPT_IDENTITY_CHANGED','ACCOUNT_NOT_EXPIRED_ATTEMPT','ATTEMPT_VERSION_CHANGED','EXPIRY_PROOF_REQUIRED','SCHEMA_REVIEW_REQUIRED','FEATURE_DATA_REVIEW_REQUIRED','MEDIA_LOCATOR_REVIEW_REQUIRED','ATTEMPT_SCOPE_LIMIT_EXCEEDED','CHALLENGE_CANCELLATION_REQUIRED','RECOVERY_PROVIDER_REQUIRED'],true)?'blocked':'waiting',$reason);});return 'incomplete';
    }
}
function run(PDO $p,int $limit=10,int $steps=20): array
{
    worker_guard();if($limit<1||$limit>25||$steps<1||$steps>25)throw new Failure('INVALID_BATCH_BOUND');if(!ready($p))throw new Failure('ATTEMPT_MIGRATION_REQUIRED');
    // Discover older obligations explicitly; missing generation proof blocks.
    foreach(q($p,'SELECT o.account_id,o.receipt_id FROM signup_expiry_obligations o LEFT JOIN signup_attempt_erasure_jobs j ON j.receipt_id=o.receipt_id WHERE j.receipt_id IS NULL AND o.cleanup_state="pending_consumer" ORDER BY o.expires_at LIMIT '.$limit)->fetchAll(PDO::FETCH_ASSOC) as $o)tx($p,fn()=>enqueue($p,(int)$o['account_id'],$o['receipt_id']));
    $counts=[];
    $rows=q($p,'SELECT receipt_id FROM signup_attempt_erasure_jobs WHERE state="queued" OR (state="waiting" AND next_attempt_at<=?) OR (state="claimed" AND claim_until<=?) ORDER BY due_at,receipt_id LIMIT '.$limit,[now($p),now($p)])->fetchAll(PDO::FETCH_COLUMN);
    foreach($rows as $receipt){$s=attempt($p,$receipt,$steps);$counts[$s]=($counts[$s]??0)+1;}
    foreach(q($p,'SELECT account_id,due_at FROM signup_attempt_erasure_jobs WHERE state<>"complete" AND due_at<=? ORDER BY due_at LIMIT '.$limit,[now($p)])->fetchAll(PDO::FETCH_ASSOC) as $j)\bushisa_signup_alert($p,(int)$j['account_id'],'SIGNUP_LIVE_CLEANUP_OVERDUE',$j['due_at']);
    return $counts;
}
/** Read-only adapter boundary; returned data are potentially personal protected
 * references, not anonymous data or independent publication acknowledgments. */
function recovery_instructions(PDO $p,string $after='',int $limit=25): array
{
    worker_guard();if($limit<1||$limit>100||($after!==''&&!preg_match('/\A[a-f0-9]{64}\z/D',$after)))throw new Failure('INVALID_RECOVERY_BOUND');
    return q($p,'SELECT receipt_id,account_id,attempt_key,account_version,action_name,effective_at,due_at,database_purged_at,live_purged_at,publication_state,copy_inventory_state,retain_until FROM signup_attempt_erasure_recovery WHERE receipt_id>? ORDER BY receipt_id LIMIT '.$limit,[$after])->fetchAll(PDO::FETCH_ASSOC);
}
