<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }

/** Generates fictional local bindings only, never an Azure destination. */
function stage_fixture_manifest(string $database,string $port,string $uuid): array
{
    $run=bin2hex(random_bytes(16));
    $root=dirname(__DIR__,2).'/.tmp/stage-'.$run;
    mkdir($root,0700,true); mkdir($root.'/sessions',0700); file_put_contents($root.'/.bushisa-stage',$run); chmod($root.'/.bushisa-stage',0600);
    $root=realpath($root);
    $roles=[]; foreach (['runtime','worker','runner','fixture','migration'] as $role) {
        $user='bst_'.substr($run,0,8).'_'.$role; $roles[$role]=['user'=>$user,'principal'=>$user.'@%'];
    }
    return ['format'=>'bushisa.synthetic-stage.v1','mode'=>'local-fixture','run_id'=>$run,'expires_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+3600),
        'candidate_sha'=>getenv('STAGE_SOURCE_SHA')?:str_repeat('0',40),
        'origin'=>'https://stage-'.$run.'.example.test','resources'=>['app'=>'local-fixture://app/'.$run,'database'=>'local-fixture://database/'.$run],
        'database'=>['host'=>'127.0.0.1','port'=>$port,'name'=>$database,'server_uuid'=>$uuid,'binding_id'=>bin2hex(random_bytes(16))],
        'roles'=>$roles,'recipients'=>['n02500001a@students.example.invalid','n02500002b@students.example.invalid'],
        'pending_recipient'=>'n02500002b@students.example.invalid','mail'=>'fake-only',
        'storage'=>['namespace'=>'stage_'.$run,'root'=>$root,'handler'=>'files'],'acceptance'=>null];
}

function stage_fixture_environment(array $m,string $role,string $intent,string $password): array
{
    $file=$m['storage']['root'].'/manifest.json';
    $raw=json_encode($m,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);file_put_contents($file,$raw);chmod($file,0600);
    $env=getenv();
    foreach (array_keys($env) as $key) if (str_starts_with($key,'BUSHISA_STAGE_') || str_starts_with($key,'SMTP_') || str_starts_with($key,'AZURE_MYSQL_')
        || in_array($key,['DB_PASS','BUSHISA_Q11_SYNTHETIC','BUSHISA_PASSWORD_RESET_SYNTHETIC','BUSHISA_SESSION_SYNTHETIC'],true)) unset($env[$key]);
    return array_merge($env,['APP_ENV'=>'development','APP_DEBUG'=>'false','BUSHISA_ALLOW_RUNTIME_DDL'=>'false','BUSHISA_TEST_DATABASE'=>'true',
        'BUSHISA_STAGE_ENABLED'=>'true','BUSHISA_STAGE_MANIFEST'=>$file,'BUSHISA_STAGE_MANIFEST_SHA256'=>hash('sha256',$raw),
        'BUSHISA_STAGE_RUN_ID'=>$m['run_id'],'BUSHISA_STAGE_ARTIFACT_SHA'=>$m['candidate_sha'],'BUSHISA_STAGE_APP_RESOURCE_ID'=>$m['resources']['app'],
        'BUSHISA_STAGE_DB_RESOURCE_ID'=>$m['resources']['database'],'BUSHISA_STAGE_STORAGE_NAMESPACE'=>$m['storage']['namespace'],'BUSHISA_STAGE_STORAGE_ROOT'=>$m['storage']['root'],
        'BUSHISA_STAGE_ROLE'=>$role,'BUSHISA_STAGE_INTENT'=>$intent,'BUSHISA_STAGE_SESSION_HANDLER'=>'files','DB_HOST'=>$m['database']['host'],'DB_PORT'=>$m['database']['port'],'DB_NAME'=>$m['database']['name'],
        'DB_USER'=>$m['roles'][$role]['user'],'DB_PASSWORD'=>$password,'MAIL_PROVIDER'=>'fake','MAIL_TRANSPORT'=>'fake','MAIL_FROM_ADDRESS'=>'synthetic@example.invalid',
        'STUDENT_EMAIL_DOMAIN_ALLOWLIST'=>'students.example.invalid','APP_BASE_URL'=>$m['origin'],'SESSION_COOKIE_SECURE'=>'true',
        'MAIL_OUTBOX_KEY'=>base64_encode(random_bytes(32)),'SIGNUP_CODE_HMAC_KEY'=>bin2hex(random_bytes(32)),
        'PASSWORD_RESET_ENABLED'=>'true','PASSWORD_RESET_RESEND_COOLDOWN_SECONDS'=>'1','PASSWORD_RESET_MAX_REQUESTS_PER_HOUR'=>'100','PASSWORD_RESET_MAX_REQUESTS_PER_DAY'=>'200']);
}

function stage_set_environment(array $env): void
{
    foreach (array_keys(getenv()) as $key) if (!array_key_exists($key,$env)) putenv($key);
    foreach ($env as $key=>$value) putenv($key.'='.$value);
}
