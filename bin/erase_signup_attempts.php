<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../php/signup_attempt_erasure.php';
try{
    \Bushisa\AttemptErasure\worker_guard(); // Before connecting; not a public route.
    $opts=getopt('',['limit:','steps:','status:']);
    foreach(['limit'=>25,'steps'=>25] as $key=>$max)if(isset($opts[$key])&&(!is_string($opts[$key])||!ctype_digit($opts[$key])||(int)$opts[$key]<1||(int)$opts[$key]>$max))throw new \Bushisa\AttemptErasure\Failure('INVALID_BATCH_BOUND');
    if(isset($opts['status'])&&(!is_string($opts['status'])||!preg_match('/\A[a-f0-9]{64}\z/D',$opts['status'])))throw new \Bushisa\AttemptErasure\Failure('INVALID_RECEIPT');
    require __DIR__.'/../config/db.php';
    if(isset($opts['status'])){
        $row=\Bushisa\AttemptErasure\q($pdo,'SELECT stage,state,error_code,due_at,completed_at FROM signup_attempt_erasure_jobs WHERE receipt_id=?',[$opts['status']])->fetch(PDO::FETCH_ASSOC);echo json_encode(['status'=>$row?:null],JSON_THROW_ON_ERROR).PHP_EOL;
    }else{echo json_encode(['attempt_erasure'=>\Bushisa\AttemptErasure\run($pdo,(int)($opts['limit']??10),(int)($opts['steps']??20))],JSON_THROW_ON_ERROR).PHP_EOL;}
}catch(Throwable $e){fwrite(STDERR,json_encode(['error'=>$e instanceof \Bushisa\AttemptErasure\Failure?$e->reason:'ERASURE_DATABASE_FAILURE']).PHP_EOL);exit(1);}
