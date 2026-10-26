<?php
declare(strict_types=1);

/** CLI process binding only; web requests continue to use the runtime identity.
 * A complete dedicated pair is mandatory in production. No admin fallback. */
function bushisa_mail_worker_bind_database(): bool
{
    if(PHP_SAPI!=='cli')throw new RuntimeException('MAIL_WORKER_DB_BINDING_INVALID');
    $production=strtolower((string)(getenv('APP_ENV')?:'development'))==='production';
    $user=getenv('MAIL_WORKER_DB_USER');$password=getenv('MAIL_WORKER_DB_PASSWORD');
    if($user===false&&$password===false&&!$production)return false;
    if(!is_string($user)||!is_string($password)||$user===''||$password==='')throw new RuntimeException('MAIL_WORKER_DB_BINDING_REQUIRED');
    $runtime=(string)(getenv('DB_USER')?:getenv('AZURE_MYSQL_USERNAME')?:'');
    if(!preg_match('/\A[A-Za-z0-9_]{1,32}\z/D',$user)||strtolower($user)==='root'
        ||strtolower($user)===strtolower(explode('@',$runtime,2)[0])
        ||strlen($password)>8192||str_contains($password,"\0")||stripos($password,'@Microsoft.KeyVault(')===0)throw new RuntimeException('MAIL_WORKER_DB_BINDING_INVALID');
    // Validate both before changing either. Target, TLS and schema settings are
    // preserved; this neither creates SQL users nor proves their grants.
    putenv('DB_USER='.$user);putenv('DB_PASSWORD='.$password);
    return true;
}
