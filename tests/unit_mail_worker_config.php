<?php
declare(strict_types=1);
require_once __DIR__.'/../php/mail_worker_config.php';
$names=['APP_ENV','DB_USER','DB_PASSWORD','AZURE_MYSQL_USERNAME','MAIL_WORKER_DB_USER','MAIL_WORKER_DB_PASSWORD'];$saved=[];foreach($names as $k)$saved[$k]=getenv($k);
function mw_reset(array $e):void{global $names;foreach($names as $k)putenv($k);foreach($e as $k=>$v)putenv($k.'='.$v);}
function mw_check(string $id,bool $ok):void{if(!$ok)throw new RuntimeException($id);echo $id." PASS\n";}
try{
    mw_reset(['APP_ENV'=>'development','DB_USER'=>'fixture_runtime','DB_PASSWORD'=>'runtime-fixture']);mw_check('MW01',!bushisa_mail_worker_bind_database()&&getenv('DB_USER')==='fixture_runtime');
    $cases=[
        ['APP_ENV'=>'production'],
        ['APP_ENV'=>'production','MAIL_WORKER_DB_USER'=>'worker'],
        ['APP_ENV'=>'production','MAIL_WORKER_DB_PASSWORD'=>'fixture'],
        ['APP_ENV'=>'development','MAIL_WORKER_DB_USER'=>'worker'],
        ['APP_ENV'=>'production','DB_USER'=>'same','MAIL_WORKER_DB_USER'=>'same','MAIL_WORKER_DB_PASSWORD'=>'fixture'],
        ['APP_ENV'=>'production','AZURE_MYSQL_USERNAME'=>'same@old-server','MAIL_WORKER_DB_USER'=>'same','MAIL_WORKER_DB_PASSWORD'=>'fixture'],
        ['APP_ENV'=>'production','MAIL_WORKER_DB_USER'=>'root','MAIL_WORKER_DB_PASSWORD'=>'fixture'],
        ['APP_ENV'=>'production','MAIL_WORKER_DB_USER'=>'worker;bad','MAIL_WORKER_DB_PASSWORD'=>'fixture'],
        ['APP_ENV'=>'production','MAIL_WORKER_DB_USER'=>'worker','MAIL_WORKER_DB_PASSWORD'=>'@Microsoft.KeyVault(SecretUri=unresolved)'],
        ['APP_ENV'=>'production','MAIL_WORKER_DB_USER'=>'worker','MAIL_WORKER_DB_PASSWORD'=>''],
    ];
    foreach($cases as $i=>$e){mw_reset($e);$before=[getenv('DB_USER'),getenv('DB_PASSWORD')];$denied=false;try{bushisa_mail_worker_bind_database();}catch(RuntimeException $x){$denied=in_array($x->getMessage(),['MAIL_WORKER_DB_BINDING_REQUIRED','MAIL_WORKER_DB_BINDING_INVALID'],true);}mw_check('MW'.str_pad((string)($i+2),2,'0',STR_PAD_LEFT),$denied&&$before===[getenv('DB_USER'),getenv('DB_PASSWORD')]);}
    mw_reset(['APP_ENV'=>'production','DB_USER'=>'runtime','DB_PASSWORD'=>'runtime-fixture','MAIL_WORKER_DB_USER'=>'worker','MAIL_WORKER_DB_PASSWORD'=>'worker-fixture']);mw_check('MW12',bushisa_mail_worker_bind_database()&&getenv('DB_USER')==='worker'&&getenv('DB_PASSWORD')==='worker-fixture');
}finally{foreach($saved as $k=>$v)putenv($v===false?$k:$k.'='.$v);}
