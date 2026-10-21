<?php
declare(strict_types=1);
// No production apply/publish mode. Guard before config/db.php can connect.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../php/evidence_recovery.php';
try {
    \Bushisa\Evidence\synthetic_guard();
    $options=getopt('',['action:','limit::']);
    if (array_diff(array_keys($options),['action','limit']) || !in_array($options['action']??'', ['cleanup','publish','replay','gate'],true)) {
        throw new \Bushisa\Evidence\Failure('INVALID_COMMAND',422);
    }
    require __DIR__.'/../config/db.php';
    $result=match($options['action']) {
        'cleanup'=>\Bushisa\Evidence\cleanup($pdo,(int)($options['limit']??25)),
        'publish'=>\Bushisa\Evidence\publish($pdo),
        'replay'=>\Bushisa\Evidence\replay($pdo),
        'gate'=>(function() use($pdo): array { \Bushisa\Evidence\recovery_gate($pdo); return ['status'=>'SYNTHETIC_GATE_READY']; })(),
    };
    fwrite(STDOUT,json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDERR,json_encode(['error'=>$e instanceof \Bushisa\Evidence\Failure?$e->reason:'LIFECYCLE_FAILED'],JSON_THROW_ON_ERROR).PHP_EOL); exit(1);
}
