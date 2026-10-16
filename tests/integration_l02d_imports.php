<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/synthetic_import_fixture.php';

use function Bushisa\LegacyHistory\{decode,digest,encode,fact_id};
use function Bushisa\SyntheticImport\{capture_guard,guard_digest,now,query};
use function Bushisa\SyntheticImport\Fixture\{bind,live_catalog,runtime_user,save,seed_pair};

$settings=bushisa_require_disposable_test_database('L02d integration setup');
if(!in_array($settings['host'],['127.0.0.1','::1'],true)||!preg_match('/\Abushisa_(?:test|ci)_l02d(?:_[a-z0-9]+)*\z/',$settings['name'])) { throw new RuntimeException('Local explicit L02d setup target required'); }
$rootPath=dirname(__DIR__); $runDir=$rootPath.'/.tmp/l02d-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)); mkdir($runDir,0700,true); mkdir($runDir.'/sessions',0700);
$started=gmdate('c'); $checks=[]; $overlaps=[]; $servers=[];
putenv('MAIL_PROVIDER=fake'); putenv('MAIL_TRANSPORT=fake'); putenv('BUSHISA_SYNTHETIC_IMPORT_ENABLE=true'); putenv('BUSHISA_SYNTHETIC_IMPORT_TEST_CONTROLS=true');
putenv('BUSHISA_TEST_HTTP_OVERLAP=true'); putenv('REPORT_CONTEXT_KEYS='.json_encode(['synthetic'=>base64_encode(random_bytes(32))])); putenv('REPORT_CONTEXT_ACTIVE_KEY=synthetic');
function check_ld(string $id,string $name,bool $ok): void { global $checks; $checks[]=['case'=>$id,'check'=>$name,'result'=>$ok?'PASS':'FAIL']; if(!$ok)throw new RuntimeException($id.': '.$name); }
function connect_ld(): PDO { $s=bushisa_require_disposable_test_database('L02d setup connection'); $p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$s['host'],$s['port'],$s['name']),$s['user'],$s['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_STRINGIFY_FETCHES=>true]); $p->exec("SET time_zone='+00:00'");return $p; }
function process_ld(array $args,array $env=[]): array {
    global $rootPath;
    $pipes=[];$p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$rootPath,array_merge(getenv(),$env));
    if(!is_resource($p))throw new RuntimeException('Child start failed'); fclose($pipes[0]);return [$p,$pipes];
}
function finish_ld(array $child): array { [$p,$pipes]=$child;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);return ['code'=>$code,'out'=>$out,'err'=>$err,'data'=>str_starts_with(trim($out),'{')?json_decode($out,true,512,JSON_THROW_ON_ERROR):null]; }
function cli_ld(array $args,array $env=[]): array { return finish_ld(process_ld($args,$env)); }
function migrate_ld(?string $through=null): string { global $rootPath;$r=cli_ld(array_merge([$rootPath.'/bin/migrate.php'],$through===null?[]:['--through='.$through]));if($r['code']!==0)throw new RuntimeException('Migration failed: '.$r['err']);return $r['out']; }
function db_digest_ld(PDO $p): string {
    $data=[];foreach($p->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) { $rows=$p->query('SELECT * FROM '.bushisa_quote_disposable_identifier($t))->fetchAll();usort($rows,fn($a,$b)=>encode($a)<=>encode($b));$data[$t]=$rows; } return digest($data,'synthetic-database-test-state');
}
function command_ld(array $f,?string $key=null,array $extra=[]): array { global $rootPath;return array_merge([$rootPath.'/bin/synthetic_legacy_import.php','import','--permit='.$f['dir'].'/permit.json','--bundle='.$f['dir'].'/bundle.json','--snapshot='.$f['dir'].'/snapshot.json','--catalog='.$f['dir'].'/catalog.json','--plan='.$f['dir'].'/plan.json','--key='.($key??$f['key'])],$extra); }
function exec_ld(array $f,array $extra=[],?string $key=null): array { global $runtimeEnv;return cli_ld(command_ld($f,$key,$extra),$runtimeEnv); }
function status_ld(array $f,?string $key=null): array { global $rootPath,$runtimeEnv;return cli_ld([$rootPath.'/bin/synthetic_legacy_import.php','status','--permit='.$f['dir'].'/permit.json','--pair='.$f['b']->pair_ref,'--key='.($key??$f['key'])],$runtimeEnv); }
function success_ld(array $r,string $id,string $name): array { check_ld($id,$name,$r['code']===0&&$r['err']===''&&($r['data']['outcome']??'')==='SYNTHETIC_IMPORT_COMMITTED');return $r['data']; }
function alternate_ld(PDO $root,array $f,string $tag,callable $change): array {
    $f['b']=decode(encode($f['b']));$f['s']=decode(encode($f['s']));$f['dir'].='-'.$tag;mkdir($f['dir'],0700,true);$change($f['b'],$f['s']);save($root,$f);return $f;
}
function rejected_ld(PDO $root,array $f,callable $change,string $id,string $name): void {
    $baseline=db_digest_ld($root);$bundleFile=$f['dir'].'/bundle.json';$catalogFile=$f['dir'].'/catalog.json';$bytes=file_get_contents($bundleFile);$aBytes=file_get_contents($catalogFile);$value=decode($bytes);$change($value);
    $a=live_catalog($value,$f['s']);file_put_contents($bundleFile,encode($value));file_put_contents($catalogFile,encode($a));
    try{$r=exec_ld($f);check_ld($id,$name,$r['code']===2&&$r['err']===''&&$baseline===db_digest_ld($root));}finally{file_put_contents($bundleFile,$bytes);file_put_contents($catalogFile,$aBytes);}
}
function parallel_ld(PDO $root,array $f,array $keys,?array $other=null,?callable $change=null): array {
    global $runtimeEnv,$overlaps;
    $barrier='l02d-'.bin2hex(random_bytes(16));$lock='bushisa-report:'.substr(hash('sha256',(string)$root->query('SELECT DATABASE()')->fetchColumn()),0,32);
    if((int)query($root,'SELECT GET_LOCK(?,5)',[$lock])->fetchColumn()!==1)throw new RuntimeException('Parent lock failed');
    try {
        $children=[process_ld(command_ld($f,$keys[0],['--barrier='.$barrier]),$runtimeEnv),process_ld(command_ld($other??$f,$keys[1],['--barrier='.$barrier]),$runtimeEnv)];
        $pattern=rtrim(sys_get_temp_dir(),'/\\').'/bushisa-l02d-barriers/'.$barrier.'-*.json';$until=microtime(true)+10;
        do { $files=glob($pattern)?:[];if(count($files)===2)break;usleep(20000); }while(microtime(true)<$until);
        $participants=array_map(fn($p)=>json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR),$files);
        $valid=count($participants)===2&&count(array_unique(array_column($participants,'pid')))===2&&count(array_unique(array_column($participants,'connection_id')))===2;
        if($valid&&$change!==null)$change();
    } finally { query($root,'SELECT RELEASE_LOCK(?)',[$lock]); }
    $results=array_map('finish_ld',$children);$overlaps[]=['barrier'=>$barrier,'participants'=>$participants,'results'=>array_map(fn($r)=>['code'=>$r['code'],'outcome'=>$r['data']['outcome']??null,'reason'=>$r['data']['reason']??null],$results)];
    if(!$valid)fwrite(STDERR,encode(end($overlaps))."\n");
    check_ld('LB28','Distinct importer PIDs AND actual database connection IDs reached pre-lock rendezvous',$valid);return $results;
}
function auth_ld(int $user): array {
    global $runDir;ini_set('session.save_path',$runDir.'/sessions');ini_set('session.use_cookies','0');session_cache_limiter('');session_id(bin2hex(random_bytes(24)));session_start();$csrf=bin2hex(random_bytes(32));$_SESSION=['user_id'=>$user,'session_version'=>3,'last_activity'=>time(),'csrf_token'=>$csrf];$id=session_id();session_write_close();return ['cookie'=>'PHPSESSID='.$id,'csrf'=>$csrf];
}
function http_ld(string $path,array $auth,?array $body=null): array {
    $c=curl_init('http://127.0.0.1:8117'.$path);$headers=['Accept: application/json','Cookie: '.$auth['cookie']];if($body!==null){$headers[]='Content-Type: application/json';$headers[]='X-CSRF-Token: '.$auth['csrf'];curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($body));}
    curl_setopt_array($c,[CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15]);$out=curl_exec($c);if($out===false)throw new RuntimeException('Synthetic HTTP failure');return ['code'=>curl_getinfo($c,CURLINFO_RESPONSE_CODE),'data'=>json_decode($out,true)];
}

try {
    bushisa_bootstrap_disposable_database($rootPath.'/database/bushisa.sql');
    check_ld('LD-M01','Fresh 001-008 migrations',str_contains(migrate_ld(),'Applying 008_synthetic_legacy_import.sql'));
    check_ld('LD-M02','Fresh repeat migration skips 008',str_contains(migrate_ld(),'Skipping 008_synthetic_legacy_import.sql'));
    $root=connect_ld();check_ld('LD-M01','Fresh schema has no import authorization or writer activation',(int)$root->query('SELECT COUNT(*) FROM synthetic_import_destination')->fetchColumn()===0&&$root->query('SELECT activated_at FROM pair_ledger_epoch')->fetchColumn()===null);
    $database=$settings['name'];putenv('DB_NAME='.$database.'_upgrade');bushisa_bootstrap_disposable_database($rootPath.'/database/bushisa.sql');migrate_ld('007');$up=connect_ld();
    query($up,'INSERT INTO users (nust_student_id,email,password_hash,is_verified) VALUES ("L02Dold","synthetic-old@example.invalid","synthetic-hash",1),("L02Dold2","synthetic-old2@example.invalid","synthetic-hash",1)');
    query($up,'INSERT INTO matches (user_a_id,user_b_id) VALUES (1,2)');
    query($up,'INSERT INTO pair_policy_history (user_low,user_high,match_id,history_state,version,generation,ending_count,chat_suppressed,created_at) VALUES (1,2,1,"legacy_incomplete",6,2,2,1,UTC_TIMESTAMP(6))');
    query($up,'INSERT INTO pair_request_counts (requester_id,recipient_id,request_count) VALUES (1,2,3),(2,1,1)');
    query($up,'INSERT INTO blocks (blocker_id,blocked_id) VALUES (1,2)');
    query($up,'INSERT INTO messages (match_id,sender_id,body) VALUES (1,1,"Retained synthetic upgrade text")');
    $old=[];foreach(['users','matches','pair_policy_history','pair_request_counts','blocks','messages'] as $t)$old[$t]=$up->query('SELECT * FROM '.$t)->fetchAll();
    $upgrade=migrate_ld();$same=true;foreach($old as $t=>$rows)$same=$same&&$rows===$up->query('SELECT * FROM '.$t)->fetchAll();
    check_ld('LD-M03','Upgrade preserves users, text, pair budgets, cutoff and blocks',str_contains($upgrade,'Applying 008_synthetic_legacy_import.sql')&&$same);
    check_ld('LD-M04','Upgrade repeat creates no marker or genesis',str_contains(migrate_ld(),'Skipping 008_synthetic_legacy_import.sql')&&(int)$up->query('SELECT COUNT(*) FROM synthetic_import_destination')->fetchColumn()===0&&(int)$up->query('SELECT COUNT(*) FROM pair_account_genesis')->fetchColumn()===0);
    $up=null;putenv('DB_NAME='.$database);$root=connect_ld();
    [$permit,$runtimePassword]=runtime_user($root,$database);
    $runtimeEnv=['DB_USER'=>$permit->user,'DB_PASSWORD'=>$runtimePassword,'DB_NAME'=>$database];
    $f=seed_pair($root,$runDir,$permit,'main');$before=db_digest_ld($root);
    $restricted=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$settings['host'],$settings['port'],$database),$permit->user,$runtimePassword,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    foreach(['UPDATE users SET session_version=session_version WHERE id='.$f['low'],'UPDATE blocks SET blocker_id=blocker_id WHERE blocker_id='.$f['low'],
        'UPDATE pair_policy_history SET history_state="complete_from_genesis" WHERE match_id='.$f['match'],'UPDATE pair_request_counts SET history_verified_at=UTC_TIMESTAMP(6) WHERE requester_id='.$f['low'],'CREATE TABLE synthetic_permission_probe (id INT)'] as $i=>$sql){
        $denied=false;try{$restricted->exec($sql);}catch(PDOException $e){$denied=in_array((int)($e->errorInfo[1]??0),[1142,1143],true);}check_ld('LB31','Executor SQL identity cannot mutate accounts/blocks/certification/DDL '.$i,$denied);
    }$restricted=null;
    rejected_ld($root,$f,fn($b)=>$b->checkpoint=null,'LB03','Incomplete source is rejected with whole database unchanged');
    rejected_ld($root,$f,function($b){$e=decode(encode($b->events[0]));$e->actor=$b->accounts[1]->source_ref;$e->recipient=$b->accounts[0]->source_ref;$b->events[]=$e;},'LB05','Contradictory immutable attribution has zero writes');
    rejected_ld($root,$f,fn($b)=>$b->format='unsupported','LB22','Unsupported input version has zero writes');
    foreach(['restored','mutual_matched','block_cleared','report_accepted','probation_started'] as $type){
        rejected_ld($root,$f,function($b)use($type){$e=decode(encode($b->events[1]));$e->identity->native_key='syn:unsupported-'.$type;$e->position=119;$e->type=$type;$e->expires_at=null;$b->events[]=$e;},$type==='report_accepted'||$type==='probation_started'?'LB17':'LB16','Unsupported action proof '.$type.' stays non-applying');
    }
    $bundleFile=$f['dir'].'/bundle.json';$bytes=file_get_contents($bundleFile);$baseline=db_digest_ld($root);file_put_contents($bundleFile,'{"a":1,"a":2}');$r=exec_ld($f);file_put_contents($bundleFile,$bytes);
    check_ld('LB22','Malformed duplicate-key JSON has zero database writes',$r['code']===2&&$baseline===db_digest_ld($root));
    $baseline=db_digest_ld($root);$badEnv=$runtimeEnv+[];$badEnv['APP_ENV']='production';$r=cli_ld(command_ld($f),$badEnv);
    check_ld('LB21','Unsafe production environment refuses before connection',($r['data']['reason']??'')==='DISPOSABLE_GUARDS_FAILED'&&$baseline===db_digest_ld($root));
    $permitFile=$f['dir'].'/permit.json';$permitBytes=file_get_contents($permitFile);$wrong=decode($permitBytes);$wrong->nonce=str_repeat('0',64);file_put_contents($permitFile,encode($wrong));$r=exec_ld($f);file_put_contents($permitFile,$permitBytes);
    check_ld('LB21','Wrong destination capability refuses after connection but before mutation',($r['data']['reason']??'')==='UNAUTHORIZED_SYNTHETIC_DESTINATION'&&$baseline===db_digest_ld($root));
    $probe=status_ld($f);
    check_ld('LB12','Unknown key status is NOT_COMMITTED without writes',($probe['data']['outcome']??'')==='NOT_COMMITTED'&&$before===db_digest_ld($root));
    $captureBefore=capture_guard($root,$f['low'],$f['high'],false);
    $result=success_ld(exec_ld($f),'LB08','First synthetic import commits');
    $captureAfter=capture_guard($root,$f['low'],$f['high'],false);
    foreach(['version','ending_count'] as $column)unset($captureBefore['history'][0][$column],$captureAfter['history'][0][$column]);
    foreach(['counts'] as $table){foreach($captureBefore[$table] as &$row)unset($row['request_count']);unset($row);foreach($captureAfter[$table] as &$row)unset($row['request_count']);unset($row);}
    check_ld('LB16','Only permitted floors/version change; grants, blocks, clearance, sessions, deadlines and all other captured metadata survive',encode($captureBefore)===encode($captureAfter));
    check_ld('LB17','Existing case and exact probation clocks survive without new reports or sanctions',encode($captureBefore['cases'])===encode($captureAfter['cases'])&&encode($captureBefore['probations'])===encode($captureAfter['probations']));
    check_ld('LB10','Permitted floor raises request count 1 to 3, not 4',(int)query($root,'SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$f['low'],$f['high']])->fetchColumn()===3);
    check_ld('LB31','Import leaves runtime legacy and writer epoch inactive',$root->query('SELECT activated_at FROM pair_ledger_epoch')->fetchColumn()===null&&query($root,'SELECT history_state FROM pair_policy_history WHERE match_id=?',[$f['match']])->fetchColumn()==='legacy_incomplete'&&(int)$root->query('SELECT COUNT(*) FROM pair_account_genesis')->fetchColumn()===0);
    $committed=db_digest_ld($root);$again=success_ld(exec_ld($f),'LB08','Identical retry resolves committed receipt before old pre-state');
    check_ld('LB08','Retry has identical receipt and zero duplicate effects',$again['receipt_ref']===$result['receipt_ref']&&$again['replayed']===true&&$committed===db_digest_ld($root));
    $reorder=function(mixed $value)use(&$reorder):mixed{if($value instanceof stdClass){$r=new stdClass();foreach(array_reverse(get_object_vars($value),true) as $k=>$v)$r->{$k}=$reorder($v);return $r;}return is_array($value)?array_map($reorder,$value):$value;};
    $bundleFile=$f['dir'].'/bundle.json';$catalogFile=$f['dir'].'/catalog.json';$originalBundle=file_get_contents($bundleFile);$originalCatalog=file_get_contents($catalogFile);
    $reformatted=json_encode($reorder($f['b']),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);$reorderedCatalog=decode($originalCatalog);$reorderedCatalog->raw_digests->bundle=[hash('sha256',$reformatted)];file_put_contents($bundleFile,$reformatted);file_put_contents($catalogFile,encode($reorderedCatalog));
    try{$reordered=success_ld(exec_ld($f),'LB08','Reordered and reformatted catalogued JSON returns existing receipt');check_ld('LB08','JSON representation cannot duplicate effects',$reordered['receipt_ref']===$result['receipt_ref']&&$committed===db_digest_ld($root));}finally{file_put_contents($bundleFile,$originalBundle);file_put_contents($catalogFile,$originalCatalog);}
    $newKey=bin2hex(random_bytes(32));$alias=success_ld(exec_ld($f,[],$newKey),'LB08','Different key for same bundle returns existing receipt');
    check_ld('LB12','New key alias can discover the original commit',(status_ld($f,$newKey)['data']['receipt_ref']??'')===$result['receipt_ref']);
    $copies=alternate_ld($root,$f,'copies',function($b,$s){$b->events[]=$b->events[0];});$baseline=db_digest_ld($root);$copyResult=success_ld(exec_ld($copies),'LB08','Duplicate source copies have same effect intent');
    check_ld('LB08','Duplicate copy retry creates no effect or key when original key used',$copyResult['receipt_ref']===$result['receipt_ref']&&$baseline===db_digest_ld($root));
    $changed=alternate_ld($root,$f,'intent',function($b,$s){$b->observations=[];});$baseline=db_digest_ld($root);$r=exec_ld($changed);
    check_ld('LB08','Same identifier with a different valid intent conflicts',($r['data']['reason']??'')==='IMPORT_KEY_CONFLICT'&&$baseline===db_digest_ld($root));
    $fresh=$f;bind($root,$fresh);save($root,$fresh);$fresh['key']=bin2hex(random_bytes(32));$baseline=db_digest_ld($root);$next=success_ld(exec_ld($fresh),'LB09','Fresh bundle can reuse already stored immutable facts');
    check_ld('LB09','Cross-bundle dedup stages zero repeated facts',$next['new_facts']===0&&(int)query($root,'SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$f['low'],$f['high']])->fetchColumn()===3);
    bind($root,$fresh);$conflicted=alternate_ld($root,$fresh,'fact',function($b,$s){$b->events[0]->retain_until=Bushisa\LegacyHistory\Fixture\time((new DateTimeImmutable($b->events[0]->retain_until->value))->modify('+1 hour')->format('Y-m-d\TH:i:s\Z'));$s->native_events[0]->retain_until=$b->events[0]->retain_until;$b->expected->fingerprint=digest($s,'state');});
    bind($root,$conflicted);save($root,$conflicted);$conflicted['key']=bin2hex(random_bytes(32));$baseline=db_digest_ld($root);$r=exec_ld($conflicted);
    check_ld('LB05','Stored immutable identity cannot acquire different expiry/content',($r['data']['reason']??'')==='STORED_FACT_CONFLICT'&&$baseline===db_digest_ld($root));
    $equiv=seed_pair($root,$runDir,$permit,'equiv');$oldIdentity=fact_id($equiv['b']->events[0]);$equiv['b']->events[0]->identity->native_key='syn:alternate121-equiv';
    $equiv['b']->equivalences=[(object)['left'=>fact_id($equiv['b']->events[0]),'right'=>$oldIdentity,'proof_ref'=>'syn:eq-proof']];save($root,$equiv);
    $er=success_ld(exec_ld($equiv),'LB09','Proved alternate native identity persists its equivalence atomically');
    check_ld('LB09','Two raw request identities plus one historical fact still produce lifetime count three',$er['new_facts']===3&&$er['request_counts']['low_to_high']===3&&(int)query($root,'SELECT COUNT(*) FROM synthetic_import_equivalences WHERE pair_ref=?',[$equiv['b']->pair_ref])->fetchColumn()===1);
    $baseline=db_digest_ld($root);$er2=success_ld(exec_ld($equiv),'LB09','Equivalence retry returns same receipt');
    check_ld('LB09','Equivalence retry has no duplicate fact/proof/floor effects',$er2['receipt_ref']===$er['receipt_ref']&&$baseline===db_digest_ld($root));
    foreach(['after_facts','after_counts','after_receipt','after_key'] as $n=>$point) {
        $fault=seed_pair($root,$runDir,$permit,'f'.$n);$baseline=db_digest_ld($root);$r=exec_ld($fault,['--fault='.$point]);
        check_ld('LB11','Actual rollback at '.$point,$r['code']===2&&($r['data']['reason']??'')==='INJECTED_'.strtoupper($point)&&$baseline===db_digest_ld($root));
        check_ld('LB11','No commit receipt after '.$point,(status_ld($fault)['data']['outcome']??'')==='NOT_COMMITTED');
        success_ld(exec_ld($fault),'LB11','Safe retry after rolled-back '.$point);
    }
    $retry=seed_pair($root,$runDir,$permit,'retry');$retried=success_ld(exec_ld($retry,['--fault=retry_once_after_facts']),'LB11','Bounded forced 40001 retry revalidates a rolled-back transaction');
    check_ld('LB11','Forced retry commits once on attempt two, not a real-deadlock claim',$retried['transaction_attempts']===2&&(int)query($root,'SELECT version FROM pair_policy_history WHERE match_id=?',[$retry['match']])->fetchColumn()===5);
    $lost=seed_pair($root,$runDir,$permit,'lost');$r=exec_ld($lost,['--fault=after_commit_exit']);
    check_ld('LB12','Actual CLI exits 75 without a response after commit',$r['code']===75&&$r['out']===''&&$r['err']==='');
    $recovered=success_ld(status_ld($lost),'LB12','Status discovers post-commit interrupted result');$lostState=db_digest_ld($root);
    check_ld('LB12','Interrupted client retry has no second effects',success_ld(exec_ld($lost),'LB12','Interrupted retry')['receipt_ref']===$recovered['receipt_ref']&&$lostState===db_digest_ld($root));
    $same=seed_pair($root,$runDir,$permit,'race');$rr=parallel_ld($root,$same,[$same['key'],$same['key']]);foreach($rr as $r)success_ld($r,'LB28','Concurrent same-key worker succeeds/replays');
    check_ld('LB28','Concurrent same-key commit increments version once',(int)query($root,'SELECT version FROM pair_policy_history WHERE match_id=?',[$same['match']])->fetchColumn()===5);
    $keys=seed_pair($root,$runDir,$permit,'keys');$rr=parallel_ld($root,$keys,[bin2hex(random_bytes(32)),bin2hex(random_bytes(32))]);
    check_ld('LB28','Concurrent different keys share one pair/bundle receipt',$rr[0]['code']===0&&$rr[1]['code']===0&&$rr[0]['data']['receipt_ref']===$rr[1]['data']['receipt_ref']);
    $compete=seed_pair($root,$runDir,$permit,'cmp');$other=alternate_ld($root,$compete,'other',fn($b,$s)=>$b->observations=[]);
    $rr=parallel_ld($root,$compete,[$compete['key'],$compete['key']],$other);$codes=array_column($rr,'code');sort($codes);
    check_ld('LB28','Concurrent conflicting same-key intents yield one commit and one conflict',$codes===[0,2]&&in_array('IMPORT_KEY_CONFLICT',array_map(fn($r)=>$r['data']['reason']??null,$rr),true));
    $compete=seed_pair($root,$runDir,$permit,'diff');$other=alternate_ld($root,$compete,'other',fn($b,$s)=>$b->observations=[]);
    $rr=parallel_ld($root,$compete,[$compete['key'],bin2hex(random_bytes(32))],$other);$codes=array_column($rr,'code');sort($codes);
    check_ld('LB13','Concurrent different bundles cannot overwrite the first newer commit',$codes===[0,2]&&in_array('STALE_LIVE_STATE',array_map(fn($r)=>$r['data']['reason']??null,$rr),true));
    $stale=seed_pair($root,$runDir,$permit,'stale');$rr=parallel_ld($root,$stale,[$stale['key'],$stale['key']],null,function()use($root,$stale){query($root,'UPDATE users SET session_version=session_version+1 WHERE id=?',[$stale['low']]);});
    foreach($rr as $r)check_ld('LB15','New session version committed before both guards rejects stale plan',$r['code']===2&&($r['data']['reason']??'')==='STALE_LIVE_STATE');
    $links=seed_pair($root,$runDir,$permit,'link');query($root,'UPDATE report_block_links SET cleared_at=UTC_TIMESTAMP(6),cleared_by=? WHERE block_id IN (SELECT id FROM blocks WHERE blocker_id=?)',[$links['high'],$links['low']]);$baseline=db_digest_ld($root);$r=exec_ld($links);
    check_ld('LB14','Report clearance change without pair bump rejects with zero writes',($r['data']['reason']??'')==='STALE_LIVE_STATE'&&$baseline===db_digest_ld($root));
    $expired=seed_pair($root,$runDir,$permit,'expiry');$expired['b']->provenance->expires_at=Bushisa\LegacyHistory\Fixture\time((new DateTimeImmutable(now($root)))->modify('-1 second')->format('Y-m-d\TH:i:s.u\Z'));
    $expired['a']=live_catalog($expired['b'],$expired['s']);$expired['plan']=Bushisa\LegacyHistory\evaluate($expired['b'],$expired['s'],$expired['a'],(new DateTimeImmutable(now($root)))->modify('-10 seconds')->format('Y-m-d\TH:i:s.u\Z'));
    check_ld('LB25','Source was valid at the old plan observation',$expired['plan']['outcome']==='PLAN_NON_ENABLING');
    foreach(['bundle'=>$expired['b'],'catalog'=>$expired['a'],'plan'=>$expired['plan']] as $name=>$value)file_put_contents($expired['dir'].'/'.$name.'.json',encode($value));$baseline=db_digest_ld($root);$r=exec_ld($expired);
    check_ld('LB25','Server time rejects source expired after planning without writes',($r['data']['reason']??'')==='SERVER_REVALIDATION_FAILED'&&$baseline===db_digest_ld($root));
    $timeout=seed_pair($root,$runDir,$permit,'timeout');$lock='bushisa-report:'.substr(hash('sha256',$database),0,32);$baseline=db_digest_ld($root);query($root,'SELECT GET_LOCK(?,5)',[$lock]);
    try{$r=exec_ld($timeout);}finally{query($root,'SELECT RELEASE_LOCK(?)',[$lock]);}
    check_ld('LB29','Actual five-second advisory-lock timeout leaves zero import writes',($r['data']['reason']??'')==='IMPORT_LOCK_TIMEOUT'&&$baseline===db_digest_ld($root));
    check_ld('LB29','Timed-out key is discoverably NOT_COMMITTED',(status_ld($timeout)['data']['outcome']??'')==='NOT_COMMITTED');
    $newer=seed_pair($root,$runDir,$permit,'newer');$nr=success_ld(exec_ld($newer),'LB32','Dedicated old-receipt recovery fixture');
    query($root,'UPDATE users SET is_suspended=1,session_version=session_version+1 WHERE id=?',[$newer['high']]);$baseline=db_digest_ld($root);$replay=success_ld(exec_ld($newer),'LB32','Old committed receipt resolves after a newer ban and session change');
    check_ld('LB32','Recovery returns metadata only and preserves newer ban/session state',$replay['receipt_ref']===$nr['receipt_ref']&&$replay['grants_chat']===false&&$replay['changes_sessions']===false&&$baseline===db_digest_ld($root));
    $corrupt=seed_pair($root,$runDir,$permit,'badrc');$cr=success_ld(exec_ld($corrupt),'LB12','Dedicated receipt integrity fixture');
    query($root,'UPDATE synthetic_import_receipts SET result_json=? WHERE receipt_ref=?',[encode(['grants_chat'=>true]),$cr['receipt_ref']]);$baseline=db_digest_ld($root);$r=status_ld($corrupt);
    check_ld('LB27','Corrupt receipt is unavailable and cannot convey a grant',$r['code']===2&&($r['data']['grants_chat']??true)===false&&$baseline===db_digest_ld($root));
    foreach(['synthetic_import_facts','synthetic_import_receipts','synthetic_import_keys'] as $i=>$table){
        $gap=seed_pair($root,$runDir,$permit,'gap'.$i);success_ld(exec_ld($gap),'LB27','Local registry gap fixture '.$i);query($root,'DELETE FROM '.$table.' WHERE pair_ref=?',[$gap['b']->pair_ref]);$baseline=db_digest_ld($root);$r=exec_ld($gap);
        check_ld('LB27','Premature detail/receipt/key purge fails closed '.$i,($r['data']['reason']??'')==='IMPORT_CONTINUITY_MISSING'&&$baseline===db_digest_ld($root));
    }
    $batch=seed_pair($root,$runDir,$permit,'batch');$br=success_ld(exec_ld($batch),'LB30','First pair of a caller-managed batch commits');$baseline=db_digest_ld($root);
    $batchBad=seed_pair($root,$runDir,$permit,'batchbad');$baseline=db_digest_ld($root);
    rejected_ld($root,$batchBad,fn($b)=>$b->checkpoint=null,'LB30','Rejected different pair does not undo an earlier committed pair');
    check_ld('LB30','Earlier pair remains discoverable and rejected pair unchanged',(status_ld($batch)['data']['receipt_ref']??'')===$br['receipt_ref']&&$baseline===db_digest_ld($root));
    $route=seed_pair($root,$runDir,$permit,'route',true);success_ld(exec_ld($route),'LB31','Unblocked synthetic route fixture imports without certification');
    foreach([8117,8118] as $port) {
        $socket=@fsockopen('127.0.0.1',$port,$errNo,$error,0.1);if($socket){fclose($socket);throw new RuntimeException('Occupied HTTP test port');}
        $pipes=[];$p=proc_open([PHP_BINARY,'-d','session.save_path='.$runDir.'/sessions','-S','127.0.0.1:'.$port,'-t',$rootPath],[0=>['pipe','r'],1=>['file',$runDir.'/http-'.$port.'.log','a'],2=>['file',$runDir.'/http-'.$port.'.log','a']],$pipes,$rootPath);if(!is_resource($p))throw new RuntimeException('HTTP start failed');fclose($pipes[0]);$servers[]=$p;
    }
    $until=microtime(true)+10;do{$socket=@fsockopen('127.0.0.1',8117,$errNo,$error,0.1);if($socket){fclose($socket);break;}usleep(50000);}while(microtime(true)<$until);
    $auth=auth_ld($route['low']);$r=http_ld('/api/message_requests.php',$auth,['action'=>'request','match_id'=>$route['match'],'pair_version'=>5,'idempotency_key'=>bin2hex(random_bytes(32))]);
    check_ld('LB31','Real request route denies imported legacy history with unused budget',$r['code']===503&&($r['data']['error']??'')==='Verified original pair history is required');
    $r=http_ld('/api/messages.php?match_id='.$route['match'],$auth);
    check_ld('LB31','Real message route grants no chat from imported facts',$r['code']===403);
    $authMain=auth_ld($f['low']);$oldOnly=fact_id($f['b']->events[1]);$r=http_ld('/api/message_requests.php?match_id='.$f['match'].'&event_key='.$oldOnly,$authMain);
    check_ld('LB31','Imported private fact cannot mint an ordinary report proof',$r['code']===404);
    $pairVersion=(int)query($root,'SELECT version FROM pair_policy_history WHERE match_id=?',[$f['match']])->fetchColumn();
    $blockId=(int)query($root,'SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?',[$f['low'],$f['high']])->fetchColumn();
    $r=http_ld('/api/safety.php',$authMain,['action'=>'unblock','user_id'=>$f['high'],'block_id'=>$blockId,'pair_version'=>$pairVersion,'idempotency_key'=>bin2hex(random_bytes(32))]);
    check_ld('LB16','Real creator-unblock route still requires exact current reviewer clearance',$r['code']===409&&($r['data']['error']??'')==='Reviewer clearance is required before removing this block'&&(int)query($root,'SELECT COUNT(*) FROM blocks WHERE blocker_id=? AND blocked_id=?',[$f['low'],$f['high']])->fetchColumn()===1);
    $c=curl_init('http://127.0.0.1:8117/bin/synthetic_legacy_import.php');curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10]);$body=curl_exec($c);$code=curl_getinfo($c,CURLINFO_RESPONSE_CODE);
    check_ld('LB21','Executor is unavailable through actual HTTP',$code===404&&$body==='');
    check_ld('LB17','No authentication mail queued by imports',(int)$root->query('SELECT COUNT(*) FROM mail_outbox')->fetchColumn()===0);
    $sources=[];foreach(['database/migrations/008_synthetic_legacy_import.sql','php/synthetic_legacy_import.php','bin/synthetic_legacy_import.php','tests/support/synthetic_import_fixture.php','tests/integration_l02d_imports.php'] as $file){$sources[$file]=hash('sha256',str_replace("\r\n","\n",file_get_contents($rootPath.'/'.$file)));}
    $evidence=['format'=>'bushisa.l02d-execution.v1','started_at'=>$started,'ended_at'=>gmdate('c'),'php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$root->query('SELECT VERSION()')->fetchColumn(),'database'=>$database,'root_connection_id'=>$root->query('SELECT CONNECTION_ID()')->fetchColumn(),'checks'=>$checks,'assertion_count'=>count($checks),'overlaps'=>$overlaps,'sources_lf_sha256'=>$sources,'production_authenticity'=>'NOT_VERIFIED'];
    file_put_contents($runDir.'/execution.json',json_encode($evidence,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
    fwrite(STDOUT,'integration_l02d_imports PASS: '.count($checks).' assertions; evidence '.$runDir.'/execution.json'."\n");
} finally {
    foreach($servers as $p){proc_terminate($p);proc_close($p);}
}
