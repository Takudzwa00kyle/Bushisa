<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../php/http_test_barrier.php';
$started=gmdate('c');$checks=[];$id='l02e_'.bin2hex(random_bytes(16));
$_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']=$id;
foreach(['APP_ENV'=>'production','APP_DEBUG'=>'true','BUSHISA_TEST_DATABASE'=>'false','BUSHISA_ALLOW_RUNTIME_DDL'=>'true','DB_NAME'=>'bushisa_db','DB_HOST'=>'example.mysql.database.azure.com','BUSHISA_TEST_HTTP_OVERLAP'=>'false'] as $key=>$bad){
    foreach(['APP_ENV'=>'development','APP_DEBUG'=>'false','BUSHISA_TEST_DATABASE'=>'true','BUSHISA_ALLOW_RUNTIME_DDL'=>'false','BUSHISA_TEST_HTTP_OVERLAP'=>'true','DB_HOST'=>'127.0.0.1','DB_PORT'=>'11111','DB_NAME'=>'bushisa_test_l02d_e_guard','DB_USER'=>'l02e_unused'] as $k=>$v)putenv($k.'='.$v);
    putenv($key.'='.$bad);$denied=false;try{bushisa_http_test_barrier('l02e_mixed');}catch(RuntimeException){$denied=true;}
    if(!$denied)throw new RuntimeException('Unsafe test control accepted');$checks[]=['case'=>'LE-G01','check'=>'Refuse unsafe '.$key.' before connection/files','result'=>'PASS'];
}
unset($_SERVER['HTTP_X_BUSHISA_TEST_BARRIER']);bushisa_http_test_barrier('l02e_mixed');
$checks[]=['case'=>'LE-G02','check'=>'No test header leaves production flow untouched','result'=>'PASS'];
$files=glob(rtrim(sys_get_temp_dir(),'/\\').'/bushisa-http-barriers/'.$id.'*')?:[];
if($files!==[])throw new RuntimeException('Rejected gate created files');
$dir=dirname(__DIR__).'/.tmp/l02e-guard-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));mkdir($dir,0700,true);$sources=[];
foreach(['php/http_test_barrier.php','tests/unit_l02e_controls.php'] as $file)$sources[$file]=hash('sha256',str_replace("\r\n","\n",file_get_contents(dirname(__DIR__).'/'.$file)));
file_put_contents($dir.'/execution.json',json_encode(['started_at'=>$started,'ended_at'=>gmdate('c'),'checks'=>$checks,'assertion_count'=>count($checks),'pdo_disabled'=>true,'connections'=>0,'files_created_by_rejected_gates'=>0,'sources_lf_sha256'=>$sources],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
echo 'unit_l02e_controls PASS: '.count($checks).' assertions; evidence '.$dir.'/execution.json'."\n";
