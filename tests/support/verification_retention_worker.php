<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/verification_retention_fixture.php';
require_once __DIR__.'/../../php/http_test_barrier.php';
qm_guard();$opts=getopt('',['job:','ready:']);$job=json_decode(file_get_contents($opts['job']),true,16,JSON_THROW_ON_ERROR);$p=q11_connect();
bushisa_write_test_record($opts['ready'],['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'sapi'=>PHP_SAPI]);
try {
    switch($job['action']) {
        case 'cleanup':$result=\Bushisa\VerificationRetention\process($p,$job['key']);break;
        case 'claim':$result=bushisa_signup_claim_mail($p,(int)$job['mail'],$job['token'])!==null;break;
        case 'cancel':bushisa_terminalize_signup_code($p,(int)$job['code'],(int)$job['account'],'unused@example.invalid',false);$result='cancelled';break;
        case 'finish':$result=bushisa_signup_finish_delivery($p,(int)$job['mail'],$job['token'],null);break;
        case 'retry':$result=bushisa_signup_finish_delivery($p,(int)$job['mail'],$job['token'],new BushisaMailTransportException('synthetic_nonacceptance',true));break;
        case 'dispatch':
            $result=bushisa_dispatch_mail($p,30,function(array $payload,int $mail)use($p,$job):void {
                if ($mail!==(int)$job['mail']) { return; }
                bushisa_write_test_record($job['inflight'],['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'transaction_open'=>$p->inTransaction()]);
                $until=microtime(true)+14;while(!is_file($job['inflight'].'.release')) { if(microtime(true)>$until) { throw new RuntimeException('Synthetic carrier timeout'); } usleep(10000); }
            });break;
        default:throw new RuntimeException('Unsupported fixture action');
    }
    echo json_encode(['result'=>$result],JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,json_encode(['error'=>$e instanceof \Bushisa\VerificationRetention\Failure?$e->reason:'Q22_FIXTURE_WORKER_FAILED']).PHP_EOL);exit(1); }
