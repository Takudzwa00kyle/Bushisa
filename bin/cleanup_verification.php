<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../php/verification_retention.php';
try {
    \Bushisa\VerificationRetention\worker_guard(); // Before loading connection settings.
    $opts=getopt('',['limit:','status:']);
    $limit=$opts['limit']??'25';
    if (!is_string($limit) || !ctype_digit($limit) || (int)$limit<1 || (int)$limit>50) { throw new \Bushisa\VerificationRetention\Failure('BATCH_LIMIT_INVALID'); }
    if (isset($opts['status']) && (!is_string($opts['status']) || !preg_match('/\A[a-f0-9]{64}\z/D',$opts['status']))) { throw new \Bushisa\VerificationRetention\Failure('RECORD_KEY_INVALID'); }
    require __DIR__.'/../config/db.php';
    $result=isset($opts['status'])?['status'=>\Bushisa\VerificationRetention\status($pdo,$opts['status'])]:['counts'=>\Bushisa\VerificationRetention\run($pdo,(int)$limit)];
    echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR,json_encode(['error'=>$e instanceof \Bushisa\VerificationRetention\Failure?$e->reason:'Q22_WORKER_FAILED']).PHP_EOL);exit(1);
}
