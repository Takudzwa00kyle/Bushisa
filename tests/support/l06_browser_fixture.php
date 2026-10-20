<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/q11_fixture.php';
require_once __DIR__.'/../../php/password_reset_mail_jobs.php';
try {
    bushisa_q11_synthetic_guard();
    if (getenv('BUSHISA_PASSWORD_RESET_SYNTHETIC') !== 'true') throw new RuntimeException('Synthetic reset flag required');
    $input=json_decode((string)stream_get_contents(STDIN),true,16,JSON_THROW_ON_ERROR);
    $pdo=q11_connect(); $action=$input['action']??'';
    $result=[];
    if ($action==='setup') {
        q11_install($pdo);
        $result=['php'=>PHP_VERSION,'mysql'=>$pdo->getAttribute(PDO::ATTR_SERVER_VERSION)];
    } elseif ($action==='create') {
        $u=q11_user($pdo,'l06browser');
        if (!($input['pending']??false)) {
            $code=bushisa_issue_signup_code($pdo,$u['id'],$u['email']);
            if (!bushisa_verify_signup_code($pdo,$u['id'],$code)['success'] || !bushisa_complete_adult_onboarding($pdo,$u['id'],'2000-01-01')['success']) throw new RuntimeException('Fixture activation failed');
        } else { bushisa_issue_signup_code($pdo,$u['id'],$u['email']); }
        $result=$u;
    } elseif ($action==='dispatch') {
        $s=$pdo->prepare('SELECT mail_id FROM password_reset_mail_jobs WHERE account_id=? AND purpose="password_reset" AND state="ready" ORDER BY queued_at DESC LIMIT 1');$s->execute([(int)$input['id']]);$id=$s->fetchColumn();
        if (!$id) throw new RuntimeException('No synthetic reset job');
        $token=null;
        $out=bushisa_reset_dispatch_one($pdo,(string)$id,static function(array $payload)use(&$token):void {
            if (!preg_match('/one-time key on the reset page:\s*([a-f0-9]{64})/i',$payload['body'],$m)) throw new RuntimeException('Synthetic reset payload invalid');
            $token=$m[1];
        });
        if ($out!=='sent' || $token===null) throw new RuntimeException('Synthetic reset dispatch failed');
        $result=['token'=>$token]; // Pipe only. Never persist or log the proof.
    } elseif ($action==='snapshot') {
        $s=$pdo->prepare('SELECT password_hash,session_version,account_status,is_verified FROM users WHERE id=?');$s->execute([(int)$input['id']]);$u=$s->fetch();
        $s=$pdo->prepare('SELECT COUNT(*) FROM password_reset_challenges WHERE account_id=? AND consumed_at IS NOT NULL');$s->execute([(int)$input['id']]);
        $result=['password_fingerprint'=>hash('sha256',$u['password_hash']),'session_version'=>(int)$u['session_version'],'status'=>$u['account_status'],'verified'=>(int)$u['is_verified'],'consumed'=>(int)$s->fetchColumn()];
        $s=$pdo->prepare('SELECT challenge_id,token_hash,issued_at,expires_at,consumed_at,invalidated_at,version FROM password_reset_challenges WHERE account_id=? ORDER BY issuance_generation');$s->execute([(int)$input['id']]);
        $result['challenge_fingerprint']=hash('sha256',json_encode($s->fetchAll(),JSON_THROW_ON_ERROR));
    } elseif ($action==='expire') {
        $pdo->prepare('UPDATE password_reset_challenges SET expires_at=UTC_TIMESTAMP(6) WHERE token_hash=?')->execute([hash('sha256',(string)$input['token'])]);$result=['expired'=>true];
    } elseif ($action==='stats') {
        $result=['signup'=>(int)$pdo->query('SELECT COUNT(*) FROM mail_outbox WHERE approval_code_id IS NOT NULL')->fetchColumn(),
            'notices'=>(int)$pdo->query('SELECT COUNT(*) FROM password_reset_mail_jobs WHERE purpose="password_changed"')->fetchColumn()];
    } elseif ($action==='diagnostics') {
        $root=realpath(__DIR__.'/../../.tmp');$dir=realpath((string)getenv('L06_BROWSER_RUN_DIR'));
        if (!$root || !$dir || !str_starts_with($dir,$root.DIRECTORY_SEPARATOR) || !str_starts_with(basename($dir),'l06-browser-')) throw new RuntimeException('Unsafe diagnostic path');
        $data='';foreach(['server.log','php-error.log'] as $file)if(is_file($dir.'/'.$file))$data.=(string)file_get_contents($dir.'/'.$file);
        $data.=json_encode($pdo->query('SELECT action FROM audit_log')->fetchAll());
        $data.=json_encode($pdo->query('SELECT reason_code,state,purpose FROM password_reset_mail_jobs')->fetchAll());
        $found=false;foreach($input['tokens']??[] as $token)if(is_string($token)&&strlen($token)===64&&str_contains($data,$token))$found=true;
        $result=['proof_found'=>$found];
    } else { throw new RuntimeException('Unknown fixture operation'); }
    fwrite(STDOUT,json_encode($result,JSON_THROW_ON_ERROR));
} catch(Throwable $e) { fwrite(STDERR,'L06 synthetic fixture failed: '.get_class($e).PHP_EOL);exit(1); }
