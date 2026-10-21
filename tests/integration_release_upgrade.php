<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/disposable_database.php';
$s=bushisa_require_disposable_test_database('Direct release rehearsal');
if (!in_array($s['host'],['127.0.0.1','localhost','::1'],true) || !str_starts_with($s['name'],'bushisa_test_release_')) throw new RuntimeException('Owned loopback release fixture required');
$repo=dirname(__DIR__);$checks=[];$p=null;$original=null;$failure=null;
$baseline='0bf311fb6d6a298712fd4cd16d0722305516edd4';
$dir=$repo.'/.tmp/release-baseline-'.bin2hex(random_bytes(8));
function rel_check(string $id,bool $ok):void { global $checks; $checks[]=['case'=>$id,'result'=>$ok?'PASS':'FAIL']; if(!$ok)throw new RuntimeException($id); }
function rel_child(array $args,?array $env=null):array {
    global $repo; $pipes=[];$proc=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,$env);
    if(!is_resource($proc))throw new RuntimeException('Child unavailable');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    return ['exit'=>proc_close($proc),'out'=>$out,'err'=>$err];
}
try {
    mkdir($dir,0700,true);
    // Fixed repository commit + fixed file allowlist, never supplied executable input.
    $files=['database/bushisa.sql','bin/migrate.php','config/db.php','config/database_settings.php','php/operational.php'];
    foreach(['001_operational_contract','002_policy_safeguards','003_restore_replay_safeguards','004_smtp_outbox'] as $f)$files[]='database/migrations/'.$f.'.sql';
    $hashes=[];
    foreach($files as $f){$r=rel_child(['git','show',$baseline.':'.$f]);if($r['exit']!==0)throw new RuntimeException('Pinned baseline unavailable');@mkdir(dirname($dir.'/'.$f),0700,true);file_put_contents($dir.'/'.$f,$r['out']);$hashes[$f]=hash('sha256',$r['out']);}
    bushisa_bootstrap_disposable_database($dir.'/database/bushisa.sql');
    $p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$s['host'],$s['port'],$s['name']),$s['user'],$s['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $original=$p->query('SELECT @@GLOBAL.sql_mode AS sql_mode,@@GLOBAL.time_zone AS time_zone')->fetch();
    $mode='NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO';
    $p->exec('SET GLOBAL sql_mode='.$p->quote($mode));$p->exec("SET GLOBAL time_zone='+00:00'");$p->exec('SET SESSION sql_mode='.$p->quote($mode));$p->exec("SET time_zone='+00:00'");
    $r=rel_child([PHP_BINARY,$dir.'/bin/migrate.php']);rel_check('DR01',$r['exit']===0);
    $versions=$p->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(PDO::FETCH_COLUMN);rel_check('DR02',count($versions)===4);
    $profile=$p->query('SELECT VERSION() AS mysql,@@session.sql_mode AS sql_mode,@@session.time_zone AS time_zone,@@session.transaction_isolation AS isolation,@@character_set_server AS charset,@@collation_server AS collation,@@require_secure_transport AS require_secure_transport,@@log_bin AS log_bin,@@log_bin_trust_function_creators AS log_bin_trust_function_creators')->fetch();
    // Synthetic legacy pending/active accounts and approved chat; no genuine anchors.
    $p->exec("INSERT INTO users(nust_student_id,email,password_hash,account_status,is_verified) VALUES ('n02511111a','n02511111a@students.example.invalid','synthetic-not-a-password','pending_email',0),('n02511112a','n02511112a@students.example.invalid','synthetic-not-a-password','active',1)");
    $p->exec('INSERT INTO matches(user_a_id,user_b_id) VALUES(1,2)');
    $p->exec('INSERT INTO message_requests(match_id,requester_id,recipient_id,status,requested_at,responded_at) VALUES(1,1,2,"approved",UTC_TIMESTAMP(),UTC_TIMESTAMP())');
    $p->exec('INSERT INTO messages(match_id,sender_id,body) VALUES(1,2,"synthetic retained chat")');
    $before=$p->query('SELECT id,email,password_hash,account_status,is_verified,session_version FROM users ORDER BY id')->fetchAll();
    $r=rel_child([PHP_BINARY,$repo.'/bin/migrate.php']);
    if($r['exit']!==0 && preg_match('/SQLSTATE\[[A-Z0-9]+\].*?\b([0-9]{4})\b/s',$r['err'],$m))$GLOBALS['releaseEngineError']='MYSQL_'.$m[1];
    rel_check('DR03',$r['exit']===0);
    rel_check('DR04',(int)$p->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn()===16);
    $r=rel_child([PHP_BINARY,$repo.'/bin/migrate.php']);rel_check('DR05',$r['exit']===0 && substr_count($r['out'],'Skipping ')===16);
    rel_check('DR06',$before===$p->query('SELECT id,email,password_hash,account_status,is_verified,session_version FROM users ORDER BY id')->fetchAll());
    rel_check('DR07',$p->query('SELECT body FROM messages WHERE id=1')->fetchColumn()==='synthetic retained chat' && $p->query('SELECT status FROM message_requests WHERE id=1')->fetchColumn()==='approved');
    rel_check('DR08',$p->query('SELECT history_state FROM pair_policy_history WHERE match_id=1')->fetchColumn()==='legacy_incomplete' && $p->query('SELECT activated_at FROM pair_ledger_epoch WHERE singleton=1')->fetchColumn()===null && (int)$p->query('SELECT COUNT(*) FROM pair_account_genesis')->fetchColumn()===0);
    rel_check('DR09',(int)$p->query('SELECT COUNT(*) FROM signup_lifecycle')->fetchColumn()===0 && (int)$p->query('SELECT COUNT(*) FROM signup_attempt_identities')->fetchColumn()===0);
    require_once $repo.'/php/signup_lifecycle.php';require_once $repo.'/php/report_intake.php';require_once $repo.'/php/pair_history.php';
    $legacy=bushisa_signup_check_account($p,1);rel_check('DR10',$legacy['state']==='anchor_unknown' && $p->query('SELECT account_status FROM users WHERE id=1')->fetchColumn()==='pending_email');
    $denied=false;$p->beginTransaction();try{$pair=bushisa_pair_ensure($p,1,2);bushisa_pair_complete($p,$pair);}catch(BushisaReportError){$denied=true;}finally{$p->rollBack();}rel_check('DR11',$denied);
    $prod=array_replace(getenv(),['APP_ENV'=>'production','DB_HOST'=>'invalid.invalid','DB_NAME'=>'bushi-database']);
    $r=rel_child([PHP_BINARY,$repo.'/bin/cleanup_policy.php'],$prod);rel_check('DR12',$r['exit']===78 && str_contains($r['err'],'POLICY_CLEANUP_GATED') && !str_contains($r['err'],'Database'));
    $r=rel_child([PHP_BINARY,$repo.'/bin/cleanup_mail.php'],array_replace($prod,['MAIL_PROVIDER'=>'fake','MAIL_TRANSPORT'=>'fake','MAIL_WORKER_DB_USER'=>'synthetic_worker','MAIL_WORKER_DB_PASSWORD'=>'synthetic-unused']));rel_check('DR13',$r['exit']===1 && str_contains($r['err'],'MAIL_CLEANUP_FAILED'));
    $r=rel_child([PHP_BINARY,$repo.'/bin/cleanup_mail.php'],array_replace(getenv(),['MAIL_PROVIDER'=>'smtp','MAIL_TRANSPORT'=>'smtp']));rel_check('DR14',$r['exit']===0 && json_decode($r['out'],true)['policy_cleanup']==='gated');
    rel_check('DR15',$p->query('SELECT body FROM messages WHERE id=1')->fetchColumn()==='synthetic retained chat' && $before===$p->query('SELECT id,email,password_hash,account_status,is_verified,session_version FROM users ORDER BY id')->fetchAll());
} catch(Throwable $e){$failure=$e instanceof PDOException?'SQL_'.($e->errorInfo[1]??0):$e->getMessage();}
finally {
    if($p && $original){$p->exec('SET GLOBAL sql_mode='.$p->quote($original['sql_mode']));$p->exec('SET GLOBAL time_zone='.$p->quote($original['time_zone']));$restored=$p->query('SELECT @@GLOBAL.sql_mode AS sql_mode,@@GLOBAL.time_zone AS time_zone')->fetch();rel_check('DR16',$original===$restored);}
    $result=['format'=>'bushisa.direct-release-rehearsal.v1','source_sha'=>getenv('RELEASE_SOURCE_SHA')?:null,'checkout_sha'=>trim(rel_child(['git','rev-parse','HEAD'])['out']),'worktree_modified'=>trim(rel_child(['git','status','--porcelain'])['out'])!=='','captured_at'=>gmdate('c'),'php'=>PHP_VERSION,'mysql_profile'=>$profile??null,'baseline_commit'=>$baseline,'baseline_is_observed_production'=>false,'baseline_file_hashes'=>$hashes??[],'cases'=>$checks,'failure'=>$failure,'engine_error'=>$GLOBALS['releaseEngineError']??null,'global_settings_restored'=>isset($restored)&&$restored===$original];
    @mkdir($repo.'/test-results',0700,true);file_put_contents($repo.'/test-results/direct-release.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
}
foreach($checks as $c)echo $c['case'].' '.$c['result']."\n";
if($failure!==null){fwrite(STDERR,'RELEASE_REHEARSAL_FAILED: '.$failure.' '.($GLOBALS['releaseEngineError']??'')."\n");exit(1);}
