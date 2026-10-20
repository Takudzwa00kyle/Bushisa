<?php
declare(strict_types=1);
require_once __DIR__.'/support/stage_fixture_contract.php';
require_once __DIR__.'/../php/password_reset.php';
$drivers=class_exists(PDO::class) && method_exists(PDO::class,'getAvailableDrivers') ? PDO::getAvailableDrivers() : [];
if(in_array('--expect-no-mysql',$argv,true) && in_array('mysql',$drivers,true))throw new RuntimeException('Minimal guard profile unexpectedly loaded MySQL');
$initial=getenv(); $m=stage_fixture_manifest('bushisa_test_stage_unit','3306','12345678-1234-1234-1234-123456789abc');
$env=stage_fixture_environment($m,'runtime','serve','synthetic-only'); $cases=[];
function sg_unit(string $id,callable $work,bool $reject=true): void {
    global $env,$m,$cases;
    stage_set_environment($env);
    $raw=json_encode($m,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); file_put_contents($env['BUSHISA_STAGE_MANIFEST'],$raw);
    $reason='ALLOWED';
    try { $work(); $ok=!$reject; } catch(Throwable $e) { $ok=$reject && str_starts_with($e->getMessage(),'STAGE_');$reason=$ok?$e->getMessage():'UNEXPECTED_EXCEPTION'; }
    $cases[]=['case'=>$id,'result'=>$ok?'PASS':'FAIL','guard_reason'=>$reason]; if(!$ok) throw new RuntimeException('Guard case failed: '.$id);
}
try {
    sg_unit('SG-U01',static function():void { $c=\Bushisa\Staging\context('serve'); if ($c['mode']!=='local-fixture') \Bushisa\Staging\deny('UNIT'); },false);
    $i=2;
    foreach (['BUSHISA_STAGE_ENABLED'=>'false','APP_ENV'=>'production','APP_DEBUG'=>'true','BUSHISA_ALLOW_RUNTIME_DDL'=>'true',
        'BUSHISA_TEST_DATABASE'=>'false','MAIL_PROVIDER'=>'smtp','MAIL_TRANSPORT'=>'smtp','SESSION_COOKIE_SECURE'=>'false',
        'BUSHISA_STAGE_MANIFEST_SHA256'=>str_repeat('0',64),'BUSHISA_STAGE_ARTIFACT_SHA'=>str_repeat('f',40),
        'BUSHISA_STAGE_RUN_ID'=>str_repeat('0',32),'BUSHISA_STAGE_APP_RESOURCE_ID'=>'Bushisa','BUSHISA_STAGE_DB_RESOURCE_ID'=>'bushi-server',
        'DB_NAME'=>'bushi-database','DB_HOST'=>'bushi-server.mysql.database.azure.com','DB_USER'=>'root',
        'BUSHISA_STAGE_ROLE'=>'fixture','BUSHISA_STAGE_INTENT'=>'execute','BUSHISA_Q11_SYNTHETIC'=>'true',
        'BUSHISA_PASSWORD_RESET_SYNTHETIC'=>'true','BUSHISA_SESSION_SYNTHETIC'=>'true','SMTP_HOST'=>'127.0.0.1',
        'BUSHISA_STAGE_STORAGE_NAMESPACE'=>'unrelated','BUSHISA_STAGE_STORAGE_ROOT'=>dirname($env['BUSHISA_STAGE_STORAGE_ROOT'])] as $key=>$value) {
        sg_unit(sprintf('SG-U%02d',$i++),static function()use($key,$value):void { putenv($key.'='.$value); \Bushisa\Staging\context(); });
    }
    sg_unit(sprintf('SG-U%02d',$i++),static function():void { putenv('APP_ENV=staging'); \Bushisa\Staging\context(); });
    foreach (['http://stage.example.test','https://bushisa.me','https://stage.bushisa.me','https://other.example.test',
        'https://user:password@stage.example.test','https://stage.example.test:8443','https://stage.example.test/?token=secret',
        'https://stage.example.test/#secret','https://stage.example.test/path',"https://stage.example.test\r\nX: value"] as $origin) {
        sg_unit(sprintf('SG-U%02d',$i++),static function()use($origin,$m,$env):void {
            $m['origin']=$origin;$raw=json_encode($m,JSON_THROW_ON_ERROR);file_put_contents($env['BUSHISA_STAGE_MANIFEST'],$raw);
            putenv('BUSHISA_STAGE_MANIFEST_SHA256='.hash('sha256',$raw));putenv('APP_BASE_URL='.$origin); \Bushisa\Staging\context();
        });
    }
    foreach (['expires_at'=>'2000-01-01T00:00:00Z','server_uuid'=>'bad','mode'=>'managed'] as $key=>$value) {
        sg_unit(sprintf('SG-U%02d',$i++),static function()use($key,$value,$m,$env):void {
            if($key==='server_uuid') $m['database'][$key]=$value; else $m[$key]=$value;
            $raw=json_encode($m,JSON_THROW_ON_ERROR);file_put_contents($env['BUSHISA_STAGE_MANIFEST'],$raw);putenv('BUSHISA_STAGE_MANIFEST_SHA256='.hash('sha256',$raw));\Bushisa\Staging\context();
        });
    }
    sg_unit(sprintf('SG-U%02d',$i++),static function():void {
        $_SERVER['HTTP_HOST']='evil.invalid';$_SERVER['HTTP_FORWARDED']='host=evil.invalid;proto=http';
        $c=\Bushisa\Staging\context(); if(bushisa_reset_trusted_origin()!==$c['origin']) \Bushisa\Staging\deny('UNIT');
    },false);
    foreach (['funding','destination','security','egress','storage','key_provenance','primary','backup'] as $missing) {
        sg_unit(sprintf('SG-U%02d',$i++),static function()use($missing,$m,$env):void {
            // Fictional bindings test missing authority only; never connect.
            $m['mode']='managed';$m['origin']='https://bushisa-stage-fixture.azurewebsites.net';
            $prefix='/subscriptions/12345678-1234-1234-1234-123456789abc/resourceGroups/synthetic-stage-fixture/providers/';
            $m['resources']=['app'=>$prefix.'Microsoft.Web/sites/bushisa-stage-fixture','database'=>$prefix.'Microsoft.DBforMySQL/flexibleServers/bushisa-stage-fixture'];
            $m['database']['host']='bushisa-stage-fixture.mysql.database.azure.com';
            $m['acceptance']=array_fill_keys(['funding','destination','security','egress','storage','key_provenance'],'synthetic-not-an-operational-approval');
            $m['acceptance']['primary']='synthetic-primary-fixture';$m['acceptance']['backup']='synthetic-backup-fixture';$m['acceptance'][$missing]=null;
            $raw=json_encode($m,JSON_THROW_ON_ERROR);file_put_contents($env['BUSHISA_STAGE_MANIFEST'],$raw);$pin=hash('sha256',$raw);
            foreach(['BUSHISA_STAGE_MANIFEST_SHA256'=>$pin,'BUSHISA_STAGE_MANAGED_AUTHORIZATION'=>$pin,'BUSHISA_ALLOW_REMOTE_TEST_DATABASE'=>'true',
                'APP_BASE_URL'=>$m['origin'],'DB_HOST'=>$m['database']['host'],'BUSHISA_STAGE_APP_RESOURCE_ID'=>$m['resources']['app'],'BUSHISA_STAGE_DB_RESOURCE_ID'=>$m['resources']['database']] as $k=>$v)putenv($k.'='.$v);
            try { \Bushisa\Staging\context(); } catch(Throwable $e) { if($e->getMessage()!=='STAGE_ACCEPTANCE') throw new LogicException('Wrong guard boundary'); throw $e; }
        });
    }
    sg_unit(sprintf('SG-U%02d',$i++),static function()use($m,$env):void {
        $m['format']='bushisa.synthetic-stage.v999';$raw=json_encode($m,JSON_THROW_ON_ERROR);file_put_contents($env['BUSHISA_STAGE_MANIFEST'],$raw);putenv('BUSHISA_STAGE_MANIFEST_SHA256='.hash('sha256',$raw));\Bushisa\Staging\context();
    });
    sg_unit(sprintf('SG-U%02d',$i++),static function()use($env):void {
        $raw=str_repeat(' ',16385);file_put_contents($env['BUSHISA_STAGE_MANIFEST'],$raw);putenv('BUSHISA_STAGE_MANIFEST_SHA256='.hash('sha256',$raw));\Bushisa\Staging\context();
    });
    sg_unit(sprintf('SG-U%02d',$i++),static function():void {
        foreach(array_keys(getenv()) as $k) if(str_starts_with($k,'BUSHISA_STAGE_')) putenv($k);
        putenv('APP_ENV=production');putenv('APP_BASE_URL=https://bushisa.me');putenv('BUSHISA_PASSWORD_RESET_SYNTHETIC=false');
        if(bushisa_reset_trusted_origin()!=='https://bushisa.me') \Bushisa\Staging\deny('UNIT');
        putenv('APP_BASE_URL=https://other.azurewebsites.net');if(bushisa_reset_trusted_origin()!==null) \Bushisa\Staging\deny('UNIT');
    },false);
    fwrite(STDOUT,json_encode(['suite'=>'staging pure guards; no database connection','status'=>'PASS','php'=>PHP_VERSION,'pdo_class_available'=>class_exists(PDO::class),'pdo_drivers'=>$drivers,'cases'=>$cases],JSON_THROW_ON_ERROR).PHP_EOL);
} finally { stage_set_environment($initial); }
