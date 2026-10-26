<?php
declare(strict_types=1);

// No HTTP entry point, production target, owner SQL or user export is accepted.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/owner_schema_fixture.php';
require_once __DIR__.'/support/release_roles.php';
$s=bushisa_require_disposable_test_database('Release routes and local recovery');
if (!str_starts_with($s['name'],'bushisa_test_release_observed_roles_')
    || !in_array($s['host'],['127.0.0.1','localhost','::1'],true)
    || getenv('BUSHISA_RELEASE_ROLE_TEST')!=='true'
    || !preg_match('/\A[0-9a-f-]{36}\z/D',(string)getenv('BUSHISA_ROLE_SERVER_UUID'))) {
    throw new RuntimeException('RELEASE_REHEARSAL_TARGET_REFUSED');
}
$repo=dirname(__DIR__);$run=$repo.'/.tmp/release-rehearsal-'.bin2hex(random_bytes(6));
$checks=[];$servers=[];$roles=[];$GLOBALS['rr_accounts']=[];$failure=null;$authorized=false;$db=null;$original=null;
$restore=$s['name'].'_restore';$timings=[];
function rc(string $id,bool $ok,array $details=[]): void {
    global $checks;$checks[]=['case'=>$id,'result'=>$ok?'PASS':'FAIL']+$details;
    if(!$ok)throw new RuntimeException($id);
}
function child(array $args,?array $env=null,?string $input=null): array {
    global $repo;$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,$env);
    if(!is_resource($p))throw new RuntimeException('CHILD_UNAVAILABLE');
    if($input!==null)fwrite($pipes[0],$input);fclose($pipes[0]);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    return ['exit'=>proc_close($p),'out'=>$out,'err'=>$err];
}
function q(PDO $db,string $sql,array $args=[]): PDOStatement { $x=$db->prepare($sql);$x->execute($args);return $x; }
function http(string $path,array &$a=[],?array $data=null,bool $form=false): array {
    $headers=[];$h=['Accept: application/json'];if(isset($a['cookie']))$h[]='Cookie: '.$a['cookie'];
    if($data!==null){if(isset($a['csrf']))$data['csrf_token']=$a['csrf'];$h[]='Content-Type: '.($form?'application/x-www-form-urlencoded':'application/json');}
    $c=curl_init('http://127.0.0.1:8197/'.$path);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>$h,
        CURLOPT_HEADERFUNCTION=>static function($c,$line)use(&$headers){$p=explode(':',$line,2);if(count($p)===2)$headers[strtolower(trim($p[0]))]=trim($p[1]);return strlen($line);}]);
    if($data!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$form?http_build_query($data):json_encode($data,JSON_THROW_ON_ERROR)]);
    $body=curl_exec($c);if($body===false)throw new RuntimeException('HTTP_TRANSPORT');
    if(preg_match('/PHPSESSID=([^;]+)/',$headers['set-cookie']??'',$m))$a['cookie']='PHPSESSID='.$m[1];
    if(preg_match('/name="csrf_token" value="([a-f0-9]+)"/',$body,$m))$a['csrf']=$m[1];
    return ['code'=>curl_getinfo($c,CURLINFO_RESPONSE_CODE),'body'=>$body,'json'=>json_decode($body,true),'headers'=>$headers];
}
function request_ok(string $id,string $path,array &$auth,?array $data=null,int $status=200): array {
    $r=http($path,$auth,$data);rc($id,$r['code']===$status,['http'=>$r['code']]);return $r['json']['data']??[];
}
function server(array $env): void {
    global $repo,$run,$servers;
    $p=proc_open([PHP_BINARY,'-d','session.save_path='.$run.'/sessions','-d','display_errors=0','-S','127.0.0.1:8197','-t',$repo],
        [0=>['pipe','r'],1=>['file',$run.'/http.log','a'],2=>['file',$run.'/http.log','a']],$pipes,$repo,$env);
    if(!is_resource($p))throw new RuntimeException('HTTP_SERVER_UNAVAILABLE');fclose($pipes[0]);$servers[]=$p;
    $until=microtime(true)+10;do{$h=@fsockopen('127.0.0.1',8197,$e,$m,.05);if($h){fclose($h);return;}usleep(20000);}while(microtime(true)<$until);
    throw new RuntimeException('HTTP_SERVER_NOT_READY');
}
function stop_server(): void { global $servers;foreach($servers as $p)if(is_resource($p)){proc_terminate($p);proc_close($p);}$servers=[]; }
function user(PDO $db,string $tag,string $gender='male',bool $legacy=false): array {
    $student='n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).chr(random_int(97,122));$email=$student.'@students.nust.ac.zw';
    q($db,'INSERT INTO users(nust_student_id,email,password_hash,is_verified,is_active,account_status,session_version) VALUES(?,?,?,1,1,"active",0)',[$student,$email,password_hash('Synthetic2!Routes',PASSWORD_DEFAULT)]);$id=(int)$db->lastInsertId();
    q($db,'INSERT INTO profiles(user_id,display_name,anon_handle,gender,date_of_birth) VALUES(?,"Synthetic",?,?,"2000-01-01")',[$id,$tag.'_'.bin2hex(random_bytes(3)),$gender]);
    q($db,'INSERT INTO preferences(user_id,preferred_gender,age_range_min,age_range_max) VALUES(?,?,18,99)',[$id,$gender==='male'?'female':'male']);
    return compact('id','email');
}
function login(array $u,string $password='Synthetic2!Routes'): array {
    static $attempt=0;
    $a=[];http('login.php',$a);$r=http('login.php',$a,['email'=>$u['email'],'password'=>$password],true);
    rc('RT.login.'.(++$attempt),$r['code']===302,['http'=>$r['code']]);return $a;
}
function pair_action(string $case,string $route,int $match,array &$a,array $data): array {
    $p=request_ok($case.'.read','api/pairs.php?match_id='.$match,$a);
    return request_ok($case,$route,$a,$data+['match_id'=>$match,'pair_version'=>$p['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))]);
}
function denied(PDO $p,string $id,string $sql): void {
    $code=null;try{$p->exec($sql);}catch(PDOException $e){$code=(int)($e->errorInfo[1]??0);}
    rc($id,in_array($code,[1044,1142,1143,1227,1370,1644],true),['mysql_error'=>$code]);
}
function fixture_snapshot(PDO $p): array {
    $out=[];
    foreach($p->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table){
        $rows=$p->query('SELECT * FROM '.obs_id($table).' LIMIT 2001')->fetchAll(PDO::FETCH_ASSOC);
        if(count($rows)>2000)throw new RuntimeException('SYNTHETIC_SNAPSHOT_BOUND');
        $canonical=array_map(static fn($r)=>json_encode($r,JSON_THROW_ON_ERROR),$rows);sort($canonical,SORT_STRING);
        $out[$table]=['rows'=>count($rows),'digest'=>hash('sha256',implode("\n",$canonical))];
    }
    ksort($out);return $out; // Synthetic data only; no row/body is persisted.
}
try {
    $db=obs_connect($s,null,false);$db->exec('SET SESSION lock_wait_timeout=5');
    rc('RR.identity',hash_equals((string)getenv('BUSHISA_ROLE_SERVER_UUID'),(string)$db->query('SELECT @@server_uuid')->fetchColumn()));
    $lock='release-rehearsal:'.substr(hash('sha256',$s['name']),0,32);
    rc('RR.exclusive_fixture',(int)q($db,'SELECT GET_LOCK(?,0)',[$lock])->fetchColumn()===1);$authorized=true;
    $db->exec('DROP DATABASE IF EXISTS '.obs_id($restore));
    $original=$db->query('SELECT @@GLOBAL.sql_mode AS sql_mode,@@GLOBAL.time_zone AS timezone,@@GLOBAL.log_bin_trust_function_creators AS trust')->fetch(PDO::FETCH_ASSOC);
    $mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO';
    $db->exec('SET GLOBAL sql_mode='.$db->quote($mode));$db->exec("SET GLOBAL time_zone='+00:00'");$db->exec('SET GLOBAL log_bin_trust_function_creators=1');$db->exec('SET SESSION sql_mode='.$db->quote($mode));
    $profile=$db->query('SELECT VERSION() AS mysql,@@session.time_zone AS timezone,@@session.transaction_isolation AS isolation,@@session.sql_mode AS sql_mode,@@collation_server AS server_collation,@@require_secure_transport AS require_secure_transport,@@log_bin AS log_bin')->fetch(PDO::FETCH_ASSOC);
    obs_construct($db,$s,obs_fixture());
    $roles['migration']=rr_role($db,$s,'migration');$me=array_replace(getenv(),['DB_USER'=>$roles['migration']['user'],'DB_PASSWORD'=>$roles['migration']['password']]);
    $t=microtime(true);$m=child([PHP_BINARY,'bin/migrate.php','--through=016'],$me);$timings['upgrade_seconds']=microtime(true)-$t;
    rc('RR.upgrade',$m['exit']===0);rc('RR.rerun',child([PHP_BINARY,'bin/migrate.php'],$me)['exit']===0);
    foreach(['runtime','worker'] as $kind)$roles[$kind]=rr_role($db,$s,$kind);
    $rp=obs_connect($s,$roles['runtime']);
    // Preserve a genuinely pre-cutover cohort, then activate ONLY the synthetic
    // fixture epoch. This is not a production activation/backfill mechanism.
    $legacyA=user($db,'legacy_a');$legacyB=user($db,'legacy_b','female');
    q($db,'INSERT INTO matches(user_a_id,user_b_id) VALUES(?,?)',[$legacyA['id'],$legacyB['id']]);$legacyMatch=(int)$db->lastInsertId();
    $db->exec('UPDATE pair_ledger_epoch SET activated_at=UTC_TIMESTAMP(6),writer_reference="synthetic-route-fixture" WHERE singleton=1');
    $u=user($db,'a');$v=user($db,'b','female');$other=user($db,'other');$deleted=user($db,'deleted');
    mkdir($run,0700,true);mkdir($run.'/sessions',0700);
    $env=array_replace(getenv(),['DB_USER'=>$roles['runtime']['user'],'DB_PASSWORD'=>$roles['runtime']['password'],'APP_ENV'=>'development','APP_DEBUG'=>'false','BUSHISA_ALLOW_RUNTIME_DDL'=>'false','MAIL_PROVIDER'=>'smtp','MAIL_TRANSPORT'=>'fake','SMTP_HOST'=>'smtp.example.invalid','SMTP_USERNAME'=>'synthetic','SMTP_PASSWORD'=>'unused','MAIL_FROM_ADDRESS'=>'DoNotReply@bushisa.me','MAIL_OUTBOX_KEY'=>base64_encode(random_bytes(32)),'SIGNUP_CODE_HMAC_KEY'=>bin2hex(random_bytes(32)),'REPORT_CONTEXT_KEYS'=>json_encode(['test-v1'=>base64_encode(random_bytes(32))]),'REPORT_CONTEXT_ACTIVE_KEY'=>'test-v1','SESSION_COOKIE_SECURE'=>'false','PASSWORD_RESET_ENABLED'=>'false']);
    server($env);$a=login($u);$b=login($v);$c=login($other);$d=login($deleted);$la=login($legacyA);
    foreach(['discover.php','matches.php','confessions.php','profile.php','account_delete.php'] as $path){$r=http($path,$a);rc('RT.page.'.$path,$r['code']===200,['http'=>$r['code']]);}
    request_ok('RT.discover','api/discover.php',$a);request_ok('RT.questionnaire.read','api/questionnaire.php',$a);
    request_ok('RT.questionnaire.write','api/questionnaire.php',$a,['catalog_version'=>'matching_v1_2026_10_06','answers'=>['intent'=>['selected'=>['serious'],'fit_mode'=>'flexible','importance'=>2]]]);
    $pr=http('profile.php',$a,['display_name'=>'Synthetic changed','bio'=>'Synthetic bio','preferred_gender'=>'female','age_range_min'=>18,'age_range_max'=>99],true);
    rc('RT.profile.write',$pr['code']===200&&q($db,'SELECT display_name FROM profiles WHERE user_id=?',[$u['id']])->fetchColumn()==='Synthetic changed');
    request_ok('RT.notifications.read','api/notifications.php',$a);request_ok('RT.notifications.preferences','api/notifications.php',$a,['action'=>'save_preferences','message'=>1]);
    request_ok('RT.notifications.mark','api/notifications.php',$a,['action'=>'mark_read']);
    require_once $repo.'/php/pair_history.php';
    bushisa_report_transaction($rp,static fn()=>bushisa_pair_ensure($rp,$u['id'],$v['id']));
    denied($rp,'RT.sql.receipt_update_denied','UPDATE pair_policy_operations SET intent_hash=intent_hash');
    request_ok('RT.swipe.a','api/swipe.php',$a,['target_id'=>$v['id'],'direction'=>'like','pair_version'=>0,'idempotency_key'=>bin2hex(random_bytes(32))]);
    $vp=request_ok('RT.swipe.b.prepare','api/pairs.php?user_id='.$u['id'],$b);
    request_ok('RT.swipe.b','api/swipe.php',$b,['target_id'=>$u['id'],'direction'=>'like','pair_version'=>$vp['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))]);
    $match=(int)q($db,'SELECT id FROM matches WHERE user_a_id=? AND user_b_id=?',[$u['id'],$v['id']])->fetchColumn();rc('RT.match.created',$match>0);
    pair_action('RT.request','api/message_requests.php',$match,$a,['action'=>'request']);
    pair_action('RT.approve','api/message_requests.php',$match,$b,['action'=>'approve']);
    $sent=request_ok('RT.message.send','api/messages.php',$a,['match_id'=>$match,'body'=>'Synthetic protected text','idempotency_key'=>bin2hex(random_bytes(24))]);
    $messages=request_ok('RT.message.read','api/messages.php?match_id='.$match,$b);$message=$messages[0]??[];
    rc('RT.message.proof',isset($message['report_context'],$message['id']));
    request_ok('RT.private.denied','api/messages.php?match_id='.$match,$c,null,403);
    pair_action('RT.unmatch','api/pairs.php',$match,$a,['action'=>'unmatch']);
    request_ok('RT.chat.survives_unmatch','api/messages.php?match_id='.$match,$b);
    pair_action('RT.restore.a','api/pairs.php',$match,$a,['action'=>'restore']);pair_action('RT.restore.b','api/pairs.php',$match,$b,['action'=>'restore']);
    $p=request_ok('RT.confession.create','api/confessions.php',$b,['body'=>'Synthetic confession']);$post=(int)($p['confessions'][0]['id']??0);
    request_ok('RT.confession.reference','api/confessions.php',$a,['body'=>'Synthetic reply','referenced_confession_id'=>$post]);request_ok('RT.confession.feed','api/confessions.php',$a);
    pair_action('RT.block','api/safety.php',$match,$b,['action'=>'block','user_id'=>$u['id']]);
    $block=(int)q($db,'SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?',[$v['id'],$u['id']])->fetchColumn();
    $reportPayload=['action'=>'report','target_type'=>'message','target_id'=>(int)$message['id'],'context'=>$message['report_context'],'block_id'=>$block,'idempotency_key'=>bin2hex(random_bytes(32)),'reason'=>'harassment','explanation'=>'Synthetic allegation'];
    request_ok('RT.report.after_block','api/safety.php',$b,$reportPayload);
    $period=$db->query('SELECT starts_at,expires_at FROM report_probation_periods')->fetchAll();
    request_ok('RT.report.same_key_retry','api/safety.php',$b,$reportPayload);
    rc('RT.report.no_duplicate_probation',$period===$db->query('SELECT starts_at,expires_at FROM report_probation_periods')->fetchAll());
    denied($rp,'RT.sql.no_probation_rewrite','UPDATE report_probation_periods SET expires_at=expires_at');
    $bp=request_ok('RT.unblock.prepare','api/pairs.php?match_id='.$match,$b);
    request_ok('RT.unblock.requires_clearance','api/safety.php',$b,['action'=>'unblock','user_id'=>$u['id'],'block_id'=>$block,'pair_version'=>$bp['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))],409);
    $receipt=(int)$db->query('SELECT COUNT(*) FROM report_submissions')->fetchColumn();rc('RT.report.persisted',$receipt===1);
    $ref=(string)$db->query('SELECT receipt_id FROM report_incidents LIMIT 1')->fetchColumn();
    request_ok('RT.receipt.own','api/safety.php',$b,['action'=>'report_receipt','receipt_id'=>$ref]);
    request_ok('RT.receipt.other_denied','api/safety.php',$c,['action'=>'report_receipt','receipt_id'=>$ref],404);
    request_ok('RT.unsend','api/messages.php',$a,['action'=>'unsend','match_id'=>$match,'message_id'=>$message['id']],403);
    $case=(int)$db->query('SELECT case_id FROM report_incidents LIMIT 1')->fetchColumn();
    request_ok('RT.review.unassigned_denied','api/safety.php',$c,['action'=>'block_report_review','case_id'=>$case,'block_id'=>$block],403);
    // Trusted synthetic server-session assertion only. This is not a deployed
    // MFA integration, appointment, or evidence that an operator is staffed.
    $moderator=user($db,'mod');q($db,'UPDATE users SET role="moderator" WHERE id=?',[$moderator['id']]);
    q($db,'INSERT INTO moderation_case_assignments(case_id,assigned_user_id) VALUES(?,?)',[$case,$moderator['id']]);
    ini_set('session.save_path',$run.'/sessions');ini_set('session.use_cookies','0');session_cache_limiter('');session_id(bin2hex(random_bytes(24)));session_start();
    $_SESSION=['user_id'=>$moderator['id'],'role'=>'moderator','session_version'=>0,'last_activity'=>time(),'csrf_token'=>bin2hex(random_bytes(32)),'mfa_verified'=>true,'mfa_user_id'=>$moderator['id'],'mfa_session_version'=>0];
    $ma=['cookie'=>'PHPSESSID='.session_id(),'csrf'=>$_SESSION['csrf_token']];session_write_close();
    foreach(['admin/dashboard.php','admin/reports.php','admin/moderation.php'] as $path){$r=http($path,$ma);rc('RT.moderator.'.$path,$r['code']===200,['http'=>$r['code']]);}
    $r=http('admin/moderation.php',$ma,['action'=>'delete_confession','confession_id'=>$post],true);
    rc('RT.moderator.hard_delete_denied',(int)q($db,'SELECT COUNT(*) FROM confessions WHERE id=?',[$post])->fetchColumn()===1&&str_contains($r['body'],'Unable to delete confession'));
    $review=request_ok('RT.review.current_scope','api/safety.php',$ma,['action'=>'block_report_review','case_id'=>$case,'block_id'=>$block]);
    request_ok('RT.review.clearance','api/safety.php',$ma,['action'=>'clear_block_report','case_id'=>$case,'block_id'=>$block,'review_version'=>$review['review_version']]);
    rc('RT.clearance_preserves_block',(int)q($db,'SELECT COUNT(*) FROM blocks WHERE id=?',[$block])->fetchColumn()===1);
    pair_action('RT.creator_unblock','api/safety.php',$match,$b,['action'=>'unblock','user_id'=>$u['id'],'block_id'=>$block]);
    pair_action('RT.chat_end','api/message_requests.php',$match,$b,['action'=>'revoke']);
    $restart=request_ok('RT.restart.prepare','api/pairs.php?match_id='.$match,$b);
    request_ok('RT.restart.closed_unmatched_denied','api/message_requests.php',$b,['action'=>'request','match_id'=>$match,'pair_version'=>$restart['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))],403);
    $cancelA=user($db,'cancel_a');$cancelB=user($db,'cancel_b','female');
    $cm=bushisa_report_transaction($db,static fn()=>bushisa_pair_match($db,bushisa_pair_ensure($db,$cancelA['id'],$cancelB['id'])));
    $ca=login($cancelA);$cb=login($cancelB);
    pair_action('RT.cancel.request','api/message_requests.php',$cm,$ca,['action'=>'request']);
    pair_action('RT.cancel','api/message_requests.php',$cm,$ca,['action'=>'cancel']);
    pair_action('RT.decline.request','api/message_requests.php',$cm,$cb,['action'=>'request']);
    pair_action('RT.decline','api/message_requests.php',$cm,$ca,['action'=>'decline']);
    pair_action('RT.recreate.unmatch','api/pairs.php',$cm,$ca,['action'=>'unmatch']);
    foreach([[$ca,$cancelB],[$cb,$cancelA]] as $index=>[$auth,$target]){
        $pp=request_ok('RT.recreate.prepare.'.$index,'api/pairs.php?match_id='.$cm,$auth);
        request_ok('RT.recreate.swipe.'.$index,'api/swipe.php',$auth,['target_id'=>$target['id'],'direction'=>'like','pair_version'=>$pp['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))]);
    }
    rc('RT.recreate.retains_budget',(int)q($db,'SELECT SUM(request_count) FROM pair_request_counts WHERE (requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)',[$cancelA['id'],$cancelB['id'],$cancelB['id'],$cancelA['id']])->fetchColumn()===2);
    pair_action('RT.legacy.fail_closed','api/message_requests.php',$legacyMatch,$la,['action'=>'read']);
    $lp=request_ok('RT.legacy.prepare','api/pairs.php?match_id='.$legacyMatch,$la);
    request_ok('RT.legacy.request_denied','api/message_requests.php',$la,['action'=>'request','match_id'=>$legacyMatch,'pair_version'=>$lp['pair_version'],'idempotency_key'=>bin2hex(random_bytes(32))],503);
    // Engine denials are distinct from ordinary app permission refusals.
    denied($rp,'RT.sql.no_role','UPDATE users SET role="admin" WHERE id='.$u['id']);
    denied($rp,'RT.sql.no_clock','UPDATE users SET created_at=UTC_TIMESTAMP() WHERE id='.$u['id']);
    denied($rp,'RT.sql.no_epoch','UPDATE pair_ledger_epoch SET writer_reference="forged" WHERE singleton=1');
    denied($rp,'RT.sql.no_genesis','DELETE FROM pair_account_genesis');
    denied($rp,'RT.sql.no_drop','DROP TABLE users');
    denied($rp,'RT.sql.no_import','INSERT INTO synthetic_import_receipts(import_id) VALUES("forged")');
    // A missing required privilege must be detected through the real form,
    // without silently granting more access or returning a false success.
    $db->exec('REVOKE UPDATE ON '.obs_id($s['name']).'.profiles FROM '.$roles['runtime']['account']);
    $before=q($db,'SELECT display_name FROM profiles WHERE user_id=?',[$other['id']])->fetchColumn();
    $r=http('profile.php',$c,['display_name'=>'Must not persist'],true);
    rc('RT.missing_privilege.atomic',q($db,'SELECT display_name FROM profiles WHERE user_id=?',[$other['id']])->fetchColumn()===$before&&!str_contains($r['body'],'Profile updated successfully'));
    $db->exec('GRANT UPDATE ON '.obs_id($s['name']).'.profiles TO '.$roles['runtime']['account']);

    // Actual student registration, verification and adult completion forms.
    // Retrieve proof only in this CLI's private memory, never HTTP diagnostics.
    $student='n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).'z';$email=$student.'@students.nust.ac.zw';$signup=[];
    http('register.php',$signup);$reg=http('register.php',$signup,['nust_student_id'=>$student,'email'=>$email,'password'=>'Synthetic2!Routes','confirm_password'=>'Synthetic2!Routes','display_name'=>'Synthetic signup','anon_handle'=>'rt_'.bin2hex(random_bytes(5)),'gender'=>'male','faculty'=>'Applied Sciences','year_of_study'=>'Part 2'],true);
    rc('RT.signup.form',$reg['code']===200,['http'=>$reg['code']]);
    $new=(int)q($db,'SELECT id FROM users WHERE email=?',[$email])->fetchColumn();rc('RT.signup.pending',$new>0&&q($db,'SELECT account_status FROM users WHERE id=?',[$new])->fetchColumn()==='pending_email');
    http('verify_email.php?email='.urlencode($email),$signup);
    $codeRow=q($db,'SELECT approval_code_id,body FROM mail_outbox WHERE recipient_email=? ORDER BY id DESC LIMIT 1',[$email])->fetch(PDO::FETCH_ASSOC);
    foreach($env as $key=>$value)if(in_array($key,['MAIL_OUTBOX_KEY','SIGNUP_CODE_HMAC_KEY','MAIL_PROVIDER','MAIL_TRANSPORT','SMTP_HOST','SMTP_USERNAME','SMTP_PASSWORD','MAIL_FROM_ADDRESS'],true))putenv($key.'='.$value);
    require_once $repo.'/php/signup_verification.php';
    $payload=bushisa_decrypt_mail_payload((string)$codeRow['body'],(int)$codeRow['approval_code_id'],$email);
    preg_match('/approval code is ([0-9]{6,10})/',$payload['body'],$code);unset($payload);
    $verified=http('verify_email.php',$signup,['email'=>$email,'action'=>'verify','approval_code'=>$code[1]??''],true);unset($code);
    rc('RT.signup.verify',q($db,'SELECT account_status FROM users WHERE id=?',[$new])->fetchColumn()==='pending_onboarding',['http'=>$verified['code']]);
    $signup=login(['id'=>$new,'email'=>$email]);http('onboarding.php',$signup);$onboard=http('onboarding.php',$signup,['date_of_birth'=>'2000-01-01'],true);
    rc('RT.signup.onboarding',q($db,'SELECT account_status FROM users WHERE id=?',[$new])->fetchColumn()==='active',['http'=>$onboard['code']]);
    $reset=[];http('forgot_password.php',$reset);$off=http('forgot_password.php',$reset,['email'=>$email],true);
    rc('RT.reset.default_off',(int)$db->query('SELECT COUNT(*) FROM password_reset_challenges')->fetchColumn()===0);
    stop_server();
    // Exercise the existing trusted configured link builder, never navigate its
    // generated URL. Every HTTP request stays on owned loopback and the carrier
    // is injected below. Do not relax the Q11-only synthetic-origin guard.
    $syntheticReset=['PASSWORD_RESET_ENABLED'=>'true','BUSHISA_PASSWORD_RESET_SYNTHETIC'=>'false','BUSHISA_Q11_SYNTHETIC'=>'false','APP_BASE_URL'=>'https://bushisa.me','PASSWORD_RESET_RESEND_COOLDOWN_SECONDS'=>'1','PASSWORD_RESET_MAX_REQUESTS_PER_HOUR'=>'30','PASSWORD_RESET_MAX_REQUESTS_PER_DAY'=>'60'];
    foreach($syntheticReset as $key=>$value)putenv($key.'='.$value);server(array_replace($env,$syntheticReset));
    http('forgot_password.php',$reset);http('forgot_password.php',$reset,['email'=>$email],true);
    $job=(string)q($db,'SELECT mail_id FROM password_reset_mail_jobs WHERE account_id=? AND purpose="password_reset" AND state="ready"',[$new])->fetchColumn();
    rc('RT.reset.issuance',$job!=='');require_once $repo.'/php/password_reset_mail_jobs.php';$proof=null;$wp=obs_connect($s,$roles['worker']);
    $result=bushisa_reset_dispatch_one($wp,$job,static function(array $p)use(&$proof):void{if(preg_match('/one-time key on the reset page:\s*([a-f0-9]{64})/i',$p['body'],$m))$proof=$m[1];});
    rc('RT.reset.worker_fake_accept',$result==='sent'&&is_string($proof));
    $resetForm=[];http('reset_password.php',$resetForm);$done=http('reset_password.php',$resetForm,['reset_token'=>$proof,'new_password'=>'Changed3!Routes','confirm_password'=>'Changed3!Routes'],true);unset($proof);
    rc('RT.reset.consume',password_verify('Changed3!Routes',(string)q($db,'SELECT password_hash FROM users WHERE id=?',[$new])->fetchColumn()));
    request_ok('RT.reset.old_session_denied','api/notifications.php',$signup,null,401);
    $signup=login(['id'=>$new,'email'=>$email],'Changed3!Routes');
    request_ok('RT.account.delete','api/account.php',$signup,['action'=>'delete_account','password'=>'Changed3!Routes']);
    rc('RT.account.deleted',q($db,'SELECT account_status FROM users WHERE id=?',[$new])->fetchColumn()==='deleted');
    // Candidate shell + actual CLIs, bound to the restricted worker. This
    // no-work test disables network functions; nonempty carrier operations
    // are independently tested by RP-W02/W18/W19, not claimed here.
    stop_server();putenv('PASSWORD_RESET_ENABLED=false');$wp=obs_connect($s,$roles['worker']);bushisa_dispatch_mail($wp,100,static function():void{});
    // Windows checkout bytes are CRLF; reviewed Linux worker packaging is LF.
    $script=str_replace("\r\n","\n",file_get_contents($repo.'/deploy/mail-webjob/run.sh'));
    file_put_contents($run.'/run.sh',str_replace('cd /home/site/wwwroot','cd '.escapeshellarg($repo),$script));
    mkdir($run.'/path',0700);file_put_contents($run.'/path/php',"#!/bin/sh\nexec ".escapeshellarg(PHP_BINARY)." -d disable_functions=fsockopen,pfsockopen,stream_socket_client \"\$@\"\n");chmod($run.'/path/php',0700);
    // An intentionally unusable runtime password makes successful actual CLI
    // queries dependent on the dedicated worker binding, even for an empty queue.
    $workerEnv=array_replace($env,['DB_USER'=>$roles['runtime']['user'],'DB_PASSWORD'=>'synthetic-intentionally-invalid-runtime','MAIL_WORKER_DB_USER'=>$roles['worker']['user'],'MAIL_WORKER_DB_PASSWORD'=>$roles['worker']['password'],'MAIL_TRANSPORT'=>'smtp','PATH'=>$run.'/path:'.getenv('PATH')]);
    $package=child(['bash',$run.'/run.sh'],$workerEnv);
    $counts=array_map(static fn($line)=>json_decode($line,true),array_filter(explode("\n",trim($package['out']))));
    rc('WK.candidate.package',$package['exit']===0&&str_contains($package['out'],'"policy_cleanup":"gated"')&&str_contains($package['out'],'"sent":0'),['exit'=>$package['exit'],'counts'=>$counts,'cleanup_failed'=>str_contains($package['err'],'MAIL_CLEANUP_FAILED'),'wrapper_lf_sha256'=>hash('sha256',$script),'materialized_wrapper_sha256'=>hash_file('sha256',$run.'/run.sh'),'runtime_credential_intentionally_invalid'=>true,'network_functions_disabled'=>true,'queue'=>'drained synthetic; no SMTP call']);

    // Logical backup/restoration uses the owned local server only. Mail is
    // deliberately never dispatched from the restored database.
    stop_server();
    $db->beginTransaction();q($db,'INSERT INTO users(nust_student_id,email,password_hash,account_status) VALUES(?,?,?,"pending_email")',['n02588888'.bin2hex(random_bytes(2)),'restore-'.bin2hex(random_bytes(4)).'@students.nust.ac.zw',password_hash('Synthetic2!Restore',PASSWORD_DEFAULT)]);$queuedUser=(int)$db->lastInsertId();bushisa_signup_record_creation($db,$queuedUser);$db->commit();
    $queuedAddress=(string)q($db,'SELECT email FROM users WHERE id=?',[$queuedUser])->fetchColumn();bushisa_issue_signup_code($db,$queuedUser,$queuedAddress);
    $mailSnapshot=$db->query('SELECT status,COUNT(*) AS total FROM mail_outbox GROUP BY status ORDER BY status')->fetchAll(PDO::FETCH_ASSOC);
    $resetSnapshot=$db->query('SELECT state,COUNT(*) AS total FROM password_reset_mail_jobs GROUP BY state ORDER BY state')->fetchAll(PDO::FETCH_ASSOC);
    q($db,'INSERT INTO consent_records(user_id,consent_type,granted_at) VALUES(?,"matching",UTC_TIMESTAMP())',[$other['id']]);
    $beforeTriggers=(int)$db->query('SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema=DATABASE()')->fetchColumn();
    $snapshot=fixture_snapshot($db);
    $mysqlEnv=array_replace(getenv(),['MYSQL_PWD'=>$s['password']]);
    $clientVersion=child(['mysqldump','--version'])['out'];
    $tlsArgs=[];if(getenv('DB_SSL_CA'))$tlsArgs=array_merge(str_contains($clientVersion,'MariaDB')?['--ssl-verify-server-cert']:['--ssl-mode=VERIFY_IDENTITY'],['--ssl-ca='.getenv('DB_SSL_CA')]);
    $base=array_merge(['--host='.$s['host'],'--port='.$s['port'],'--user='.$s['user']],$tlsArgs);
    $dumpOptions=['--single-transaction','--no-tablespaces','--routines','--triggers'];
    if(!str_contains($clientVersion,'MariaDB'))$dumpOptions[]='--set-gtid-purged=OFF';
    $t=microtime(true);$dump=child(array_merge(['mysqldump'],$base,$dumpOptions,[$s['name']]),$mysqlEnv);
    rc('REC.dump',$dump['exit']===0);$timings['dump_seconds']=microtime(true)-$t;$timings['dump_bytes']=strlen($dump['out']);
    // Synthetic independent-of-the-dump instruction, held only in this owned
    // runner. No claim of durable production publication or complete adapter.
    // Actual fixture-event creation clock, not restore time or an invented
    // historical anchor. These baseline fields store seconds, not microseconds.
    $effective=(string)$db->query('SELECT UTC_TIMESTAMP()')->fetchColumn();
    $events=[['action'=>'delete','id'=>$deleted['id'],'version'=>1,'effective_at'=>$effective],['action'=>'revoke_consent','id'=>$other['id'],'version'=>1,'effective_at'=>$effective],['action'=>'block','from'=>$other['id'],'to'=>$v['id'],'effective_at'=>$effective],['action'=>'revoke_session','id'=>$v['id'],'version'=>1,'effective_at'=>$effective]];
    $journal=json_encode(['format'=>'synthetic-release-recovery-v1','events'=>$events],JSON_THROW_ON_ERROR);file_put_contents($run.'/protective-instructions.json',$journal);
    $db->exec('CREATE DATABASE '.obs_id($restore).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    // Definer privileges are not part of a logical schema dump. Bind this
    // controlled fixture migrator to the known restore destination explicitly.
    // Runtime/worker privileges remain separate; no immutable trigger is removed.
    $db->exec('GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,ALTER,INDEX,REFERENCES,TRIGGER,CREATE ROUTINE,ALTER ROUTINE,EXECUTE ON '.obs_id($restore).'.* TO '.$roles['migration']['account']);
    $t=microtime(true);$restored=child(array_merge(['mysql'],$base,[$restore]),$mysqlEnv,$dump['out']);unset($dump);
    rc('REC.restore',$restored['exit']===0);$timings['restore_seconds']=microtime(true)-$t;
    $rs=$s;$rs['name']=$restore;$rd=obs_connect($rs);
    rc('REC.all_table_data_restored',$snapshot===fixture_snapshot($rd),['table_count'=>count($snapshot),'synthetic_rows'=>array_sum(array_column($snapshot,'rows'))]);
    rc('REC.schema',(int)$rd->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn()===16&&(int)$rd->query('SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema=DATABASE()')->fetchColumn()===$beforeTriggers);
    $health=$rd->query('CALL bushisa_pair_trigger_health()');$healthy=(int)$health->fetchColumn()===1;$health->closeCursor();rc('REC.definer_health',$healthy);
    rc('REC.stale_copy_detected',q($rd,'SELECT account_status FROM users WHERE id=?',[$deleted['id']])->fetchColumn()==='active'&&q($rd,'SELECT COUNT(*) FROM consent_records WHERE user_id=? AND revoked_at IS NULL',[$other['id']])->fetchColumn()==1);
    // This fixture demonstrates required replay effects, not a production apply
    // tool. It deliberately has no CLI input, apply mode or release-open switch.
    $t=microtime(true);
    for($pass=0;$pass<2;$pass++){
        $rd->beginTransaction();
        foreach(json_decode($journal,true,8,JSON_THROW_ON_ERROR)['events'] as $e){
            if($e['action']==='delete')q($rd,'UPDATE users SET account_status="deleted",is_active=0,deleted_at=COALESCE(deleted_at,?),session_version=GREATEST(session_version,?) WHERE id=?',[$e['effective_at'],$e['version'],$e['id']]);
            elseif($e['action']==='revoke_consent'){q($rd,'UPDATE consent_records SET revoked_at=COALESCE(revoked_at,?) WHERE user_id=? AND consent_type="matching"',[$e['effective_at'],$e['id']]);q($rd,'UPDATE users SET session_version=GREATEST(session_version,?) WHERE id=?',[$e['version'],$e['id']]);}
            elseif($e['action']==='block')q($rd,'INSERT IGNORE INTO blocks(blocker_id,blocked_id,created_at) VALUES(?,?,?)',[$e['from'],$e['to'],$e['effective_at']]);
            elseif($e['action']==='revoke_session')q($rd,'UPDATE users SET session_version=GREATEST(session_version,?) WHERE id=?',[$e['version'],$e['id']]);
        }
        $rd->commit();
    }
    $timings['replay_two_passes_seconds']=microtime(true)-$t;
    rc('REC.original_event_clocks',q($rd,'SELECT DATE_FORMAT(deleted_at,"%Y-%m-%d %H:%i:%s") FROM users WHERE id=?',[$deleted['id']])->fetchColumn()===$effective&&q($rd,'SELECT DATE_FORMAT(revoked_at,"%Y-%m-%d %H:%i:%s") FROM consent_records WHERE user_id=?',[$other['id']])->fetchColumn()===$effective);
    rc('REC.replay_idempotent',q($rd,'SELECT COUNT(*) FROM blocks WHERE blocker_id=? AND blocked_id=?',[$other['id'],$v['id']])->fetchColumn()==1&&q($rd,'SELECT session_version FROM users WHERE id=?',[$v['id']])->fetchColumn()==1);
    // Restore destination gets the same proposed runtime grants. No worker is
    // started; fake carrier and issuance remain disabled during read checks.
    putenv('DB_NAME='.$restore);$restoreRole=rr_role($rd,$rs,'runtime');putenv('DB_NAME='.$s['name']);
    $restoredEnv=array_replace($env,['DB_NAME'=>$restore,'DB_USER'=>$restoreRole['user'],'DB_PASSWORD'=>$restoreRole['password'],'PASSWORD_RESET_ENABLED'=>'false','MAIL_PROVIDER'=>'fake','MAIL_TRANSPORT'=>'fake']);
    server($restoredEnv);
    request_ok('REC.deleted_session_denied','api/notifications.php',$d,null,401);
    request_ok('REC.revoked_session_denied','api/notifications.php',$b,null,401);
    request_ok('REC.withdrawn_session_denied','api/notifications.php',$c,null,401);
    $newC=login($other);request_ok('REC.block_contact_denied','api/pairs.php?user_id='.$v['id'],$newC,null,403);
    rc('REC.block_preserved',(int)q($rd,'SELECT COUNT(*) FROM blocks WHERE blocker_id=? AND blocked_id=?',[$other['id'],$v['id']])->fetchColumn()===1);
    require_once $repo.'/php/consent_manager.php';rc('REC.consent_withdrawn',!has_active_consent($rd,$other['id'],'matching'));
    $noDispatch=child([PHP_BINARY,'-d','disable_functions=fsockopen,pfsockopen,stream_socket_client','bin/dispatch_mail.php'],$restoredEnv);
    rc('REC.restore_dispatch_refused',$noDispatch['exit']!==0);
    rc('REC.mail_not_dispatched',$mailSnapshot===$rd->query('SELECT status,COUNT(*) AS total FROM mail_outbox GROUP BY status ORDER BY status')->fetchAll(PDO::FETCH_ASSOC)&&$resetSnapshot===$rd->query('SELECT state,COUNT(*) AS total FROM password_reset_mail_jobs GROUP BY state ORDER BY state')->fetchAll(PDO::FETCH_ASSOC));
    rc('REC.pair_limits_preserved',$db->query('SELECT requester_id,recipient_id,request_count FROM pair_request_counts ORDER BY requester_id,recipient_id')->fetchAll()===$rd->query('SELECT requester_id,recipient_id,request_count FROM pair_request_counts ORDER BY requester_id,recipient_id')->fetchAll()&&$db->query('SELECT match_id,ending_count,chat_suppressed FROM pair_policy_history ORDER BY user_low,user_high')->fetchAll()===$rd->query('SELECT match_id,ending_count,chat_suppressed FROM pair_policy_history ORDER BY user_low,user_high')->fetchAll());
    rc('REC.source_unchanged',q($db,'SELECT account_status FROM users WHERE id=?',[$deleted['id']])->fetchColumn()==='active'&&q($db,'SELECT session_version FROM users WHERE id=?',[$v['id']])->fetchColumn()==0);
} catch(Throwable $e){
    $last=$checks?end($checks):null;$failure=['class'=>get_class($e),'case'=>$last['case']??null];
    if($e instanceof PDOException)$failure['engine_error']=$e->errorInfo[1]??null;
    if($e instanceof PDOException&&preg_match("/(SELECT|INSERT|UPDATE|DELETE|EXECUTE) command denied.*?(?:table|routine) '([a-zA-Z0-9_.]+)'/",(string)($e->errorInfo[2]??''),$m))$failure['denied_object']=$m[1].' '.$m[2];
    foreach(['certificate verify failed','Connection refused','No such file','Access denied','Unknown database'] as $reason)if(str_contains($e->getMessage(),$reason))$failure['connection_reason']=$reason;
    $failure['location']=basename($e->getFile()).':'.$e->getLine();
} finally {
    stop_server();
    if($db&&$authorized){
        // A failed replay may still own locks on the destination. Release all
        // owned transactions before cleanup, never wait on our own connection.
        foreach([$rd??null,$rp??null,$wp??null] as $connection)if($connection instanceof PDO&&$connection->inTransaction())$connection->rollBack();
        if($db->inTransaction())$db->rollBack();
        foreach([$restore,$s['name']] as $name)$db->exec('DROP DATABASE IF EXISTS '.obs_id($name));
        foreach(array_reverse($GLOBALS['rr_accounts']) as $account)$db->exec('DROP USER IF EXISTS '.$account);
        if($original){$db->exec('SET GLOBAL sql_mode='.$db->quote($original['sql_mode']));$db->exec('SET GLOBAL time_zone='.$db->quote($original['timezone']));$db->exec('SET GLOBAL log_bin_trust_function_creators='.(int)$original['trust']);}
    }
    $report=['format'=>'bushisa.release-route-recovery.v1','utc'=>gmdate('c'),'php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$db?($db->getAttribute(PDO::ATTR_SERVER_VERSION)):null,'candidate'=>getenv('RELEASE_SOURCE_SHA')?:null,'checks'=>$checks,'timings'=>$timings,'failure'=>$failure,'production_recovery'=>'NOT RUN','production_replay_adapter'=>'UNAVAILABLE; fixture replay only','independent_durability'=>'NOT PROVED','managed_staging'=>'DEFERRED / NOT RUN'];
    foreach(['tests/integration_release_routes_recovery.php','tests/support/release_roles.php','tests/support/owner_schema_fixture.php','php/pair_history.php','php/report_intake.php','php/evidence_lifecycle.php','deploy/mail-webjob/run.sh'] as $file)$report['sources_lf_sha256'][$file]=hash('sha256',str_replace("\r\n","\n",file_get_contents($repo.'/'.$file)));
    $report['client_version']=trim($clientVersion??'NOT RUN');
    $report['profile']=$profile??null;
    @mkdir($repo.'/test-results',0700,true);file_put_contents($repo.'/test-results/release-routes-recovery.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
    // Delete only this run's known synthetic files. No broad store traversal.
    if(is_dir($run.'/sessions')){foreach(glob($run.'/sessions/sess_*')?:[] as $f)if(is_file($f)&&!is_link($f))unlink($f);@rmdir($run.'/sessions');}
    if(is_file($run.'/path/php'))unlink($run.'/path/php');if(is_dir($run.'/path'))rmdir($run.'/path');
    foreach(['http.log','protective-instructions.json','run.sh'] as $f)if(is_file($run.'/'.$f))unlink($run.'/'.$f);if(is_dir($run))@rmdir($run);
}
foreach($checks as $c)echo $c['case'].' '.$c['result']."\n";
if($failure){fwrite(STDERR,json_encode($failure)."\n");exit(1);}
