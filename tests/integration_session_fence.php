<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/q11_fixture.php';
require_once __DIR__.'/support/attempt_erasure_fixture.php';
require_once __DIR__.'/../php/managed_session.php';
require_once __DIR__.'/../php/http_test_barrier.php';
bushisa_q11_synthetic_guard();
$settings=bushisa_require_disposable_test_database('Session fence integration');
$repo=dirname(__DIR__); $nonce=bin2hex(random_bytes(16)); $dir=$repo.'/.tmp/sf-run-'.$nonce;
mkdir($dir,0700,true); $store=realpath($repo.'/.tmp').DIRECTORY_SEPARATOR.'sf-storage-'.$nonce; mkdir($store,0700);
$namespace='ss_'.$nonce; file_put_contents($store.'/.synthetic-session-store',$namespace);
putenv('BUSHISA_SESSION_SYNTHETIC=true'); putenv('BUSHISA_SESSION_ROOT='.$store); putenv('BUSHISA_SESSION_NAMESPACE='.$namespace); putenv('BUSHISA_SESSION_KEY='.bin2hex(random_bytes(32)));
putenv('MAIL_OUTBOX_KEY='.base64_encode(random_bytes(32))); putenv('SIGNUP_CODE_HMAC_KEY='.bin2hex(random_bytes(32)));
$checks=[]; $races=[]; $migrations=[]; $servers=[]; $failure=null; $environment=[]; $started=gmdate('c');
function sc(string $case,string $check,bool $ok): void {
    global $checks; $checks[]=['case'=>$case,'check'=>$check,'result'=>$ok?'PASS':'FAIL'];
    if (!$ok) { throw new RuntimeException($case.': '.$check); }
}
function sf_child(array $args,array $env=[]): array {
    global $repo; $pipes=[]; $p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,array_merge(getenv(),$env));
    if (!is_resource($p)) { throw new RuntimeException('SESSION_CHILD_UNAVAILABLE'); } fclose($pipes[0]); return [$p,$pipes];
}
function sf_end(array $child): array {
    [$p,$pipes]=$child; $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]);fclose($pipes[2]);
    $result=['exit'=>proc_close($p),'stdout'=>$out,'stderr'=>$err,'json'=>json_decode($out,true)];
    $GLOBALS['sf_last_child']=$result; return $result;
}
function sf_cli(array $args,array $env=[]): array { return sf_end(sf_child($args,$env)); }
function sf_worker(array $job,array $env=[]): array {
    global $dir,$repo; $path=$dir.'/job-'.bin2hex(random_bytes(8)).'.json';
    file_put_contents($path,json_encode($job,JSON_THROW_ON_ERROR)); return sf_child([$repo.'/tests/support/session_fence_worker.php','--job='.$path],$env);
}
function sf_run(array $job,array $env=[]): array { return sf_end(sf_worker($job,$env)); }
function sf_capsule(): string { global $dir; $path=$dir.'/client-'.bin2hex(random_bytes(8)).'.json';file_put_contents($path,'{}');return $path; }
function sf_login(array $u,array $env): string {
    $capsule=sf_capsule(); $r=sf_run(['action'=>'login','email'=>$u['email'],'capsule'=>$capsule],$env);
    if ($r['exit']!==0 || !($r['json']['result']??false)) { throw new RuntimeException('SESSION_FIXTURE_LOGIN_FAILED: '.($r['json']['error']??'unknown')); } return $capsule;
}
function sf_cookie(string $capsule): string { $a=json_decode(file_get_contents($capsule),true);return 'PHPSESSID='.$a['sid']; }
function sf_http(string $capsule,string $route,array $data=[],array $headers=[],int $port=8165): CurlHandle {
    $c=curl_init('http://127.0.0.1:'.$port.'/'.$route);$a=json_decode(file_get_contents($capsule),true);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>22,CURLOPT_HTTPHEADER=>array_merge(['Cookie: '.sf_cookie($capsule)],$headers)]);
    if ($data) { curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data+['csrf_token'=>$a['csrf']])]); }return $c;
}
function sf_fetch(string $capsule,string $route,array $data=[],int $port=8165): array {
    $c=sf_http($capsule,$route,$data,[],$port);$body=curl_exec($c);$status=curl_getinfo($c,CURLINFO_RESPONSE_CODE);return compact('status','body');
}
function sf_until(callable $fn,?CurlMultiHandle $multi=null): void {
    $until=microtime(true)+12; do { if($multi)curl_multi_exec($multi,$n); if($fn())return;usleep(10000); }while(microtime(true)<$until);throw new RuntimeException('SESSION_OBSERVATION_TIMEOUT');
}
function sf_waits(PDO $p,int $blocker): array {
    return \Bushisa\ManagedSession\q($p,'SELECT r.PROCESSLIST_ID AS requesting_connection,b.PROCESSLIST_ID AS blocking_connection FROM performance_schema.data_lock_waits w
        JOIN performance_schema.threads r ON r.THREAD_ID=w.REQUESTING_THREAD_ID JOIN performance_schema.threads b ON b.THREAD_ID=w.BLOCKING_THREAD_ID WHERE b.PROCESSLIST_ID=?',[$blocker])->fetchAll();
}
/** HTTP read completes before the root takes the lock. Both mutation workers
 * then wait simultaneously on that account: actual overlap, not sequential calls. */
function sf_overlap(PDO $root,array $u,array $env,string $capsule,string $case,string $action='expire',?string $deadline=null): array {
    global $races; $barrier='sf_'.bin2hex(random_bytes(16));$point='session_write';
    $base=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bushisa-http-barriers'.DIRECTORY_SEPARATOR.$barrier.'_'.$point;
    $headers=['X-Bushisa-Test-Barrier: '.$barrier,'X-Bushisa-Test-Barrier-Point: '.$point,'X-Bushisa-Test-Barrier-Expected: 2','X-Bushisa-Test-Barrier-Timeout-Ms: 14000','X-Bushisa-Test-Gate: true'];
    $c=sf_http($capsule,'tests/support/session_fence_probe.php?action=write',[],$headers); $multi=curl_multi_init();curl_multi_add_handle($multi,$c);
    $job=['action'=>$action,'id'=>$u['id'],'barrier'=>$barrier,'point'=>$point];
    if ($action==='erase') {
        sf_until(fn()=>count(glob($base.'-*.json')?:[])===1,$multi);
        q11_clock($root,$deadline);$expiry=bushisa_signup_check_account($root,$u['id']);$job['receipt']=$expiry['receipt'];
        q11_clock($root,bushisa_signup_time($deadline,'+24 hours'));
    }
    $worker=sf_worker($job,$env);
    try {
        sf_until(fn()=>count(glob($base.'-*.json')?:[])===2,$multi);
        $records=array_map(fn($f)=>json_decode(file_get_contents($f),true),glob($base.'-*.json'));
        $root->beginTransaction(); \Bushisa\ManagedSession\q($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$u['id']])->fetch();
        $blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
        if($deadline!==null && $action!=='erase') { q11_clock($root,$deadline); } // Committed with release of root lock.
        $revoker=array_values(array_filter($records,fn($r)=>$r['sapi']==='cli'))[0];
        $http=array_values(array_filter($records,fn($r)=>$r['sapi']==='cli-server'))[0];
        touch($base.'-'.$revoker['pid'].'.release');
        sf_until(fn()=>in_array($revoker['connection_id'],array_column(sf_waits($root,$blocker),'requesting_connection')),$multi);
        touch($base.'-'.$http['pid'].'.release');
        $waits=[];sf_until(function()use($root,$blocker,&$waits,$records):bool {
            $waits=sf_waits($root,$blocker); return count(array_intersect(array_column($waits,'requesting_connection'),array_column($records,'connection_id')))===2;
        },$multi);
        sc($case,'distinct HTTP/CLI processes and SQL connections simultaneously blocked at account fence',count(array_unique(array_column($records,'pid')))===2 && count(array_unique(array_column($records,'connection_id')))===2);
        $root->commit();sf_until(function()use($multi):bool{curl_multi_exec($multi,$n);return $n===0;},$multi);
        $answer=sf_end($worker);$worker=null;$status=curl_getinfo($c,CURLINFO_RESPONSE_CODE);$body=curl_multi_getcontent($c);
        $races[]=['case'=>$case,'participants'=>$records,'observed_row_waits'=>$waits,'worker_exit'=>$answer['exit'],'http_status'=>$status];
        sc($case,'late HTTP response is fenced without private output',$answer['exit']===0 && $status===503 && !str_contains($body,'user_id'));
        return compact('answer','status');
    } finally { if($root->inTransaction())$root->rollBack();if($worker){proc_terminate($worker[0]);sf_end($worker);}curl_multi_close($multi); }
}
/** Actual successful route transaction pauses before resuming its old binding. */
function sf_handoff_race(PDO $root,array $u,array $env,string $capsule,string $kind,string $op,string $code=''): void {
    global $races; $case=$kind==='write'?'SC21':'SC24';$point=$kind==='write'?'session_write':'session_resume';$barrier='sf_'.bin2hex(random_bytes(16));
    $base=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bushisa-http-barriers'.DIRECTORY_SEPARATOR.$barrier.'_'.$point;
    $headers=['X-Bushisa-Test-Barrier: '.$barrier,'X-Bushisa-Test-Barrier-Point: '.$point,'X-Bushisa-Test-Barrier-Expected: 2','X-Bushisa-Test-Barrier-Timeout-Ms: 14000','X-Bushisa-Test-Gate: true'];
    $route=$kind==='verify'?'verify_email.php':'onboarding.php';$data=$kind==='verify'?['action'=>'verify','approval_code'=>$code]:['date_of_birth'=>'2000-01-01'];
    if ($kind==='write') { $route='tests/support/session_fence_probe.php?action=write';$data=[]; }
    $first=sf_http($capsule,$route,$data,$headers);$multi=curl_multi_init();curl_multi_add_handle($multi,$first);
    $second=null;$worker=null;
    try {
        sf_until(fn()=>count(glob($base.'-*.json')?:[])===1,$multi);
        if($op==='logout') { $second=sf_http($capsule,'logout.php',[],$headers,8166);curl_multi_add_handle($multi,$second); }
        else { $worker=sf_worker(['action'=>'version','id'=>$u['id'],'barrier'=>$barrier,'point'=>$point],$env); }
        sf_until(fn()=>count(glob($base.'-*.json')?:[])===2,$multi);
        $records=array_map(fn($f)=>json_decode(file_get_contents($f),true),glob($base.'-*.json'));
        $transition=array_values(array_filter($records,fn($r)=>str_contains($r['uri'],$route)))[0];
        $revoker=array_values(array_filter($records,fn($r)=>$r['pid']!==$transition['pid']))[0];
        $root->beginTransaction();\Bushisa\ManagedSession\q($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$u['id']])->fetch();$blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
        touch($base.'-'.$revoker['pid'].'.release');sf_until(fn()=>in_array($revoker['connection_id'],array_column(sf_waits($root,$blocker),'requesting_connection')),$multi);
        touch($base.'-'.$transition['pid'].'.release');$waits=[];
        sf_until(function()use($root,$blocker,&$waits,$records){$waits=sf_waits($root,$blocker);return count(array_intersect(array_column($waits,'requesting_connection'),array_column($records,'connection_id')))===2;},$multi);
        $root->commit();sf_until(function()use($multi){curl_multi_exec($multi,$n);return $n===0;},$multi);
        $answer=$worker?sf_end($worker):null;$worker=null;
        $status=curl_getinfo($first,CURLINFO_RESPONSE_CODE);
        sc($case,$kind.' response loses to '.$op.' under simultaneous account waits',$status===503 && ($answer===null||$answer['exit']===0) && count(array_unique(array_column($records,'pid')))===2 && count(array_unique(array_column($records,'connection_id')))===2);
        $races[]=['case'=>$case,'variant'=>$kind.' versus '.$op,'participants'=>$records,'observed_row_waits'=>$waits,'http_status'=>$status,'other_status'=>$second?curl_getinfo($second,CURLINFO_RESPONSE_CODE):null];
    } finally { if($root->inTransaction())$root->rollBack();if($worker){proc_terminate($worker[0]);sf_end($worker);}curl_multi_close($multi); }
}
function sf_login_replacement_race(PDO $root,array $env): void {
    global $races;
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$u=q11_user($root,'passwordproof');$capsule=sf_capsule();sf_run(['action'=>'read','capsule'=>$capsule],$env);
    $point='session_login';$barrier='sf_'.bin2hex(random_bytes(16));$base=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bushisa-http-barriers'.DIRECTORY_SEPARATOR.$barrier.'_'.$point;
    $headers=['X-Bushisa-Test-Barrier: '.$barrier,'X-Bushisa-Test-Barrier-Point: '.$point,'X-Bushisa-Test-Barrier-Expected: 2','X-Bushisa-Test-Barrier-Timeout-Ms: 14000','X-Bushisa-Test-Gate: true'];
    $http=sf_http($capsule,'login.php',['email'=>$u['email'],'password'=>'Synthet1c!Fixture'],$headers);$multi=curl_multi_init();curl_multi_add_handle($multi,$http);
    $observer=sf_worker(['action'=>'observe','barrier'=>$barrier,'point'=>$point],$env);
    try {
        sf_until(fn()=>count(glob($base.'-*.json')?:[])===2,$multi);$records=array_map(fn($f)=>json_decode(file_get_contents($f),true),glob($base.'-*.json'));
        $auth=array_values(array_filter($records,fn($r)=>$r['sapi']==='cli-server'))[0];
        $root->beginTransaction();$old=\Bushisa\ManagedSession\q($root,'SELECT * FROM users WHERE id=? FOR UPDATE',[$u['id']])->fetch();$blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
        \Bushisa\ManagedSession\q($root,'DELETE FROM users WHERE id=?',[$u['id']]);
        foreach(['signup_attempt_identities','signup_lifecycle'] as $table) { \Bushisa\ManagedSession\q($root,'DELETE FROM '.$table.' WHERE account_id=?',[$u['id']]); }
        // Even identical credentials and original second cannot turn G1 proof
        // into G2 authority. This deliberately adversarial fixture is synthetic.
        \Bushisa\ManagedSession\q($root,'INSERT INTO users (id,nust_student_id,email,password_hash,is_verified,is_suspended,is_active,account_status,session_version,created_at) VALUES (?,?,?,?,0,0,1,"pending_email",0,?)',[$u['id'],$old['nust_student_id'],$old['email'],$old['password_hash'],$old['created_at']]);
        bushisa_signup_record_creation($root,$u['id']);$new=\Bushisa\ManagedSession\q($root,'SELECT signup_attempt_key FROM users WHERE id=?',[$u['id']])->fetchColumn();
        touch($base.'-'.$auth['pid'].'.release');$waits=[];
        sf_until(function()use($root,$blocker,&$waits,$auth){$waits=sf_waits($root,$blocker);return in_array($auth['connection_id'],array_column($waits,'requesting_connection'));},$multi);
        $root->commit();foreach($records as $r)touch($base.'-'.$r['pid'].'.release');sf_until(function()use($multi){curl_multi_exec($multi,$n);return $n===0;},$multi);$answer=sf_end($observer);$observer=null;
        sc('SC04','password proof cannot transfer to replacement generation with identical ID/email/hash/time',curl_getinfo($http,CURLINFO_RESPONSE_CODE)===200 && str_contains(curl_multi_getcontent($http),'Session service unavailable.') && $new!==$old['signup_attempt_key']
            && (int)\Bushisa\ManagedSession\q($root,'SELECT COUNT(*) FROM session_bindings WHERE account_key=?',[$new])->fetchColumn()===0 && $answer['exit']===0);
        $races[]=['case'=>'SC04','variant'=>'actual HTTP password proof versus replacement generation','participants'=>$records,'replacing_writer'=>['pid'=>getmypid(),'connection_id'=>$blocker],'observed_row_waits'=>$waits,'http_status'=>200];
    } finally { if($root->inTransaction())$root->rollBack();if($observer){proc_terminate($observer[0]);sf_end($observer);}curl_multi_close($multi); }
}
try {
    bushisa_bootstrap_disposable_database($repo.'/database/bushisa.sql');
    $fresh=sf_cli([$repo.'/bin/migrate.php']);$repeat=sf_cli([$repo.'/bin/migrate.php']);
    sc('SC39','fresh migration 014 and repeated run',$fresh['exit']===0 && str_contains($fresh['stdout'],'Applying 014_') && $repeat['exit']===0 && str_contains($repeat['stdout'],'Skipping 014_'));
    $migrations[]=['kind'=>'fresh-repeat','exit'=>[$fresh['exit'],$repeat['exit']]];
    $root=q11_connect();q11_install($root);$runtime=qe_runtime($root,$settings['name']);$db=q11_connect($runtime);
    $env=['DB_USER'=>$runtime['user'],'DB_PASSWORD'=>$runtime['password'],'SESSION_COOKIE_SECURE'=>'false'];
    $environment=['php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$root->getAttribute(PDO::ATTR_SERVER_VERSION),'mysqlnd'=>phpversion('mysqlnd'),'database'=>$settings['name'],'port'=>$settings['port'],
        'settings'=>$root->query('SELECT @@server_uuid AS server_uuid,@@time_zone AS time_zone,@@transaction_isolation AS isolation_level,@@sql_mode AS sql_mode')->fetch(),
        'runtime_grants'=>$root->query('SHOW GRANTS FOR '.$runtime['account'])->fetchAll(PDO::FETCH_COLUMN),'adapter'=>'synthetic_local_v1'];
    foreach([8165,8166] as $port) {
        $pipes=[];$proc=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,'-t',$repo],[0=>['pipe','r'],1=>['file',$dir.'/http-'.$port.'.log','a'],2=>['file',$dir.'/http-'.$port.'.log','a']],$pipes,$repo,array_merge(getenv(),$env));
        if(!is_resource($proc))throw new RuntimeException('SESSION_HTTP_UNAVAILABLE');fclose($pipes[0]);$servers[]=$proc;
        sf_until(function()use($port){$s=@fsockopen('127.0.0.1',$port,$e,$m,.1);if($s){fclose($s);return true;}return false;});
    }
    $anonymous=sf_capsule();$r=sf_run(['action'=>'read','capsule'=>$anonymous],$env);
    sc('SC01','anonymous CSRF/session does not supply account identity',$r['exit']===0 && $r['json']['snapshot']['authenticated']===false);
    $unbound=q11_user($root,'enteredcode');$code=bushisa_issue_signup_code($db,$unbound['id'],$unbound['email']);
    $approved=sf_fetch($anonymous,'verify_email.php',['email'=>$unbound['email'],'action'=>'verify','approval_code'=>$code]);
    $stillAnonymous=sf_run(['action'=>'read','capsule'=>$anonymous],$env);
    sc('SC01','legitimate anonymous entered-code verification never supplies authenticated binding',$approved['status']===200 && str_contains($approved['body'],'Student email approved.') && !($stillAnonymous['json']['snapshot']['authenticated']??true));
    $r=sf_run(['action'=>'inject','field'=>'user_id','value'=>1,'capsule'=>$anonymous],$env);
    sc('SC01','serialized account claim cannot bind anonymous session',!isset($r['json']['snapshot']) && str_contains($r['stdout'],'Session service unavailable'));
    $u=q11_user($root,'binding');$s=sf_login($u,$env);$r=sf_run(['action'=>'read','capsule'=>$s],$env);
    sc('SC02','password-authenticated new attempt has authoritative generation without persisted email',$r['exit']===0 && $r['json']['snapshot']['generation_present'] && !$r['json']['snapshot']['email_present']);
    $p=sf_fetch($s,'api/notifications.php');sc('SC02','pending email cannot enter protected API',$p['status']===403);
    $g=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
    sc('SC06','binding/read leaves original Q11 clock unchanged',$g['unverified_expires_at']===bushisa_signup_time($g['registered_at'],'+72 hours'));
    bushisa_issue_signup_code($db,$u['id'],$u['email']);
    sc('SC06','resend cannot renew session or signup expiry',$g===\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch());
    $r=sf_run(['action'=>'read','capsule'=>sf_capsule()],$env);
    $legacy=q11_user($root,'legacy',false);$bad=sf_run(['action'=>'login','email'=>$legacy['email'],'capsule'=>sf_capsule()],$env);
    sc('SC03','missing legacy generation never backfilled or admitted',($bad['json']['result']??null)===false && \Bushisa\ManagedSession\q($root,'SELECT signup_attempt_key FROM users WHERE id=?',[$legacy['id']])->fetchColumn()===null);
    $broken=q11_user($root,'creation');\Bushisa\ManagedSession\q($root,'UPDATE signup_attempt_identities SET registered_at=DATE_ADD(registered_at,INTERVAL 1 SECOND) WHERE account_id=?',[$broken['id']]);
    $bad=sf_run(['action'=>'login','email'=>$broken['email'],'capsule'=>sf_capsule()],$env);sc('SC03','contradictory original registration denied',($bad['json']['result']??null)===false);
    $old=sf_login($u,$env);$other=sf_login($u,$env);$code=bushisa_issue_signup_code($db,$u['id'],$u['email']);
    $r=sf_run(['action'=>'handoff','kind'=>'verify','code'=>$code,'capsule'=>$s],$env);
    sc('SC09','actual verification transaction hands only its current binding to new epoch',($r['json']['result']??false) && $r['json']['snapshot']['version']===1);
    $r=sf_run(['action'=>'read','capsule'=>$old],$env);sc('SC05','other old-version binding cannot expose private payload',str_contains($r['stdout'],'Session service unavailable'));
    $p=sf_fetch($s,'api/notifications.php');sc('SC07','approved email does not grant adult/onboarded API permissions',$p['status']===403);
    $approved=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
    $r=sf_run(['action'=>'handoff','kind'=>'onboard','capsule'=>$s],$env);
    sc('SC09','actual adult transition hands same principal to active scope',($r['json']['result']??false) && $r['json']['snapshot']['version']===2);
    $anchors=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
    sc('SC07','reads and successful later onboarding preserve first approval/deadline',$anchors['first_email_approved_at']===$approved['first_email_approved_at'] && $anchors['onboarding_expires_at']===$approved['onboarding_expires_at']);
    $p=sf_fetch($s,'api/notifications.php');sc('SC02','active session can enter real protected API',$p['status']===200);
    $stale=bushisa_signup_check_account($db,$u['id']);
    sc('SC08','stale pending eligibility check preserves completed account/binding',$stale['state']==='not_pending' && sf_fetch($s,'api/notifications.php')['status']===200);
    $r=sf_run(['action'=>'invent_handoff','id'=>$u['id'],'old'=>2,'new'=>3,'capsule'=>$s],$env);sc('SC09','posted/guessed new authorization version cannot establish handoff',($r['json']['result']??null)===false);
    $before=json_decode(file_get_contents($s),true);$oldCapsule=sf_capsule();file_put_contents($oldCapsule,json_encode($before));
    $r=sf_run(['action'=>'rotate','capsule'=>$s],$env);sc('SC14','regeneration preserves trusted principal and replaces identifier/CSRF',($r['json']['snapshot']['user_id']??null)===$u['id'] && json_decode(file_get_contents($s),true)['sid']!==$before['sid'] && json_decode(file_get_contents($s),true)['csrf']!==$before['csrf']);
    $r=sf_run(['action'=>'read','capsule'=>$oldCapsule],$env);sc('SC23','old alias stays retired after regeneration',str_contains($r['stdout'],'Session service unavailable'));
    $s2=sf_login($u,$env);$r=sf_run(['action'=>'logout','capsule'=>$s],$env);$otherRead=sf_run(['action'=>'read','capsule'=>$s2],$env);
    sc('SC10','current logout durably retires only its binding',str_contains(sf_run(['action'=>'read','capsule'=>$s],$env)['stdout'],'Session service unavailable') && ($otherRead['json']['snapshot']['authenticated']??false));
    // Binding itself never adopts a timeout or polling activity extension.
    $before=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();sf_fetch($s2,'api/notifications.php');
    sc('SC15','polling cannot change account/onboarding expiry anchors',$before===\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch());
    $lazy=sf_run(['action'=>'lazy','capsule'=>$s2],$env);sc('SC21','PHP lazy timestamp callback participates in manifest/write fence',($lazy['json']['identity']['lazy_calls']??0)===1);
    sc('SC40','second independent HTTP instance uses same managed authority',sf_fetch($s2,'api/notifications.php',[],8166)['status']===200);
    // Every currently protected route group is denied after account epoch change.
    $revoked=sf_login($u,$env);$revoked2=sf_login($u,$env);\Bushisa\ManagedSession\q($root,'UPDATE users SET session_version=session_version+1 WHERE id=?',[$u['id']]);
    $routes=[];
    foreach(array_merge(glob($repo.'/*.php'),glob($repo.'/api/*.php'),glob($repo.'/admin/*.php')) as $path) {
        if(preg_match('/\brequire_login\(\);/',file_get_contents($path)))$routes[]=str_replace('\\','/',substr($path,strlen($repo)+1));
    }
    foreach($routes as $route) { sc('SC05','revoked account denied before private route '.$route,sf_fetch($revoked,$route)['status']===503); }
    sc('SC05','second session cannot adopt the newer account version',str_contains(sf_run(['action'=>'read','capsule'=>$revoked2],$env)['stdout'],'Session service unavailable'));
    foreach(['-1 microsecond','+0 seconds','+1 microsecond'] as $n=>$delta) {
        q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'boundary'.$n);$c=sf_login($f,$env);
        $l=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$f['id']])->fetch();q11_clock($root,bushisa_signup_time($l['unverified_expires_at'],$delta));
        $r=sf_run(['action'=>'write','capsule'=>$c],$env);sc('SC06','72-hour write boundary '.$delta,$n===0?($r['json']['snapshot']['authenticated']??false):str_contains($r['stdout'],'Session service unavailable'));
    }
    foreach(['-1 microsecond','+0 seconds','+1 microsecond'] as $n=>$delta) {
        q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'onboardedge'.$n);$code=bushisa_issue_signup_code($db,$f['id'],$f['email']);bushisa_verify_signup_code($db,$f['id'],$code);$c=sf_login($f,$env);
        $l=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$f['id']])->fetch();q11_clock($root,bushisa_signup_time($l['onboarding_expires_at'],$delta));
        $r=sf_run(['action'=>'read','capsule'=>$c],$env);sc('SC07','seven-day read boundary '.$delta,$n===0?($r['json']['snapshot']['authenticated']??false):str_contains($r['stdout'],'Session service unavailable'));
    }
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'latewrite');$c=sf_login($f,$env);
    $l=\Bushisa\ManagedSession\q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$f['id']])->fetch();
    sf_overlap($root,$f,$env,$c,'SC21','expire',$l['unverified_expires_at']);
    $o=\Bushisa\ManagedSession\q($root,'SELECT * FROM session_copy_obligations WHERE account_id=?',[$f['id']])->fetch();
    sc('SC18','Q11 component obligation preserves original deadline and unknown recovery bounds',$o && $o['effective_at']===$l['unverified_expires_at'] && $o['purge_due_at']===bushisa_signup_time($l['unverified_expires_at'],'+24 hours') && $o['state']==='pending_consumer' && $o['retain_until']===null && $o['publication_state']==='unpublished');
    bushisa_signup_check_account($db,$f['id']);sc('SC18','expiry retry does not duplicate or renew session-copy obligation',(int)\Bushisa\ManagedSession\q($root,'SELECT COUNT(*) FROM session_copy_obligations WHERE account_id=?',[$f['id']])->fetchColumn()===1);
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'versionrace');$c=sf_login($f,$env);sf_overlap($root,$f,$env,$c,'SC21','version');
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'eraserace');$c=sf_login($f,$env);$due=\Bushisa\ManagedSession\q($root,'SELECT unverified_expires_at FROM signup_lifecycle WHERE account_id=?',[$f['id']])->fetchColumn();
    $r=sf_overlap($root,$f,$env,$c,'SC22','erase',$due);
    sc('SC22','ordinary erasure remains separate from unfinished session-copy erasure',($r['answer']['json']['result']??null)==='complete' && \Bushisa\ManagedSession\q($root,'SELECT state FROM session_copy_obligations WHERE account_id=?',[$f['id']])->fetchColumn()==='pending_consumer');
    foreach(['verify','onboard'] as $kind)foreach(['logout','version'] as $op) {
        q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'response');$code=bushisa_issue_signup_code($db,$f['id'],$f['email']);
        if($kind==='onboard') { bushisa_verify_signup_code($db,$f['id'],$code); }
        $c=sf_login($f,$env);sf_handoff_race($root,$f,$env,$c,$kind,$op,$code);
    }
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$f=q11_user($root,'logoutwrite');$c=sf_login($f,$env);sf_handoff_race($root,$f,$env,$c,'write','logout');
    // Two already-read writers: publish one, reject the stale revision.
    $f=q11_user($root,'twowriters');$c=sf_login($f,$env);$ready=[];$children=[];
    foreach([0,1] as $i) { $ready[$i]=$dir.'/write-ready-'.$i.'.json';$children[$i]=sf_worker(['action'=>'implicit','capsule'=>$c,'ready'=>$ready[$i]],$env); }
    sf_until(fn()=>is_file($ready[0])&&is_file($ready[1]));$ids=array_map(fn($p)=>json_decode(file_get_contents($p),true),$ready);
    $root->beginTransaction();\Bushisa\ManagedSession\q($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$f['id']])->fetch();$blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
    foreach($ready as $path)touch($path.'.release');$waits=[];sf_until(function()use($root,$blocker,&$waits,$ids){$waits=sf_waits($root,$blocker);return count(array_intersect(array_column($waits,'requesting_connection'),array_column($ids,'connection_id')))===2;});$root->commit();
    $answers=array_map('sf_end',$children);$races[]=['case'=>'SC21','participants'=>$ids,'observed_row_waits'=>$waits,'variant'=>'two implicit writers'];
    sc('SC21','implicit shutdown CAS rejects one stale writer without a duplicate publication',count(array_filter($answers,fn($r)=>str_contains($r['stdout'],'Session service unavailable')))===1);
    $f=q11_user($root,'rotatewrite');$c=sf_login($f,$env);$rotated=sf_capsule();file_put_contents($rotated,file_get_contents($c));$ready=[];$children=[];
    foreach(['rotate','write'] as $i=>$action) { $ready[$i]=$dir.'/rotate-ready-'.$i.'.json';$children[$i]=sf_worker(['action'=>$action,'capsule'=>$i===0?$rotated:$c,'ready'=>$ready[$i]],$env); }
    sf_until(fn()=>is_file($ready[0])&&is_file($ready[1]));$ids=array_map(fn($path)=>json_decode(file_get_contents($path),true),$ready);
    $root->beginTransaction();\Bushisa\ManagedSession\q($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$f['id']])->fetch();$blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
    touch($ready[0].'.release');sf_until(fn()=>in_array($ids[0]['connection_id'],array_column(sf_waits($root,$blocker),'requesting_connection')));
    touch($ready[1].'.release');$waits=[];sf_until(function()use($root,$blocker,&$waits,$ids){$waits=sf_waits($root,$blocker);return count(array_intersect(array_column($waits,'requesting_connection'),array_column($ids,'connection_id')))===2;});$root->commit();
    $answers=array_map('sf_end',$children);$races[]=['case'=>'SC23','participants'=>$ids,'observed_row_waits'=>$waits,'variant'=>'regeneration versus stale explicit close'];
    sc('SC23','regeneration retires old writer and preserves newly mapped copy',($answers[0]['json']['snapshot']['authenticated']??false) && str_contains($answers[1]['stdout'],'Session service unavailable') && (sf_run(['action'=>'read','capsule'=>$rotated],$env)['json']['snapshot']['authenticated']??false));
    // Faults preserve the manifest even when the private object outlives rollback.
    foreach(['after_intent','after_object'] as $point) {
        $f=q11_user($root,'crash');$c=sf_login($f,$env);$before=(int)$root->query('SELECT COUNT(*) FROM session_copy_manifests WHERE state="reserved"')->fetchColumn();
        $r=sf_run(['action'=>'write','capsule'=>$c],$env+['BUSHISA_SESSION_CRASH'=>$point]);
        sc('SC27','crash leaves committed unpublished intent '.$point,$r['exit']===77 && (int)$root->query('SELECT COUNT(*) FROM session_copy_manifests WHERE state="reserved"')->fetchColumn()===$before+1);
        $r=sf_run(['action'=>'read','capsule'=>$c],$env);sc('SC27','retry reads previous committed copy and can publish safely '.$point,$r['json']['snapshot']['authenticated']??false);
    }
    $f=q11_user($root,'handlerfail');$c=sf_login($f,$env);$r=sf_run(['action'=>'write','capsule'=>$c],$env+['BUSHISA_SESSION_STORE_FAIL'=>'true']);
    sc('SC29','handler failure suppresses authenticated success without native fallback',str_contains($r['stdout'],'Session service unavailable'));
    foreach(['email'=>'synthetic@example.invalid','mfa_verified'=>true,'evidence_authority_assertion'=>'synthetic-secret','user_id'=>999999,'session_version'=>null,'account_generation'=>null] as $field=>$value) {
        $c=sf_login($f,$env);$r=sf_run(['action'=>'inject','field'=>$field,'value'=>$value,'capsule'=>$c],$env);
        sc(in_array($field,['session_version','account_generation'],true)?'SC03':'SC33','rejects uncontracted/private capability payload field '.$field,str_contains($r['stdout'],'Session service unavailable'));
    }
    $r=sf_run(['action'=>'read','capsule'=>sf_capsule()],$env+['DB_NAME'=>'production','DB_PORT'=>'1']);sc('SC39','unsafe destination denied before connection',!str_contains($r['stderr'],'SQLSTATE') && !isset($r['json']['snapshot']));
    foreach(['BUSHISA_SESSION_KEY'=>'','BUSHISA_SESSION_ROOT'=>$store.'/../outside','BUSHISA_SESSION_NAMESPACE'=>'wrong'] as $key=>$value) {
        $r=sf_run(['action'=>'read','capsule'=>sf_capsule()],$env+[$key=>$value]);sc('SC38','missing key or ambiguous store fails closed '.$key,!isset($r['json']['snapshot']));
    }
    $unmanaged=sf_capsule();$r=sf_run(['action'=>'unmanaged','capsule'=>$unmanaged],$env+['BUSHISA_SESSION_ROOT'=>$store]);
    // Only the exact newly created synthetic fixture; never inspect native
    // production sessions or enumerate an existing/shared session directory.
    $unmanagedClient=json_decode(file_get_contents($unmanaged),true);$unmanagedObject=dirname($unmanaged).'/isolated-unmanaged/sess_'.$unmanagedClient['sid'];
    sc('SC32','already active unmanaged handler is aborted without publishing pending content',str_contains($r['stdout'],'Session service unavailable') && !str_contains((string)file_get_contents($unmanagedObject),'synthetic-discarded-write'));
    // Actual fresh writer event, with a synthetic reused numeric ID and email.
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());$g1=q11_user($root,'g1');$g1s=sf_login($g1,$env);
    $root->beginTransaction();\Bushisa\ManagedSession\q($root,'DELETE FROM users WHERE id=?',[$g1['id']]);
    foreach(['signup_attempt_identities','signup_lifecycle'] as $table) { \Bushisa\ManagedSession\q($root,'DELETE FROM '.$table.' WHERE account_id=?',[$g1['id']]); }
    \Bushisa\ManagedSession\q($root,'INSERT INTO users (id,nust_student_id,email,password_hash,is_verified,is_suspended,is_active,account_status,session_version,created_at) VALUES (?,?,?,?,0,0,1,"pending_email",0,?)',[$g1['id'],$g1['student'],$g1['email'],password_hash('Synthet1c!Fixture',PASSWORD_DEFAULT),bushisa_signup_now($root)]);
    bushisa_signup_record_creation($root,$g1['id']);$root->commit();$g2s=sf_login($g1,$env);
    sc('SC04','old generation denied despite reused numeric ID/email/version',str_contains(sf_run(['action'=>'read','capsule'=>$g1s],$env)['stdout'],'Session service unavailable'));
    sf_fetch($g1s,'logout.php');sc('SC04','old-generation logout preserves fresh-generation binding',sf_run(['action'=>'read','capsule'=>$g2s],$env)['json']['snapshot']['authenticated']??false);
    sf_login_replacement_race($root,$env);
    $f=q11_user($root,'privilege');\Bushisa\ManagedSession\q($root,'UPDATE users SET role="admin" WHERE id=?',[$f['id']]);$c=sf_login($f,$env);
    \Bushisa\ManagedSession\q($root,'UPDATE users SET role="user" WHERE id=?',[$f['id']]);$r=sf_run(['action'=>'read','capsule'=>$c],$env);
    sc('SC12','cached role cannot override authoritative role withdrawal',($r['json']['snapshot']['role']??null)==='user');
    $r=sf_run(['action'=>'inject','field'=>'mfa_verified','value'=>true,'capsule'=>$c],$env);
    sc('SC12','client-style cached MFA cannot create reviewed authority',str_contains($r['stdout'],'Session service unavailable'));
    $f=q11_user($root,'banepoch');$c=sf_login($f,$env);
    \Bushisa\ManagedSession\q($root,'UPDATE users SET is_suspended=1,session_version=session_version+1 WHERE id=?',[$f['id']]);
    sc('SC11','authoritative suspension/epoch denies old binding',str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));
    \Bushisa\ManagedSession\q($root,'UPDATE users SET is_suspended=0 WHERE id=?',[$f['id']]);
    sc('SC11','lifting a versioned restriction cannot resurrect old binding',str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));
    $switch=sf_login($f,$env);$a=sf_run(['action'=>'login','email'=>$g1['email'],'capsule'=>$switch],$env);
    sc('SC14','fresh password authentication switches principal without old identity/capability',($a['json']['snapshot']['user_id']??null)===$g1['id'] && !$a['json']['snapshot']['mfa_present']);
    // Corrupt manifested identifiers are data, never paths supplied to an adapter.
    $f=q11_user($root,'locators');$c=sf_login($f,$env);$capsule=json_decode(file_get_contents($c),true);
    $index=new \Bushisa\ManagedSession\Handler(\Bushisa\ManagedSession\connection());
    $b=\Bushisa\ManagedSession\q($root,'SELECT * FROM session_bindings WHERE sid_hmac=?',[$index->hmac($capsule['sid'])])->fetch();
    $m=\Bushisa\ManagedSession\q($root,'SELECT * FROM session_copy_manifests WHERE copy_ref=?',[$b['current_copy']])->fetch();
    foreach(['../outside','/absolute','C:\\outside','\\\\server\\share',"bad\0name"] as $i=>$locator) {
        \Bushisa\ManagedSession\q($root,'INSERT INTO session_copy_manifests (copy_ref,session_ref,binding_epoch,expected_revision,namespace_id,adapter_version,instance_id,state,created_at) VALUES (?,?,?,?,?,"synthetic_local_v1","fixture_shared","published",?)',[$locator,$b['session_ref'],$b['binding_epoch'],(int)$b['revision']-1,$namespace,bushisa_signup_now($root)]);
        \Bushisa\ManagedSession\q($root,'UPDATE session_bindings SET current_copy=? WHERE session_ref=?',[$locator,$b['session_ref']]);
        sc('SC30','unsafe manifested locator rejected variant '.$i,str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));
        \Bushisa\ManagedSession\q($root,'UPDATE session_bindings SET current_copy=? WHERE session_ref=?',[$b['current_copy'],$b['session_ref']]);
        \Bushisa\ManagedSession\q($root,'DELETE FROM session_copy_manifests WHERE copy_ref=?',[$locator]);
    }
    \Bushisa\ManagedSession\q($root,'UPDATE session_copy_manifests SET binding_epoch=binding_epoch+1 WHERE copy_ref=?',[$b['current_copy']]);
    sc('SC31','stale manifest epoch cannot expose another copy',str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));
    \Bushisa\ManagedSession\q($root,'UPDATE session_copy_manifests SET binding_epoch=? WHERE copy_ref=?',[$b['binding_epoch'],$b['current_copy']]);
    $object=$store.DIRECTORY_SEPARATOR.$b['current_copy'].'.sealed';$link=$store.DIRECTORY_SEPARATOR.'synthetic-hardlink';
    if (@link($object,$link)) {
        sc('SC30','hardlinked fixture object refused',str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));unlink($link);
    } else { $environment['hardlink_variant']='NOT RUN: platform fixture creation unavailable'; }
    if (PHP_OS_FAMILY!=='Windows') {
        $original=$object.'.original';rename($object,$original);
        if (@symlink($original,$object)) { sc('SC30','symlinked fixture object refused',str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));unlink($object); }
        else { $environment['symlink_variant']='NOT RUN: platform fixture creation unavailable'; }
        rename($original,$object);
    } else { $environment['symlink_reparse_variant']='NOT RUN: Windows platform permission/equivalence unverified'; }
    $cipher=file_get_contents($object);sf_fetch($c,'logout.php');file_put_contents($object,$cipher);unset($cipher);
    sc('SC34','synthetically restored encrypted copy cannot bypass current retirement registry',str_contains(sf_run(['action'=>'read','capsule'=>$c],$env)['stdout'],'Session service unavailable'));
    $c=sf_login($f,$env);$published=(int)$root->query('SELECT COUNT(*) FROM session_copy_manifests WHERE state="published"')->fetchColumn();
    $quoted=bushisa_quote_disposable_identifier($settings['name']);$root->exec("REVOKE INSERT ON $quoted.session_copy_manifests FROM ".$runtime['account']);
    $r=sf_run(['action'=>'write','capsule'=>$c],$env);sc('SC39','missing manifest privilege cannot publish or fall back',str_contains($r['stdout'],'Session service unavailable') && (int)$root->query('SELECT COUNT(*) FROM session_copy_manifests WHERE state="published"')->fetchColumn()===$published);
    $root->exec("GRANT INSERT ON $quoted.session_copy_manifests TO ".$runtime['account']);
    // Real upgrade leaves all pre-014 identities/sessions unmapped.
    $base=$settings['name'];putenv('DB_NAME='.$base.'_upgrade');bushisa_bootstrap_disposable_database($repo.'/database/bushisa.sql');$pre=sf_cli([$repo.'/bin/migrate.php','--through=013']);$up=q11_connect();q11_install($up);$old=q11_user($up,'old013');$rows=\Bushisa\ManagedSession\q($up,'SELECT * FROM users')->fetchAll();
    $upgrade=sf_cli([$repo.'/bin/migrate.php']);$again=sf_cli([$repo.'/bin/migrate.php']);
    sc('SC39','013 upgrade/repeat preserves prior data and creates no historical binding',$pre['exit']===0 && $upgrade['exit']===0 && $again['exit']===0 && $rows===\Bushisa\ManagedSession\q($up,'SELECT * FROM users')->fetchAll() && (int)$up->query('SELECT COUNT(*) FROM session_bindings')->fetchColumn()===0);
    $migrations[]=['kind'=>'013-upgrade-repeat','exit'=>[$pre['exit'],$upgrade['exit'],$again['exit']]];putenv('DB_NAME='.$base);
    $diagnostics=json_encode([$root->query('SELECT * FROM session_copy_manifests')->fetchAll(),$root->query('SELECT * FROM session_lineage')->fetchAll(),$root->query('SELECT * FROM session_handoffs')->fetchAll(),$root->query('SELECT * FROM session_copy_obligations')->fetchAll(),$races],JSON_THROW_ON_ERROR);
    sc('SC33','manifests, lineage, obligations and diagnostics exclude secrets/personal payload',!preg_match('/"(?:sid|cookie|csrf_token|email|body|payload|mfa_verified|locator|path)"\s*:/',$diagnostics)&&!str_contains($diagnostics,'@students.nust.ac.zw'));
} catch(Throwable $e) {
    $failure=['type'=>get_class($e),'message'=>$e instanceof PDOException?'SQL_'.$e->getCode().'_'.($e->errorInfo[1]??0):$e->getMessage(),'line'=>$e->getLine(),'file'=>basename($e->getFile()),'last_worker_error'=>substr($GLOBALS['sf_last_child']['stderr']??'',0,1200)];
} finally {
    if(isset($root)&&$root->inTransaction())$root->rollBack();
    foreach($servers as $s){proc_terminate($s);proc_close($s);}
    $e=['suite'=>'Generation-bound session write fence','started_utc'=>$started,'finished_utc'=>gmdate('c'),'environment'=>$environment,'migrations'=>$migrations,'checks'=>$checks,'races'=>$races,
        'totals'=>['assertions'=>count($checks),'passed'=>count(array_filter($checks,fn($c)=>$c['result']==='PASS'))],'failure'=>$failure];
    file_put_contents($dir.'/evidence.json',json_encode($e,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    echo json_encode(['result'=>$failure?'FAIL':'PASS','evidence'=>$dir.'/evidence.json','totals'=>$e['totals'],'failure'=>$failure],JSON_UNESCAPED_SLASHES).PHP_EOL;
} exit($failure?1:0);
