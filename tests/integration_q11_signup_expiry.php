<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/q11_fixture.php';
require_once __DIR__.'/../php/http_test_barrier.php';
require_once __DIR__.'/../php/sanitize.php';
require_once __DIR__.'/../php/password_policy.php';
require_once __DIR__.'/../php/rate_limit.php';
require_once __DIR__.'/../php/db.php';
require_once __DIR__.'/../php/auth.php';
bushisa_q11_synthetic_guard();
$settings=bushisa_require_disposable_test_database('Q11 integration');
$repo=dirname(__DIR__); $dir=$repo.'/.tmp/q11-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
mkdir($dir,0700,true); mkdir($dir.'/sessions',0700);
putenv('MAIL_OUTBOX_KEY='.base64_encode(random_bytes(32))); putenv('SIGNUP_CODE_HMAC_KEY='.bin2hex(random_bytes(32)));
$checks=[]; $races=[]; $servers=[]; $failure=null; $environment=[]; $migration=[]; $started=gmdate('c');
function qs(string $id,string $check,bool $ok): void {
    global $checks; $checks[]=['case'=>$id,'check'=>$check,'result'=>$ok?'PASS':'FAIL'];
    if (!$ok) { throw new RuntimeException($id.': '.$check); }
}
function qs_child(array $args,array $env=[]): array {
    global $repo; $pipes=[]; $p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,array_merge(getenv(),$env));
    if (!is_resource($p)) { throw new RuntimeException('Child process unavailable'); } fclose($pipes[0]); return [$p,$pipes];
}
function qs_finish(array $c): array {
    [$p,$pipes]=$c; $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return ['exit'=>proc_close($p),'stdout'=>$out,'stderr'=>$err,'json'=>json_decode($out,true)];
}
function qs_cli(array $args,array $env=[]): array { return qs_finish(qs_child($args,$env)); }
function qs_row(PDO $p,string $table,int $id,string $key='account_id'): array {
    return bushisa_signup_query($p,'SELECT * FROM '.$table.' WHERE '.$key.'=?',[$id])->fetch(PDO::FETCH_ASSOC)?:[];
}
function qs_clock(PDO $p,string $at): void { q11_clock($p,bushisa_signup_time($at)); }
function qs_reset_clock(PDO $p): void { qs_clock($p,(string)$p->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn()); }
function qs_code(PDO $p,array $u): string { return bushisa_issue_signup_code($p,$u['id'],$u['email']); }
function qs_onboard(PDO $root,PDO $db,string $tag): array {
    qs_reset_clock($root); $u=q11_user($root,$tag); $c=qs_code($db,$u);
    if (!bushisa_verify_signup_code($db,$u['id'],$c)['success']) { throw new RuntimeException('Synthetic email approval failed'); } return $u;
}
function qs_session(array $u,int $version=0): array {
    global $dir; ini_set('session.save_path',$dir.'/sessions'); ini_set('session.use_cookies','0'); session_cache_limiter('');
    session_id(bin2hex(random_bytes(24))); session_start();
    $csrf=bin2hex(random_bytes(32)); $_SESSION=['user_id'=>$u['id'],'email'=>$u['email'],'session_version'=>$version,'role'=>'user','last_activity'=>time(),'csrf_token'=>$csrf];
    $id=session_id(); session_write_close(); return ['cookie'=>'PHPSESSID='.$id,'csrf_token'=>$csrf];
}
function qs_http(array $session,string $route,array $data,int $port=8147,array $headers=[]): CurlHandle {
    $c=curl_init('http://127.0.0.1:'.$port.'/'.$route);
    curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data+['csrf_token'=>$session['csrf_token']]),CURLOPT_HTTPHEADER=>array_merge(['Cookie: '.$session['cookie']],$headers),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]); return $c;
}
function qs_pump(?CurlMultiHandle $multi): void { if($multi) { curl_multi_exec($multi,$running); } }
function qs_until(callable $fn,?CurlMultiHandle $multi=null,int $ms=9000): void {
    $end=microtime(true)+$ms/1000; do { qs_pump($multi); if($fn())return; usleep(10000); }while(microtime(true)<$end); throw new RuntimeException('Synthetic observation timed out');
}
function qs_worker(array $job,array $env): array {
    global $dir,$repo; $id=bin2hex(random_bytes(6)); $path=$dir.'/job-'.$id.'.json'; $ready=$dir.'/ready-'.$id.'.json';
    file_put_contents($path,json_encode($job,JSON_THROW_ON_ERROR));
    return [qs_child([$repo.'/tests/support/q11_worker.php','--job='.$path,'--ready='.$ready],$env),$ready];
}
function qs_waits(PDO $p,int $blocker): array {
    return bushisa_signup_query($p,'SELECT r.PROCESSLIST_ID AS requesting_connection,b.PROCESSLIST_ID AS blocking_connection FROM performance_schema.data_lock_waits w
        JOIN performance_schema.threads r ON r.THREAD_ID=w.REQUESTING_THREAD_ID JOIN performance_schema.threads b ON b.THREAD_ID=w.BLOCKING_THREAD_ID WHERE b.PROCESSLIST_ID=?',[$blocker])->fetchAll(PDO::FETCH_ASSOC);
}
/** Both real workers reach the guarded barrier, then are observed concurrently
 * waiting for this account's row lock. Gate ordering exercises both outcomes. */
function qs_race(PDO $root,array $u,array $env,array $session,string $route,array $data,string $case,bool $httpFirst): array {
    global $races; $barrier='q11_'.bin2hex(random_bytes(16)); $key=$barrier.'_q11_mixed';
    $base=rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bushisa-http-barriers'.DIRECTORY_SEPARATOR.$key;
    $headers=['X-Bushisa-Test-Barrier: '.$barrier,'X-Bushisa-Test-Barrier-Point: q11_mixed','X-Bushisa-Test-Barrier-Expected: 2','X-Bushisa-Test-Barrier-Timeout-Ms: 14000','X-Bushisa-Test-Gate: true'];
    $root->beginTransaction(); bushisa_signup_query($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$u['id']])->fetchColumn();
    $blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
    [$worker,$ready]=qs_worker(['action'=>'expire','id'=>$u['id'],'barrier'=>$barrier],$env);
    $curl=qs_http($session,$route,$data,8147,$headers); $multi=curl_multi_init(); curl_multi_add_handle($multi,$curl);
    try {
        qs_until(fn()=>count(glob($base.'-*.json')?:[])===2,$multi);
        $records=array_map(fn($f)=>json_decode(file_get_contents($f),true,16,JSON_THROW_ON_ERROR),glob($base.'-*.json'));
        $http=array_values(array_filter($records,fn($r)=>$r['sapi']==='cli-server'))[0]; $cli=array_values(array_filter($records,fn($r)=>$r['sapi']==='cli'))[0];
        $first=$httpFirst?$http:$cli; $second=$httpFirst?$cli:$http;
        touch($base.'-'.$first['pid'].'.release');
        qs_until(fn()=>in_array($first['connection_id'],array_column(qs_waits($root,$blocker),'requesting_connection')),$multi);
        touch($base.'-'.$second['pid'].'.release');
        $waits=[]; qs_until(function()use($root,$blocker,&$waits,$records):bool { $waits=qs_waits($root,$blocker); return count(array_intersect(array_column($waits,'requesting_connection'),array_column($records,'connection_id')))===2; },$multi);
        qs($case,'distinct HTTP/CLI PIDs and SQL connections with simultaneous engine-observed row waits',count(array_unique(array_column($records,'pid')))===2 && count(array_unique(array_column($records,'connection_id')))===2);
        $root->commit();
        qs_until(function()use($multi):bool { curl_multi_exec($multi,$n);return $n===0; },$multi);
        $reply=['status'=>curl_getinfo($curl,CURLINFO_RESPONSE_CODE),'body'=>curl_multi_getcontent($curl)]; $result=qs_finish($worker); $worker=null;
        $races[]=['case'=>$case,'participants'=>$records,'observed_row_waits'=>$waits,'first'=>$httpFirst?'HTTP':'expiry','http_status'=>$reply['status'],'worker_exit'=>$result['exit'],'expiry_result'=>$result['json']['result']??null];
        qs($case,'HTTP and expiry workers complete',$result['exit']===0 && in_array($reply['status'],[200,302],true)); return $reply;
    } finally {
        if($root->inTransaction())$root->rollBack();
        if($worker) { proc_terminate($worker[0]);qs_finish($worker); }
        curl_multi_remove_handle($multi,$curl); curl_multi_close($multi);
    }
}
try {
    foreach(['APP_ENV'=>'production','DB_NAME'=>'bushisa_db','DB_HOST'=>'example.invalid','MAIL_TRANSPORT'=>'smtp','BUSHISA_TEST_DATABASE'=>'false'] as $key=>$value) {
        $old=getenv($key);putenv($key.'='.$value);$refused=false;try{bushisa_q11_synthetic_guard();}catch(RuntimeException){$refused=true;}putenv($key.'='.$old);
        qs('QS01','pre-connection guard rejects '.$key,$refused);
    }
    bushisa_bootstrap_disposable_database($repo.'/database/bushisa.sql');
    $m=qs_cli([$repo.'/bin/migrate.php']);$again=qs_cli([$repo.'/bin/migrate.php']);
    qs('QS02','fresh 011 and repeat',$m['exit']===0 && str_contains($m['stdout'],'Applying 011_') && $again['exit']===0 && str_contains($again['stdout'],'Skipping 011_'));$migration[]=['kind'=>'fresh-repeat','results'=>[$m['exit'],$again['exit']]];
    $root=q11_connect();q11_install($root);$runtime=q11_worker_user($root,$settings['name']);$db=q11_connect($runtime);
    $env=['DB_USER'=>$runtime['user'],'DB_PASSWORD'=>$runtime['password'],'SESSION_COOKIE_SECURE'=>'false'];
    $environment=['php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$root->getAttribute(PDO::ATTR_SERVER_VERSION),'mysqlnd'=>phpversion('mysqlnd'),'database'=>$settings['name'],'host'=>$settings['host'],'port'=>$settings['port'],
        'settings'=>$root->query('SELECT @@server_uuid AS server_uuid,@@time_zone AS time_zone,@@transaction_isolation AS isolation_level,@@sql_mode AS sql_mode')->fetch(PDO::FETCH_ASSOC),'runtime_grants'=>$root->query('SHOW GRANTS FOR '.$runtime['account'])->fetchAll(PDO::FETCH_COLUMN)];
    // Actual register path, not a synthetic anchor import.
    $student='n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).'q';
    $registered=register_user($db,['nust_student_id'=>$student,'email'=>$student.'@students.nust.ac.zw','password'=>'Synthet1c!Fixture','display_name'=>'Q11 Register','anon_handle'=>'q11_register_'.bin2hex(random_bytes(4)),'gender'=>'male','faculty'=>'Applied Sciences','year_of_study'=>'Part 2']);
    $u=find_user_by_email($root,$student.'@students.nust.ac.zw');$l=$u?qs_row($root,'signup_lifecycle',(int)$u['id']):[];
    qs('QS04','registration persists original creation+72h in the creation transaction',$registered['success'] && $l['registered_at']===bushisa_signup_time($u['created_at']) && $l['unverified_expires_at']===bushisa_signup_time($u['created_at'],'+72 hours'));
    $validKey=getenv('MAIL_OUTBOX_KEY');putenv('MAIL_OUTBOX_KEY=invalid');$badStudent='n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).'z';
    $bad=register_user($db,['nust_student_id'=>$badStudent,'email'=>$badStudent.'@students.nust.ac.zw','password'=>'Synthet1c!Fixture','display_name'=>'Q11 Rollback','anon_handle'=>'q11_rollback_'.bin2hex(random_bytes(4)),'gender'=>'male','faculty'=>'Applied Sciences','year_of_study'=>'Part 2']);putenv('MAIL_OUTBOX_KEY='.$validKey);
    qs('QS04','queue failure rolls back account and creation anchor',!$bad['success'] && find_user_by_email($root,$badStudent.'@students.nust.ac.zw')===null);
    foreach(['-1 microsecond','+0 seconds','+1 microsecond'] as $n=>$delta) {
        qs_reset_clock($root);$f=q11_user($root,'email'.$n);$l=qs_row($root,'signup_lifecycle',$f['id']);qs_clock($root,bushisa_signup_time($l['unverified_expires_at'],$delta));
        $r=bushisa_signup_check_account($db,$f['id']);$s=qs_row($root,'users',$f['id'],'id');
        qs('QS05','72h '.$delta,$n===0?$r['state']==='live':($r['state']==='expired' && $s['account_status']==='deleted' && (int)$s['is_active']===0));
    }
    foreach(['-1 microsecond','+0 seconds','+1 microsecond'] as $n=>$delta) {
        $f=qs_onboard($root,$db,'onboard'.$n);$l=qs_row($root,'signup_lifecycle',$f['id']);qs_clock($root,bushisa_signup_time($l['onboarding_expires_at'],$delta));
        $r=bushisa_complete_adult_onboarding($db,$f['id'],'2000-01-01');$s=qs_row($root,'users',$f['id'],'id');
        qs('QS06','7d '.$delta,$n===0?($r['success'] && $s['account_status']==='active'):(!$r['success'] && $s['account_status']==='deleted'));
    }
    qs_reset_clock($root);$f=q11_user($root,'resend');$first=qs_code($db,$f);$oldMail=(int)bushisa_signup_query($root,'SELECT MAX(id) FROM mail_outbox WHERE approval_code_id IN (SELECT id FROM student_email_approval_codes WHERE user_id=?)',[$f['id']])->fetchColumn();$l=qs_row($root,'signup_lifecycle',$f['id']);
    qs_clock($root,bushisa_signup_time(bushisa_signup_now($root),'+3 minutes'));$second=qs_code($db,$f);$cancel=qs_row($root,'signup_mail_jobs',$oldMail,'mail_id');
    qs('QS07','resend preserves creation deadline and cancels old generation',$l===qs_row($root,'signup_lifecycle',$f['id']) && $cancel['state']==='cancelled' && qs_row($root,'mail_outbox',$oldMail,'id')['body']===null && !bushisa_verify_signup_code($db,$f['id'],$first)['success']);
    qs('QS08','first email approval succeeds',bushisa_verify_signup_code($db,$f['id'],$second)['success']);$approved=qs_row($root,'signup_lifecycle',$f['id']);
    qs_clock($root,bushisa_signup_time(bushisa_signup_now($root),'+2 days'));bushisa_signup_deadline($db,$f['id']);bushisa_signup_check_account($db,$f['id']);bushisa_verify_signup_code($db,$f['id'],$second);
    $blocked=false;try{qs_code($db,$f);}catch(RuntimeException){$blocked=true;}
    qs('QS08','reads, repeat verification and resend cannot renew first approval',$blocked && $approved===qs_row($root,'signup_lifecycle',$f['id']) && $approved['onboarding_expires_at']===bushisa_signup_time($approved['first_email_approved_at'],'+7 days'));
    qs_reset_clock($root);$legacy=q11_user($root,'legacy',false);$legacy2=q11_user($root,'legacy2',false);bushisa_signup_query($root,'UPDATE users SET account_status="pending_onboarding",is_verified=1 WHERE id=?',[$legacy2['id']]);
    foreach([$legacy,$legacy2] as $f) {
        $before=qs_row($root,'users',$f['id'],'id');$r=bushisa_signup_check_account($db,$f['id']);$l=qs_row($root,'signup_lifecycle',$f['id']);
        qs('QS09','unknown legacy anchors observed without deletion/backfill',$r['state']==='anchor_unknown' && $before===qs_row($root,'users',$f['id'],'id') && $l['registered_at']===null && $l['first_email_approved_at']===null);
    }
    qs('QS09','unknown legacy continuation cannot manufacture approval',!bushisa_verify_signup_code($db,$legacy['id'],'123456')['success'] && !bushisa_complete_adult_onboarding($db,$legacy2['id'],'2000-01-01')['success']);
    $broken=q11_user($root,'contradictory');bushisa_signup_query($root,'UPDATE signup_lifecycle SET unverified_expires_at=DATE_ADD(registered_at,INTERVAL 1 HOUR) WHERE account_id=?',[$broken['id']]);
    qs('QS09','contradictory expiry is neither a deletion anchor nor a displayed deadline',bushisa_signup_check_account($db,$broken['id'])['state']==='anchor_unknown' && qs_row($root,'users',$broken['id'],'id')['account_status']==='pending_email' && bushisa_signup_deadline($db,$broken['id'])===null);
    // Old untracked rows cannot acquire invented delivery timestamps from a
    // cleanup read. This synthetic orphan is deliberately not dispatchable.
    bushisa_signup_query($root,'INSERT INTO mail_outbox (recipient_email,subject,body,provider,status,expires_at,code_bearing) VALUES ("synthetic@example.invalid","Synthetic legacy","Synthetic discarded fixture","fake","sent",?,1)',[bushisa_signup_time(bushisa_signup_now($root),'-1 minute')]);
    $orphan=(int)$root->lastInsertId();bushisa_signup_cleanup_mail($db);$orphanRow=qs_row($root,'mail_outbox',$orphan,'id');
    qs('QS09','legacy cleanup erases without inventing acceptance time',$orphanRow['body']===null && $orphanRow['sent_at']===null && $orphanRow['status']==='sent');
    qs_reset_clock($root);$f=q11_user($root,'restricted');$l=qs_row($root,'signup_lifecycle',$f['id']);bushisa_signup_query($root,'UPDATE users SET is_suspended=1 WHERE id=?',[$f['id']]);qs_clock($root,$l['unverified_expires_at']);
    $before=qs_row($root,'users',$f['id'],'id');bushisa_signup_check_account($db,$f['id']);
    qs('QS10','suspension survives cleanup and cannot be activated',$before===qs_row($root,'users',$f['id'],'id') && !bushisa_complete_adult_onboarding($db,$f['id'],'2000-01-01')['success']);
    qs_reset_clock($root);$f=q11_user($root,'atomic');qs_code($db,$f);$l=qs_row($root,'signup_lifecycle',$f['id']);$before=qs_row($root,'users',$f['id'],'id');$codeBefore=bushisa_signup_query($root,'SELECT * FROM student_email_approval_codes WHERE user_id=?',[$f['id']])->fetchAll();
    // Pre-existing protected material is outside this account-attempt slice.
    bushisa_signup_query($root,'INSERT INTO moderation_cases (reporter_id,target_type,target_id,reported_user_id,reason) VALUES (?,"user",?,?,"Synthetic protected allegation")',[$f['id'],$f['id'],$f['id']]);$case=(int)$root->lastInsertId();
    bushisa_signup_query($root,'INSERT INTO moderation_evidence (case_id,source_type,source_id,subject_user_id,evidence_excerpt) VALUES (?,"user",?,?,"Synthetic held evidence")',[$case,$f['id'],$f['id']]);
    $hold=hash('sha256','q11-hold');$item=hash('sha256','q11-item');
    bushisa_signup_query($root,'INSERT INTO evidence_lifecycle_holds (hold_id,case_id,item_key,source_revision,kind,state,requested_hours,reason_code,created_by,expires_at,created_at) VALUES (?,?,?,1,"urgent","active",24,"CASE_CONTEXT",?,DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 1 DAY),UTC_TIMESTAMP(6))',[$hold,$case,$item,$f['id']]);
    bushisa_signup_query($root,'INSERT INTO protective_replay_events (event_type,subject_type,subject_id,payload_json) VALUES ("synthetic_restriction","user",?,JSON_OBJECT("synthetic",true))',[$f['id']]);
    bushisa_signup_query($root,'INSERT INTO evidence_recovery_outbox (event_id,item_key,source_revision,action_name,effective_at) VALUES (?,?,1,"synthetic_hold",UTC_TIMESTAMP(6))',[hash('sha256','q11-recovery'),$item]);
    $protected=[];foreach(['moderation_cases','moderation_evidence','evidence_lifecycle_holds','protective_replay_events','evidence_recovery_outbox'] as $t){$protected[$t]=$root->query('SELECT * FROM '.$t)->fetchAll();}
    qs_clock($root,$l['unverified_expires_at']);
    foreach(['after_cancellation','before_commit'] as $point) {
        putenv('BUSHISA_Q11_FAULT='.$point);$failed=false;try{bushisa_signup_check_account($db,$f['id']);}catch(RuntimeException){$failed=true;}putenv('BUSHISA_Q11_FAULT');
        qs('QS11','atomic rollback at '.$point,$failed && $before===qs_row($root,'users',$f['id'],'id') && $codeBefore===bushisa_signup_query($root,'SELECT * FROM student_email_approval_codes WHERE user_id=?',[$f['id']])->fetchAll() && qs_row($root,'signup_expiry_obligations',$f['id'])===[]);
    }
    $r=bushisa_signup_check_account($db,$f['id']);$receipt=qs_row($root,'signup_expiry_obligations',$f['id']);$version=qs_row($root,'users',$f['id'],'id')['session_version'];
    qs_clock($root,bushisa_signup_time($l['unverified_expires_at'],'+2 days'));bushisa_signup_check_account($db,$f['id']);bushisa_cleanup_expired_signup_attempts($db);bushisa_cleanup_expired_signup_attempts($db);
    qs('QS11','retries preserve receipt, version and original overdue obligation',$r['state']==='expired' && $receipt===qs_row($root,'signup_expiry_obligations',$f['id']) && $version===qs_row($root,'users',$f['id'],'id')['session_version'] && $receipt['live_purge_due_at']===bushisa_signup_time($l['unverified_expires_at'],'+24 hours'));
    qs('QS12','obligations stay pending/unpublished with no invented backup bound',$receipt['cleanup_state']==='pending_consumer' && $receipt['publication_state']==='unpublished' && $receipt['retain_until']===null && count(bushisa_signup_query($root,'SELECT * FROM signup_lifecycle_alerts WHERE account_id=? AND reason_code="SIGNUP_LIVE_CLEANUP_OVERDUE"',[$f['id']])->fetchAll())===1);
    foreach($protected as $table=>$rows){qs('QS12','preserves '.$table,$rows===$root->query('SELECT * FROM '.$table)->fetchAll());}
    $maintenance=qs_cli([$repo.'/bin/cleanup_policy.php']);$maintenanceJson=json_decode($maintenance['stdout'],true);
    qs('QS11','actual cleanup CLI keeps pending physical-erasure obligations honest',$maintenance['exit']===0 && isset($maintenanceJson['signup_expiry']) && qs_row($root,'signup_expiry_obligations',$f['id'])['cleanup_state']==='pending_consumer');
    foreach($protected as $table=>$rows){qs('QS12','cleanup CLI preserves '.$table,$rows===$root->query('SELECT * FROM '.$table)->fetchAll());}
    // Useful mail deadline, exact boundary, bounded retry and ambiguous outcome.
    qs_reset_clock($root);$f=q11_user($root,'mail');$code=qs_code($db,$f);$mail=(int)$root->query('SELECT MAX(id) FROM mail_outbox')->fetchColumn();$job=qs_row($root,'signup_mail_jobs',$mail,'mail_id');
    $c=bushisa_signup_query($root,'SELECT * FROM student_email_approval_codes WHERE user_id=? ORDER BY id DESC LIMIT 1',[$f['id']])->fetch(PDO::FETCH_ASSOC);
    qs('QS13','immutable useful deadline is min(queue+5m, challenge-60s, account deadline)',$job['useful_until']===min(bushisa_signup_time($job['queued_at'],'+5 minutes'),bushisa_signup_time($c['expires_at'],'-60 seconds'),qs_row($root,'signup_lifecycle',$f['id'])['unverified_expires_at']));
    // Remove earlier ready jobs without transport; these are only test fixtures.
    bushisa_signup_query($root,'UPDATE mail_outbox SET expires_at=? WHERE id<? AND status="pending"',[bushisa_signup_now($root),$mail]);bushisa_signup_cleanup_mail($db);
    $out=bushisa_dispatch_mail($db,30,fn()=>throw new BushisaMailTransportException('synthetic_transient',true));
    qs('QS14','known non-acceptance retries within original deadline',$out['retried']===1 && qs_row($root,'signup_mail_jobs',$mail,'mail_id')['useful_until']===$job['useful_until']);
    qs_clock($root,$job['useful_until']);$count=0;bushisa_dispatch_mail($db,30,function()use(&$count){$count++;});
    qs('QS13','exact useful deadline discards without sending',$count===0 && qs_row($root,'mail_outbox',$mail,'id')['body']===null);
    qs_reset_clock($root);$f=q11_user($root,'unknown');qs_code($db,$f);$mail=(int)$root->query('SELECT MAX(id) FROM mail_outbox')->fetchColumn();
    bushisa_dispatch_mail($db,30,fn()=>throw new BushisaMailTransportException('synthetic_unknown',true,true));$n=0;bushisa_dispatch_mail($db,30,function()use(&$n){$n++;});
    qs('QS14','ambiguous transport is terminal uncertain, never automatic resend',$n===0 && qs_row($root,'signup_mail_jobs',$mail,'mail_id')['state']==='uncertain' && qs_row($root,'mail_outbox',$mail,'id')['body']===null);
    // Claim lease expiry permits a new owner only BEFORE transport started.
    qs_reset_clock($root);$f=q11_user($root,'lease');qs_code($db,$f);$mail=(int)$root->query('SELECT MAX(id) FROM mail_outbox')->fetchColumn();$a=bin2hex(random_bytes(32));$b=bin2hex(random_bytes(32));
    bushisa_signup_claim_mail($db,$mail,$a);qs_clock($root,bushisa_signup_time(bushisa_signup_now($root),'+60 seconds'));bushisa_signup_claim_mail($db,$mail,$b);
    qs('QS15','expired claim cannot deliver after replacement',bushisa_signup_begin_delivery($db,$mail,$a)===null && bushisa_signup_begin_delivery($db,$mail,$b)!==null);
    qs_clock($root,bushisa_signup_time(bushisa_signup_now($root),'+60 seconds'));bushisa_signup_cleanup_mail($db);
    qs('QS15','abandoned inflight becomes uncertain without resend',qs_row($root,'signup_mail_jobs',$mail,'mail_id')['state']==='uncertain');
    foreach(['-1 microsecond','+1 microsecond'] as $delta) {
        qs_reset_clock($root);$f=q11_user($root,'useful'.bin2hex(random_bytes(3)));qs_code($db,$f);$mail=(int)$root->query('SELECT MAX(id) FROM mail_outbox')->fetchColumn();$j=qs_row($root,'signup_mail_jobs',$mail,'mail_id');
        qs_clock($root,bushisa_signup_time($j['useful_until'],$delta));$calls=0;bushisa_dispatch_mail($db,30,function()use(&$calls){$calls++;});
        qs('QS13','useful delivery boundary '.$delta,$calls===($delta==='-1 microsecond'?1:0));
    }
    foreach(['-1 microsecond','+0 seconds','+1 microsecond'] as $delta) {
        qs_reset_clock($root);$f=q11_user($root,'challenge'.bin2hex(random_bytes(3)));$code=qs_code($db,$f);
        $due=bushisa_signup_query($root,'SELECT MAX(expires_at) FROM student_email_approval_codes WHERE user_id=?',[$f['id']])->fetchColumn();qs_clock($root,bushisa_signup_time($due,$delta));
        $verified=bushisa_verify_signup_code($db,$f['id'],$code);qs('QS24','challenge boundary '.$delta,$verified['success']===($delta==='-1 microsecond'));
        if(!$verified['success']){bushisa_cleanup_expired_signup_codes($db);qs('QS24','repeat code cleanup is idempotent',bushisa_cleanup_expired_signup_codes($db)===0);}
    }

    foreach([8147,8148] as $port) {
        $pipes=[];$server=proc_open([PHP_BINARY,'-d','session.save_path='.$dir.'/sessions','-S','127.0.0.1:'.$port,'-t',$repo],[0=>['pipe','r'],1=>['file',$dir.'/http-'.$port.'.log','a'],2=>['file',$dir.'/http-'.$port.'.log','a']],$pipes,$repo,array_merge(getenv(),$env));
        fclose($pipes[0]);$servers[]=$server;qs_until(function()use($port){$s=@fsockopen('127.0.0.1',$port,$e,$m,.05);if($s){fclose($s);return true;}return false;});
    }
    foreach([true,false] as $win) {
        qs_reset_clock($root);$f=q11_user($root,'raceverify'.(int)$win);$l=qs_row($root,'signup_lifecycle',$f['id']);qs_clock($root,bushisa_signup_time($l['unverified_expires_at'],'-2 minutes'));$code=qs_code($db,$f);qs_clock($root,bushisa_signup_time($l['unverified_expires_at'],$win?'-1 microsecond':'+0 seconds'));
        qs_race($root,$f,$env,qs_session($f),'verify_email.php',['action'=>'verify','email'=>$f['email'],'approval_code'=>$code],'QS16',$win);
        qs('QS16',$win?'verification before expiry wins':'exact expiry prevents verification',qs_row($root,'users',$f['id'],'id')['account_status']===($win?'pending_onboarding':'deleted'));
    }
    foreach([true,false] as $win) {
        $f=qs_onboard($root,$db,'raceonboard'.(int)$win);$l=qs_row($root,'signup_lifecycle',$f['id']);qs_clock($root,bushisa_signup_time($l['onboarding_expires_at'],$win?'-1 microsecond':'+0 seconds'));
        qs_race($root,$f,$env,qs_session($f,1),'onboarding.php',['date_of_birth'=>'2000-01-01'],'QS17',$win);
        qs('QS17',$win?'onboarding before expiry wins':'expiry prevents stale onboarding activation',qs_row($root,'users',$f['id'],'id')['account_status']===($win?'active':'deleted'));
    }
    qs_reset_clock($root);$f=q11_user($root,'raceresend');$l=qs_row($root,'signup_lifecycle',$f['id']);$code=qs_code($db,$f);qs_clock($root,$l['unverified_expires_at']);
    qs_race($root,$f,$env,qs_session($f),'verify_email.php',['action'=>'resend','email'=>$f['email']],'QS18',false);
    qs('QS18','expiry versus resend leaves no live challenge or queued body',(int)bushisa_signup_query($root,'SELECT COUNT(*) FROM student_email_approval_codes WHERE user_id=? AND code_hash IS NOT NULL',[$f['id']])->fetchColumn()===0 && (int)bushisa_signup_query($root,'SELECT COUNT(*) FROM mail_outbox WHERE approval_code_id IN (SELECT id FROM student_email_approval_codes WHERE user_id=?) AND body IS NOT NULL',[$f['id']])->fetchColumn()===0);
    // Actual HTTP resend while a separate dispatcher is in its fake transport.
    foreach(['resend','expiry'] as $mode) {
        qs_reset_clock($root);$f=q11_user($root,'inflight'.$mode);$l=qs_row($root,'signup_lifecycle',$f['id']);qs_clock($root,bushisa_signup_time($l['unverified_expires_at'],'-2 minutes'));$old=qs_code($db,$f);$mail=(int)$root->query('SELECT MAX(id) FROM mail_outbox')->fetchColumn();
        bushisa_signup_query($root,'UPDATE mail_outbox SET expires_at=? WHERE id<? AND status="pending"',[bushisa_signup_now($root),$mail]);bushisa_signup_cleanup_mail($db);
        $gate=$dir.'/inflight-'.bin2hex(random_bytes(6)).'.json';[$worker,$ready]=qs_worker(['action'=>'dispatch','inflight'=>$gate],$env);
        qs_until(fn()=>is_file($gate));$sender=json_decode(file_get_contents($gate),true,16,JSON_THROW_ON_ERROR);
        if($mode==='expiry')qs_clock($root,$l['unverified_expires_at']);
        $session=qs_session($f);$root->beginTransaction();bushisa_signup_query($root,'SELECT id FROM users WHERE id=? FOR UPDATE',[$f['id']])->fetchColumn();$blocker=(int)$root->query('SELECT CONNECTION_ID()')->fetchColumn();
        $curl=qs_http($session,'verify_email.php',['action'=>'resend','email'=>$f['email']],8148);$multi=curl_multi_init();curl_multi_add_handle($multi,$curl);$waits=[];
        qs_until(function()use($root,$blocker,&$waits){$waits=qs_waits($root,$blocker);return count($waits)>0;},$multi);
        $httpConn=(int)$waits[0]['requesting_connection'];$root->commit();qs_until(function()use($multi){curl_multi_exec($multi,$n);return $n===0;},$multi);
        $cancelled=qs_row($root,'signup_mail_jobs',$mail,'mail_id');$erased=qs_row($root,'mail_outbox',$mail,'id')['body']===null;
        touch($gate.'.release');$answer=qs_finish($worker);curl_multi_remove_handle($multi,$curl);curl_multi_close($multi);
        $job=qs_row($root,'signup_mail_jobs',$mail,'mail_id');
        qs('QS19','overlapping HTTP '.$mode.' cancels inflight without pretending recall',!$sender['transaction_open'] && $sender['connection_id']!==$httpConn && $cancelled['state']==='inflight' && $cancelled['cancel_requested_at']!==null && $erased && $answer['exit']===0 && $job['state']==='accepted_after_cancel' && !bushisa_verify_signup_code($db,$f['id'],$old)['success']);
        $races[]=['case'=>'QS19','variant'=>$mode,'carrier'=>$sender,'http_server_pid'=>proc_get_status($servers[1])['pid'],'http_connection'=>$httpConn,'observed_row_waits'=>$waits,'state'=>$job['state']];
    }
    // Distinct workers race a held claim against HTTP cancellation before I/O.
    qs_reset_clock($root);$f=q11_user($root,'claimed');$old=qs_code($db,$f);$mail=(int)$root->query('SELECT MAX(id) FROM mail_outbox')->fetchColumn();
    $token=bin2hex(random_bytes(32));bushisa_signup_claim_mail($db,$mail,$token);
    $curl=qs_http(qs_session($f),'verify_email.php',['action'=>'resend','email'=>$f['email']],8148);$reply=curl_exec($curl);
    qs('QS20','HTTP supersession cancels an existing claim before delivery',curl_getinfo($curl,CURLINFO_RESPONSE_CODE)===200 && qs_row($root,'signup_mail_jobs',$mail,'mail_id')['state']==='cancelled' && bushisa_signup_begin_delivery($db,$mail,$token)===null);
    // Current state/version is rechecked, and protected pages deny expired sessions.
    qs_reset_clock($root);$f=qs_onboard($root,$db,'session');$l=qs_row($root,'signup_lifecycle',$f['id']);$session=qs_session($f,1);qs_clock($root,$l['onboarding_expires_at']);
    $curl=qs_http($session,'onboarding.php',['date_of_birth'=>'2000-01-01']);curl_exec($curl);
    qs('QS21','actual protected route revokes expired session',curl_getinfo($curl,CURLINFO_RESPONSE_CODE)===302 && qs_row($root,'users',$f['id'],'id')['account_status']==='deleted');
    qs_reset_clock($root);$f=qs_onboard($root,$db,'stale');bushisa_signup_query($root,'UPDATE users SET session_version=session_version+1 WHERE id=?',[$f['id']]);
    qs('QS21','stale session version cannot activate',!bushisa_complete_adult_onboarding($db,$f['id'],'2000-01-01',1)['success'] && qs_row($root,'users',$f['id'],'id')['account_status']==='pending_onboarding');
    // Migration from an existing 010 schema never certifies legacy rows.
    $base=$settings['name'];putenv('DB_NAME='.$base.'_upgrade');bushisa_bootstrap_disposable_database($repo.'/database/bushisa.sql');$pre=qs_cli([$repo.'/bin/migrate.php','--through=010']);$up=q11_connect();q11_install($up);$legacy=q11_user($up,'upgrade',false);$snap=qs_row($up,'users',$legacy['id'],'id');
    $m=qs_cli([$repo.'/bin/migrate.php']);$again=qs_cli([$repo.'/bin/migrate.php']);
    $upgradedUser=qs_row($up,'users',$legacy['id'],'id');
    qs('QS03','010 upgrade/repeat preserves original fields and has no anchor/generation backfill',$pre['exit']===0 && $m['exit']===0 && $again['exit']===0 && $snap===array_intersect_key($upgradedUser,$snap) && ($upgradedUser['signup_attempt_key']??null)===null && qs_row($up,'signup_lifecycle',$legacy['id'])===[]);
    $migration[]=['kind'=>'010-upgrade-repeat','results'=>[$pre['exit'],$m['exit'],$again['exit']]];putenv('DB_NAME='.$base);
    // Synthetic execution has no permissions to change clocks or schema.
    $denied=0;foreach(['UPDATE q11_fixture_clock SET clock_at=clock_at','CREATE TABLE q11_forbidden (id INT)'] as $sql){try{$db->exec($sql);}catch(PDOException){$denied++;}}
    qs('QS22','runtime cannot fabricate clock or perform DDL',$denied===2);
    $artifact=json_encode(['checks'=>$checks,'races'=>$races,'environment'=>$environment],JSON_THROW_ON_ERROR);
    qs('QS23','diagnostics contain no payload/code/token fields',!preg_match('/"(?:approval_code|code_hash|body|payload|password|claim_hash)"\s*:/',$artifact));
} catch(Throwable $e) { $failure=['type'=>get_class($e),'message'=>$e instanceof PDOException?'SQL_FAILURE_'.$e->getCode().'_'.($e->errorInfo[1]??0):$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine(),
    'trace'=>array_map(fn($t)=>['file'=>basename($t['file']??''),'line'=>$t['line']??0,'function'=>$t['function']],$e->getTrace())]; }
finally {
    foreach($servers as $s) { proc_terminate($s);proc_close($s); }
    $totals=['assertions'=>count($checks),'passed'=>count(array_filter($checks,fn($c)=>$c['result']==='PASS'))];
    $evidence=['suite'=>'Q11 synthetic signup expiry','started_utc'=>$started,'finished_utc'=>gmdate('c'),'environment'=>$environment,'migration'=>$migration,'checks'=>$checks,'races'=>$races,'totals'=>$totals,'failure'=>$failure];
    file_put_contents($dir.'/evidence.json',json_encode($evidence,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    fwrite(STDOUT,json_encode(['result'=>$failure?'FAIL':'PASS','evidence'=>$dir.'/evidence.json','totals'=>$totals,'failure'=>$failure],JSON_UNESCAPED_SLASHES).PHP_EOL);
}
exit($failure?1:0);
