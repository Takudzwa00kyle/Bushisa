<?php
declare(strict_types=1);

require_once __DIR__ . '/report_intake.php';

function bushisa_pair_ready(PDO $pdo): bool
{
    return operational_table_exists($pdo, 'pair_policy_history');
}

/** Caller owns the shared report/pair transaction. Absent history is not proof. */
function bushisa_pair_ensure(PDO $pdo, int $a, int $b): array
{
    if (!bushisa_pair_ready($pdo)) { throw new BushisaReportError(503, 'Pair history migration is required'); }
    if (!$pdo->inTransaction() || $a === $b) { throw new LogicException('Invalid pair transaction'); }
    $low=min($a,$b); $high=max($a,$b);
    $stmt=$pdo->prepare('SELECT * FROM pair_policy_history WHERE user_low=? AND user_high=? FOR UPDATE');
    $stmt->execute([$low,$high]); $pair=$stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($pair)) { return $pair; }
    $mc=operational_match_columns($pdo);
    $stmt=$pdo->prepare(sprintf('SELECT id FROM matches WHERE (%1$s=? AND %2$s=?) OR (%1$s=? AND %2$s=?) ORDER BY id LIMIT 1 FOR UPDATE',$mc['user_a'],$mc['user_b']));
    $stmt->execute([$low,$high,$high,$low]); $match=$stmt->fetchColumn();
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM pair_request_counts WHERE (requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)');
    $stmt->execute([$low,$high,$high,$low]); $oldCounts=(int)$stmt->fetchColumn();
    $stmt=$pdo->prepare('SELECT account_id FROM pair_account_genesis WHERE account_id IN (?,?) ORDER BY account_id LIMIT 1');
    $stmt->execute([$low,$high]); $genesis=$stmt->fetchColumn();
    // A real AFTER INSERT record proves at least one original account did not
    // exist before this ledger. Known pre-ledger pair rows contradict freshness.
    $complete=$genesis!==false && $match===false && $oldCounts===0;
    $stmt=$pdo->prepare('INSERT INTO pair_policy_history
        (user_low,user_high,match_id,history_state,genesis_account_id,generation,created_at)
        VALUES (?,?,?,?,?,?,UTC_TIMESTAMP(6))');
    $stmt->execute([$low,$high,$match===false?null:$match,$complete?'complete_from_genesis':'legacy_incomplete',$complete?$genesis:null,$match===false?0:1]);
    foreach ([[$low,$high],[$high,$low]] as [$from,$to]) {
        $stmt=$pdo->prepare('INSERT IGNORE INTO pair_request_counts (requester_id,recipient_id,request_count) VALUES (?,?,0)');
        $stmt->execute([$from,$to]);
    }
    return bushisa_pair_ensure($pdo,$a,$b);
}

function bushisa_pair_complete(PDO $pdo,array $pair): void
{
    $proof=$pdo->prepare('SELECT 1 FROM pair_account_genesis g JOIN pair_ledger_epoch e ON e.singleton=1
        WHERE g.account_id=? AND g.recorded_at<=? AND g.recorded_at>=e.activated_at AND e.writer_reference IS NOT NULL');
    $proof->execute([$pair['genesis_account_id'],$pair['created_at']]);
    // information_schema.TRIGGERS hides rows from a least-privilege runtime
    // without TRIGGER rights. The migration-owned fixed-schema routine exposes
    // only current health, preserving fail-closed behavior without granting DDL.
    try { $health=$pdo->query('CALL bushisa_pair_trigger_health()'); $trigger=(int)$health->fetchColumn()===1; $health->closeCursor(); }
    catch (PDOException) { $trigger=false; }
    if ($pair['history_state'] !== 'complete_from_genesis' || !$proof->fetchColumn() || !$trigger
        || !in_array((int)$pair['genesis_account_id'],[(int)$pair['user_low'],(int)$pair['user_high']],true)) {
        throw new BushisaReportError(503, 'Verified original pair history is required');
    }
}

function bushisa_pair_bump(PDO $pdo, array $pair): void
{
    $stmt=$pdo->prepare('UPDATE pair_policy_history SET version=version+1 WHERE user_low=? AND user_high=?');
    $stmt->execute([$pair['user_low'],$pair['user_high']]);
}

function bushisa_pair_members(PDO $pdo, int $match, int $actor): array
{
    $mc=operational_match_columns($pdo);
    $stmt=$pdo->prepare(sprintf('SELECT id,%1$s AS a,%2$s AS b,match_status FROM matches WHERE id=? AND (%1$s=? OR %2$s=?) FOR UPDATE',$mc['user_a'],$mc['user_b']));
    $stmt->execute([$match,$actor,$actor]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) { throw new BushisaReportError(404,'Pair unavailable'); }
    return $row;
}

/** Public own-pair metadata only. Never a profile/chat permission. */
function bushisa_pair_projection(PDO $pdo, array $pair, int $actor): array
{
    $complete=false;
    if ($pair['history_state']==='complete_from_genesis') {
        try { bushisa_pair_complete($pdo,$pair); $complete=true; } catch (BushisaReportError) { /* Lost positive proof is not eligibility. */ }
    }
    $other=(int)$pair['user_low']===$actor?(int)$pair['user_high']:(int)$pair['user_low'];
    $stmt=$pdo->prepare('SELECT id FROM blocks WHERE blocker_id=? AND blocked_id=?'); $stmt->execute([$actor,$other]);
    $block=$stmt->fetchColumn();
    return ['match_id'=>$pair['match_id']===null?null:(int)$pair['match_id'],'pair_version'=>(int)$pair['version'],
        'history_state'=>$pair['history_state']==='complete_from_genesis'&&!$complete?'provenance_unavailable':$pair['history_state'],
        'generation'=>(int)$pair['generation'],'ending_count'=>(int)$pair['ending_count'],
        'ending_count_is_complete'=>$complete,
        'own_unmatch'=>(bool)$pair[(int)$pair['user_low']===$actor?'low_unmatched':'high_unmatched'],
        'own_block_id'=>$block===false?null:(int)$block,'last_ended_by'=>$pair['last_ended_by']===null?null:(int)$pair['last_ended_by'],
        'restart_at'=>$pair['last_ended_at']===null?null:bushisa_report_iso(bushisa_report_time($pair['last_ended_at'])->modify('+12 hours')->format('Y-m-d H:i:s.u'))];
}

/** Exact client intent plus CAS stops stale first deliveries and later retries. */
function bushisa_pair_operation(PDO $pdo, int $actor, array $pair, array $input, callable $work): array
{
    $key=$input['idempotency_key']??null; $version=$input['pair_version']??null;
    if (!is_string($key) || !preg_match('/\A[a-f0-9]{64}\z/',$key) || !is_int($version) || $version<0) {
        throw new BushisaReportError(400,'A submission key and current pair version are required');
    }
    unset($input['csrf_token']); ksort($input);
    $hash=hash('sha256',json_encode($input,JSON_THROW_ON_ERROR)); $keyHash=hash('sha256',$key);
    $stmt=$pdo->prepare('SELECT * FROM pair_policy_operations WHERE actor_id=? AND key_hash=? FOR UPDATE');
    $stmt->execute([$actor,$keyHash]); $old=$stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($old)) {
        if (!hash_equals($old['intent_hash'],$hash)) { throw new BushisaReportError(409,'Submission key conflict'); }
        if (bushisa_report_now($pdo)>=$old['expires_at']) { throw new BushisaReportError(409,'Submission receipt expired; refresh pair state'); }
        // Recheck current actor before entry. No cached content or access grant.
        return ['replayed'=>true,'resulting_version'=>(int)$old['resulting_version']];
    }
    if ((int)$pair['version']!==$version) { throw new BushisaReportError(409,'Pair state changed; refresh before choosing again'); }
    $result=$work();
    bushisa_pair_bump($pdo,$pair);
    $stmt=$pdo->prepare('INSERT INTO pair_policy_operations (actor_id,key_hash,intent_hash,match_id,resulting_version,created_at,expires_at)
        VALUES (?,?,?,?,?,UTC_TIMESTAMP(6),DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 30 DAY))');
    $stmt->execute([$actor,$keyHash,$hash,$pair['match_id'],(int)$pair['version']+1]);
    return array_merge($result,['replayed'=>false,'resulting_version'=>(int)$pair['version']+1]);
}

function bushisa_pair_check_contact(PDO $pdo, int $actor, int $other): void
{
    if (!bushisa_account_is_chat_eligible($pdo,$actor) || !bushisa_account_is_chat_eligible($pdo,$other)
        || operational_is_blocked($pdo,$actor,$other)) { throw new BushisaReportError(403,'This pair is unavailable'); }
}

function bushisa_pair_segment(PDO $pdo, int $match): ?array
{
    $stmt=$pdo->prepare('SELECT * FROM pair_chat_segments WHERE match_id=? AND ended_at IS NULL ORDER BY opened_at DESC LIMIT 1');
    $stmt->execute([$match]); return $stmt->fetch(PDO::FETCH_ASSOC)?:null;
}

function bushisa_pair_legacy_segment(int $match,array $request): string
{
    // A precision-widening migration must not rename a pre-007 zero-fraction
    // segment whose original identity was based on the stored timestamp text.
    $time=preg_replace('/\.0{6}$/','',(string)$request['responded_at']);
    return hash('sha256',$match.':'.$request['id'].':'.$time);
}

function bushisa_pair_open_chat(PDO $pdo, int $match, string $source, ?string $event=null): string
{
    $pdo->prepare('UPDATE pair_policy_history SET chat_suppressed=0 WHERE match_id=?')->execute([$match]);
    $current=bushisa_pair_segment($pdo,$match);
    if ($current!==null) { return $current['segment_key']; }
    $key=bin2hex(random_bytes(32));
    $stmt=$pdo->prepare('INSERT INTO pair_chat_segments (segment_key,match_id,opened_at,grant_source,request_event_key)
        VALUES (?,?,UTC_TIMESTAMP(6),?,?)'); $stmt->execute([$key,$match,$source,$event]);
    return $key;
}

/** Import an observed current row, not a synthetic event or completeness claim. */
function bushisa_pair_observe_request(PDO $pdo, array $pair, ?array $row): ?string
{
    if ($row===null) { return null; }
    $key=hash('sha256','observed-request:'.$row['id'].':'.$row['requested_at']);
    $stmt=$pdo->prepare('INSERT IGNORE INTO pair_request_events
        (event_key,match_id,generation,request_id,requester_id,recipient_id,requested_at,request_expires_at,status,closed_at,purge_due_at,provenance)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,"observed_current")');
    $purge=$row['purge_due_at'];
    if ($purge===null && $row['status']==='pending' && $row['expires_at']!==null) {
        $purge=bushisa_report_time($row['expires_at'])->modify('+30 days')->format('Y-m-d H:i:s.u');
    }
    if ($purge===null && in_array($row['status'],['expired','declined','cancelled','revoked'],true)) {
        if ($row['closed_at']===null) { return null; } // No invented terminal retention boundary.
        $purge=bushisa_report_time($row['closed_at'])->modify('+30 days')->format('Y-m-d H:i:s.u');
    }
    $stmt->execute([$key,$row['match_id'],$pair['generation'],$row['id'],$row['requester_id'],$row['recipient_id'],
        $row['requested_at'],$row['expires_at'],$row['status'],$row['closed_at'],$purge]);
    $pdo->prepare('UPDATE pair_policy_history SET current_request_event_key=COALESCE(current_request_event_key,?) WHERE user_low=? AND user_high=?')->execute([$key,$pair['user_low'],$pair['user_high']]);
    return $key;
}

function bushisa_pair_request_terminal(PDO $pdo, int $match, string $status, string $at): void
{
    $stmt=$pdo->prepare('UPDATE pair_request_events SET status=?,closed_at=COALESCE(closed_at,?),
        purge_due_at=LEAST(COALESCE(purge_due_at,DATE_ADD(?,INTERVAL 30 DAY)),DATE_ADD(?,INTERVAL 30 DAY))
        WHERE match_id=? AND status IN ("pending","approved")'); $stmt->execute([$status,$at,$at,$at,$match]);
}

/** Unmatch keeps approval and pending expiry. A block is not a chat ending. */
function bushisa_pair_close_relationship(PDO $pdo, int $actor, int $other, string $reason, bool $bump=true): void
{
    $pair=bushisa_pair_ensure($pdo,$actor,$other); $match=(int)$pair['match_id'];
    if ($match===0) { if ($bump) { bushisa_pair_bump($pdo,$pair); } return; }
    $request=operational_message_request_status($pdo,$match,$actor,$other)['row'];
    bushisa_pair_observe_request($pdo,$pair,$request);
    $now=bushisa_report_now($pdo);
    if ($reason==='unmatched') {
        $side=(int)$pair['user_low']===$actor?'low':'high';
        if (!(bool)$pair[$side.'_unmatched']) {
            $stmt=$pdo->prepare('UPDATE pair_policy_history SET '.$side.'_unmatched=1,low_restore_at=NULL,high_restore_at=NULL,unmatched_at=? WHERE user_low=? AND user_high=?');
            $stmt->execute([$now,$pair['user_low'],$pair['user_high']]);
        }
    } else {
        // Keep an already approved grant; the actual block/account checks deny
        // access. Clear pending requests without counting an explicit chat end.
        if (is_array($request) && $request['status']==='pending') {
            $stmt=$pdo->prepare('UPDATE message_requests SET status="revoked",revoked_at=?,closed_at=?,purge_due_at=DATE_ADD(?,INTERVAL 30 DAY) WHERE match_id=? AND status="pending"');
            $stmt->execute([$now,$now,$now,$match]); bushisa_pair_request_terminal($pdo,$match,'revoked',$now);
        }
        $pdo->prepare('UPDATE pair_policy_history SET low_restore_at=NULL,high_restore_at=NULL,chat_suppressed=1 WHERE user_low=? AND user_high=?')->execute([$pair['user_low'],$pair['user_high']]);
        bushisa_suppress_chat_notifications_for_match($pdo,$match);
    }
    $status=$reason==='deleted_cleanup'?'deleted_cleanup':($reason==='unmatched'?'unmatched':'blocked');
    $mc=operational_match_columns($pdo);
    $stmt=$pdo->prepare(sprintf('SELECT id FROM matches WHERE (%1$s=? AND %2$s=?) OR (%1$s=? AND %2$s=?) FOR UPDATE',$mc['user_a'],$mc['user_b']));
    $stmt->execute([$actor,$other,$other,$actor]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $alias) {
        $pdo->prepare('UPDATE matches SET match_status=?,closed_at=COALESCE(closed_at,?),closed_reason=?,closed_by=? WHERE id=?')->execute([$status,$now,$reason,$actor,$alias]);
        if ($reason!=='unmatched' && (int)$alias!==$match) {
            $pdo->prepare('UPDATE message_requests SET status="revoked",revoked_at=?,closed_at=?,purge_due_at=DATE_ADD(?,INTERVAL 30 DAY) WHERE match_id=? AND status="pending"')->execute([$now,$now,$now,$alias]);
            bushisa_pair_request_terminal($pdo,(int)$alias,'revoked',$now);
            bushisa_suppress_chat_notifications_for_match($pdo,(int)$alias);
        }
    }
    $sc=operational_swipe_columns($pdo);
    $stmt=$pdo->prepare(sprintf('DELETE FROM swipes WHERE (swiper_id=? AND %1$s=?) OR (swiper_id=? AND %1$s=?)',$sc['target']));
    $stmt->execute([$actor,$other,$other,$actor]);
    if ($bump) { bushisa_pair_bump($pdo,$pair); }
}

function bushisa_pair_restore(PDO $pdo, array $pair, int $actor, string $action): array
{
    $other=(int)$pair['user_low']===$actor?(int)$pair['user_high']:(int)$pair['user_low'];
    $match=(int)$pair['match_id']; $row=bushisa_pair_members($pdo,$match,$actor);
    if ($action==='unmatch') {
        // Safety/consent withdrawal is never rate limited or history gated.
        bushisa_pair_close_relationship($pdo,$actor,$other,'unmatched',false);
        return [];
    }
    bushisa_pair_check_contact($pdo,$actor,$other);
    if ($row['match_status']!=='unmatched' || $pair['unmatched_at']===null) { throw new BushisaReportError(409,'No unmatch to restore'); }
    bushisa_pair_complete($pdo,$pair);
    $side=(int)$pair['user_low']===$actor?'low':'high';
    if ($action==='refuse_restore') {
        $pdo->prepare('UPDATE pair_policy_history SET '.$side.'_restore_at=NULL WHERE user_low=? AND user_high=?')->execute([$pair['user_low'],$pair['user_high']]);
        return [];
    }
    $stmt=$pdo->prepare('UPDATE pair_policy_history SET '.$side.'_restore_at=UTC_TIMESTAMP(6),'.$side.'_unmatched=0 WHERE user_low=? AND user_high=?');
    $stmt->execute([$pair['user_low'],$pair['user_high']]);
    $fresh=bushisa_pair_ensure($pdo,$actor,$other);
    if ($fresh['low_restore_at']!==null && $fresh['high_restore_at']!==null && !(bool)$fresh['low_unmatched'] && !(bool)$fresh['high_unmatched']) {
        if ($fresh['last_restored_at']!==null && bushisa_report_now($pdo)<bushisa_report_time($fresh['last_restored_at'])->modify('+24 hours')->format('Y-m-d H:i:s.u')) {
            throw new BushisaReportError(429,'One successful restoration per pair per rolling 24 hours');
        }
        $pdo->prepare('UPDATE matches SET match_status="active",closed_at=NULL,closed_reason=NULL,closed_by=NULL WHERE id=?')->execute([$match]);
        $pdo->prepare('UPDATE pair_policy_history SET last_restored_at=UTC_TIMESTAMP(6),low_restore_at=NULL,high_restore_at=NULL WHERE user_low=? AND user_high=?')->execute([$pair['user_low'],$pair['user_high']]);
        // No generation, decline, budget, ending or original request clock reset.
        return ['restored'=>true];
    }
    return ['restored'=>false];
}

function bushisa_pair_unblock_grant(PDO $pdo, int $actor, int $other): void
{
    $pair=bushisa_pair_ensure($pdo,$actor,$other);
    if (operational_is_blocked($pdo,$actor,$other)) { return; }
    if ($pair['match_id']!==null) {
        // This changes visibility state only; it cannot create a grant. It also
        // permits independently authorized seven-day read-only closed history.
        $pdo->prepare('UPDATE matches SET match_status=CASE WHEN match_status="blocked" THEN "unmatched" ELSE match_status END WHERE id=?')->execute([$pair['match_id']]);
    }
    try { bushisa_pair_complete($pdo,$pair); } catch (BushisaReportError) { return; }
    // Personal block removal remains possible; unproved history cannot confer
    // a new privilege. Other participant's block and bans independently dominate.
    if ($pair['history_state']!=='complete_from_genesis' || (int)$pair['ending_count']>=2
        || !bushisa_account_is_chat_eligible($pdo,$actor) || !bushisa_account_is_chat_eligible($pdo,$other)
        || operational_is_blocked($pdo,$actor,$other)) { return; }
    if ($pair['match_id']===null) {
        $mc=operational_match_columns($pdo);
        $stmt=$pdo->prepare(sprintf('INSERT INTO matches (%s,%s,%s,match_status) VALUES (?,?,UTC_TIMESTAMP(6),"unmatched")',$mc['user_a'],$mc['user_b'],$mc['created_at']));
        $stmt->execute([$pair['user_low'],$pair['user_high']]); $pair['match_id']=(int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE pair_policy_history SET match_id=? WHERE user_low=? AND user_high=?')->execute([$pair['match_id'],$pair['user_low'],$pair['user_high']]);
    }
    bushisa_pair_open_chat($pdo,(int)$pair['match_id'],'creator_unblock');
}

/** New mutual interest after unmatch, never an undo/restoration. */
function bushisa_pair_match(PDO $pdo, array $pair): int
{
    $match=$pair['match_id'];
    if ($match===null) {
        $mc=operational_match_columns($pdo);
        $stmt=$pdo->prepare(sprintf('INSERT INTO matches (%s,%s,%s) VALUES (?,?,UTC_TIMESTAMP(6))',$mc['user_a'],$mc['user_b'],$mc['created_at']));
        $stmt->execute([$pair['user_low'],$pair['user_high']]); $match=(int)$pdo->lastInsertId();
    } else {
        // Fresh swipes are cleared on every unmatch/block; restoration records
        // are separate. The next two new likes constitute a new generation.
        $pdo->prepare('UPDATE matches SET match_status="active",closed_at=NULL,closed_reason=NULL,closed_by=NULL WHERE id=?')->execute([$match]);
        $pdo->prepare('UPDATE pair_decline_restrictions SET cleared_at=UTC_TIMESTAMP(6) WHERE (requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)')->execute([$pair['user_low'],$pair['user_high'],$pair['user_high'],$pair['user_low']]);
    }
    $pdo->prepare('UPDATE pair_policy_history SET match_id=?,generation=generation+1,low_unmatched=0,high_unmatched=0,low_restore_at=NULL,high_restore_at=NULL WHERE user_low=? AND user_high=?')->execute([$match,$pair['user_low'],$pair['user_high']]);
    return (int)$match;
}

function bushisa_pair_cleanup(PDO $pdo): array
{
    // Ordinary access checks enforce these clocks even when no worker runs.
    // Never delete lifetime counters, declines, ending/cutoff or active blocks.
    $pdo->exec('UPDATE message_requests SET status="expired",closed_at=expires_at,purge_due_at=DATE_ADD(expires_at,INTERVAL 30 DAY)
        WHERE status="pending" AND expires_at<=UTC_TIMESTAMP(6)');
    $pdo->exec('UPDATE pair_request_counts c JOIN message_requests mr ON mr.requester_id=c.requester_id AND mr.recipient_id=c.recipient_id
        SET c.retry_not_before=GREATEST(COALESCE(c.retry_not_before,"1000-01-01"),DATE_ADD(mr.expires_at,INTERVAL 12 HOUR))
        WHERE mr.status="expired" AND mr.expires_at IS NOT NULL');
    $pdo->exec('UPDATE pair_request_events SET status="expired",closed_at=request_expires_at,
        purge_due_at=LEAST(COALESCE(purge_due_at,DATE_ADD(request_expires_at,INTERVAL 30 DAY)),DATE_ADD(request_expires_at,INTERVAL 30 DAY))
        WHERE status="pending" AND request_expires_at<=UTC_TIMESTAMP(6)');
    $events=$pdo->exec('DELETE FROM pair_request_events WHERE purge_due_at<=UTC_TIMESTAMP(6)');
    $pdo->exec('UPDATE pair_policy_history ph LEFT JOIN pair_request_events e ON e.event_key=ph.current_request_event_key SET ph.current_request_event_key=NULL WHERE ph.current_request_event_key IS NOT NULL AND e.event_key IS NULL');
    $operations=$pdo->exec('DELETE FROM pair_policy_operations WHERE expires_at<=UTC_TIMESTAMP(6)');
    $requests=$pdo->exec('DELETE FROM message_requests WHERE purge_due_at<=UTC_TIMESTAMP(6) AND status IN ("expired","cancelled","declined","revoked")');
    // Only immutable per-message closed-session deadlines select ordinary rows.
    // A later restart cannot widen this query to its new messages. Separately
    // preserved report evidence/holds are not ordinary message rows.
    $messages=$pdo->exec('DELETE FROM messages WHERE evidence_purge_due_at<=UTC_TIMESTAMP(6)');
    $segments=$pdo->exec('DELETE s FROM pair_chat_segments s WHERE s.read_until<=UTC_TIMESTAMP(6)
        AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.match_id=s.match_id AND m.report_segment_key=s.segment_key)');
    return ['request_events_purged'=>$events,'operation_receipts_purged'=>$operations,'terminal_requests_purged'=>$requests,
        'closed_messages_purged'=>$messages,'closed_segments_purged'=>$segments];
}

function bushisa_pair_request(PDO $pdo, int $actor, int $sessionVersion, int $match, string $action, array $input=[]): array
{
    return bushisa_report_transaction($pdo,function() use($pdo,$actor,$sessionVersion,$match,$action,$input):array {
        if (!bushisa_report_actor_ok($pdo,$actor,$sessionVersion)) { throw new BushisaReportError(401,'Your session is no longer valid'); }
        $members=bushisa_pair_members($pdo,$match,$actor); $other=(int)((int)$members['a']===$actor?$members['b']:$members['a']);
        $pair=bushisa_pair_ensure($pdo,$actor,$other);
        $state=operational_message_request_status($pdo,$match,$actor,$other);
        if ($action==='read') {
            if ($pair['current_request_event_key']===null) { bushisa_pair_observe_request($pdo,$pair,$state['row']); }
            if (is_array($state['row']) && $state['status']==='pending' && $state['row']['expires_at']!==null && bushisa_report_now($pdo)>=$state['row']['expires_at']) {
                $state['status']='expired'; // Projection only; every mutation rechecks the immutable deadline.
            }
            $state['pair']=bushisa_pair_projection($pdo,$pair,$actor);
            $selected=$input['event_key']??null;
            if ($selected!==null && (!is_string($selected)||!preg_match('/\A[a-f0-9]{64}\z/',$selected))) { throw new BushisaReportError(404,'Request event unavailable'); }
            $state['report_context']=bushisa_report_context_for_response($pdo,$actor,'match',$match,null,$selected===null?null:'ledger:'.$selected);
            if ($selected!==null && $state['report_context']===null) { throw new BushisaReportError(404,'Request event unavailable'); }
            $events=$pdo->prepare('SELECT event_key,status,requested_at,request_expires_at,purge_due_at FROM pair_request_events WHERE match_id=? AND (purge_due_at IS NULL OR purge_due_at>UTC_TIMESTAMP(6)) ORDER BY requested_at DESC LIMIT 20');
            $events->execute([$match]); $state['events']=$events->fetchAll(PDO::FETCH_ASSOC);
            return $state;
        }
        $operation=bushisa_pair_operation($pdo,$actor,$pair,$input,function() use($pdo,$actor,$other,$match,$action,$pair,$members,$state):array {
            bushisa_pair_check_contact($pdo,$actor,$other);
            $row=$state['row']; $now=bushisa_report_now($pdo);
            $event=$pair['current_request_event_key']??bushisa_pair_observe_request($pdo,$pair,$row);
            if ($row!==null && $row['status']==='pending' && $row['expires_at']!==null && $now>=$row['expires_at']) {
                $pdo->prepare('UPDATE message_requests SET status="expired",closed_at=expires_at,purge_due_at=DATE_ADD(expires_at,INTERVAL 30 DAY) WHERE id=?')->execute([$row['id']]);
                bushisa_pair_request_terminal($pdo,$match,'expired',$row['expires_at']);
                $pdo->prepare('UPDATE pair_request_counts SET retry_not_before=GREATEST(COALESCE(retry_not_before,"1000-01-01"),DATE_ADD(?,INTERVAL 12 HOUR)) WHERE requester_id=? AND recipient_id=?')->execute([$row['expires_at'],$row['requester_id'],$row['recipient_id']]);
                $row['status']='expired'; $state['status']='expired';
            }
            if ($action==='request') {
                bushisa_pair_complete($pdo,$pair);
                if ((int)$pair['ending_count']>=2) { throw new BushisaReportError(403,'The original pair has reached its chat-ending cutoff'); }
                if (operational_has_message_approval($pdo,$match)) { return []; }
                if ($members['match_status']!=='active') { throw new BushisaReportError(403,'An eligible mutual match is required'); }
                if ($state['status']==='pending') { throw new BushisaReportError(409,'A pending request already exists'); }
                if ((int)$pair['ending_count']>0) {
                    if ((int)$pair['last_ended_by']!==$actor) { throw new BushisaReportError(403,'Only the participant who ended chat may request restart'); }
                    if ($pair['last_ended_at']===null || $now<bushisa_report_time($pair['last_ended_at'])->modify('+12 hours')->format('Y-m-d H:i:s.u')) {
                        throw new BushisaReportError(409,'The 12-hour chat restart cooldown is active');
                    }
                }
                $decline=$pdo->prepare('SELECT 1 FROM pair_decline_restrictions WHERE requester_id=? AND recipient_id=? AND cleared_at IS NULL');
                $decline->execute([$actor,$other]);
                if ($decline->fetchColumn()) { throw new BushisaReportError(409,'This declined request direction is restricted'); }
                $budget=$pdo->prepare('SELECT request_count,retry_not_before FROM pair_request_counts WHERE requester_id=? AND recipient_id=? FOR UPDATE');
                $budget->execute([$actor,$other]); $counter=$budget->fetch(PDO::FETCH_ASSOC);
                if (!is_array($counter)) { throw new BushisaReportError(503,'Pair accounting unavailable'); }
                if ($counter['retry_not_before']!==null && $now<$counter['retry_not_before']) { throw new BushisaReportError(409,'The 12-hour request cooldown is active'); }
                if ((int)$counter['request_count']>=3) { throw new BushisaReportError(429,'Lifetime pair allowance reached'); }
                bushisa_report_request_allowance($pdo,$actor);
                $incoming=$pdo->prepare('SELECT COUNT(*) FROM message_request_sends WHERE recipient_id=? AND created_at>DATE_SUB(?,INTERVAL 12 HOUR)');
                $incoming->execute([$other,$now]); if ((int)$incoming->fetchColumn()>=10) { throw new BushisaReportError(429,'Recipient allowance reached'); }
                if ($row!==null) {
                    $pdo->prepare('UPDATE message_requests SET requester_id=?,recipient_id=?,status="pending",requested_at=?,expires_at=DATE_ADD(?,INTERVAL 7 DAY),responded_at=NULL,revoked_at=NULL,closed_at=NULL,purge_due_at=NULL,generation_key=? WHERE id=?')->execute([$actor,$other,$now,$now,'pair:'.$pair['generation'],$row['id']]);
                    $request=(int)$row['id'];
                } else {
                    $pdo->prepare('INSERT INTO message_requests (match_id,requester_id,recipient_id,status,requested_at,expires_at,generation_key) VALUES (?,?,?,"pending",?,DATE_ADD(?,INTERVAL 7 DAY),?)')->execute([$match,$actor,$other,$now,$now,'pair:'.$pair['generation']]); $request=(int)$pdo->lastInsertId();
                }
                $event=bin2hex(random_bytes(32));
                $pdo->prepare('INSERT INTO pair_request_events (event_key,match_id,generation,request_id,requester_id,recipient_id,requested_at,request_expires_at,status,purge_due_at,provenance)
                    VALUES (?,?,?,?,?,?,?,DATE_ADD(?,INTERVAL 7 DAY),"pending",DATE_ADD(?,INTERVAL 37 DAY),"ledger_created")')->execute([$event,$match,$pair['generation'],$request,$actor,$other,$now,$now,$now]);
                $pdo->prepare('UPDATE pair_policy_history SET current_request_event_key=? WHERE user_low=? AND user_high=?')->execute([$event,$pair['user_low'],$pair['user_high']]);
                bushisa_report_record_send($pdo,$actor,$other,$request);
                operational_write_notification($pdo,$other,'message_request','Message request','An established match requested permission to chat.','chat.php?match_id='.$match,'message_request:'.$match.':'.$other);
            } elseif ($action==='approve' || $action==='decline') {
                if ($row===null || (int)$row['recipient_id']!==$actor || $state['status']!=='pending'
                    || ($action==='approve' && $members['match_status']!=='active')) { throw new BushisaReportError(409,'No eligible pending request to respond to'); }
                if ($action==='approve' && (int)$pair['ending_count']>=2) { throw new BushisaReportError(403,'Chat-ending cutoff'); }
                if ($action==='approve') { bushisa_pair_complete($pdo,$pair); }
                if ($action==='approve' && $row['expires_at']===null) { throw new BushisaReportError(503,'Original request expiry is unverified'); }
                $next=$action==='approve'?'approved':'declined';
                $pdo->prepare('UPDATE message_requests SET status=?,responded_at=?,closed_at=IF(?="declined",?,NULL),purge_due_at=IF(?="declined",DATE_ADD(?,INTERVAL 30 DAY),NULL),revoked_at=NULL WHERE id=?')->execute([$next,$now,$next,$now,$next,$now,$row['id']]);
                if ($next==='approved') {
                    $pdo->prepare('UPDATE pair_request_events SET status="approved",purge_due_at=NULL WHERE event_key=?')->execute([$event]);
                    bushisa_pair_open_chat($pdo,$match,'request_approval',$event);
                } else {
                    bushisa_pair_request_terminal($pdo,$match,'declined',$now);
                    $pdo->prepare('INSERT INTO pair_decline_restrictions (requester_id,recipient_id,source_match_id,declined_at,cleared_at) VALUES (?,?,?,?,NULL) ON DUPLICATE KEY UPDATE source_match_id=VALUES(source_match_id),declined_at=VALUES(declined_at),cleared_at=NULL')->execute([$other,$actor,$match,$now]);
                }
            } elseif ($action==='cancel') {
                if ($row===null || (int)$row['requester_id']!==$actor || $state['status']!=='pending') { throw new BushisaReportError(409,'No pending request to cancel'); }
                $pdo->prepare('UPDATE message_requests SET status="cancelled",closed_at=?,purge_due_at=DATE_ADD(?,INTERVAL 30 DAY) WHERE id=?')->execute([$now,$now,$row['id']]);
                $pdo->prepare('UPDATE pair_request_counts SET retry_not_before=DATE_ADD(?,INTERVAL 12 HOUR) WHERE requester_id=? AND recipient_id=?')->execute([$now,$actor,$other]);
                bushisa_pair_request_terminal($pdo,$match,'cancelled',$now);
            } else {
                $grant=operational_has_message_approval($pdo,$match);
                if (!$grant && $state['status']!=='pending') { throw new BushisaReportError(409,'No permission to revoke'); }
                if (!$grant && (int)($row['requester_id']??0)!==$actor) { throw new BushisaReportError(403,'Only the requester may cancel; the recipient may decline'); }
                if (!$grant) {
                    $pdo->prepare('UPDATE pair_request_counts SET retry_not_before=DATE_ADD(?,INTERVAL 12 HOUR) WHERE requester_id=? AND recipient_id=?')->execute([$now,$actor,$other]);
                }
                if ($grant) {
                    $segment=bushisa_pair_segment($pdo,$match);
                    if ($segment===null && $row!==null && $row['responded_at']!==null) {
                        $key=bushisa_pair_legacy_segment($match,$row);
                        $pdo->prepare('INSERT IGNORE INTO pair_chat_segments (segment_key,match_id,opened_at,grant_source) VALUES (?,?,?,"legacy_observed")')->execute([$key,$match,$row['responded_at']]);
                    }
                    $pdo->prepare('UPDATE pair_chat_segments SET ended_at=?,ended_by=?,read_until=DATE_ADD(?,INTERVAL 7 DAY) WHERE match_id=? AND ended_at IS NULL')->execute([$now,$actor,$now,$match]);
                    $pdo->prepare('UPDATE messages SET evidence_purge_due_at=LEAST(COALESCE(evidence_purge_due_at,DATE_ADD(?,INTERVAL 7 DAY)),DATE_ADD(?,INTERVAL 7 DAY)) WHERE match_id=? AND (report_segment_key IS NULL OR report_segment_key IN (SELECT segment_key FROM pair_chat_segments WHERE match_id=? AND ended_at=?))')->execute([$now,$now,$match,$match,$now]);
                    $pdo->prepare('UPDATE pair_policy_history SET ending_count=ending_count+1,last_ended_by=?,last_ended_at=? WHERE user_low=? AND user_high=?')->execute([$actor,$now,$pair['user_low'],$pair['user_high']]);
                }
                $pdo->prepare('UPDATE message_requests SET status="revoked",revoked_at=?,closed_at=COALESCE(closed_at,?),purge_due_at=COALESCE(purge_due_at,DATE_ADD(?,INTERVAL 30 DAY)) WHERE match_id=? AND status IN ("pending","approved")')->execute([$now,$now,$now,$match]);
                bushisa_pair_request_terminal($pdo,$match,'revoked',$now);
                bushisa_suppress_chat_notifications_for_match($pdo,$match);
            }
            return [];
        });
        $state=operational_message_request_status($pdo,$match,$actor,$other);
        $state['operation']=$operation;
        $state['pair']=bushisa_pair_projection($pdo,bushisa_pair_ensure($pdo,$actor,$other),$actor);
        $state['report_context']=bushisa_report_context_for_response($pdo,$actor,'match',$match);
        return $state;
    });
}
