<?php
declare(strict_types=1);
namespace Bushisa\ManagedSession;

use RuntimeException;

/** Only the guarded private immutable synthetic namespace. All writer/deleter
 * calls also hold the same account fence; no untrusted filesystem writers are
 * supported. A lost root or identity is never an empty store. */
final class Store
{
    public const MAGIC="BSS2\0";
    public function __construct(private array $config) {}
    public function version(): string
    {
        $current=configuration();
        if($current['namespace']!==$this->config['namespace'] || $current['root']!==$this->config['root'] || !hash_equals($current['key'],$this->config['key'])) { throw new RuntimeException('SESSION_STORE_CHANGED'); }
        $s=@lstat($current['root']);$marker=@lstat($current['root'].'/.synthetic-session-store');
        if(!$s || !$marker || $s['ino']<=0 || $marker['ino']<=0 || $marker['nlink']!==1) { throw new RuntimeException('SESSION_STORE_IDENTITY_REQUIRED'); }
        return hash_hmac('sha256',implode('|',[$current['root'],$current['namespace'],$s['dev'],$s['ino'],$marker['dev'],$marker['ino']]),$current['key']);
    }
    public function path(string $copy): string
    {
        $this->version();
        if(!preg_match('/\A[a-f0-9]{64}\z/D',$copy)) { throw new RuntimeException('SESSION_LOCATOR_REFUSED'); }
        $path=$this->config['root'].DIRECTORY_SEPARATOR.$copy.'.sealed';clearstatcache(true,$path);
        if(is_link($path)) { throw new RuntimeException('SESSION_OBJECT_REFUSED'); }return $path;
    }
    /** Negative entry lookup only within this newly owned fixture namespace.
     * No directory adoption, serialization scan, native GC or broad deletion. */
    private function absent(string $copy): bool
    {
        $before=$this->version();$dir=@opendir($this->config['root']);
        if(!$dir) { throw new RuntimeException('SESSION_STORE_UNAVAILABLE'); }
        $found=false;$count=0;
        try { while(($entry=readdir($dir))!==false) {
            if(++$count>10000) { throw new RuntimeException('SESSION_STORE_LOOKUP_LIMIT'); }
            if($entry===$copy.'.sealed') { $found=true;break; }
        }} finally { closedir($dir); }
        if(!hash_equals($before,$this->version())) { throw new RuntimeException('SESSION_STORE_CHANGED'); }return !$found;
    }
    /** Returns a held nonblocking OS lock and an opaque physical identity token.
     * Read only the fixed generation header, never session contents. */
    public function probe(string $copy,string $generation): array
    {
        if(!preg_match('/\A[a-f0-9]{64}\z/D',$generation)) { throw new RuntimeException('SESSION_OBJECT_GENERATION_REQUIRED'); }
        $path=$this->path($copy);$s=@lstat($path);
        if(!$s) {
            if(!$this->absent($copy)) { throw new RuntimeException('SESSION_OBJECT_UNAVAILABLE'); }
            return ['handle'=>null,'identity'=>'absent','path'=>$path];
        }
        if(($s['mode']&0170000)!==0100000 || $s['nlink']!==1 || $s['ino']<=0 || $s['size']<37 || $s['size']>12288 || realpath($path)!==$path) { throw new RuntimeException('SESSION_OBJECT_REFUSED'); }
        $f=@fopen($path,'rb');if(!$f) { throw new RuntimeException('SESSION_OBJECT_UNAVAILABLE'); }
        try {
            if(!@flock($f,LOCK_EX|LOCK_NB)) { throw new RuntimeException('SESSION_OBJECT_LOCKED'); }
            $h=@fstat($f);$header=@fread($f,37);
            if(!$h || $h['dev']!==$s['dev'] || $h['ino']!==$s['ino'] || $h['nlink']!==1 || $header!==self::MAGIC.hex2bin($generation)) { throw new RuntimeException('SESSION_OBJECT_CHANGED'); }
            $identity=hash_hmac('sha256',implode('|',[$h['dev'],$h['ino'],$h['size'],$h['mtime'],$h['ctime'],$generation]),$this->config['key']);
            return ['handle'=>$f,'identity'=>$identity,'path'=>$path];
        } catch(\Throwable $e) { @fclose($f);throw $e; }
    }
    public function inspect(string $copy,string $generation): string
    { $o=$this->probe($copy,$generation);if(is_resource($o['handle']))@fclose($o['handle']);return $o['identity']; }
    public function erase(string $copy,string $generation,string $expected,callable $beforeUnlink): void
    {
        $o=$this->probe($copy,$generation);
        try {
            if($o['identity']==='absent') { return; } // Reconcile an interrupted unlink.
            if($expected==='absent' || !hash_equals($expected,$o['identity'])) { throw new RuntimeException('SESSION_OBJECT_CHANGED'); }
            $beforeUnlink();
            // Current account + copy row fence and held OS lock cover this
            // immutable private namespace. No other writer replaces objects.
            $s=@lstat($o['path']);$h=@fstat($o['handle']);
            if(!$s || !$h || $s['dev']!==$h['dev'] || $s['ino']!==$h['ino'] || is_link($o['path'])) { throw new RuntimeException('SESSION_OBJECT_CHANGED'); }
            if(!@unlink($o['path'])) { throw new RuntimeException('SESSION_OBJECT_DELETE_FAILED'); }
        } finally { if(is_resource($o['handle']))@fclose($o['handle']); }
        if(!$this->absent($copy)) { throw new RuntimeException('SESSION_OBJECT_ABSENCE_UNVERIFIED'); }
    }
}
