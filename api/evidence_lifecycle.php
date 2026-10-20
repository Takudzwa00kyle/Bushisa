<?php
declare(strict_types=1);
require_once __DIR__.'/../php/security_headers.php';
require_once __DIR__.'/../php/session_manager.php';
require_once __DIR__.'/../php/csrf.php';
require_once __DIR__.'/../php/evidence_lifecycle.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, private');
try {
    if ($_SERVER['REQUEST_METHOD']!=='POST') { throw new \Bushisa\Evidence\Failure('METHOD_NOT_ALLOWED',405); }
    $raw=file_get_contents('php://input',false,null,0,8193);
    if (!is_string($raw) || strlen($raw)>8192) { throw new \Bushisa\Evidence\Failure('INVALID_REQUEST',422); }
    $p=json_decode($raw,true,12,JSON_THROW_ON_ERROR);
    if (!is_array($p) || array_diff(array_keys($p),['action','case','key','args']) || !is_string($p['action']??null)
        || !is_int($p['case']??null) || !is_string($p['key']??null) || !is_array($p['args']??null)) { throw new \Bushisa\Evidence\Failure('INVALID_REQUEST',422); }
    if (!validate_csrf_token((string)($_SERVER['HTTP_X_CSRF_TOKEN']??''))) { throw new \Bushisa\Evidence\Failure('INVALID_CSRF',403); }
    $ctx=\Bushisa\Evidence\Context::fromServerSession($_SESSION);
    session_write_close();
    require __DIR__.'/../config/db.php';
    if ($p['action']==='held_view') {
        if (array_diff(array_keys($p['args']),['item','revision','purpose']) || !is_string($p['args']['item']??null)
            || !is_int($p['args']['revision']??null) || !is_string($p['args']['purpose']??null)) { throw new \Bushisa\Evidence\Failure('INVALID_REQUEST',422); }
        $result=['text'=>\Bushisa\Evidence\held_view($pdo,$ctx,$p['case'],$p['args']['item'],$p['args']['revision'],$p['args']['purpose'])];
    } else { $result=\Bushisa\Evidence\act($pdo,$ctx,$p['case'],$p['key'],$p['action'],$p['args']); }
    echo json_encode(['data'=>$result],JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code($e instanceof \Bushisa\Evidence\Failure?$e->status:503);
    echo json_encode(['error'=>$e instanceof \Bushisa\Evidence\Failure?$e->reason:'LIFECYCLE_UNAVAILABLE']);
}
