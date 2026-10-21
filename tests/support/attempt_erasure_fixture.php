<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/q11_fixture.php';
require_once __DIR__.'/../../php/signup_attempt_erasure.php';
use function Bushisa\AttemptErasure\{q,now};
function qe_guard(): void
{ bushisa_q11_synthetic_guard();if(getenv('BUSHISA_ATTEMPT_ERASURE_SYNTHETIC')!=='true')throw new RuntimeException('Synthetic erasure guard refused'); }
function qe_runtime(PDO $root,string $database): array
{
    $c=q11_worker_user($root,$database);$db=bushisa_quote_disposable_identifier($database);$a=$c['account'];
    foreach(['signup_attempt_erasure_objects','signup_attempt_erasure_receipts','signup_attempt_erasure_recovery'] as $t)$root->exec("GRANT INSERT,UPDATE ON $db.$t TO $a");
    foreach(['profiles','preferences','find_match_answers','notification_preferences','profile_images'] as $t)$root->exec("GRANT DELETE ON $db.$t TO $a");
    $root->exec("GRANT EXECUTE ON PROCEDURE $db.bushisa_attempt_schema_health TO $a");return $c;
}
function qe_clock(PDO $root,?string $at=null): void { q11_clock($root,$at??(string)$root->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn()); }
function qe_new(PDO $root,PDO $db,string $tag,bool $onboarding=false,int $files=0): array
{
    qe_clock($root);$f=q11_user($root,$tag);$f['code']=bushisa_issue_signup_code($db,$f['id'],$f['email']);
    if($onboarding && !bushisa_verify_signup_code($db,$f['id'],$f['code'])['success'])throw new RuntimeException('Synthetic email approval failed');
    q($root,'INSERT INTO preferences (user_id,preferred_gender) VALUES (?,"female")',[$f['id']]);
    q($root,'INSERT INTO find_match_answers (user_id,question_key,answer_value) VALUES (?,"synthetic","synthetic answer")',[$f['id']]);
    q($root,'INSERT INTO notification_preferences (user_id,category) VALUES (?,"synthetic")',[$f['id']]);
    $f['paths']=[];
    for($n=0;$n<$files;$n++){
        $name='photo_'.bin2hex(random_bytes(8)).'.jpg';$path='uploads/'.$f['id'].'/'.$name;$f['paths'][]=$path;
        $dir=getenv('BUSHISA_ATTEMPT_STORAGE_ROOT').'/uploads/'.$f['id'];if(!is_dir($dir))mkdir($dir,0700,true);file_put_contents($dir.'/'.$name,'Synthetic profile bytes '.$tag.' '.$n);
        if($n===0)q($root,'UPDATE profiles SET profile_photo_path=? WHERE user_id=?',[$path,$f['id']]);
        else q($root,'INSERT INTO profile_images (user_id,slot,path,mime_type,byte_size) VALUES (?,"discovery_1",?,"image/jpeg",32)',[$f['id'],$path]);
    }
    $c=q($root,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$f['id']])->fetch(PDO::FETCH_ASSOC);$f['due']=$onboarding?$c['onboarding_expires_at']:$c['unverified_expires_at'];
    $f['key']=q($root,'SELECT signup_attempt_key FROM users WHERE id=?',[$f['id']])->fetchColumn();return $f;
}
function qe_expire(PDO $root,PDO $db,array $f): array
{
    qe_clock($root,$f['due']);$r=bushisa_signup_check_account($db,$f['id']);if($r['state']!=='expired')throw new RuntimeException('Synthetic attempt did not expire');$f['receipt']=$r['receipt'];return $f;
}
