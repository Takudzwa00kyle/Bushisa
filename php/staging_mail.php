<?php
declare(strict_types=1);
namespace Bushisa\Staging;

require_once __DIR__.'/staging_guard.php';
require_once __DIR__.'/signup_verification.php';
require_once __DIR__.'/password_reset_mail_jobs.php';

/** Stage signup uses the original signup envelope and clocks, not a reset row. */
function queue_signup(\PDO $pdo,string $email,string $code,?int $codeId): void
{
    $m=context(); database($pdo,$m);
    demand($pdo->inTransaction() && $codeId!==null,'SIGNUP_TRANSACTION');
    $s=$pdo->prepare('SELECT u.id,u.signup_attempt_key,c.expires_at FROM users u JOIN student_email_approval_codes c ON c.user_id=u.id WHERE c.id=? AND c.email=?');
    $s->execute([$codeId,strtolower($email)]); $u=$s->fetch(\PDO::FETCH_ASSOC);
    demand(is_array($u) && account($pdo,$m,(int)$u['id'],(string)$u['signup_attempt_key'],$email),'ACCOUNT_BINDING');
    $payload=['recipient'=>strtolower($email),'from_address'=>'synthetic@example.invalid','subject'=>'Synthetic Bushisa approval','body'=>'Your Bushisa approval code is '.$code.'.'];
    $s=$pdo->prepare('INSERT INTO mail_outbox (recipient_email,subject,body,provider,status,created_at,expires_at,code_bearing,approval_code_id,next_attempt_at,metadata_json)
        VALUES (?,?,?,"fake","pending",UTC_TIMESTAMP(),?,1,?,UTC_TIMESTAMP(),JSON_OBJECT("synthetic_development_only",true))');
    $s->execute([strtolower($email),$payload['subject'],\bushisa_encrypt_mail_payload($payload,$codeId,strtolower($email)),$u['expires_at'],$codeId]);
    \bushisa_signup_register_mail_job($pdo,(int)$pdo->lastInsertId(),$codeId);
}

/** Explicit bounded jobs only. Validate the entire selection before claims. */
function dispatch(\PDO $pdo,array $jobs,?callable $capture=null): array
{
    $m=context('dispatch'); database($pdo,$m);
    demand(count($jobs)>=1 && count($jobs)<=20,'BATCH');
    $selected=[]; $seen=[];
    foreach ($jobs as $job) {
        demand(is_array($job) && count($job)===2 && isset($job['purpose'],$job['id']),'JOB');
        $purpose=$job['purpose']; $id=$job['id'];
        demand(in_array($purpose,['signup','password_reset','password_changed'],true) && is_string($id),'PURPOSE');
        demand(preg_match($purpose==='signup'?'/\A[1-9][0-9]{0,9}\z/D':'/\A[a-f0-9]{64}\z/D',$id)===1,'JOB_ID');
        demand(!isset($seen[$purpose.':'.$id]),'DUPLICATE_SELECTION'); $seen[$purpose.':'.$id]=true;
        if ($purpose==='signup') {
            $s=$pdo->prepare('SELECT u.id,u.email,u.signup_attempt_key,m.body payload,m.next_attempt_at,j.state,j.useful_until,UTC_TIMESTAMP(6) server_now FROM mail_outbox m JOIN signup_mail_jobs j ON j.mail_id=m.id JOIN student_email_approval_codes c ON c.id=m.approval_code_id JOIN users u ON u.id=c.user_id WHERE m.id=? AND m.provider="fake"');
        } else {
            $s=$pdo->prepare('SELECT u.id,u.email,u.signup_attempt_key,m.encrypted_payload payload,m.next_attempt_at,m.state,m.useful_until,UTC_TIMESTAMP(6) server_now FROM password_reset_mail_jobs m LEFT JOIN users u ON u.id=m.account_id WHERE m.mail_id=? AND m.purpose=?');
        }
        $s->execute($purpose==='signup'?[$id]:[$id,$purpose]); $r=$s->fetch(\PDO::FETCH_ASSOC);
        demand(is_array($r),'JOB_BINDING');
        // A terminal, body-free retry has no effect and cannot retrieve proof.
        if ($r['payload']===null) { $selected[]=[$purpose,$id,null]; continue; }
        demand($r['id']!==null && account($pdo,$m,(int)$r['id'],(string)$r['signup_attempt_key'],(string)$r['email']),'ACCOUNT_BINDING');
        if ($r['state']==='ready' && $r['next_attempt_at']!==null && $r['next_attempt_at']>$r['server_now'] && $r['useful_until']>$r['server_now']) {
            $selected[]=[$purpose,$id,null]; continue;
        }
        $selected[]=[$purpose,$id,$r];
    }
    $counts=['sent'=>0,'retried'=>0,'discarded'=>0,'failed'=>0];
    foreach ($selected as [$purpose,$id,$row]) {
        if ($row===null) { $counts['discarded']++; continue; }
        $private=null;
        // This callback cannot instantiate SMTP. Recheck immediately at carrier I/O.
        $carrier=static function(array $payload)use($pdo,$purpose,$row,&$private):void {
            $current=context('dispatch'); database($pdo,$current);
            demand(account($pdo,$current,(int)$row['id'],(string)$row['signup_attempt_key'],(string)$row['email'])
                && ($payload['recipient']??null)===$row['email'] && ($payload['from_address']??null)==='synthetic@example.invalid','CARRIER_BINDING');
            $body=$payload['body']??null; demand(is_string($body) && strlen($body)<4096,'PAYLOAD');
            $private=['purpose'=>$purpose,'account_id'=>(int)$row['id']];
            if ($purpose==='signup') {
                demand(preg_match('/approval code is ([0-9]{6,10})\./',$body,$p)===1,'SIGNUP_PAYLOAD'); $private['code']=$p[1];
            } elseif ($purpose==='password_reset') {
                demand(preg_match('/one-time key on the reset page:\s*([a-f0-9]{64})/i',$body,$p)===1
                    && str_contains($body,$current['origin'].'/reset_password.php#token='.$p[1]),'RESET_PAYLOAD');
                $private['token']=$p[1];
            } else demand(!str_contains($body,'#token='),'NOTICE_PAYLOAD');
        };
        if ($purpose==='signup') {
            $claim=bin2hex(random_bytes(32));
            if (!\bushisa_signup_claim_mail($pdo,(int)$id,$claim) || !($delivery=\bushisa_signup_begin_delivery($pdo,(int)$id,$claim))) { $counts['discarded']++; continue; }
            $error=null;
            try { $carrier($delivery['payload']); } catch (\Throwable $e) { $error=$e; }
            unset($delivery);
            $result=\bushisa_signup_finish_delivery($pdo,(int)$id,$claim,$error);
        } else $result=\bushisa_reset_dispatch_one($pdo,$id,$carrier);
        $counts[$result]++;
        // The transport result/receipt commits before proof is handed to its runner.
        if ($result==='sent' && $private!==null && $capture!==null) $capture($private);
        unset($private);
    }
    return $counts;
}

function private_pipe(array $input): mixed
{
    demand(PHP_SAPI==='cli' && getenv('BUSHISA_STAGE_PRIVATE_PIPE')==='true'
        && is_string($input['nonce']??null) && preg_match('/\A[a-f0-9]{64}\z/D',$input['nonce'])===1
        && hash_equals((string)getenv('BUSHISA_STAGE_PIPE_NONCE'),$input['nonce']),'PRIVATE_PIPE');
    $pipe=@fopen('php://fd/3','wb'); demand(is_resource($pipe),'PRIVATE_PIPE'); return $pipe;
}

function input(): array
{
    $raw=stream_get_contents(STDIN,8193); demand(is_string($raw) && strlen($raw)<=8192,'INPUT_SIZE');
    try { $in=json_decode($raw,true,8,JSON_THROW_ON_ERROR); } catch (\Throwable) { deny('INPUT_JSON'); }
    demand(is_array($in),'INPUT_JSON'); return $in;
}
