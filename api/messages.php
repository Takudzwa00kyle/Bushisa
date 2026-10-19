<?php
declare(strict_types=1);
require_once __DIR__.'/../php/security_headers.php';
require_once __DIR__.'/../php/session_manager.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../php/csrf.php';
require_once __DIR__.'/../php/function.php';
require_once __DIR__.'/../php/sanitize.php';
require_once __DIR__.'/../php/pair_messages.php';
require_once __DIR__.'/../php/http_test_barrier.php';
if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { json_response(['error'=>'Method not allowed'],405); }
require_login();
$actor=(int)$_SESSION['user_id']; $version=(int)$_SESSION['session_version'];
$input=$_SERVER['REQUEST_METHOD']==='POST'?(json_decode(file_get_contents('php://input')?:'',true)?:[]):$_GET;
if (!is_array($input)) { json_response(['error'=>'Invalid message'],400); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_SERVER['HTTP_X_CSRF_TOKEN']??$input['csrf_token']??'';
    if (!is_string($token)||!validate_csrf_token($token)) { json_response(['error'=>'Invalid CSRF token'],403); }
}
$match=clean_int($input['match_id']??null);
if ($match===null||$match<1) { json_response(['error'=>'Invalid match ID'],400); }
$action=$_SERVER['REQUEST_METHOD']==='GET'?'read':($input['action']??'send');
session_write_close();
try {
    bushisa_http_test_barrier($action==='unsend'?'report_before_transaction':'chat_relationship_mutation_before_update');
    $data=bushisa_pair_messages($pdo,$actor,$version,$match,$action,$input);
    json_response(['success'=>true,'data'=>$data]);
} catch (BushisaReportError $e) { json_response(['error'=>$e->getMessage()],$e->status); }
catch (Throwable $e) { log_to_file('Message action failed: '.get_class($e),'ERROR'); json_response(['error'=>'Message action unavailable'],503); }
