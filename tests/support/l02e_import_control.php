<?php
declare(strict_types=1);
require_once __DIR__.'/../../php/http_test_barrier.php';

// Loaded only after the executor's CLI/permit/disposable/test gates pass.
// No supplied SQL, paths or production mode. All extra row locks are test-only.
function bushisa_l02e_import_control(stdClass $permit, string $point, array $test, PDO $pdo): void
{
    if (PHP_SAPI !== 'cli' || !$permit->test_controls || getenv('BUSHISA_SYNTHETIC_IMPORT_TEST_CONTROLS') !== 'true'
        || !preg_match('/\Al02e_[a-f0-9]{32}\z/D', $test['test-id'] ?? '')
        || !in_array($test['test-mode'] ?? '', ['mixed','mixed_hold','deadlock_once','deadlock_exhaust','row_timeout_once','row_timeout_exhaust'], true)) {
        throw new Bushisa\LegacyHistory\InvalidInput('TEST_SCENARIO_DISABLED');
    }
    Bushisa\SyntheticImport\guards($permit);
    $directory=rtrim(sys_get_temp_dir(),'/\\').'/bushisa-l02e-controls';
    if(!is_dir($directory)&&!@mkdir($directory,0700,true)&&!is_dir($directory))throw new RuntimeException('Test control IO');
    $base=$directory.'/'.$test['test-id']; $attempt=(int)($test['attempt']??0);
    $stamp=static fn(array $extra)=>array_merge(['pid'=>getmypid(),'connection_id'=>(int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn(),'at'=>Bushisa\SyntheticImport\now($pdo)],$extra);
    if($point==='engine_error'){
        bushisa_write_test_record($base.'-error-'.$attempt.'.json',$stamp(['attempt'=>$attempt,'sqlstate'=>$test['sqlstate'],'errno'=>$test['errno']]));return;
    }
    if(in_array($test['test-mode'],['mixed','mixed_hold'],true) && $point==='before_lock'){
        require_once __DIR__.'/../../php/http_test_barrier.php';
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']=$test['test-id'];
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_POINT']='l02e_mixed';
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_EXPECTED']='3';
        $_SERVER['HTTP_X_BUSHISA_TEST_BARRIER_TIMEOUT_MS']='15000';
        $_SERVER['HTTP_X_BUSHISA_TEST_GATE']='true';
        bushisa_http_test_barrier('l02e_mixed',$pdo);return;
    }
    if($point!=='after_facts' || $test['test-mode']==='mixed' || ($attempt>1 && str_ends_with($test['test-mode'],'_once')))return;
    if($test['test-mode']==='mixed_hold'){
        bushisa_write_test_record($base.'-ready-'.$attempt.'.json',$stamp(['attempt'=>$attempt]));
        $deadline=microtime(true)+15;while(!is_file($base.'-proceed-'.$attempt)){if(microtime(true)>=$deadline)throw new RuntimeException('Mixed row gate timeout');usleep(10000);}return;
    }
    $control=Bushisa\LegacyHistory\read_artifact($base.'-control.json')['value'];
    Bushisa\LegacyHistory\object($control,['format','database','probe_pair']);
    if($control->format!=='bushisa.synthetic-lock-probe.v1' || $control->database!==$pdo->query('SELECT DATABASE()')->fetchColumn())throw new RuntimeException('Test control scope');
    Bushisa\LegacyHistory\ref($control->probe_pair);
    bushisa_write_test_record($base.'-ready-'.$attempt.'.json',$stamp(['attempt'=>$attempt]));
    $deadline=microtime(true)+15;
    while(!is_file($base.'-proceed-'.$attempt)){if(microtime(true)>=$deadline)throw new RuntimeException('Test probe gate timeout');usleep(10000);}
    $pdo->exec('SET SESSION innodb_lock_wait_timeout=1');
    $q=$pdo->prepare('SELECT h.user_low FROM pair_policy_history h JOIN synthetic_import_pairs p ON p.user_low=h.user_low AND p.user_high=h.user_high WHERE p.pair_ref=? FOR UPDATE');
    $q->execute([$control->probe_pair]);if($q->fetchColumn()===false)throw new RuntimeException('Missing synthetic probe pair');
}
