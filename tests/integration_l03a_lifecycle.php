<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/l03a_fixture.php';
use Bushisa\Evidence\{Context,Failure};
use function Bushisa\Evidence\{act,add,alert,cleanup,held_view,identity,now,publish,purge_locked,query,recovery_bound,recovery_gate,register_case,replay,transaction};
use function Bushisa\EvidenceFixture\{advance,authority,clock,connect,install,item,scenario,user,worker_user};

// Explicit flags and fake transport are prerequisites, not selected by this test.
\Bushisa\Evidence\synthetic_guard();
$settings=bushisa_require_disposable_test_database('L03a integration');
$rootPath=dirname(__DIR__); $runDir=$rootPath.'/.tmp/l03a-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
mkdir($runDir,0700,true); mkdir($runDir.'/sessions',0700); mkdir($runDir.'/l03a-journal',0700);
putenv('BUSHISA_L03A_JOURNAL='.$runDir.'/l03a-journal');
putenv('BUSHISA_L03A_RECOVERY_NAMESPACE='.bin2hex(random_bytes(32)));
putenv('REPORT_CONTEXT_KEYS='.json_encode(['synthetic'=>base64_encode(random_bytes(32))])); putenv('REPORT_CONTEXT_ACTIVE_KEY=synthetic');
$checks=[]; $races=[]; $servers=[]; $migration=[]; $failure=null; $environment=[]; $started=gmdate('c'); $runtime=[];
function check_lc(string $id,string $name,bool $ok): void {
    global $checks; $checks[]=['case'=>$id,'check'=>$name,'result'=>$ok?'PASS':'FAIL'];
    if (!$ok) { throw new RuntimeException($id.': '.$name); }
}
function fails_lc(string $id,string $name,callable $fn,string $reason): void {
    try { $fn(); $got='NO_FAILURE'; } catch(Failure $e) { $got=$e->reason; }
    check_lc($id,$name,$got===$reason);
}
function child_lc(array $args,array $env=[]): array {
    global $rootPath; $pipes=[];
    $p=proc_open(array_merge([PHP_BINARY],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$rootPath,array_merge(getenv(),$env));
    if (!is_resource($p)) { throw new RuntimeException('Unable to start synthetic child'); } fclose($pipes[0]); return [$p,$pipes];
}
function finish_lc(array $child): array {
    [$p,$pipes]=$child; $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return ['code'=>proc_close($p),'stdout'=>$out,'stderr'=>$err,'json'=>json_decode($out,true)];
}
function cli_lc(array $args,array $env=[]): array { return finish_lc(child_lc($args,$env)); }
function context_lc(array $f,string $who='reviewer'): Context { return Context::fromServerSession($f['auth'][$who]); }
function action_lc(PDO $db,array $f,string $action,array $args=[],string $who='reviewer',?string $key=null): array {
    return act($db,context_lc($f,$who),$f['case'],$key??bin2hex(random_bytes(16)),$action,$args);
}
function resolved_lc(PDO $db,PDO $root,string $tag,string $class='ordinary'): array {
    $f=scenario($root,$tag); action_lc($db,$f,'notice',['classification'=>$class]);
    advance($root,'+14 days'); action_lc($db,$f,'finalize');
    $f['clock']=query($root,'SELECT * FROM evidence_case_clocks WHERE case_id=?',[$f['case']])->fetch(PDO::FETCH_ASSOC); return $f;
}
function purge_lc(PDO $db,string $key): array { return transaction($db,fn()=>purge_locked($db,$key)); }
function body_lc(PDO $db,array $f,int $which=0): mixed { return query($db,'SELECT evidence_excerpt FROM moderation_evidence WHERE id=?',[$f['evidence'][$which]])->fetchColumn(); }
function state_lc(PDO $db,string $key): array { return query($db,'SELECT * FROM evidence_lifecycle_items WHERE item_key=?',[$key])->fetch(PDO::FETCH_ASSOC); }
function hold_lc(PDO $db,array $f,string $key,int $hours=24): array { return action_lc($db,$f,'urgent_hold',['item'=>$key,'revision'=>1,'hours'=>$hours,'reason'=>'CASE_CONTEXT']); }
function release_lc(PDO $db,array $f,string $hold): array {
    action_lc($db,$f,'request_release',['hold'=>$hold],'lead'); return action_lc($db,$f,'release_hold',['hold'=>$hold],'privacy');
}
function journal_init_lc(): void {
    $path=\Bushisa\Evidence\journal_path(); $head=['watermark'=>0,'digest'=>identity('empty-journal-v1')];
    file_put_contents($path,json_encode(['format'=>'synthetic-l03a-journal-v1','namespace'=>getenv('BUSHISA_L03A_RECOVERY_NAMESPACE'),'events'=>[]]+$head)); file_put_contents($path.'.head',json_encode($head));
}
function ready_lc(PDO $db): void { publish($db); replay($db); recovery_gate($db); }
function session_lc(array $session): array {
    global $runDir; ini_set('session.save_path',$runDir.'/sessions'); ini_set('session.use_cookies','0'); session_cache_limiter('');
    session_id(bin2hex(random_bytes(24))); session_start(); $csrf=bin2hex(random_bytes(32)); $_SESSION=$session+['last_activity'=>time(),'csrf_token'=>$csrf];
    $id=session_id(); session_write_close(); return ['cookie'=>'PHPSESSID='.$id,'csrf'=>$csrf];
}
function http_lc(array $session,array $payload): array {
    $c=curl_init('http://127.0.0.1:8137/api/evidence_lifecycle.php');
    curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_HTTPHEADER=>['Content-Type: application/json','Cookie: '.$session['cookie'],'X-CSRF-Token: '.$session['csrf']],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15]);
    $out=curl_exec($c); if ($out===false) { throw new RuntimeException('Synthetic HTTP transport failure'); }
    return ['code'=>curl_getinfo($c,CURLINFO_RESPONSE_CODE),'json'=>json_decode($out,true)];
}
function until_lc(callable $condition,int $ms=4500): void {
    $end=microtime(true)+$ms/1000; while(!$condition()) { if(microtime(true)>=$end) { throw new RuntimeException('Synthetic overlap observation timed out'); } usleep(10000); }
}
/** Actual process/connection overlap observed at MySQL's shared advisory lock.
 * Parent changes only fixture clock/authorization before permitting lock order. */
function race_lc(PDO $root,array $jobs,array $env,?callable $between=null): array {
    global $runDir,$rootPath,$races; $id=bin2hex(random_bytes(6)); $children=[]; $ready=[]; $answers=[];
    $lock='bushisa-report:'.substr(hash('sha256',(string)$root->query('SELECT DATABASE()')->fetchColumn()),0,32);
    query($root,'SELECT GET_LOCK(?,5)',[$lock]);
    try {
        foreach($jobs as $n=>$job) {
            $file=$runDir.'/job-'.$id.'-'.$n.'.json'; $r=$runDir.'/ready-'.$id.'-'.$n.'.json'; file_put_contents($file,json_encode($job,JSON_THROW_ON_ERROR));
            $children[]=child_lc([$rootPath.'/tests/support/l03a_worker.php','--job='.$file,'--ready='.$r],$env); $ready[]=$r;
        }
        until_lc(fn()=>count(array_filter($ready,'is_file'))===count($jobs));
        $workers=array_map(fn($f)=>json_decode(file_get_contents($f),true,16,JSON_THROW_ON_ERROR),$ready);
        $ids=array_column($workers,'connection_id'); $waiting=[];
        until_lc(function() use($root,$ids,&$waiting): bool {
            $waiting=query($root,'SELECT t.PROCESSLIST_ID AS connection_id,m.LOCK_STATUS FROM performance_schema.metadata_locks m JOIN performance_schema.threads t ON t.THREAD_ID=m.OWNER_THREAD_ID
                WHERE m.OBJECT_TYPE="USER LEVEL LOCK" AND m.LOCK_STATUS="PENDING" AND t.PROCESSLIST_ID IN ('.implode(',',array_fill(0,count($ids),'?')).')',$ids)->fetchAll(PDO::FETCH_ASSOC);
            return count($waiting)===count($ids);
        });
        check_lc('LC09','distinct overlapping process/SQL identities',count(array_unique(array_column($workers,'pid')))===count($jobs) && count(array_unique($ids))===count($jobs));
        if ($between) { $between(); }
        query($root,'SELECT RELEASE_LOCK(?)',[$lock]);
        foreach($children as $c) { $answers[]=finish_lc($c); } $children=[];
        $races[]=['workers'=>$workers,'observed_pending_locks'=>$waiting,'results'=>array_map(fn($a)=>['exit'=>$a['code'],'result'=>$a['json']['result']??null,'error'=>json_decode($a['stderr'],true)['error']??null],$answers)];
        return $answers;
    } catch(Throwable $e) {
        $diagnostic=[];
        foreach($children as $c) { proc_terminate($c[0]); $diagnostic[]=finish_lc($c); } $children=[];
        file_put_contents($runDir.'/race-failure.json',json_encode(['workers'=>$workers??[],'waiting'=>$waiting??[],'children'=>$diagnostic],JSON_PRETTY_PRINT));
        throw $e;
    } finally {
        query($root,'SELECT RELEASE_LOCK(?)',[$lock]);
        foreach($children as $c) { proc_terminate($c[0]); finish_lc($c); }
    }
}

try {
    bushisa_bootstrap_disposable_database($rootPath.'/database/bushisa.sql');
    $m=cli_lc([$rootPath.'/bin/migrate.php']); check_lc('LC17','fresh migration 010',$m['code']===0 && str_contains($m['stdout'],'Applying 010_')); $migration[]=['kind'=>'fresh','result'=>$m['code']];
    $repeat=cli_lc([$rootPath.'/bin/migrate.php']); check_lc('LC17','fresh repeat skips 010',$repeat['code']===0 && str_contains($repeat['stdout'],'Skipping 010_'));
    $root=connect(); install($root); $runtime=worker_user($root,$settings['name']); $db=connect($runtime);
    $runtimeEnv=['DB_USER'=>$runtime['user'],'DB_PASSWORD'=>$runtime['password']]; journal_init_lc();
    $environment=['php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$root->getAttribute(PDO::ATTR_SERVER_VERSION),'mysqlnd'=>phpversion('mysqlnd'),
        'extensions'=>array_values(array_intersect(['PDO','pdo_mysql','openssl','curl','mbstring','fileinfo'],get_loaded_extensions())),
        'settings'=>$root->query('SELECT @@transaction_isolation AS isolation_level,@@time_zone AS utc,@@sql_mode AS sql_mode,@@binlog_format AS binlog_format,@@server_uuid AS server_uuid')->fetch(PDO::FETCH_ASSOC),
        'database'=>$settings['name'],'host'=>$settings['host'],'port'=>$settings['port'],'runtime_grants'=>$root->query('SHOW GRANTS FOR '.$runtime['account'])->fetchAll(PDO::FETCH_COLUMN)];

    // Missing and contradictory anchors are visible obligations, not guessed ages.
    $missing=scenario($root,'missing'); $missingItem=item($db,$missing);
    $r=purge_lc($db,$missingItem); check_lc('LC01','missing effective notice blocks expiry/purge',$r['status']==='unresolved' && body_lc($root,$missing)!==null && state_lc($db,$missingItem)['ordinary_expires_at']===null);
    query($root,'UPDATE moderation_cases SET final_resolution_at="2020-01-01",status="reviewed" WHERE id=?',[$missing['case']]);
    // Discovery cannot certify that raw historical value on an existing row.
    transaction($db,fn()=>register_case($db,$missing['case']));
    check_lc('LC01','legacy reviewed/raw final is not a trusted anchor',query($db,'SELECT final_at FROM evidence_case_clocks WHERE case_id=?',[$missing['case']])->fetchColumn()===null);
    $legacy=scenario($root,'legacy'); query($root,'UPDATE moderation_cases SET intake_contract_version=0 WHERE id=?',[$legacy['case']]);
    check_lc('LC01','legacy intake excluded',transaction($db,fn()=>register_case($db,$legacy['case']))===false);
    $bad=scenario($root,'bad'); action_lc($db,$bad,'notice',['classification'=>'ordinary']);
    query($root,'UPDATE evidence_case_clocks SET appeal_disposed_at=notice_at WHERE case_id=?',[$bad['case']]); purge_lc($db,item($db,$bad));
    check_lc('LC01','contradictory disposition stays explicit/unresolved',query($db,'SELECT unresolved_code FROM evidence_case_clocks WHERE case_id=?',[$bad['case']])->fetchColumn()==='CONTRADICTORY_ANCHORS' && body_lc($db,$bad)!==null);
    $gap=scenario($root,'anchorGap'); action_lc($db,$gap,'notice',['classification'=>'ordinary']);
    query($root,'UPDATE evidence_case_clocks SET appeal_until=NULL WHERE case_id=?',[$gap['case']]); purge_lc($db,item($db,$gap));
    check_lc('LC01','missing appeal boundary explicitly reported',query($db,'SELECT unresolved_code FROM evidence_case_clocks WHERE case_id=?',[$gap['case']])->fetchColumn()==='MISSING_APPEAL_BOUNDARY' && body_lc($db,$gap)!==null);

    $f=resolved_lc($db,$root,'normal'); $key=item($db,$f); $due=$f['clock']['content_expires_at'];
    check_lc('LC02','180d content and distinct 365d decision anchors',$due===add($f['clock']['final_at'],'+180 days') && $f['clock']['decision_expires_at']===add($f['clock']['final_at'],'+365 days'));
    clock($root,add($due,'-1 microsecond')); check_lc('LC02','one microsecond before expiry remains ordinary',bushisa_report_receipt($db,$f['a'],$f['submission'])['receipt_id']===$f['receipt'] && purge_lc($db,$key)['count']===0);
    clock($root,$due);
    try { bushisa_report_receipt($db,$f['a'],$f['submission']); $denied=false; } catch(BushisaReportError $e) { $denied=$e->status===404; }
    check_lc('LC02','exact boundary denies receipt without worker',$denied && body_lc($db,$f)!==null);
    check_lc('LC02','exact boundary purges text only',purge_lc($db,$key)['count']===1 && body_lc($db,$f)===null && query($db,'SELECT reason FROM moderation_cases WHERE id=?',[$f['case']])->fetchColumn()==='Synthetic allegation');
    $short=resolved_lc($db,$root,'short','accidental'); check_lc('LC02','wholly accidental 30d bound',$short['clock']['content_expires_at']===add($short['clock']['final_at'],'+30 days'));
    clock($root,$short['clock']['content_expires_at']);
    check_lc('LC02','30d exact boundary physically purges selected copy',purge_lc($db,item($db,$short))['count']===1);

    $p=scenario($root,'appeal'); action_lc($db,$p,'notice',['classification'=>'ordinary']); $notice=now($db); advance($root,'+2 days'); action_lc($db,$p,'appeal_received');
    advance($root,'+13 days'); purge_lc($db,item($db,$p));
    check_lc('LC03','timely pending appeal prevents initial finding clock',query($db,'SELECT final_at FROM evidence_case_clocks WHERE case_id=?',[$p['case']])->fetchColumn()===null);
    fails_lc('LC03','initial reviewer cannot decide own appeal',fn()=>action_lc($db,$p,'appeal_decided',['classification'=>'unrelated']),'CURRENT_AUTHORITY_REQUIRED');
    action_lc($db,$p,'appeal_decided',['classification'=>'unrelated'],'privacy'); $final=now($db); $pc=query($db,'SELECT * FROM evidence_case_clocks WHERE case_id=?',[$p['case']])->fetch(PDO::FETCH_ASSOC);
    check_lc('LC03','later appeal uses final unrelated finding30d',$pc['final_at']===$final && $pc['content_expires_at']===add($final,'+30 days') && $final>add($notice,'+14 days'));
    $early=scenario($root,'earlyAppeal');
    query($root,'UPDATE moderation_cases SET report_resolution_class="accidental" WHERE id=?',[$early['case']]);
    action_lc($db,$early,'notice',['classification'=>'accidental']); $earlyNotice=now($db); advance($root,'+1 day');
    action_lc($db,$early,'appeal_received'); action_lc($db,$early,'appeal_decided',['classification'=>'ordinary'],'privacy');
    check_lc('LC03','early disposition waits for original appeal boundary',query($db,'SELECT final_at FROM evidence_case_clocks WHERE case_id=?',[$early['case']])->fetchColumn()===null);
    clock($root,add($earlyNotice,'+14 days')); action_lc($db,$early,'finalize');
    clock($root,add($earlyNotice,'+44 days'));
    check_lc('LC03','ordinary appeal outcome180d updates legacy projection and ordinary receipt',query($db,'SELECT content_expires_at FROM evidence_case_clocks WHERE case_id=?',[$early['case']])->fetchColumn()===add(add($earlyNotice,'+14 days'),'+180 days')
        && query($db,'SELECT report_resolution_class FROM moderation_cases WHERE id=?',[$early['case']])->fetchColumn()==='ordinary'
        && bushisa_report_receipt($db,$early['a'],$early['submission'])['receipt_id']===$early['receipt']);
    fails_lc('LC03','late repeat appeal cannot renew old expiry',fn()=>action_lc($db,$p,'appeal_received'),'APPEAL_WINDOW_CLOSED');
    fails_lc('LC03','arbitrary reopen is unavailable',fn()=>action_lc($db,$p,'reopen',['final_at'=>$notice]),'UNSUPPORTED_OR_UNTRUSTED_FIELDS');

    $h=resolved_lc($db,$root,'holds'); $hk=item($db,$h); $h2=item($db,$h,1); $original=$h['clock']['content_expires_at'];
    $ordinarySession=$h['auth']['reviewer']; unset($ordinarySession['evidence_authority_assertion']); $ordinarySession['mfa_verified']=true; $ordinarySession['role']='admin';
    fails_lc('LC04','session/client MFA flags are not authority',fn()=>Context::fromServerSession($ordinarySession),'TRUSTED_AUTHORITY_REQUIRED');
    fails_lc('LC04','wrong case cannot use valid authority',fn()=>act($db,context_lc($h),$f['case'],bin2hex(random_bytes(16)),'urgent_hold',['item'=>$hk,'revision'=>1,'hours'=>24,'reason'=>'CASE_CONTEXT']),'CURRENT_AUTHORITY_REQUIRED');
    fails_lc('LC04','stale item version refused',fn()=>hold_lc($db,$h,$hk,25),'INVALID_HOLD_SCOPE');
    fails_lc('LC04','wrong source revision refused',fn()=>action_lc($db,$h,'urgent_hold',['item'=>$hk,'revision'=>2,'hours'=>24,'reason'=>'CASE_CONTEXT']),'ITEM_UNAVAILABLE');
    query($root,'UPDATE moderation_case_assignments SET revoked_at=? WHERE case_id=? AND assigned_user_id=?',[now($db),$h['case'],$h['reviewer']]);
    fails_lc('LC04','revoked assignment cannot grant hold',fn()=>hold_lc($db,$h,$hk),'CURRENT_ASSIGNMENT_REQUIRED');
    query($root,'UPDATE moderation_case_assignments SET revoked_at=NULL WHERE case_id=? AND assigned_user_id=?',[$h['case'],$h['reviewer']]);
    query($root,'UPDATE l03a_fixture_authority SET expires_at=? WHERE actor_id=?',[now($db),$h['reviewer']]);
    fails_lc('LC04','expired trusted fixture assertion cannot grant hold',fn()=>hold_lc($db,$h,$hk),'CURRENT_AUTHORITY_REQUIRED');
    query($root,'UPDATE l03a_fixture_authority SET expires_at="2099-01-01" WHERE actor_id=?',[$h['reviewer']]);
    fails_lc('LC04','foreign item refused',fn()=>hold_lc($db,$h,item($db,$p)),'ITEM_UNAVAILABLE');
    fails_lc('LC04','free text cannot be placed in reason diagnostics',fn()=>action_lc($db,$h,'urgent_hold',['item'=>$hk,'revision'=>1,'hours'=>24,'reason'=>'PRIVATE_TEXT']),'INVALID_HOLD_SCOPE');
    foreach(['role'=>'admin','mfa'=>true,'at'=>'2020-01-01','final_resolution'=>true] as $field=>$value) {
        fails_lc('LC04','client '.$field.' not authoritative',fn()=>action_lc($db,$h,'urgent_hold',['item'=>$hk,'revision'=>1,'hours'=>24,'reason'=>'CASE_CONTEXT',$field=>$value]),'UNSUPPORTED_OR_UNTRUSTED_FIELDS');
    }
    $urgent=hold_lc($db,$h,$h2); $holdrow=query($db,'SELECT * FROM evidence_lifecycle_holds WHERE hold_id=?',[$urgent['hold']])->fetch(PDO::FETCH_ASSOC);
    check_lc('LC04','urgent hold precisely 24h exact item',$holdrow['expires_at']===add($holdrow['granted_at'],'+24 hours') && $holdrow['item_key']===$h2);
    fails_lc('LC05','urgent reissue cannot evade joint24h requirement',fn()=>hold_lc($db,$h,$h2),'JOINT_AUTHORIZATION_REQUIRED');
    query($root,'UPDATE users SET role="user" WHERE id=?',[$h['reviewer']]);
    fails_lc('LC04','removed role cannot reuse authority',fn()=>hold_lc($db,$h,$h2),'CURRENT_AUTHORITY_REQUIRED'); query($root,'UPDATE users SET role="moderator" WHERE id=?',[$h['reviewer']]);

    $proposal=action_lc($db,$h,'propose_hold',['item'=>$hk,'revision'=>1,'hours'=>720,'reason'=>'SAFETY_CASE'],'lead');
    check_lc('LC05','single lead proposal is not active',query($db,'SELECT state FROM evidence_lifecycle_holds WHERE hold_id=?',[$proposal['hold']])->fetchColumn()==='proposed');
    fails_lc('LC05','single-role approval cannot grant hold',fn()=>action_lc($db,$h,'approve_hold',['hold'=>$proposal['hold']],'lead'),'CURRENT_AUTHORITY_REQUIRED');
    fails_lc('LC05','over thirty days refused',fn()=>action_lc($db,$h,'propose_hold',['item'=>$hk,'revision'=>1,'hours'=>721,'reason'=>'SAFETY_CASE'],'lead'),'INVALID_HOLD_SCOPE');
    query($root,'UPDATE l03a_fixture_authority SET revoked_at=? WHERE actor_id=?',[now($db),$h['lead']]);
    fails_lc('LC05','second approval rechecks first authority',fn()=>action_lc($db,$h,'approve_hold',['hold'=>$proposal['hold']],'privacy'),'CURRENT_AUTHORITY_REQUIRED');
    query($root,'UPDATE l03a_fixture_authority SET revoked_at=NULL WHERE actor_id=?',[$h['lead']]);
    fails_lc('LC05','client cannot extend next review past14d',fn()=>action_lc($db,$h,'propose_hold',['item'=>$hk,'revision'=>1,'hours'=>720,'reason'=>'SAFETY_CASE','review_days'=>15],'lead'),'UNSUPPORTED_OR_UNTRUSTED_FIELDS');
    action_lc($db,$h,'approve_hold',['hold'=>$proposal['hold']],'privacy');
    $joint=query($db,'SELECT * FROM evidence_lifecycle_holds WHERE hold_id=?',[$proposal['hold']])->fetch(PDO::FETCH_ASSOC);
    check_lc('LC05','joint30d/review14d use server time',$joint['expires_at']===add($joint['granted_at'],'+30 days') && $joint['review_due_at']===add($joint['granted_at'],'+14 days'));
    advance($root,'+1 day'); action_lc($db,$h,'review_hold',['hold'=>$proposal['hold']],'lead');
    check_lc('LC05','review does not renew expiry',query($db,'SELECT expires_at FROM evidence_lifecycle_holds WHERE hold_id=?',[$proposal['hold']])->fetchColumn()===$joint['expires_at']);
    fails_lc('LC05','automatic renewal operation absent',fn()=>action_lc($db,$h,'renew_hold',['hold'=>$proposal['hold']],'lead'),'UNSUPPORTED_OR_UNTRUSTED_FIELDS');
    release_lc($db,$h,$proposal['hold']);
    check_lc('LC06','release before ordinary expiry retains original due',state_lc($db,$hk)['ordinary_expires_at']===$original && query($db,'SELECT purge_due_at FROM evidence_purge_obligations WHERE item_key=?',[$hk])->fetchColumn()===$original);

    clock($root,add($original,'-1 hour')); $active=hold_lc($db,$h,$hk);
    $overlap=action_lc($db,$h,'propose_hold',['item'=>$hk,'revision'=>1,'hours'=>23,'reason'=>'SAFETY_CASE'],'lead');
    action_lc($db,$h,'approve_hold',['hold'=>$overlap['hold']],'privacy');
    clock($root,$original); $preserved=purge_lc($db,$hk);
    check_lc('LC08','one held copy survives while other evidence purges',$preserved['status']==='held' && body_lc($db,$h)!==null && purge_lc($db,$h2)['count']===1);
    // Existing L01 ordinary-review callback has broader locking privileges than
    // the new read-only authority adapter. Exercise it with fixture credentials;
    // do not broaden the L03a worker's grants to accommodate an unrelated path.
    check_lc('LC08','hold does not restore ordinary reviewer access',bushisa_report_review_evidence($root,$h['case'],$h['reviewer'],['user_id'=>$h['reviewer'],'session_version'=>3,'mfa_verified'=>true,'mfa_user_id'=>$h['reviewer'],'mfa_session_version'=>3])===[]);
    $submissionKey=identity('text-item-v1','submission',(string)$h['submission']); purge_lc($db,$submissionKey);
    check_lc('LC08','hold does not protect explanation/fingerprints',query($db,'SELECT explanation FROM report_submissions WHERE id=?',[$h['submission']])->fetchColumn()===null && query($db,'SELECT intent_hash FROM report_operations WHERE submission_id=?',[$h['submission']])->fetchColumn()===null);
    fails_lc('LC08','unpublished journal blocks scoped held read',fn()=>held_view($db,context_lc($h),$h['case'],$hk,1,'CASE_EVIDENCE_REVIEW'),'INDEPENDENT_RECOVERY_NOT_READY');
    ready_lc($db); check_lc('LC08','scoped qualified fixture held read returns only selected item',held_view($db,context_lc($h),$h['case'],$hk,1,'CASE_EVIDENCE_REVIEW')===body_lc($db,$h));
    fails_lc('LC08','unheld expired item not resurrected',fn()=>held_view($db,context_lc($h),$h['case'],$h2,1,'CASE_EVIDENCE_REVIEW'),'ITEM_UNAVAILABLE');
    check_lc('LC08','ordinary source and chat relation untouched',query($db,'SELECT body FROM messages WHERE id=?',[$h['message']])->fetchColumn()==='Synthetic ordinary source NOT an evidence copy');
    release_lc($db,$h,$overlap['hold']); check_lc('LC06','release one overlapping hold preserves other',purge_lc($db,$hk)['status']==='held');
    advance($root,'+2 hours'); $releaseKey=bin2hex(random_bytes(16)); action_lc($db,$h,'request_release',['hold'=>$active['hold']],'lead');
    $released=action_lc($db,$h,'release_hold',['hold'=>$active['hold']],'privacy',$releaseKey);
    $ob=query($db,'SELECT * FROM evidence_purge_obligations WHERE item_key=?',[$hk])->fetch(PDO::FETCH_ASSOC);
    check_lc('LC06','overdue released hold has original due plus bounded release obligation',$ob['original_due_at']===$original && $ob['purge_due_at']===add(now($db),'+24 hours'));
    check_lc('LC07','same release receipt no extra effects',action_lc($db,$h,'release_hold',['hold'=>$active['hold']],'privacy',$releaseKey)===$released);
    fails_lc('LC07','changed intent cannot reuse receipt key',fn()=>action_lc($db,$h,'release_hold',['hold'=>$overlap['hold']],'privacy',$releaseKey),'IDEMPOTENCY_CONFLICT');
    $first=purge_lc($db,$hk); $second=purge_lc($db,$hk);
    check_lc('LC07','purge once, retry reports already completed',$first['count']===1 && $second['status']==='already_completed' && (int)query($db,'SELECT COUNT(*) FROM evidence_recovery_outbox WHERE event_id=?',[$first['obligation']])->fetchColumn()===1);

    // Natural expiry and late release must use actual end, not later read/review.
    $ex=resolved_lc($db,$root,'expiry'); $exkey=item($db,$ex); clock($root,add($ex['clock']['content_expires_at'],'-1 hour')); $eh=hold_lc($db,$ex,$exkey,2);
    advance($root,'+4 hours');
    transaction($db,fn()=>\Bushisa\Evidence\refresh_obligation($db,state_lc($db,$exkey)));
    check_lc('LC06','natural expiry creates obligation without release click',query($db,'SELECT purge_due_at FROM evidence_purge_obligations WHERE item_key=?',[$exkey])->fetchColumn()===add($ex['clock']['content_expires_at'],'+25 hours'));
    action_lc($db,$ex,'request_release',['hold'=>$eh['hold']],'lead'); action_lc($db,$ex,'release_hold',['hold'=>$eh['hold']],'privacy');
    $expected=add($ex['clock']['content_expires_at'],'+25 hours');
    check_lc('LC06','late release does not move actual hold-expiry deadline',query($db,'SELECT purge_due_at FROM evidence_purge_obligations WHERE item_key=?',[$exkey])->fetchColumn()===$expected);

    // Atomic failure points are injected faults, never engine deadlock claims.
    $ff=resolved_lc($db,$root,'faults'); $fk=item($db,$ff); clock($root,$ff['clock']['content_expires_at']);
    foreach(['before_text','after_text','after_obligation','before_commit'] as $point) {
        putenv('BUSHISA_L03A_FAULT='.$point); fails_lc('LC11','rollback at '.$point,fn()=>purge_lc($db,$fk),'SYNTHETIC_FAILURE_'.$point); putenv('BUSHISA_L03A_FAULT');
        check_lc('LC11',$point.' leaves all text/receipt/outbox atomic',body_lc($db,$ff)!==null && state_lc($db,$fk)['purged_at']===null && (int)query($db,'SELECT COUNT(*) FROM evidence_recovery_outbox WHERE event_id=?',[identity('purge-v1',$fk,'1')])->fetchColumn()===0);
    }
    $job=$runDir.'/job-interrupted.json'; file_put_contents($job,json_encode(['action'=>'purge','item'=>$fk]));
    $lost=cli_lc([$rootPath.'/tests/support/l03a_worker.php','--job='.$job],$runtimeEnv+['BUSHISA_L03A_FAULT'=>'after_commit']);
    check_lc('LC11','lost commit result has durable receipt',$lost['code']===1 && body_lc($db,$ff)===null && purge_lc($db,$fk)['status']==='already_completed');
    // Genuine engine 1205, not an injected exception/deadlock. One owner holds
    // the item row while a distinct restricted child exhausts three attempts.
    $timed=resolved_lc($db,$root,'engineTimeout'); $tk=item($db,$timed); clock($root,$timed['clock']['content_expires_at']);
    $file=$runDir.'/job-timeout.json'; $readyFile=$runDir.'/ready-timeout.json'; file_put_contents($file,json_encode(['action'=>'purge','item'=>$tk]));
    $root->beginTransaction(); query($root,'SELECT item_key FROM evidence_lifecycle_items WHERE item_key=? FOR UPDATE',[$tk])->fetchColumn();
    $child=child_lc([$rootPath.'/tests/support/l03a_worker.php','--job='.$file,'--ready='.$readyFile],$runtimeEnv+['BUSHISA_L03A_LOCK_TIMEOUT'=>'1']);
    try {
        until_lc(fn()=>is_file($readyFile)); $participant=json_decode(file_get_contents($readyFile),true,16,JSON_THROW_ON_ERROR); $rowWaits=[];
        until_lc(function() use($root,$participant,&$rowWaits):bool {
            $rowWaits=query($root,'SELECT r.PROCESSLIST_ID AS requesting_connection,b.PROCESSLIST_ID AS blocking_connection FROM performance_schema.data_lock_waits w
                JOIN performance_schema.threads r ON r.THREAD_ID=w.REQUESTING_THREAD_ID JOIN performance_schema.threads b ON b.THREAD_ID=w.BLOCKING_THREAD_ID WHERE r.PROCESSLIST_ID=?',[$participant['connection_id']])->fetchAll(PDO::FETCH_ASSOC);
            return $rowWaits!==[];
        },800);
        $timeout=finish_lc($child); $engine=json_decode($timeout['stderr'],true,16,JSON_THROW_ON_ERROR);
    } finally { $root->rollBack(); }
    check_lc('LC11','engine1205 retries bounded at three and rolls back',$timeout['code']===1 && $engine['engine_errno']===1205 && $engine['transaction_attempts']===3 && body_lc($db,$timed)!==null);
    $races[]=['mode'=>'engine_1205_not_deadlock','worker'=>$participant,'observed_row_waits'=>$rowWaits,'result'=>$engine];
    check_lc('LC11','retry after real lock release commits once',purge_lc($db,$tk)['count']===1 && purge_lc($db,$tk)['status']==='already_completed');

    // Observe real queued SQL connections; rejected post-expiry hold and purge.
    $race=resolved_lc($db,$root,'races'); $rk=item($db,$race); $rdue=$race['clock']['content_expires_at']; clock($root,add($rdue,'-1 hour'));
    $lock='bushisa-report:'.substr(hash('sha256',$settings['name']),0,32);
    $holdJob=['action'=>'urgent_hold','case'=>$race['case'],'session'=>$race['auth']['reviewer'],'key'=>bin2hex(random_bytes(16)),'args'=>['item'=>$rk,'revision'=>1,'hours'=>24,'reason'=>'CASE_CONTEXT']];
    $answers=race_lc($root,[['action'=>'purge','item'=>$rk],$holdJob],$runtimeEnv,fn()=>clock($root,$rdue));
    check_lc('LC09','expired hold/purge overlap exactly one purge',array_sum(array_map(fn($a)=>(int)($a['json']['result']['count']??0),$answers))===1 && $answers[1]['code']===1);
    fails_lc('LC09','purge-first hold cannot resurrect text',fn()=>hold_lc($db,$race,$rk),'ITEM_UNAVAILABLE');
    $race2=resolved_lc($db,$root,'racesheld'); $r2k=item($db,$race2); clock($root,add($race2['clock']['content_expires_at'],'-1 hour')); hold_lc($db,$race2,$r2k);
    $jointRace=action_lc($db,$race2,'propose_hold',['item'=>$r2k,'revision'=>1,'hours'=>24,'reason'=>'SAFETY_CASE'],'lead');
    clock($root,$race2['clock']['content_expires_at']);
    $answers=race_lc($root,[['action'=>'purge','item'=>$r2k],['action'=>'approve_hold','case'=>$race2['case'],'session'=>$race2['auth']['privacy'],'key'=>bin2hex(random_bytes(16)),
        'args'=>['hold'=>$jointRace['hold']]]],$runtimeEnv);
    check_lc('LC09','hold-first overlapping extension/purge preserves exact copy',body_lc($db,$race2)!==null && array_sum(array_map(fn($a)=>(int)($a['json']['result']['count']??0),$answers))===0 && $answers[0]['code']===0 && $answers[1]['code']===0);

    // Current authority is rechecked after acquiring shared serialization.
    ready_lc($db);
    $reviewJob=['action'=>'held_view','case'=>$race2['case'],'session'=>$race2['auth']['reviewer'],'item'=>$r2k];
    $answers=race_lc($root,[$reviewJob,['action'=>'purge','item'=>$r2k]],$runtimeEnv,function()use($root,$race2):void { query($root,'UPDATE users SET session_version=session_version+1,account_status="deleted",is_active=0 WHERE id=?',[$race2['reviewer']]); });
    check_lc('LC10','revocation/deletion wins queued held read',$answers[0]['code']===1 && str_contains($answers[0]['stderr'],'CURRENT_AUTHORITY_REQUIRED') && body_lc($db,$race2)!==null);
    check_lc('LC10','purge never restores revoked account version',(int)query($db,'SELECT session_version FROM users WHERE id=?',[$race2['reviewer']])->fetchColumn()===4);

    $retryCase=resolved_lc($db,$root,'retryRace'); $retryKey=item($db,$retryCase); clock($root,add($retryCase['clock']['content_expires_at'],'-1 hour'));
    $retryHold=hold_lc($db,$retryCase,$retryKey); action_lc($db,$retryCase,'request_release',['hold'=>$retryHold['hold']],'lead');
    clock($root,$retryCase['clock']['content_expires_at']);
    $releaseJob=['action'=>'release_hold','case'=>$retryCase['case'],'session'=>$retryCase['auth']['privacy'],'key'=>bin2hex(random_bytes(16)),'args'=>['hold'=>$retryHold['hold']]];
    $answers=race_lc($root,[$releaseJob,$releaseJob],$runtimeEnv);
    check_lc('LC07','concurrent identical release returns one receipt',$answers[0]['code']===0 && $answers[1]['code']===0 && $answers[0]['json']===$answers[1]['json']);
    $answers=race_lc($root,[['action'=>'purge','item'=>$retryKey],['action'=>'purge','item'=>$retryKey]],$runtimeEnv);
    check_lc('LC07','concurrent purge retries produce exactly one effect',array_sum(array_map(fn($r)=>(int)($r['json']['result']['count']??0),$answers))===1);

    // Real HTTP boundary with restricted SQL identity; no browser or real mail.
    $socket=@fsockopen('127.0.0.1',8137,$errno,$message,0.1); if($socket) { fclose($socket); throw new RuntimeException('Refusing occupied L03a HTTP port'); }
    $pipes=[]; $server=proc_open([PHP_BINARY,'-d','session.save_path='.$runDir.'/sessions','-S','127.0.0.1:8137','-t',$rootPath],
        [0=>['pipe','r'],1=>['file',$runDir.'/http.log','a'],2=>['file',$runDir.'/http.log','a']],$pipes,$rootPath,array_merge(getenv(),$runtimeEnv)); fclose($pipes[0]); $servers[]=$server;
    until_lc(function():bool { $s=@fsockopen('127.0.0.1',8137,$e,$m,0.05); if($s)fclose($s); return (bool)$s; });
    $web=scenario($root,'http'); $auth=session_lc($web['auth']['reviewer']); $payload=['action'=>'notice','case'=>$web['case'],'key'=>bin2hex(random_bytes(16)),'args'=>['classification'=>'ordinary']];
    $reply=http_lc($auth,$payload); check_lc('LC04','actual HTTP trusted fixture notice accepted',$reply['code']===200);
    $payload['args']['mfa_verified']=true; check_lc('LC04','actual HTTP rejects submitted authority fields',http_lc($auth,$payload)['code']===422);
    $fake=session_lc(['user_id'=>$web['reviewer'],'session_version'=>3,'mfa_verified'=>true,'role'=>'admin']);
    check_lc('LC04','actual HTTP MFA boolean alone fails',http_lc($fake,$payload)['code']===403);

    // Newer/private unrelated state, including import tables, is invariant.
    $safe=resolved_lc($db,$root,'safe'); $safeKey=item($db,$safe);
    query($root,'INSERT INTO pair_request_counts (requester_id,recipient_id,request_count) VALUES (?,?,3)',[$safe['a'],$safe['b']]);
    query($root,'INSERT INTO blocks (blocker_id,blocked_id) VALUES (?,?)',[$safe['a'],$safe['b']]); $block=(int)$root->lastInsertId();
    query($root,'INSERT INTO report_block_links (block_id,case_id,submission_id,attached_at) VALUES (?,?,?,?)',[$block,$safe['case'],$safe['submission'],now($db)]);
    query($root,'INSERT INTO report_probation_periods (actor_id,trigger_submission_id,starts_at,expires_at) VALUES (?,?,?,?)',[$safe['b'],$safe['submission'],now($db),add(now($db),'+168 hours')]);
    query($root,'INSERT INTO pair_policy_history (user_low,user_high,match_id,history_state,version,generation,ending_count,last_ended_by,last_ended_at,created_at) VALUES (?,?,?,"legacy_incomplete",7,2,1,?,?,?)',
        [$safe['a'],$safe['b'],$safe['match'],$safe['a'],add(now($db),'-13 hours'),now($db)]);
    $oldSegment=identity('old-segment',$safeKey); $newSegment=identity('new-segment',$safeKey);
    query($root,'INSERT INTO pair_chat_segments (segment_key,match_id,opened_at,grant_source,ended_at,ended_by,read_until) VALUES (?,?,?,"synthetic_fixture",?,?,?)',
        [$oldSegment,$safe['match'],add(now($db),'-30 days'),add(now($db),'-13 hours'),$safe['a'],add(now($db),'+155 hours')]);
    query($root,'INSERT INTO pair_chat_segments (segment_key,match_id,opened_at,grant_source) VALUES (?,?,?,"synthetic_fixture")',[$newSegment,$safe['match'],now($db)]);
    query($root,'INSERT INTO messages (match_id,sender_id,body,report_segment_key) VALUES (?, ?,"Synthetic new restarted-session text",?)',[$safe['match'],$safe['b'],$newSegment]);
    query($root,'INSERT INTO synthetic_import_facts (fact_id,incarnation,pair_ref,identity_json,payload_digest,event_json,created_at,expires_at) VALUES (?,"syn:l03a","syn:pair-safe","{}",?,"{}",?,?)',
        [identity('private-fact',$safeKey),identity('payload'),now($db),add(now($db),'+365 days')]);
    query($root,'INSERT INTO synthetic_import_receipts (receipt_ref,incarnation,pair_ref,bundle_digest,intent_digest,before_digest,after_digest,result_json,result_digest,committed_at,expires_at) VALUES (?,"syn:l03a","syn:pair-safe",?,?,?,?,"{}",?,?,?)',
        [identity('private-receipt',$safeKey),identity('bundle'),identity('intent'),identity('before'),identity('after'),identity('result'),now($db),add(now($db),'+365 days')]);
    $protected=['users','matches','messages','blocks','report_block_links','report_probation_periods','pair_request_counts','pair_policy_history','pair_chat_segments','synthetic_import_facts','synthetic_import_receipts'];
    $before=[]; foreach($protected as $t) { $before[$t]=query($root,'SELECT * FROM '.$t)->fetchAll(PDO::FETCH_ASSOC); }
    clock($root,$safe['clock']['content_expires_at']); purge_lc($db,$safeKey);
    foreach($protected as $t) { check_lc('LC14',$t.' unchanged by text purge',$before[$t]===query($root,'SELECT * FROM '.$t)->fetchAll(PDO::FETCH_ASSOC)); }
    // Out-of-band replacement is not permission to delete the replacement.
    $newKey=item($db,$safe,1); query($root,'UPDATE moderation_evidence SET evidence_excerpt="Synthetic newer copy" WHERE id=?',[$safe['evidence'][1]]);
    check_lc('LC14','newer source mismatch is explicit and not purged',purge_lc($db,$newKey)['status']==='source_changed' && body_lc($db,$safe,1)==='Synthetic newer copy');

    // Immutable clocks across reads/reviews/migration/retries; second case's hold
    // cannot authorize erasure exemption for another case.
    $immutable=query($db,'SELECT ordinary_expires_at FROM evidence_lifecycle_items WHERE item_key=?',[$r2k])->fetchColumn();
    cli_lc([$rootPath.'/bin/migrate.php']); purge_lc($db,$r2k); purge_lc($db,$r2k);
    check_lc('LC12','migration/read/retry never rewrites immutable ordinary clock',query($db,'SELECT ordinary_expires_at FROM evidence_lifecycle_items WHERE item_key=?',[$r2k])->fetchColumn()===$immutable);
    $addendum=resolved_lc($db,$root,'addendum'); $oldExpiry=$addendum['clock']['content_expires_at']; advance($root,'+1 day');
    transaction($root,function()use($root,$addendum):void {
        bushisa_report_capture($root,$addendum['case'],['body'=>'Synthetic later supporting context','type'=>'message','id'=>$addendum['message'],'author'=>$addendum['a'],'version'=>identity('later-context')]);
        register_case($root,$addendum['case']);
    });
    check_lc('LC12','actual capture writer new context retains case original expiry',(int)query($db,'SELECT COUNT(*) FROM evidence_lifecycle_items WHERE case_id=? AND ordinary_expires_at=?',[$addendum['case'],$oldExpiry])->fetchColumn()===4);

    // Restricted permissions: no authority minting, DDL, or half-purge on a
    // missing outbox INSERT. Fixture privileges are never given to workers.
    foreach(['UPDATE l03a_fixture_clock SET clock_at=clock_at','UPDATE l03a_fixture_authority SET revoked_at=NULL','CREATE TABLE l03a_forbidden (id INT)'] as $sql) {
        try { $db->exec($sql); $refused=false; } catch(PDOException) { $refused=true; } check_lc('LC18','restricted worker denied fixture/DDL: '.strtok($sql,' '),$refused);
    }
    $priv=resolved_lc($db,$root,'priv'); $privKey=item($db,$priv); clock($root,$priv['clock']['content_expires_at']);
    $schema=bushisa_quote_disposable_identifier($settings['name']); $root->exec('REVOKE INSERT ON '.$schema.'.evidence_recovery_outbox FROM '.$runtime['account']);
    try { purge_lc($db,$privKey); $denied=false; } catch(PDOException) { $denied=true; }
    check_lc('LC18','missing publication grant rolls back text/receipt atomically',$denied && body_lc($db,$priv)!==null && state_lc($db,$privKey)['purged_at']===null);
    $root->exec('GRANT INSERT ON '.$schema.'.evidence_recovery_outbox TO '.$runtime['account']);

    // Partial batch errors stay failed; cursor progress prevents starvation.
    putenv('BUSHISA_L03A_FAULT=after_text'); $failed=cleanup($db,100); putenv('BUSHISA_L03A_FAULT');
    check_lc('LC13','failed batch has failures and no false completion',$failed['failures']>0 && body_lc($db,$priv)!==null);
    $batchJobs=[['action'=>'cleanup','limit'=>100],['action'=>'cleanup','limit'=>100]];
    $answers=race_lc($root,$batchJobs,$runtimeEnv);
    check_lc('LC13','concurrent bounded cleanup/resume succeeds',count(array_filter($answers,fn($r)=>$r['code']===0))===2 && body_lc($db,$priv)===null);
    check_lc('LC13','completed item has one receipt/event',(int)query($db,'SELECT COUNT(*) FROM evidence_lifecycle_events WHERE event_id=?',[identity('purge-v1',$privKey,'1')])->fetchColumn()===1);
    check_lc('LC13','missing anchor and overdue alerts contain no body',(int)query($db,'SELECT COUNT(*) FROM evidence_lifecycle_alerts WHERE reason_code IN ("MISSING_FINAL_RESOLUTION","PURGE_DEADLINE")')->fetchColumn()>0);

    // Synthetic stale SQL copy + external-to-restore fixture journal. Save
    // before purge, publish later, restore that old item, replay latest deletion.
    $rest=resolved_lc($db,$root,'restore'); $restKey=item($db,$rest); $oldItem=state_lc($db,$restKey);
    $oldCopy=query($root,'SELECT * FROM moderation_evidence WHERE id=?',[$rest['evidence'][0]])->fetch(PDO::FETCH_ASSOC);
    clock($root,$rest['clock']['content_expires_at']); purge_lc($db,$restKey);
    putenv('BUSHISA_L03A_FAULT=after_journal_ack'); fails_lc('LC15','lost publication ack is not local durability',fn()=>publish($db),'SYNTHETIC_FAILURE_after_journal_ack'); putenv('BUSHISA_L03A_FAULT');
    publish($db); $head=\Bushisa\Evidence\synthetic_journal_head();
    query($root,'UPDATE moderation_evidence SET evidence_excerpt=?,evidence_hash=?,purged_at=NULL WHERE id=?',[$oldCopy['evidence_excerpt'],$oldCopy['evidence_hash'],$rest['evidence'][0]]);
    query($root,'UPDATE evidence_lifecycle_items SET source_hash=?,state="pending",purged_at=NULL WHERE item_key=?',[$oldItem['source_hash'],$restKey]);
    query($root,'UPDATE evidence_recovery_state SET ready=0,applied_watermark=0,head_digest=NULL WHERE singleton=1');
    fails_lc('LC15','restored reads/dispatch interface fail closed before replay',fn()=>recovery_gate($db),'INDEPENDENT_RECOVERY_NOT_READY');
    $replayed=replay($db); recovery_gate($db);
    check_lc('LC15','latest synthetic journal re-erases stale text without grants',$replayed['status']==='SYNTHETIC_INTERFACE_REPLAYED' && body_lc($db,$rest)===null);
    $path=\Bushisa\Evidence\journal_path(); rename($path,$path.'.missing');
    fails_lc('LC15','missing independent source prevents release',fn()=>recovery_gate($db),'JOURNAL_MISSING_OR_OVERSIZED'); rename($path.'.missing',$path);
    $j=file_get_contents($path); $decoded=json_decode($j,true); $decoded['events']=[]; $decoded['watermark']=0; $decoded['digest']=identity('empty-journal-v1'); file_put_contents($path,json_encode($decoded));
    fails_lc('LC15','internally consistent truncated journal is not enough',fn()=>recovery_gate($db),'JOURNAL_CHECKPOINT_REQUIRED'); file_put_contents($path,$j);
    check_lc('LC15','replay retry stable frontier',replay($db)['watermark']===$head['watermark']);
    // Simulate older SQL outbox/frontier as well, then verify the next writer
    // allocates above the separate journal, without runtime ALTER/DDL.
    query($root,'DELETE FROM evidence_recovery_outbox WHERE sequence_id=?',[$head['watermark']]);
    query($root,'UPDATE evidence_recovery_state SET required_watermark=?,applied_watermark=0,ready=0 WHERE singleton=1',[$head['watermark']-1]);
    replay($db);
    $fresh=scenario($root,'postReplay'); $freshKey=item($db,$fresh); hold_lc($db,$fresh,$freshKey);
    check_lc('LC15','post-restore sequence exceeds independent frontier',(int)query($db,'SELECT MAX(sequence_id) FROM evidence_recovery_outbox')->fetchColumn()>$head['watermark']);
    $savedNs=getenv('BUSHISA_L03A_RECOVERY_NAMESPACE'); putenv('BUSHISA_L03A_RECOVERY_NAMESPACE='.str_repeat('f',64));
    fails_lc('LC15','foreign journal namespace refused',fn()=>replay($db),'JOURNAL_NAMESPACE_REQUIRED'); putenv('BUSHISA_L03A_RECOVERY_NAMESPACE='.$savedNs);

    $d='2026-01-01 00:00:00.000000'; $live=add($d,'+7 days'); $copy=add($live,'+35 days');
    check_lc('LC16','conditional 7+35+7 equals49 days',recovery_bound($d,$live,[$copy],true)['retain_until']===add($d,'+49 days'));
    check_lc('LC16','later verified copy extends bound',recovery_bound($d,$live,[add($copy,'+2 days')],true)['retain_until']===add($d,'+51 days'));
    check_lc('LC16','unknown copy blocks release',recovery_bound($d,$live,[null],false)['retain_until']===null);
    check_lc('LC16','active protective state may need longer',recovery_bound($d,$live,[$copy],true,add($d,'+100 days'))['retain_until']===add($d,'+100 days'));

    // Real current-schema ->010 upgrade in a separate disposable database.
    $base=$settings['name']; putenv('DB_NAME='.$base.'_upgrade');
    bushisa_bootstrap_disposable_database($rootPath.'/database/bushisa.sql'); $pre=cli_lc([$rootPath.'/bin/migrate.php','--through=009']);
    $up=connect(); install($up); $old=scenario_upgrade_lc($up); $snapshot=query($up,'SELECT * FROM moderation_cases')->fetchAll(PDO::FETCH_ASSOC);
    $upgrade=cli_lc([$rootPath.'/bin/migrate.php']); $again=cli_lc([$rootPath.'/bin/migrate.php']);
    check_lc('LC17','009 upgrade+repeat preserves unknown resolution rows',$pre['code']===0 && $upgrade['code']===0 && $again['code']===0 && $snapshot===query($up,'SELECT * FROM moderation_cases')->fetchAll(PDO::FETCH_ASSOC));
    transaction($up,fn()=>register_case($up,$old));
    check_lc('LC17','upgrade flags untrusted historical final',query($up,'SELECT unresolved_code FROM evidence_case_clocks WHERE case_id=?',[$old])->fetchColumn()==='UNTRUSTED_HISTORICAL_FINAL' && query($up,'SELECT final_at FROM evidence_case_clocks WHERE case_id=?',[$old])->fetchColumn()===null);
    $migration[]=['kind'=>'009-upgrade-repeat','result'=>0]; putenv('DB_NAME='.$base);
    check_lc('LC18','no mail queued',(int)query($root,'SELECT COUNT(*) FROM mail_outbox')->fetchColumn()===0);
} catch(Throwable $e) { $failure=$e->getMessage(); fwrite(STDERR,'FAIL: '.$failure.PHP_EOL); }
finally {
    foreach($servers as $s) { if(is_resource($s)) { proc_terminate($s); proc_close($s); } }
    $manifest=[];
    foreach(['php/evidence_lifecycle.php','php/evidence_recovery.php','php/report_intake.php','api/evidence_lifecycle.php','bin/evidence_lifecycle_synthetic.php','database/migrations/010_text_evidence_lifecycle.sql','tests/integration_l03a_lifecycle.php','tests/support/l03a_fixture.php','tests/support/l03a_worker.php'] as $file) { $manifest[$file]=hash_file('sha256',$rootPath.'/'.$file); }
    $e=['suite'=>'L03a guarded synthetic lifecycle','result'=>$failure===null?'PASS':'FAIL','started_utc'=>$started,'finished_utc'=>gmdate('c'),'environment'=>$environment,'checks'=>$checks,'races'=>$races,'migrations'=>$migration,
        'failure'=>$failure,'manifest_sha256'=>$manifest,'limitations'=>['Synthetic authority is not operational MFA/staffing/qualified review','Synthetic journal is not independent storage durability or witnessed restore','Legacy cleanup and production scheduling are excluded']];
    file_put_contents($runDir.'/evidence.json',json_encode($e,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
    if(isset($root,$runtime['account'])) { $root->exec('DROP USER IF EXISTS '.$runtime['account']); }
    echo json_encode(['result'=>$e['result'],'checks'=>count($checks),'races'=>count($races),'evidence'=>$runDir.'/evidence.json','failure'=>$failure],JSON_UNESCAPED_SLASHES).PHP_EOL;
}
exit($failure===null?0:1);

function scenario_upgrade_lc(PDO $db): int {
    $a=user($db,'upgradea'); $b=user($db,'upgradeb');
    query($db,'INSERT INTO moderation_cases (reporter_id,target_type,target_id,reported_user_id,reason,status,intake_contract_version,final_resolution_at) VALUES (?,"user",?,?,"Synthetic old final","reviewed",1,"2020-01-01")',[$a,$b,$b]);
    return (int)$db->lastInsertId();
}
