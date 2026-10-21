<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/q11_fixture.php';
// All guards run before the first connection. Input selects only known actions;
// no SQL, authority, clock or executable content is accepted from the job file.
bushisa_q11_synthetic_guard();
$options=getopt('',['job:','ready:']);
$job=json_decode((string)file_get_contents($options['job']),true,16,JSON_THROW_ON_ERROR);
$p=q11_connect();
require_once __DIR__.'/../../php/http_test_barrier.php';
bushisa_write_test_record($options['ready'],['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'sapi'=>PHP_SAPI]);
try {
    if (isset($job['barrier'])) {
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']=$job['barrier'];
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']=$job['point']??'q11_mixed';
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED']='2';
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS']='14000';
        $_SERVER['HTTP_X_BUSHISA_TEST_GATE']='true';
    }
    switch($job['action']) {
        case 'expire':
            bushisa_signup_test_point($p,'q11_mixed');
            $result=bushisa_signup_check_account($p,(int)$job['id']); break;
        case 'dispatch':
            $result=bushisa_dispatch_mail($p,1,function(array $payload)use($p,$job):void {
                if (!isset($job['inflight'])) { return; }
                // Publish identities only. No recipient, token or payload bytes.
                bushisa_write_test_record($job['inflight'],['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'transaction_open'=>$p->inTransaction()]);
                $until=microtime(true)+12;
                while(!is_file($job['inflight'].'.release')) { if(microtime(true)>$until) { throw new RuntimeException('Synthetic carrier gate timed out'); } usleep(10000); }
            }); break;
        default: throw new RuntimeException('Unsupported synthetic worker action');
    }
    echo json_encode(['result'=>$result],JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,json_encode(['error'=>'Q11_WORKER_FAILED','type'=>get_class($e)]).PHP_EOL); exit(1); }
