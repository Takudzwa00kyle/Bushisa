<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../../php/evidence_recovery.php';
try {
    \Bushisa\Evidence\synthetic_guard();
    $o=getopt('',['job:','ready:']);
    $root=realpath(__DIR__.'/../../.tmp'); $file=realpath((string)($o['job']??''));
    if (!$root || !$file || !str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/l03a-') || filesize($file)>16384) { throw new RuntimeException('Unsafe worker fixture'); }
    $job=json_decode((string)file_get_contents($file),true,16,JSON_THROW_ON_ERROR);
    require __DIR__.'/../../config/db.php';
    if (getenv('BUSHISA_L03A_LOCK_TIMEOUT')==='1') { $pdo->exec('SET SESSION innodb_lock_wait_timeout=1'); }
    $attempts=0;
    if (isset($o['ready'])) {
        $ready=(string)$o['ready'];
        if (realpath(dirname($ready))!==dirname($file) || !preg_match('/\Aready-[a-z0-9-]+\.json\z/D',basename($ready))) { throw new RuntimeException('Unsafe worker readiness path'); }
        require_once __DIR__.'/../../php/http_test_barrier.php';
        bushisa_write_test_record($ready,['pid'=>getmypid(),'connection_id'=>(int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn()]);
    }
    $result=match($job['action']) {
        'purge'=>\Bushisa\Evidence\transaction($pdo,function() use($pdo,$job,&$attempts): array { $attempts++; return \Bushisa\Evidence\purge_locked($pdo,$job['item']); }),
        'cleanup'=>\Bushisa\Evidence\cleanup($pdo,$job['limit']),
        'held_view'=>(function() use($pdo,$job): array {
            \Bushisa\Evidence\held_view($pdo,\Bushisa\Evidence\Context::fromServerSession($job['session']),$job['case'],$job['item'],1,'CASE_EVIDENCE_REVIEW');
            return ['status'=>'held_view_authorized']; // Never log the body.
        })(),
        default=>\Bushisa\Evidence\act($pdo,\Bushisa\Evidence\Context::fromServerSession($job['session']),$job['case'],$job['key'],$job['action'],$job['args']),
    };
    \Bushisa\Evidence\fault('after_commit');
    echo json_encode(['result'=>$result,'transaction_attempts'=>$attempts],JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) {
    fwrite(STDERR,json_encode(['error'=>$e instanceof \Bushisa\Evidence\Failure?$e->reason:'WORKER_TRANSACTION_FAILED','engine_errno'=>$e instanceof PDOException?(int)($e->errorInfo[1]??0):null,'transaction_attempts'=>$attempts??0],JSON_THROW_ON_ERROR).PHP_EOL); exit(1);
}
