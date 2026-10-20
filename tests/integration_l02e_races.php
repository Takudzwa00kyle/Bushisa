<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/support/l02e_fixture.php';
use function Bushisa\LegacyHistory\{decode,digest,encode};
use function Bushisa\SyntheticImport\{capture_guard,now,query};
use function Bushisa\SyntheticImport\Fixture\{bind,runtime_user,save,seed_pair};
use function Bushisa\ImportRaceFixture\{scenario,web_user};

$settings=bushisa_require_disposable_test_database('L02e synthetic fixture');
if(!in_array($settings['host'],['127.0.0.1','::1'],true)||!preg_match('/\Abushisa_(?:test|ci)_l02d_e[a-z0-9]*(?:_[a-z0-9]+)*\z/D',$settings['name'])||getenv('MAIL_PROVIDER')!=='fake')throw new RuntimeException('Explicit local L02e synthetic target/fake mail required');
$rootPath=dirname(__DIR__);$runDir=$rootPath.'/.tmp/l02e-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));mkdir($runDir,0700,true);mkdir($runDir.'/sessions',0700);
$started=gmdate('c');$checks=[];$races=[];$engineTrials=[];$servers=[];$environment=[];$failure=null;
foreach(['MAIL_TRANSPORT'=>'fake','BUSHISA_SYNTHETIC_IMPORT_ENABLE'=>'true','BUSHISA_SYNTHETIC_IMPORT_TEST_CONTROLS'=>'true','BUSHISA_TEST_HTTP_OVERLAP'=>'true','REPORT_CONTEXT_KEYS'=>json_encode(['synthetic'=>base64_encode(random_bytes(32))]),'REPORT_CONTEXT_ACTIVE_KEY'=>'synthetic'] as $k=>$v)putenv($k.'='.$v);
putenv('PHP_CLI_SERVER_WORKERS');
function check_le(string $case,string $name,bool $ok): void{global $checks;$checks[]=['case'=>$case,'check'=>$name,'result'=>$ok?'PASS':'FAIL'];if(!$ok)throw new RuntimeException($case.': '.$name);}
function connect_le(): PDO{$s=bushisa_require_disposable_test_database('L02e fixture/monitor');$p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$s['host'],$s['port'],$s['name']),$s['user'],$s['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_STRINGIFY_FETCHES=>true]);$p->exec("SET time_zone='+00:00'");return $p;}
function child_le(array $args,array $env=[]): array{global $rootPath;$pipes=[];$p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$rootPath,array_merge(getenv(),$env));if(!is_resource($p))throw new RuntimeException('Child start');fclose($pipes[0]);return [$p,$pipes];}
function finish_le(array $c): array{[$p,$pipes]=$c;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);return ['code'=>$exit,'err'=>$err,'json'=>str_starts_with(trim($out),'{')?json_decode($out,true,512,JSON_THROW_ON_ERROR):null,'stdout'=>$out];}
function import_args_le(array $f,?string $id=null,string $mode='mixed'): array{global $rootPath;$args=[$rootPath.'/bin/synthetic_legacy_import.php','import','--permit='.$f['dir'].'/permit.json','--bundle='.$f['dir'].'/bundle.json','--snapshot='.$f['dir'].'/snapshot.json','--catalog='.$f['dir'].'/catalog.json','--plan='.$f['dir'].'/plan.json','--key='.$f['key']];if($id!==null)$args=array_merge($args,['--test-id='.$id,'--test-mode='.$mode]);return $args;}
function import_le(array $f): array{global $importEnv;return finish_le(child_le(import_args_le($f),$importEnv));}
function session_le(int $actor,bool $mfa=false): array{global $runDir;ini_set('session.save_path',$runDir.'/sessions');ini_set('session.use_cookies','0');session_cache_limiter('');session_id(bin2hex(random_bytes(24)));session_start();$csrf=bin2hex(random_bytes(32));$_SESSION=['user_id'=>$actor,'session_version'=>3,'last_activity'=>time(),'csrf_token'=>$csrf,'mfa_verified'=>$mfa,'mfa_user_id'=>$actor,'mfa_session_version'=>3];$id=session_id();session_write_close();return ['cookie'=>'PHPSESSID='.$id,'csrf'=>$csrf];}
function curl_le(int $port,string $path,array $auth,?array $body=null,array $extra=[]): CurlHandle{$c=curl_init('http://127.0.0.1:'.$port.$path);$headers=['Accept: application/json','Cookie: '.$auth['cookie']];if($body!==null){$headers[]='Content-Type: application/json';$headers[]='X-CSRF-Token: '.$auth['csrf'];curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));}curl_setopt_array($c,[CURLOPT_HTTPHEADER=>array_merge($headers,$extra),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_FOLLOWLOCATION=>false]);return $c;}
function response_le(CurlHandle $c,string|false $body): array{if($body===false)throw new RuntimeException('HTTP transport '.curl_error($c));return ['code'=>curl_getinfo($c,CURLINFO_RESPONSE_CODE),'json'=>json_decode($body,true)];}
function http_le(string $path,array $auth,?array $body=null): array{$c=curl_le(8127,$path,$auth,$body);return response_le($c,curl_exec($c));}
function data_le(array $r): array{if($r['code']!==200||!is_array($r['json']['data']??null))throw new RuntimeException('Fixture preparation HTTP '.$r['code'].' '.($r['json']['error']??''));return $r['json']['data'];}
function pump_le(CurlMultiHandle $m): int{do{$code=curl_multi_exec($m,$running);}while($code===CURLM_CALL_MULTI_PERFORM);if($code!==CURLM_OK)throw new RuntimeException('Multi HTTP');return $running;}
function until_le(callable $condition,?CurlMultiHandle $m=null,int $milliseconds=10000): void{$end=microtime(true)+$milliseconds/1000;do{if($m!==null)pump_le($m);if($condition())return;usleep(10000);}while(microtime(true)<$end);throw new RuntimeException('Synthetic rendezvous deadline');}
function finish_http_le(CurlMultiHandle $m,array $handles): array{until_le(fn()=>pump_le($m)===0,$m,15000);$r=[];foreach($handles as $h){$r[]=response_le($h,curl_multi_getcontent($h));curl_multi_remove_handle($m,$h);}curl_multi_close($m);return $r;}
function barrier_dir_le(): string{return rtrim(sys_get_temp_dir(),'/\\').'/bushisa-http-barriers';}
function control_base_le(string $id): string{$dir=rtrim(sys_get_temp_dir(),'/\\').'/bushisa-l02e-controls';if(!is_dir($dir))mkdir($dir,0700,true);return $dir.'/'.$id;}
function unlock_gate_le(string $id,int $pid): void{file_put_contents(barrier_dir_le().'/'.$id.'_l02e_mixed-'.$pid.'.release','release');}
function named_waits_le(PDO $p,string $name,array $ids): array{if($ids===[])return [];$slots=implode(',',array_fill(0,count($ids),'?'));return query($p,"SELECT t.PROCESSLIST_ID AS connection_id,l.LOCK_STATUS AS status FROM performance_schema.metadata_locks l JOIN performance_schema.threads t ON t.THREAD_ID=l.OWNER_THREAD_ID WHERE l.OBJECT_TYPE='USER LEVEL LOCK' AND l.OBJECT_NAME=? AND l.LOCK_STATUS='PENDING' AND t.PROCESSLIST_ID IN ($slots)",array_merge([$name],$ids))->fetchAll();}
function row_waits_le(PDO $p,int $requester,int $blocker): array{return query($p,'SELECT rt.PROCESSLIST_ID AS requester,bt.PROCESSLIST_ID AS blocker,l.OBJECT_SCHEMA,l.OBJECT_NAME,l.LOCK_TYPE,l.LOCK_MODE FROM performance_schema.data_lock_waits w JOIN performance_schema.threads rt ON rt.THREAD_ID=w.REQUESTING_THREAD_ID JOIN performance_schema.threads bt ON bt.THREAD_ID=w.BLOCKING_THREAD_ID JOIN performance_schema.data_locks l ON l.ENGINE_LOCK_ID=w.REQUESTING_ENGINE_LOCK_ID WHERE rt.PROCESSLIST_ID=? AND bt.PROCESSLIST_ID=?',[$requester,$blocker])->fetchAll();}
function pair_state_le(PDO $p,array $f): string{$state=capture_guard($p,$f['low'],$f['high'],false);foreach(['synthetic_import_facts','synthetic_import_equivalences','synthetic_import_receipts','synthetic_import_keys','synthetic_import_pairs'] as $table){$rows=query($p,'SELECT * FROM '.$table.' WHERE pair_ref=?',[$f['b']->pair_ref])->fetchAll();usort($rows,fn($a,$b)=>encode($a)<=>encode($b));$state[$table]=$rows;}return digest($state,'l02e-atomic-state');}

/** Both HTTP workers and the importer have live, separately identified SQL connections. */
function mixed_le(PDO $root,array $f,array $a,array $b,string $kind,string $schedule): array
{
    global $importEnv,$races;
    $id='l02e_'.bin2hex(random_bytes(16));$lock='bushisa-report:'.substr(hash('sha256',(string)$root->query('SELECT DATABASE()')->fetchColumn()),0,32);
    if((int)query($root,'SELECT GET_LOCK(?,5)',[$lock])->fetchColumn()!==1)throw new RuntimeException('Parent mutex');
    $m=curl_multi_init();$handles=[];$participants=[];$waits=[];
    $headers=['X-Bushisa-Test-Barrier: '.$id,'X-Bushisa-Test-Barrier-Point: l02e_mixed','X-Bushisa-Test-Barrier-Expected: 3','X-Bushisa-Test-Barrier-Timeout-Ms: 15000','X-Bushisa-Test-Gate: true'];
    foreach([[$a,8127],[$b,8128]] as [$request,$port]){[$path,$auth,$body]=$request;$h=curl_le($port,$path,$auth,$body,$headers);curl_multi_add_handle($m,$h);$handles[]=$h;}
    $child=child_le(import_args_le($f,$id),$importEnv);
    try{
        $files=[];until_le(function()use(&$files,$id){$files=glob(barrier_dir_le().'/'.$id.'_l02e_mixed-*.json')?:[];return count($files)===3;},$m);
        $participants=array_map(fn($file)=>json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR),$files);
        check_le('LB29',$kind.'/'.$schedule.' has three distinct PIDs AND SQL connections',count(array_unique(array_column($participants,'pid')))===3&&count(array_unique(array_column($participants,'connection_id')))===3);
        $web=array_values(array_filter($participants,fn($p)=>$p['sapi']!=='cli'));$cli=array_values(array_filter($participants,fn($p)=>$p['sapi']==='cli'))[0];
        foreach($web as $p)unlock_gate_le($id,$p['pid']);
        if($schedule==='all_queued')unlock_gate_le($id,$cli['pid']);
        $ids=array_column($schedule==='all_queued'?$participants:$web,'connection_id');
        until_le(function()use($root,$lock,$ids,&$waits){$waits=named_waits_le($root,$lock,$ids);return count($waits)===count($ids);},$m,4000);
        check_le('LB29',$kind.'/'.$schedule.' actual engine-observed pending named locks',count($waits)===count($ids));
        query($root,'SELECT RELEASE_LOCK(?)',[$lock]);
        $responses=finish_http_le($m,$handles);$m=null;
        if($schedule==='http_first')unlock_gate_le($id,$cli['pid']);
        $import=finish_le($child);$child=null;
        check_le('LB29',$kind.'/'.$schedule.' importer committed or rejected stale state',in_array($import['code'],[0,2],true)&&$import['err']===''&&($import['code']===0||in_array($import['json']['reason']??'',['STALE_LIVE_STATE','LIVE_ACCOUNT_VERSION'],true)));
        $races[]=['kind'=>$kind,'schedule'=>$schedule,'barrier'=>$id,'participants'=>$participants,'observed_named_lock_waits'=>$waits,'http'=>array_map(fn($r)=>['status'=>$r['code'],'error'=>$r['json']['error']??null],$responses),'import'=>['exit'=>$import['code'],'outcome'=>$import['json']['outcome']??null,'reason'=>$import['json']['reason']??null,'resulting_pair_version'=>$import['json']['resulting_pair_version']??null]];
        return [$responses,$import];
    }finally{
        query($root,'SELECT RELEASE_LOCK(?)',[$lock]);foreach($participants as $p)unlock_gate_le($id,$p['pid']);
        if($m instanceof CurlMultiHandle){foreach($handles as $h)curl_multi_remove_handle($m,$h);curl_multi_close($m);}if(is_array($child)){proc_terminate($child[0]);finish_le($child);}
    }
}

function engine_le(PDO $root,stdClass $permit,string $mode,int $n): void
{
    global $runDir,$importEnv,$engineTrials;
    $f=seed_pair($root,$runDir,$permit,'eg'.$n);$probe=seed_pair($root,$runDir,$permit,'pr'.$n);$baseline=pair_state_le($root,$f);
    $opponent=connect_le();$monitor=connect_le();$opponentId=(int)$opponent->query('SELECT CONNECTION_ID()')->fetchColumn();
    $id='l02e_'.bin2hex(random_bytes(16));$base=control_base_le($id);file_put_contents($base.'-control.json',encode(['format'=>'bushisa.synthetic-lock-probe.v1','database'=>$permit->database,'probe_pair'=>$probe['b']->pair_ref]));
    $child=child_le(import_args_le($f,$id,$mode),$importEnv);$attempts=str_ends_with($mode,'_exhaust')?3:1;$errors=[];$observations=[];
    try{
        for($attempt=1;$attempt<=$attempts;$attempt++){
            until_le(fn()=>is_file($base.'-ready-'.$attempt.'.json'));$ready=json_decode(file_get_contents($base.'-ready-'.$attempt.'.json'),true,512,JSON_THROW_ON_ERROR);
            $opponent->beginTransaction();query($opponent,'SELECT user_low FROM pair_policy_history WHERE user_low=? AND user_high=? FOR UPDATE',[$probe['low'],$probe['high']])->fetchColumn();
            if(str_starts_with($mode,'deadlock'))$opponent->exec('UPDATE l02e_deadlock_weight SET weight=weight+1');
            file_put_contents($base.'-proceed-'.$attempt,'proceed');$wait=[];
            until_le(function()use($monitor,$ready,$opponentId,&$wait){$wait=row_waits_le($monitor,$ready['connection_id'],$opponentId);return $wait!==[];},null,800);
            $isolation=query($monitor,'SELECT e.ISOLATION_LEVEL FROM performance_schema.events_transactions_current e JOIN performance_schema.threads t ON t.THREAD_ID=e.THREAD_ID WHERE t.PROCESSLIST_ID=?',[$ready['connection_id']])->fetchColumn();
            check_le('LE-C08',$mode.' actual transaction uses SERIALIZABLE',$isolation==='SERIALIZABLE');
            if(str_starts_with($mode,'deadlock')){
                // Actual reversed row dependency: opponent waits for importer's
                // destination marker; importer already waits for opponent's pair.
                query($opponent,'SELECT singleton FROM synthetic_import_destination WHERE singleton=1 FOR UPDATE')->fetchColumn();$opponent->commit();
            }
            until_le(fn()=>is_file($base.'-error-'.$attempt.'.json'));$error=json_decode(file_get_contents($base.'-error-'.$attempt.'.json'),true,512,JSON_THROW_ON_ERROR);$errors[]=$error;
            $expected=str_starts_with($mode,'deadlock')?1213:1205;
            check_le('LB29',$mode.' genuine engine errno on attempt '.$attempt,$error['errno']===$expected&&$error['sqlstate']===($expected===1213?'40001':'HY000'));
            $observations[]=['attempt'=>$attempt,'ready'=>$ready,'row_waits'=>$wait,'observed_transaction_isolation'=>$isolation];
            if($opponent->inTransaction())$opponent->rollBack();
        }
        $result=finish_le($child);$child=null;$exhaust=str_ends_with($mode,'_exhaust');
        if($exhaust){check_le('LB11',$mode.' bounded at three and atomic zero effects',$result['code']===2&&($result['json']['reason']??'')==='IMPORT_RETRY_EXHAUSTED'&&pair_state_le($root,$f)===$baseline);}
        else{check_le('LB11',$mode.' retries real engine error and commits exactly once',$result['code']===0&&($result['json']['transaction_attempts']??0)===2&&(int)query($root,'SELECT version FROM pair_policy_history WHERE match_id=?',[$f['match']])->fetchColumn()===5);}
        $committed=pair_state_le($root,$f);$retry=import_le($f);
        check_le('LB12',$mode.' safe retry resolves existing or previously rolled-back outcome',$retry['code']===0&&($exhaust||(($retry['json']['replayed']??false)===true&&$committed===pair_state_le($root,$f))));
        check_le('LB28',$mode.' duplicate-free fact/receipt/key effects',(int)query($root,'SELECT COUNT(*) FROM synthetic_import_receipts WHERE pair_ref=?',[$f['b']->pair_ref])->fetchColumn()===1&&(int)query($root,'SELECT COUNT(*) FROM synthetic_import_facts WHERE pair_ref=?',[$f['b']->pair_ref])->fetchColumn()===2);
        $status=$root->query('SHOW ENGINE INNODB STATUS')->fetch(PDO::FETCH_ASSOC)['Status'];
        $engineTrials[]=['mode'=>$mode,'opponent_connection_id'=>$opponentId,'errors'=>$errors,'observations'=>$observations,'result'=>['exit'=>$result['code'],'outcome'=>$result['json']['outcome']??null,'reason'=>$result['json']['reason']??null,'transaction_attempts'=>$result['json']['transaction_attempts']??null],
            'innodb_status_sha256'=>hash('sha256',$status),'deadlock_section_observed'=>str_contains($status,'LATEST DETECTED DEADLOCK'),'status_mentions_import_connection'=>str_contains($status,'MySQL thread id '.$errors[0]['connection_id'].','),'status_mentions_opponent_connection'=>str_contains($status,'MySQL thread id '.$opponentId.',')];
        if(str_starts_with($mode,'deadlock')){ $observed=end($engineTrials);check_le('LB29',$mode.' engine deadlock section identifies both real SQL connections',$observed['deadlock_section_observed']&&$observed['status_mentions_import_connection']&&$observed['status_mentions_opponent_connection']); }
    }finally{if($opponent->inTransaction())$opponent->rollBack();if(is_array($child)){proc_terminate($child[0]);finish_le($child);}}
}

function health_checks_le(PDO $root,stdClass $permit,array $web): void
{
    global $settings,$runDir,$rootPath;
    $f=scenario($root,$runDir,$permit,'health','new_request');$auth=session_le($f['low']);
    $body=['action'=>'request','match_id'=>$f['native_match'],'pair_version'=>0,'idempotency_key'=>bin2hex(random_bytes(32))];$before=pair_state_le($root,$f);
    $app=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$settings['host'],$settings['port'],$settings['name']),$web['user'],$web['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $hidden=(int)$app->query("SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema=DATABASE() AND trigger_name='bushisa_pair_account_genesis'")->fetchColumn();
    $app->beginTransaction();$call=$app->query('CALL bushisa_pair_trigger_health()');$present=(int)$call->fetchColumn();$call->closeCursor();$stillAtomic=$app->inTransaction();$app->rollBack();
    check_le('LE-C03','Restricted runtime gets live health without TRIGGER and CALL preserves transaction',$hidden===0&&$present===1&&$stillAtomic);
    $db=bushisa_quote_disposable_identifier($settings['name']);$account=$web['account'];
    $root->exec("REVOKE EXECUTE ON PROCEDURE $db.bushisa_pair_trigger_health FROM $account");
    try{$r=http_le('/api/message_requests.php',$auth,$body);check_le('LE-C04','Missing health EXECUTE fails closed atomically',$r['code']===503&&pair_state_le($root,$f)===$before);}finally{$root->exec("GRANT EXECUTE ON PROCEDURE $db.bushisa_pair_trigger_health TO $account");}
    $root->exec('DROP TRIGGER bushisa_pair_account_genesis');
    try{$r=http_le('/api/message_requests.php',$auth,$body);check_le('LE-C05','Removed live trigger cannot be hidden by old genesis records',$r['code']===503&&pair_state_le($root,$f)===$before);}finally{require_once $rootPath.'/php/operational.php';require_once $rootPath.'/php/pair_migration.php';bushisa_migrate_pair_history($root);}
    $root->exec('DROP PROCEDURE bushisa_pair_trigger_health');
    try{$r=http_le('/api/message_requests.php',$auth,$body);check_le('LE-C06','Missing health routine fails closed atomically',$r['code']===503&&pair_state_le($root,$f)===$before);}finally{$sql=file_get_contents($rootPath.'/database/migrations/009_pair_trigger_health.sql');$root->exec(str_replace("'__BUSHISA_CURRENT_SCHEMA__'",$root->quote($settings['name']),$sql));$root->exec("GRANT EXECUTE ON PROCEDURE $db.bushisa_pair_trigger_health TO $account");}
    query($root,'DELETE FROM schema_migrations WHERE version="009_pair_trigger_health"');$resume=finish_le(child_le([$rootPath.'/bin/migrate.php']));
    check_le('LE-M05','Exact partial 009 retry preserves the existing routine and its grants',$resume['code']===0);
    $r=http_le('/api/message_requests.php',$auth,$body);check_le('LE-C07','Exact routine capability and actual trigger permit native request without enabling legacy pair',$r['code']===200&&query($root,'SELECT history_state FROM pair_policy_history WHERE match_id=?',[$f['match']])->fetchColumn()==='legacy_incomplete');
}

function deletion_le(PDO $root,stdClass $permit): void
{
    global $runDir,$importEnv,$races;
    $f=scenario($root,$runDir,$permit,'del','delete');$a=session_le($f['low']);$b=session_le($f['high']);
    $id='l02e_'.bin2hex(random_bytes(16));$base=control_base_le($id);$lock='bushisa-report:'.substr(hash('sha256',$permit->database),0,32);query($root,'SELECT GET_LOCK(?,5)',[$lock]);
    $m=curl_multi_init();$headers=['X-Bushisa-Test-Barrier: '.$id,'X-Bushisa-Test-Barrier-Point: l02e_mixed','X-Bushisa-Test-Barrier-Expected: 3','X-Bushisa-Test-Barrier-Timeout-Ms: 15000','X-Bushisa-Test-Gate: true'];
    $h1=curl_le(8127,'/api/account.php',$a,['action'=>'delete_account','password'=>'Synthet1c!Test'],$headers);
    $h2=curl_le(8128,'/api/message_requests.php',$b,['action'=>'request','match_id'=>$f['match'],'pair_version'=>4,'idempotency_key'=>bin2hex(random_bytes(32))],$headers);
    curl_multi_add_handle($m,$h1);curl_multi_add_handle($m,$h2);$child=child_le(import_args_le($f,$id,'mixed_hold'),$importEnv);$participants=[];$monitor=connect_le();
    try{
        $files=[];until_le(function()use($id,&$files){$files=glob(barrier_dir_le().'/'.$id.'_l02e_mixed-*.json')?:[];return count($files)===3;},$m);
        $participants=array_map(fn($p)=>json_decode(file_get_contents($p),true,512,JSON_THROW_ON_ERROR),$files);$cli=array_values(array_filter($participants,fn($p)=>$p['sapi']==='cli'))[0];$web=array_values(array_filter($participants,fn($p)=>$p['sapi']!=='cli'));
        check_le('LB15','Deletion overlap has three distinct live processes/connections',count(array_unique(array_column($participants,'pid')))===3&&count(array_unique(array_column($participants,'connection_id')))===3);
        unlock_gate_le($id,$cli['pid']);until_le(fn()=>count(named_waits_le($monitor,$lock,[$cli['connection_id']]))===1,$m,4000);query($root,'SELECT RELEASE_LOCK(?)',[$lock]);
        until_le(fn()=>is_file($base.'-ready-1.json'),$m);foreach($web as $p)unlock_gate_le($id,$p['pid']);
        $deleting=array_values(array_filter($web,fn($p)=>$p['uri']==='/api/account.php'))[0];$wait=[];
        until_le(function()use($monitor,$deleting,$cli,&$wait){$wait=row_waits_le($monitor,$deleting['connection_id'],$cli['connection_id']);return $wait!==[];},$m,4000);
        check_le('LB15','Actual account-deletion SQL waits on importer row/range locks',$wait!==[]);
        check_le('LB11','Other connection sees no staged facts before commit',(int)query($monitor,'SELECT COUNT(*) FROM synthetic_import_facts WHERE pair_ref=?',[$f['b']->pair_ref])->fetchColumn()===0);
        file_put_contents($base.'-proceed-1','proceed');$responses=finish_http_le($m,[$h1,$h2]);$m=null;$import=finish_le($child);$child=null;
        $user=query($root,'SELECT account_status,session_version FROM users WHERE id=?',[$f['low']])->fetch();
        $races[]=['kind'=>'account_delete','schedule'=>'import_holds_rows_then_http','barrier'=>$id,'participants'=>$participants,'observed_row_waits'=>$wait,'http'=>array_map(fn($r)=>['status'=>$r['code'],'error'=>$r['json']['error']??null],$responses),'import'=>['exit'=>$import['code'],'outcome'=>$import['json']['outcome']??null],'account_status'=>$user['account_status'],'session_version'=>$user['session_version']];
        check_le('LB15','Importer commits then deletion remains effective with advanced sessions',$import['code']===0&&$responses[0]['code']===200&&$user['account_status']==='deleted'&&(int)$user['session_version']>3);
        $history=query($root,'SELECT history_state FROM pair_policy_history WHERE match_id=?',[$f['match']])->fetchColumn();
        check_le('LB31','Post-deletion authorization denies old sessions and counterparty history',$history==='legacy_incomplete'&&http_le('/api/messages.php?match_id='.$f['match'],$a)['code']!==200&&http_le('/api/messages.php?match_id='.$f['match'],$b)['code']!==200);
        $before=pair_state_le($root,$f);$replay=import_le($f);check_le('LB32','Old import receipt cannot restore deleted account or its sessions',$replay['code']===0&&($replay['json']['replayed']??false)===true&&$before===pair_state_le($root,$f));
    }finally{query($root,'SELECT RELEASE_LOCK(?)',[$lock]);foreach($participants as $p)unlock_gate_le($id,$p['pid']);file_put_contents($base.'-proceed-1','release');if($m instanceof CurlMultiHandle){curl_multi_remove_handle($m,$h1);curl_multi_remove_handle($m,$h2);curl_multi_close($m);}if(is_array($child)){proc_terminate($child[0]);finish_le($child);}}
}

function upgrade_le(string $database): void
{
    global $rootPath;
    putenv('DB_NAME='.$database.'_upgrade');
    try{
        bushisa_bootstrap_disposable_database($rootPath.'/database/bushisa.sql');$r=finish_le(child_le([$rootPath.'/bin/migrate.php','--through=008']));if($r['code']!==0)throw new RuntimeException('Upgrade base migration');$p=connect_le();
        query($p,'INSERT INTO users (nust_student_id,email,password_hash,is_verified) VALUES ("L02eOldA","oldA@example.invalid","synthetic",1),("L02eOldB","oldB@example.invalid","synthetic",1)');
        query($p,'INSERT INTO matches (user_a_id,user_b_id) VALUES (1,2)');query($p,'INSERT INTO pair_policy_history (user_low,user_high,match_id,history_state,version,generation,ending_count,created_at) VALUES (1,2,1,"legacy_incomplete",7,2,2,UTC_TIMESTAMP(6))');
        query($p,'INSERT INTO pair_request_counts (requester_id,recipient_id,request_count) VALUES (1,2,3),(2,1,1)');query($p,'INSERT INTO blocks (blocker_id,blocked_id) VALUES (1,2)');query($p,'INSERT INTO messages (match_id,sender_id,body) VALUES (1,1,"Retained synthetic upgrade text")');
        $old=[];foreach(['users','matches','pair_policy_history','pair_request_counts','blocks','messages'] as $t)$old[$t]=$p->query('SELECT * FROM '.$t)->fetchAll();
        $first=finish_le(child_le([$rootPath.'/bin/migrate.php']));$same=true;foreach($old as $t=>$rows){
            $projection=implode(',',array_map('bushisa_quote_disposable_identifier',array_keys($rows[0])));
            $same=$same&&$rows===$p->query('SELECT '.$projection.' FROM '.$t)->fetchAll();
        }
        $same=$same&&(int)$p->query('SELECT COUNT(*) FROM users WHERE signup_attempt_key IS NOT NULL')->fetchColumn()===0;
        check_le('LE-M02','Existing 008 schema upgrades through 009 preserving exact users/text/budgets/cutoff/blocks',$first['code']===0&&str_contains($first['stdout'],'Applying 009_pair_trigger_health.sql')&&$same);
        $second=finish_le(child_le([$rootPath.'/bin/migrate.php']));check_le('LE-M03','Second upgrade run skips 009 and creates no genesis/activation/import marker',$second['code']===0&&str_contains($second['stdout'],'Skipping 009_pair_trigger_health.sql')&&(int)$p->query('SELECT COUNT(*) FROM pair_account_genesis')->fetchColumn()===0&&$p->query('SELECT activated_at FROM pair_ledger_epoch')->fetchColumn()===null&&(int)$p->query('SELECT COUNT(*) FROM synthetic_import_destination')->fetchColumn()===0);
        $p->exec('DROP PROCEDURE bushisa_pair_trigger_health');$p->exec('CREATE PROCEDURE bushisa_pair_trigger_health() SQL SECURITY DEFINER READS SQL DATA SELECT 0 AS unrelated_fixture_object');
        query($p,'DELETE FROM schema_migrations WHERE version="009_pair_trigger_health"');$before=$p->query('SELECT ROUTINE_DEFINITION FROM information_schema.routines WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME="bushisa_pair_trigger_health"')->fetchColumn();
        $conflict=finish_le(child_le([$rootPath.'/bin/migrate.php']));$after=$p->query('SELECT ROUTINE_DEFINITION FROM information_schema.routines WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME="bushisa_pair_trigger_health"')->fetchColumn();
        check_le('LE-M06','Conflicting existing routine is preserved and migration refuses it',$conflict['code']!==0&&$before===$after&&(int)$p->query('SELECT COUNT(*) FROM schema_migrations WHERE version="009_pair_trigger_health"')->fetchColumn()===0);
    }finally{putenv('DB_NAME='.$database);}
}

try{
    bushisa_bootstrap_disposable_database($rootPath.'/database/bushisa.sql');$migration=finish_le(child_le([$rootPath.'/bin/migrate.php']));check_le('LE-M01','Fresh migration with fixture identity',$migration['code']===0);
    $repeat=finish_le(child_le([$rootPath.'/bin/migrate.php']));check_le('LE-M04','Fresh second migration skips 009',$repeat['code']===0&&str_contains($repeat['stdout'],'Skipping 009_pair_trigger_health.sql'));upgrade_le($settings['name']);
    $root=connect_le();$environment=$root->query('SELECT VERSION() AS mysql,@@version_comment AS engine_comment,@@transaction_isolation AS default_isolation,@@innodb_lock_wait_timeout AS lock_wait_timeout,@@innodb_rollback_on_timeout AS rollback_on_timeout,@@innodb_deadlock_detect AS deadlock_detection,@@sql_mode AS sql_mode,@@time_zone AS default_time_zone,@@system_time_zone AS system_time_zone,@@collation_server AS collation_server,@@lower_case_table_names AS lower_case_table_names')->fetch();
    $environment['php']=PHP_VERSION;$environment['os']=PHP_OS_FAMILY;$environment['pdo_client']=$root->getAttribute(PDO::ATTR_CLIENT_VERSION);$environment['extensions']=array_values(array_intersect(['PDO','pdo_mysql','openssl','curl','mbstring','fileinfo'],get_loaded_extensions()));
    $environment['database']=$settings['name'];$environment['host']=$settings['host'];$environment['port']=$settings['port'];$environment['session_time_zone']=$root->query('SELECT @@time_zone')->fetchColumn();
    $environment['ssl_cipher']=$root->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_ASSOC)['Value'];
    $environment['database_collation']=$root->query('SELECT @@collation_database')->fetchColumn();
    $environment['binary_logging']=$root->query('SELECT @@log_bin')->fetchColumn();
    $environment['trust_function_creators']=$root->query('SELECT @@log_bin_trust_function_creators')->fetchColumn();
    check_le('LE-C09','All used runtime extensions present',count($environment['extensions'])===6);
    check_le('LE-C01','Engine deadlock detection available',(int)$environment['deadlock_detection']===1);
    check_le('LE-C02','UTC and TIMESTAMP(6) migration',(int)$root->query('SELECT datetime_precision FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name="message_requests" AND column_name="requested_at"')->fetchColumn()===6&&$environment['session_time_zone']==='+00:00');
    $root->exec('CREATE TABLE l02e_deadlock_weight (id INT PRIMARY KEY,weight INT NOT NULL) ENGINE=InnoDB');$root->beginTransaction();for($i=1;$i<=1000;$i++)query($root,'INSERT INTO l02e_deadlock_weight VALUES (?,0)',[$i]);$root->commit();
    query($root,'UPDATE pair_ledger_epoch SET activated_at=UTC_TIMESTAMP(6),writer_reference="synthetic:l02e-isolated-fixture" WHERE singleton=1 AND activated_at IS NULL');
    [$permit,$password]=runtime_user($root,$settings['name']);$importEnv=['DB_USER'=>$permit->user,'DB_PASSWORD'=>$password];$web=web_user($root,$settings['name']);$webEnv=['DB_USER'=>$web['user'],'DB_PASSWORD'=>$web['password']];
    foreach([8127,8128] as $port){$socket=@fsockopen('127.0.0.1',$port,$n,$e,0.1);if($socket){fclose($socket);throw new RuntimeException('Occupied synthetic HTTP port');}$pipes=[];$p=proc_open([PHP_BINARY,'-d','session.save_path='.$runDir.'/sessions','-S','127.0.0.1:'.$port,'-t',$rootPath],[0=>['pipe','r'],1=>['file',$runDir.'/http-'.$port.'.log','a'],2=>['file',$runDir.'/http-'.$port.'.log','a']],$pipes,$rootPath,array_merge(getenv(),$webEnv));if(!is_resource($p))throw new RuntimeException('HTTP start');fclose($pipes[0]);$servers[]=$p;}
    until_le(function(){$s=@fsockopen('127.0.0.1',8128,$n,$e,0.1);if(!$s)return false;fclose($s);return true;});
    health_checks_le($root,$permit,$web);$i=0;
    foreach(['cancel','new_request','block','report','review','unmatch','end','incomplete_request'] as $kind)foreach(['all_queued','http_first'] as $schedule){
        $f=scenario($root,$runDir,$permit,'m'.$i++,$kind);$auth=session_le($f['low']);$actor=$auth;$match=$f['match'];$version=4;$payload=['action'=>'request','match_id'=>$match,'pair_version'=>$version,'idempotency_key'=>bin2hex(random_bytes(32))];$path='/api/message_requests.php';
        if($kind==='cancel'||$kind==='end')$payload['action']=$kind==='end'?'revoke':'cancel';
        if($kind==='new_request'){$payload['match_id']=$f['native_match'];$payload['pair_version']=0;}
        if($kind==='block'){$path='/api/safety.php';$payload=['action'=>'block','user_id'=>$f['high'],'pair_version'=>4,'idempotency_key'=>bin2hex(random_bytes(32))];}
        if($kind==='unmatch'){$path='/api/pairs.php';$payload['action']='unmatch';}
        if($kind==='report'){$data=data_le(http_le('/api/message_requests.php?match_id='.$match,$auth));$path='/api/safety.php';$payload=['action'=>'report','target_type'=>'match','target_id'=>$match,'context'=>$data['report_context'],'conduct_actor_id'=>$f['high'],'block_id'=>(int)query($root,'SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?',[$f['low'],$f['high']])->fetchColumn(),'idempotency_key'=>bin2hex(random_bytes(32)),'reason'=>'harassment','explanation'=>'Synthetic concurrent allegation'];}
        if($kind==='review'){$actor=session_le($f['reviewer'],true);$scope=data_le(http_le('/api/safety.php',$actor,['action'=>'block_report_review','case_id'=>$f['case'],'block_id'=>$f['block']]));$path='/api/safety.php';$payload=['action'=>'clear_block_report','case_id'=>$f['case'],'block_id'=>$f['block'],'review_version'=>$scope['review_version']];}
        bind($root,$f);save($root,$f);$beforeProbation=encode(query($root,'SELECT * FROM report_probation_periods WHERE actor_id IN (?,?) ORDER BY id',[$f['low'],$f['high']])->fetchAll());
        [$responses,$import]=mixed_le($root,$f,[$path,$actor,$payload],[$path,$actor,$payload],$kind,$schedule);$codes=array_column($responses,'code');
        $history=query($root,'SELECT * FROM pair_policy_history WHERE match_id=?',[$match])->fetch();$count=(int)query($root,'SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$f['low'],$f['high']])->fetchColumn();
        check_le('LB16',$kind.'/'.$schedule.' import target remains incomplete with intact lifetime floor',$history['history_state']==='legacy_incomplete'&&$count>=1&&$count<=3);
        if($kind==='incomplete_request')check_le('LB31','Concurrent legacy request stays fail-closed',count(array_intersect($codes,[409,503]))===2&&(int)query($root,'SELECT COUNT(*) FROM message_requests WHERE match_id=?',[$match])->fetchColumn()===0);
        else check_le('LB29',$kind.'/'.$schedule.' actual mutation occurs or stale pair intent is refused',in_array(200,$codes,true)||$codes===[409,409]);
        if($schedule==='http_first'&&$kind!=='incomplete_request')check_le('LB13',$kind.' newer HTTP state forces import replan',$import['code']===2&&($import['json']['reason']??'')==='STALE_LIVE_STATE');
        if($kind==='new_request')check_le('LB13','New native event/window across another pair is retained once',(int)query($root,'SELECT COUNT(*) FROM pair_request_events WHERE match_id=?',[$f['native_match']])->fetchColumn()===1&&(int)query($root,'SELECT request_count FROM pair_request_counts WHERE requester_id=? AND recipient_id=?',[$f['low'],$f['buddy']])->fetchColumn()===1&&(int)query($root,'SELECT COUNT(*) FROM message_request_sends WHERE requester_id=?',[$f['low']])->fetchColumn()===1);
        if($kind!=='report')check_le('LB17',$kind.' exact probation clocks unchanged',$beforeProbation===encode(query($root,'SELECT * FROM report_probation_periods WHERE actor_id IN (?,?) ORDER BY id',[$f['low'],$f['high']])->fetchAll()));
        if($kind==='report')check_le('LB17','Duplicate HTTP report creates one new actor probation without extending existing period',(int)query($root,'SELECT COUNT(*) FROM report_probation_periods WHERE actor_id=?',[$f['high']])->fetchColumn()===1&&(int)query($root,'SELECT COUNT(*) FROM report_submissions WHERE reporter_id=?',[$f['low']])->fetchColumn()===1);
        if($kind==='block')check_le('LB14','Live block cannot be cleared by import',($codes===[409,409])||(int)query($root,'SELECT COUNT(*) FROM blocks WHERE blocker_id=? AND blocked_id=?',[$f['low'],$f['high']])->fetchColumn()===1);
        if($kind==='review')check_le('LB14','Reviewer clearance leaves personal block in place without chat grant',(int)query($root,'SELECT COUNT(*) FROM report_block_links WHERE block_id=? AND cleared_at IS NULL',[$f['block']])->fetchColumn()===0&&(int)query($root,'SELECT COUNT(*) FROM blocks WHERE id=?',[$f['block']])->fetchColumn()===1&&(int)$history['chat_suppressed']===1);
        if($kind==='unmatch'&&in_array(200,$codes,true))check_le('LB16','Unmatch alone preserves existing approved chat',http_le('/api/messages.php?match_id='.$match,$auth)['code']===200&&(int)$history['ending_count']===0);
        if($kind==='end'&&in_array(200,$codes,true))check_le('LB13','One actual ending retains actor/deadline and cannot be undone by import',(int)$history['ending_count']===1&&(int)$history['last_ended_by']===$f['low']&&(int)query($root,'SELECT COUNT(*) FROM pair_chat_segments WHERE match_id=? AND ended_at IS NOT NULL AND read_until=DATE_ADD(ended_at,INTERVAL 7 DAY)',[$match])->fetchColumn()===1);
    }
    deletion_le($root,$permit);
    foreach(['deadlock_once','deadlock_exhaust','row_timeout_once','row_timeout_exhaust'] as $n=>$mode)engine_le($root,$permit,$mode,$n);
    $ordinaryPermit=clone $permit;$ordinaryPermit->test_controls=false;$ordinary=seed_pair($root,$runDir,$ordinaryPermit,'ordinary');$ordinaryEnv=$importEnv;$ordinaryEnv['BUSHISA_SYNTHETIC_IMPORT_TEST_CONTROLS']='false';
    $plain=finish_le(child_le(import_args_le($ordinary),$ordinaryEnv));check_le('LE-G03','Ordinary authorized synthetic import does not require test controls',$plain['code']===0&&($plain['json']['grants_chat']??true)===false);
    $before=pair_state_le($root,$ordinary);$refused=finish_le(child_le(import_args_le($ordinary,'l02e_'.bin2hex(random_bytes(16)),'mixed'),$ordinaryEnv));
    check_le('LE-G04','Test selectors without permit/environment authorization refuse before effects',$refused['code']===2&&($refused['json']['reason']??'')==='TEST_CONTROLS_DISABLED'&&$before===pair_state_le($root,$ordinary));
    // Missing privileges must fail atomically, never prompt the executor to grant them.
    $db=bushisa_quote_disposable_identifier($settings['name']);$host=(string)$root->query("SELECT SUBSTRING_INDEX(USER(),'@',-1)")->fetchColumn();$account="'".$permit->user."'@'".$host."'";
    foreach(['INSERT ON '.$db.'.synthetic_import_receipts','INSERT ON '.$db.'.synthetic_import_keys','UPDATE (request_count) ON '.$db.'.pair_request_counts','UPDATE (ending_count,version) ON '.$db.'.pair_policy_history','LOCK TABLES ON '.$db.'.*'] as $n=>$privilege){
        $f=seed_pair($root,$runDir,$permit,'pv'.$n);$root->exec('REVOKE '.$privilege.' FROM '.$account);$before=pair_state_le($root,$f);
        try{$r=import_le($f);check_le('LE-P01','Missing '.$privilege.' rejects atomically',$r['code']===2&&($r['json']['reason']??'')==='IMPORT_PRIVILEGE_DENIED'&&$before===pair_state_le($root,$f));}finally{$root->exec('GRANT '.$privilege.' TO '.$account);}
        $r=import_le($f);check_le('LE-P02','Restored exact baseline capability succeeds without broadening',$r['code']===0);
    }
    foreach([[$importEnv,'UPDATE users SET session_version=session_version WHERE 1=0'],[$importEnv,'UPDATE pair_policy_history SET history_state="complete_from_genesis" WHERE 1=0'],[$webEnv,'UPDATE pair_ledger_epoch SET activated_at=activated_at WHERE 1=0'],[$webEnv,'SELECT * FROM synthetic_import_receipts LIMIT 0'],[$webEnv,'CREATE TABLE l02e_forbidden (id INT)'],[$webEnv,'CREATE TRIGGER l02e_forbidden_trigger BEFORE INSERT ON users FOR EACH ROW SET NEW.is_verified=NEW.is_verified']] as $n=>[$env,$sql]){
        $p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$settings['host'],$settings['port'],$settings['name']),$env['DB_USER'],$env['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$denied=false;$errno=null;
        try{$p->exec($sql);}catch(PDOException $e){$errno=(int)($e->errorInfo[1]??0);$denied=in_array($errno,[1142,1143],true)||($n===5&&in_array($errno,[1227,1419],true));}
        check_le('LE-P03','Separate restricted identity denies forbidden operation '.$n,$denied);$checks[array_key_last($checks)]['observed_mysql_errno']=$errno;
    }
    check_le('LE-P04','No real mail and no queued fake auth work',(int)$root->query('SELECT COUNT(*) FROM mail_outbox')->fetchColumn()===0);
    $result='PASS';
}catch(Throwable $e){$result='FAIL';$failure=$e->getMessage();fwrite(STDERR,'FAIL: '.$failure."\n");}
finally{
    foreach($servers as $p){proc_terminate($p);proc_close($p);}
    $sources=[];foreach(['api/safety.php','api/account.php','php/http_test_barrier.php','php/synthetic_legacy_import.php','bin/synthetic_legacy_import.php','bin/migrate.php','database/migrations/009_pair_trigger_health.sql','tests/support/l02e_import_control.php','tests/support/l02e_fixture.php','tests/integration_l02e_races.php','tests/support/synthetic_import_fixture.php','tests/support/disposable_database.php','php/pair_history.php','php/report_intake.php','.github/workflows/ci.yml','tests/support/l02e.Dockerfile'] as $file)$sources[$file]=hash('sha256',str_replace("\r\n","\n",file_get_contents($rootPath.'/'.$file)));
    file_put_contents($runDir.'/execution.json',json_encode(['format'=>'bushisa.l02e-execution.v1','result'=>$result??'FAIL','failure'=>$failure,'started_at'=>$started,'ended_at'=>gmdate('c'),'environment'=>$environment,'checks'=>$checks,'assertion_count'=>count($checks),'races'=>$races,'engine_trials'=>$engineTrials,'web_grant_inventory'=>$web['grants']??null,'sources_lf_sha256'=>$sources,'production_authenticity'=>'NOT_VERIFIED','production_cutover_attested'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
    fwrite(STDOUT,json_encode(['result'=>$result??'FAIL','assertions'=>count($checks),'races'=>count($races),'engine_trials'=>count($engineTrials),'evidence'=>$runDir.'/execution.json'])."\n");
}
exit(($result??'FAIL')==='PASS'?0:1);
