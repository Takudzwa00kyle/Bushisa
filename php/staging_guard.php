<?php
declare(strict_types=1);

namespace Bushisa\Staging;

require_once __DIR__.'/../config/database_settings.php';

/** Deployment configuration is authority here; HTTP input is never consulted. */
function requested(): bool
{
    return getenv('BUSHISA_STAGE_ENABLED') !== false || getenv('BUSHISA_STAGE_MANIFEST') !== false;
}

function deny(string $code): never
{
    // Only fixed reason codes may cross the diagnostic boundary.
    throw new \RuntimeException('STAGE_'.$code);
}

function demand(bool $ok, string $code): void { if (!$ok) deny($code); }

function canonical_origin(mixed $value): ?string
{
    if (!is_string($value) || strlen($value)>253 || preg_match('/[\x00-\x20\\\\]/', $value)) return null;
    $p=parse_url($value);
    if (!is_array($p) || array_diff(array_keys($p), ['scheme','host','port','path'])
        || ($p['scheme']??'')!=='https' || !isset($p['host']) || isset($p['port']) && $p['port']!==443
        || !in_array($p['path']??'', ['', '/'], true)) return null;
    $host=$p['host'];
    if ($host!==strtolower($host) || !preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+\z/D',$host)
        || $host==='bushisa.me' || str_ends_with($host,'.bushisa.me')) return null;
    return 'https://'.$host;
}

function private_path(string $path, bool $directory): string
{
    demand($path!=='' && !preg_match('~(?:^|[\\\\/])\.\.(?:[\\\\/]|$)|[\x00-\x1f]~',$path), 'PATH');
    $real=realpath($path);
    demand(is_string($real) && ($directory?is_dir($real):is_file($real)), 'PATH');
    for ($part=$path; $part!=='' && dirname($part)!==$part; $part=dirname($part)) demand(!is_link($part),'SYMLINK');
    if (PHP_OS_FAMILY!=='Windows') demand((fileperms($real)&0077)===0, 'PRIVATE_PERMISSIONS');
    return $real;
}

/** Bounded pinned manifest; no DSN, SQL or executable input is accepted. */
function context(?string $requiredIntent=null): array
{
    foreach (['BUSHISA_STAGE_ENABLED'=>'true','APP_ENV'=>'development','APP_DEBUG'=>'false',
        'BUSHISA_ALLOW_RUNTIME_DDL'=>'false','BUSHISA_TEST_DATABASE'=>'true','MAIL_PROVIDER'=>'fake','MAIL_TRANSPORT'=>'fake',
        'SESSION_COOKIE_SECURE'=>'true'] as $key=>$expected) demand(getenv($key)===$expected,'ENVIRONMENT');
    foreach (['BUSHISA_Q11_SYNTHETIC','BUSHISA_PASSWORD_RESET_SYNTHETIC','BUSHISA_SESSION_SYNTHETIC'] as $key)
        demand(!filter_var(getenv($key)?:'false',FILTER_VALIDATE_BOOL),'MIXED_PROFILE');
    foreach (getenv() as $key=>$value) if ($key==='DB_PASS' || str_starts_with($key,'SMTP_') || str_starts_with($key,'AZURE_MYSQL_'))
        demand($value==='', 'FOREIGN_CREDENTIALS');
    $file=private_path((string)getenv('BUSHISA_STAGE_MANIFEST'),false);
    demand(filesize($file)<=16384, 'MANIFEST_SIZE');
    $raw=file_get_contents($file,false,null,0,16385);
    $pin=(string)getenv('BUSHISA_STAGE_MANIFEST_SHA256');
    demand(is_string($raw) && strlen($raw)<=16384 && preg_match('/\A[a-f0-9]{64}\z/D',$pin)===1 && hash_equals($pin,hash('sha256',$raw)),'MANIFEST_PIN');
    try { $m=json_decode($raw,true,12,JSON_THROW_ON_ERROR); } catch (\Throwable) { deny('MANIFEST_JSON'); }
    demand(is_array($m) && ($m['format']??null)==='bushisa.synthetic-stage.v1','FORMAT');
    demand(in_array($m['mode']??null,['local-fixture','managed'],true),'MODE');
    $run=$m['run_id']??'';
    demand(is_string($run) && preg_match('/\A[a-f0-9]{32}\z/D',$run)===1 && getenv('BUSHISA_STAGE_RUN_ID')===$run,'RUN_BINDING');
    $expiry=$m['expires_at']??'';
    demand(is_string($expiry) && preg_match('/\A\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ\z/D',$expiry)===1,'EXPIRY');
    $at=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z',$expiry,new \DateTimeZone('UTC'));
    demand($at!==false && $at->format('Y-m-d\TH:i:s\Z')===$expiry && $at->getTimestamp()>time(),'EXPIRED');
    demand(is_string($m['candidate_sha']??null) && preg_match('/\A[a-f0-9]{40}\z/D',$m['candidate_sha'])===1
        && getenv('BUSHISA_STAGE_ARTIFACT_SHA')===$m['candidate_sha'],'ARTIFACT');
    $origin=canonical_origin($m['origin']??null);
    demand($origin!==null && $m['origin']===$origin && getenv('APP_BASE_URL')===$origin,'ORIGIN');
    $db=$m['database']??[]; $resources=$m['resources']??[];
    demand(is_array($db) && is_array($resources),'RESOURCE_BINDING');
    foreach (['app','database'] as $kind) {
        $resource=$resources[$kind]??'';
        demand(is_string($resource) && strlen($resource)<512 && !preg_match('/[\x00-\x20]/',$resource)
            && getenv($kind==='app'?'BUSHISA_STAGE_APP_RESOURCE_ID':'BUSHISA_STAGE_DB_RESOURCE_ID')===$resource,'RESOURCE_BINDING');
    }
    demand(is_string($db['name']??null) && preg_match('/\Abushisa_test_stage_[a-z0-9_]{1,40}\z/D',$db['name'])===1,'DATABASE_NAME');
    demand(is_string($db['server_uuid']??null) && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D',$db['server_uuid'])===1,'SERVER_ID');
    demand(is_string($db['binding_id']??null) && preg_match('/\A[a-f0-9]{32}\z/D',$db['binding_id'])===1,'DATABASE_BINDING');
    foreach (['host','port','name'] as $key) demand(is_string($db[$key]??null) && getenv('DB_'.strtoupper($key))===$db[$key],'DATABASE_CONFIG');
    demand(ctype_digit($db['port']) && (int)$db['port']>0 && (int)$db['port']<=65535,'DATABASE_PORT');
    demand(!str_contains(strtolower($db['host']),'bushi-server') && $db['name']!=='bushi-database','PRODUCTION_TARGET');
    $roles=$m['roles']??[];
    demand(is_array($roles) && count($roles)===5 && count(array_unique(array_column($roles,'user')))===5,'ROLES');
    foreach (['runtime','worker','runner','fixture','migration'] as $role) {
        $r=$roles[$role]??[];
        demand(is_array($r) && is_string($r['user']??null) && preg_match('/\Abst_[a-z0-9_]{3,24}\z/D',$r['user'])===1
            && is_string($r['principal']??null) && str_starts_with($r['principal'],$r['user'].'@') && strlen($r['principal'])<288,'ROLE_IDENTITY');
    }
    $role=(string)getenv('BUSHISA_STAGE_ROLE'); $intent=(string)getenv('BUSHISA_STAGE_INTENT');
    demand(isset($roles[$role]) && in_array($intent,match($role){'runtime'=>['serve'],'worker'=>['dispatch'],'runner'=>['preflight','execute'],'fixture'=>['prepare'],'migration'=>['migrate'],default=>[]},true),'INTENT');
    demand($requiredIntent===null || $intent===$requiredIntent,'INTENT');
    demand(PHP_SAPI==='cli' || $role==='runtime','CLI_ONLY');
    demand(getenv('DB_USER')===$roles[$role]['user'] && is_string(getenv('DB_PASSWORD')) && getenv('DB_PASSWORD')!=='','ROLE_CREDENTIAL');
    demand(($m['mail']??null)==='fake-only' && getenv('STUDENT_EMAIL_DOMAIN_ALLOWLIST')==='students.example.invalid'
        && getenv('MAIL_FROM_ADDRESS')==='synthetic@example.invalid','MAIL_ISOLATION');
    demand(is_array($m['recipients']??null) && count($m['recipients'])>=1 && count($m['recipients'])<=10
        && count(array_unique($m['recipients']))===count($m['recipients']),'RECIPIENTS');
    foreach ($m['recipients'] as $recipient) demand(is_string($recipient) && preg_match('/\An025[0-9]{5}[a-z]@students\.example\.invalid\z/D',$recipient)===1,'RECIPIENT');
    demand(in_array($m['pending_recipient']??null,$m['recipients'],true) && count($m['recipients'])>=2,'FIXTURE_STATES');
    $storage=$m['storage']??[];
    demand(is_array($storage) && ($storage['namespace']??null)==='stage_'.$run && getenv('BUSHISA_STAGE_STORAGE_NAMESPACE')===$storage['namespace'],'NAMESPACE');
    demand(($storage['handler']??null)==='files' && getenv('BUSHISA_STAGE_SESSION_HANDLER')==='files','SESSION_HANDLER');
    $root=private_path((string)($storage['root']??''),true);
    demand(basename($root)==='stage-'.$run && getenv('BUSHISA_STAGE_STORAGE_ROOT')===$root,'STORAGE_BINDING');
    demand(is_file($root.'/.bushisa-stage') && !is_link($root.'/.bushisa-stage') && file_get_contents($root.'/.bushisa-stage',false,null,0,33)===$run,'STORAGE_MARKER');
    $sessions=private_path($root.'/sessions',true);
    demand(dirname($sessions)===$root,'SESSION_PATH');
    if ($m['mode']==='local-fixture') {
        demand(getenv('WEBSITE_SITE_NAME')===false || getenv('WEBSITE_SITE_NAME')==='','LOCAL_NOT_APP_SERVICE');
        demand(in_array($db['host'],['127.0.0.1','localhost','::1'],true),'LOCAL_DATABASE');
        demand($resources['app']==='local-fixture://app/'.$run && $resources['database']==='local-fixture://database/'.$run
            && $origin==='https://stage-'.$run.'.example.test','LOCAL_BINDING');
    } else {
        demand(getenv('BUSHISA_ALLOW_REMOTE_TEST_DATABASE')==='true' && getenv('BUSHISA_STAGE_MANAGED_AUTHORIZATION')===$pin,'MANAGED_AUTHORIZATION');
        foreach (['funding','destination','security','egress','storage','key_provenance','primary','backup'] as $key) {
            $v=$m['acceptance'][$key]??null;
            demand(is_string($v) && strlen($v)>=5 && strlen($v)<=200 && !preg_match('/UNASSIGNED|UNKNOWN|PLACEHOLDER|<|>/i',$v),'ACCEPTANCE');
        }
        demand($m['acceptance']['primary']!==$m['acceptance']['backup'],'COVERAGE');
        foreach (['app'=>'Microsoft.Web/sites','database'=>'Microsoft.DBforMySQL/flexibleServers'] as $kind=>$type)
            demand(preg_match('~\A/subscriptions/[a-f0-9-]{36}/resourceGroups/[A-Za-z0-9_-]+/providers/'.preg_quote($type,'~').'/bushisa-stage-[a-z0-9-]+\z~D',$resources[$kind])===1,'MANAGED_RESOURCE');
        $app=basename($resources['app']); $server=basename($resources['database']);
        demand($origin==='https://'.$app.'.azurewebsites.net' && $db['host']===$server.'.mysql.database.azure.com','MANAGED_BINDING');
        $resourceParts=explode('/',$resources['app']);
        demand(getenv('WEBSITE_SITE_NAME')===$app && getenv('WEBSITE_HOSTNAME')===$app.'.azurewebsites.net'
            && getenv('WEBSITE_RESOURCE_GROUP')===$resourceParts[4]
            && in_array(getenv('WEBSITE_SLOT_NAME'),['Production',false,''],true),'APP_SERVICE_IDENTITY');
        $owner=$resources['platform_owner']??null;
        demand(is_string($owner) && $owner!=='' && getenv('WEBSITE_OWNER_NAME')===$owner
            && preg_match('/(?<![A-Za-z0-9])'.preg_quote($resourceParts[2],'/').'(?![A-Za-z0-9])/i',$owner)===1,'SUBSCRIPTION_BINDING');
        $web=realpath(__DIR__.'/..');
        foreach ([$file,$root] as $path) demand(!str_starts_with(strtolower($path),strtolower($web.DIRECTORY_SEPARATOR)),'OUTSIDE_WEB_ROOT');
        demand(is_string(getenv('DB_SSL_CA')) && getenv('DB_SSL_CA')!=='' && is_readable((string)getenv('DB_SSL_CA')),'CA');
        demand(is_string($db['ca_sha256']??null) && preg_match('/\A[a-f0-9]{64}\z/D',$db['ca_sha256'])===1
            && hash_equals($db['ca_sha256'],hash_file('sha256',(string)getenv('DB_SSL_CA'))),'CA_PIN');
    }
    // Reuse the existing disposable guard, without relaxing its independent rules.
    require_once __DIR__.'/../tests/support/disposable_database.php';
    \bushisa_require_disposable_test_database('Guarded stage');
    $m['_role']=$role; $m['_intent']=$intent; $m['_sessions']=$sessions;
    return $m;
}

/** Read-only identity proof precedes any fixture or application mutation. */
function database(\PDO $pdo, array $m, bool $requireMarker=true): array
{
    $r=$pdo->query('SELECT DATABASE() db,@@server_uuid server_uuid,CURRENT_USER() principal,VERSION() version')->fetch(\PDO::FETCH_ASSOC);
    demand(is_array($r) && $r['db']===$m['database']['name'] && $r['server_uuid']===$m['database']['server_uuid']
        && $r['principal']===$m['roles'][$m['_role']]['principal'],'CONNECTED_IDENTITY');
    if ($m['mode']==='managed') {
        $tls=$pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(\PDO::FETCH_NUM);
        demand(is_array($tls) && !empty($tls[1]),'TLS');
    }
    if ($requireMarker) {
        $s=$pdo->query('SELECT run_id,binding_id,app_resource,db_resource,namespace FROM bushisa_stage_identity WHERE singleton=1');
        $marker=$s->fetch(\PDO::FETCH_ASSOC);
        demand(is_array($marker) && $marker['run_id']===$m['run_id'] && $marker['binding_id']===$m['database']['binding_id']
            && $marker['app_resource']===$m['resources']['app'] && $marker['db_resource']===$m['resources']['database']
            && $marker['namespace']===$m['storage']['namespace'],'DATABASE_MARKER');
    }
    // Do not turn a matching username into assumed least privilege.
    foreach ($pdo->query('SHOW GRANTS')->fetchAll(\PDO::FETCH_COLUMN) as $grant) {
        demand(!preg_match('/WITH GRANT OPTION|GRANT (?!USAGE ON )[^\n]* ON \*\.\*/i',$grant),'GLOBAL_GRANTS');
        demand(preg_match('/ ON (.+?) TO /',$grant,$target)===1 && !preg_match('/\bPROXY\b/i',$grant),'INHERITED_ROLE');
        $object=str_replace('`','',$target[1]); $object=preg_replace('/\A(?:PROCEDURE|FUNCTION) /','',$object);
        demand(str_starts_with($grant,'GRANT USAGE ON *.* TO ') || str_starts_with($object,$m['database']['name'].'.'),'FOREIGN_GRANTS');
        if (!in_array($m['_role'],['fixture','migration'],true)) {
            demand(!preg_match('/\b(CREATE|ALTER|DROP|TRIGGER|SUPER|FILE|PROCESS|ALL PRIVILEGES)\b/i',$grant),'DDL_GRANTS');
            if (preg_match('/\b(INSERT|UPDATE|DELETE)\b/i',$grant))
                demand($object!==$m['database']['name'].'.*' && !str_starts_with($object,$m['database']['name'].'.bushisa_stage_'),'MARKER_WRITER');
        }
        if ($m['_role']==='runner') demand(!preg_match('/\b(INSERT|UPDATE|DELETE|EXECUTE|ALL PRIVILEGES)\b/i',$grant),'RUNNER_GRANTS');
    }
    return ['php'=>PHP_VERSION,'mysql'=>$r['version'],'mode'=>$m['mode']];
}

function schema(\PDO $pdo): void
{
    $versions=$pdo->query('SELECT version FROM schema_migrations ORDER BY version')->fetchAll(\PDO::FETCH_COLUMN);
    $expected=array_map(static fn(string $file):string=>pathinfo($file,PATHINFO_FILENAME),glob(__DIR__.'/../database/migrations/*.sql')?:[]);
    sort($expected);
    demand($versions===$expected && in_array('016_password_reset',$versions,true),'MIGRATIONS');
}

function account(\PDO $pdo, array $m, int $id, string $generation, string $email): bool
{
    if (!in_array(strtolower($email),$m['recipients'],true)) return false;
    $s=$pdo->prepare('SELECT a.account_generation FROM bushisa_stage_accounts a JOIN users u ON u.id=a.account_id AND u.signup_attempt_key=a.account_generation WHERE a.account_id=? AND a.run_id=? AND a.recipient_hash=? AND LOWER(u.email)=?');
    $s->execute([$id,$m['run_id'],hash('sha256',strtolower($email)),strtolower($email)]);
    $bound=$s->fetchColumn();
    return is_string($bound) && hash_equals($bound,$generation);
}

function connect(array $m, bool $requireMarker=true): \PDO
{
    $s=\bushisa_database_settings();
    $pdo=new \PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$s['host'],$s['port'],$s['name']),$s['user'],$s['password'],\bushisa_disposable_database_pdo_options($s));
    database($pdo,$m,$requireMarker);
    $pdo->exec("SET time_zone='+00:00'");
    return $pdo;
}
