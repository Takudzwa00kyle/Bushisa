<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/support/session_copy_fixture.php';
require_once __DIR__.'/../php/http_test_barrier.php';
use function Bushisa\SessionCopies\{attempt,claim,step,run,recovery_instructions};
bushisa_q11_synthetic_guard();$settings=bushisa_require_disposable_test_database('Q11 synthetic session consumer');
$repo=dirname(__DIR__);$nonce=bin2hex(random_bytes(16));$dir=$repo.'/.tmp/sc-run-'.$nonce;mkdir($dir,0700,true);
$storeRoot=realpath($repo.'/.tmp').DIRECTORY_SEPARATOR.'sf-storage-'.$nonce;mkdir($storeRoot,0700);
$namespace='ss_'.$nonce;file_put_contents($storeRoot.'/.synthetic-session-store',$namespace);
putenv('BUSHISA_SESSION_SYNTHETIC=true');putenv('BUSHISA_SESSION_CLEANUP_ENABLED=true');putenv('BUSHISA_SESSION_ROOT='.$storeRoot);putenv('BUSHISA_SESSION_NAMESPACE='.$namespace);putenv('BUSHISA_SESSION_KEY='.bin2hex(random_bytes(32)));
putenv('MAIL_OUTBOX_KEY='.base64_encode(random_bytes(32)));putenv('SIGNUP_CODE_HMAC_KEY='.bin2hex(random_bytes(32)));
$checks=[];$races=[];$migrations=[];$servers=[];$children=[];$environment=[];$failure=null;$phase='startup';$started=gmdate('c');
function cc(string $case,string $check,bool $ok): void {
    global $checks;$checks[]=['case'=>$case,'check'=>$check,'result'=>$ok?'PASS':'FAIL'];if(!$ok)throw new RuntimeException($case.': '.$check);
}
function cp(array $args,array $env=[]): array {
    global $repo;$pipes=[];$p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,array_merge(getenv(),$env));
    if(!is_resource($p))throw new RuntimeException('SESSION_CHILD_UNAVAILABLE');fclose($pipes[0]);return [$p,$pipes];
}
function ce(array $child): array {
    [$p,$pipes]=$child;$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    $r=['exit'=>proc_close($p),'json'=>json_decode($out,true),'stdout'=>$out,'stderr'=>$err];$GLOBALS['sc_last_child']=$r;return $r;
}
function cli(array $args,array $env=[]): array { return ce(cp($args,$env)); }
function worker(array $job,array $env=[],bool $session=false): array {
    global $dir,$repo;$path=$dir.'/job-'.bin2hex(random_bytes(8)).'.json';file_put_contents($path,json_encode($job,JSON_THROW_ON_ERROR));
    return cp([$repo.'/tests/support/'.($session?'session_fence_worker.php':'session_copy_worker.php'),'--job='.$path],$env);
}
function call_worker(array $job,array $env=[],bool $session=false): array { return ce(worker($job,$env,$session)); }
function capsule(): string { global $dir;$path=$dir.'/client-'.bin2hex(random_bytes(8)).'.json';file_put_contents($path,'{}');return $path; }
function login_session(array $u,array $env): string {
    $s=capsule();$r=call_worker(['action'=>'login','email'=>$u['email'],'capsule'=>$s],$env,true);
    if($r['exit']!==0||!($r['json']['result']??false)) {
        $code=is_string($r['json']['login_error']??null)?preg_replace('/[^A-Za-z0-9 ]/','',$r['json']['login_error']):'NO_RESULT';
        $stderr=preg_match('/\ASESSION_[A-Z_]+\s*\z/D',trim($r['stderr']))?trim($r['stderr']):'UNCLASSIFIED';
        throw new RuntimeException('SESSION_SYNTHETIC_LOGIN_FAILED_'.$code.'_'.$stderr);
    }
    return $s;
}
function clock_reset(PDO $p): void { q11_clock($p,(string)$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn()); }
function due_clock(PDO $p,array $o,string $offset='+0 seconds'): void { q11_clock($p,bushisa_signup_time($o['purge_due_at'],$offset)); }
function fresh(PDO $root,array $env,string $tag): array {
    clock_reset($root);$u=q11_user($root,$tag);
    try { $u['capsule']=login_session($u,$env); }
    catch(Throwable $e) {
        $account=sc_q($root,'SELECT account_status,is_active,is_verified,session_version,created_at FROM users WHERE id=?',[$u['id']])->fetch();
        $lifecycle=sc_q($root,'SELECT anchor_status,registered_at,unverified_expires_at,expired_at,version FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
        throw new RuntimeException('SESSION_FIXTURE_LOGIN_CONTEXT_'.$e->getMessage().'_'.json_encode([$account,$lifecycle,bushisa_signup_now($root)],JSON_UNESCAPED_SLASHES));
    }
    $u['key']=sc_q($root,'SELECT signup_attempt_key FROM users WHERE id=?',[$u['id']])->fetchColumn();return $u;
}
function copies(PDO $p,array $u): array
{
    if(operational_column_exists($p,'session_copy_manifests','account_key')) {
        return sc_q($p,'SELECT * FROM session_copy_manifests WHERE account_key=? ORDER BY copy_ref',[$u['key']])->fetchAll();
    }
    return sc_q($p,'SELECT m.* FROM session_copy_manifests m JOIN session_bindings b ON b.session_ref=m.session_ref WHERE b.account_id=? AND b.account_key=? ORDER BY m.copy_ref',[$u['id'],$u['key']])->fetchAll();
}
function job(PDO $p,string $event): array { return sc_q($p,'SELECT * FROM session_copy_cleanup_jobs WHERE event_ref=?',[$event])->fetch()?:[]; }
function object_path(string $copy): string { global $storeRoot;if(!\Bushisa\SessionCopies\key_ok($copy))throw new RuntimeException('SESSION_FIXTURE_COPY_REFUSED');return $storeRoot.DIRECTORY_SEPARATOR.$copy.'.sealed'; }
function absent_copies(PDO $p,array $u): bool { foreach(copies($p,$u) as $m)if(file_exists(object_path($m['copy_ref'])))return false;return true; }
function until(callable $fn,?CurlMultiHandle $multi=null): void {
    $end=microtime(true)+12;do{if($multi)curl_multi_exec($multi,$running);if($fn())return;usleep(10000);}while(microtime(true)<$end);throw new RuntimeException('SESSION_OBSERVATION_TIMEOUT');
}
function waits(PDO $p,int $blocker): array {
    return sc_q($p,'SELECT r.PROCESSLIST_ID AS requesting_connection,b.PROCESSLIST_ID AS blocking_connection FROM performance_schema.data_lock_waits w JOIN performance_schema.threads r ON r.THREAD_ID=w.REQUESTING_THREAD_ID JOIN performance_schema.threads b ON b.THREAD_ID=w.BLOCKING_THREAD_ID WHERE b.PROCESSLIST_ID=?',[$blocker])->fetchAll();
}
function request(string $capsule,string $action,array $headers=[],int $port=8175): CurlHandle {
    $client=json_decode(file_get_contents($capsule),true);$h=curl_init('http://127.0.0.1:'.$port.'/tests/support/session_fence_probe.php?action='.$action);
    curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>23,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>array_merge(['Cookie: PHPSESSID='.$client['sid']],$headers)]);return $h;
}
function finish_event(PDO $p,string $event): string { for($i=0;$i<10;$i++){ $s=attempt($p,$event);if($s!=='resumable')return $s;}throw new RuntimeException('SESSION_FIXTURE_PROGRESS_LIMIT'); }
function prepare_item(PDO $p,string $event,string $token): array {
    if(!claim($p,$event,$token))throw new RuntimeException('SESSION_FIXTURE_CLAIM_FAILED');
    for($i=0;$i<12;$i++) {
        $item=sc_q($p,'SELECT * FROM session_copy_purge_items WHERE event_ref=? AND state="deleting" ORDER BY copy_ref LIMIT 1',[$event])->fetch();
        if($item)return $item;step($p,$event,$token);
    }throw new RuntimeException('SESSION_FIXTURE_INTENT_MISSING');
}
function retry_ready(PDO $root,string $event): void { q11_clock($root,bushisa_signup_time(bushisa_signup_now($root),'+61 seconds')); }
function pair_barrier(): array {
    $id='sf_'.bin2hex(random_bytes(16));$point='session_cleanup';
    $base=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bushisa-http-barriers'.DIRECTORY_SEPARATOR.$id.'_'.$point;return [$id,$point,$base];
}
function duplicate_race(PDO $root,array $u,array $o,array $env): void {
    global $races;[$barrier,$point,$base]=pair_barrier();$workers=[];
    foreach([0,1] as $i)$workers[]=worker(['action'=>'attempt','event'=>$o['event_ref'],'barrier'=>$barrier,'point'=>$point],$env);
    try {
        until(fn()=>count(glob($base.'-*.json')?:[])===2);$records=array_map(fn($p)=>json_decode(file_get_contents($p),true),glob($base.'-*.json'));
        $root->beginTransaction();sc_q($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$u['id']])->fetch();$blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
        foreach($records as $r)touch($base.'-'.$r['pid'].'.release');$observed=[];
        until(function()use($root,$blocker,&$observed,$records){$observed=waits($root,$blocker);return count(array_intersect(array_column($observed,'requesting_connection'),array_column($records,'connection_id')))===2;});$root->commit();
        $answers=array_map('ce',$workers);$workers=[];
        cc('SC25','duplicate processes/connections overlap at the engine fence and yield one receipt',count(array_unique(array_column($records,'pid')))===2&&count(array_unique(array_column($records,'connection_id')))===2&&array_column($answers,'exit')===[0,0]&&(int)sc_q($root,'SELECT COUNT(*) FROM session_copy_purge_receipts WHERE event_ref=?',[$o['event_ref']])->fetchColumn()===1);
        cc('SC25','all duplicate-target copies are verified absent',absent_copies($root,$u));
        $races[]=['case'=>'SC25','kind'=>'engine_wait','participants'=>$records,'observed_row_waits'=>$observed,'results'=>array_column(array_column($answers,'json'),'result')];
    } finally { if($root->inTransaction())$root->rollBack();foreach($workers as $w){proc_terminate($w[0]);ce($w);} }
}
try {
    $phase='bootstrap';bushisa_bootstrap_disposable_database($repo.'/database/bushisa.sql');
    $through=cli([$repo.'/bin/migrate.php','--through=014']);
    if($through['exit']!==0)throw new RuntimeException('SESSION_UPGRADE_BASELINE_FAILED');
    $phase='fixture-clock';$root=q11_connect();q11_install($root);
    if(!sc_q($root,'SELECT clock_at FROM q11_fixture_clock WHERE singleton=1')->fetchColumn())throw new RuntimeException('SESSION_FIXTURE_CLOCK_REQUIRED');
    // Seed a v1-only account/copy before 015. Migration must not backfill
    // writer coverage, object generations or an inferred legacy mapping.
    $phase='pre-015-user';$legacy=q11_user($root,'pre015');$legacy['key']=sc_q($root,'SELECT signup_attempt_key FROM users WHERE id=?',[$legacy['id']])->fetchColumn();
    $phase='pre-015-login';$legacy['capsule']=login_session($legacy,['DB_USER'=>$settings['user'],'DB_PASSWORD'=>$settings['password'],'SESSION_COOKIE_SECURE'=>'false']);
    $legacyCopy=copies($root,$legacy)[0];
    $phase='015-upgrade';$m=cli([$repo.'/bin/migrate.php']);$repeat=cli([$repo.'/bin/migrate.php']);
    cc('SC39','014-to-015 existing-schema upgrade and repeat migration',$m['exit']===0&&str_contains($m['stdout'],'Applying 015_')&&$repeat['exit']===0&&str_contains($repeat['stdout'],'Skipping 015_'));
    cc('SC20','upgrade leaves old session rows uncertified and intact',!isset($legacyCopy['object_generation'])&&sc_q($root,'SELECT COUNT(*) FROM session_copy_coverage WHERE account_key=?',[$legacy['key']])->fetchColumn()===0);
    $migrations[]=['kind'=>'014-upgrade-repeat','exit'=>[$m['exit'],$repeat['exit']],'pre_upgrade_manifest_adapter'=>$legacyCopy['adapter_version']];
    $phase='runtime-grants';$runtime=sc_runtime($root,$settings['name']);$db=\Bushisa\ManagedSession\connection();
    // Separate fixture administrator from actual execution account.
    $db=q11_connect($runtime);$db->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $env=['DB_USER'=>$runtime['user'],'DB_PASSWORD'=>$runtime['password'],'SESSION_COOKIE_SECURE'=>'false'];
    $environment=['php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$root->getAttribute(PDO::ATTR_SERVER_VERSION),'mysqlnd'=>phpversion('mysqlnd'),'database'=>$settings['name'],'port'=>$settings['port'],
        'settings'=>$root->query('SELECT @@server_uuid AS server_uuid,@@time_zone AS time_zone,@@transaction_isolation AS isolation_level,@@sql_mode AS sql_mode')->fetch(),
        'consumer_settings'=>$db->query('SELECT @@time_zone AS time_zone,@@transaction_isolation AS isolation_level')->fetch(),'runtime_grants'=>$root->query('SHOW GRANTS FOR '.$runtime['account'])->fetchAll(PDO::FETCH_COLUMN)];
    foreach([8175,8176] as $port) {
        $pipes=[];$p=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,'-t',$repo],[0=>['pipe','r'],1=>['file',$dir.'/http-'.$port.'.log','a'],2=>['file',$dir.'/http-'.$port.'.log','a']],$pipes,$repo,array_merge(getenv(),$env));
        if(!is_resource($p))throw new RuntimeException('SESSION_HTTP_UNAVAILABLE');fclose($pipes[0]);$servers[]=$p;
        until(function()use($port){$s=@fsockopen('127.0.0.1',$port,$n,$e,.1);if($s){fclose($s);return true;}return false;});
    }
    $store=new \Bushisa\ManagedSession\Store(\Bushisa\SessionCopies\guard());
    // A migrated v1 attempt is expired only after 015, proving a missing
    // provenance/coverage row blocks physical cleanup rather than sweeping it.
    $legacyObligation=sc_expire($root,$db,$legacy);
    cc('SC20','pre-upgrade copy cannot be inferred or physically purged',attempt($db,$legacyObligation['event_ref'])==='skipped'&&job($root,$legacyObligation['event_ref'])['state']==='blocked'&&file_exists(object_path($legacyCopy['copy_ref'])));

    $u=fresh($root,$env,'scope');$before=copies($root,$u);$activeEvent=sc_event($root,$u);
    cc('SC16','before actual expiry no obligation or cleanup is manufactured',attempt($db,$activeEvent)==='skipped'&&!absent_copies($root,$u)&&!job($root,$activeEvent));
    $u=fresh($root,$env,'deadline-before');$o=sc_expire($root,$db,$u);q11_clock($root,bushisa_signup_time($o['purge_due_at'],'-1 microsecond'));
    cc('SC16','no physical deletion before original expiry+24h deadline',attempt($db,$o['event_ref'])==='skipped'&&!absent_copies($root,$u)&&job($root,$o['event_ref'])['state']==='waiting'&&!sc_q($root,'SELECT 1 FROM session_copy_purge_receipts WHERE event_ref=?',[$o['event_ref']])->fetchColumn());
    q11_clock($root,$o['purge_due_at']);
    cc('SC16','exact approved physical deadline completes without clock renewal',finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u));
    $receipt=sc_q($root,'SELECT * FROM session_copy_purge_receipts WHERE event_ref=?',[$o['event_ref']])->fetch();
    cc('SC16','receipt retains original expiry and expiry+24h anchors',$receipt['effective_at']===$o['effective_at']&&$receipt['purge_due_at']===$o['purge_due_at']&&$receipt['component']==='session_copies_v1');
    $recovery=sc_q($root,'SELECT publication_state,copy_inventory,retain_until,live_purged_at FROM session_copy_recovery WHERE event_ref=?',[$o['event_ref']])->fetch();
    cc('SC37','synthetic purge leaves independent recovery unpublished and unverified',$recovery['publication_state']==='unpublished'&&$recovery['copy_inventory']==='unverified'&&$recovery['retain_until']===null&&$recovery['live_purged_at']!==null);
    $u=fresh($root,$env,'deadline-after');$o=sc_expire($root,$db,$u);q11_clock($root,bushisa_signup_time($o['purge_due_at'],'+1 microsecond'));
    cc('SC16','late consumer completes original obligation and records overdue alert',finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u)&&(bool)sc_q($root,'SELECT 1 FROM session_copy_cleanup_alerts WHERE event_ref=? AND reason_code="SESSION_Q11_CLEANUP_OVERDUE"',[$o['event_ref']])->fetchColumn());
    $phase='approved-onboarding';$u=fresh($root,$env,'onboarding');$code=bushisa_issue_signup_code($db,$u['id'],$u['email']);bushisa_verify_signup_code($db,$u['id'],$code);$u['capsule']=login_session($u,$env);$o=sc_expire($root,$db,$u);
    due_clock($root,$o);$onboardingResult=finish_event($db,$o['event_ref']);$onboardingJob=job($root,$o['event_ref']);
    cc('SC16','unfinished email-approved attempt uses first-approval +7d: '.$onboardingResult.'/'.($onboardingJob['state']??'missing').'/'.($onboardingJob['stage']??'missing').'/'.($onboardingJob['error_code']??'none').'/absent='.(absent_copies($root,$u)?'true':'false'),$onboardingResult==='complete'&&absent_copies($root,$u));
    $u=fresh($root,$env,'active-onboarding');$code=bushisa_issue_signup_code($db,$u['id'],$u['email']);bushisa_verify_signup_code($db,$u['id'],$code);$u['capsule']=login_session($u,$env);$active=sc_event($root,$u);
    cc('SC22','still-eligible onboarding is not physically cleaned early',attempt($db,$active)==='skipped'&&!job($root,$active)&&!absent_copies($root,$u));
    $u=fresh($root,$env,'unrelated');$other=fresh($root,$env,'other');$otherBefore=copies($root,$other);$o=sc_expire($root,$db,$u);
    due_clock($root,$o);cc('SC22','Q11 cleanup preserves unrelated live account sessions',finish_event($db,$o['event_ref'])==='complete'&&$otherBefore===copies($root,$other)&&!absent_copies($root,$other));
    $u=fresh($root,$env,'missing');$o=sc_expire($root,$db,$u);$copy=copies($root,$u)[0];unlink(object_path($copy['copy_ref']));
    due_clock($root,$o);cc('SC28','missing exact manifested object reconciles without assuming a missing store',finish_event($db,$o['event_ref'])==='complete');
    $u=fresh($root,$env,'latewrite');$o=sc_expire($root,$db,$u);$beforeCount=count(copies($root,$u));
    $late=call_worker(['action'=>'write','capsule'=>$u['capsule']],$env,true);
    $writeDenied=$late['exit']!==0||($late['json']['failed']??false)===true||str_contains($late['stderr'],'SESSION_AUTHORITY_CHANGED');$stable=count(copies($root,$u))===$beforeCount;$present=!absent_copies($root,$u);due_clock($root,$o);$lateCleanup=finish_event($db,$o['event_ref']);$gone=absent_copies($root,$u);
    cc('SC22','late write denied/preserved/cleaned: denied='.(int)$writeDenied.' stable='.(int)$stable.' before_present='.(int)$present.' cleanup='.$lateCleanup.' absent='.(int)$gone,$stable&&$present&&$writeDenied&&$lateCleanup==='complete'&&$gone);
    $u=fresh($root,$env,'laterotate');$o=sc_expire($root,$db,$u);$beforeCount=count(copies($root,$u));
    $late=call_worker(['action'=>'rotate','capsule'=>$u['capsule']],$env,true);
    $rotationDenied=$late['exit']!==0||($late['json']['failed']??false)===true||str_contains($late['stderr'],'SESSION_AUTHORITY_CHANGED');due_clock($root,$o);cc('SC23','late regeneration cannot create an unfenced alias',count(copies($root,$u))===$beforeCount&&$rotationDenied&&finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u));

    $u=fresh($root,$env,'handoff');$binding=sc_q($root,'SELECT * FROM session_bindings WHERE account_id=? ORDER BY created_at DESC LIMIT 1',[$u['id']])->fetch();
    $handoff=bin2hex(random_bytes(32));sc_q($root,'INSERT INTO session_handoffs (event_ref,session_ref,account_key,old_version,new_version,expected_revision,event_type,state,created_at) VALUES (?,?,?,?,?,?,"email_approval","pending",?)',[$handoff,$binding['session_ref'],$u['key'],(int)$binding['auth_version'],(int)$binding['auth_version']+1,$binding['revision'],bushisa_signup_now($root)]);
    $o=sc_expire($root,$db,$u);due_clock($root,$o);
    cc('SC24','cleanup fences a pending one-use handoff without restoring its session',finish_event($db,$o['event_ref'])==='complete'&&sc_q($root,'SELECT state FROM session_handoffs WHERE event_ref=?',[$handoff])->fetchColumn()==='fenced'&&absent_copies($root,$u));

    $u=fresh($root,$env,'locked-object');$o=sc_expire($root,$db,$u);due_clock($root,$o);$copy=copies($root,$u)[0]['copy_ref'];
    $ready=$dir.'/locked-'.bin2hex(random_bytes(8)).'.json';$locker=worker(['action'=>'lock','copy'=>$copy,'ready'=>$ready],$env);
    until(fn()=>is_file($ready));$lockIdentity=json_decode(file_get_contents($ready),true);$locked=attempt($db,$o['event_ref']);
    touch($ready.'.release');$lockResult=ce($locker);
    $races[]=['case'=>'SC29','kind'=>'fixture_file_lock','locker'=>$lockIdentity,'cleanup_connection'=>(int)$db->query('SELECT CONNECTION_ID()')->fetchColumn()];
    if($locked==='incomplete'&&file_exists(object_path($copy))&&($lockResult['json']['result']??null)==='unlocked') {
        cc('SC29','locked object remains pending with a distinct bounded worker',true);
        q11_clock($root,bushisa_signup_time($o['purge_due_at'],'+31 seconds'));
        cc('SC29','retry after object lock release verifies and completes',finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u));
    } elseif($locked==='complete'&&absent_copies($root,$u)&&($lockResult['json']['result']??null)==='unlocked') {
        $checks[]=['case'=>'SC29','check'=>'cross-process advisory flock variant is not enforced by this Windows PHP/filesystem profile','result'=>'NOT RUN'];
    } else {
        cc('SC29','locked object failure boundary has a documented result: '.$locked.'/'.($lockResult['json']['result']??'unknown'),false);
    }
    $u=fresh($root,$env,'duplicates');call_worker(['action'=>'write','capsule'=>$u['capsule']],$env,true);$o=sc_expire($root,$db,$u);due_clock($root,$o);duplicate_race($root,$u,$o,$env);
    $u=fresh($root,$env,'staletoken');$o=sc_expire($root,$db,$u);due_clock($root,$o);$a=bin2hex(random_bytes(32));$b=bin2hex(random_bytes(32));claim($db,$o['event_ref'],$a);retry_ready($root,$o['event_ref']);
    cc('SC26','new claim fences expired claimant rather than assuming quiescence',claim($db,$o['event_ref'],$b)&&step($db,$o['event_ref'],$a)==='stale_claim'&&!absent_copies($root,$u));
    // Release the fixture claim through its immutable technical lease, not clocks.
    retry_ready($root,$o['event_ref']);cc('SC26','fresh claimant resumes original obligation',finish_event($db,$o['event_ref'])==='complete');
    // Physical store failure leaves exact objects and the original deadline
    // intact; retry occurs only after the bounded technical backoff.
    $u=fresh($root,$env,'unlinkfail');$o=sc_expire($root,$db,$u);due_clock($root,$o);
    $failed=cli([$repo.'/bin/cleanup_session_copies.php','--event='.$o['event_ref'], '--steps=25'],array_merge($env,['BUSHISA_SESSION_CLEANUP_FAIL'=>'unlink_failure']));
    cc('SC29','unlink failure is code-free, incomplete and retryable',$failed['exit']===0&&($failed['json']['result']??null)==='incomplete'&&!absent_copies($root,$u));
    q11_clock($root,bushisa_signup_time($o['purge_due_at'],'+31 seconds'));
    cc('SC29','retry after storage repair removes only manifested objects',finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u));

    // A committed intent followed by process loss before unlink is recovered
    // after the technical claim lease; the Q11 clock is never rewritten.
    $u=fresh($root,$env,'crashintent');$o=sc_expire($root,$db,$u);due_clock($root,$o);
    $crash=cli([$repo.'/bin/cleanup_session_copies.php','--event='.$o['event_ref'], '--steps=25'],array_merge($env,['BUSHISA_SESSION_CLEANUP_CRASH'=>'after_purge_intent_commit']));
    cc('SC27','crash after durable physical intent leaves object and deadline unchanged',$crash['exit']===77&&!absent_copies($root,$u));
    q11_clock($root,bushisa_signup_time($o['purge_due_at'],'+61 seconds'));
    cc('SC27','retry after intent crash completes same obligation',finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u)&&sc_q($root,'SELECT purge_due_at FROM session_copy_obligations WHERE event_ref=?',[$o['event_ref']])->fetchColumn()===$o['purge_due_at']);

    $u=fresh($root,$env,'crashunlink');$o=sc_expire($root,$db,$u);due_clock($root,$o);
    $crash=cli([$repo.'/bin/cleanup_session_copies.php','--event='.$o['event_ref'], '--steps=25'],array_merge($env,['BUSHISA_SESSION_CLEANUP_CRASH'=>'after_unlink']));
    cc('SC28','crash between unlink and SQL acknowledgment leaves no false receipt',$crash['exit']===77&&absent_copies($root,$u)&&!sc_q($root,'SELECT 1 FROM session_copy_purge_receipts WHERE event_ref=?',[$o['event_ref']])->fetchColumn());
    q11_clock($root,bushisa_signup_time($o['purge_due_at'],'+61 seconds'));
    cc('SC28','retry verifies exact absence before stable receipt',finish_event($db,$o['event_ref'])==='complete'&&absent_copies($root,$u));

    $u=fresh($root,$env,'changedobject');$o=sc_expire($root,$db,$u);due_clock($root,$o);$copy=copies($root,$u)[0];$token=bin2hex(random_bytes(32));
    if(!claim($db,$o['event_ref'],$token))throw new RuntimeException('SESSION_FIXTURE_CLAIM_FAILED');
    $intent=null;for($i=0;$i<12;$i++) { $intent=sc_q($root,'SELECT * FROM session_copy_purge_items WHERE event_ref=? AND state="deleting"',[$o['event_ref']])->fetch();if($intent)break;step($db,$o['event_ref'],$token); }
    $changed=object_path($copy['copy_ref']);$bytes=file_get_contents($changed);$wrong=\Bushisa\ManagedSession\Store::MAGIC.str_repeat('0',32).str_repeat('x',max(1,strlen($bytes)-37));file_put_contents($changed,$wrong);
    try { step($db,$o['event_ref'],$token); } catch(Throwable) { $objectChanged=true; }
    q11_clock($root,bushisa_signup_time($o['purge_due_at'],'+61 seconds'));
    $changedAttempt=attempt($db,$o['event_ref']);$changedJob=job($root,$o['event_ref']);
    cc('SC31','changed exact object identity blocks deletion and receipt: direct='.(int)isset($objectChanged).' retry='.$changedAttempt.'/'.($changedJob['state']??'missing').'/'.($changedJob['error_code']??'none'),isset($objectChanged)&&$changedAttempt==='incomplete'&&$changedJob['state']==='blocked'&&file_exists($changed)&&!sc_q($root,'SELECT 1 FROM session_copy_purge_receipts WHERE event_ref=?',[$o['event_ref']])->fetchColumn());

    $phase='SC28-client-reply';$u=fresh($root,$env,'crashreply');$o=sc_expire($root,$db,$u);due_clock($root,$o);
    $crash=cli([$repo.'/bin/cleanup_session_copies.php','--event='.$o['event_ref']],array_merge($env,['BUSHISA_SESSION_CLEANUP_CRASH'=>'after_complete_commit']));
    cc('SC28','lost final client reply is discoverable through idempotent status',$crash['exit']===77&&job($root,$o['event_ref'])['state']==='complete'&&finish_event($db,$o['event_ref'])==='complete'&&(int)sc_q($root,'SELECT COUNT(*) FROM session_copy_purge_receipts WHERE event_ref=?',[$o['event_ref']])->fetchColumn()===1);

    // A different current authorization version is never treated as the old
    // eligible Q11 attempt. Its object remains untouched for review.
    $u=fresh($root,$env,'changedversion');$o=sc_expire($root,$db,$u);due_clock($root,$o);$copy=copies($root,$u)[0];
    sc_q($root,'UPDATE users SET session_version=session_version+1 WHERE id=?',[$u['id']]);
    cc('SC18','changed authorization version blocks stale physical deletion',attempt($db,$o['event_ref'])==='skipped'&&job($root,$o['event_ref'])['state']==='blocked'&&file_exists(object_path($copy['copy_ref'])));

    $u=q11_user($root,'unknown-anchor',true);$u['key']=sc_q($root,'SELECT signup_attempt_key FROM users WHERE id=?',[$u['id']])->fetchColumn();$u['capsule']=login_session($u,$env);
    sc_q($root,'UPDATE signup_lifecycle SET unverified_expires_at=NULL WHERE account_id=?',[$u['id']]);
    $unknown=bushisa_signup_check_account($db,$u['id']);
    cc('SC18','unknown historical Q11 anchor creates no guessed session-copy deadline',$unknown['state']==='anchor_unknown'&&!sc_q($root,'SELECT 1 FROM session_copy_obligations WHERE account_id=?',[$u['id']])->fetchColumn()&&!absent_copies($root,$u));

    // CLI destination checks precede DB connection. Traversal input is rejected
    // before any filesystem operation and does not disclose the locator.
    $guard=cli([$repo.'/bin/cleanup_session_copies.php','--status=..\\outside'],['BUSHISA_SESSION_CLEANUP_ENABLED'=>'false','BUSHISA_SESSION_SYNTHETIC'=>'true']);
    cc('SC39','disabled consumer refuses before connection',$guard['exit']===1&&str_contains($guard['stderr'],'SESSION_CONSUMER_DISABLED'));
    try { $store->inspect('../../outside','0'.str_repeat('0',63));$traversal=false; } catch(Throwable) { $traversal=true; }
    cc('SC30','store rejects traversal without opening or deleting an outside locator',$traversal);
    $sentinel=$dir.'/outside-sentinel';file_put_contents($sentinel,'fixture-sentinel');$linkRef=bin2hex(random_bytes(32));$link=object_path($linkRef);$linked=@symlink($sentinel,$link);
    if($linked) { try { $store->inspect($linkRef,bin2hex(random_bytes(32)));$symlinkRefused=false; } catch(Throwable) { $symlinkRefused=true; }
        cc('SC30','symlink escape is refused without changing its target',$symlinkRefused&&file_get_contents($sentinel)==='fixture-sentinel'&&is_link($link));unlink($link);
    } else { $checks[]=['case'=>'SC30','check'=>'platform symlink/reparse creation variant unavailable','result'=>'NOT RUN']; }

    // Verify status output is intentionally content- and pseudonym-free.
    $status=cli([$repo.'/bin/cleanup_session_copies.php','--status='.$o['event_ref']],$env);
    cc('SC33','operator status excludes event/account/copy pseudonyms',$status['exit']===0&&!str_contains($status['stdout'],$o['event_ref'])&&!str_contains($status['stdout'],$u['key'])&&!preg_match('/[a-f0-9]{64}/',$status['stdout']));
} catch(Throwable $e) {
    $failure=['phase'=>$phase,'type'=>get_class($e),'message'=>$e instanceof PDOException?'SQL_'.$e->getCode().'_'.($e->errorInfo[1]??0):$e->getMessage(),'database_detail'=>$e instanceof PDOException&&($e->errorInfo[1]??0)===1054?substr(preg_replace('/[^A-Za-z0-9_` .-]/','',(string)($e->errorInfo[2]??'')),0,160):null,'line'=>$e->getLine(),'file'=>basename($e->getFile()),'last_worker_error'=>substr($GLOBALS['sc_last_child']['stderr']??'',0,1200)];
} finally {
    if(isset($root)&&$root->inTransaction())$root->rollBack();
    foreach($children as $c){if(is_resource($c[0])){proc_terminate($c[0]);ce($c);}}
    foreach($servers as $p){proc_terminate($p);proc_close($p);}
    $e=['suite'=>'Q11-only synthetic session_copies_v1 consumer','started_utc'=>$started,'finished_utc'=>gmdate('c'),'environment'=>$environment,'migrations'=>$migrations,'checks'=>$checks,'races'=>$races,
        'totals'=>['assertions'=>count($checks),'passed'=>count(array_filter($checks,fn($c)=>$c['result']==='PASS'))],'failure'=>$failure];
    file_put_contents($dir.'/evidence.json',json_encode($e,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    echo json_encode(['result'=>$failure?'FAIL':'PASS','evidence'=>$dir.'/evidence.json','totals'=>$e['totals'],'failure'=>$failure],JSON_UNESCAPED_SLASHES).PHP_EOL;
}exit($failure?1:0);
