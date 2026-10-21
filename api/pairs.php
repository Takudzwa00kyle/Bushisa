<?php
declare(strict_types=1);
require_once __DIR__.'/../php/security_headers.php';
require_once __DIR__.'/../php/session_manager.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../php/csrf.php';
require_once __DIR__.'/../php/sanitize.php';
require_once __DIR__.'/../php/function.php';
require_once __DIR__.'/../php/pair_history.php';
require_once __DIR__.'/../php/http_test_barrier.php';

require_login();
if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { json_response(['error'=>'Method not allowed'],405); }
$actor=(int)$_SESSION['user_id']; $version=(int)$_SESSION['session_version'];
$input=$_SERVER['REQUEST_METHOD']==='POST'?(json_decode(file_get_contents('php://input')?:'',true)?:[]):$_GET;
if (!is_array($input)) { json_response(['error'=>'Invalid pair action'],400); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_SERVER['HTTP_X_CSRF_TOKEN']??$input['csrf_token']??'';
    if (!is_string($token)||!validate_csrf_token($token)) { json_response(['error'=>'Invalid CSRF token'],403); }
}
session_write_close();
try {
    bushisa_http_test_barrier('pair_transition_before_transaction');
    $data=bushisa_report_transaction($pdo,function() use($pdo,$actor,$version,$input):array {
        if (!bushisa_report_actor_ok($pdo,$actor,$version)) { throw new BushisaReportError(401,'Your session is no longer valid'); }
        $match=clean_int($input['match_id']??null); $other=clean_int($input['user_id']??null);
        if ($match!==null && $match>0) { $members=bushisa_pair_members($pdo,$match,$actor); $other=(int)((int)$members['a']===$actor?$members['b']:$members['a']); }
        if ($other===null || $other<1 || $other===$actor) { throw new BushisaReportError(400,'Invalid pair'); }
        // For never-matched targets, only public matching eligibility permits
        // preparation. GET discloses no target existence/private profile fields.
        if ($match===null) {
            $existing=$pdo->prepare('SELECT 1 FROM pair_policy_history WHERE user_low=? AND user_high=?');
            $existing->execute([min($actor,$other),max($actor,$other)]);
            if (!$existing->fetchColumn()) { bushisa_pair_check_contact($pdo,$actor,$other); }
        }
        $pair=bushisa_pair_ensure($pdo,$actor,$other);
        if ($_SERVER['REQUEST_METHOD']==='GET') { return bushisa_pair_projection($pdo,$pair,$actor); }
        $action=$input['action']??'';
        if (!in_array($action,['unmatch','restore','refuse_restore'],true) || $pair['match_id']===null) { throw new BushisaReportError(400,'Invalid pair action'); }
        $operation=bushisa_pair_operation($pdo,$actor,$pair,$input,fn()=>bushisa_pair_restore($pdo,$pair,$actor,$action));
        return ['operation'=>$operation,'pair'=>bushisa_pair_projection($pdo,bushisa_pair_ensure($pdo,$actor,$other),$actor)];
    });
    json_response(['success'=>true,'data'=>$data]);
} catch (BushisaReportError $e) { json_response(['error'=>$e->getMessage()],$e->status); }
catch (Throwable $e) { log_to_file('Pair action failed: '.get_class($e),'ERROR'); json_response(['error'=>'Pair action unavailable'],503); }
