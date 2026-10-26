<?php
declare(strict_types=1);
require_once __DIR__.'/../php/managed_session.php';
// Also run with php -n -d disable_classes=PDO: unsafe inputs must be refused
// before a driver can connect. No files, sessions or database are opened here.
$safe=['APP_ENV'=>'development','APP_DEBUG'=>'false','BUSHISA_TEST_DATABASE'=>'true','BUSHISA_ALLOW_RUNTIME_DDL'=>'false',
    'BUSHISA_Q11_SYNTHETIC'=>'true','BUSHISA_SESSION_SYNTHETIC'=>'true','MAIL_PROVIDER'=>'fake','MAIL_TRANSPORT'=>'fake',
    'DB_HOST'=>'127.0.0.1','DB_PORT'=>'3371','DB_NAME'=>'bushisa_test_q11_guard','DB_USER'=>'unused',
    'BUSHISA_SESSION_ROOT'=>'','BUSHISA_SESSION_KEY'=>'','BUSHISA_SESSION_NAMESPACE'=>''];
$variants=[['APP_ENV'=>'production'],['APP_DEBUG'=>'true'],['BUSHISA_TEST_DATABASE'=>'false'],['BUSHISA_ALLOW_RUNTIME_DDL'=>'true'],
    ['BUSHISA_SESSION_SYNTHETIC'=>'false'],['BUSHISA_Q11_SYNTHETIC'=>'false'],['MAIL_PROVIDER'=>'smtp'],['MAIL_TRANSPORT'=>'smtp'],
    ['DB_HOST'=>'remote.invalid'],['DB_NAME'=>'bushisa'],['DB_PORT'=>'0'],['DB_USER'=>''],[]];
foreach($variants as $n=>$override) {
    foreach(array_replace($safe,$override) as $key=>$value)putenv($key.'='.$value);
    try { \Bushisa\ManagedSession\connection();fwrite(STDERR,'FAIL guard variant '.$n.PHP_EOL);exit(1); }
    catch(RuntimeException $e) { if(!in_array($e->getMessage(),['SESSION_SYNTHETIC_GUARD','SESSION_STORE_GUARD'],true))throw $e; }
}
echo 'unit_session_fence_guard PASS (13 pre-connection refusals)'.PHP_EOL;
