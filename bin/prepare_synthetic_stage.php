<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../php/staging_mail.php';
require_once __DIR__.'/../php/password_policy.php';
try {
    $m=\Bushisa\Staging\context('prepare');
    \Bushisa\Staging\demand($argc===2 && $argv[1]==='--prepare-new-synthetic-fixtures','PREPARE_INTENT');
    $password=(string)getenv('BUSHISA_STAGE_FIXTURE_PASSWORD');
    \Bushisa\Staging\demand(validate_account_password($password)===[],'FIXTURE_PASSWORD');
    $pdo=\Bushisa\Staging\connect($m,false); // Actual UUID/principal checked BEFORE DDL or fixtures.
    \Bushisa\Staging\schema($pdo);
    $markerExists=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='bushisa_stage_identity'")->fetchColumn()===1;
    if ($markerExists && (int)$pdo->query('SELECT COUNT(*) FROM bushisa_stage_identity')->fetchColumn()>0) {
        \Bushisa\Staging\database($pdo,$m);
        $rows=$pdo->query('SELECT u.id,u.signup_attempt_key,u.email FROM users u JOIN bushisa_stage_accounts a ON a.account_id=u.id')->fetchAll();
        \Bushisa\Staging\demand(count($rows)===count($m['recipients']),'FIXTURE_CONFLICT');
        foreach ($rows as $row) \Bushisa\Staging\demand(\Bushisa\Staging\account($pdo,$m,(int)$row['id'],$row['signup_attempt_key'],$row['email']),'FIXTURE_CONFLICT');
        fwrite(STDOUT,"{\"status\":\"ALREADY_PREPARED\"}\n"); exit;
    }
    \Bushisa\Staging\demand((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===0,'NONEMPTY_DATABASE');
    // Fixture-only tables, never installed into production by migrations.
    $pdo->exec('CREATE TABLE IF NOT EXISTS bushisa_stage_identity (singleton TINYINT PRIMARY KEY,run_id CHAR(32) NOT NULL,binding_id CHAR(32) NOT NULL,app_resource VARCHAR(512) NOT NULL,db_resource VARCHAR(512) NOT NULL,namespace VARCHAR(64) NOT NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS bushisa_stage_accounts (account_id INT PRIMARY KEY,run_id CHAR(32) NOT NULL,account_generation CHAR(64) NOT NULL,recipient_hash CHAR(64) NOT NULL,UNIQUE KEY(run_id,recipient_hash)) ENGINE=InnoDB');
    $pdo->beginTransaction();
    $s=$pdo->prepare('INSERT INTO bushisa_stage_identity VALUES(1,?,?,?,?,?)');
    $s->execute([$m['run_id'],$m['database']['binding_id'],$m['resources']['app'],$m['resources']['database'],$m['storage']['namespace']]);
    foreach ($m['recipients'] as $email) {
        $student=explode('@',$email)[0];
        $s=$pdo->prepare('INSERT INTO users(nust_student_id,email,password_hash,is_verified,is_suspended,is_active,account_status,session_version,created_at) VALUES(?,?,?,0,0,1,"pending_email",0,UTC_TIMESTAMP())');
        $s->execute([$student,$email,password_hash($password,PASSWORD_DEFAULT)]); $id=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO profiles(user_id,display_name,anon_handle,gender) VALUES(?,"Stage synthetic",?,"male")')->execute([$id,'stage_'.bin2hex(random_bytes(8))]);
        bushisa_signup_record_creation($pdo,$id);
        $s=$pdo->prepare('SELECT signup_attempt_key FROM users WHERE id=?'); $s->execute([$id]); $generation=$s->fetchColumn();
        $pdo->prepare('INSERT INTO bushisa_stage_accounts VALUES(?,?,?,?)')->execute([$id,$m['run_id'],$generation,hash('sha256',$email)]);
    }
    // Account creation anchors exist before their successful approval events.
    foreach ($m['recipients'] as $email) {
        $u=bushisa_reset_account($pdo,$email); $code=bushisa_issue_signup_code($pdo,(int)$u['id'],$email);
        if ($email!==$m['pending_recipient']) {
            \Bushisa\Staging\demand(bushisa_verify_signup_code($pdo,(int)$u['id'],$code)['success']===true,'FIXTURE_VERIFICATION');
            \Bushisa\Staging\demand(bushisa_complete_adult_onboarding($pdo,(int)$u['id'],'2000-01-01')['success']===true,'FIXTURE_ONBOARDING');
        }
    }
    $pdo->commit();
    fwrite(STDOUT,"{\"status\":\"PREPARED_SYNTHETIC_ONLY\"}\n");
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $reason=preg_match('/\ASTAGE_[A-Z_]+\z/D',$e->getMessage())?$e->getMessage():($e instanceof PDOException?'STAGE_SQL_'.(int)($e->errorInfo[1]??0):'STAGE_FIXTURE_FAILURE');
    fwrite(STDERR,json_encode(['status'=>'BLOCKED','reason'=>$reason],JSON_THROW_ON_ERROR).PHP_EOL); exit(1);
}
