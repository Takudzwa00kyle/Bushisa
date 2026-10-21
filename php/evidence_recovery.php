<?php
declare(strict_types=1);
namespace Bushisa\Evidence;

use PDO;

require_once __DIR__.'/evidence_lifecycle.php';

/** This is an interface exercise, NOT an independently durable storage driver.
 * Paths are server configuration, constrained to the owned synthetic directory.
 * Nothing here can acknowledge a production journal or release production. */
function journal_path(): string
{
    synthetic_guard();
    $root=realpath(__DIR__.'/../.tmp'); $dir=realpath((string)getenv('BUSHISA_L03A_JOURNAL'));
    if (!$root || !$dir || !str_starts_with(str_replace('\\','/',$dir).'/',str_replace('\\','/',$root).'/')
        || !str_starts_with(basename($dir),'l03a-')) { throw new Failure('JOURNAL_DESTINATION_REFUSED',503); }
    return $dir.'/synthetic-journal.json';
}
function with_journal(callable $f): mixed
{
    $path=journal_path(); $lock=fopen($path.'.lock','c+b');
    if (!$lock || !flock($lock,LOCK_EX)) { throw new Failure('JOURNAL_UNAVAILABLE',503); }
    try { return $f($path); } finally { flock($lock,LOCK_UN); fclose($lock); }
}
function read_journal(string $path): array
{
    if (!is_file($path) || filesize($path)>4*1024*1024) { throw new Failure('JOURNAL_MISSING_OR_OVERSIZED',503); }
    try { $j=json_decode((string)file_get_contents($path),true,24,JSON_THROW_ON_ERROR); }
    catch (\Throwable) { throw new Failure('JOURNAL_INVALID',503); }
    if (!is_array($j) || ($j['format']??null)!=='synthetic-l03a-journal-v1' || !is_array($j['events']??null)
        || !is_int($j['watermark']??null) || !is_string($j['digest']??null)) { throw new Failure('JOURNAL_INVALID',503); }
    if (!preg_match('/\A[a-f0-9]{64}\z/D',(string)getenv('BUSHISA_L03A_RECOVERY_NAMESPACE'))
        || !is_string($j['namespace']??null) || !hash_equals((string)getenv('BUSHISA_L03A_RECOVERY_NAMESPACE'),$j['namespace'])) { throw new Failure('JOURNAL_NAMESPACE_REQUIRED',503); }
    $last=0; $digest=identity('empty-journal-v1'); $seen=[];
    foreach($j['events'] as $r) {
        $required=['sequence_id','event_id','item_key','source_revision','action_name','effective_at','ordinary_expires_at','preserve_until'];
        if (!is_array($r) || array_diff(array_keys($r),$required) || array_diff($required,array_keys($r))
            || !is_int($r['sequence_id']) || $r['sequence_id']<=$last || !is_int($r['source_revision']) || $r['source_revision']<1
            || !preg_match('/\A[a-f0-9]{64}\z/D',(string)$r['event_id']) || !preg_match('/\A[a-f0-9]{64}\z/D',(string)$r['item_key'])
            || isset($seen[$r['event_id']]) || !in_array($r['action_name'],['hold','release','purge'],true)) { throw new Failure('JOURNAL_CONFLICT',503); }
        foreach(['effective_at','ordinary_expires_at','preserve_until'] as $k) {
            if ($r[$k]===null) {
                if ($k==='effective_at' || ($k==='preserve_until' && $r['action_name']==='hold')) { throw new Failure('JOURNAL_CLOCK_INVALID',503); }
                continue;
            }
            $time=is_string($r[$k])?\DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u',$r[$k],new \DateTimeZone('UTC')):false;
            if (!$time || $time->format('Y-m-d H:i:s.u')!==$r[$k]) { throw new Failure('JOURNAL_CLOCK_INVALID',503); }
        }
        $last=$r['sequence_id']; $seen[$r['event_id']]=true;
        $digest=identity($digest,json_encode($r,JSON_THROW_ON_ERROR));
    }
    if ($last!==$j['watermark'] || !hash_equals($digest,$j['digest'])) { throw new Failure('JOURNAL_CONTINUITY_REQUIRED',503); }
    // A separate checkpoint catches a truncated yet internally consistent file.
    $checkpoint=is_file($path.'.head')?json_decode((string)file_get_contents($path.'.head'),true):null;
    if ($checkpoint!==['watermark'=>$last,'digest'=>$digest]) { throw new Failure('JOURNAL_CHECKPOINT_REQUIRED',503); }
    return $j;
}
function synthetic_journal_head(): array
{
    return with_journal(function(string $path): array { $j=read_journal($path); return ['watermark'=>$j['watermark'],'digest'=>$j['digest'],'namespace'=>$j['namespace']]; });
}
function publish(PDO $db): array
{
    synthetic_guard();
    $head=with_journal(function(string $path) use($db): array {
        $j=read_journal($path); $seen=[];
        $ns=query($db,'SELECT journal_namespace FROM evidence_recovery_state WHERE singleton=1')->fetchColumn();
        if (!is_string($ns) || !hash_equals($ns,$j['namespace'])) { throw new Failure('JOURNAL_NAMESPACE_REQUIRED',503); }
        foreach($j['events'] as $r) { $seen[$r['event_id']]=$r; }
        $rows=query($db,'SELECT sequence_id,event_id,item_key,source_revision,action_name,effective_at,ordinary_expires_at,preserve_until FROM evidence_recovery_outbox ORDER BY sequence_id')->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r) {
            $r['sequence_id']=(int)$r['sequence_id']; $r['source_revision']=(int)$r['source_revision'];
            if (isset($seen[$r['event_id']])) {
                if ($seen[$r['event_id']]!==$r) { throw new Failure('JOURNAL_EVENT_CONFLICT',503); }
                continue;
            }
            if ($r['sequence_id']<=$j['watermark']) { throw new Failure('JOURNAL_FRONTIER_CONFLICT',503); }
            $j['events'][]=$r; $j['watermark']=$r['sequence_id']; $j['digest']=identity($j['digest'],json_encode($r,JSON_THROW_ON_ERROR));
        }
        // Fail closed on a publication interrupted between these two atomic
        // replacements; repair needs independently verified continuity, not an
        // automatic acceptance of the shorter file. No durability claim.
        $temp=$path.'.'.bin2hex(random_bytes(6)).'.part';
        if (file_put_contents($temp,json_encode($j,JSON_THROW_ON_ERROR))===false || !rename($temp,$path)) { throw new Failure('JOURNAL_PUBLICATION_FAILED',503); }
        $head=['watermark'=>$j['watermark'],'digest'=>$j['digest']];
        $temp=$path.'.head.'.bin2hex(random_bytes(6)).'.part';
        if (file_put_contents($temp,json_encode($head,JSON_THROW_ON_ERROR))===false || !rename($temp,$path.'.head')) { throw new Failure('JOURNAL_PUBLICATION_FAILED',503); }
        return $head;
    });
    fault('after_journal_ack');
    transaction($db,function() use($db,$head): void {
        query($db,'UPDATE evidence_recovery_outbox SET published_at=COALESCE(published_at,?) WHERE sequence_id<=?',[now($db),$head['watermark']]);
    });
    return $head;
}

/** Simulated stale-restore replay. Caller cannot submit a watermark. The
 * external adapter supplies the complete current journal and checkpoint.
 * Replay never grants authority, revives a case, or touches account/pair state. */
function replay(PDO $db): array
{
    synthetic_guard();
    return with_journal(function(string $path) use($db): array {
        $j=read_journal($path);
        return transaction($db,function() use($db,$j): array {
            query($db,'INSERT IGNORE INTO evidence_recovery_state (singleton,ready) VALUES (1,0)');
            $ns=query($db,'SELECT journal_namespace FROM evidence_recovery_state WHERE singleton=1 FOR UPDATE')->fetchColumn();
            if (!is_string($ns) || !hash_equals($ns,$j['namespace'])) { throw new Failure('JOURNAL_NAMESPACE_REQUIRED',503); }
            query($db,'UPDATE evidence_recovery_state SET ready=0 WHERE singleton=1');
            $latest=[];
            foreach($j['events'] as $r) {
                // Preserve overlapping holds: replay aggregate state across
                // events, rather than letting release of one clear another.
                $latest[$r['item_key']][]=$r;
            }
            foreach($latest as $key=>$events) {
                $r=$events[array_key_last($events)];
                $i=query($db,'SELECT * FROM evidence_lifecycle_items WHERE item_key=? FOR UPDATE',[$key])->fetch(PDO::FETCH_ASSOC);
                if (!$i) { continue; } // Deleted copy remains absent; never recapture.
                if ((int)$i['source_revision']!==$r['source_revision']) { throw new Failure('RECOVERY_REVISION_CONFLICT',503); }
                if ($r['ordinary_expires_at']!==null) {
                    if ($i['ordinary_expires_at']!==null && $i['ordinary_expires_at']!==$r['ordinary_expires_at']) { throw new Failure('RECOVERY_DEADLINE_CONFLICT',503); }
                    query($db,'UPDATE evidence_lifecycle_items SET ordinary_expires_at=COALESCE(ordinary_expires_at,?),purge_due_at=COALESCE(purge_due_at,?) WHERE item_key=?',[$r['ordinary_expires_at'],$r['ordinary_expires_at'],$key]);
                }
                $purge=null; $until=null;
                foreach($events as $e) {
                    if ($e['action_name']==='purge') { $purge=$e; }
                    // hold events carry the full aggregate preserve-until at
                    // that transition, release events carry remaining bound.
                    $until=$e['preserve_until'];
                }
                if ($purge) {
                    if ($i['purged_at']===null) {
                        $s=source($db,$i);
                        if (!$s || !hash_equals((string)$i['source_hash'],source_hash($s))) { throw new Failure('RECOVERY_SOURCE_CHANGED',503); }
                        if ($i['item_kind']==='evidence') {
                            query($db,'UPDATE moderation_evidence SET evidence_excerpt=NULL,evidence_hash=NULL,purged_at=? WHERE id=?',[$purge['effective_at'],$i['source_id']]);
                        } else {
                            query($db,'UPDATE report_submissions SET explanation=NULL,information_hash=?,purged_at=? WHERE id=?',[identity('expired',(string)$i['source_id']),$purge['effective_at'],$i['source_id']]);
                            query($db,'UPDATE report_operations SET intent_hash=NULL WHERE submission_id=?',[$i['source_id']]);
                        }
                        query($db,'UPDATE evidence_lifecycle_items SET source_hash=NULL,state="purged",purged_at=? WHERE item_key=?',[$purge['effective_at'],$key]);
                    }
                    $r=$purge; $until=null;
                }
                query($db,'INSERT INTO evidence_recovery_overrides (item_key,source_revision,sequence_id,action_name,preserve_until,effective_at) VALUES (?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE source_revision=?,sequence_id=?,action_name=?,preserve_until=?,effective_at=?',
                    [$key,$r['source_revision'],$r['sequence_id'],$r['action_name'],$until,$r['effective_at'],$r['source_revision'],$r['sequence_id'],$r['action_name'],$until,$r['effective_at']]);
            }
            // A local restored outbox with events absent from the independent
            // journal is not proof of publication; keep reads/dispatch closed.
            $local=(int)query($db,'SELECT COALESCE(MAX(sequence_id),0) FROM evidence_recovery_outbox')->fetchColumn();
            $ready=$local<=$j['watermark'];
            query($db,'UPDATE evidence_recovery_state SET required_watermark=GREATEST(required_watermark,?),applied_watermark=?,head_digest=?,ready=?,verified_at=? WHERE singleton=1',
                [$j['watermark'],$j['watermark'],$j['digest'],$ready?1:0,now($db)]);
            return ['status'=>$ready?'SYNTHETIC_INTERFACE_REPLAYED':'PUBLICATION_PENDING','watermark'=>$j['watermark'],'digest'=>$j['digest']];
        });
    });
}
