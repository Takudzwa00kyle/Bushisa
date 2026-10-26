<?php
declare(strict_types=1);
require_once __DIR__.'/../php/security_headers.php';
require_once __DIR__.'/../php/session_manager.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../php/csrf.php';
require_once __DIR__.'/../php/sanitize.php';
require_once __DIR__.'/../php/db.php';
require_once __DIR__.'/../php/rate_limit.php';
require_once __DIR__.'/../php/function.php';
require_once __DIR__.'/../php/pair_history.php';
require_once __DIR__.'/../php/matching/service.php';
require_once __DIR__.'/../php/http_test_barrier.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { json_response(['error'=>'Method not allowed'],405); }
require_login();
$actor=(int)$_SESSION['user_id']; $version=(int)$_SESSION['session_version'];
$input=json_decode(file_get_contents('php://input')?:'',true);
if (!is_array($input)) { json_response(['error'=>'Invalid swipe payload'],400); }
$token=$_SERVER['HTTP_X_CSRF_TOKEN']??$input['csrf_token']??'';
if (!is_string($token)||!validate_csrf_token($token)) { json_response(['error'=>'Invalid CSRF token'],403); }
$other=clean_int($input['target_id']??$input['swiped_id']??null); $direction=$input['direction']??null;
if ($other===null||$other<1||$other===$actor||!in_array($direction,['like','pass'],true)) { json_response(['error'=>'Invalid swipe payload'],400); }
session_write_close();
try {
    // Established rendezvous retained, now before shared lock/transaction.
    bushisa_http_test_barrier('swipe_after_transaction_begin');
    $response=bushisa_report_transaction($pdo,function() use($pdo,$actor,$other,$direction,$input,$version):array {
        if (!bushisa_report_actor_ok($pdo,$actor,$version)) { throw new BushisaReportError(401,'Your session is no longer valid'); }
        bushisa_pair_check_contact($pdo,$actor,$other);
        $viewer=get_profile($pdo,$actor); $target=get_profile($pdo,$other);
        $answers=matching_load_answers($pdo,[$actor,$other]);
        if (!matching_pair_is_eligible($viewer??[],get_preferences($pdo,$actor)??[],$target??[],get_preferences($pdo,$other)??[],$answers[$actor]??[],$answers[$other]??[])) {
            throw new BushisaReportError(403,'This profile is unavailable');
        }
        $pair=bushisa_pair_ensure($pdo,$actor,$other);
        return bushisa_pair_operation($pdo,$actor,$pair,$input,function() use($pdo,$pair,$actor,$other,$direction):array {
            if (!check_rate_limit($pdo,'swipe',(string)$actor,100,3600)) { throw new BushisaReportError(429,'Rate limit exceeded'); }
            if ($pair['match_id']!==null && bushisa_pair_members($pdo,(int)$pair['match_id'],$actor)['match_status']==='active') {
                throw new BushisaReportError(409,'This pair is already matched');
            }
            $sc=operational_swipe_columns($pdo);
            $stmt=$pdo->prepare(sprintf('INSERT IGNORE INTO swipes (swiper_id,%s,%s,created_at) VALUES (?,?,?,UTC_TIMESTAMP(6))',$sc['target'],$sc['direction']));
            $stmt->execute([$actor,$other,$direction]);
            if ($stmt->rowCount()!==1) { throw new BushisaReportError(409,'You have already swiped on this user'); }
            record_attempt($pdo,'swipe',(string)$actor);
            if ($direction==='like') {
                $stmt=$pdo->prepare(sprintf('SELECT id FROM swipes WHERE swiper_id=? AND %s=? AND %s="like" FOR UPDATE',$sc['target'],$sc['direction']));
                $stmt->execute([$other,$actor]);
                if ($stmt->fetchColumn()) {
                    $id=bushisa_pair_match($pdo,$pair);
                    foreach ([$actor,$other] as $recipient) {
                        operational_write_notification($pdo,$recipient,'match','New match','You have a new established match on Bushisa.','matches.php','match:'.$id.':'.$recipient);
                    }
                    return ['match'=>true,'matched'=>true,'match_id'=>$id];
                }
            }
            return ['match'=>false,'matched'=>false];
        });
    });
    json_response(['success'=>true,'data'=>$response]+$response);
} catch (BushisaReportError $e) { json_response(['error'=>$e->getMessage()],$e->status); }
catch (Throwable $e) { log_to_file('Swipe failed: '.get_class($e),'ERROR'); json_response(['error'=>'Unable to process swipe'],503); }
