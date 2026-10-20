<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/session_copy_fixture.php';
try {
    \Bushisa\SessionCopies\guard(); // Before connection or reading a capsule.
    $options=getopt('',['job:']);$path=(string)($options['job']??'');$real=realpath($path);$base=realpath(dirname(__DIR__,2).'/.tmp');
    if(!$real||!$base||is_link($path)||!str_starts_with(str_replace('\\','/',$real),str_replace('\\','/',$base).'/sc-run-')||filesize($real)>16384)throw new RuntimeException('SESSION_FIXTURE_REFUSED');
    $job=json_decode(file_get_contents($real),true,8,JSON_THROW_ON_ERROR);$p=\Bushisa\SessionCopies\connection();
    $identity=['pid'=>getmypid(),'connection_id'=>(int)$p->query('SELECT CONNECTION_ID()')->fetchColumn(),'sapi'=>PHP_SAPI];
    if(isset($job['barrier'])) {
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']=$job['barrier'];$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']=$job['point'];
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED']='2';$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS']='14000';$_SERVER['HTTP_X_BUSHISA_TEST_GATE']='true';
        bushisa_signup_test_point($p,$job['point']);
    }
    switch($job['action']) {
        case 'attempt':$result=\Bushisa\SessionCopies\attempt($p,$job['event'],$job['steps']??25);break;
        case 'claim':$result=\Bushisa\SessionCopies\claim($p,$job['event'],$job['token']);break;
        case 'step':$result=\Bushisa\SessionCopies\step($p,$job['event'],$job['token']);break;
        case 'expire_cleanup':
            bushisa_signup_check_account($p,$job['id']);$result=\Bushisa\SessionCopies\attempt($p,$job['event']);break;
        case 'lock':
            $m=sc_q($p,'SELECT object_generation FROM session_copy_manifests WHERE copy_ref=?',[$job['copy']])->fetchColumn();
            $o=(new \Bushisa\ManagedSession\Store(\Bushisa\SessionCopies\guard()))->probe($job['copy'],$m);
            require_once __DIR__.'/../../php/http_test_barrier.php';bushisa_write_test_record($job['ready'],$identity);
            $until=microtime(true)+15;
            while(!is_file($job['ready'].'.release')){if(microtime(true)>$until)throw new RuntimeException('SESSION_FIXTURE_TIMEOUT');usleep(10000);}
            if(is_resource($o['handle']))fclose($o['handle']);$result='unlocked';break;
        default:throw new RuntimeException('SESSION_FIXTURE_ACTION');
    }
    echo json_encode(['result'=>$result,'identity'=>$identity],JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,json_encode(['error'=>\Bushisa\SessionCopies\reason($e)]).PHP_EOL);exit(1); }
