<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/q11_fixture.php';
require_once __DIR__.'/../../php/verification_retention.php';
function qm_guard(): void
{ bushisa_q11_synthetic_guard();if (getenv('BUSHISA_Q22_SYNTHETIC')!=='true') { throw new RuntimeException('Q22 synthetic fixture refused'); } }
function qm_runtime(PDO $root,string $database): array
{
    $c=q11_worker_user($root,$database);$db=bushisa_quote_disposable_identifier($database);$a=$c['account'];
    $root->exec("GRANT DELETE ON $db.mail_outbox TO $a");$root->exec("GRANT DELETE ON $db.signup_mail_jobs TO $a");
    $root->exec("GRANT EXECUTE ON PROCEDURE $db.bushisa_verification_schema_health TO $a");return $c;
}
function qm_new(PDO $root,PDO $db,string $tag): array
{
    q11_clock($root,(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn());
    $f=q11_user($root,$tag);$f['code']=bushisa_issue_signup_code($db,$f['id'],$f['email']);
    $c=bushisa_signup_query($root,'SELECT * FROM student_email_approval_codes WHERE user_id=? ORDER BY id DESC LIMIT 1',[$f['id']])->fetch(PDO::FETCH_ASSOC);
    $m=bushisa_signup_query($root,'SELECT * FROM mail_outbox WHERE approval_code_id=? ORDER BY id DESC LIMIT 1',[$c['id']])->fetch(PDO::FETCH_ASSOC);
    $f['cid']=(int)$c['id'];$f['mid']=(int)$m['id'];$f['ck']=$c['retention_key'];$f['mk']=$m['retention_key'];$f['expires']=bushisa_signup_time($c['expires_at']);$f['useful']=bushisa_signup_time($m['expires_at']);return $f;
}
