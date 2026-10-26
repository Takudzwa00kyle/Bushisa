<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../php/staging_mail.php';
try {
    $m=\Bushisa\Staging\context();
    \Bushisa\Staging\demand($m['_role']==='runner','RUNNER');
    $in=\Bushisa\Staging\input(); $pipe=\Bushisa\Staging\private_pipe($in);
    $pdo=\Bushisa\Staging\connect($m); $env=\Bushisa\Staging\database($pdo,$m); \Bushisa\Staging\schema($pdo);
    \Bushisa\Staging\demand(bushisa_reset_throttle_config()!==null,'TEST_THROTTLES');
    $s=$pdo->prepare('SELECT u.id,u.email,u.signup_attempt_key,u.account_status FROM users u JOIN bushisa_stage_accounts a ON a.account_id=u.id WHERE a.run_id=? ORDER BY u.id');
    $s->execute([$m['run_id']]); $accounts=$s->fetchAll();
    \Bushisa\Staging\demand(count($accounts)===count($m['recipients']),'ACCOUNT_INVENTORY');
    foreach ($accounts as $u) \Bushisa\Staging\demand(\Bushisa\Staging\account($pdo,$m,(int)$u['id'],$u['signup_attempt_key'],$u['email']),'ACCOUNT_BINDING');
    $action=$in['action']??'preflight'; $private=[];
    if ($action==='preflight') $private=['accounts'=>$accounts,'origin'=>$m['origin'],'mode'=>$m['mode'],'worker_user'=>$m['roles']['worker']['user']];
    elseif (in_array($action,['jobs','snapshot'],true) && $m['_intent']==='execute') {
        $id=(int)($in['account_id']??0); \Bushisa\Staging\demand(in_array($id,array_map('intval',array_column($accounts,'id')),true),'ACCOUNT_SELECTION');
        if ($action==='jobs') {
            $s=$pdo->prepare('SELECT mail_id id,purpose FROM password_reset_mail_jobs WHERE account_id=? AND state IN ("ready","claimed","inflight") ORDER BY queued_at LIMIT 20'); $s->execute([$id]);
            $private=['jobs'=>$s->fetchAll()];
            $s=$pdo->prepare('SELECT CAST(m.id AS CHAR) id FROM mail_outbox m JOIN student_email_approval_codes c ON c.id=m.approval_code_id WHERE c.user_id=? AND m.provider="fake" AND m.status="pending" ORDER BY m.id LIMIT 20');$s->execute([$id]);
            foreach ($s->fetchAll() as $r) $private['jobs'][]=['id'=>$r['id'],'purpose'=>'signup'];
        } else {
            $s=$pdo->prepare('SELECT password_hash,session_version,account_status FROM users WHERE id=?');$s->execute([$id]);$u=$s->fetch();
            $s=$pdo->prepare('SELECT challenge_id,token_hash,issued_at,expires_at,consumed_at,invalidated_at,version FROM password_reset_challenges WHERE account_id=? ORDER BY issuance_generation');$s->execute([$id]);
            $private=['password_fingerprint'=>hash('sha256',$u['password_hash']),'session_version'=>(int)$u['session_version'],'status'=>$u['account_status'],'proof_fingerprint'=>hash('sha256',json_encode($s->fetchAll(),JSON_THROW_ON_ERROR))];
        }
    } elseif ($action==='diagnostics' && $m['_intent']==='execute' && $m['mode']==='local-fixture') {
        \Bushisa\Staging\demand(is_string($in['proof']??null) && preg_match('/\A[a-f0-9]{64}\z/D',$in['proof'])===1,'DIAGNOSTIC_INPUT');
        $found=false;
        foreach (['server.log','php-error.log'] as $name) {
            $file=$m['storage']['root'].'/'.$name;
            if (!is_file($file)) continue;
            \Bushisa\Staging\demand(!is_link($file) && filesize($file)<=1048576,'DIAGNOSTIC_SCOPE');
            if(str_contains((string)file_get_contents($file),$in['proof'])) $found=true;
        }
        $data=json_encode($pdo->query('SELECT action FROM audit_log')->fetchAll(),JSON_THROW_ON_ERROR);
        $data.=json_encode($pdo->query('SELECT state,purpose,reason_code FROM password_reset_mail_jobs')->fetchAll(),JSON_THROW_ON_ERROR);
        $private=['proof_found'=>$found || str_contains($data,$in['proof'])];
    } else \Bushisa\Staging\deny('ACTION');
    $frame=json_encode($private,JSON_THROW_ON_ERROR); \Bushisa\Staging\demand(fwrite($pipe,$frame)===strlen($frame),'PIPE_RESULT_LOST'); fclose($pipe);
    fwrite(STDOUT,json_encode(['status'=>'PREFLIGHT_READY','environment'=>$env,'reset_issuance_enabled'=>bushisa_reset_enabled(),'managed_acceptance'=>false],JSON_THROW_ON_ERROR).PHP_EOL);
} catch (Throwable $e) {
    $reason=preg_match('/\ASTAGE_[A-Z_]+\z/D',$e->getMessage())?$e->getMessage():'STAGE_DATABASE_OR_SCHEMA';
    fwrite(STDERR,json_encode(['status'=>'BLOCKED','reason'=>$reason],JSON_THROW_ON_ERROR).PHP_EOL); exit(1);
}
