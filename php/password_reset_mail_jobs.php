<?php
declare(strict_types=1);
require_once __DIR__ . '/password_reset.php';

function bushisa_reset_mail_terminal(PDO $pdo,array $m,string $state,string $reason): void
{
    $now=bushisa_reset_now($pdo);
    $pdo->prepare('UPDATE password_reset_mail_jobs SET state=?,reason_code=?,terminal_at=COALESCE(terminal_at,?),encrypted_payload=NULL,payload_purge_due_at=?,next_attempt_at=NULL,
        account_id=NULL,account_generation=NULL,recipient_hash=NULL,challenge_id=NULL,claim_hash=NULL,claim_until=NULL,version=version+1 WHERE mail_id=?')
        ->execute([$state,$reason,$now,$now,$m['mail_id']]);
}

function bushisa_reset_dispatch_one(PDO $pdo,string $id,?callable $transport): string
{
    $hint=$pdo->prepare('SELECT account_id FROM password_reset_mail_jobs WHERE mail_id=?'); $hint->execute([$id]); $account=(int)$hint->fetchColumn();
    if ($account<1) return 'discarded';
    $claim=bin2hex(random_bytes(32));
    $delivery=bushisa_reset_transaction($pdo,function()use($pdo,$id,$account,$claim):?array {
        $us=$pdo->prepare('SELECT u.*,p.date_of_birth FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? FOR UPDATE');$us->execute([$account]);$u=$us->fetch(PDO::FETCH_ASSOC);
        $ms=$pdo->prepare('SELECT * FROM password_reset_mail_jobs WHERE mail_id=? FOR UPDATE');$ms->execute([$id]);$m=$ms->fetch(PDO::FETCH_ASSOC);
        if (!$m || !$u || (int)$m['account_id']!==$account) return null;
        $now=bushisa_reset_now($pdo);
        if (in_array($m['state'],['accepted','accepted_after_cancel','failed','cancelled','uncertain'],true)) return null;
        // A configuration rollback must never dispatch a queued reset proof.
        // Password-change notices are code-free and remain independently deliverable.
        if ($m['purpose']==='password_reset' && !bushisa_reset_enabled()) {
            if ($m['challenge_id'] !== null) {
                $pdo->prepare('UPDATE password_reset_challenges SET invalidated_at=COALESCE(invalidated_at,?),token_hash=NULL,secret_purge_due_at=?,version=version+1 WHERE challenge_id=?')
                    ->execute([$now,$now,$m['challenge_id']]);
            }
            bushisa_reset_mail_terminal($pdo,$m,'cancelled','reset_issuance_disabled');
            return null;
        }
        if ($m['state']==='inflight') {
            if ($m['claim_until']!==null && $m['claim_until']<=$now) bushisa_reset_mail_terminal($pdo,$m,'uncertain','carrier_outcome_unknown');
            return null;
        }
        if ($m['state']==='claimed' && $m['claim_until']!==null && $m['claim_until']>$now) return null;
        if ($m['useful_until']<=$now || $m['encrypted_payload']===null || $m['cancel_requested_at']!==null) { bushisa_reset_mail_terminal($pdo,$m,'cancelled','outside_useful_window'); return null; }
        $c=null;
        if ($m['purpose']==='password_reset') {
            $cs=$pdo->prepare('SELECT * FROM password_reset_challenges WHERE challenge_id=? FOR UPDATE');$cs->execute([$m['challenge_id']]);$c=$cs->fetch(PDO::FETCH_ASSOC);
            $stateS=$pdo->prepare('SELECT account_generation,issuance_generation FROM password_reset_state WHERE account_id=? FOR UPDATE');$stateS->execute([$account]);$state=$stateS->fetch(PDO::FETCH_ASSOC);
            $ok=$c && $state && bushisa_reset_eligible($u) && $c['consumed_at']===null && $c['invalidated_at']===null && $c['token_hash']!==null
                && $c['expires_at']>$now && hash_equals((string)$c['account_generation'],(string)$u['signup_attempt_key'])
                && hash_equals((string)$c['account_generation'],(string)$state['account_generation']) && (int)$state['issuance_generation']===(int)$c['issuance_generation']
                && (int)$c['issued_session_version']===(int)$u['session_version'] && hash_equals((string)$c['recipient_hash'],bushisa_reset_hash('recipient',(string)$u['email']))
                && hash_equals((string)$m['account_generation'],(string)$u['signup_attempt_key']);
            if (!$ok) { bushisa_reset_mail_terminal($pdo,$m,'cancelled','challenge_no_longer_eligible'); return null; }
        } elseif ($m['purpose']==='password_changed') {
            if ($u['account_status']==='deleted' || $u['deleted_at']!==null || $m['account_generation']!==$u['signup_attempt_key']) { bushisa_reset_mail_terminal($pdo,$m,'cancelled','account_no_longer_available'); return null; }
        } else { bushisa_reset_mail_terminal($pdo,$m,'failed','unsupported_purpose'); return null; }
        try { $payload=bushisa_reset_decrypt((string)$m['encrypted_payload'],$id,(string)$m['purpose'],(string)$m['challenge_id'],(string)$m['account_generation'],(string)$u['email']); }
        catch(Throwable) { bushisa_reset_mail_terminal($pdo,$m,'failed','payload_authentication_failed'); return null; }
        if (strtolower((string)($payload['recipient']??''))!==strtolower((string)$u['email'])) { bushisa_reset_mail_terminal($pdo,$m,'failed','recipient_binding_failed'); return null; }
        $until=bushisa_reset_time($now,'+60 seconds');
        $pdo->prepare('UPDATE password_reset_mail_jobs SET state="inflight",claim_hash=?,claim_until=?,attempts=attempts+1,version=version+1 WHERE mail_id=?')->execute([hash('sha256',$claim),$until,$id]);
        return ['payload'=>$payload,'purpose'=>$m['purpose']];
    });
    if (!$delivery) return 'discarded';
    $error=null;
    try {
        if ($transport!==null) $transport($delivery['payload'],$id,$delivery['purpose']);
        else bushisa_send_smtp_reset_payload($delivery['payload'],$id,$delivery['purpose']);
    } catch(Throwable $e) { $error=$e; }
    unset($delivery);
    return bushisa_reset_transaction($pdo,function()use($pdo,$id,$claim,$error):string {
        $s=$pdo->prepare('SELECT account_id FROM password_reset_mail_jobs WHERE mail_id=?');$s->execute([$id]);$account=(int)$s->fetchColumn();
        if ($account<1) return 'discarded';
        $uS=$pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$uS->execute([$account]);
        $s=$pdo->prepare('SELECT * FROM password_reset_mail_jobs WHERE mail_id=? FOR UPDATE');$s->execute([$id]);$m=$s->fetch(PDO::FETCH_ASSOC);
        if (!$m || $m['state']!=='inflight' || !hash_equals((string)$m['claim_hash'],hash('sha256',$claim))) return 'discarded';
        $now=bushisa_reset_now($pdo);
        if ($error===null) {
            $cancelled=$m['cancel_requested_at']!==null;
            bushisa_reset_mail_terminal($pdo,$m,$cancelled?'accepted_after_cancel':'accepted',$cancelled?'accepted_after_cancel':'smtp_accepted');
            return 'sent';
        }
        $uncertain=!$error instanceof BushisaMailTransportException || $error->uncertain;
        if ($uncertain) { bushisa_reset_mail_terminal($pdo,$m,'uncertain','carrier_outcome_unknown');return 'failed'; }
        if ($error->retryable && (int)$m['attempts']<5 && bushisa_reset_time($now,'+30 seconds')<$m['useful_until']) {
            $pdo->prepare('UPDATE password_reset_mail_jobs SET state="ready",claim_hash=NULL,claim_until=NULL,next_attempt_at=?,version=version+1 WHERE mail_id=?')->execute([bushisa_reset_time($now,'+30 seconds'),$id]);
            return 'retried';
        }
        bushisa_reset_mail_terminal($pdo,$m,'failed','transport_failed'); return 'failed';
    });
}

function bushisa_dispatch_reset_jobs(PDO $pdo,int $limit=10,?callable $transport=null): array
{
    if ($pdo->inTransaction()) throw new RuntimeException('Dispatch must run outside a transaction');
    if (!bushisa_reset_schema_ready($pdo)) return ['sent'=>0,'retried'=>0,'discarded'=>0,'failed'=>0];
    $out=['sent'=>0,'retried'=>0,'discarded'=>0,'failed'=>0];
    $ids=$pdo->prepare('SELECT mail_id FROM password_reset_mail_jobs WHERE state IN ("ready","claimed","inflight") AND (next_attempt_at IS NULL OR next_attempt_at<=UTC_TIMESTAMP(6) OR useful_until<=UTC_TIMESTAMP(6) OR claim_until<=UTC_TIMESTAMP(6)) ORDER BY queued_at LIMIT '.max(1,min(20,$limit)));
    $ids->execute();
    $pending=$ids->fetchAll(PDO::FETCH_COLUMN);
    if ($transport===null && (getenv('MAIL_TRANSPORT')==='fake' || bushisa_mail_provider()!=='smtp')) {
        $purposeFilter=bushisa_reset_enabled()?'':' AND purpose="password_changed"';
        $sendable=(int)$pdo->query('SELECT COUNT(*) FROM password_reset_mail_jobs WHERE state="ready" AND (next_attempt_at IS NULL OR next_attempt_at<=UTC_TIMESTAMP(6)) AND encrypted_payload IS NOT NULL AND cancel_requested_at IS NULL AND useful_until>UTC_TIMESTAMP(6)'.$purposeFilter)->fetchColumn();
        $sendable+=(int)$pdo->query('SELECT COUNT(*) FROM password_reset_mail_jobs WHERE state="claimed" AND claim_until<=UTC_TIMESTAMP(6) AND encrypted_payload IS NOT NULL AND cancel_requested_at IS NULL AND useful_until>UTC_TIMESTAMP(6)'.$purposeFilter)->fetchColumn();
        if ($sendable>0) throw new RuntimeException('A fake carrier is required for synthetic reset dispatch');
    }
    foreach($pending as $id) { $result=bushisa_reset_dispatch_one($pdo,(string)$id,$transport); $out[$result]++; }
    return $out;
}

/** Q47/Q22 secret and payload clocks; metadata expires seven days after terminal outcome. */
function bushisa_reset_cleanup(PDO $pdo,int $limit=100): array
{
    if (!bushisa_reset_schema_ready($pdo)) return ['payloads'=>0,'challenge_hashes'=>0,'metadata'=>0,'rate_events'=>0,'rate_subjects'=>0];
    $now=bushisa_reset_now($pdo); $result=['payloads'=>0,'challenge_hashes'=>0,'metadata'=>0,'rate_events'=>0,'rate_subjects'=>0];
    $ids=$pdo->prepare('SELECT mail_id FROM password_reset_mail_jobs WHERE (state IN ("ready","claimed") AND useful_until<=?) OR (state="inflight" AND claim_until<=?) OR (state IN ("accepted","accepted_after_cancel","failed","cancelled","uncertain") AND (encrypted_payload IS NOT NULL OR account_id IS NOT NULL)) ORDER BY queued_at LIMIT '.max(1,min(500,$limit)));
    $ids->execute([$now,$now]);
    foreach($ids->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $changed=bushisa_reset_transaction($pdo,function()use($pdo,$id):bool {
            $s=$pdo->prepare('SELECT account_id FROM password_reset_mail_jobs WHERE mail_id=?');$s->execute([$id]);$account=$s->fetchColumn();
            if ($account!==false && $account!==null) { $lock=$pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$lock->execute([$account]); }
            $s=$pdo->prepare('SELECT * FROM password_reset_mail_jobs WHERE mail_id=? FOR UPDATE');$s->execute([$id]);$m=$s->fetch(PDO::FETCH_ASSOC);
            if (!$m) return false; $at=bushisa_reset_now($pdo);
            if ($m['state']==='inflight' && $m['claim_until']!==null && $m['claim_until']<=$at) {
                bushisa_reset_mail_terminal($pdo,$m,'uncertain','carrier_outcome_unknown'); return true;
            }
            if (in_array($m['state'],['ready','claimed'],true) && $m['useful_until']<=$at) {
                bushisa_reset_mail_terminal($pdo,$m,'cancelled','useful_window_expired'); return true;
            }
            if (in_array($m['state'],['accepted','accepted_after_cancel','failed','cancelled','uncertain'],true) && ($m['encrypted_payload']!==null || $m['account_id']!==null)) {
                bushisa_reset_mail_terminal($pdo,$m,(string)$m['state'],'terminal_cleanup'); return true;
            }
            return false;
        });
        if ($changed) $result['payloads']++;
    }
    $s=$pdo->prepare('UPDATE password_reset_challenges SET token_hash=NULL,version=version+1 WHERE token_hash IS NOT NULL AND secret_purge_due_at<=? LIMIT '.max(1,min(500,$limit)));
    $s->execute([$now]); $result['challenge_hashes']=$s->rowCount();
    // Challenge rows carry only account/generation/recipient pseudonyms once the
    // secret clock ends; remove them after their linked mail has been terminalized.
    $s=$pdo->prepare('DELETE FROM password_reset_challenges WHERE challenge_id IN (
        SELECT challenge_id FROM (SELECT c.challenge_id FROM password_reset_challenges c WHERE c.token_hash IS NULL AND c.secret_purge_due_at<=?
          AND NOT EXISTS (SELECT 1 FROM password_reset_mail_jobs m WHERE m.challenge_id=c.challenge_id AND m.purpose="password_reset" AND m.state IN ("ready","claimed","inflight"))
          ORDER BY c.secret_purge_due_at LIMIT '.max(1,min(500,$limit)).') expired_challenges)');
    $s->execute([$now]);
    $s=$pdo->prepare('DELETE FROM password_reset_rate_events WHERE expires_at<=? LIMIT '.max(1,min(1000,$limit)));$s->execute([$now]);$result['rate_events']=$s->rowCount();
    $s=$pdo->prepare('DELETE FROM password_reset_rate_subjects WHERE touched_at<=DATE_SUB(?,INTERVAL 48 HOUR) AND NOT EXISTS (SELECT 1 FROM password_reset_rate_events e WHERE e.subject_hash=password_reset_rate_subjects.subject_hash) LIMIT '.max(1,min(500,$limit)));
    $s->execute([$now]);$result['rate_subjects']=$s->rowCount();
    // Q22 permits only seven days of code-free terminal mail metadata.
    $s=$pdo->prepare('DELETE FROM password_reset_mail_jobs WHERE state IN ("accepted","accepted_after_cancel","failed","cancelled","uncertain") AND terminal_at<=DATE_SUB(?,INTERVAL 7 DAY) AND encrypted_payload IS NULL LIMIT '.max(1,min(500,$limit)));
    $s->execute([$now]);$result['metadata']=$s->rowCount();
    return $result;
}
