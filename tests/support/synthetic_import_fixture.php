<?php
declare(strict_types=1);
namespace Bushisa\SyntheticImport\Fixture;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../php/synthetic_legacy_import.php';
require_once __DIR__ . '/legacy_history_fixture.php';

use DateTimeImmutable;
use PDO;
use stdClass;
use function Bushisa\LegacyHistory\{clock, decode, digest, encode, evaluate, fact_id};
use function Bushisa\LegacyHistory\Fixture\{absent, catalog, event, fixture};
use function Bushisa\SyntheticImport\{capture_guard, guard_digest, now, query, sql_time};

function map_values(mixed $v, callable $f): mixed
{
    if ($v instanceof stdClass) { $r=new stdClass(); foreach(get_object_vars($v) as $k=>$x) { $r->{$k}=map_values($x,$f); } return $r; }
    if (is_array($v)) { return array_map(fn($x)=>map_values($x,$f),$v); }
    return $f($v);
}
function shift_clocks(mixed $v, int $seconds): mixed
{
    if ($v instanceof stdClass && property_exists($v,'precision') && is_string($v->value??null)) {
        $v=clone $v; $v->value=(new DateTimeImmutable($v->value))->modify(($seconds>=0?'+':'').$seconds.' seconds')->format(str_contains($v->value,'.')?'Y-m-d\TH:i:s.u\Z':'Y-m-d\TH:i:s\Z'); return $v;
    }
    if($v instanceof stdClass) { $r=new stdClass(); foreach(get_object_vars($v) as $k=>$x) { $r->{$k}=shift_clocks($x,$seconds); } return $r; }
    if(is_array($v)) { return array_map(fn($x)=>shift_clocks($x,$seconds),$v); } return $v;
}

function runtime_user(PDO $root, string $database): array
{
    $user='l02d_exec_'.bin2hex(random_bytes(5)); $password=bin2hex(random_bytes(24));
    // Bind to the server-observed client host. A local container service can
    // observe its bridge address instead of the CLI's literal loopback target.
    $clientHost=(string)$root->query("SELECT SUBSTRING_INDEX(USER(),'@',-1)")->fetchColumn();
    if(!preg_match('/\A[a-zA-Z0-9_.:-]{1,255}\z/D',$clientHost))throw new \RuntimeException('Unsafe synthetic SQL account host');
    query($root,"CREATE USER '$user'@'$clientHost' IDENTIFIED BY ?",[$password]);
    $db=\bushisa_quote_disposable_identifier($database); $account="'$user'@'$clientHost'";
    $root->exec("GRANT SELECT ON $db.* TO $account");
    $root->exec("GRANT LOCK TABLES ON $db.* TO $account");
    foreach(['synthetic_import_facts','synthetic_import_equivalences','synthetic_import_receipts','synthetic_import_keys'] as $table) { $root->exec("GRANT INSERT ON $db.$table TO $account"); }
    $root->exec("GRANT UPDATE (committed_receipts,stored_facts,stored_keys,stored_equivalences) ON $db.synthetic_import_pairs TO $account");
    $root->exec("GRANT UPDATE (request_count) ON $db.pair_request_counts TO $account");
    $root->exec("GRANT UPDATE (ending_count,version) ON $db.pair_policy_history TO $account");
    $expires=(new DateTimeImmutable(now($root)))->modify('+2 hours')->format('Y-m-d\TH:i:s.u\Z');
    $permit=(object)['format'=>'bushisa.synthetic-import-permit.v1','domain'=>'synthetic','host'=>getenv('DB_HOST'),'port'=>getenv('DB_PORT'),'database'=>$database,
        'user'=>$user,'incarnation'=>'syn:D','server_uuid'=>$root->query('SELECT @@server_uuid')->fetchColumn(),'nonce'=>bin2hex(random_bytes(32)),'expires_at'=>$expires,'test_controls'=>true];
    query($root,'INSERT INTO synthetic_import_destination VALUES (1,?,?,?,?)',[$permit->incarnation,hash('sha256',$permit->nonce),$permit->server_uuid,sql_time($expires)]);
    return [$permit,$password];
}

function seed_pair(PDO $root, string $runDir, stdClass $permit, string $tag, bool $route = false): array
{
    [$b,$s]=fixture();
    $oldId=fact_id($b->events[0]);
    $replace=['syn:P'=>'syn:P'.$tag,'syn:A1'=>'syn:A'.$tag,'syn:B1'=>'syn:B'.$tag,'syn:DA1'=>'syn:DA'.$tag,'syn:DB1'=>'syn:DB'.$tag,'syn:M1'=>'syn:M'.$tag,'syn:request121'=>'syn:request121-'.$tag];
    $b=map_values($b,fn($x)=>is_string($x)?($replace[$x]??$x):$x); $s=map_values($s,fn($x)=>is_string($x)?($replace[$x]??$x):$x);
    $newId=fact_id($b->events[0]); $b=map_values($b,fn($x)=>$x===$oldId?$newId:$x); $s=map_values($s,fn($x)=>$x===$oldId?$newId:$x);
    if(!$route) {
        $older=event('syn:historic99-'.$tag,99); $older->actor=$b->accounts[0]->source_ref; $older->recipient=$b->accounts[1]->source_ref;
        $b->events[]=$older;
    } else {
        $b->checkpoint->counts->low_to_high=0; $b->checkpoint->ending_count=0; $b->checkpoint->last_end=(object)['actor'=>null,'time'=>absent()];
        $s->state->blocks=[]; $s->state->probations=[]; $s->state->match->matched=true; $s->state->match->low_unmatched=false; $s->state->match->unmatched_at=absent();
        $s->state->chat->segments=[]; $s->state->current_request=null;
    }
    $s->state->counts->low_to_high=1; $s->state->ending_count=$route?0:1;
    $s->state->last_end=$route?(object)['actor'=>null,'time'=>absent()]:decode(encode($b->checkpoint->last_end));
    $delta=(new DateTimeImmutable(now($root)))->getTimestamp()-30-(new DateTimeImmutable('2026-10-16T10:00:00Z'))->getTimestamp();
    $b=shift_clocks($b,$delta); $s=shift_clocks($s,$delta);
    $users=[];
    foreach($s->state->accounts as $i=>$account) {
        query($root,'INSERT INTO users (nust_student_id,email,password_hash,is_verified,is_suspended,is_active,account_status,session_version,created_at) VALUES (?,?,?,1,0,1,"active",3,?)',
            ['L02d'.$tag.$i,'l02d'.$tag.$i.'@example.invalid',password_hash('Synthet1c!Test',PASSWORD_DEFAULT),sql_time(clock($account->created_at))]);
        $id=(int)$root->lastInsertId(); $users[]=$id;
        query($root,'INSERT INTO profiles (user_id,display_name,anon_handle,gender,date_of_birth) VALUES (?,?,?,?,"2000-01-01")',[$id,'Synthetic '.$tag.$i,'l02d_'.$tag.'_'.$i,$i===0?'female':'male']);
        query($root,'INSERT INTO preferences (user_id,preferred_gender) VALUES (?,?)',[$id,$i===0?'male':'female']);
    }
    [$low,$high]=$users;
    query($root,'INSERT INTO matches (user_a_id,user_b_id,match_status,matched_at) VALUES (?,?,?,UTC_TIMESTAMP())',[$low,$high,$route?'active':'unmatched']);
    $match=(int)$root->lastInsertId();
    $event=$s->native_events[0];
    if(!$route) {
        query($root,'INSERT INTO message_requests (match_id,requester_id,recipient_id,status,requested_at,expires_at,generation_key) VALUES (?,?,?,"pending",?,?,"synthetic")',[$match,$low,$high,sql_time(clock($event->time)),sql_time(clock($event->expires_at))]);
        $request=(int)$root->lastInsertId();
    } else { $request=0; }
    query($root,'INSERT INTO pair_request_events (event_key,match_id,generation,request_id,requester_id,recipient_id,requested_at,request_expires_at,status,purge_due_at,provenance) VALUES (?,?,1,?,?,?,?,?,"pending",?,"ledger_created")',
        [fact_id($event),$match,$request,$low,$high,sql_time(clock($event->time)),sql_time(clock($event->expires_at)),sql_time(clock($event->retain_until))]);
    query($root,'INSERT INTO pair_request_counts (requester_id,recipient_id,request_count) VALUES (?,?,1),(?,?,0)',[$low,$high,$high,$low]);
    query($root,'INSERT INTO pair_policy_history (user_low,user_high,match_id,history_state,version,generation,current_request_event_key,ending_count,last_ended_by,last_ended_at,chat_suppressed,low_unmatched,unmatched_at,created_at) VALUES (?,?,?,"legacy_incomplete",4,1,?,?,?,?,1,?,?,UTC_TIMESTAMP(6))',
        [$low,$high,$match,fact_id($event),$route?0:1,$route?null:$low,$route?null:sql_time(clock($s->state->last_end->time)),$route?0:1,$route?null:sql_time(clock($s->state->match->unmatched_at))]);
    if(!$route) {
        query($root,'INSERT INTO blocks (blocker_id,blocked_id) VALUES (?,?)',[$low,$high]); $block=(int)$root->lastInsertId();
        query($root,'INSERT INTO moderation_cases (reporter_id,target_type,target_id,reported_user_id,reason) VALUES (?,"match",?,?,"Synthetic allegation")',[$low,$match,$high]); $case=(int)$root->lastInsertId();
        query($root,'INSERT INTO report_block_links (block_id,case_id,submission_id,attached_at) VALUES (?,?,1,UTC_TIMESTAMP(6))',[$block,$case]);
        query($root,'INSERT INTO report_probation_periods (actor_id,trigger_submission_id,starts_at,expires_at) VALUES (?,?,?,?)',[$low,$low,sql_time(clock($s->state->probations[0]->start)),sql_time(clock($s->state->probations[0]->end))]);
    }
    $dir=$runDir.'/fixture-'.$tag; mkdir($dir,0700,true);
    $f=['b'=>$b,'s'=>$s,'permit'=>$permit,'dir'=>$dir,'low'=>$low,'high'=>$high,'match'=>$match,'key'=>bin2hex(random_bytes(32))];
    bind($root,$f); save($root,$f); return $f;
}

function bind(PDO $root, array &$f): void
{
    $f['s']->state->pair_version=(int)query($root,'SELECT version FROM pair_policy_history WHERE user_low=? AND user_high=?',[$f['low'],$f['high']])->fetchColumn();
    $f['s']->state->ending_count=(int)query($root,'SELECT ending_count FROM pair_policy_history WHERE user_low=? AND user_high=?',[$f['low'],$f['high']])->fetchColumn();
    foreach(['low_to_high'=>[$f['low'],$f['high']],'high_to_low'=>[$f['high'],$f['low']]] as $d=>$ids) { $f['s']->state->counts->{$d}=(int)query($root,'SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',$ids)->fetchColumn(); }
    $f['b']->expected->pair_version=$f['s']->state->pair_version; $f['b']->expected->fingerprint=digest($f['s'],'state');
    $guard=guard_digest(capture_guard($root,$f['low'],$f['high'],false));
    query($root,'INSERT INTO synthetic_import_pairs (pair_ref,user_low,user_high,match_id,binding_version,snapshot_json,snapshot_digest,guard_digest,expires_at) VALUES (?,?,?,?,1,?,?,?,?) ON DUPLICATE KEY UPDATE binding_version=binding_version+1,snapshot_json=VALUES(snapshot_json),snapshot_digest=VALUES(snapshot_digest),guard_digest=VALUES(guard_digest)',
        [$f['b']->pair_ref,$f['low'],$f['high'],$f['match'],encode($f['s']),digest($f['s'],'state'),$guard,sql_time($f['permit']->expires_at)]);
}
function save(PDO $root, array &$f): void
{
    $f['a']=live_catalog($f['b'],$f['s']); $f['plan']=evaluate($f['b'],$f['s'],$f['a'],now($root));
    if($f['plan']['outcome']!=='PLAN_NON_ENABLING') { throw new \RuntimeException('Synthetic fixture plan: '.encode($f['plan']['reasons'])); }
    foreach(['bundle'=>$f['b'],'snapshot'=>$f['s'],'catalog'=>$f['a'],'plan'=>$f['plan'],'permit'=>$f['permit']] as $name=>$value) { file_put_contents($f['dir'].'/'.$name.'.json',encode($value)); }
}

function live_catalog(stdClass $b, stdClass $s): stdClass
{
    // Keep the synthetic oracle's expiry relative to this run, not the old
    // offline fixture's fixed observation date. This attests no real custody.
    $a=catalog($b,$s); $a->expires_at=clone $s->expires_at;
    foreach($a->proofs as $proof) { $proof->expires_at=clone $s->expires_at; }
    return $a;
}
