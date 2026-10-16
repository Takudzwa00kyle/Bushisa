<?php
declare(strict_types=1);

namespace Bushisa\SyntheticImport;

require_once __DIR__ . '/legacy_history.php';
require_once __DIR__ . '/../tests/support/disposable_database.php';

use DateTimeImmutable;
use PDO;
use PDOException;
use stdClass;
use Throwable;
use Bushisa\LegacyHistory\InvalidInput;
use function Bushisa\LegacyHistory\{bundle_digest, catalog_schema, clock, decode, digest, encode, evaluate, fact_id, instant, object, ref, sha, snapshot_schema};

/** All preconnection decisions are independent of .env/application bootstrap. */
function guards(mixed $p): array
{
    if (PHP_SAPI !== 'cli' || getenv('BUSHISA_SYNTHETIC_IMPORT_ENABLE') !== 'true' || getenv('MAIL_PROVIDER') !== 'fake'
        || getenv('BUSHISA_ALLOW_REMOTE_TEST_DATABASE') === 'true') { throw new InvalidInput('SYNTHETIC_IMPORT_DISABLED', 'REJECT_DESTINATION'); }
    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'] as $key) {
        if (getenv($key) === false || getenv($key) === '') { throw new InvalidInput('EXPLICIT_DB_SETTINGS_REQUIRED', 'REJECT_DESTINATION'); }
    }
    try { $settings = \bushisa_require_disposable_test_database('synthetic import'); }
    catch (Throwable) { throw new InvalidInput('DISPOSABLE_GUARDS_FAILED', 'REJECT_DESTINATION'); }
    if (!in_array($settings['host'], ['127.0.0.1', '::1'], true)
        || !preg_match('/\Abushisa_(?:test|ci)_l02d(?:_[a-z0-9]+)*\z/D', $settings['name'])
        || !preg_match('/\Al02d_[a-z0-9_]{1,48}\z/D', $settings['user'])) { throw new InvalidInput('LOCAL_L02D_TARGET_REQUIRED', 'REJECT_DESTINATION'); }
    object($p, ['format', 'domain', 'host', 'port', 'database', 'user', 'incarnation', 'server_uuid', 'nonce', 'expires_at', 'test_controls']);
    if ($p->format !== 'bushisa.synthetic-import-permit.v1' || $p->domain !== 'synthetic' || !is_bool($p->test_controls)) { throw new InvalidInput('SYNTHETIC_PERMIT_REQUIRED', 'REJECT_DESTINATION'); }
    foreach (['host' => 'host', 'port' => 'port', 'database' => 'name', 'user' => 'user'] as $key => $field) {
        if (!is_string($p->{$key}) || $p->{$key} !== (string) $settings[$field]) { throw new InvalidInput('PERMIT_DESTINATION_MISMATCH', 'REJECT_DESTINATION'); }
    }
    ref($p->incarnation); sha($p->nonce);
    if (instant($p->expires_at) <= instant(gmdate('Y-m-d\TH:i:s\Z'))) { throw new InvalidInput('PERMIT_EXPIRED', 'REJECT_DESTINATION'); }
    if (!is_string($p->server_uuid) || !preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\z/D', $p->server_uuid)) { throw new InvalidInput('PERMIT_SERVER_ID', 'REJECT_DESTINATION'); }
    return $settings;
}

function open(stdClass $permit): PDO
{
    // Recheck here too: there is no public unguarded connection constructor.
    $settings=guards($permit);
    try {
        $options = \bushisa_disposable_database_pdo_options($settings);
        $options[PDO::ATTR_TIMEOUT] = 3; $options[PDO::ATTR_STRINGIFY_FETCHES] = true;
        $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'], $settings['name']), $settings['user'], $settings['password'], $options);
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    } catch (PDOException) { throw new InvalidInput('SYNTHETIC_CONNECTION_FAILED', 'REJECT_DESTINATION'); }
}
function query(PDO $pdo, string $sql, array $args = []): \PDOStatement
{
    $q = $pdo->prepare($sql); $q->execute($args); return $q;
}
function now(PDO $pdo): string { return (string) $pdo->query("SELECT DATE_FORMAT(UTC_TIMESTAMP(6),'%Y-%m-%dT%H:%i:%s.%fZ')")->fetchColumn(); }
function sql_time(string $utc): string { return (new DateTimeImmutable(instant($utc)))->format('Y-m-d H:i:s.u'); }

/** Static metadata-only query inventory; no message/case bodies or credentials in the capture. */
function capture_guard(PDO $pdo, int $low, int $high, bool $lock = true): array
{
    $ids = [$low, $high]; $both = [$low, $high, $high, $low];
    $spec = [
        'users' => ['SELECT id,created_at,is_verified,is_active,is_suspended,account_status,session_version,deleted_at FROM users WHERE id IN (?,?)', $ids],
        'profiles' => ['SELECT * FROM profiles WHERE user_id IN (?,?)', $ids],
        'preferences' => ['SELECT * FROM preferences WHERE user_id IN (?,?)', $ids],
        'answers' => ['SELECT * FROM find_match_answers WHERE user_id IN (?,?)', $ids],
        'consents' => ['SELECT * FROM consent_records WHERE user_id IN (?,?)', $ids],
        'history' => ['SELECT * FROM pair_policy_history WHERE user_low=? AND user_high=?', $ids],
        'counts' => ['SELECT * FROM pair_request_counts WHERE (requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)', $both],
        'declines' => ['SELECT * FROM pair_decline_restrictions WHERE (requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)', $both],
        'matches' => ['SELECT * FROM matches WHERE (user_a_id=? AND user_b_id=?) OR (user_a_id=? AND user_b_id=?)', $both],
        'swipes' => ['SELECT * FROM swipes WHERE (swiper_id=? AND swiped_id=?) OR (swiper_id=? AND swiped_id=?)', $both],
        'blocks' => ['SELECT * FROM blocks WHERE (blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?)', $both],
        'requests' => ['SELECT r.* FROM message_requests r JOIN matches m ON m.id=r.match_id WHERE (m.user_a_id=? AND m.user_b_id=?) OR (m.user_a_id=? AND m.user_b_id=?)', $both],
        'native_events' => ['SELECT e.* FROM pair_request_events e JOIN matches m ON m.id=e.match_id WHERE (m.user_a_id=? AND m.user_b_id=?) OR (m.user_a_id=? AND m.user_b_id=?)', $both],
        'segments' => ['SELECT e.* FROM pair_chat_segments e JOIN matches m ON m.id=e.match_id WHERE (m.user_a_id=? AND m.user_b_id=?) OR (m.user_a_id=? AND m.user_b_id=?)', $both],
        'messages' => ['SELECT e.id,e.match_id,e.sender_id,e.sent_at,e.unsent_at,e.report_segment_key,e.evidence_purge_due_at,e.ordinary_body_purged_at FROM messages e JOIN matches m ON m.id=e.match_id WHERE (m.user_a_id=? AND m.user_b_id=?) OR (m.user_a_id=? AND m.user_b_id=?)', $both],
        'operations' => ['SELECT * FROM pair_policy_operations WHERE actor_id IN (?,?)', $ids],
        'send_windows' => ['SELECT * FROM message_request_sends WHERE requester_id IN (?,?) OR recipient_id IN (?,?)', [$low,$high,$low,$high]],
        'probations' => ['SELECT * FROM report_probation_periods WHERE actor_id IN (?,?)', $ids],
        'block_links' => ['SELECT l.* FROM report_block_links l JOIN blocks b ON b.id=l.block_id WHERE (b.blocker_id=? AND b.blocked_id=?) OR (b.blocker_id=? AND b.blocked_id=?)', $both],
        'cases' => ['SELECT id,reporter_id,reported_user_id,target_type,target_id,status,final_resolution_at FROM moderation_cases WHERE reporter_id IN (?,?) OR reported_user_id IN (?,?)', [$low,$high,$low,$high]],
        'assignments' => ['SELECT a.* FROM moderation_case_assignments a JOIN moderation_cases c ON c.id=a.case_id WHERE c.reporter_id IN (?,?) OR c.reported_user_id IN (?,?)', [$low,$high,$low,$high]],
        'replay' => ['SELECT id,event_type,subject_type,subject_id,actor_user_id,occurred_at,replayed_at,SHA2(CAST(payload_json AS CHAR),256) AS payload_digest FROM protective_replay_events WHERE subject_id IN (?,?)', $ids],
        'genesis' => ['SELECT * FROM pair_account_genesis WHERE account_id IN (?,?)', $ids],
        'epoch' => ['SELECT * FROM pair_ledger_epoch', []],
        'schema' => ['SELECT * FROM schema_migrations', []],
    ];
    $capture = [];
    foreach ($spec as $name => [$sql, $args]) {
        $rows = query($pdo, $sql . ' LIMIT 5001' . ($lock ? ' FOR UPDATE' : ''), $args)->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 5000) { throw new InvalidInput('SYNTHETIC_STATE_BOUND_EXCEEDED', 'REPLAN_REQUIRED'); }
        usort($rows, static fn($a, $b) => encode($a) <=> encode($b)); $capture[$name] = $rows;
    }
    return $capture; // Consumers persist only the digest, not this temporary capture.
}
function guard_digest(array $capture): string { return digest($capture, 'synthetic-live-guard'); }

function marker(PDO $pdo, stdClass $permit): void
{
    $m = query($pdo, 'SELECT * FROM synthetic_import_destination WHERE singleton=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
    if (!is_array($m) || $m['incarnation'] !== $permit->incarnation || !hash_equals($m['nonce_hash'], hash('sha256', $permit->nonce))
        || $m['server_uuid'] !== $permit->server_uuid || $pdo->query('SELECT @@server_uuid')->fetchColumn() !== $permit->server_uuid
        || sql_time(now($pdo)) >= $m['expires_at'] || now($pdo) >= instant($permit->expires_at)) { throw new InvalidInput('UNAUTHORIZED_SYNTHETIC_DESTINATION', 'REJECT_DESTINATION'); }
}
function binding(PDO $pdo, string $pair): array
{
    $r = query($pdo, 'SELECT * FROM synthetic_import_pairs WHERE pair_ref=? FOR UPDATE', [$pair])->fetch(PDO::FETCH_ASSOC);
    if (!is_array($r) || sql_time(now($pdo)) >= $r['expires_at']) { throw new InvalidInput('SYNTHETIC_PAIR_NOT_BOUND', 'REJECT_DESTINATION'); }
    $receipts = (int) query($pdo, 'SELECT COUNT(*) FROM synthetic_import_receipts WHERE pair_ref=?', [$pair])->fetchColumn();
    $facts = (int) query($pdo, 'SELECT COUNT(*) FROM synthetic_import_facts WHERE pair_ref=?', [$pair])->fetchColumn();
    $keys = (int) query($pdo,'SELECT COUNT(*) FROM synthetic_import_keys WHERE pair_ref=?',[$pair])->fetchColumn();
    $equiv = (int) query($pdo,'SELECT COUNT(*) FROM synthetic_import_equivalences WHERE pair_ref=?',[$pair])->fetchColumn();
    if ($receipts !== (int) $r['committed_receipts'] || $facts !== (int) $r['stored_facts'] || $keys !== (int)$r['stored_keys'] || $equiv !== (int)$r['stored_equivalences']) { throw new InvalidInput('IMPORT_CONTINUITY_MISSING', 'REJECT_DESTINATION'); }
    return $r;
}
function semantic_plan(stdClass|array $p): string
{
    $data = (array) $p; unset($data['observed_at'], $data['raw_custody_check']); return encode($data);
}
function import_intent(stdClass $b, stdClass $plan): string
{
    // Diagnostic duplicate counts and processing time are not new effects.
    $projection=[];
    foreach(['state_fingerprint','proposed_actions','fact_ids','retention_ceiling','current_pair_version','expected_pair_version','request_union','ending_count','last_end'] as $key) { $projection[$key]=$plan->{$key}; }
    return digest(['bundle'=>bundle_digest($b),'projection'=>$projection],'synthetic-import-intent');
}
function preflight(stdClass $b, stdClass $s, stdClass $a, stdClass $plan, array $raw): array
{
    catalog_schema($a);
    if(property_exists($plan,'raw_custody_check') && $plan->raw_custody_check!=='MATCHED_SYNTHETIC_CATALOG_ONLY') { throw new InvalidInput('PLAN_CUSTODY_LABEL'); }
    foreach (['bundle','snapshot'] as $kind) {
        if (!in_array($raw[$kind] ?? '', $a->raw_digests->{$kind}, true)) { throw new InvalidInput('RAW_CUSTODY_DIGEST_MISMATCH'); }
    }
    $at = instant($plan->observed_at ?? null);
    $expected = evaluate($b, $s, $a, $at);
    if ($expected['outcome'] !== 'PLAN_NON_ENABLING') { throw new InvalidInput('VALIDATED_COMPLETE_SYNTHETIC_INPUT_REQUIRED', $expected['outcome']); }
    if (semantic_plan($plan) !== semantic_plan($expected)) { throw new InvalidInput('PLAN_CONTENT_MISMATCH'); }
    return $expected;
}

/** Test hook is gated by the permit AND a second explicit test flag. */
function control(stdClass $permit, string $point, array $test, PDO $pdo): void
{
    if ($test === []) { return; }
    if (!$permit->test_controls || getenv('BUSHISA_SYNTHETIC_IMPORT_TEST_CONTROLS') !== 'true') { throw new InvalidInput('TEST_CONTROLS_DISABLED'); }
    if (($test['fault'] ?? '') === $point) { throw new InvalidInput('INJECTED_' . strtoupper($point)); }
    static $retried = false;
    if (($test['fault'] ?? '') === 'retry_once_after_facts' && $point === 'after_facts' && !$retried) {
        $retried = true; $e = new PDOException('Synthetic forced retry, not a real deadlock'); $e->errorInfo=['40001',1213,'synthetic forced retry']; throw $e;
    }
    if ($point === 'before_lock' && isset($test['barrier'])) {
        $id = $test['barrier'];
        if (!is_string($id) || !preg_match('/\Al02d-[a-f0-9]{32}\z/', $id)) { throw new InvalidInput('TEST_BARRIER_ID'); }
        $dir = rtrim(sys_get_temp_dir(), '/\\') . '/bushisa-l02d-barriers';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) { throw new InvalidInput('TEST_BARRIER_IO'); }
        file_put_contents($dir . '/' . $id . '-' . getmypid() . '.json', encode(['pid' => getmypid(), 'connection_id' => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn(), 'entered_at' => now($pdo)]));
        $until = microtime(true) + 10;
        do { if (count(glob($dir . '/' . $id . '-*.json') ?: []) >= 2) { return; } usleep(20000); } while (microtime(true) < $until);
        throw new InvalidInput('TEST_BARRIER_TIMEOUT');
    }
}
function transaction(PDO $pdo, stdClass $permit, callable $work, array $test = []): array
{
    control($permit, 'before_lock', $test, $pdo);
    $lockName = 'bushisa-report:' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    for ($attempt = 0; $attempt < 3; $attempt++) {
        if ((int) query($pdo, 'SELECT GET_LOCK(?,5)', [$lockName])->fetchColumn() !== 1) { throw new InvalidInput('IMPORT_LOCK_TIMEOUT', 'REPLAN_REQUIRED'); }
        try {
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); $pdo->beginTransaction();
            marker($pdo, $permit); $result = $work($attempt+1); $pdo->commit(); return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if ($e instanceof PDOException && in_array((int) ($e->errorInfo[1] ?? 0), [1205,1213], true)) {
                if($attempt<2) { continue; }
                throw new InvalidInput('IMPORT_RETRY_EXHAUSTED','REPLAN_REQUIRED');
            }
            throw $e;
        } finally {
            try { query($pdo, 'SELECT RELEASE_LOCK(?)', [$lockName]); } catch (PDOException) { /* Connection loss is reported; never replay an uncertain commit. */ }
        }
    }
    throw new InvalidInput('IMPORT_RETRY_EXHAUSTED', 'REPLAN_REQUIRED');
}
function receipt_result(PDO $pdo, array $row, string $incarnation, string $pair): array
{
    $stored=decode($row['result_json']);
    object($stored,['outcome','receipt_ref','pair_ref','bundle_digest','resulting_pair_version','request_counts','ending_floor','new_facts','committed_at','expires_at','runtime_history_state','grants_chat','changes_sessions','production_authenticity','replayed','transaction_attempts']);
    if($row['incarnation']!==$incarnation || $row['pair_ref']!==$pair || $stored->pair_ref!==$pair || $stored->receipt_ref!==$row['receipt_ref'] || $stored->bundle_digest!==$row['bundle_digest']
        || digest($stored,'synthetic-committed-result')!==$row['result_digest'] || $stored->runtime_history_state!=='legacy_incomplete' || $stored->grants_chat!==false || $stored->changes_sessions!==false || $stored->replayed!==false
        || $stored->production_authenticity!=='NOT_VERIFIED' || $stored->outcome!=='SYNTHETIC_IMPORT_COMMITTED'
        || instant($stored->committed_at)!==instant(str_replace(' ','T',$row['committed_at']).'Z') || instant($stored->expires_at)!==instant(str_replace(' ','T',$row['expires_at']).'Z')) { throw new InvalidInput('COMMITTED_RECEIPT_INTEGRITY','REJECT_DESTINATION'); }
    if (sql_time(now($pdo)) >= $row['expires_at']) { throw new InvalidInput('RECEIPT_EXPIRED_DO_NOT_REAPPLY', 'REJECT_DESTINATION'); }
    $result = (array) $stored; $result['replayed'] = true; return $result;
}

function execute(stdClass $permit, stdClass $b, stdClass $s, stdClass $a, stdClass $plan, string $key, array $raw, array $test = []): array
{
    $settings = guards($permit); sha($key); preflight($b, $s, $a, $plan, $raw);
    $intent = import_intent($b,$plan);
    $pdo = open($permit);
    return transaction($pdo, $permit, function (int $attempt) use ($pdo,$permit,$b,$s,$a,$plan,$key,$intent,$test): array {
        $bound = binding($pdo, $b->pair_ref);
        if ($permit->incarnation !== $b->destination_incarnation) { throw new InvalidInput('DESTINATION_IDENTITY', 'REJECT_DESTINATION'); }
        $oldKey = query($pdo, 'SELECT * FROM synthetic_import_keys WHERE incarnation=? AND pair_ref=? AND key_hash=? FOR UPDATE', [$permit->incarnation,$b->pair_ref,hash('sha256',$key)])->fetch(PDO::FETCH_ASSOC);
        if (is_array($oldKey)) {
            if (!hash_equals($oldKey['intent_digest'], $intent)) { throw new InvalidInput('IMPORT_KEY_CONFLICT', 'CONFLICT_NO_APPLY'); }
            $old = query($pdo, 'SELECT * FROM synthetic_import_receipts WHERE receipt_ref=?', [$oldKey['receipt_ref']])->fetch(PDO::FETCH_ASSOC);
            if (!is_array($old)) { throw new InvalidInput('IMPORT_CONTINUITY_MISSING', 'REJECT_DESTINATION'); }
            return receipt_result($pdo, $old,$permit->incarnation,$b->pair_ref); // Before checking the old plan's pre-state.
        }
        $bundleHash = bundle_digest($b);
        $old = query($pdo, 'SELECT * FROM synthetic_import_receipts WHERE incarnation=? AND pair_ref=? AND bundle_digest=? FOR UPDATE', [$permit->incarnation,$b->pair_ref,$bundleHash])->fetch(PDO::FETCH_ASSOC);
        if (is_array($old)) {
            if ($old['intent_digest'] !== $intent) { throw new InvalidInput('BUNDLE_INTENT_CONFLICT', 'CONFLICT_NO_APPLY'); }
            $result=receipt_result($pdo,$old,$permit->incarnation,$b->pair_ref);
            query($pdo,'INSERT INTO synthetic_import_keys VALUES (?,?,?,?,?,?,?)',[$permit->incarnation,$b->pair_ref,hash('sha256',$key),$intent,$old['receipt_ref'],sql_time(now($pdo)),$old['expires_at']]);
            query($pdo,'UPDATE synthetic_import_pairs SET stored_keys=stored_keys+1 WHERE pair_ref=?',[$b->pair_ref]);
            return $result;
        }
        $at = now($pdo);
        $valid = evaluate($b, $s, $a, $at);
        if ($valid['outcome'] !== 'PLAN_NON_ENABLING' || semantic_plan($valid) !== semantic_plan($plan)) { throw new InvalidInput('SERVER_REVALIDATION_FAILED', $valid['outcome'] === 'PLAN_NON_ENABLING' ? 'REPLAN_REQUIRED' : $valid['outcome']); }
        if ($bound['snapshot_digest'] !== digest($s,'state') || encode(decode($bound['snapshot_json'])) !== encode($s)) { throw new InvalidInput('STALE_FIXTURE_BINDING','REPLAN_REQUIRED'); }
        $capture = capture_guard($pdo,(int)$bound['user_low'],(int)$bound['user_high']);
        if ($bound['guard_digest'] !== guard_digest($capture)) { throw new InvalidInput('STALE_LIVE_STATE','REPLAN_REQUIRED'); }
        $history = $capture['history'][0] ?? null;
        if ($history === null || $history['history_state'] !== 'legacy_incomplete' || (int)$history['version'] !== $s->state->pair_version
            || (int)$history['ending_count'] !== $s->state->ending_count || count($capture['users']) !== 2 || count($capture['counts']) !== 2
            || (int)$history['match_id'] !== (int)$bound['match_id']) { throw new InvalidInput('LIVE_LEGACY_STATE_REQUIRED','REJECT_DESTINATION'); }
        foreach ($capture['users'] as $u) {
            $index = (int)$u['id'] === (int)$bound['user_low'] ? 0 : 1;
            if ($u['deleted_at'] !== null || $u['account_status'] === 'deleted' || (int)$u['session_version'] !== $s->state->accounts[$index]->session_version) { throw new InvalidInput('LIVE_ACCOUNT_VERSION','REPLAN_REQUIRED'); }
        }
        foreach ($capture['counts'] as $count) {
            $direction = (int)$count['requester_id'] === (int)$bound['user_low'] ? 'low_to_high' : 'high_to_low';
            if ((int)$count['request_count'] !== $s->state->counts->{$direction} || $valid['request_union'][$direction] < (int)$count['request_count']) { throw new InvalidInput('LIVE_COUNT_CONFLICT','CONFLICT_NO_APPLY'); }
        }
        $expiry = min($valid['retention_ceiling'], instant($permit->expires_at), str_replace(' ','T',$bound['expires_at']) . 'Z');
        $eventSet = [];
        foreach (array_merge($b->events,$s->native_events) as $event) { $eventSet[fact_id($event)] = $event; }
        $added = 0;
        foreach ($eventSet as $id => $event) {
            $payload = digest($event,'immutable-event-payload');
            $old = query($pdo,'SELECT * FROM synthetic_import_facts WHERE fact_id=? FOR UPDATE',[$id])->fetch(PDO::FETCH_ASSOC);
            if (is_array($old)) {
                if ($old['pair_ref'] !== $b->pair_ref || $old['incarnation'] !== $b->source->incarnation || $old['payload_digest'] !== $payload || $old['identity_json'] !== encode($event->identity)
                    || digest(decode($old['event_json']),'immutable-event-payload')!==$old['payload_digest']) { throw new InvalidInput('STORED_FACT_CONFLICT','CONFLICT_NO_APPLY'); }
                if (sql_time($at) >= $old['expires_at']) { throw new InvalidInput('EXPIRED_FACT_DO_NOT_RECREATE','REJECT_DESTINATION'); }
                continue;
            }
            query($pdo,'INSERT INTO synthetic_import_facts (fact_id,incarnation,pair_ref,identity_json,payload_digest,event_json,created_at,expires_at) VALUES (?,?,?,?,?,?,?,?)',
                [$id,$b->source->incarnation,$b->pair_ref,encode($event->identity),$payload,encode($event),sql_time($at),sql_time(min($expiry,clock($event->retain_until)))]); $added++;
        }
        $addedEquiv=0;
        foreach ($b->equivalences as $e) {
            $left = min($e->left,$e->right); $right = max($e->left,$e->right); $proof = digest(['left'=>$left,'right'=>$right,'proof_ref'=>$e->proof_ref],'equivalence');
            $old = query($pdo,'SELECT proof_digest FROM synthetic_import_equivalences WHERE pair_ref=? AND left_id=? AND right_id=? FOR UPDATE',[$b->pair_ref,$left,$right])->fetchColumn();
            if ($old !== false && $old !== $proof) { throw new InvalidInput('STORED_EQUIVALENCE_CONFLICT','CONFLICT_NO_APPLY'); }
            if ($old === false) { query($pdo,'INSERT INTO synthetic_import_equivalences VALUES (?,?,?,?,?)',[$b->pair_ref,$left,$right,$proof,sql_time($expiry)]); $addedEquiv++; }
        }
        control($permit,'after_facts',$test,$pdo);
        foreach (['low_to_high','high_to_low'] as $i => $direction) {
            $from = (int)$bound[$i===0?'user_low':'user_high']; $to=(int)$bound[$i===0?'user_high':'user_low'];
            query($pdo,'UPDATE pair_request_counts SET request_count=? WHERE requester_id=? AND recipient_id=?',[$valid['request_union'][$direction],$from,$to]);
        }
        query($pdo,'UPDATE pair_policy_history SET ending_count=?,version=version+1 WHERE user_low=? AND user_high=? AND history_state="legacy_incomplete"',
            [max((int)$history['ending_count'],$valid['ending_count']),(int)$bound['user_low'],(int)$bound['user_high']]);
        control($permit,'after_counts',$test,$pdo);
        $after = guard_digest(capture_guard($pdo,(int)$bound['user_low'],(int)$bound['user_high']));
        $receipt = digest([$permit->incarnation,$b->pair_ref,$bundleHash],'synthetic-receipt');
        $result = ['outcome'=>'SYNTHETIC_IMPORT_COMMITTED','receipt_ref'=>$receipt,'pair_ref'=>$b->pair_ref,'bundle_digest'=>$bundleHash,'resulting_pair_version'=>(int)$history['version']+1,
            'request_counts'=>$valid['request_union'],'ending_floor'=>max((int)$history['ending_count'],$valid['ending_count']),'new_facts'=>$added,'committed_at'=>$at,
            'expires_at'=>$expiry,'runtime_history_state'=>'legacy_incomplete','grants_chat'=>false,'changes_sessions'=>false,'production_authenticity'=>'NOT_VERIFIED','replayed'=>false,'transaction_attempts'=>$attempt];
        query($pdo,'INSERT INTO synthetic_import_receipts (receipt_ref,incarnation,pair_ref,bundle_digest,intent_digest,before_digest,after_digest,result_json,result_digest,committed_at,expires_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$receipt,$permit->incarnation,$b->pair_ref,$bundleHash,$intent,$bound['guard_digest'],$after,encode($result),digest($result,'synthetic-committed-result'),sql_time($at),sql_time($expiry)]);
        control($permit,'after_receipt',$test,$pdo);
        query($pdo,'INSERT INTO synthetic_import_keys VALUES (?,?,?,?,?,?,?)',[$permit->incarnation,$b->pair_ref,hash('sha256',$key),$intent,$receipt,sql_time($at),sql_time($expiry)]);
        query($pdo,'UPDATE synthetic_import_pairs SET committed_receipts=committed_receipts+1,stored_facts=stored_facts+?,stored_keys=stored_keys+1,stored_equivalences=stored_equivalences+? WHERE pair_ref=?',[$added,$addedEquiv,$b->pair_ref]);
        control($permit,'after_key',$test,$pdo);
        return $result;
    },$test);
}

function status(stdClass $permit, string $pair, string $key): array
{
    guards($permit); ref($pair); sha($key); $pdo=open($permit);
    return transaction($pdo,$permit,function() use($pdo,$permit,$pair,$key):array {
        binding($pdo,$pair);
        $row=query($pdo,'SELECT r.* FROM synthetic_import_keys k JOIN synthetic_import_receipts r ON r.receipt_ref=k.receipt_ref WHERE k.incarnation=? AND k.pair_ref=? AND k.key_hash=?',[$permit->incarnation,$pair,hash('sha256',$key)])->fetch(PDO::FETCH_ASSOC);
        return is_array($row)?receipt_result($pdo,$row,$permit->incarnation,$pair):['outcome'=>'NOT_COMMITTED','grants_chat'=>false,'changes_sessions'=>false];
    });
}
