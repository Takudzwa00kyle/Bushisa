<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/support/legacy_history_fixture.php';
require_once __DIR__.'/../php/synthetic_legacy_import.php';
use Bushisa\LegacyHistory\InvalidInput;
use function Bushisa\LegacyHistory\{decode,encode,evaluate};
use function Bushisa\LegacyHistory\Fixture\fixture;
use function Bushisa\SyntheticImport\{guards,preflight};

$checks=[];$started=gmdate('c');
function baseline_guard_ld(): stdClass {
    foreach(['AZURE_MYSQL_HOST','AZURE_MYSQL_PORT','AZURE_MYSQL_DBNAME','AZURE_MYSQL_USERNAME','AZURE_MYSQL_PASSWORD','DB_SSL_CA','DB_PASS','BUSHISA_ALLOW_REMOTE_TEST_DATABASE'] as $v)putenv($v);
    foreach(['APP_ENV'=>'development','APP_DEBUG'=>'false','BUSHISA_ALLOW_RUNTIME_DDL'=>'false','BUSHISA_TEST_DATABASE'=>'true','BUSHISA_SYNTHETIC_IMPORT_ENABLE'=>'true','MAIL_PROVIDER'=>'fake',
        'DB_HOST'=>'127.0.0.1','DB_PORT'=>'11111','DB_NAME'=>'bushisa_test_l02d_guard','DB_USER'=>'l02d_guard','DB_PASSWORD'=>'synthetic-unused'] as $k=>$v)putenv($k.'='.$v);
    return (object)['format'=>'bushisa.synthetic-import-permit.v1','domain'=>'synthetic','host'=>'127.0.0.1','port'=>'11111','database'=>'bushisa_test_l02d_guard','user'=>'l02d_guard','incarnation'=>'syn:D',
        'server_uuid'=>'00000000-0000-0000-0000-000000000000','nonce'=>str_repeat('a',64),'expires_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+3600),'test_controls'=>false];
}
function assert_guard_ld(string $case,string $name,bool $ok): void {global $checks;$checks[]=['case'=>$case,'check'=>$name,'result'=>$ok?'PASS':'FAIL'];if(!$ok)throw new RuntimeException($name);}
function reject_guard_ld(callable $action): bool {try{$action();return false;}catch(InvalidInput){return true;}}
$p=baseline_guard_ld();assert_guard_ld('LB21','Explicit synthetic preconnection settings accepted without opening PDO',guards($p)['name']==='bushisa_test_l02d_guard');
foreach(['APP_ENV'=>'production','APP_DEBUG'=>'true','BUSHISA_ALLOW_RUNTIME_DDL'=>'true','BUSHISA_TEST_DATABASE'=>'false','BUSHISA_SYNTHETIC_IMPORT_ENABLE'=>'false','MAIL_PROVIDER'=>'smtp',
    'DB_HOST'=>'example.mysql.database.azure.com','DB_PORT'=>'3306;dbname=live','DB_NAME'=>'bushisa_db','DB_USER'=>'root','BUSHISA_ALLOW_REMOTE_TEST_DATABASE'=>'true'] as $k=>$v){
    $p=baseline_guard_ld();putenv($k.'='.$v);assert_guard_ld('LB21','Reject unsafe setting '.$k,reject_guard_ld(fn()=>guards($p)));
}
foreach(['APP_ENV','DB_HOST','DB_PORT','DB_NAME','DB_USER','BUSHISA_SYNTHETIC_IMPORT_ENABLE'] as $k){$p=baseline_guard_ld();putenv($k);assert_guard_ld('LB21','Missing explicit '.$k.' is default-deny',reject_guard_ld(fn()=>guards($p)));}
foreach(['database'=>'bushisa_test_l02d_other','host'=>'localhost','incarnation'=>'actual-resource','nonce'=>'bad','server_uuid'=>'not-a-server','expires_at'=>'2000-01-01T00:00:00Z','domain'=>'production','format'=>'bushisa.real-import.v1'] as $k=>$v){
    $p=baseline_guard_ld();$p->{$k}=$v;assert_guard_ld('LB21','Reject permit mismatch '.$k,reject_guard_ld(fn()=>guards($p)));
}
$p=baseline_guard_ld();$p->force=true;assert_guard_ld('LB21','Permit cannot carry a force/production override',reject_guard_ld(fn()=>guards($p)));
[$b,$s,$a]=fixture();$plan=decode(encode(evaluate($b,$s,$a,'2026-10-16T12:00:00Z')));$raw=['bundle'=>hash('sha256',encode($b)),'snapshot'=>hash('sha256',encode($s))];
assert_guard_ld('LB22','Existing synthetic v1 passes pure preflight without a database',preflight($b,$s,$a,$plan,$raw)['outcome']==='PLAN_NON_ENABLING');
foreach(['request_union','actual_write_set','grants_chat','raw_custody_check'] as $field){
    $bad=decode(encode($plan));$bad->{$field}=match($field){'request_union'=>(object)['low_to_high'=>0,'high_to_low'=>0],'actual_write_set'=>['users'],'grants_chat'=>true,default=>'PRODUCTION_VERIFIED'};
    assert_guard_ld('LB22','Reject forged plan field '.$field,reject_guard_ld(fn()=>preflight($b,$s,$a,$bad,$raw)));
}
$bad=decode(encode($b));$bad->checkpoint=null;$badA=Bushisa\LegacyHistory\Fixture\catalog($bad,$s);
assert_guard_ld('LB03','Incomplete source rejected before database access',reject_guard_ld(fn()=>preflight($bad,$s,$badA,$plan,['bundle'=>hash('sha256',encode($bad)),'snapshot'=>$raw['snapshot']])));
$bad=decode(encode($b));$bad->format='unsupported';assert_guard_ld('LB22','Unsupported input version rejected before database access',reject_guard_ld(fn()=>preflight($bad,$s,$a,$plan,$raw)));
$dir=dirname(__DIR__).'/.tmp/l02d-guard-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));mkdir($dir,0700,true);
$sources=[];foreach(['php/synthetic_legacy_import.php','tests/unit_synthetic_import_guard.php'] as $file)$sources[$file]=hash('sha256',str_replace("\r\n","\n",file_get_contents(dirname(__DIR__).'/'.$file)));
file_put_contents($dir.'/execution.json',json_encode(['started_at'=>$started,'ended_at'=>gmdate('c'),'php'=>PHP_VERSION,'checks'=>$checks,'assertion_count'=>count($checks),'sources_lf_sha256'=>$sources,'database_connections'=>0,'scope'=>'Pure guards/preflight; no connection API invoked'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
fwrite(STDOUT,'unit_synthetic_import_guard PASS: '.count($checks).' assertions; evidence '.$dir.'/execution.json'."\n");
