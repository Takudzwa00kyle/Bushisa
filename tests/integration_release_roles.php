<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/support/owner_schema_fixture.php';
require_once __DIR__.'/support/release_roles.php';

$settings = bushisa_require_disposable_test_database('Release role checks');
if (!str_starts_with($settings['name'], 'bushisa_test_release_observed_roles_')
    || !in_array($settings['host'], ['127.0.0.1','localhost','::1'], true)
    || getenv('BUSHISA_RELEASE_ROLE_TEST') !== 'true'
    || !preg_match('/\A[0-9a-f-]{36}\z/D', (string)getenv('BUSHISA_ROLE_SERVER_UUID'))) {
    throw new RuntimeException('RELEASE_ROLE_TARGET_REFUSED');
}
$root = null; $original = null; $authorized = false; $checks = []; $failure = null; $roles = [];
$GLOBALS['rr_accounts'] = []; $repo = dirname(__DIR__);
function rr_check(string $id, string $description, bool $ok, array $detail = []): void {
    global $checks;
    $checks[] = ['case'=>$id,'check'=>$description,'result'=>$ok?'PASS':'FAIL'] + $detail;
    if (!$ok) throw new RuntimeException($id);
}
function rr_denied(string $id, PDO $pdo, string $sql, array $codes = [1044,1045,1142,1143,1227,1370]): void {
    $engine = null;
    try { $pdo->exec($sql); } catch (PDOException $e) { $engine = (int)($e->errorInfo[1]??0); }
    rr_check($id, 'Expected engine denial', in_array($engine, $codes, true), ['engine_error'=>$engine]);
}
function rr_child(array $args, ?array $env = null): array {
    global $repo; $pipes = [];
    $p = proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$repo,$env);
    if (!is_resource($p)) throw new RuntimeException('CHILD_UNAVAILABLE');
    fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);return ['exit'=>proc_close($p),'out'=>$out,'err'=>$err];
}
function rr_user(PDO $p, string $tag, bool $anchored = true): array {
    $student = 'n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).'a';
    $email = $student.'@students.example.invalid';
    $p->beginTransaction();
    $p->prepare('INSERT INTO users(nust_student_id,email,password_hash,account_status) VALUES(?,?,?,"pending_email")')->execute([$student,$email,password_hash('Synthetic2!Roles',PASSWORD_DEFAULT)]);
    $id=(int)$p->lastInsertId();
    $p->prepare('INSERT INTO profiles(user_id,display_name,anon_handle,gender,date_of_birth) VALUES(?,"Synthetic role fixture",?,"male","2000-01-01")')->execute([$id,'role_'.$tag.'_'.bin2hex(random_bytes(3))]);
    if ($anchored) bushisa_signup_record_creation($p,$id);
    $p->commit(); return compact('id','email');
}
try {
    $root=obs_connect($settings,null,false);
    // Read-only server identity check precedes all fixture/role/global writes.
    rr_check('RP00.identity','Owned local server UUID',hash_equals((string)getenv('BUSHISA_ROLE_SERVER_UUID'),(string)$root->query('SELECT @@server_uuid')->fetchColumn()));
    $authorized=true;
    foreach ([
        'production'=>['APP_ENV'=>'production'],
        'production_name'=>['DB_NAME'=>'bushi-database'],
        'remote_host'=>['DB_HOST'=>'bushi-server.mysql.database.azure.com'],
        'missing_intent'=>['BUSHISA_RELEASE_ROLE_TEST'=>'false'],
        'missing_identity'=>['BUSHISA_ROLE_SERVER_UUID'=>''],
    ] as $case=>$changes) {
        $negative=rr_child([PHP_BINARY,'-n',__FILE__],array_replace(getenv(),$changes));
        rr_check('RP-G.'.$case,'Unsafe fixture refuses without a loaded PDO driver',$negative['exit']!==0
            && (str_contains($negative['out'].$negative['err'],'requires a disposable database')||str_contains($negative['out'].$negative['err'],'RELEASE_ROLE_TARGET_REFUSED'))
            && !str_contains($negative['out'].$negative['err'],'Class "PDO" not found'));
    }
    $original=$root->query('SELECT @@GLOBAL.sql_mode AS sql_mode,@@GLOBAL.time_zone AS time_zone,@@GLOBAL.log_bin_trust_function_creators AS trust')->fetch();
    $mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO';
    $root->exec('SET GLOBAL sql_mode='.$root->quote($mode));$root->exec("SET GLOBAL time_zone='+00:00'");$root->exec('SET GLOBAL log_bin_trust_function_creators=1');$root->exec('SET SESSION sql_mode='.$root->quote($mode));
    $profile=$root->query('SELECT VERSION() AS mysql,@@session.time_zone AS timezone,@@session.transaction_isolation AS isolation,@@session.sql_mode AS sql_mode,@@collation_server AS collation,@@require_secure_transport AS require_secure_transport,@@log_bin AS log_bin,@@log_bin_trust_function_creators AS trust')->fetch();
    $tls=$root->query("SHOW SESSION STATUS WHERE Variable_name IN ('Ssl_cipher','Ssl_version')")->fetchAll(PDO::FETCH_KEY_PAIR);
    obs_construct($root,$settings,obs_fixture());
    rr_check('RP00.schema','Observed 001–004 reconstructed before upgrade', (int)$root->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn()===4);
    $wrongIdentity=rr_child([PHP_BINARY,__FILE__],array_replace(getenv(),['BUSHISA_ROLE_SERVER_UUID'=>'00000000-0000-0000-0000-000000000000']));
    rr_check('RP-G.identity_mismatch','Wrong actual server identity cannot erase or rewrite a fixture',$wrongIdentity['exit']!==0
        && str_contains($wrongIdentity['out'].$wrongIdentity['err'],'RP00.identity FAIL')
        && (int)$root->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn()===4);
    $roles['migration']=rr_role($root,$settings,'migration');
    $env=array_replace(getenv(),['DB_USER'=>$roles['migration']['user'],'DB_PASSWORD'=>$roles['migration']['password']]);
    $migrate=rr_child([PHP_BINARY,$repo.'/bin/migrate.php','--through=016'],$env);
    rr_check('RP-M01','Pending 005–016 permitted under schema migrator',$migrate['exit']===0);
    $again=rr_child([PHP_BINARY,$repo.'/bin/migrate.php','--through=016'],$env);
    rr_check('RP-M02','Second migration run skips all sixteen',$again['exit']===0&&substr_count($again['out'],'Skipping ')===16);
    $mp=obs_connect($settings,$roles['migration']);
    $root->exec('CREATE TABLE role_ddl_canary(id INT PRIMARY KEY)');
    $root->exec('CREATE TABLE role_other_canary(id INT PRIMARY KEY)');
    $root->exec('CREATE PROCEDURE role_private_canary() SELECT 1 AS fixed_value');
    $root->exec('CREATE DATABASE '.obs_id($settings['name'].'_foreign'));
    $other=obs_id($settings['name'].'_foreign');
    $mp->exec('ALTER TABLE role_ddl_canary ADD value INT NULL');
    rr_check('RP-M03','Migrator ALTER permitted',(int)$root->query('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name="role_ddl_canary" AND column_name="value"')->fetchColumn()===1);
    rr_denied('RP-M04.no_drop',$mp,'DROP TABLE role_ddl_canary');
    rr_denied('RP-M05.no_other_schema',$mp,'CREATE TABLE '.$other.'.forbidden(id INT)');
    rr_denied('RP-M06.no_admin',$mp,'SET GLOBAL max_connections=@@GLOBAL.max_connections');
    rr_denied('RP-M07.no_grant_option',$mp,'GRANT SELECT ON '.obs_id($settings['name']).'.users TO '.$roles['migration']['account']);
    $definers=$root->query('SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema=DATABASE() AND SUBSTRING_INDEX(definer,"@",1)='.$root->quote($roles['migration']['user']))->fetchColumn();
    rr_check('RP-M08.definers','Upgrade triggers retain controlled migrator definer',(int)$definers>0);

    require_once $repo.'/php/signup_verification.php';require_once $repo.'/php/password_reset_mail_jobs.php';
    foreach(['MAIL_PROVIDER'=>'smtp','MAIL_TRANSPORT'=>'fake','MAIL_FROM_ADDRESS'=>'DoNotReply@bushisa.me','MAIL_FROM_NAME'=>'Bushisa','SMTP_HOST'=>'smtp.example.invalid','SMTP_PORT'=>'587','SMTP_USERNAME'=>'synthetic','SMTP_PASSWORD'=>'synthetic-unused','SMTP_ENCRYPTION'=>'tls','MAIL_OUTBOX_KEY'=>base64_encode(random_bytes(32)),'SIGNUP_CODE_HMAC_KEY'=>bin2hex(random_bytes(32)),'PASSWORD_RESET_ENABLED'=>'false','STUDENT_EMAIL_DOMAIN_ALLOWLIST'=>'students.example.invalid'] as $key=>$value) putenv($key.'='.$value);
    $u=rr_user($root,'mail');$unrelated=rr_user($root,'unrelated');$legacy=rr_user($root,'legacy',false);
    bushisa_issue_signup_code($root,$u['id'],$u['email']);
    $mail=(int)$root->query('SELECT MAX(mail_id) FROM signup_mail_jobs')->fetchColumn();
    foreach(['worker','missing','runtime'] as $kind) $roles[$kind]=rr_role($root,$settings,$kind);
    $wp=obs_connect($settings,$roles['worker']);$bp=obs_connect($settings,$roles['missing']);$rp=obs_connect($settings,$roles['runtime']);
    $snapshot=$root->query('SELECT * FROM signup_mail_jobs WHERE mail_id='.$mail)->fetch();
    $denial=null;
    try { bushisa_dispatch_mail($bp,10,static function():void { throw new RuntimeException('CARRIER_MUST_NOT_RUN'); }); }
    catch(PDOException $e) { $denial=(int)($e->errorInfo[1]??0); }
    rr_check('RP-W01.atomic_missing_right','Missing outbox UPDATE rolls claim back',in_array($denial,[1142,1143],true)&&$snapshot===$root->query('SELECT * FROM signup_mail_jobs WHERE mail_id='.$mail)->fetch(),['engine_error'=>$denial]);
    $calls=0;
    $sent=bushisa_dispatch_mail($wp,10,static function()use(&$calls):void { $calls++; });
    $repeat=bushisa_dispatch_mail($wp,10,static function()use(&$calls):void { $calls++; });
    rr_check('RP-W02.delivery','Strict column-scoped worker accepts once, erases payload and safely retries',$calls===1&&$sent['sent']===1&&$repeat['sent']===0&&$root->query('SELECT body FROM mail_outbox WHERE id='.$mail)->fetchColumn()===null);
    rr_denied('RP-W03.no_password_read',$wp,'SELECT password_hash FROM users');
    rr_denied('RP-W04.no_password_write',$wp,'UPDATE users SET password_hash=password_hash WHERE id='.$u['id']);
    rr_denied('RP-W05.no_suspend',$wp,'UPDATE users SET is_suspended=1 WHERE id='.$u['id']);
    rr_denied('RP-W06.no_email_write',$wp,'UPDATE users SET email=email WHERE id='.$u['id']);
    rr_denied('RP-W07.no_generation_write',$wp,'UPDATE users SET signup_attempt_key=REPEAT("e",64) WHERE id='.$u['id']);
    rr_denied('RP-W08.no_account_insert',$wp,'INSERT INTO users(nust_student_id,email,password_hash) VALUES("n02599999z","fixture@example.invalid","unused")');
    rr_denied('RP-W09.no_private_chat',$wp,'SELECT body FROM messages');
    rr_denied('RP-W10.no_evidence',$wp,'SELECT * FROM reports');
    rr_denied('RP-W11.no_ddl',$wp,'ALTER TABLE role_ddl_canary ADD forbidden_worker INT');
    rr_denied('RP-W12.no_drop',$wp,'DROP TABLE role_ddl_canary');
    rr_denied('RP-W13.no_arbitrary_execute',$wp,'CALL role_private_canary()');
    rr_denied('RP-W14.no_other_schema',$wp,'CREATE TABLE '.$other.'.worker_forbidden(id INT)');
    rr_denied('RP-W14.no_dob_write',$wp,'UPDATE profiles SET date_of_birth="2000-01-02" WHERE user_id='.$u['id']);
    rr_denied('RP-W14.no_obligation_edit',$wp,'UPDATE session_copy_obligations SET policy_ref=policy_ref');
    rr_denied('RP-W14.no_genesis_edit',$wp,'UPDATE signup_attempt_identities SET registered_at=registered_at');
    // Denial in a real application transaction must roll its earlier write back.
    $prior=$root->query('SELECT session_version FROM users WHERE id='.$u['id'])->fetchColumn();
    try { bushisa_signup_transaction($wp,function()use($wp,$u):void { $wp->exec('UPDATE users SET session_version=session_version+1 WHERE id='.$u['id']);$wp->exec('UPDATE users SET is_suspended=1 WHERE id='.$u['id']); }); }
    catch(PDOException $e) { $atomic=(int)($e->errorInfo[1]??0); }
    rr_check('RP-W15.atomic_denial','Forbidden mutation rolls back permitted earlier mutation',in_array($atomic??null,[1142,1143],true)&&$prior===$root->query('SELECT session_version FROM users WHERE id='.$u['id'])->fetchColumn());
    // Trusted anchors created by real creation; no timestamp backfill of legacy.
    $due=bushisa_signup_time((string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn(),'-1 second');
    // Fixture clock changes happen only before an immutable lifecycle is made:
    // create a genuinely old synthetic account through the creation transaction.
    $root->beginTransaction();
    $student='n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).'z';
    $root->prepare('INSERT INTO users(nust_student_id,email,password_hash,account_status,created_at) VALUES(?,?,?,"pending_email",?)')->execute([$student,$student.'@students.example.invalid',password_hash('Synthetic2!Roles',PASSWORD_DEFAULT),bushisa_signup_time($due,'-72 hours')]);
    $old=(int)$root->lastInsertId();bushisa_signup_record_creation($root,$old);
    // A wholly synthetic earlier issuance/queue, linked to the genuinely old
    // fixture creation. No immutable clock is edited or real history inferred.
    $queued=bushisa_signup_time($due,'-2 minutes');$codeExpires=bushisa_signup_time($queued,'+'.bushisa_signup_code_ttl_seconds().' seconds');
    $email=$student.'@students.example.invalid';$code=bushisa_generate_signup_code();
    $root->prepare('INSERT INTO student_email_approval_codes(user_id,email,code_hash,created_at,expires_at) VALUES(?,?,?,?,?)')->execute([$old,$email,bushisa_hash_signup_code($old,$email,$code),$queued,$codeExpires]);
    $oldCode=(int)$root->lastInsertId();\Bushisa\VerificationRetention\register_code($root,$oldCode);
    $payload=['recipient'=>$email,'from_address'=>'DoNotReply@bushisa.me','subject'=>'Synthetic expiry envelope','body'=>'Synthetic approval '.$code];
    $root->prepare('INSERT INTO mail_outbox(recipient_email,subject,body,provider,status,created_at,expires_at,code_bearing,approval_code_id,next_attempt_at) VALUES(?,?,?,"smtp","pending",?,?,1,?,?)')->execute([$email,$payload['subject'],bushisa_encrypt_mail_payload($payload,$oldCode,$email),$queued,$codeExpires,$oldCode,$queued]);
    $oldMail=(int)$root->lastInsertId();bushisa_signup_register_mail_job($root,$oldMail,$oldCode);$root->commit();unset($code,$payload);
    $unrelatedBefore=$root->query('SELECT * FROM users WHERE id='.$unrelated['id'])->fetch();
    $out=bushisa_signup_transaction($wp,function()use($wp,$old):array {
        $row=$wp->query('SELECT id,email,created_at,is_verified,is_suspended,is_active,account_status,deleted_at,session_version,signup_attempt_key FROM users WHERE id='.$old.' FOR UPDATE')->fetch();
        return bushisa_signup_expire_locked($wp,$row);
    });
    $obligations=(int)$root->query('SELECT COUNT(*) FROM signup_expiry_obligations WHERE account_id='.$old)->fetchColumn();
    rr_check('RP-W16.expiry','Due anchored attempt revokes access and creates obligations under strict worker',$out['state']==='expired'&&$obligations===1&&$root->query('SELECT account_status FROM users WHERE id='.$old)->fetchColumn()==='deleted'&&$unrelatedBefore===$root->query('SELECT * FROM users WHERE id='.$unrelated['id'])->fetch());
    rr_check('RP-W16.cancellation','Due attempt cancels its linked challenge and ready mail atomically',$root->query('SELECT code_hash FROM student_email_approval_codes WHERE id='.$oldCode)->fetchColumn()===null&&$root->query('SELECT body FROM mail_outbox WHERE id='.$oldMail)->fetchColumn()===null&&$root->query('SELECT state FROM signup_mail_jobs WHERE mail_id='.$oldMail)->fetchColumn()==='cancelled');
    $out=bushisa_signup_transaction($wp,fn()=>bushisa_signup_expire_locked($wp,$wp->query('SELECT id,email,created_at,is_verified,is_suspended,is_active,account_status,deleted_at,session_version,signup_attempt_key FROM users WHERE id='.$old.' FOR UPDATE')->fetch()));
    rr_check('RP-W17.expiry_retry','Expiry retry does not duplicate obligations',(int)$root->query('SELECT COUNT(*) FROM signup_expiry_obligations WHERE account_id='.$old)->fetchColumn()===$obligations);
    $oldAnchor=$root->query('SELECT created_at FROM users WHERE id='.$legacy['id'])->fetchColumn();
    $r=bushisa_signup_check_account($root,$legacy['id']);
    rr_check('RP-L01.legacy_anchor','Missing legacy anchor remains unknown without generation fabrication',$r['state']==='anchor_unknown'&&$root->query('SELECT created_at FROM users WHERE id='.$legacy['id'])->fetchColumn()===$oldAnchor&&$root->query('SELECT signup_attempt_key FROM users WHERE id='.$legacy['id'])->fetchColumn()===null);

    // Purpose-separated fixtures, no issuance enable or real carrier.
    $account=$root->query('SELECT * FROM users WHERE id='.$u['id'])->fetch();
    $now=(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn();$challenge=bin2hex(random_bytes(32));$reset=bin2hex(random_bytes(32));
    $root->prepare('INSERT INTO password_reset_challenges(challenge_id,account_id,account_generation,recipient_hash,issuance_generation,issued_session_version,token_hash,issued_at,expires_at,secret_purge_due_at) VALUES(?,?,?,?,1,?,?,?, ?,?)')->execute([$challenge,$u['id'],$account['signup_attempt_key'],bushisa_reset_hash('recipient',$u['email']),$account['session_version'],hash('sha256',random_bytes(32)),$now,bushisa_reset_time($now,'+15 minutes'),bushisa_reset_time($now,'+1 day')]);
    $root->prepare('INSERT INTO password_reset_mail_jobs(mail_id,challenge_id,account_id,account_generation,purpose,recipient_hash,encrypted_payload,queued_at,useful_until,state,next_attempt_at) VALUES(?,?,?,?,"password_reset",?,"synthetic-unused",?,?,"ready",?)')->execute([$reset,$challenge,$u['id'],$account['signup_attempt_key'],bushisa_reset_hash('recipient',$u['email']),$now,bushisa_reset_time($now,'+5 minutes'),$now]);
    $result=bushisa_reset_dispatch_one($wp,$reset,static function():void { throw new RuntimeException('DISABLED_RESET_CARRIER'); });
    rr_check('RP-W18.reset_off','Disabled reset cancellation can invalidate proof with strict worker',$result==='discarded'&&$root->query('SELECT token_hash FROM password_reset_challenges WHERE challenge_id='.$root->quote($challenge))->fetchColumn()===null&&$root->query('SELECT state FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($reset))->fetchColumn()==='cancelled'&&!bushisa_reset_enabled());
    // Explicit synthetic-only activation for a preconstructed purpose-bound
    // job; these test values do not approve production reset throttles.
    foreach(['PASSWORD_RESET_ENABLED'=>'true','PASSWORD_RESET_RESEND_COOLDOWN_SECONDS'=>'1','PASSWORD_RESET_MAX_REQUESTS_PER_HOUR'=>'30','PASSWORD_RESET_MAX_REQUESTS_PER_DAY'=>'60'] as $key=>$value)putenv($key.'='.$value);
    $root->exec('UPDATE users SET is_verified=1,account_status="active" WHERE id='.$u['id']);
    $proof=bin2hex(random_bytes(32));$activeChallenge=bin2hex(random_bytes(32));$activeMail=bin2hex(random_bytes(32));
    $recipient=bushisa_reset_hash('recipient',$u['email']);
    $root->prepare('INSERT INTO password_reset_state(account_id,account_generation,issuance_generation,updated_at) VALUES(?,?,2,?)')->execute([$u['id'],$account['signup_attempt_key'],$now]);
    $root->prepare('INSERT INTO password_reset_challenges(challenge_id,account_id,account_generation,recipient_hash,issuance_generation,issued_session_version,token_hash,issued_at,expires_at,secret_purge_due_at) VALUES(?,?,?,?,2,?,?,?, ?,?)')->execute([$activeChallenge,$u['id'],$account['signup_attempt_key'],$recipient,$account['session_version'],bushisa_reset_hash('token',$proof),$now,bushisa_reset_time($now,'+15 minutes'),bushisa_reset_time($now,'+1 day')]);
    $activePayload=['recipient'=>$u['email'],'from_address'=>'DoNotReply@bushisa.me','subject'=>'Synthetic reset','body'=>'Synthetic reset proof '.$proof];
    $envelope=bushisa_reset_encrypt($activePayload,$activeMail,'password_reset',$activeChallenge,$account['signup_attempt_key'],$u['email']);
    $root->prepare('INSERT INTO password_reset_mail_jobs(mail_id,challenge_id,account_id,account_generation,purpose,recipient_hash,encrypted_payload,queued_at,useful_until,state,next_attempt_at) VALUES(?,?,?,?,"password_reset",?,?,?,?,"ready",?)')->execute([$activeMail,$activeChallenge,$u['id'],$account['signup_attempt_key'],$recipient,$envelope,$now,bushisa_reset_time($now,'+5 minutes'),$now]);
    $proofCalls=0;$result=bushisa_reset_dispatch_one($wp,$activeMail,static function()use(&$proofCalls):void{$proofCalls++;});
    putenv('PASSWORD_RESET_ENABLED=false');unset($proof,$activePayload,$envelope);
    rr_check('RP-W18.reset_permitted','Guarded synthetic reset-purpose job can validate current state and fake-accept once',$result==='sent'&&$proofCalls===1&&!bushisa_reset_enabled());
    $notice=bin2hex(random_bytes(32));$payload=['recipient'=>$u['email'],'from_address'=>'DoNotReply@bushisa.me','subject'=>'Synthetic password change','body'=>'Synthetic security notice'];
    $encrypted=bushisa_reset_encrypt($payload,$notice,'password_changed','',$account['signup_attempt_key'],$u['email']);
    $root->prepare('INSERT INTO password_reset_mail_jobs(mail_id,account_id,account_generation,purpose,encrypted_payload,queued_at,useful_until,state,next_attempt_at) VALUES(?,?,?,"password_changed",?,?,?,"ready",?)')->execute([$notice,$u['id'],$account['signup_attempt_key'],$encrypted,$now,bushisa_reset_time($now,'+5 minutes'),$now]);
    $noticeCalls=0;$result=bushisa_reset_dispatch_one($wp,$notice,static function()use(&$noticeCalls):void { $noticeCalls++; });
    rr_check('RP-W19.notice','Code-free notice fake-accepted once while issuance remains off',$result==='sent'&&$noticeCalls===1&&$root->query('SELECT encrypted_payload FROM password_reset_mail_jobs WHERE mail_id='.$root->quote($notice))->fetchColumn()===null);
    $cleaned=bushisa_cleanup_expired_signup_codes($wp);$payloads=bushisa_signup_cleanup_mail($wp);
    rr_check('RP-W22.cleanup','Bounded cleanup functions run under strict role with no account reactivation',$cleaned>=0&&$payloads>=0&&$root->query('SELECT account_status FROM users WHERE id='.$old)->fetchColumn()==='deleted');
    // Drain only synthetic work through an injected carrier before exercising
    // production-shaped CLI bindings with socket functions disabled.
    bushisa_dispatch_mail($wp,100,static function():void {});
    $workerEnv=array_replace(getenv(),['DB_USER'=>$roles['runtime']['user'],'DB_PASSWORD'=>$roles['runtime']['password'],'MAIL_WORKER_DB_USER'=>$roles['worker']['user'],'MAIL_WORKER_DB_PASSWORD'=>$roles['worker']['password'],'MAIL_TRANSPORT'=>'smtp']);
    foreach(['cleanup_mail','dispatch_mail'] as $cli) {
        $result=rr_child([PHP_BINARY,'-d','disable_functions=fsockopen,pfsockopen,stream_socket_client',$repo.'/bin/'.$cli.'.php'],$workerEnv);
        $json=json_decode($result['out'],true);
        rr_check('RP-W23.'.$cli,'SMTP-shaped CLI with empty queue and network carrier functions disabled',$result['exit']===0&&is_array($json)&&($cli!=='dispatch_mail'||($json['sent']??-1)===0));
    }

    $rp->prepare('UPDATE profiles SET bio=? WHERE user_id=?')->execute(['Synthetic role bio',$u['id']]);
    $rp->exec('INSERT INTO confessions(author_id,body) VALUES('.$u['id'].',"Synthetic role confession")');
    rr_check('RP-R01.allowed','Runtime profile and confession writes permitted',$root->query('SELECT bio FROM profiles WHERE user_id='.$u['id'])->fetchColumn()==='Synthetic role bio'&&(int)$root->query('SELECT COUNT(*) FROM confessions WHERE author_id='.$u['id'])->fetchColumn()===1);
    $hash=password_hash('Synthetic3!Runtime',PASSWORD_DEFAULT);$rp->prepare('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?')->execute([$hash,$u['id']]);
    rr_check('RP-R02.password_purpose','Runtime password/session write is distinct from worker denial',password_verify('Synthetic3!Runtime',(string)$root->query('SELECT password_hash FROM users WHERE id='.$u['id'])->fetchColumn()));
    foreach(['worker'=>$wp,'runtime'=>$rp] as $kind=>$p) {
        rr_denied('RP-'.($kind==='worker'?'W20':'R03').'.no_admin',$p,'SET GLOBAL max_connections=@@GLOBAL.max_connections');
        rr_denied('RP-'.($kind==='worker'?'W21':'R04').'.no_grants',$p,'GRANT SELECT ON '.obs_id($settings['name']).'.users TO '.$roles[$kind]['account']);
    }
    rr_denied('RP-R05.no_ddl',$rp,'CREATE TABLE forbidden_runtime(id INT)');
    rr_denied('RP-R06.no_drop',$rp,'DROP TABLE role_ddl_canary');
    rr_denied('RP-R07.no_other_schema',$rp,'CREATE TABLE '.$other.'.runtime_forbidden(id INT)');
    rr_denied('RP-R08.no_arbitrary_execute',$rp,'CALL role_private_canary()');
    rr_denied('RP-R09.immutable_generation',$rp,'UPDATE users SET signup_attempt_key=REPEAT("e",64) WHERE id='.$u['id'],[1644]);
    // Issuance/cancellation need outbox UPDATE; physical row deletion is not
    // a runtime operation. Historical RP-R10 remains in its original evidence.
    rr_denied('RP-R10.no_mail_row_delete',$rp,'DELETE FROM mail_outbox WHERE id='.$mail);
    rr_check('RP-L02.epoch','Migration does not activate real cutover or certify genesis',$root->query('SELECT activated_at FROM pair_ledger_epoch WHERE singleton=1')->fetchColumn()===null&&(int)$root->query('SELECT COUNT(*) FROM pair_account_genesis')->fetchColumn()===0);
} catch (Throwable $e) {
    $failure=$e instanceof PDOException?'MYSQL_'.($e->errorInfo[1]??0):$e->getMessage();
    if ($e instanceof PDOException && preg_match("/(SELECT|INSERT|UPDATE|DELETE|EXECUTE) command denied.*?(?:table|routine) '([a-zA-Z0-9_.]+)'/",(string)($e->errorInfo[2]??''),$match)) $failure.=' '.$match[1].' '.$match[2];
    if ($e instanceof PDOException && in_array((int)($e->errorInfo[1]??0),[1142,1143],true)) $failure.=' '.preg_replace('/[\x27`][^\x27`]*[\x27`]/','<identifier>',(string)($e->errorInfo[2]??''));
    $failureTrace=array_map(static fn($frame)=>['file'=>basename($frame['file']??''),'line'=>$frame['line']??null,'function'=>$frame['function']??''],array_slice($e->getTrace(),0,6));
} finally {
    if ($root && $authorized) {
        // The disposable schemas alone can be dropped. Never target another name.
        if ($root->inTransaction()) $root->rollBack();
        $cleanupOk=true;
        try { $root->exec('DROP DATABASE IF EXISTS '.obs_id($settings['name']));$root->exec('DROP DATABASE IF EXISTS '.obs_id($settings['name'].'_foreign')); } catch(Throwable) {$cleanupOk=false;}
        foreach (array_reverse($GLOBALS['rr_accounts']) as $a) { try {$root->exec('DROP USER IF EXISTS '.$a);} catch(Throwable) {$cleanupOk=false;} }
        if ($original) {
            $root->exec('SET GLOBAL sql_mode='.$root->quote($original['sql_mode']));$root->exec('SET GLOBAL time_zone='.$root->quote($original['time_zone']));$root->exec('SET GLOBAL log_bin_trust_function_creators='.(int)$original['trust']);
            $restored=$root->query('SELECT @@GLOBAL.sql_mode AS sql_mode,@@GLOBAL.time_zone AS time_zone,@@GLOBAL.log_bin_trust_function_creators AS trust')->fetch();
            $cleanupOk=$cleanupOk&&$restored===$original;
        }
        $checks[]=['case'=>'RP-X.restore','check'=>'Owned fixture roles/schemas removed and engine globals restored','result'=>$cleanupOk?'PASS':'FAIL'];
        if(!$cleanupOk)$failure??='FIXTURE_CLEANUP_FAILED';
    }
    $grants=[];foreach($roles as $kind=>$role)$grants[$kind]=$role['grants'];
    $report=['format'=>'bushisa.release-role-checks.v1','executed_utc'=>gmdate('c'),'checkout'=>trim(rr_child(['git','rev-parse','HEAD'])['out']),'modified_worktree'=>trim(rr_child(['git','status','--porcelain'])['out'])!=='','php'=>PHP_VERSION,'os'=>PHP_OS_FAMILY,'profile'=>$profile??null,'tls'=>$tls??null,'source_fixture_sha256'=>hash_file('sha256',__DIR__.'/fixtures/owner-observed-schema-20261020.json'),'grants'=>$grants,'cases'=>$checks,'failure'=>$failure,'full_D03_runtime_grants'=>'PARTIAL: bounded write profile; not all routes','production_grants'=>'NOT RUN','recovery_acceptance'=>'NOT RUN','production_changes'=>false];
    @mkdir($repo.'/test-results',0700,true);file_put_contents($repo.'/test-results/release-roles.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
}
foreach($checks as $check)echo $check['case'].' '.$check['result']."\n";
if($failure!==null){fwrite(STDERR,'RELEASE_ROLES_FAILED '.$failure.' '.json_encode($failureTrace??[])."\n");exit(1);}
