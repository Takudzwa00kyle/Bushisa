<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/disposable_database.php';
require_once __DIR__.'/support/stage_fixture_contract.php';
require_once __DIR__.'/../php/staging_mail.php';
$settings=bushisa_require_disposable_test_database('Staging integration');
if (!in_array($settings['host'],['127.0.0.1','localhost','::1'],true) || !str_starts_with($settings['name'],'bushisa_test_stage_')) throw new RuntimeException('Owned loopback staging test database required');
$original=getenv();$repo=dirname(__DIR__);$checks=[];$m=null;$root=null;$server=null;$proxy=null;$roles=[];$failure=null;$overlap=null;$mailOverlap=null;$engineSetup=null;$started=gmdate('c');
function st_check(string $id,bool $ok): void { global $checks; $checks[]=['case'=>$id,'result'=>$ok?'PASS':'FAIL']; if(!$ok) throw new RuntimeException($id); }
function st_child(array $args,array $env,array $input=[],bool $private=false): array {
    global $repo; $pipes=[]; $spec=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];if($private)$spec[3]=['pipe','w'];
    $p=proc_open($args,$spec,$pipes,$repo,$env);if(!is_resource($p))throw new RuntimeException('Child unavailable');
    fwrite($pipes[0],json_encode($input,JSON_THROW_ON_ERROR));fclose($pipes[0]);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    $frame=$private?stream_get_contents($pipes[3]):null;if($private)fclose($pipes[3]);
    $exit=proc_close($p); $GLOBALS['stageLastReason']=null;
    if($exit!==0 && preg_match('/STAGE_[A-Z_0-9]+/',$err.$out,$reason)) $GLOBALS['stageLastReason']=$reason[0];
    elseif($exit!==0 && preg_match('/SQLSTATE\[[A-Z0-9]+\].*?\b([0-9]{4})\b/s',$err.$out,$reason)) $GLOBALS['stageLastReason']='MYSQL_'.$reason[1];
    elseif($exit!==0 && preg_match('/Uncaught ([A-Za-z_\\\\]+):/',$err.$out,$reason)) $GLOBALS['stageLastReason']='PHP_'.$reason[1];
    return ['exit'=>$exit,'out'=>$out,'err'=>$err,'private'=>$frame===null?null:json_decode($frame,true)];
}
function st_role(array $base,array $m,array $passwords,string $role,string $intent): array {
    return array_replace($base,['DB_USER'=>$m['roles'][$role]['user'],'DB_PASSWORD'=>$passwords[$role],'BUSHISA_STAGE_ROLE'=>$role,'BUSHISA_STAGE_INTENT'=>$intent]);
}
function st_connections(PDO $root,string $user): int {
    $s=$root->prepare('SELECT COALESCE(SUM(TOTAL_CONNECTIONS),0) FROM performance_schema.accounts WHERE USER=?');$s->execute([$user]);return (int)$s->fetchColumn();
}
function st_dispatch(array $env,array $jobs): array {
    $nonce=bin2hex(random_bytes(32));$env['BUSHISA_STAGE_PRIVATE_PIPE']='true';$env['BUSHISA_STAGE_PIPE_NONCE']=$nonce;
    return st_child([PHP_BINARY,'-d','disable_functions=fsockopen,pfsockopen,stream_socket_client','bin/dispatch_synthetic_stage_mail.php'],$env,['jobs'=>$jobs,'nonce'=>$nonce],true);
}
function st_token(PDO $pdo,int $id,array $runtime,array $worker): string {
    stage_set_environment($runtime);$_SERVER['REMOTE_ADDR']='stage-fixture-'.bin2hex(random_bytes(8));
    $u=$pdo->query('SELECT email FROM users WHERE id='.$id)->fetchColumn();bushisa_reset_request($pdo,$u);
    $s=$pdo->prepare('SELECT mail_id FROM password_reset_mail_jobs WHERE account_id=? AND purpose="password_reset" AND state="ready" ORDER BY queued_at DESC LIMIT 1');$s->execute([$id]);$job=$s->fetchColumn();
    $r=st_dispatch($worker,[['id'=>$job,'purpose'=>'password_reset']]);
    if($r['exit']!==0 || !isset($r['private']['deliveries'][0]['token']))throw new RuntimeException('Private fixture dispatch');
    return $r['private']['deliveries'][0]['token'];
}
try {
    // Destructive setup stays in the existing explicitly guarded local bootstrap.
    $r=st_child([PHP_BINARY,'tests/bootstrap_database.php'],$original);st_check('SG-I01',$r['exit']===0);
    $root=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$settings['host'],$settings['port'],$settings['name']),$settings['user'],$settings['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $root->exec("SET time_zone='+00:00'");$uuid=$root->query('SELECT @@server_uuid')->fetchColumn();
    $originalTrust=(int)$root->query('SELECT @@GLOBAL.log_bin_trust_function_creators')->fetchColumn();
    $engineSetup=['original_log_bin_trust_function_creators'=>$originalTrust,'fixture_only_override'=>false];
    if($originalTrust!==1) {
        if(getenv('BUSHISA_STAGE_TEST_ENGINE_SETUP')!=='true')throw new RuntimeException('Explicit disposable-engine fixture setup required');
        $root->exec('SET GLOBAL log_bin_trust_function_creators=1');$engineSetup['fixture_only_override']=true;
    }
    $m=stage_fixture_manifest($settings['name'],(string)$settings['port'],$uuid);
    $m['recipients'][]='n02500003c@students.example.invalid';
    $passwords=[];$db=bushisa_quote_disposable_identifier($settings['name']);
    foreach($m['roles'] as $role=>$identity) {
        $passwords[$role]=bin2hex(random_bytes(24));$account="'".$identity['user']."'@'%'";$roles[]=$account;
        $root->exec('CREATE USER '.$account.' IDENTIFIED BY '.$root->quote($passwords[$role]));
        if($role==='migration') $root->exec('GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,ALTER,DROP,INDEX,REFERENCES,TRIGGER,CREATE ROUTINE,ALTER ROUTINE,EXECUTE ON '.$db.'.* TO '.$account);
        elseif($role==='fixture') $root->exec('GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,INDEX,REFERENCES,EXECUTE ON '.$db.'.* TO '.$account);
        else $root->exec('GRANT SELECT ON '.$db.'.* TO '.$account);
    }
    $base=stage_fixture_environment($m,'fixture','prepare',$passwords['fixture']);$base['BUSHISA_STAGE_FIXTURE_PASSWORD']='StageFixture2!Private';
    $migration=st_role($base,$m,$passwords,'migration','migrate');
    $r=st_child([PHP_BINARY,'bin/migrate.php'],$migration);st_check('SG-I02',$r['exit']===0);
    $r=st_child([PHP_BINARY,'bin/migrate.php'],$migration);st_check('SG-I03',$r['exit']===0);
    $tables=$root->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
    foreach(['runtime','worker'] as $role) {
        $account="'".$m['roles'][$role]['user']."'@'%'";
        foreach($tables as $table) $root->exec('GRANT INSERT,UPDATE,DELETE ON '.$db.'.`'.str_replace('`','``',$table).'` TO '.$account);
        $root->exec('GRANT EXECUTE ON '.$db.'.* TO '.$account);
    }
    $runtime=st_role($base,$m,$passwords,'runtime','serve');$worker=st_role($base,$m,$passwords,'worker','dispatch');$runner=st_role($base,$m,$passwords,'runner','preflight');
    $r=st_child([PHP_BINARY,'bin/prepare_synthetic_stage.php','--prepare-new-synthetic-fixtures'],$base);st_check('SG-I04',$r['exit']===0);
    $r=st_child([PHP_BINARY,'bin/prepare_synthetic_stage.php','--prepare-new-synthetic-fixtures'],$base);st_check('SG-I05',$r['exit']===0 && str_contains($r['out'],'ALREADY_PREPARED'));
    $users=$root->query('SELECT id,email,signup_attempt_key FROM users ORDER BY id')->fetchAll();$test=$users[2];
    stage_set_environment($runner);$readOnlyProbe=\Bushisa\Staging\connect(\Bushisa\Staging\context('preflight'));unset($readOnlyProbe);
    $connections=st_connections($root,$m['roles']['runner']['user']);
    st_check('SG-I05b',$connections>=1);
    $negative=0;
    foreach ([['APP_ENV'=>'production'],['APP_ENV'=>'staging'],['DB_NAME'=>'bushi-database'],['DB_HOST'=>'bushi-server.mysql.database.azure.com'],['APP_BASE_URL'=>'https://bushisa.me'],['BUSHISA_STAGE_ENABLED'=>'false'],['BUSHISA_STAGE_MANIFEST_SHA256'=>str_repeat('0',64)]] as $override) {
        $r=st_child([PHP_BINARY,'bin/stage_control.php'],array_replace($runner,$override));
        st_check('SG-I06.'.(++$negative),$r['exit']!==0 && str_contains($r['err'],'STAGE_'));
    }
    st_check('SG-I07',(int)$root->query('SELECT @@performance_schema')->fetchColumn()===1 && st_connections($root,$m['roles']['runner']['user'])===$connections);
    stage_set_environment($runtime);$pdo=\Bushisa\Staging\connect(\Bushisa\Staging\context());
    $before=(int)$root->query('SELECT COUNT(*) FROM password_reset_rate_events')->fetchColumn();
    bushisa_reset_request($pdo,'outsider@students.example.invalid');
    st_check('SG-I08',(int)$root->query('SELECT COUNT(*) FROM password_reset_rate_events')->fetchColumn()===$before);
    $_SERVER['REMOTE_ADDR']='stage-first';bushisa_reset_request($pdo,$test['email']);
    $s=$root->prepare('SELECT * FROM password_reset_mail_jobs WHERE account_id=? AND state="ready"');$s->execute([$test['id']]);$mail=$s->fetch();
    st_check('SG-I09',is_array($mail) && str_starts_with($mail['encrypted_payload'],'r1.') && $mail['challenge_id']!==null);
    $r=st_dispatch($worker,[['id'=>$mail['mail_id'],'purpose'=>'password_reset']]);
    $proof=$r['private']['deliveries'][0]['token']??'';
    st_check('SG-I10',$r['exit']===0 && preg_match('/\A[a-f0-9]{64}\z/D',$proof)===1 && !str_contains($r['out'].$r['err'],$proof));
    $s=$root->prepare('SELECT token_hash FROM password_reset_challenges WHERE challenge_id=?');$s->execute([$mail['challenge_id']]);
    $stored=$root->query('SELECT encrypted_payload FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($mail['mail_id']))->fetchColumn();
    st_check('SG-I11',$s->fetchColumn()===hash('sha256',$proof) && $stored===null);
    $r=st_dispatch($worker,[['id'=>$mail['mail_id'],'purpose'=>'password_reset']]);
    st_check('SG-I12',$r['exit']===0 && ($r['private']['deliveries']??null)===[] && json_decode($r['out'],true)['counts']['sent']===0);
    $count=$root->query('SELECT SUM(version) FROM password_reset_mail_jobs')->fetchColumn();
    $r=st_dispatch($worker,[['id'=>$mail['mail_id'],'purpose'=>'password_reset'],['id'=>$mail['mail_id'],'purpose'=>'password_reset']]);
    st_check('SG-I13',$r['exit']!==0 && $count===$root->query('SELECT SUM(version) FROM password_reset_mail_jobs')->fetchColumn());
    $r=st_dispatch($worker,[['id'=>$mail['mail_id'],'purpose'=>'password_changed']]);
    st_check('SG-I14',$r['exit']!==0 && $count===$root->query('SELECT SUM(version) FROM password_reset_mail_jobs')->fetchColumn());
    $immutable=false;
    try { $root->prepare('UPDATE users SET signup_attempt_key=? WHERE id=?')->execute([bin2hex(random_bytes(32)),$test['id']]); }
    catch(PDOException $e) { $immutable=(int)($e->errorInfo[1]??0)===1644; }
    st_check('SG-I15a',$immutable);
    // Perturb the fixture-only copy mapping, never the immutable attempt identity.
    $s=$root->prepare('UPDATE bushisa_stage_accounts SET account_generation=? WHERE account_id=?');$s->execute([bin2hex(random_bytes(32)),$test['id']]);
    st_check('SG-I15',bushisa_reset_consume($pdo,$proof,'RejectedStage2!Password')===false);
    $s->execute([$test['signup_attempt_key'],$test['id']]);
    usleep(1100000);$proof=st_token($pdo,(int)$test['id'],$runtime,$worker);
    // Genuine concurrent lock wait: both workers must be engine-observed waiting.
    $root->beginTransaction();$root->query('SELECT id FROM users WHERE id='.(int)$test['id'].' FOR UPDATE')->fetchColumn();
    $workers=[];$identities=[];
    foreach([1,2] as $n) {
        $barrier=bin2hex(random_bytes(8));$pipes=[];
        $p=proc_open([PHP_BINARY,'tests/support/stage_consume_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,$runtime);
        fwrite($pipes[0],json_encode(['barrier'=>$barrier,'token'=>$proof,'password'=>'ConcurrentStage2!Changed'],JSON_THROW_ON_ERROR));fclose($pipes[0]);
        $workers[]=[$p,$pipes];$file=$m['storage']['root'].'/ready-'.$barrier;$until=microtime(true)+15;
        while(!is_file($file)&&microtime(true)<$until)usleep(10000);
        if(!is_file($file))throw new RuntimeException('Stage worker ready timeout');$identities[]=json_decode(file_get_contents($file),true);
    }
    $until=microtime(true)+15;$waiting=[];
    do {
        $ids=array_column($identities,'connection_id');$processes=$root->query('SHOW FULL PROCESSLIST')->fetchAll();$waiting=[];
        foreach($processes as $p) if(in_array((int)$p['Id'],$ids,true) && str_contains((string)$p['Info'],'FOR UPDATE'))$waiting[]=(int)$p['Id'];
        if(count(array_unique($waiting))===2)break;usleep(20000);
    } while(microtime(true)<$until);
    $root->commit();$wins=0;
    foreach($workers as [$p,$pipes]) {$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0)throw new RuntimeException('Stage consume child failed');$wins+=(int)(json_decode($out,true)['success']??false);}
    $overlap=['workers'=>$identities,'engine_observed_waiters'=>$waiting,'successful_consumptions'=>$wins];
    st_check('SG-I16',count(array_unique(array_column($identities,'pid')))===2 && count(array_unique($waiting))===2 && $wins===1);
    // A matching configured endpoint is not proof of its connected identity.
    $raw=file_get_contents($base['BUSHISA_STAGE_MANIFEST']);$wrong=$m;$wrong['database']['server_uuid']='12345678-1234-1234-1234-123456789abc';
    $wrongRaw=json_encode($wrong,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);file_put_contents($base['BUSHISA_STAGE_MANIFEST'],$wrongRaw);
    $beforeUsers=(int)$root->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $badEnv=array_replace($base,['BUSHISA_STAGE_MANIFEST_SHA256'=>hash('sha256',$wrongRaw)]);
    $r=st_child([PHP_BINARY,'bin/prepare_synthetic_stage.php','--prepare-new-synthetic-fixtures'],$badEnv);
    file_put_contents($base['BUSHISA_STAGE_MANIFEST'],$raw);
    st_check('SG-I19',$r['exit']!==0 && str_contains($r['err'],'STAGE_CONNECTED_IDENTITY') && (int)$root->query('SELECT COUNT(*) FROM users')->fetchColumn()===$beforeUsers);
    $runtimeAccount="'".$m['roles']['runtime']['user']."'@'%'";
    $root->exec('GRANT CREATE ON '.$db.'.* TO '.$runtimeAccount);
    $rejected=false;
    try { \Bushisa\Staging\database($pdo,\Bushisa\Staging\context()); } catch(Throwable $e) {$rejected=$e->getMessage()==='STAGE_DDL_GRANTS';}
    $root->exec('REVOKE CREATE ON '.$db.'.* FROM '.$runtimeAccount);
    st_check('SG-I20',$rejected);
    usleep(1100000);$_SERVER['REMOTE_ADDR']='stage-backoff';bushisa_reset_request($pdo,$test['email']);
    $s=$root->prepare('SELECT mail_id FROM password_reset_mail_jobs WHERE account_id=? AND state="ready" AND purpose="password_reset" ORDER BY queued_at DESC LIMIT 1');$s->execute([$test['id']]);$pendingJob=$s->fetchColumn();
    $root->prepare('UPDATE password_reset_mail_jobs SET next_attempt_at=UTC_TIMESTAMP(6)+INTERVAL 30 SECOND WHERE mail_id=?')->execute([$pendingJob]);
    $beforeMail=$root->query('SELECT * FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($pendingJob))->fetch();
    $r=st_dispatch($worker,[['id'=>$pendingJob,'purpose'=>'password_reset']]);
    st_check('SG-I21',$r['exit']===0 && ($r['private']['deliveries']??null)===[] && $beforeMail===$root->query('SELECT * FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($pendingJob))->fetch());
    $root->prepare('UPDATE password_reset_mail_jobs SET next_attempt_at=UTC_TIMESTAMP(6) WHERE mail_id=?')->execute([$pendingJob]);
    $beforeMail=$root->query('SELECT * FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($pendingJob))->fetch();
    $r=st_dispatch($worker,[['id'=>$pendingJob,'purpose'=>'password_reset'],['id'=>str_repeat('f',64),'purpose'=>'password_reset']]);
    st_check('SG-I22',$r['exit']!==0 && $beforeMail===$root->query('SELECT * FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($pendingJob))->fetch());
    $connections=st_connections($root,$m['roles']['worker']['user']);
    $r=st_dispatch(array_replace($worker,['APP_ENV'=>'production']),[['id'=>$pendingJob,'purpose'=>'password_reset']]);
    st_check('SG-I23',$connections>=1 && $r['exit']!==0 && st_connections($root,$m['roles']['worker']['user'])===$connections);
    // Close the private result reader. The fake acceptance must stay committed,
    // but a retry cannot recover/publish the erased original proof.
    $nonce=bin2hex(random_bytes(32));$env=array_replace($worker,['BUSHISA_STAGE_PRIVATE_PIPE'=>'true','BUSHISA_STAGE_PIPE_NONCE'=>$nonce]);$pipes=[];
    $p=proc_open([PHP_BINARY,'bin/dispatch_synthetic_stage_mail.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w'],3=>['pipe','w']],$pipes,$repo,$env);
    fclose($pipes[3]);fwrite($pipes[0],json_encode(['nonce'=>$nonce,'jobs'=>[['id'=>$pendingJob,'purpose'=>'password_reset']]],JSON_THROW_ON_ERROR));fclose($pipes[0]);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$lostExit=proc_close($p);
    $r=st_dispatch($worker,[['id'=>$pendingJob,'purpose'=>'password_reset']]);
    $ended=$root->query('SELECT state,encrypted_payload,attempts FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($pendingJob))->fetch();
    st_check('SG-I24',$lostExit!==0 && $ended['state']==='accepted' && $ended['encrypted_payload']===null && (int)$ended['attempts']===1 && ($r['private']['deliveries']??null)===[]);
    usleep(1100000);$_SERVER['REMOTE_ADDR']='stage-carrier-race';bushisa_reset_request($pdo,$test['email']);
    $s->execute([$test['id']]);$pendingJob=$s->fetchColumn();
    $root->beginTransaction();$root->query('SELECT id FROM users WHERE id='.(int)$test['id'].' FOR UPDATE')->fetchColumn();$dispatchers=[];$pids=[];
    foreach([1,2] as $n) {
        $nonce=bin2hex(random_bytes(32));$env=array_replace($worker,['BUSHISA_STAGE_PRIVATE_PIPE'=>'true','BUSHISA_STAGE_PIPE_NONCE'=>$nonce]);$pipes=[];
        $p=proc_open([PHP_BINARY,'bin/dispatch_synthetic_stage_mail.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w'],3=>['pipe','w']],$pipes,$repo,$env);
        $pids[]=proc_get_status($p)['pid'];fwrite($pipes[0],json_encode(['nonce'=>$nonce,'jobs'=>[['id'=>$pendingJob,'purpose'=>'password_reset']]],JSON_THROW_ON_ERROR));fclose($pipes[0]);$dispatchers[]=[$p,$pipes];
    }
    $until=microtime(true)+15;$waiters=[];
    do {$waiters=[];foreach($root->query('SHOW FULL PROCESSLIST')->fetchAll() as $process)if($process['User']===$m['roles']['worker']['user'] && str_contains((string)$process['Info'],'FOR UPDATE'))$waiters[]=(int)$process['Id'];if(count(array_unique($waiters))===2)break;usleep(20000);}while(microtime(true)<$until);
    $root->commit();$delivered=0;
    foreach($dispatchers as [$p,$pipes]) { $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);$private=stream_get_contents($pipes[3]);foreach([1,2,3] as $fd)fclose($pipes[$fd]);$exit=proc_close($p);if($exit!==0)throw new RuntimeException('Duplicate carrier worker failed');$delivered+=count(json_decode($private,true)['deliveries']??[]); }
    $mailOverlap=['pids'=>$pids,'engine_observed_waiters'=>$waiters,'private_deliveries'=>$delivered];
    $ended=$root->query('SELECT state,attempts FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($pendingJob))->fetch();
    st_check('SG-I25',count(array_unique($pids))===2 && count(array_unique($waiters))===2 && $delivered===1 && $ended['state']==='accepted' && (int)$ended['attempts']===1);
    // Rendered driver on the untouched first active fixture, private TLS ingress.
    if (getenv('STAGE_RUN_BROWSER')==='true') {
        $key=openssl_pkey_new(['private_key_bits'=>2048]);$csr=openssl_csr_new(['commonName'=>parse_url($m['origin'],PHP_URL_HOST)],$key,['digest_alg'=>'sha256']);$cert=openssl_csr_sign($csr,null,$key,1,['digest_alg'=>'sha256']);
        openssl_pkey_export($key,$keyPem);openssl_x509_export($cert,$certPem);$dir=$m['storage']['root'];
        file_put_contents($dir.'/tls.key',$keyPem);chmod($dir.'/tls.key',0600);file_put_contents($dir.'/tls.crt',$certPem);chmod($dir.'/tls.crt',0600);
        $public=openssl_pkey_get_details($key)['key'];$der=base64_decode(preg_replace('/-----[^-]+-----|\s/','',$public));$pin=base64_encode(hash('sha256',$der,true));
        $log=$dir.'/server.log';$spec=[0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']];
        $server=proc_open([PHP_BINARY,'-d','error_log='.$dir.'/php-error.log','-S','127.0.0.1:8197','-t',$repo],$spec,$serverPipes,$repo,$runtime);fclose($serverPipes[0]);
        $proxyEnv=array_replace($runtime,['BUSHISA_STAGE_LOCAL_PROXY'=>'true']);
        $proxy=proc_open(['node','tests/support/stage_tls_proxy.js'],$spec,$proxyPipes,$repo,$proxyEnv);fclose($proxyPipes[0]);usleep(800000);
        $driver=array_replace(st_role($base,$m,$passwords,'runner','execute'),['BUSHISA_STAGE_WORKER_DB_PASSWORD'=>$passwords['worker'],'BUSHISA_STAGE_LOCAL_TLS_SPKI'=>$pin]);
        $r=st_child(['node','bin/managed_stage_driver.js','--execute-local-fixture'],$driver);
        $rendered=json_decode($r['out'],true);
        st_check('SG-I17',$r['exit']===0 && ($rendered['status']??'')==='PASS');
        $data=(string)file_get_contents($log).(is_file($dir.'/php-error.log')?file_get_contents($dir.'/php-error.log'):'');
        st_check('SG-I18',!str_contains($data,$proof));
    } else $rendered=['status'=>'NOT RUN','reason'=>'STAGE_RUN_BROWSER not enabled'];
} catch(Throwable $e) { $failure=$e; }
finally {
    if($root instanceof PDO && $root->inTransaction())$root->rollBack();
    foreach([$proxy,$server] as $process)if(is_resource($process)){proc_terminate($process);proc_close($process);}
    if($root instanceof PDO)foreach($roles as $role)try{$root->exec('DROP USER IF EXISTS '.$role);}catch(Throwable){}
    if($root instanceof PDO && ($engineSetup['fixture_only_override']??false)) {
        $root->exec('SET GLOBAL log_bin_trust_function_creators='.(int)$engineSetup['original_log_bin_trust_function_creators']);
        $engineSetup['restored_log_bin_trust_function_creators']=(int)$root->query('SELECT @@GLOBAL.log_bin_trust_function_creators')->fetchColumn();
        if($engineSetup['restored_log_bin_trust_function_creators']!==$engineSetup['original_log_bin_trust_function_creators'])throw new RuntimeException('Disposable engine fixture restoration failed');
    }
    stage_set_environment($original);
    $report=['suite'=>'guarded staging local implementation','status'=>$failure?'FAIL':'PASS','started_at_utc'=>$started,'finished_at_utc'=>gmdate('c'),'source_sha'=>getenv('STAGE_SOURCE_SHA')?:null,
        'checkout_sha'=>getenv('GITHUB_SHA')?:null,'worktree_state'=>getenv('STAGE_WORKTREE_STATE')?:'CI checkout or unrecorded local state',
        'environment'=>['php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'mysql'=>$root instanceof PDO?$root->getAttribute(PDO::ATTR_SERVER_VERSION):null,'execution'=>'owned loopback disposable DB; synthetic accounts; no SMTP'],
        'checks'=>$checks,'engine_fixture'=>$engineSetup,'overlap'=>$overlap,'mail_overlap'=>$mailOverlap,'driver'=>$rendered??['status'=>'NOT RUN'],'failure_case'=>$failure?($checks[count($checks)-1]['case']??'setup'):null,
        'failure_reason'=>$failure?(['class'=>get_class($failure),'file'=>basename($failure->getFile()),'line'=>$failure->getLine(),'child_code'=>$GLOBALS['stageLastReason']??null,'code'=>$failure instanceof PDOException?(int)($failure->errorInfo[1]??0):(preg_match('/\ASTAGE_[A-Z_]+\z/D',$failure->getMessage())?$failure->getMessage():null)]):null,
        'managed_azure_cases'=>'AZS01-AZS18 NOT RUN','SC26_production_topology'=>'PARTIAL'];
    @mkdir($repo.'/test-results',0770,true);file_put_contents($repo.'/test-results/staging-local.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
}
if($failure){fwrite(STDERR,'Staging integration failed after '.($checks[count($checks)-1]['case']??'setup').'; '.json_encode($report['failure_reason'],JSON_THROW_ON_ERROR).PHP_EOL);exit(1);}
fwrite(STDOUT,json_encode($report,JSON_UNESCAPED_SLASHES).PHP_EOL);
