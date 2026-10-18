<?php
declare(strict_types=1);

namespace Bushisa\ManagedSession;

use PDO;
use RuntimeException;
use Throwable;

require_once __DIR__.'/signup_lifecycle.php';

/** This is deliberately NOT an operational session-handler selector. */
function requested(): bool { return getenv('BUSHISA_SESSION_SYNTHETIC')==='true'; }
function ref(): string { return bin2hex(random_bytes(32)); }
function q(PDO $p,string $sql,array $args=[]): \PDOStatement { return \bushisa_signup_query($p,$sql,$args); }

/** All guards run before the handler opens a connection. Never create a root. */
function configuration(): array
{
    if (!requested() || !\bushisa_q11_synthetic_enabled()) { throw new RuntimeException('SESSION_SYNTHETIC_GUARD'); }
    $root=(string)getenv('BUSHISA_SESSION_ROOT');
    $namespace=(string)getenv('BUSHISA_SESSION_NAMESPACE');
    $key=(string)getenv('BUSHISA_SESSION_KEY');
    $real=realpath($root); $base=realpath(dirname(__DIR__).'/.tmp');
    if (!preg_match('/\Ass_[a-f0-9]{32}\z/D',$namespace) || !preg_match('/\A[a-f0-9]{64}\z/D',$key)
        || $real===false || $base===false || $real!==$root || is_link($root)
        || !str_starts_with(str_replace('\\','/',$real),str_replace('\\','/',$base).'/sf-storage-')
        || dirname($real)!==$base || basename($real)!=='sf-storage-'.substr($namespace,3)
        || is_link($root.'/.synthetic-session-store')
        || !is_file($root.'/.synthetic-session-store')
        || @file_get_contents($root.'/.synthetic-session-store')!==$namespace) {
        throw new RuntimeException('SESSION_STORE_GUARD');
    }
    // Detect linked ancestors too; no untrusted writers may share this fixture root.
    for($part=$root;strlen($part)>=strlen($base);$part=dirname($part)) {
        if (is_link($part)) { throw new RuntimeException('SESSION_STORE_GUARD'); }
        if ($part===$base) { break; }
    }
    return ['root'=>$root,'namespace'=>$namespace,'key'=>hex2bin($key)];
}

function connection(): PDO
{
    configuration();
    $p=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',getenv('DB_HOST'),getenv('DB_PORT'),getenv('DB_NAME')),
        (string)getenv('DB_USER'),(string)getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $p->exec("SET time_zone='+00:00'");
    // A second admission check must see a clock/authority update committed while
    // bounded local I/O ran, including the guarded synthetic clock fixture.
    $p->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED'); return $p;
}

/** account -> binding -> manifest. Caller already holds the account lock. */
function authority(PDO $p,array $b): ?array
{
    if ($b['account_id']===null) { return null; }
    $u=q($p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$b['account_id']])->fetch();
    if (!$u || !is_string($u['signup_attempt_key']) || !hash_equals((string)$b['account_key'],$u['signup_attempt_key'])
        || !isset($u['session_version']) || (int)$u['session_version']!==(int)$b['auth_version']
        || !\bushisa_signup_user_live($u)) { throw new RuntimeException('SESSION_AUTHORITY_CHANGED'); }
    $g=q($p,'SELECT * FROM signup_attempt_identities WHERE account_id=? AND attempt_key=?',[$u['id'],$b['account_key']])->fetch();
    if (!$g || \bushisa_signup_time($g['registered_at'])!==\bushisa_signup_time($u['created_at'])) { throw new RuntimeException('SESSION_GENERATION_REQUIRED'); }
    if (in_array($u['account_status'],['pending_email','pending_onboarding'],true)) {
        $l=q($p,'SELECT * FROM signup_lifecycle WHERE account_id=?',[$u['id']])->fetch();
        $due=$l?\bushisa_signup_stage_due($u,$l):null;
        if ($due===null || $l['registered_at']!==$g['registered_at'] || \bushisa_signup_now($p)>=$due) { throw new RuntimeException('SESSION_ATTEMPT_EXPIRED_OR_UNKNOWN'); }
    } elseif ($u['account_status']!=='active' || (int)$u['is_verified']!==1) { throw new RuntimeException('SESSION_SCOPE_REQUIRED'); }
    return $u;
}

/** Record Q11's original component obligation, never QE's completion receipt. */
function expire_locked(PDO $p,array $u,string $receipt,string $effective,string $due): void
{
    if (!\operational_table_exists($p,'session_copy_obligations') || empty($u['signup_attempt_key'])) { return; }
    $id=hash('sha256','session_copies_v1|'.$receipt.'|'.$u['signup_attempt_key']);
    q($p,'INSERT IGNORE INTO session_copy_obligations (event_ref,account_id,account_key,source_receipt,effective_at,purge_due_at,policy_ref) VALUES (?,?,?,?,?,?,"Q11")',
        [$id,$u['id'],$u['signup_attempt_key'],$receipt,$effective,$due]);
    $o=q($p,'SELECT * FROM session_copy_obligations WHERE event_ref=? FOR UPDATE',[$id])->fetch();
    if (!$o || (int)$o['account_id']!==(int)$u['id'] || $o['account_key']!==$u['signup_attempt_key']
        || $o['source_receipt']!==$receipt || $o['effective_at']!==$effective || $o['purge_due_at']!==$due) { throw new RuntimeException('SESSION_OBLIGATION_CONFLICT'); }
    q($p,'UPDATE session_bindings SET state="retired",retired_at=COALESCE(retired_at,?),reason_code="Q11_EXPIRED",revision=revision+1 WHERE account_id=? AND account_key=? AND state="live"',
        [\bushisa_signup_now($p),$u['id'],$u['signup_attempt_key']]);
}

/** Called only inside the actual successful verification/onboarding transaction. */
function transition_locked(PDO $p,int $id,int $old,int $new,string $kind): void
{
    $h=$GLOBALS['bushisa_managed_handler']??null;
    if (!$h instanceof Handler || !$h->context || (int)($h->context['account_id']??0)!==$id) { return; }
    if (!$p->inTransaction() || !in_array($kind,['email_approval','adult_onboarding'],true) || $new!==$old+1) { throw new RuntimeException('SESSION_TRANSITION_REQUIRED'); }
    $b=q($p,'SELECT * FROM session_bindings WHERE session_ref=? FOR UPDATE',[$h->context['session_ref']])->fetch();
    if (!$b || $b['state']!=='live' || (int)$b['auth_version']!==$old || (int)$b['revision']!==(int)$h->context['revision']) { return; }
    $u=q($p,'SELECT * FROM users WHERE id=?',[$id])->fetch();
    if (!$u || $u['signup_attempt_key']!==$b['account_key'] || (int)$u['session_version']!==$new) { throw new RuntimeException('SESSION_TRANSITION_REQUIRED'); }
    $event=ref();
    q($p,'INSERT INTO session_handoffs (event_ref,session_ref,account_key,old_version,new_version,expected_revision,event_type,state,created_at) VALUES (?,?,?,?,?,?,?,"pending",?)',
        [$event,$b['session_ref'],$b['account_key'],$old,$new,$b['revision'],$kind,\bushisa_signup_now($p)]);
    $h->handoff=$event; // Private process memory, not a posted/session role assertion.
}

/** Guarded immutable local objects; no native/remote handler fallback. */
final class Handler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    public ?array $context=null;
    public ?string $handoff=null;
    public bool $failed=false;
    public string $error='';
    private ?string $seed=null;
    private int $lazyCalls=0;
    private array $config;
    public function __construct(private PDO $p) { $this->config=configuration(); }
    public function open(string $path,string $name): bool { return true; }
    public function close(): bool { return true; }
    public function gc(int $max_lifetime): int|false { return 0; } // Not a retention policy or cleanup proof.
    public function hmac(string $id): string
    {
        if (!preg_match('/\A[A-Za-z0-9,-]{16,128}\z/D',$id)) { throw new RuntimeException('SESSION_IDENTIFIER_REFUSED'); }
        return hash_hmac('sha256',$this->config['namespace']."\0".$id,$this->config['key']);
    }
    private function payloadKey(): string { return hash_hkdf('sha256',$this->config['key'],32,'session-payload-v1',$this->config['namespace']); }
    private function lookup(string $id): ?array
    { return q($this->p,'SELECT * FROM session_bindings WHERE namespace_id=? AND key_version=1 AND sid_hmac=?',[$this->config['namespace'],$this->hmac($id)])->fetch()?:null; }
    public function validateId(string $id): bool
    {
        try { return $this->lookup($id)!==null; }
        catch(Throwable) { $this->fail('SESSION_REGISTRY_UNAVAILABLE'); return false; }
    }
    private function fail(string $code): void { $this->failed=true; $this->error=$code; }
    private function tx(callable $work): mixed { return \bushisa_signup_transaction($this->p,$work); }
    private function locked(array $expected): array
    {
        // Never acquire a session-file mutex before this ordering.
        $u=authority($this->p,$expected);
        $b=q($this->p,'SELECT * FROM session_bindings WHERE session_ref=? FOR UPDATE',[$expected['session_ref']])->fetch();
        if (!$b || $b['namespace_id']!==$this->config['namespace'] || $b['key_version']!=1 || $b['state']!=='live'
            || $b['account_key']!==$expected['account_key'] || $b['account_id']!==$expected['account_id']
            || $b['auth_version']!==$expected['auth_version'] || $b['binding_epoch']!==$expected['binding_epoch']
            || (int)$b['revision']!==(int)$expected['revision']) { throw new RuntimeException('SESSION_WRITE_FENCED'); }
        return [$b,$u];
    }
    private function create(string $id,?array $from=null): array
    {
        $b=['session_ref'=>ref(),'sid_hmac'=>$this->hmac($id),'namespace_id'=>$this->config['namespace'],'key_version'=>1,
            'account_id'=>$from['account_id']??null,'account_key'=>$from['account_key']??null,'auth_version'=>$from['auth_version']??null,
            'binding_epoch'=>1,'revision'=>0,'state'=>'live','current_copy'=>null];
        q($this->p,'INSERT INTO session_bindings (session_ref,sid_hmac,namespace_id,key_version,account_id,account_key,auth_version,state,created_at) VALUES (?,?,?,?,?,?,?,"live",?)',
            [$b['session_ref'],$b['sid_hmac'],$b['namespace_id'],1,$b['account_id'],$b['account_key'],$b['auth_version'],\bushisa_signup_now($this->p)]);
        return $b;
    }
    private function payload(array $data,array $b,?array $u): array
    {
        $allowed=['csrf_token','last_activity','user_id','session_version','account_generation','role'];
        if (array_diff(array_keys($data),$allowed)) { throw new RuntimeException('SESSION_PAYLOAD_SCOPE'); }
        if ($u===null) {
            if (array_intersect(array_keys($data),['user_id','session_version','account_generation','role'])) { throw new RuntimeException('SESSION_BINDING_REQUIRED'); }
        } else {
            if (($data['user_id']??null)!==(int)$b['account_id'] || ($data['session_version']??null)!==(int)$b['auth_version']
                || ($data['account_generation']??null)!==$b['account_key']) { throw new RuntimeException('SESSION_BINDING_REQUIRED'); }
            $data['role']=$u['role']??'user';
        }
        if (isset($data['csrf_token']) && (!is_string($data['csrf_token']) || !preg_match('/\A[a-f0-9]{64}\z/D',$data['csrf_token']))) { throw new RuntimeException('SESSION_PAYLOAD_SCOPE'); }
        if (isset($data['last_activity']) && !is_int($data['last_activity'])) { throw new RuntimeException('SESSION_PAYLOAD_SCOPE'); }
        return $data;
    }
    private function decode(string $data): array
    {
        if (strlen($data)>8192) { throw new RuntimeException('SESSION_PAYLOAD_LIMIT'); }
        $a=@unserialize($data,['allowed_classes'=>false,'max_depth'=>4]);
        if (!is_array($a)) { throw new RuntimeException('SESSION_PAYLOAD_INVALID'); } return $a;
    }
    private function path(string $copy,bool $exists=false): string
    {
        configuration();
        if (!preg_match('/\A[a-f0-9]{64}\z/D',$copy)) { throw new RuntimeException('SESSION_LOCATOR_REFUSED'); }
        $path=$this->config['root'].DIRECTORY_SEPARATOR.$copy.'.sealed'; clearstatcache(true,$path);
        if (is_link($path)) { throw new RuntimeException('SESSION_OBJECT_REFUSED'); }
        if (file_exists($path)) {
            $s=@lstat($path);
            if (!is_file($path) || !$s || $s['nlink']!==1 || realpath($path)!==$path || $s['size']>12288) { throw new RuntimeException('SESSION_OBJECT_REFUSED'); }
        } elseif ($exists) { throw new RuntimeException('SESSION_COPY_UNAVAILABLE'); }
        return $path;
    }
    private function load(array $b): string
    {
        if ($b['current_copy']===null) {
            if ($this->seed!==null && $this->context && $this->context['session_ref']===$b['session_ref']) { return $this->seed; }
            if ($b['account_id']!==null) { throw new RuntimeException('SESSION_COPY_UNAVAILABLE'); }
            return serialize([]);
        }
        $m=q($this->p,'SELECT * FROM session_copy_manifests WHERE copy_ref=?',[$b['current_copy']])->fetch();
        if (!$m || $m['session_ref']!==$b['session_ref'] || $m['binding_epoch']!==$b['binding_epoch'] || $m['state']!=='published'
            || (int)$m['expected_revision']+1!==(int)$b['revision']
            || $m['namespace_id']!==$this->config['namespace'] || $m['adapter_version']!=='synthetic_local_v1') { throw new RuntimeException('SESSION_MANIFEST_REQUIRED'); }
        $sealed=@file_get_contents($this->path($m['copy_ref'],true));
        if (!is_string($sealed) || strlen($sealed)<28) { throw new RuntimeException('SESSION_COPY_INVALID'); }
        $plain=openssl_decrypt(substr($sealed,28),'aes-256-gcm',$this->payloadKey(),OPENSSL_RAW_DATA,substr($sealed,0,12),substr($sealed,12,16),$m['copy_ref']);
        if (!is_string($plain)) { throw new RuntimeException('SESSION_COPY_INVALID'); } return $plain;
    }
    public function read(string $id): string|false
    {
        try {
            if ($this->failed) { return false; }
            $b=$this->lookup($id);
            if (!$b) { $b=$this->tx(fn()=>$this->create($id)); }
            $data=$this->tx(function()use($b) {
                [$current,$u]=$this->locked($b);
                return serialize($this->payload($this->decode($this->load($current)),$current,$u));
            });
            $this->context=$b; return $data;
        } catch(Throwable $e) { $this->fail($this->safeError($e,'SESSION_READ_FENCED')); return false; }
    }
    public function write(string $id,string $data): bool
    {
        try {
            if ($this->failed || !$this->context || !hash_equals($this->context['sid_hmac'],$this->hmac($id))) { throw new RuntimeException('SESSION_BINDING_REQUIRED'); }
            $b=$this->context; $copy=ref(); $input=$this->decode($data);
            \bushisa_signup_test_point($this->p,'session_write');
            // Intent commits BEFORE any object exists. A crash never hides a copy.
            $this->tx(function()use($b,$copy,$input) {
                [$current,$u]=$this->locked($b); $this->payload($input,$current,$u);
                q($this->p,'INSERT INTO session_copy_manifests (copy_ref,session_ref,binding_epoch,expected_revision,namespace_id,adapter_version,instance_id,state,created_at) VALUES (?,?,?,?,?,"synthetic_local_v1","fixture_shared","reserved",?)',
                    [$copy,$b['session_ref'],$b['binding_epoch'],$b['revision'],$this->config['namespace'],\bushisa_signup_now($this->p)]);
            });
            $this->fault('after_intent');
            $this->tx(function()use($b,$copy,$input) {
                [$current,$u]=$this->locked($b);
                $plain=serialize($this->payload($input,$current,$u));
                $iv=random_bytes(12); $tag='';
                $cipher=openssl_encrypt($plain,'aes-256-gcm',$this->payloadKey(),OPENSSL_RAW_DATA,$iv,$tag,$copy);
                if (!is_string($cipher)) { throw new RuntimeException('SESSION_STORE_FAILED'); }
                $this->fault('storage_failure');
                $f=@fopen($this->path($copy),'xb');
                if (!$f) { throw new RuntimeException('SESSION_STORE_FAILED'); }
                try {
                    @chmod($this->path($copy),0600); $sealed=$iv.$tag.$cipher;
                    if (@fwrite($f,$sealed)!==strlen($sealed) || !@fflush($f)) { throw new RuntimeException('SESSION_STORE_FAILED'); }
                } finally { @fclose($f); }
                $this->fault('after_object');
                // Time may advance while I/O is blocked. Recheck before publication.
                authority($this->p,$b);
                q($this->p,'UPDATE session_copy_manifests SET state="published",published_at=? WHERE copy_ref=? AND state="reserved"',[\bushisa_signup_now($this->p),$copy]);
                q($this->p,'UPDATE session_bindings SET current_copy=?,revision=revision+1 WHERE session_ref=? AND revision=? AND state="live"',[$copy,$b['session_ref'],$b['revision']]);
            });
            $this->context=$this->lookup($id); $this->seed=null; return true;
        } catch(Throwable $e) { $this->fail($this->safeError($e,'SESSION_WRITE_FENCED')); return false; }
    }
    public function updateTimestamp(string $id,string $data): bool { $this->lazyCalls++; return $this->write($id,$data); }
    public function destroy(string $id): bool
    {
        try {
            $b=$this->lookup($id); if (!$b) { return true; }
            // Closed fixture rendezvous for a delayed handoff versus logout.
            \bushisa_signup_test_point($this->p,'session_resume');
            \bushisa_signup_test_point($this->p,'session_write');
            $this->tx(function()use($b) {
                if ($b['account_id']!==null) { q($this->p,'SELECT id FROM users WHERE id=? FOR UPDATE',[$b['account_id']])->fetch(); }
                q($this->p,'SELECT session_ref FROM session_bindings WHERE session_ref=? FOR UPDATE',[$b['session_ref']])->fetch();
                q($this->p,'UPDATE session_bindings SET state="retired",revision=revision+1,retired_at=COALESCE(retired_at,?),reason_code="CURRENT_LOGOUT" WHERE session_ref=? AND state="live"',[\bushisa_signup_now($this->p),$b['session_ref']]);
            });
            // No physical erasure claim. Retained manifests need the later consumer.
            $this->context=null; return true;
        } catch(Throwable) { $this->fail('SESSION_RETIRE_FAILED'); return false; }
    }
    public function admit(int $id,string $verifiedPasswordHash,?string $verifiedGeneration,string $verifiedCreated): array
    {
        if (!$this->context || $this->context['account_id']!==null || $this->failed) { throw new RuntimeException('SESSION_FRESH_BINDING_REQUIRED'); }
        $b=$this->context;
        $this->context=$this->tx(function()use($b,$id,$verifiedPasswordHash,$verifiedGeneration,$verifiedCreated) {
            $u=q($this->p,'SELECT * FROM users WHERE id=? FOR UPDATE',[$id])->fetch();
            if (!$u || !is_string($verifiedGeneration) || !is_string($u['signup_attempt_key'])
                || !hash_equals($verifiedGeneration,$u['signup_attempt_key'])
                || \bushisa_signup_time($verifiedCreated)!==\bushisa_signup_time($u['created_at'])
                || !hash_equals($u['password_hash'],$verifiedPasswordHash)) { throw new RuntimeException('SESSION_ADMISSION_DENIED'); }
            $candidate=$b; $candidate['account_id']=$id; $candidate['account_key']=$u['signup_attempt_key']; $candidate['auth_version']=(int)$u['session_version'];
            authority($this->p,$candidate); $this->locked($b);
            q($this->p,'UPDATE session_bindings SET account_id=?,account_key=?,auth_version=?,binding_epoch=binding_epoch+1,revision=revision+1,current_copy=NULL WHERE session_ref=?',[$id,$candidate['account_key'],$candidate['auth_version'],$b['session_ref']]);
            return q($this->p,'SELECT * FROM session_bindings WHERE session_ref=?',[$b['session_ref']])->fetch();
        });
        $payload=$this->identity($this->context); $this->seed=serialize($payload); return $payload;
    }
    private function identity(array $b): array
    {
        $data=['csrf_token'=>ref(),'last_activity'=>time()];
        if ($b['account_id']!==null) { $u=authority($this->p,$b); $data+=['user_id'=>(int)$b['account_id'],'account_generation'=>$b['account_key'],'session_version'=>(int)$b['auth_version'],'role'=>$u['role']??'user']; }
        return $data;
    }
    /** Caller aborts old PHP handle and starts the reserved new ID. */
    public function rotate(string $newId,bool $clearIdentity=false): array
    {
        if (!$this->context || $this->failed) { throw new RuntimeException('SESSION_BINDING_REQUIRED'); }
        $old=$this->context;
        \bushisa_signup_test_point($this->p,'session_write');
        $new=$this->tx(function()use($old,$newId,$clearIdentity) {
            $this->locked($old); $new=$this->create($newId,$clearIdentity?null:$old);
            q($this->p,'INSERT INTO session_lineage (event_ref,old_ref,new_ref,event_type,created_at) VALUES (?,?,?,"regeneration",?)',[ref(),$old['session_ref'],$new['session_ref'],\bushisa_signup_now($this->p)]);
            q($this->p,'UPDATE session_bindings SET state="retired",revision=revision+1,retired_at=?,reason_code="REGENERATED" WHERE session_ref=?',[\bushisa_signup_now($this->p),$old['session_ref']]);
            return $new;
        });
        $this->context=$new; $payload=$this->tx(fn()=>$this->identity($new)); $this->seed=serialize($payload); return $payload;
    }
    public function resume(int $id,int $old,int $new): bool
    {
        try {
            if (!$this->handoff || !$this->context || $this->failed) { return false; }
            \bushisa_signup_test_point($this->p,'session_resume');
            $b=$this->context; $event=$this->handoff;
            $this->context=$this->tx(function()use($b,$id,$old,$new,$event) {
                $candidate=$b; $candidate['auth_version']=$new;
                authority($this->p,$candidate);
                $current=q($this->p,'SELECT * FROM session_bindings WHERE session_ref=? FOR UPDATE',[$b['session_ref']])->fetch();
                $h=q($this->p,'SELECT * FROM session_handoffs WHERE event_ref=? FOR UPDATE',[$event])->fetch();
                if (!$current || !$h || $h['state']!=='pending' || $current['state']!=='live' || (int)$current['account_id']!==$id
                    || (int)$h['old_version']!==$old || (int)$h['new_version']!==$new || (int)$current['auth_version']!==$old
                    || $current['account_key']!==$h['account_key'] || (int)$current['revision']!==(int)$h['expected_revision']) { throw new RuntimeException('SESSION_HANDOFF_FENCED'); }
                q($this->p,'UPDATE session_bindings SET auth_version=?,binding_epoch=binding_epoch+1,revision=revision+1,current_copy=NULL WHERE session_ref=?',[$new,$b['session_ref']]);
                q($this->p,'UPDATE session_handoffs SET state="consumed",completed_at=? WHERE event_ref=?',[\bushisa_signup_now($this->p),$event]);
                return q($this->p,'SELECT * FROM session_bindings WHERE session_ref=?',[$b['session_ref']])->fetch();
            });
            $this->seed=serialize($this->tx(fn()=>$this->identity($this->context))); $this->handoff=null; return true;
        } catch(Throwable) { $this->fail('SESSION_HANDOFF_FENCED'); return false; }
    }
    public function current(): bool
    {
        try { if (!$this->context || $this->failed) { return false; } $this->tx(fn()=>$this->locked($this->context)); return true; }
        catch(Throwable) { $this->fail('SESSION_AUTHORITY_CHANGED'); return false; }
    }
    private function fault(string $point): void
    {
        // The entire adapter already requires the closed disposable/fake-mail guard.
        if (getenv('BUSHISA_SESSION_CRASH')===$point) { exit(77); }
        if ($point==='storage_failure' && getenv('BUSHISA_SESSION_STORE_FAIL')==='true') { throw new RuntimeException('SESSION_STORE_FAILED'); }
    }
    public function testIdentity(): array
    {
        configuration();
        return ['pid'=>getmypid(),'connection_id'=>(int)$this->p->query('SELECT CONNECTION_ID()')->fetchColumn(),'sapi'=>PHP_SAPI,'lazy_calls'=>$this->lazyCalls];
    }
    private function safeError(Throwable $e,string $fallback): string
    { return $e instanceof RuntimeException && preg_match('/\ASESSION_[A-Z_]+\z/D',$e->getMessage())?$e->getMessage():$fallback; }
}

function install(): Handler
{
    configuration();
    if (session_status()!==PHP_SESSION_NONE) { throw new RuntimeException('SESSION_UNMANAGED_ACTIVE_HANDLER'); }
    $h=new Handler(connection());
    ini_set('session.serialize_handler','php_serialize');
    ini_set('session.save_path',''); // No unused native locator in PHP diagnostics.
    if (!session_set_save_handler($h,false)) { throw new RuntimeException('SESSION_HANDLER_UNAVAILABLE'); }
    register_shutdown_function(static function(): void {
        // Own the implicit close too: PHP's warning must not expose a locator.
        // The response buffer observes Handler::failed and emits only a fixed code.
        if (session_status()===PHP_SESSION_ACTIVE) { @session_write_close(); }
    });
    $GLOBALS['bushisa_managed_handler']=$h; return $h;
}
