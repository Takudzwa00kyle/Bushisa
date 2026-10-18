<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/q11_fixture.php';
require_once __DIR__.'/../../php/managed_session.php';
\Bushisa\ManagedSession\configuration(); // Before connection or fixture content.
$options=getopt('',['job:']);
function sf_fixture_path(string $path): string {
    $real=realpath($path); $base=realpath(dirname(__DIR__,2).'/.tmp');
    if ($real===false || $base===false || is_link($path) || (!str_starts_with(str_replace('\\','/',$real),str_replace('\\','/',$base).'/sf-')&&!str_starts_with(str_replace('\\','/',$real),str_replace('\\','/',$base).'/sc-run-')) || filesize($real)>16384) { throw new RuntimeException('SESSION_FIXTURE_REFUSED'); }
    return $real;
}
$job=json_decode((string)file_get_contents(sf_fixture_path((string)($options['job']??''))),true,8,JSON_THROW_ON_ERROR);
try {
    $p=q11_connect();
    if (in_array($job['action'],['expire','version','erase','observe'],true)) {
        $identity=['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'sapi'=>PHP_SAPI];
        if (isset($job['barrier'])) {
            $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']=$job['barrier'];
            $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']=$job['point'];
            $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED']='2';
            $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS']='14000';
            $_SERVER['HTTP_X_BUSHISA_TEST_GATE']='true';
            bushisa_signup_test_point($p,$job['point']);
        }
        if ($job['action']==='expire') { $result=bushisa_signup_check_account($p,(int)$job['id']); }
        elseif ($job['action']==='erase') {
            require_once __DIR__.'/../../php/signup_attempt_erasure.php';
            $result=\Bushisa\AttemptErasure\attempt($p,$job['receipt']);
        } elseif ($job['action']==='observe') { $result='observed'; }
        else {
            bushisa_signup_transaction($p,fn()=>bushisa_signup_query($p,'UPDATE users SET session_version=session_version+1 WHERE id=?',[$job['id']]));
            $result='version_advanced';
        }
        echo json_encode(['result'=>$result,'identity'=>$identity],JSON_THROW_ON_ERROR); exit;
    }
    $capsule=isset($job['capsule'])?json_decode(file_get_contents(sf_fixture_path($job['capsule'])),true,8,JSON_THROW_ON_ERROR):[];
    if (isset($capsule['sid'])) { session_id($capsule['sid']); }
    ini_set('session.use_cookies','1'); session_cache_limiter('');
    if ($job['action']==='unmanaged') {
        $native=dirname($job['capsule']).'/isolated-unmanaged'; if(!is_dir($native))mkdir($native,0700);
        ini_set('session.save_path',$native); session_start();
        file_put_contents($job['capsule'],json_encode(['sid'=>session_id()],JSON_THROW_ON_ERROR));
        $_SESSION['unmanaged_private_marker']='synthetic-discarded-write';
    }
    require_once __DIR__.'/../../php/session_manager.php';
    require_once __DIR__.'/../../php/sanitize.php';
    require_once __DIR__.'/../../php/password_policy.php';
    require_once __DIR__.'/../../php/rate_limit.php';
    require_once __DIR__.'/../../php/db.php';
    require_once __DIR__.'/../../php/function.php';
    require_once __DIR__.'/../../php/auth.php';
    require_once __DIR__.'/../../php/csrf.php';
    $pdo=$p; $h=bushisa_managed_handler();
    generate_csrf_token();
    if (isset($job['ready'])) {
        file_put_contents($job['ready'],json_encode($h->testIdentity(),JSON_THROW_ON_ERROR));
        $until=microtime(true)+15;
        while(!is_file($job['ready'].'.release')) { if(microtime(true)>$until) { throw new RuntimeException('SESSION_FIXTURE_TIMEOUT'); } usleep(10000); }
    }
    $result=true;$loginError=null;
    switch($job['action']) {
        case 'login':
            $login=attempt_login($p,$job['email'],'Synthet1c!Fixture','sf_'.bin2hex(random_bytes(8)));$result=$login['success'];$loginError=$login['error'];break;
        case 'write': case 'implicit':
            $_SESSION['last_activity']=time(); break;
        case 'lazy': break;
        case 'inject': $_SESSION[$job['field']]=$job['value']; break;
        case 'rotate': regenerate_session(); break;
        case 'logout': destroy_session(); break;
        case 'handoff':
            $id=(int)$_SESSION['user_id']; $old=(int)$_SESSION['session_version']; session_write_close();
            $transition=($job['kind']==='verify')?bushisa_verify_signup_code($p,$id,$job['code'],$old):bushisa_complete_adult_onboarding($p,$id,'2000-01-01',$old);
            $result=$transition['success']&&bushisa_resume_transition_session($p,$id,$old,(int)$transition['session_version']); break;
        case 'invent_handoff':
            session_write_close(); $result=bushisa_resume_transition_session($p,$job['id'],$job['old'],$job['new']); break;
        case 'read': break;
        default: throw new RuntimeException('SESSION_FIXTURE_ACTION');
    }
    $snapshot=['authenticated'=>isset($_SESSION['user_id']),'user_id'=>$_SESSION['user_id']??null,'version'=>$_SESSION['session_version']??null,'role'=>$_SESSION['role']??null,
        'email_present'=>isset($_SESSION['email']),'mfa_present'=>isset($_SESSION['mfa_verified']),'generation_present'=>isset($_SESSION['account_generation'])];
    // This private synthetic client capsule is NOT diagnostics/evidence. No
    // session identifiers, cookies or CSRF values are printed or retained there.
    if (isset($job['capsule']) && session_id()!=='') {
        file_put_contents($job['capsule'],json_encode(['sid'=>session_id(),'csrf'=>$_SESSION['csrf_token']??null],JSON_THROW_ON_ERROR));
    }
    if ($job['action']!=='implicit' && session_status()===PHP_SESSION_ACTIVE) { @session_write_close(); }
    echo json_encode(['result'=>$result,'snapshot'=>$snapshot,'identity'=>$h->testIdentity(),'failed'=>$h->failed,'error'=>$h->error,'login_error'=>$loginError],JSON_THROW_ON_ERROR);
} catch(Throwable) { http_response_code(503); echo '{"error":"SESSION_FIXTURE_FAILED"}'; exit(1); }
