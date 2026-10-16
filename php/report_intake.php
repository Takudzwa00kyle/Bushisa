<?php
declare(strict_types=1);

require_once __DIR__ . '/operational.php';
require_once __DIR__ . '/policy_safeguards.php';

final class BushisaReportError extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = 'Report target unavailable')
    {
        parent::__construct($message);
    }
}

function bushisa_report_keys(): array
{
    $raw = json_decode((string) getenv('REPORT_CONTEXT_KEYS'), true);
    if (!is_array($raw) || $raw === []) {
        throw new BushisaReportError(503, 'Reporting is unavailable');
    }
    $keys = [];
    foreach ($raw as $id => $encoded) {
        $key = is_string($encoded) ? base64_decode($encoded, true) : false;
        if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,40}\z/', $id) || $key === false || strlen($key) !== 32) {
            throw new BushisaReportError(503, 'Reporting is unavailable');
        }
        $keys[$id] = $key;
    }
    $active = (string) (getenv('REPORT_CONTEXT_ACTIVE_KEY') ?: array_key_first($keys));
    if (!isset($keys[$active])) {
        throw new BushisaReportError(503, 'Reporting is unavailable');
    }
    return ['active' => $active, 'keys' => $keys];
}

function bushisa_report_available(PDO $pdo): bool
{
    try {
        bushisa_report_keys();
        return operational_table_exists($pdo, 'report_operations');
    } catch (BushisaReportError) {
        return false;
    }
}

function bushisa_report_now(PDO $pdo): string
{
    return (string) $pdo->query('SELECT UTC_TIMESTAMP(6)')->fetchColumn();
}

function bushisa_report_time(string $value): DateTimeImmutable
{
    return new DateTimeImmutable($value, new DateTimeZone('UTC'));
}

function bushisa_report_iso(?string $value): ?string
{
    return $value === null ? null : bushisa_report_time($value)->format('Y-m-d\TH:i:s.u\Z');
}

function bushisa_report_mac(string $purpose, string $value, string $key): string
{
    return hash_hmac('sha256', $purpose . "\0" . $value, $key);
}

/** Stateless, encrypted, reporter-bound proof. No new backend exposure history. */
function bushisa_report_seal(array $proof): string
{
    $ring = bushisa_report_keys();
    $id = $ring['active'];
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt(json_encode($proof, JSON_THROW_ON_ERROR), 'aes-256-gcm',
        $ring['keys'][$id], OPENSSL_RAW_DATA, $iv, $tag, 'bushisa-report-proof-v1:' . $id);
    if ($cipher === false) {
        throw new BushisaReportError(503, 'Reporting is unavailable');
    }
    return $id . '.' . rtrim(strtr(base64_encode($iv . $tag . $cipher), '+/', '-_'), '=');
}

function bushisa_report_open(string $token, int $reporter, array $ring): array
{
    if (strlen($token) > 8192) {
        throw new BushisaReportError(404);
    }
    $parts = explode('.', $token, 2);
    $blob = count($parts) === 2 ? base64_decode(strtr($parts[1], '-_', '+/'), true) : false;
    if ($blob === false || strlen($blob) < 29 || !isset($ring['keys'][$parts[0]])) {
        throw new BushisaReportError(404);
    }
    $clear = openssl_decrypt(substr($blob, 28), 'aes-256-gcm', $ring['keys'][$parts[0]],
        OPENSSL_RAW_DATA, substr($blob, 0, 12), substr($blob, 12, 16), 'bushisa-report-proof-v1:' . $parts[0]);
    $proof = $clear === false ? null : json_decode($clear, true);
    if (!is_array($proof) || ($proof['v'] ?? null) !== 1 || ($proof['reporter'] ?? null) !== $reporter) {
        throw new BushisaReportError(404);
    }
    return $proof;
}

/** Only called for an item actually returned by an already-authorized reader. */
function bushisa_report_context_for_response(PDO $pdo, int $reporter, string $type, int $id, ?string $observedBody = null, ?string $event = null): ?string
{
    if (!bushisa_report_available($pdo)) {
        return null;
    }
    $source = bushisa_report_source($pdo, $reporter, $type, $id, $pdo->inTransaction(),null,true,$event);
    if ($source === null || !$source['visible'] || ($observedBody !== null && $source['capturable'] && $source['body'] !== $observedBody)) {
        return null;
    }
    $now = bushisa_report_now($pdo);
    $expires = $source['expires'];
    if ($expires!==null && $now>=$expires) { return null; }
    if ($type === 'user') {
        $expires = bushisa_report_time($now)->modify('+30 days')->format('Y-m-d H:i:s.u');
    }
    return bushisa_report_seal([
        'v' => 1, 'reporter' => $reporter, 'type' => $type, 'id' => $id,
        'author' => $source['author'], 'participants' => $source['participants'],
        'container' => $source['container'], 'created' => $source['created'],
        'version' => $source['version'], 'issued' => $now, 'expires' => $expires,
        'event' => $source['event'], 'capturable' => $source['capturable'],
    ]);
}

/**
 * Scoped source loader. Metadata membership is checked before any private body.
 * Issuance callers must already have applied their ordinary read predicate.
 */
function bushisa_report_source(PDO $pdo, int $reporter, string $type, int $id, bool $lock, ?string $expectedVersion = null, bool $captureAllowed = true, ?string $expectedEvent = null): ?array
{
    $suffix = $lock ? ' FOR UPDATE' : '';
    $row = null;
    $body = null;
    $participants = [];
    $container = '';
    $author = null;
    $visible = true;
    $expires = null;
    $revision = '';
    $eventIdentity = null;
    $capturable = true;
    if ($type === 'message') {
        $mc = operational_match_columns($pdo);
        $meta = $pdo->prepare(sprintf(
            'SELECT msg.id, msg.sender_id, msg.match_id, msg.unsent_at, msg.ordinary_body_purged_at,
                    msg.evidence_purge_due_at, msg.report_revision, msg.report_segment_key, msg.%3$s AS created, m.%1$s AS a, m.%2$s AS b,
                    m.%4$s AS generation, m.match_status
             FROM messages msg JOIN matches m ON m.id = msg.match_id
             WHERE msg.id = ? AND (m.%1$s = ? OR m.%2$s = ?)%5$s',
            $mc['user_a'], $mc['user_b'], operational_message_columns($pdo)['created_at'], $mc['created_at'], $suffix));
        $meta->execute([$id, $reporter, $reporter]);
        $row = $meta->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        $participants = [(int) $row['a'], (int) $row['b']];
        // Never invent a legacy grant/session boundary. Unknown legacy segments
        // support an individual item only, not neighboring-message capture.
        $container = $row['match_id'] . ':' . ($row['report_segment_key'] ?? ('legacy-item:' . $id));
        $author = (int) $row['sender_id'];
        $revision = (string) $row['report_revision'];
        $capturable = $row['report_segment_key'] !== null;
        $expires = $row['evidence_purge_due_at'];
        $visible = empty($row['unsent_at']) && empty($row['ordinary_body_purged_at']);
        if ($visible && $capturable && $captureAllowed && ($expectedVersion === null || hash_equals($expectedVersion, hash('sha256', $revision . "\0" . $container . "\0" . $row['created']))) && ($expires === null || bushisa_report_now($pdo) < $expires)) {
            $bodyStmt = $pdo->prepare('SELECT ' . operational_message_columns($pdo)['body'] . ' FROM messages WHERE id = ?' . $suffix);
            $bodyStmt->execute([$id]);
            $body = (string) $bodyStmt->fetchColumn();
        }
    } elseif ($type === 'confession' || $type === 'confession_reference') {
        $cc = operational_confession_columns($pdo);
        if ($type === 'confession_reference') {
            $stmt = $pdo->prepare('SELECT id, confession_id, referenced_confession_id, created_at, deleted_at FROM confession_references WHERE id = ?' . $suffix);
            $stmt->execute([$id]);
            $ref = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($ref)) {
                return null;
            }
            $postId = (int) $ref['confession_id'];
        } else {
            $postId = $id;
        }
        $stmt = $pdo->prepare(sprintf('SELECT id, %s AS author, created_at AS created, deleted_at, purge_due_at, is_flagged, report_revision FROM confessions WHERE id = ?%s', $cc['author'], $suffix));
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        $author = $row['author'] === null ? null : (int) $row['author'];
        $container = 'confession:' . $postId;
        $revision = (string) $row['report_revision'];
        if ($type === 'confession_reference') {
            $row['created'] = $ref['created_at'];
            $revision .= ':reference:' . $ref['referenced_confession_id'];
        }
        $expires = $row['purge_due_at'];
        $visible = empty($row['deleted_at']) && ($type !== 'confession_reference' || empty($ref['deleted_at']));
        if ($visible && $captureAllowed && ($expectedVersion === null || hash_equals($expectedVersion, hash('sha256', $revision . "\0" . $container . "\0" . $row['created']))) && ($expires === null || bushisa_report_now($pdo) < $expires)) {
            $stmt = $pdo->prepare('SELECT ' . $cc['body'] . ' FROM confessions WHERE id = ?' . $suffix);
            $stmt->execute([$postId]);
            $body = (string) $stmt->fetchColumn();
        }
        // Flagged posts may be captured only by an existing legitimately issued proof.
        if ($expectedVersion === null && (int) $row['is_flagged'] === 1) {
            $visible = false;
        }
        if ($type === 'confession_reference') {
            $row['created'] = $ref['created_at'];
        }
    } elseif ($type === 'match') {
        $mc = operational_match_columns($pdo);
        $stmt = $pdo->prepare(sprintf('SELECT id, %1$s AS a, %2$s AS b, %3$s AS created, match_status FROM matches WHERE id = ? AND (%1$s = ? OR %2$s = ?)%4$s',
            $mc['user_a'], $mc['user_b'], $mc['created_at'], $suffix));
        $stmt->execute([$id, $reporter, $reporter]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        $participants = [(int) $row['a'], (int) $row['b']];
        $container = 'match:' . $id . ':' . $row['created'];
        $stmt = $pdo->prepare('SELECT id, requester_id, recipient_id, status, requested_at, responded_at, expires_at, generation_key, purge_due_at FROM message_requests WHERE match_id = ? ORDER BY id DESC LIMIT 1' . $suffix);
        $stmt->execute([$id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (($event['status']??'')==='pending' && empty($event['expires_at'])) { return null; }
        if (in_array($event['status']??'',['expired','cancelled','declined','revoked'],true) && empty($event['purge_due_at']) && $expectedEvent===null) { return null; }
        $expires = $event['purge_due_at'] ?? null;
        $eventIdentity = $event === [] ? 'pair-only' : ($event['id'] . ':' . $event['requested_at']);
        if ($expectedEvent!==null && preg_match('/\A([0-9]+):(.+)\z/',$expectedEvent,$oldIdentity)
            && (int)$oldIdentity[1]===(int)($event['id']??0)
            && bushisa_report_time($oldIdentity[2])==bushisa_report_time($event['requested_at'])) {
            $eventIdentity=$expectedEvent; // Same observed instant across precision widening.
        }
        if ($expires === null && ($event['status'] ?? '') === 'pending' && !empty($event['expires_at'])) {
            $expires = bushisa_report_time($event['expires_at'])->modify('+30 days')->format('Y-m-d H:i:s.u');
        }
        $revision = json_encode([$row['match_status'], $event], JSON_THROW_ON_ERROR);
        if (operational_table_exists($pdo,'pair_request_events')) {
            $eventKey=null;
            if ($expectedEvent!==null && str_starts_with($expectedEvent,'ledger:')) { $eventKey=substr($expectedEvent,7); }
            elseif ($expectedEvent===null) {
                $current=$pdo->prepare('SELECT current_request_event_key FROM pair_policy_history WHERE match_id=?');
                $current->execute([$id]); $eventKey=$current->fetchColumn()?:null;
            }
            if ($eventKey!==null) {
                $stored=$pdo->prepare('SELECT * FROM pair_request_events WHERE match_id=? AND event_key=?'.$suffix);
                $stored->execute([$id,$eventKey]); $historical=$stored->fetch(PDO::FETCH_ASSOC);
                if (!is_array($historical)) { return null; }
                $eventIdentity='ledger:'.$eventKey; $expires=$historical['purge_due_at'];
                $revision=json_encode($historical,JSON_THROW_ON_ERROR);
            }
        }
    } elseif ($type === 'user') {
        $stmt = $pdo->prepare('SELECT id, created_at AS created, deleted_at FROM users WHERE id = ?' . $suffix);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        $author = $id;
        $participants = [$reporter, $id];
        $container = 'user:' . $id;
        $visible = empty($row['deleted_at']);
        if (!$visible) {
            $expires = bushisa_report_time($row['deleted_at'])->modify('+7 days')->format('Y-m-d H:i:s.u');
        }
    } else {
        return null;
    }
    return [
        'author' => $author, 'participants' => $participants, 'container' => $container,
        'created' => (string) $row['created'], 'expires' => $expires,
        'version' => hash('sha256', $revision . "\0" . $container . "\0" . $row['created']),
        'body' => $body, 'visible' => $visible,
        'event' => $eventIdentity, 'capturable' => $capturable,
    ];
}

function bushisa_report_actor_ok(PDO $pdo, int $actor, ?int $sessionVersion = null): bool
{
    $stmt = $pdo->prepare('SELECT account_status, session_version, is_active, is_suspended, is_verified, deleted_at FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$actor]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) && (int) $row['is_active'] === 1 && (int) $row['is_verified'] === 1
        && (int) $row['is_suspended'] === 0 && empty($row['deleted_at'])
        && !in_array($row['account_status'], ['pending_email', 'pending_onboarding', 'underage_blocked', 'suspended', 'deleted'], true)
        && ($sessionVersion === null || (int) $row['session_version'] === $sessionVersion);
}

/** A coarse DB lock keeps this bounded slice deterministic across HTTP workers. */
function bushisa_report_transaction(PDO $pdo, callable $work): mixed
{
    if ($pdo->inTransaction()) {
        throw new LogicException('Report service owns its transaction');
    }
    $lockName = 'bushisa-report:' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $lock->execute([$lockName]);
    if ((int) $lock->fetchColumn() !== 1) {
        throw new BushisaReportError(503, 'Please retry this operation');
    }
    try {
        $pdo->beginTransaction();
        try {
            $result = $work();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
}

function bushisa_report_validate(array $input): array
{
    $allowed = ['action', 'target_type', 'target_id', 'context', 'idempotency_key', 'reason',
        'explanation', 'new_incident', 'receipt_id', 'context_tokens', 'conduct_actor_id', 'block_id', 'csrf_token'];
    if (array_diff(array_keys($input), $allowed)) {
        throw new BushisaReportError(400, 'Invalid report');
    }
    $action = $input['action'] ?? '';
    if (!in_array($action, ['report', 'block_and_report', 'report_addendum'], true)
        || !is_string($input['idempotency_key'] ?? null)
        || !preg_match('/\A[a-f0-9]{64}\z/', $input['idempotency_key'])) {
        throw new BushisaReportError(400, 'A report submission key is required');
    }
    $reason = $input['reason'] ?? '';
    $reasons = ['harassment', 'hate_speech', 'hate_directed_abuse', 'explicit_content', 'spam',
        'catfish_impersonation', 'impersonation', 'threats', 'stalking', 'coercion', 'unwanted_contact',
        'doxxing', 'scams', 'exploitation', 'nonconsensual_sexual_content', 'public_explicit_media',
        'child_exploitation', 'safety_evasion', 'other'];
    $text = $input['explanation'] ?? '';
    if (!is_string($reason) || !in_array($reason, $reasons, true) || !is_string($text)
        || !preg_match('//u', $text) || str_contains($text, "\0")) {
        throw new BushisaReportError(400, 'Invalid report');
    }
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    if (mb_strlen($text, 'UTF-8') > 2000 || (in_array($reason, ['other', 'explicit_content'], true) && trim($text) === '')) {
        throw new BushisaReportError(400, 'Provide an explanation of at most 2,000 characters');
    }
    $context = $input['context'] ?? null;
    $type = $input['target_type'] ?? null;
    $id = $input['target_id'] ?? null;
    $hasTarget = $context !== null || $type !== null || $id !== null;
    if (($action !== 'report_addendum' || $hasTarget)
        && (!in_array($type, ['user', 'message', 'match', 'confession', 'confession_reference'], true)
            || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
            || !is_string($context) || strlen($context) > 8192)) {
        throw new BushisaReportError(400, 'Invalid report');
    }
    $receipt = $input['receipt_id'] ?? null;
    if (($action === 'report_addendum' || $receipt !== null)
        && (!is_string($receipt) || !preg_match('/\A[a-f0-9]{64}\z/', $receipt))) {
        throw new BushisaReportError(400, 'Invalid report');
    }
    $tokens = $input['context_tokens'] ?? [];
    if (!is_array($tokens) || count($tokens) > 20
        || array_filter($tokens, static fn($s) => !is_string($s) || strlen($s) > 8192)
        || ($tokens !== [] && trim($text) === '')) {
        throw new BushisaReportError(400, 'Invalid supporting context');
    }
    foreach (['conduct_actor_id', 'block_id'] as $field) {
        if (isset($input[$field]) && filter_var($input[$field], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new BushisaReportError(400, 'Invalid report');
        }
    }
    if (isset($input['new_incident']) && !is_bool($input['new_incident'])) {
        throw new BushisaReportError(400, 'Invalid incident intent');
    }
    return [
        'action' => $action, 'target_type' => $type, 'target_id' => $hasTarget ? (int) $id : null,
        'context' => $context, 'idempotency_key' => $input['idempotency_key'], 'reason' => $reason,
        'explanation' => $text, 'new_incident' => $input['new_incident'] ?? false,
        'receipt_id' => $receipt, 'context_tokens' => array_values($tokens),
        'conduct_actor_id' => isset($input['conduct_actor_id']) ? (int) $input['conduct_actor_id'] : null,
        'block_id' => isset($input['block_id']) ? (int) $input['block_id'] : null,
    ];
}

/** Fingerprint selectors, not randomized encryption envelopes or transport order. */
function bushisa_report_canonical(array $intent, int $reporter, array $ring): string
{
    $selector = static function (string $token) use ($reporter, $ring): array {
        $p = bushisa_report_open($token, $reporter, $ring);
        return [$p['type'], $p['id'], $p['container'], $p['created'], $p['version']];
    };
    unset($intent['idempotency_key']);
    $intent['context'] = $intent['context'] === null ? null : $selector($intent['context']);
    $refs = array_map($selector, $intent['context_tokens']);
    usort($refs, static fn(array $a, array $b): int => $a <=> $b);
    $intent['context_tokens'] = array_values(array_unique($refs, SORT_REGULAR));
    return json_encode($intent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function bushisa_report_resolve(PDO $pdo, int $reporter, array $intent, array $ring, string $now, bool $allowOwn = false): array
{
    $proof = bushisa_report_open($intent['context'], $reporter, $ring);
    if (!empty($proof['expires']) && $now >= $proof['expires']) {
        throw new BushisaReportError(404);
    }
    $type = $intent['target_type'];
    $id = $intent['target_id'];
    if (($proof['type'] ?? '') !== $type || ($proof['id'] ?? 0) !== $id) {
        // Explicit account-report fallback from a proven own interaction; no profile reload.
        if ($type !== 'user' || !in_array($proof['type'] ?? '', ['message', 'match'], true)
            || !in_array($id, $proof['participants'] ?? [], true) || $id === $reporter) {
            throw new BushisaReportError(404);
        }
        $original = bushisa_report_source($pdo, $reporter, $proof['type'], (int) $proof['id'], true, $proof['version'], false,$proof['event']??null);
        if ($original === null || $original['created'] !== $proof['created']) {
            throw new BushisaReportError(404);
        }
        $source = bushisa_report_source($pdo, $reporter, 'user', $id, true);
        if ($source === null) {
            throw new BushisaReportError(404);
        }
        $proof = array_merge($proof, ['type' => 'user', 'id' => $id, 'author' => $id,
            'created' => $source['created'], 'container' => $source['container'], 'version' => $source['version']]);
    }
    $author = $proof['author'];
    if ($author === $reporter && $type !== 'match' && !$allowOwn) {
        throw new BushisaReportError(400, 'Own content cannot be a primary report; use the human recovery route for disputed authorship');
    }
    $capture = ($proof['capturable'] ?? false) === true;
    if ($author !== null) {
        $actor = $pdo->prepare('SELECT deleted_at FROM users WHERE id = ? FOR UPDATE');
        $actor->execute([(int) $author]);
        $actorRow = $actor->fetch(PDO::FETCH_ASSOC);
        if (!is_array($actorRow)) {
            // A removed account has no trustworthy deletion/retention boundary.
            // Do not turn a lost protected reference into indefinite permission.
            throw new BushisaReportError(404);
        } elseif (!empty($actorRow['deleted_at'])) {
            $capture = false;
            if ($now >= bushisa_report_time($actorRow['deleted_at'])->modify('+7 days')->format('Y-m-d H:i:s.u')) {
                throw new BushisaReportError(404);
            }
        }
    }
    $source = bushisa_report_source($pdo, $reporter, $type, $id, true, $proof['version'], $capture,$proof['event']??null);
    if ($source === null || $source['created'] !== $proof['created']
        || $source['container'] !== $proof['container']
        || ($type === 'match' && ($proof['event'] ?? null) !== $source['event'])
        || ($source['expires'] !== null && $now >= $source['expires'])) {
        throw new BushisaReportError(404);
    }
    if ($type === 'match') {
        $author = $intent['conduct_actor_id'];
        if ($author === null || !in_array($author, $proof['participants'], true)) {
            // Legitimate pair/event, but conduct attribution remains ambiguous.
            $author = null;
        }
    } elseif ($source['author'] !== $proof['author']) {
        $author = null;
        $capture = false;
    }
    if ($author === $reporter && !$allowOwn) {
        throw new BushisaReportError(400, 'Own content cannot be a primary report; use the human recovery route for disputed authorship');
    }
    $body = $capture && hash_equals($proof['version'], $source['version']) ? $source['body'] : null;
    if (in_array($type, ['match', 'user'], true)) {
        $body = null;
    }
    return ['type' => $type, 'id' => $id, 'version' => $proof['version'],
        'container' => $proof['container'], 'author' => $author, 'body' => $body,
        'participants' => $proof['participants'], 'created' => $proof['created']];
}

function bushisa_report_case_expiry(array $row): ?string
{
    $expiry = $row['expires_at'] ?? null;
    if (!empty($row['final_resolution_at'])) {
        $days = in_array($row['report_resolution_class'] ?? '', ['unrelated', 'accidental'], true) ? 30 : 180;
        $finalExpiry = bushisa_report_time($row['final_resolution_at'])->modify('+' . $days . ' days')->format('Y-m-d H:i:s.u');
        $expiry = $expiry === null ? $finalExpiry : min($expiry, $finalExpiry);
    }
    return $expiry;
}

function bushisa_report_incident(PDO $pdo, int $reporter, string $receipt): array
{
    $stmt = $pdo->prepare('SELECT i.*, c.final_resolution_at, c.report_resolution_class, c.status AS case_status
        FROM report_incidents i JOIN moderation_cases c ON c.id = i.case_id
        WHERE i.reporter_id = ? AND i.receipt_id = ? FOR UPDATE');
    $stmt->execute([$reporter, $receipt]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row) || (($expiry = bushisa_report_case_expiry($row)) !== null && bushisa_report_now($pdo) >= $expiry)) {
        throw new BushisaReportError(404);
    }
    return $row;
}

function bushisa_report_receipt(PDO $pdo, int $reporter, int $submission): array
{
    $stmt = $pdo->prepare('SELECT s.submission_ref, s.received_at, s.purged_at, i.receipt_id,
            i.expires_at, c.final_resolution_at, c.report_resolution_class, c.status AS case_status
        FROM report_submissions s JOIN report_incidents i ON i.id = s.incident_id
        JOIN moderation_cases c ON c.id = i.case_id WHERE s.id = ? AND s.reporter_id = ?');
    $stmt->execute([$submission, $reporter]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $expiry = is_array($row) ? bushisa_report_case_expiry($row) : null;
    if (!is_array($row) || $row['purged_at'] !== null || ($expiry !== null && bushisa_report_now($pdo) >= $expiry)) {
        throw new BushisaReportError(404);
    }
    return ['receipt_id' => $row['receipt_id'], 'submission_id' => $row['submission_ref'],
        'received_at' => bushisa_report_iso($row['received_at']), 'status' => 'received',
        'operation_expires_at' => bushisa_report_iso($expiry)];
}

function bushisa_report_audit(PDO $pdo, int $actor, ?int $case, string $action, string $outcome): void
{
    $stmt = $pdo->prepare('INSERT INTO report_action_audit (actor_id, case_id, action_name, outcome_code, occurred_at, expires_at)
        VALUES (?, ?, ?, ?, UTC_TIMESTAMP(6), DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 365 DAY))');
    $stmt->execute([$actor, $case, $action, $outcome]);
}

/** Pure boundary decision, shared by actual integration and boundary tests. */
function bushisa_report_should_activate(?string $lastEnd, string $received, string $activation): bool
{
    return $lastEnd === null || ($received >= $lastEnd && $activation >= $lastEnd);
}

function bushisa_report_activate(PDO $pdo, int $actor, int $submission, string $received): void
{
    $stmt = $pdo->prepare('SELECT expires_at FROM report_probation_periods WHERE actor_id = ? ORDER BY expires_at DESC LIMIT 1 FOR UPDATE');
    $stmt->execute([$actor]);
    $end = $stmt->fetchColumn();
    $now = bushisa_report_now($pdo);
    if (!bushisa_report_should_activate($end === false ? null : (string) $end, $received, $now)) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO report_probation_periods (actor_id, trigger_submission_id, starts_at, expires_at)
        VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 168 HOUR))');
    $stmt->execute([$actor, $submission, $now, $now]);
}

function bushisa_report_capture(PDO $pdo, int $case, array $target): void
{
    if ($target['body'] === null) {
        return;
    }
    $stmt = $pdo->prepare('SELECT 1 FROM moderation_evidence WHERE case_id = ? AND source_type = ? AND source_id = ? AND report_source_version = ?');
    $stmt->execute([$case, $target['type'], $target['id'], $target['version']]);
    if ($stmt->fetchColumn()) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO moderation_evidence
        (case_id, source_type, source_id, subject_user_id, evidence_excerpt, evidence_hash, captured_at, purge_due_at, report_source_version)
        VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(6), NULL, ?)');
    $stmt->execute([$case, $target['type'], $target['id'], $target['author'], $target['body'], hash('sha256', $target['body']), $target['version']]);
}

function bushisa_report_attach_block(PDO $pdo, int $block, int $case, int $submission, int $reporter, ?int $subject): void
{
    if ($subject === null) {
        throw new BushisaReportError(404);
    }
    $stmt = $pdo->prepare('SELECT blocker_id, blocked_id FROM blocks WHERE id = ? FOR UPDATE');
    $stmt->execute([$block]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row) || !in_array($reporter, [(int) $row['blocker_id'], (int) $row['blocked_id']], true)
        || !in_array($subject, [(int) $row['blocker_id'], (int) $row['blocked_id']], true)) {
        throw new BushisaReportError(404);
    }
    $stmt = $pdo->prepare('INSERT IGNORE INTO report_block_links (block_id, case_id, submission_id, attached_at)
        VALUES (?, ?, ?, UTC_TIMESTAMP(6))');
    $stmt->execute([$block, $case, $submission]);
}

function bushisa_report_intake(PDO $pdo, int $reporter, array $payload, ?int $sessionVersion = null, ?string $receivedAt = null): array
{
    $intent = bushisa_report_validate($payload);
    $ring = bushisa_report_keys();
    if (!bushisa_report_available($pdo)) {
        throw new BushisaReportError(503, 'Reporting is unavailable');
    }
    // Keep the authenticated server receipt across lock waits/internal retries.
    // A rolled-back attempt is never an accepted report or success receipt.
    $received = $receivedAt ?? bushisa_report_now($pdo);
    $work = function () use ($pdo, $reporter, $intent, $ring, $sessionVersion, $received): array {
        if (!bushisa_report_actor_ok($pdo, $reporter, $sessionVersion)) {
            throw new BushisaReportError(401, 'Your session is no longer valid');
        }
        if (!bushisa_account_is_chat_eligible($pdo, $reporter)) {
            throw new BushisaReportError(403, 'Your account is not eligible for ordinary reporting');
        }
        // Refuse premature key removal, including during rolling deployments.
        foreach ($pdo->query('SELECT DISTINCT key_id FROM report_operations WHERE intent_hash IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) as $keyId) {
            if (!isset($ring['keys'][$keyId])) {
                throw new BushisaReportError(503, 'Reporting is unavailable');
            }
        }
        $canonical = bushisa_report_canonical($intent, $reporter, $ring);
        foreach ($ring['keys'] as $keyId => $secret) {
            $keyHash = bushisa_report_mac('operation-key', $intent['idempotency_key'], $secret);
            $stmt = $pdo->prepare('SELECT * FROM report_operations WHERE reporter_id = ? AND key_hash = ? FOR UPDATE');
            $stmt->execute([$reporter, $keyHash]);
            $operation = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($operation)) {
                if ((int) $operation['contract_version'] !== 1 || $operation['intent_hash'] === null) {
                    throw new BushisaReportError(404);
                }
                if (!hash_equals($operation['intent_hash'], bushisa_report_mac('intent', $canonical, $secret))) {
                    throw new BushisaReportError(409, 'Submission key already used for another intent');
                }
                return bushisa_report_receipt($pdo, $reporter, (int) $operation['submission_id']);
            }
        }
        $now = bushisa_report_now($pdo);
        $existing = $intent['receipt_id'] === null ? null : bushisa_report_incident($pdo, $reporter, $intent['receipt_id']);
        $target = $intent['target_type'] === null ? null : bushisa_report_resolve($pdo, $reporter, $intent, $ring, $now);
        if ($target === null && $existing === null) {
            throw new BushisaReportError(404);
        }
        if ($target !== null && $target['type'] === 'user' && $existing === null && !$intent['new_incident']) {
            throw new BushisaReportError(400, 'Choose a new incident or add information to your own report');
        }
        $anchor = $target === null ? $existing['anchor_hash'] : hash('sha256', json_encode([
            $target['type'], $target['id'], $target['version'],
            $target['type'] === 'match' ? $target['author'] : null,
            $target['type'] === 'user' ? $intent['idempotency_key'] : null,
        ], JSON_THROW_ON_ERROR));
        $stmt = $pdo->prepare('SELECT receipt_id FROM report_incidents WHERE reporter_id = ? AND anchor_hash = ? FOR UPDATE');
        $stmt->execute([$reporter, $anchor]);
        $known = $stmt->fetchColumn();
        $newReport = $known === false;
        if ($known !== false) {
            $incident = bushisa_report_incident($pdo, $reporter, (string) $known);
            if ($existing !== null && (int) $existing['case_id'] !== (int) $incident['case_id']) {
                $link = $pdo->prepare('INSERT IGNORE INTO report_case_links (from_case_id, to_case_id, reporter_id, created_at)
                    VALUES (?, ?, ?, UTC_TIMESTAMP(6))');
                $link->execute([(int) $existing['case_id'], (int) $incident['case_id'], $reporter]);
                bushisa_report_audit($pdo, $reporter, (int) $existing['case_id'], 'case_link', 'review_link_only');
            }
        } else {
            if ($target === null) {
                throw new BushisaReportError(404);
            }
            if ($existing === null) {
                $stmt = $pdo->prepare('INSERT INTO moderation_cases
                    (reporter_id, target_type, target_id, reported_user_id, reason, status, created_at, evidence_purge_due_at, intake_contract_version)
                    VALUES (?, ?, ?, ?, ?, "pending", UTC_TIMESTAMP(6), NULL, 1)');
                $stmt->execute([$reporter, $target['type'], $target['id'], $target['author'], $intent['reason']]);
                $caseId = (int) $pdo->lastInsertId();
            } else {
                $caseId = (int) $existing['case_id'];
            }
            $stmt = $pdo->prepare('INSERT INTO report_incidents
                (reporter_id, anchor_hash, receipt_id, case_id, key_id, created_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(6))');
            $stmt->execute([$reporter, $anchor, bin2hex(random_bytes(32)), $caseId, $ring['active']]);
            $incidentId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT receipt_id FROM report_incidents WHERE id = ?');
            $stmt->execute([$incidentId]);
            $incident = bushisa_report_incident($pdo, $reporter, (string) $stmt->fetchColumn());
        }
        $secret = $ring['keys'][$incident['key_id']] ?? null;
        if ($secret === null) {
            throw new BushisaReportError(503, 'Reporting is unavailable');
        }
        // Equivalent proof envelopes do not create new information. Resolve only
        // their actor-bound selectors here; private bodies remain scoped below.
        $contexts = [];
        foreach ($intent['context_tokens'] as $token) {
            $proof = bushisa_report_open($token, $reporter, $ring);
            if (($proof['type'] ?? '') !== 'message') {
                throw new BushisaReportError(404);
            }
            $context = bushisa_report_resolve_context($pdo, $reporter, [
                'context' => $token, 'target_type' => 'message', 'target_id' => $proof['id'],
                'conduct_actor_id' => null,
            ], $ring, $now);
            $contexts[$context['id'] . ':' . $context['version']] = $context;
        }
        ksort($contexts, SORT_STRING);
        $contextRefs = array_map(static fn(array $c): array => [$c['type'], $c['id'], $c['version'], $c['container']], array_values($contexts));
        $info = bushisa_report_mac('case-information', json_encode([$intent['reason'], $intent['explanation'],
            $contextRefs], JSON_THROW_ON_ERROR), $secret);
        $stmt = $pdo->prepare('SELECT id FROM report_submissions WHERE incident_id = ? AND information_hash = ? FOR UPDATE');
        $stmt->execute([(int) $incident['id'], $info]);
        $submission = $stmt->fetchColumn();
        if ($submission === false) {
            $stmt = $pdo->prepare('INSERT INTO report_submissions
                (submission_ref, incident_id, reporter_id, reported_actor_id, information_hash, reason_code, explanation, is_new_report, received_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([bin2hex(random_bytes(32)), (int) $incident['id'], $reporter, $target['author'] ?? null,
                $info, $intent['reason'], $intent['explanation'], $newReport ? 1 : 0, $received]);
            $submission = (int) $pdo->lastInsertId();
            $caseId = (int) $incident['case_id'];
            if ($target !== null) {
                bushisa_report_capture($pdo, $caseId, $target);
            }
            if ($intent['context_tokens'] !== [] && $target === null) {
                throw new BushisaReportError(400, 'Supporting context needs an authorized primary target');
            }
            $before = $after = 0;
            foreach ($contexts as $context) {
                if ($target['type'] === 'message') {
                    if ($context['container'] !== $target['container']) {
                        throw new BushisaReportError(404);
                    }
                    if ([$context['created'], $context['id']] < [$target['created'], $target['id']]) { $before++; }
                    elseif ([$context['created'], $context['id']] > [$target['created'], $target['id']]) { $after++; }
                    if ($before > 10 || $after > 10) {
                        throw new BushisaReportError(400, 'Supporting context exceeds the permitted scope');
                    }
                } elseif ($target['author'] === null || !in_array($target['author'], $context['participants'], true)) {
                    throw new BushisaReportError(404);
                }
                bushisa_report_capture($pdo, $caseId, $context);
            }
            if ($newReport && $target['author'] !== null) {
                bushisa_report_activate($pdo, $target['author'], $submission, $received);
            }
            bushisa_report_audit($pdo, $reporter, $caseId, 'report_received', $newReport ? 'new_report' : 'information_added');
        }
        $caseId = (int) $incident['case_id'];
        // A deliberate combined action can add a block to an already reported
        // incident. Only a completed same-key retry skips these effects.
        if ($intent['action'] === 'block_and_report') {
            if ($target['author'] === null) {
                throw new BushisaReportError(404);
            }
            $stmt = $pdo->prepare('INSERT IGNORE INTO blocks (blocker_id, blocked_id, created_at) VALUES (?, ?, UTC_TIMESTAMP())');
            $stmt->execute([$reporter, $target['author']]);
            $stmt = $pdo->prepare('SELECT id FROM blocks WHERE blocker_id = ? AND blocked_id = ?');
            $stmt->execute([$reporter, $target['author']]);
            $block = (int) $stmt->fetchColumn();
            bushisa_close_match_between($pdo, $reporter, $target['author'], 'blocked');
            bushisa_report_attach_block($pdo, $block, $caseId, $submission, $reporter, $target['author']);
        } elseif ($intent['block_id'] !== null) {
            bushisa_report_attach_block($pdo, $intent['block_id'], $caseId, $submission, $reporter, $target['author'] ?? null);
        }
        $keyId = $ring['active'];
        $stmt = $pdo->prepare('INSERT INTO report_operations
            (reporter_id, key_hash, key_id, action_name, contract_version, intent_hash, submission_id, created_at)
            VALUES (?, ?, ?, ?, 1, ?, ?, UTC_TIMESTAMP(6))');
        $stmt->execute([$reporter, bushisa_report_mac('operation-key', $intent['idempotency_key'], $ring['keys'][$keyId]),
            $keyId, $intent['action'], bushisa_report_mac('intent', $canonical, $ring['keys'][$keyId]), (int) $submission]);
        return bushisa_report_receipt($pdo, $reporter, (int) $submission);
    };
    for ($attempt = 0; ; $attempt++) {
        try {
            return bushisa_report_transaction($pdo, $work);
        } catch (PDOException $error) {
            // One bounded retry of a fully rolled-back serialization/deadlock
            // failure, with fresh source/account checks and the original clock.
            if ($attempt >= 1 || ($error->errorInfo[0] ?? $error->getCode()) !== '40001') {
                throw $error;
            }
        }
    }
}

function bushisa_report_resolve_context(PDO $pdo, int $reporter, array $intent, array $ring, string $now): array
{
    return bushisa_report_resolve($pdo, $reporter, $intent, $ring, $now, true);
}

function bushisa_report_own_status(PDO $pdo, int $reporter, string $receipt, ?int $version): array
{
    return bushisa_report_transaction($pdo, function () use ($pdo, $reporter, $receipt, $version): array {
        if (!bushisa_report_actor_ok($pdo, $reporter, $version)) {
            throw new BushisaReportError(401, 'Your session is no longer valid');
        }
        $incident = bushisa_report_incident($pdo, $reporter, $receipt);
        $stmt = $pdo->prepare('SELECT id FROM report_submissions WHERE incident_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([(int) $incident['id']]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new BushisaReportError(404);
        }
        bushisa_report_audit($pdo, $reporter, (int) $incident['case_id'], 'own_receipt', 'allowed');
        return bushisa_report_receipt($pdo, $reporter, (int) $id);
    });
}

/** Must be called inside the review transaction. */
function bushisa_report_require_reviewer(PDO $pdo, int $reviewer, int $case, array $auth): void
{
        if (!bushisa_report_actor_ok($pdo, $reviewer, $auth['session_version'] ?? -1)
            || ($auth['mfa_verified'] ?? false) !== true
            || ($auth['mfa_user_id'] ?? null) !== $reviewer
            || ($auth['mfa_session_version'] ?? null) !== ($auth['session_version'] ?? -1)) {
            throw new BushisaReportError(403, 'Assigned reviewer and verified MFA required');
        }
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$reviewer]);
        $role = $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT 1 FROM moderation_case_assignments WHERE case_id = ? AND assigned_user_id = ? AND revoked_at IS NULL FOR UPDATE');
        $stmt->execute([$case, $reviewer]);
        if (!in_array($role, ['moderator', 'admin'], true) || !$stmt->fetchColumn()) {
            throw new BushisaReportError(403, 'Assigned reviewer required');
        }
}

function bushisa_report_block_scope(PDO $pdo, int $case, int $block): array
{
        $stmt = $pdo->prepare('SELECT id, submission_id FROM report_block_links WHERE case_id = ? AND block_id = ? AND cleared_at IS NULL ORDER BY id FOR UPDATE');
        $stmt->execute([$case, $block]);
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($links === []) {
            throw new BushisaReportError(404);
        }
        return $links;
}

function bushisa_report_block_review(PDO $pdo, int $reviewer, int $case, int $block, array $auth): array
{
    return bushisa_report_transaction($pdo, function () use ($pdo, $reviewer, $case, $block, $auth): array {
        bushisa_report_require_reviewer($pdo, $reviewer, $case, $auth);
        $links = bushisa_report_block_scope($pdo, $case, $block);
        $ring = bushisa_report_keys();
        $id = $ring['active'];
        $scope = json_encode([$case, $block, $reviewer, $auth['session_version'], $links], JSON_THROW_ON_ERROR);
        bushisa_report_audit($pdo, $reviewer, $case, 'block_review', 'current_scope');
        return ['review_version' => $id . '.' . bushisa_report_mac('block-clearance', $scope, $ring['keys'][$id])];
    });
}

/** An unseen newly attached submission invalidates the reviewed scope. */
function bushisa_report_clear_block(PDO $pdo, int $reviewer, int $case, int $block, array $auth, string $reviewVersion = ''): void
{
    bushisa_report_transaction($pdo, function () use ($pdo, $reviewer, $case, $block, $auth, $reviewVersion): void {
        bushisa_report_require_reviewer($pdo, $reviewer, $case, $auth);
        $links = bushisa_report_block_scope($pdo, $case, $block);
        $parts = explode('.', $reviewVersion, 2);
        $ring = bushisa_report_keys();
        $scope = json_encode([$case, $block, $reviewer, $auth['session_version'], $links], JSON_THROW_ON_ERROR);
        if (count($parts) !== 2 || !isset($ring['keys'][$parts[0]])
            || !hash_equals(bushisa_report_mac('block-clearance', $scope, $ring['keys'][$parts[0]]), $parts[1])) {
            throw new BushisaReportError(409, 'Reports changed; review the current block scope before clearing');
        }
        $stmt = $pdo->prepare('UPDATE report_block_links SET cleared_by = ?, cleared_at = UTC_TIMESTAMP(6)
            WHERE case_id = ? AND block_id = ? AND cleared_at IS NULL');
        $stmt->execute([$reviewer, $case, $block]);
        bushisa_report_audit($pdo, $reviewer, $case, 'block_clearance', 'contact_reviewed');
        // The personal block is intentionally untouched.
    });
}

function bushisa_report_guard_unblock(PDO $pdo, int $block): void
{
    if (!operational_table_exists($pdo, 'report_block_links')) {
        return;
    }
    $stmt = $pdo->prepare('SELECT 1 FROM report_block_links WHERE block_id = ? AND cleared_at IS NULL LIMIT 1 FOR UPDATE');
    $stmt->execute([$block]);
    if ($stmt->fetchColumn()) {
        throw new BushisaReportError(409, 'Reviewer clearance is required before removing this block');
    }
}

/** Assigned, current, MFA-authenticated review; no privileged-account bypass. */
function bushisa_report_review_evidence(PDO $pdo, int $case, int $reviewer, array $auth): array
{
    return bushisa_report_transaction($pdo, function () use ($pdo, $case, $reviewer, $auth): array {
        if (($auth['user_id'] ?? null) !== $reviewer || ($auth['mfa_verified'] ?? false) !== true
            || ($auth['mfa_user_id'] ?? null) !== $reviewer
            || ($auth['mfa_session_version'] ?? null) !== ($auth['session_version'] ?? -1)
            || !bushisa_report_actor_ok($pdo, $reviewer, $auth['session_version'] ?? -1)) {
            return [];
        }
        $stmt = $pdo->prepare('SELECT 1 FROM users u JOIN moderation_case_assignments a ON a.assigned_user_id = u.id
            WHERE u.id = ? AND u.role IN ("moderator", "admin") AND a.case_id = ? AND a.revoked_at IS NULL FOR UPDATE');
        $stmt->execute([$reviewer, $case]);
        if (!$stmt->fetchColumn()) { return []; }
        $stmt = $pdo->prepare('SELECT evidence_purge_due_at AS expires_at, final_resolution_at, report_resolution_class
            FROM moderation_cases WHERE id = ? AND intake_contract_version = 1 FOR UPDATE');
        $stmt->execute([$case]);
        $caseRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($caseRow)) { return []; }
        $expiry = bushisa_report_case_expiry($caseRow);
        if ($expiry !== null && bushisa_report_now($pdo) >= $expiry) { return []; }
        $stmt = $pdo->prepare('SELECT i.expires_at, c.final_resolution_at, c.report_resolution_class
            FROM report_incidents i JOIN moderation_cases c ON c.id = i.case_id WHERE i.case_id = ? FOR UPDATE');
        $stmt->execute([$case]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $expiry = bushisa_report_case_expiry($row);
            if ($expiry !== null && bushisa_report_now($pdo) >= $expiry) { return []; }
        }
        $stmt = $pdo->prepare('SELECT id, source_type, source_id, evidence_excerpt, captured_at, purge_due_at
            FROM moderation_evidence WHERE case_id = ? AND purged_at IS NULL
            AND (purge_due_at IS NULL OR purge_due_at > UTC_TIMESTAMP(6)) ORDER BY id');
        $stmt->execute([$case]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        bushisa_report_audit($pdo, $reviewer, $case, 'evidence_view', 'assigned_case_review');
        return $rows;
    });
}

/** Used under the same transaction lock as successful request creation. */
function bushisa_report_request_allowance(PDO $pdo, int $sender): void
{
    $stmt = $pdo->prepare('SELECT 1 FROM report_probation_periods WHERE actor_id = ? AND starts_at <= UTC_TIMESTAMP(6) AND expires_at > UTC_TIMESTAMP(6) LIMIT 1');
    $stmt->execute([$sender]);
    $hours = $stmt->fetchColumn() ? 24 : 12;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM message_request_sends
        WHERE requester_id = ? AND created_at > DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . $hours . ' HOUR)');
    $stmt->execute([$sender]);
    if ((int) $stmt->fetchColumn() >= 3) {
        throw new BushisaReportError(429, 'Sending allowance reached; try again after the rolling window');
    }
}

function bushisa_report_record_send(PDO $pdo, int $sender, int $recipient, int $request): void
{
    $stmt = $pdo->prepare('INSERT INTO pair_request_counts (requester_id, recipient_id, request_count) VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE request_count = request_count + 1');
    $stmt->execute([$sender, $recipient]);
    $stmt = $pdo->prepare('INSERT INTO message_request_sends (requester_id, recipient_id, request_id, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP(6))');
    $stmt->execute([$sender, $recipient, $request]);
}

/** Erases ordinary intake copies at immutable final-resolution deadlines.
 * Private evidence holds/full L03 object cleanup are a separate release gate. */
function bushisa_report_cleanup(PDO $pdo): array
{
    if (!operational_table_exists($pdo, 'report_incidents')) {
        return ['intake_copies_purged' => 0];
    }
    $pdo->exec('UPDATE report_incidents i JOIN moderation_cases c ON c.id = i.case_id
        SET i.expires_at = CASE WHEN i.expires_at IS NULL THEN
            DATE_ADD(c.final_resolution_at, INTERVAL (CASE WHEN c.report_resolution_class IN ("unrelated", "accidental") THEN 30 ELSE 180 END) DAY)
            ELSE LEAST(i.expires_at, DATE_ADD(c.final_resolution_at, INTERVAL (CASE WHEN c.report_resolution_class IN ("unrelated", "accidental") THEN 30 ELSE 180 END) DAY)) END
        WHERE c.final_resolution_at IS NOT NULL');
    $stmt = $pdo->prepare('UPDATE report_submissions s JOIN report_incidents i ON i.id = s.incident_id
        SET s.explanation = NULL, s.information_hash = SHA2(CONCAT("expired:", s.id), 256),
            s.purged_at = COALESCE(s.purged_at, UTC_TIMESTAMP(6)), s.expires_at = i.expires_at
        WHERE i.expires_at <= UTC_TIMESTAMP(6) AND s.purged_at IS NULL');
    $stmt->execute();
    $count = $stmt->rowCount();
    $pdo->exec('UPDATE moderation_evidence e JOIN moderation_cases c ON c.id = e.case_id
        SET e.purge_due_at = CASE WHEN e.purge_due_at IS NULL THEN
            DATE_ADD(c.final_resolution_at, INTERVAL (CASE WHEN c.report_resolution_class IN ("unrelated", "accidental") THEN 30 ELSE 180 END) DAY)
            ELSE LEAST(e.purge_due_at, DATE_ADD(c.final_resolution_at, INTERVAL (CASE WHEN c.report_resolution_class IN ("unrelated", "accidental") THEN 30 ELSE 180 END) DAY)) END
        WHERE c.intake_contract_version = 1 AND c.final_resolution_at IS NOT NULL');
    $evidence = $pdo->prepare('UPDATE moderation_evidence e JOIN moderation_cases c ON c.id = e.case_id
        SET e.evidence_excerpt = NULL, e.evidence_hash = NULL, e.purged_at = COALESCE(e.purged_at, UTC_TIMESTAMP(6))
        WHERE c.intake_contract_version = 1 AND e.purge_due_at <= UTC_TIMESTAMP(6) AND e.purged_at IS NULL
        AND NOT EXISTS (SELECT 1 FROM report_evidence_holds h WHERE h.evidence_id = e.id AND h.case_id = e.case_id
            AND h.starts_at <= UTC_TIMESTAMP(6) AND h.expires_at > UTC_TIMESTAMP(6) AND h.released_at IS NULL)');
    $evidence->execute();
    $evidenceCount = $evidence->rowCount();
    $pdo->exec('UPDATE report_operations o JOIN report_submissions s ON s.id = o.submission_id
        SET o.intent_hash = NULL, o.expires_at = s.expires_at WHERE s.purged_at IS NOT NULL');
    $pdo->exec('DELETE FROM report_action_audit WHERE expires_at <= UTC_TIMESTAMP(6)');
    // Only the longest approved rolling window needs timestamps. The smaller
    // pair ledger preserves the lifetime total independently of this purge.
    $pdo->exec('DELETE FROM message_request_sends WHERE created_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 24 HOUR)');
    $pdo->exec('DELETE l FROM report_case_links l JOIN moderation_cases a ON a.id = l.from_case_id
        JOIN moderation_cases b ON b.id = l.to_case_id
        WHERE DATE_ADD(a.final_resolution_at, INTERVAL 365 DAY) <= UTC_TIMESTAMP(6)
           OR DATE_ADD(b.final_resolution_at, INTERVAL 365 DAY) <= UTC_TIMESTAMP(6)');
    // Content-free decision/idempotency references have their own 365-day
    // final-resolution bound. Active block protections are independent rows.
    $pdo->exec('DELETE o FROM report_operations o JOIN report_submissions s ON s.id = o.submission_id
        JOIN report_incidents i ON i.id = s.incident_id JOIN moderation_cases c ON c.id = i.case_id
        WHERE DATE_ADD(c.final_resolution_at, INTERVAL 365 DAY) <= UTC_TIMESTAMP(6)');
    $pdo->exec('DELETE p FROM report_probation_periods p JOIN report_submissions s ON s.id = p.trigger_submission_id
        JOIN report_incidents i ON i.id = s.incident_id JOIN moderation_cases c ON c.id = i.case_id
        WHERE p.expires_at <= UTC_TIMESTAMP(6) AND DATE_ADD(c.final_resolution_at, INTERVAL 365 DAY) <= UTC_TIMESTAMP(6)');
    $pdo->exec('DELETE s FROM report_submissions s JOIN report_incidents i ON i.id = s.incident_id
        JOIN moderation_cases c ON c.id = i.case_id
        WHERE DATE_ADD(c.final_resolution_at, INTERVAL 365 DAY) <= UTC_TIMESTAMP(6)');
    $pdo->exec('DELETE i FROM report_incidents i JOIN moderation_cases c ON c.id = i.case_id
        WHERE DATE_ADD(c.final_resolution_at, INTERVAL 365 DAY) <= UTC_TIMESTAMP(6)');
    return ['intake_copies_purged' => $count, 'text_evidence_purged' => $evidenceCount];
}
