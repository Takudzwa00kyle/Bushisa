<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/disposable_database.php';
require_once __DIR__.'/../../php/signup_verification.php';

function q11_connect(?array $credentials=null): PDO
{
    bushisa_q11_synthetic_guard();
    $s=bushisa_require_disposable_test_database('Q11 synthetic fixture');
    $p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$s['host'],$s['port'],$s['name']),$credentials['user']??$s['user'],$credentials['password']??$s['password'],
        bushisa_disposable_database_pdo_options($s));
    $p->exec("SET time_zone='+00:00'"); return $p;
}
function q11_install(PDO $root): void
{
    $root->exec('CREATE TABLE q11_fixture_clock (singleton INT PRIMARY KEY,clock_at DATETIME(6) NOT NULL) ENGINE=InnoDB');
    $root->exec('INSERT INTO q11_fixture_clock VALUES (1,UTC_TIMESTAMP(6))');
}
function q11_clock(PDO $root,string $at): void
{ bushisa_signup_query($root,'UPDATE q11_fixture_clock SET clock_at=? WHERE singleton=1',[$at]); }
function q11_cancel_earlier_fixture_jobs(PDO $root,PDO $worker,int $before): void
{
    // Exercise real cancellation instead of rewriting immutable source expiry.
    $rows=bushisa_signup_query($root,'SELECT DISTINCT c.id,c.user_id,c.email FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id WHERE m.id<? AND m.status="pending" ORDER BY c.id',[$before])->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r) { bushisa_terminalize_signup_code($worker,(int)$r['id'],(int)$r['user_id'],$r['email'],false); }
}
function q11_user(PDO $root,string $tag,bool $anchored=true): array
{
    $student='n025'.str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT).chr(random_int(97,122));
    $email=$student.'@students.nust.ac.zw';
    $root->beginTransaction();
    bushisa_signup_query($root,'INSERT INTO users (nust_student_id,email,password_hash,is_verified,is_suspended,is_active,account_status,session_version,created_at)
        VALUES (?,?,?,0,0,1,"pending_email",0,?)',[$student,$email,password_hash('Synthet1c!Fixture',PASSWORD_DEFAULT),bushisa_signup_now($root)]);
    $id=(int)$root->lastInsertId();
    bushisa_signup_query($root,'INSERT INTO profiles (user_id,display_name,anon_handle,gender) VALUES (?,"Q11 Synthetic",?,"male")',[$id,'q11_'.$tag.'_'.bin2hex(random_bytes(4))]);
    if ($anchored) { bushisa_signup_record_creation($root,$id); }
    $root->commit();
    return compact('id','email','student');
}
function q11_worker_user(PDO $root,string $database): array
{
    $user='q11_exec_'.bin2hex(random_bytes(4)); $password=bin2hex(random_bytes(24));
    $host=(string)$root->query("SELECT SUBSTRING_INDEX(USER(),'@',-1)")->fetchColumn();
    if (!preg_match('/\A[a-zA-Z0-9_.:-]{1,255}\z/D',$host)) { throw new RuntimeException('Invalid fixture client host'); }
    $account="'$user'@'$host'"; $db=bushisa_quote_disposable_identifier($database);
    // This DDL grammar does not accept native prepared placeholders.
    $root->exec("CREATE USER $account IDENTIFIED BY ".$root->quote($password));
    $root->exec("GRANT SELECT ON $db.* TO $account");
    foreach(['users','profiles','preferences','student_email_approval_codes','mail_outbox','signup_lifecycle','signup_mail_jobs','signup_expiry_obligations','signup_lifecycle_alerts','cleanup_outbox','audit_log','rate_limits','signup_attempt_identities','signup_attempt_erasure_jobs','verification_retention','verification_cleanup_receipts','verification_recovery_obligations','verification_late_results','verification_cleanup_alerts'] as $table) {
        if (operational_table_exists($root,$table)) { $root->exec("GRANT INSERT,UPDATE ON $db.$table TO $account"); }
    }
    $root->exec("GRANT DELETE ON $db.rate_limits TO $account");
    if (operational_table_exists($root,'rate_limit_attempts')) { $root->exec("GRANT INSERT,UPDATE,DELETE ON $db.rate_limit_attempts TO $account"); }
    foreach(['session_bindings','session_copy_manifests','session_lineage','session_handoffs','session_copy_obligations','session_copy_coverage'] as $table) {
        if (operational_table_exists($root,$table)) { $root->exec("GRANT INSERT,UPDATE ON $db.$table TO $account"); }
    }
    return compact('user','password','account');
}
