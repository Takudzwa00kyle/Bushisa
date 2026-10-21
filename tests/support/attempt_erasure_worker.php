<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/attempt_erasure_fixture.php';
require_once __DIR__.'/../../php/http_test_barrier.php';
qe_guard();$opts=getopt('',['job:','ready:']);$job=json_decode(file_get_contents($opts['job']),true,16,JSON_THROW_ON_ERROR);$p=q11_connect();
bushisa_write_test_record($opts['ready'],['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'sapi'=>PHP_SAPI]);
if(isset($job['barrier'])){
    $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']=$job['barrier'];$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']='q11_mixed';$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED']='2';$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS']='14000';$_SERVER['HTTP_X_BUSHISA_TEST_GATE']='true';
}
try{
    if($job['action']==='attempt')$result=\Bushisa\AttemptErasure\attempt($p,$job['receipt'],(int)($job['steps']??25));
    elseif($job['action']==='dispatch')$result=bushisa_dispatch_mail($p,1,function()use($p,$job){bushisa_write_test_record($job['inflight'],['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'transaction_open'=>$p->inTransaction()]);$until=microtime(true)+12;while(!is_file($job['inflight'].'.release')){if(microtime(true)>$until)throw new RuntimeException('Synthetic carrier timeout');usleep(10000);}});
    else throw new RuntimeException('Unsupported synthetic worker action');
    echo json_encode(['result'=>$result],JSON_THROW_ON_ERROR).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,json_encode(['error'=>$e instanceof \Bushisa\AttemptErasure\Failure?$e->reason:'SYNTHETIC_WORKER_FAILURE']).PHP_EOL);exit(1);}
