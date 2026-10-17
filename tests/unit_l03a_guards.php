<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
// Run with -n -d disable_classes=PDO: a mistakenly early connection must fail
// differently from the expected explicit refusal.
require_once __DIR__.'/../php/evidence_lifecycle.php';
use Bushisa\Evidence\{Context,Failure};
$safe=['APP_ENV'=>'development','APP_DEBUG'=>'false','BUSHISA_ALLOW_RUNTIME_DDL'=>'false','BUSHISA_TEST_DATABASE'=>'true','BUSHISA_L03A_SYNTHETIC'=>'true',
    'DB_HOST'=>'127.0.0.1','DB_PORT'=>'3351','DB_USER'=>'synthetic_worker','DB_NAME'=>'bushisa_test_l03a_unit','MAIL_PROVIDER'=>'fake','MAIL_TRANSPORT'=>'fake'];
foreach($safe as $k=>$v) { putenv($k.'='.$v); }
$checks=[];
foreach(['APP_ENV'=>'production','APP_DEBUG'=>'true','BUSHISA_ALLOW_RUNTIME_DDL'=>'true','BUSHISA_TEST_DATABASE'=>'false','BUSHISA_L03A_SYNTHETIC'=>'false',
    'DB_HOST'=>'example.invalid','DB_PORT'=>'70000','DB_USER'=>'','DB_NAME'=>'bushisa_db','MAIL_PROVIDER'=>'acs','MAIL_TRANSPORT'=>'smtp'] as $k=>$bad) {
    putenv($k.'='.$bad);
    try { \Bushisa\Evidence\synthetic_guard(); $ok=false; } catch(Failure $e) { $ok=$e->reason==='SYNTHETIC_DESTINATION_REFUSED'; }
    $checks[]=['field'=>$k,'pre_connection_refused'=>$ok]; putenv($k.'='.$safe[$k]);
}
putenv('APP_ENV=production');
try { Context::fromServerSession(['user_id'=>1,'session_version'=>1,'mfa_verified'=>true,'role'=>'admin','evidence_authority_assertion'=>str_repeat('a',64)]); $ok=false; }
catch(Failure $e) { $ok=$e->reason==='AUTHORITY_INTEGRATION_UNAVAILABLE'; }
$checks[]=['field'=>'trusted operational authority','pre_connection_refused'=>$ok];
$pipes=[];
$child=proc_open([PHP_BINARY,'-n','-d','disable_classes=PDO',__DIR__.'/../bin/evidence_lifecycle_synthetic.php','--action=cleanup'],
    [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__),getenv());
if (!is_resource($child)) { throw new RuntimeException('Guard process unavailable'); }
fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
$checks[]=['field'=>'actual CLI with PDO disabled','pre_connection_refused'=>proc_close($child)===1 && $out==='' && (json_decode($err,true)['error']??'')==='SYNTHETIC_DESTINATION_REFUSED'];
echo json_encode(['result'=>count(array_filter($checks,fn($r)=>!$r['pre_connection_refused']))===0?'PASS':'FAIL','checks'=>$checks],JSON_PRETTY_PRINT).PHP_EOL;
exit(count(array_filter($checks,fn($r)=>!$r['pre_connection_refused']))===0?0:1);
