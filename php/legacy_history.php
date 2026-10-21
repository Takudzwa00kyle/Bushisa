<?php
declare(strict_types=1);

namespace Bushisa\LegacyHistory;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use stdClass;

// Deliberately independent of application/bootstrap, PDO, mail and runtime DDL.
const MAX_BYTES = 1048576;
const MAX_DEPTH = 24;
const MAX_NODES = 20000;
const MAX_FACTS = 1000;
const MAX_LIST = 256;
const MAX_COUNT = 2147483647;
const DIMENSIONS = ['requests', 'request_state', 'endings', 'restoration', 'blocks_clearance', 'account_consent_sessions', 'global_windows_probation'];
const EVENT_TYPES = ['request_created', 'request_approved', 'request_declined', 'request_cancelled', 'request_expired', 'chat_ended', 'unmatched', 'restored', 'mutual_matched', 'block_created', 'block_cleared', 'report_accepted', 'probation_started'];

final class InvalidInput extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly string $outcome = 'REJECT_INPUT')
    {
        parent::__construct($reason); // Fixed codes only; never echo supplied content.
    }
}

/** Small bounded JSON reader: preserves object/list distinction and rejects duplicate decoded keys. */
final class JsonReader
{
    private int $offset = 0;
    private int $nodes = 0;

    public function __construct(private readonly string $input)
    {
        if (strlen($input) > MAX_BYTES || preg_match('//u', $input) !== 1) {
            throw new InvalidInput('JSON_SIZE_OR_UTF8');
        }
    }

    public function read(): mixed
    {
        $value = $this->value(0);
        $this->space();
        if ($this->offset !== strlen($this->input)) { throw new InvalidInput('JSON_TRAILING_DATA'); }
        return $value;
    }

    private function space(): void
    {
        while (isset($this->input[$this->offset]) && str_contains(" \t\r\n", $this->input[$this->offset])) { $this->offset++; }
    }

    private function string(): string
    {
        $start = $this->offset++;
        while (isset($this->input[$this->offset])) {
            $c = $this->input[$this->offset++];
            if ($c === '\\') { $this->offset++; continue; }
            if ($c === '"') {
                try { return json_decode(substr($this->input, $start, $this->offset - $start), false, MAX_DEPTH, JSON_THROW_ON_ERROR); }
                catch (\JsonException) { throw new InvalidInput('JSON_STRING'); }
            }
        }
        throw new InvalidInput('JSON_STRING');
    }

    private function value(int $depth): mixed
    {
        if ($depth > MAX_DEPTH || ++$this->nodes > MAX_NODES) { throw new InvalidInput('JSON_COMPLEXITY'); }
        $this->space();
        $c = $this->input[$this->offset] ?? '';
        if ($c === '"') { return $this->string(); }
        if ($c === '{' || $c === '[') {
            $object = $c === '{'; $end = $object ? '}' : ']'; $this->offset++;
            $result = $object ? new stdClass() : [];
            $this->space();
            if (($this->input[$this->offset] ?? '') === $end) { $this->offset++; return $result; }
            while (true) {
                $this->space();
                if ($object) {
                    if (($this->input[$this->offset] ?? '') !== '"') { throw new InvalidInput('JSON_OBJECT'); }
                    $key = $this->string();
                    if (strlen($key) > 128 || preg_match('/[\x00-\x1f]/', $key)) { throw new InvalidInput('JSON_KEY'); }
                    if (property_exists($result, $key)) { throw new InvalidInput('JSON_DUPLICATE_KEY'); }
                    $this->space();
                    if (($this->input[$this->offset++] ?? '') !== ':') { throw new InvalidInput('JSON_OBJECT'); }
                    $result->{$key} = $this->value($depth + 1);
                } else { $result[] = $this->value($depth + 1); }
                $this->space(); $next = $this->input[$this->offset++] ?? '';
                if ($next === $end) { return $result; }
                if ($next !== ',') { throw new InvalidInput('JSON_SEPARATOR'); }
            }
        }
        foreach (['true' => true, 'false' => false, 'null' => null] as $literal => $value) {
            if (substr($this->input, $this->offset, strlen($literal)) === $literal) { $this->offset += strlen($literal); return $value; }
        }
        if (preg_match('/\G-?(?:0|[1-9][0-9]*)(?![0-9.eE])/A', $this->input, $m, 0, $this->offset)) {
            $this->offset += strlen($m[0]);
            if (strlen(ltrim($m[0], '-')) > 10) { throw new InvalidInput('JSON_INTEGER_RANGE'); }
            $n = (int) $m[0];
            if (abs($n) > MAX_COUNT) { throw new InvalidInput('JSON_INTEGER_RANGE'); }
            return $n;
        }
        throw new InvalidInput('JSON_VALUE');
    }
}

function decode(string $input): mixed { return (new JsonReader($input))->read(); }

/** Only explicit local regular files, read once through a bounded handle. No wrappers or supplied paths in manifests. */
function read_artifact(string $path): array
{
    if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '\\\\') || str_starts_with($path, '//') || preg_match('~^[a-z][a-z0-9+.-]*://~i', $path)) { throw new InvalidInput('LOCAL_FILE_REQUIRED'); }
    $real = realpath($path);
    if ($real === false || !is_file($real)) { throw new InvalidInput('LOCAL_FILE_REQUIRED'); }
    $handle = @fopen($real, 'rb');
    if ($handle === false) { throw new InvalidInput('FILE_UNREADABLE'); }
    try {
        $stat = fstat($handle);
        if ($stat === false || ($stat['mode'] & 0170000) !== 0100000 || $stat['size'] > MAX_BYTES) { throw new InvalidInput('FILE_SIZE_OR_TYPE'); }
        $bytes = stream_get_contents($handle, MAX_BYTES + 1);
        if ($bytes === false) { throw new InvalidInput('FILE_UNREADABLE'); }
        return ['value' => decode($bytes), 'raw_sha256' => hash('sha256', $bytes)];
    } finally { fclose($handle); }
}
function read_file(string $path): mixed { return read_artifact($path)['value']; }

function object(mixed $v, array $keys): stdClass
{
    if (!$v instanceof stdClass) { throw new InvalidInput('SCHEMA_OBJECT'); }
    $actual = array_keys(get_object_vars($v)); sort($actual); sort($keys);
    if ($actual !== $keys) { throw new InvalidInput('SCHEMA_FIELDS'); }
    return $v;
}
function items(mixed $v, int $max = MAX_LIST): array
{
    if (!is_array($v) || !array_is_list($v) || count($v) > $max) { throw new InvalidInput('SCHEMA_LIST'); }
    return $v;
}
function ref(mixed $v): string
{
    if (!is_string($v) || !preg_match('/\Asyn:[A-Za-z0-9][A-Za-z0-9._:-]{0,90}\z/D', $v)) { throw new InvalidInput('SYNTHETIC_REFERENCE_REQUIRED'); }
    return $v;
}
function number(mixed $v): int
{
    if (!is_int($v) || $v < 0 || $v > MAX_COUNT) { throw new InvalidInput('SCHEMA_COUNTER'); }
    return $v;
}
function flag(mixed $v): bool
{
    if (!is_bool($v)) { throw new InvalidInput('SCHEMA_BOOLEAN'); }
    return $v;
}
function choice(mixed $v, array $choices): string
{
    if (!is_string($v) || !in_array($v, $choices, true)) { throw new InvalidInput('SCHEMA_ENUM'); }
    return $v;
}
function sha(mixed $v): string
{
    if (!is_string($v) || !preg_match('/\A[a-f0-9]{64}\z/D', $v)) { throw new InvalidInput('SCHEMA_DIGEST'); }
    return $v;
}
function instant(mixed $v): string
{
    if (!is_string($v) || !preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\.[0-9]{6})?Z\z/D', $v)) { throw new InvalidInput('UTC_REQUIRED'); }
    $format = str_contains($v, '.') ? 'Y-m-d\TH:i:s.u\Z' : 'Y-m-d\TH:i:s\Z';
    $date = DateTimeImmutable::createFromFormat('!' . $format, $v, new DateTimeZone('UTC'));
    if ($date === false || $date->format($format) !== $v) { throw new InvalidInput('UTC_CALENDAR'); }
    return $date->format('Y-m-d\TH:i:s.u\Z');
}
function clock(mixed $v): ?string
{
    object($v, ['value', 'precision']);
    choice($v->precision, ['unknown', 'absent', 'second', 'microsecond']);
    if (in_array($v->precision, ['unknown', 'absent'], true)) {
        if ($v->value !== null) { throw new InvalidInput('CLOCK_PRECISION'); }
        return null;
    }
    $time = instant($v->value);
    if (($v->precision === 'second') === str_contains($v->value, '.')) { throw new InvalidInput('CLOCK_PRECISION'); }
    return $time;
}
function counts(mixed $v): void { object($v, ['low_to_high', 'high_to_low']); number($v->low_to_high); number($v->high_to_low); }
function last_end(mixed $v): void { object($v, ['actor', 'time']); if ($v->actor !== null) { ref($v->actor); } clock($v->time); }

function canonical(mixed $value): mixed
{
    if ($value instanceof stdClass) {
        $out = new stdClass(); $fields = get_object_vars($value); ksort($fields, SORT_STRING);
        foreach ($fields as $key => $v) { $out->{$key} = canonical($v); } return $out;
    }
    if (is_array($value)) { if (!array_is_list($value)) { ksort($value, SORT_STRING); } return array_map(__NAMESPACE__ . '\\canonical', $value); }
    return $value;
}
function encode(mixed $value): string { return json_encode(canonical($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }
function digest(mixed $value, string $domain = 'value'): string { return hash('sha256', 'bushisa.legacy.v1:' . $domain . "\0" . encode($value)); }
function set_values(array $values): array
{
    $byValue = []; foreach ($values as $value) { $byValue[encode($value)] = $value; } ksort($byValue, SORT_STRING); return array_values($byValue);
}
function bundle_digest(stdClass $bundle): string
{
    $b = clone $bundle;
    $b->events = set_values($b->events); $b->observations = set_values($b->observations); $b->equivalences = set_values($b->equivalences);
    $b->aliases = set_values($b->aliases);
    $b->coverage = clone $b->coverage;
    foreach (['writers', 'ranges', 'dimensions', 'exclusions'] as $k) { $b->coverage->{$k} = set_values($b->coverage->{$k}); }
    return digest($b, 'bundle');
}
function fact_id(stdClass $event): string { return digest($event->identity, 'fact-identity'); }
function proof_digest(string $kind, stdClass $bundle, mixed $claim): string
{
    return digest(['kind' => $kind, 'source_incarnation' => $bundle->source->incarnation, 'destination_incarnation' => $bundle->destination_incarnation,
        'pair_ref' => $bundle->pair_ref, 'claim' => $claim], 'synthetic-proof-claim');
}

function event_schema(mixed $e): void
{
    object($e, ['identity', 'type', 'actor', 'recipient', 'position', 'time', 'expires_at', 'retain_until', 'request_ref', 'generation', 'source_ref']);
    object($e->identity, ['incarnation', 'stream', 'native_key', 'ordinal']);
    foreach (['incarnation', 'stream', 'native_key'] as $k) { ref($e->identity->{$k}); }
    if ($e->identity->ordinal !== null) { number($e->identity->ordinal); }
    choice($e->type, EVENT_TYPES);
    foreach (['actor', 'recipient', 'source_ref'] as $k) { if ($e->{$k} !== null) { ref($e->{$k}); } }
    if ($e->position !== null) { number($e->position); }
    if ($e->generation !== null) { number($e->generation); }
    clock($e->time); if ($e->expires_at !== null) { clock($e->expires_at); }
    clock($e->retain_until);
    if ($e->request_ref !== null) { sha($e->request_ref); }
}

function bundle_schema(mixed $b): stdClass
{
    object($b, ['format', 'domain', 'policy_semantics', 'source', 'destination_incarnation', 'pair_ref', 'accounts', 'aliases', 'provenance', 'checkpoint', 'coverage', 'events', 'observations', 'equivalences', 'expected']);
    choice($b->format, ['bushisa.legacy-history.v1']); choice($b->domain, ['synthetic']); choice($b->policy_semantics, ['decisions-1-33-v1']);
    object($b->source, ['ref', 'incarnation', 'stream', 'schema_ref']); foreach (get_object_vars($b->source) as $v) { ref($v); }
    ref($b->destination_incarnation); ref($b->pair_ref);
    $accounts = items($b->accounts, 2); if (count($accounts) !== 2) { throw new InvalidInput('TWO_ORIGINAL_ACCOUNTS_REQUIRED'); }
    foreach ($accounts as $a) { object($a, ['source_ref', 'destination_ref', 'created_at']); ref($a->source_ref); ref($a->destination_ref); clock($a->created_at); }
    if ($accounts[0]->source_ref >= $accounts[1]->source_ref || $accounts[0]->destination_ref === $accounts[1]->destination_ref) { throw new InvalidInput('CANONICAL_ACCOUNT_MAPPING'); }
    foreach (items($b->aliases) as $alias) { ref($alias); }
    object($b->provenance, ['collector_ref', 'authority_ref', 'custody_ref', 'artifact_ref', 'tool_digest', 'captured_at', 'expires_at']);
    foreach (['collector_ref', 'authority_ref', 'custody_ref', 'artifact_ref'] as $k) { if ($b->provenance->{$k} !== null) { ref($b->provenance->{$k}); } }
    sha($b->provenance->tool_digest); clock($b->provenance->captured_at); clock($b->provenance->expires_at);
    if ($b->checkpoint !== null) {
        $c = object($b->checkpoint, ['ref', 'position', 'basis', 'proof_ref', 'counts', 'ending_count', 'last_end']);
        ref($c->ref); number($c->position); choice($c->basis, ['certified_prefix', 'account_origin']);
        if ($c->proof_ref !== null) { ref($c->proof_ref); } counts($c->counts); number($c->ending_count); last_end($c->last_end);
    }
    $c = object($b->coverage, ['B', 'W', 'H', 'C', 'method', 'writers', 'ranges', 'dimensions', 'exclusions']);
    foreach (['B', 'W', 'H', 'C'] as $k) { if ($c->{$k} !== null) { number($c->{$k}); } }
    choice($c->method, ['synthetic_stream', 'synthetic_quiesced']);
    foreach (items($c->writers, 12) as $w) { object($w, ['id', 'revision', 'from', 'through']); if (!is_string($w->id) || !preg_match('/\AW(?:0[1-9]|1[0-2])\z/', $w->id)) { throw new InvalidInput('WRITER_ID'); } sha($w->revision); number($w->from); number($w->through); }
    foreach (items($c->ranges) as $r) { object($r, ['from', 'through', 'proof_ref']); number($r->from); number($r->through); if ($r->proof_ref !== null) { ref($r->proof_ref); } }
    foreach (items($c->dimensions, 7) as $d) { object($d, ['name', 'through', 'proof_ref']); choice($d->name, DIMENSIONS); number($d->through); if ($d->proof_ref !== null) { ref($d->proof_ref); } }
    foreach (items($c->exclusions) as $v) { choice($v, ['missing_writer', 'missing_interval', 'unknown_origin', 'unverified_source', 'unknown_global_window', 'unknown_current_state']); }
    foreach (items($b->events, MAX_FACTS) as $event) { event_schema($event); }
    foreach (items($b->observations, MAX_FACTS) as $o) {
        object($o, ['request_ref', 'position', 'status', 'source_ref']); sha($o->request_ref); number($o->position);
        choice($o->status, ['pending', 'approved', 'declined', 'cancelled', 'expired']); ref($o->source_ref);
    }
    foreach (items($b->equivalences) as $e) { object($e, ['left', 'right', 'proof_ref']); sha($e->left); sha($e->right); if ($e->proof_ref !== null) { ref($e->proof_ref); } }
    object($b->expected, ['fingerprint', 'pair_version']); sha($b->expected->fingerprint); number($b->expected->pair_version);
    return $b;
}

function state_schema(mixed $s): void
{
    object($s, ['pair_version', 'history_state', 'accounts', 'counts', 'ending_count', 'last_end', 'declines', 'cooldowns', 'match', 'chat', 'current_request', 'blocks', 'probations', 'recovery_version', 'schema_ref', 'epoch_ref']);
    number($s->pair_version); choice($s->history_state, ['legacy_incomplete', 'complete_from_genesis', 'provenance_unavailable']); counts($s->counts); number($s->ending_count); last_end($s->last_end);
    if (count(items($s->accounts, 2)) !== 2) { throw new InvalidInput('TWO_DESTINATION_ACCOUNTS_REQUIRED'); }
    foreach ($s->accounts as $a) {
        object($a, ['ref', 'created_at', 'session_version', 'status', 'consent_version', 'consent']); ref($a->ref); clock($a->created_at); number($a->session_version); choice($a->status, ['active', 'suspended', 'deleted', 'underage', 'unverified']); number($a->consent_version); flag($a->consent);
    }
    object($s->declines, ['low_to_high', 'high_to_low']); foreach (get_object_vars($s->declines) as $v) { flag($v); }
    object($s->cooldowns, ['low_to_high', 'high_to_low']); foreach (get_object_vars($s->cooldowns) as $v) { clock($v); }
    object($s->match, ['generation', 'matched', 'low_unmatched', 'high_unmatched', 'low_agreement', 'high_agreement', 'low_like', 'high_like', 'low_restore_at', 'high_restore_at', 'unmatched_at', 'last_restored_at']);
    number($s->match->generation); foreach (['matched', 'low_unmatched', 'high_unmatched', 'low_agreement', 'high_agreement', 'low_like', 'high_like'] as $k) { flag($s->match->{$k}); }
    foreach (['low_restore_at', 'high_restore_at', 'unmatched_at', 'last_restored_at'] as $k) { clock($s->match->{$k}); }
    object($s->chat, ['grant', 'suppressed', 'segments']); flag($s->chat->grant); flag($s->chat->suppressed);
    foreach (items($s->chat->segments) as $seg) { object($seg, ['ref', 'closed_at', 'access_expires_at']); ref($seg->ref); clock($seg->closed_at); clock($seg->access_expires_at); }
    if ($s->current_request !== null) { object($s->current_request, ['event_ref', 'status', 'expires_at']); sha($s->current_request->event_ref); choice($s->current_request->status, ['pending', 'approved', 'declined', 'cancelled', 'expired']); clock($s->current_request->expires_at); }
    foreach (items($s->blocks) as $block) {
        object($block, ['ref', 'creator', 'subject', 'links']); foreach (['ref', 'creator', 'subject'] as $k) { ref($block->{$k}); }
        foreach (items($block->links) as $l) { object($l, ['case_ref', 'submission_ref', 'scope_version', 'clearance_version', 'reviewer_ref']); ref($l->case_ref); ref($l->submission_ref); number($l->scope_version); if ($l->clearance_version !== null) { number($l->clearance_version); } if ($l->reviewer_ref !== null) { ref($l->reviewer_ref); } }
    }
    foreach (items($s->probations, 2) as $p) { object($p, ['actor', 'start', 'end']); ref($p->actor); clock($p->start); clock($p->end); }
    number($s->recovery_version); ref($s->schema_ref); if ($s->epoch_ref !== null) { ref($s->epoch_ref); }
}

function snapshot_schema(mixed $s): stdClass
{
    object($s, ['format', 'domain', 'destination', 'pair_ref', 'aliases', 'position', 'captured_at', 'expires_at', 'state', 'native_events']);
    choice($s->format, ['bushisa.legacy-snapshot.v1']); choice($s->domain, ['synthetic']);
    object($s->destination, ['mode', 'incarnation']);
    if ($s->destination->mode !== 'offline-synthetic') { throw new InvalidInput('OFFLINE_DESTINATION_ONLY', 'REJECT_DESTINATION'); }
    ref($s->destination->incarnation); ref($s->pair_ref); number($s->position); clock($s->captured_at); clock($s->expires_at);
    foreach (items($s->aliases) as $alias) { ref($alias); }
    state_schema($s->state); foreach (items($s->native_events, MAX_FACTS) as $e) { event_schema($e); }
    return $s;
}

/** Separate synthetic oracle/custody catalogue. It is explicitly NOT a production trust anchor. */
function catalog_schema(mixed $a): stdClass
{
    object($a, ['format', 'domain', 'collector_ref', 'authority_ref', 'custody_ref', 'artifact_ref', 'source_incarnation', 'destination_incarnation', 'tool_digest', 'bundle_digest', 'snapshot_digest', 'raw_digests', 'proofs', 'expires_at']);
    choice($a->format, ['bushisa.legacy-fixture-catalog.v1']); choice($a->domain, ['synthetic']);
    foreach (['collector_ref', 'authority_ref', 'custody_ref', 'artifact_ref', 'source_incarnation', 'destination_incarnation'] as $k) { ref($a->{$k}); }
    foreach (['tool_digest', 'bundle_digest', 'snapshot_digest'] as $k) { sha($a->{$k}); } clock($a->expires_at);
    object($a->raw_digests, ['bundle', 'snapshot']);
    foreach (['bundle', 'snapshot'] as $kind) { if (items($a->raw_digests->{$kind}, 16) === []) { throw new InvalidInput('MISSING_RAW_CUSTODY'); } foreach ($a->raw_digests->{$kind} as $value) { sha($value); } }
    foreach (items($a->proofs) as $proof) {
        object($proof, ['ref', 'kind', 'claim_digest', 'expires_at']); ref($proof->ref);
        choice($proof->kind, ['checkpoint', 'range', 'dimension', 'equivalence', 'writer_inventory', 'account_mapping']); sha($proof->claim_digest); clock($proof->expires_at);
    }
    return $a;
}

function base_result(string $at): array
{
    return ['format' => 'bushisa.legacy-plan.v1', 'evidence_domain' => 'synthetic', 'observed_at' => instant($at),
        'classification' => 'INCOMPLETE', 'outcome' => 'INCOMPLETE_NO_APPLY', 'reasons' => [], 'proposed_actions' => [],
        'actual_write_set' => [], 'applies' => false, 'grants_chat' => false, 'changes_sessions' => false, 'runtime_history_state' => 'legacy_incomplete',
        'production_authenticity' => 'NOT_VERIFIED', 'attests_production_cutover' => false,
        'unresolved_prerequisites' => ['production_source_authenticity', 'observed_all_writer_cutover', 'qualified_privacy_security_safety_review', 'retention_and_access_review', 'independent_recovery_proof', 'separate_synthetic_apply_implementation', 'separate_eligibility_adapter']];
}

/** Pure function. Inputs are already bounded JSON values. No connection, clock read or mutation of inputs. */
function evaluate(mixed $input, mixed $snapshot, mixed $catalog, string $at): array
{
    $result = base_result($at);
    try {
        $b = bundle_schema($input); $a = catalog_schema($catalog); $s = $snapshot === null ? null : snapshot_schema($snapshot);
        $gaps = []; $conflicts = [];
        $now = instant($at);
        $bd = bundle_digest($b);
        $proofs = [];
        foreach ($a->proofs as $proof) {
            $key = $proof->kind . ':' . $proof->ref;
            if (isset($proofs[$key]) && encode($proofs[$key]) !== encode($proof)) { $conflicts[] = 'PROOF_RECORD_CONFLICT'; }
            $proofs[$key] = $proof;
        }
        $proofCeilings = [];
        $checkProof = static function (string $kind, ?string $reference, mixed $claim) use ($proofs, $b, $now, &$gaps, &$conflicts, &$proofCeilings): void {
            $p = $reference === null ? null : ($proofs[$kind . ':' . $reference] ?? null);
            if ($p === null) { $gaps[] = 'MISSING_REFERENCED_PROOF'; return; }
            if ($p->claim_digest !== proof_digest($kind, $b, $claim)) { $conflicts[] = 'PROOF_SCOPE_CONFLICT'; }
            $until = clock($p->expires_at);
            if ($until === null) { $gaps[] = 'UNKNOWN_PROOF_EXPIRY'; }
            elseif ($now >= $until) { throw new InvalidInput('PROOF_EXPIRED'); }
            else { $proofCeilings[] = $until; }
        };
        $checkProof('account_mapping', $b->pair_ref, ['accounts' => $b->accounts, 'aliases' => set_values($b->aliases)]);
        $checkProof('writer_inventory', $b->source->ref, set_values($b->coverage->writers));
        if ($b->checkpoint !== null) { $checkProof('checkpoint', $b->checkpoint->proof_ref, $b->checkpoint); }
        foreach ($b->coverage->ranges as $r) { $checkProof('range', $r->proof_ref, $r); }
        foreach ($b->coverage->dimensions as $d) { $checkProof('dimension', $d->proof_ref, $d); }
        foreach ($b->equivalences as $e) { $checkProof('equivalence', $e->proof_ref, $e); }
        foreach (['collector_ref', 'authority_ref', 'custody_ref', 'artifact_ref'] as $k) {
            if ($b->provenance->{$k} === null) { $gaps[] = 'MISSING_PROVENANCE'; }
            elseif ($b->provenance->{$k} !== $a->{$k}) { $conflicts[] = 'CONTRADICTORY_CUSTODY'; }
        }
        if ($b->source->incarnation !== $a->source_incarnation || $b->destination_incarnation !== $a->destination_incarnation || $b->provenance->tool_digest !== $a->tool_digest) { $conflicts[] = 'CONTRADICTORY_SOURCE_MAPPING'; }
        if (!hash_equals($a->bundle_digest, $bd)) { throw new InvalidInput('CATALOG_BUNDLE_MISMATCH'); }
        $ceilings = $proofCeilings;
        foreach ([$b->provenance, $a] as $p) {
            $expiry = clock($p->expires_at);
            if ($expiry === null) { $gaps[] = 'UNKNOWN_SOURCE_EXPIRY'; }
            elseif ($now >= $expiry) { throw new InvalidInput('SOURCE_EXPIRED'); }
            else { $ceilings[] = $expiry; }
        }
        $captured = clock($b->provenance->captured_at);
        if ($captured === null) { $gaps[] = 'UNKNOWN_CAPTURE_TIME'; }
        elseif ($captured > $now || ($ceilings !== [] && $captured >= min($ceilings))) { $conflicts[] = 'CAPTURE_CLOCK_CONFLICT'; }
        $result['bundle_digest'] = $bd; $result['pair_ref'] = $b->pair_ref;
        $result['source_incarnation'] = $b->source->incarnation; $result['destination_incarnation'] = $b->destination_incarnation;
        $result['boundaries'] = ['B' => $b->coverage->B, 'W' => $b->coverage->W, 'H' => $b->coverage->H, 'C' => $b->coverage->C];
        $c = $b->coverage;
        if (in_array(null, [$c->B, $c->W, $c->H, $c->C], true)) { $gaps[] = 'UNKNOWN_BOUNDARY'; }
        elseif ($c->W > $c->B || $c->B > $c->H || $c->H > $c->C) { $conflicts[] = 'BOUNDARY_ORDER'; }
        if ($c->method === 'synthetic_quiesced' && count(array_unique([$c->B, $c->W, $c->H, $c->C], SORT_REGULAR)) !== 1) { $gaps[] = 'QUIESCENCE_NOT_ESTABLISHED'; }
        $writers = [];
        foreach ($c->writers as $w) {
            if (isset($writers[$w->id])) { $conflicts[] = 'DUPLICATE_WRITER'; }
            $writers[$w->id] = $w;
            if ($w->from > $w->through) { $conflicts[] = 'WRITER_RANGE_ORDER'; }
            if ($c->W === null || $c->C === null || $w->from > $c->W || $w->through < $c->C) { $gaps[] = 'WRITER_COVERAGE_GAP'; }
        }
        if (count($writers) !== 12) { $gaps[] = 'MISSING_WRITER'; }
        $ranges = $c->ranges; usort($ranges, fn($x, $y) => [$x->from, $x->through] <=> [$y->from, $y->through]);
        $cursor = $c->B;
        foreach ($ranges as $r) {
            if ($r->through < $r->from) { $conflicts[] = 'RANGE_ORDER'; }
            if (($c->B !== null && $r->from < $c->B) || ($c->C !== null && $r->through > $c->C)) { $conflicts[] = 'RANGE_OUTSIDE_SCOPE'; }
            if ($r->proof_ref === null || $cursor === null || $r->from > $cursor) { $gaps[] = 'SNAPSHOT_TAIL_GAP'; }
            if ($cursor !== null) { $cursor = max($cursor, $r->through); }
        }
        if ($cursor === null || $c->C === null || $cursor !== $c->C) { $gaps[] = 'SNAPSHOT_TAIL_GAP'; }
        $dimensions = [];
        foreach ($c->dimensions as $d) {
            if (isset($dimensions[$d->name])) { $conflicts[] = 'DUPLICATE_DIMENSION'; }
            $dimensions[$d->name] = $d;
            if ($d->proof_ref === null || $d->through !== $c->C) { $gaps[] = 'DIMENSION_GAP_' . strtoupper($d->name); }
        }
        foreach (DIMENSIONS as $d) { if (!isset($dimensions[$d])) { $gaps[] = 'DIMENSION_GAP_' . strtoupper($d); } }
        if ($c->exclusions !== []) { $gaps[] = 'DECLARED_EXCLUSION'; }
        $result['dimensions'] = array_map(static function ($name) use ($dimensions, $proofs, $b, $now, $c): array {
            $d = $dimensions[$name] ?? null;
            $p = $d === null || $d->proof_ref === null ? null : ($proofs['dimension:' . $d->proof_ref] ?? null);
            $claimed = $d !== null && $d->through === $c->C;
            $matched = $p !== null && $p->claim_digest === proof_digest('dimension', $b, $d) && clock($p->expires_at) !== null && clock($p->expires_at) > $now;
            return ['name' => $name, 'claimed_through_horizon' => $claimed, 'proof_record_matches' => $matched, 'covered' => $claimed && $matched];
        }, DIMENSIONS);
        $accounts = array_map(fn($v) => $v->source_ref, $b->accounts);
        foreach ($b->accounts as $account) {
            $origin = clock($account->created_at);
            if ($origin === null) { $gaps[] = 'UNKNOWN_ACCOUNT_ORIGIN'; }
            elseif ($origin > $now || ($captured !== null && $origin > $captured)) { $conflicts[] = 'ACCOUNT_ORIGIN_CLOCK'; }
        }
        $prefix = $b->checkpoint;
        $totals = ['low_to_high' => null, 'high_to_low' => null]; $endingCount = null; $lastEnd = null;
        if ($prefix === null || $prefix->proof_ref === null) { $gaps[] = 'NO_CERTIFIED_PREFIX'; }
        else {
            if ($prefix->position !== $c->B) { $conflicts[] = 'CHECKPOINT_BOUNDARY'; }
            $totals = get_object_vars($prefix->counts); $endingCount = $prefix->ending_count; $lastEnd = $prefix->last_end;
            if ($endingCount > 0 && ($lastEnd->actor === null || clock($lastEnd->time) === null)) { $gaps[] = 'UNKNOWN_LAST_END'; }
            if ($lastEnd->actor !== null && !in_array($lastEnd->actor, $accounts, true)) { $conflicts[] = 'FOREIGN_END_ACTOR'; }
            if ($endingCount === 0 && ($lastEnd->actor !== null || clock($lastEnd->time) !== null)) { $conflicts[] = 'ZERO_END_WITH_EVENT'; }
            if ($prefix->basis === 'account_origin' && ($totals['low_to_high'] !== 0 || $totals['high_to_low'] !== 0 || $endingCount !== 0)) { $conflicts[] = 'ORIGIN_PREFIX_NONZERO'; }
        }
        $stateDigest = null;
        if ($s !== null) {
            if (!hash_equals($a->snapshot_digest, digest($s, 'snapshot'))) { throw new InvalidInput('CATALOG_SNAPSHOT_MISMATCH'); }
            if ($s->destination->incarnation !== $b->destination_incarnation || $s->pair_ref !== $b->pair_ref) { throw new InvalidInput('DESTINATION_IDENTITY', 'REJECT_DESTINATION'); }
            $stateDigest = digest($s, 'state');
            $result['state_fingerprint'] = $stateDigest; $result['current_pair_version'] = $s->state->pair_version;
            $result['expected_pair_version'] = $b->expected->pair_version;
            $result['current_session_versions'] = array_map(fn($v) => ['ref' => $v->ref, 'version' => $v->session_version], $s->state->accounts);
            if ($s->position !== $c->C) { $gaps[] = 'DESTINATION_HORIZON_GAP'; }
            if ($s->state->history_state !== 'legacy_incomplete') { throw new InvalidInput('LEGACY_TARGET_ONLY', 'REJECT_DESTINATION'); }
            $mapped = array_map(fn($v) => $v->destination_ref, $b->accounts);
            $actual = array_map(fn($v) => $v->ref, $s->state->accounts);
            if ($mapped !== $actual || set_values($b->aliases) !== set_values($s->aliases)) { $conflicts[] = 'ACCOUNT_OR_ALIAS_MAPPING'; }
            foreach ($s->state->accounts as $i => $account) {
                if ($account->status === 'deleted') { throw new InvalidInput('DELETED_TARGET', 'REJECT_DESTINATION'); }
                if (clock($account->created_at) !== clock($b->accounts[$i]->created_at)) { $conflicts[] = 'ACCOUNT_INCARNATION'; }
            }
            foreach ($s->state->cooldowns as $deadline) { if ($deadline->precision === 'unknown') { $gaps[] = 'UNKNOWN_CURRENT_COOLDOWN'; } }
            if ($s->state->match->last_restored_at->precision === 'unknown') { $gaps[] = 'UNKNOWN_RESTORATION_CLOCK'; }
            foreach (['low', 'high'] as $side) {
                $restore = $s->state->match->{$side . '_restore_at'};
                if ($restore->precision === 'unknown') { $gaps[] = 'UNKNOWN_RESTORATION_CLOCK'; }
                elseif ($s->state->match->{$side . '_agreement'} !== (clock($restore) !== null)) { $conflicts[] = 'RESTORATION_AGREEMENT_CLOCK'; }
            }
            if (($s->state->match->low_unmatched || $s->state->match->high_unmatched) && clock($s->state->match->unmatched_at) === null) { $gaps[] = 'UNKNOWN_UNMATCH_CLOCK'; }
            foreach ($s->state->blocks as $block) {
                if (!in_array($block->creator, $mapped, true) || !in_array($block->subject, $mapped, true) || $block->creator === $block->subject) { $conflicts[] = 'BLOCK_ATTRIBUTION'; }
                foreach ($block->links as $link) {
                    if ($link->clearance_version !== null && $link->clearance_version > $link->scope_version) { $conflicts[] = 'FUTURE_CLEARANCE_SCOPE'; }
                    if ($link->clearance_version !== null && $link->reviewer_ref === null) { $gaps[] = 'MISSING_CLEARANCE_AUTHORITY'; }
                }
            }
            foreach ($s->state->probations as $p) {
                $start = clock($p->start); $end = clock($p->end);
                if (!in_array($p->actor, $mapped, true)) { $conflicts[] = 'PROBATION_ATTRIBUTION'; }
                if ($start === null || $end === null) { $gaps[] = 'UNKNOWN_PROBATION_CLOCK'; }
                elseif ((new DateTimeImmutable($start))->modify('+168 hours')->format('Y-m-d\TH:i:s.u\Z') !== $end) { $conflicts[] = 'PROBATION_DURATION'; }
            }
            $sx = clock($s->expires_at); $sc = clock($s->captured_at);
            if ($sx === null || $sc === null) { $gaps[] = 'UNKNOWN_SNAPSHOT_CLOCK'; }
            elseif ($now >= $sx || $sc > $now || $sx <= $sc) { throw new InvalidInput('SNAPSHOT_EXPIRED_OR_FUTURE'); }
            else { $ceilings[] = $sx; }
        } else { $gaps[] = 'NO_DESTINATION_SNAPSHOT'; }
        $events = $b->events;
        if ($s !== null) { $events = array_merge($events, $s->native_events); }
        $facts = []; $duplicates = 0; $aliases = [];
        foreach ($events as $event) {
            $id = fact_id($event);
            if (isset($facts[$id])) {
                if (encode($facts[$id]) !== encode($event)) { $conflicts[] = 'FACT_PAYLOAD_CONFLICT'; } else { $duplicates++; }
            } else { $facts[$id] = $event; }
            $aliases[$id] = $id;
        }
        foreach ($b->equivalences as $e) {
            if ($e->proof_ref === null || !isset($facts[$e->left], $facts[$e->right])) { $gaps[] = 'UNPROVED_EQUIVALENCE'; continue; }
            $left = clone $facts[$e->left]; $right = clone $facts[$e->right]; unset($left->identity, $right->identity);
            if (encode($left) !== encode($right)) { $conflicts[] = 'EQUIVALENCE_PAYLOAD_CONFLICT'; continue; }
            $root = min($aliases[$e->left], $aliases[$e->right]); $old = [$aliases[$e->left], $aliases[$e->right]];
            foreach ($aliases as $id => $alias) { if (in_array($alias, $old, true)) { $aliases[$id] = $root; } }
        }
        $unique = [];
        foreach ($facts as $id => $event) { $unique[$aliases[$id]] = $event; }
        uasort($unique, fn($x, $y) => [$x->position ?? MAX_COUNT, $x->identity->ordinal ?? MAX_COUNT, fact_id($x)] <=> [$y->position ?? MAX_COUNT, $y->identity->ordinal ?? MAX_COUNT, fact_id($y)]);
        $positions = []; $requests = []; $commits = []; $nativeIds = [];
        foreach ($s?->native_events ?? [] as $native) { $nativeIds[$aliases[fact_id($native)] ?? fact_id($native)] = true; }
        $observedPrefix = ['low_to_high' => 0, 'high_to_low' => 0]; $observedPrefixEndings = 0;
        foreach ($unique as $id => $e) {
            if ($e->identity->incarnation !== $b->source->incarnation || $e->identity->stream !== $b->source->stream || $e->source_ref !== $b->source->ref) { $conflicts[] = 'EVENT_SOURCE_MISMATCH'; }
            if ($e->position === null || $e->time->value === null || $e->actor === null || $e->generation === null) { $gaps[] = 'UNKNOWN_EVENT_ORDER_ACTOR_TIME'; }
            if ($e->actor !== null && !in_array($e->actor, $accounts, true)) { $conflicts[] = 'FOREIGN_EVENT_ACTOR'; }
            if ($e->position !== null && ($c->C === null || $e->position > $c->C)) { $gaps[] = 'EVENT_AFTER_HORIZON'; }
            if ($e->position !== null && $c->H !== null && $e->position > $c->H && !isset($nativeIds[$id])) { $gaps[] = 'MISSING_DESTINATION_TAIL_FACT'; }
            if ($e->position !== null) {
                $commits[$e->position][] = $e->identity->ordinal;
                $order = $e->position . ':' . ($e->identity->ordinal ?? 'unknown');
                if (isset($positions[$order]) && $positions[$order] !== $id) { $gaps[] = 'AMBIGUOUS_COMMIT_ORDER'; }
                $positions[$order] = $id;
            }
            $time = clock($e->time);
            $detailExpiry = clock($e->retain_until);
            if ($detailExpiry === null) { $gaps[] = 'UNKNOWN_DETAIL_EXPIRY'; }
            elseif ($now >= $detailExpiry) { throw new InvalidInput('EVENT_DETAIL_EXPIRED'); }
            else { $ceilings[] = $detailExpiry; }
            if ($e->request_ref !== null && !isset($facts[$e->request_ref])) { $gaps[] = 'MISSING_REFERENCED_REQUEST'; }
            if ($time !== null && ($time > $now || ($captured !== null && $time > $captured))) { $conflicts[] = 'EVENT_AFTER_CAPTURE'; }
            foreach ($b->accounts as $original) {
                if ($time !== null && clock($original->created_at) !== null && $time < clock($original->created_at)) { $conflicts[] = 'EVENT_BEFORE_ACCOUNT_ORIGIN'; }
            }
            if ($e->type === 'request_created') {
                if ($e->actor === null || !in_array($e->actor, $accounts, true)) {
                    $requests[$id] = $e;
                    $totals = ['low_to_high' => null, 'high_to_low' => null];
                    continue; // Unknown attribution never manufactures a direction or exact total.
                }
                $direction = $e->actor === $accounts[0] ? 'low_to_high' : 'high_to_low';
                if ($e->recipient !== ($e->actor === $accounts[0] ? $accounts[1] : $accounts[0])) { $conflicts[] = 'REQUEST_RECIPIENT'; }
                $expiry = $e->expires_at === null ? null : clock($e->expires_at);
                if ($expiry === null) { $gaps[] = 'UNKNOWN_ORIGINAL_EXPIRY'; }
                elseif ($time !== null && $expiry <= $time) { $conflicts[] = 'REQUEST_EXPIRY_ORDER'; }
                elseif ($time !== null && $expiry !== (new DateTimeImmutable($time))->modify('+168 hours')->format('Y-m-d\TH:i:s.u\Z')) { $conflicts[] = 'DECLARED_REQUEST_POLICY_CONFLICT'; }
                $requests[$id] = $e;
                if ($prefix !== null && $e->position !== null && $e->position <= $prefix->position) { $observedPrefix[$direction]++; }
                if ($prefix !== null && $e->position !== null && $e->position > $prefix->position && $totals[$direction] !== null) {
                    if ($totals[$direction] === MAX_COUNT) { throw new InvalidInput('COUNT_UNION_OVERFLOW'); }
                    $totals[$direction]++;
                }
            }
            if ($e->type === 'chat_ended' && $prefix !== null && $e->position !== null && $e->position <= $prefix->position) { $observedPrefixEndings++; }
            if ($e->type === 'chat_ended' && $prefix !== null && $e->position !== null && $e->position > $prefix->position) {
                if ($endingCount === MAX_COUNT) { throw new InvalidInput('COUNT_UNION_OVERFLOW'); }
                if ($endingCount !== null) { $endingCount++; }
                if ($lastEnd !== null && clock($lastEnd->time) !== null && $time !== null && $time < clock($lastEnd->time)) { $conflicts[] = 'ENDING_CLOCK_CONFLICT'; }
                $lastEnd = (object) ['actor' => $e->actor, 'time' => $e->time];
            }
        }
        if ($prefix !== null) {
            foreach ($observedPrefix as $d => $n) { if ($n > $prefix->counts->{$d}) { $conflicts[] = 'PREFIX_COUNT_CONTRADICTION'; } }
            if ($observedPrefixEndings > $prefix->ending_count) { $conflicts[] = 'PREFIX_ENDING_CONTRADICTION'; }
        }
        foreach ($commits as $ordinals) { if (count($ordinals) > 1 && in_array(null, $ordinals, true)) { $gaps[] = 'AMBIGUOUS_COMMIT_ORDER'; } }
        $decisions = [];
        foreach ($unique as $e) {
            if (in_array($e->type, ['mutual_matched', 'restored'], true)) { $gaps[] = 'UNSUPPORTED_MUTUAL_CONSENT_PROOF'; }
            if (in_array($e->type, ['block_cleared', 'report_accepted', 'probation_started'], true)) { $gaps[] = 'UNSUPPORTED_REVIEW_ACTION_PROOF'; }
            if (!in_array($e->type, ['request_approved', 'request_declined', 'request_cancelled', 'request_expired'], true)) { continue; }
            $target = $aliases[$e->request_ref ?? ''] ?? $e->request_ref;
            if ($target === null || !isset($requests[$target])) { $gaps[] = 'MISSING_REFERENCED_REQUEST'; continue; }
            $request = $requests[$target];
            if (isset($decisions[$target])) { $conflicts[] = 'MULTIPLE_REQUEST_DECISIONS'; } else { $decisions[$target] = $e; }
            if ($request->position === null || $e->position === null || ($e->position === $request->position && ($e->identity->ordinal === null || $request->identity->ordinal === null))) { $gaps[] = 'TRANSITION_CAUSAL_ORDER'; }
            elseif ($e->position < $request->position || ($e->position === $request->position && $e->identity->ordinal <= $request->identity->ordinal)) { $conflicts[] = 'TRANSITION_CAUSAL_CONFLICT'; }
            $requiredActor = in_array($e->type, ['request_approved', 'request_declined'], true) ? $request->recipient : $request->actor;
            if ($e->actor !== null && $e->actor !== $requiredActor) { $conflicts[] = 'TRANSITION_ACTOR'; }
            if (clock($e->time) !== null && clock($request->time) !== null && clock($e->time) < clock($request->time)) { $conflicts[] = 'TRANSITION_CLOCK_ORDER'; }
            if (in_array($e->type, ['request_cancelled', 'request_declined', 'request_expired'], true) && clock($e->time) !== null && clock($e->retain_until) !== null) {
                $terminal = $e->type === 'request_expired' ? ($request->expires_at === null ? null : clock($request->expires_at)) : clock($e->time);
                if ($terminal === null) { $gaps[] = 'UNKNOWN_TERMINAL_TRIGGER'; }
                elseif (clock($e->retain_until) > (new DateTimeImmutable($terminal))->modify('+30 days')->format('Y-m-d\TH:i:s.u\Z')) { $conflicts[] = 'TERMINAL_RETENTION_EXTENSION'; }
            }
        }
        foreach ($b->observations as $o) {
            $id = $aliases[$o->request_ref] ?? $o->request_ref;
            if (!isset($requests[$id]) || $o->source_ref !== $b->source->ref || $o->position > ($c->C ?? -1)) { $gaps[] = 'UNPROVED_OBSERVATION'; continue; }
            if ($requests[$id]->position === null || $o->position < $requests[$id]->position) { $conflicts[] = 'OBSERVATION_BEFORE_CREATION'; }
            if ($o->status === 'pending') {
                if (isset($decisions[$id]) && $decisions[$id]->position !== null && $decisions[$id]->position < $o->position) { $conflicts[] = 'PENDING_AFTER_DECISION'; }
                continue;
            }
            $needed = 'request_' . $o->status;
            $matches = array_filter($unique, fn($e) => $e->type === $needed && ($aliases[$e->request_ref ?? ''] ?? $e->request_ref) === $id && $e->position !== null && $e->position <= $o->position && clock($e->time) !== null);
            if ($matches === []) { $gaps[] = 'MISSING_TRANSITION_ACTION'; }
        }
        $lower = ['low_to_high' => 0, 'high_to_low' => 0];
        foreach ($requests as $r) {
            if ($r->actor !== null && in_array($r->actor, $accounts, true)) { $lower[$r->actor === $accounts[0] ? 'low_to_high' : 'high_to_low']++; }
        }
        foreach ($lower as $d => $n) {
            $lower[$d] = max($n, $s?->state->counts->{$d} ?? 0, $prefix?->counts->{$d} ?? 0);
        }
        if ($s !== null) {
            if ($s->state->ending_count > 0 && ($s->state->last_end->actor === null || clock($s->state->last_end->time) === null)) { $gaps[] = 'UNKNOWN_CURRENT_END'; }
            if ($s->state->current_request !== null) {
                $target = $aliases[$s->state->current_request->event_ref] ?? $s->state->current_request->event_ref;
                if (!isset($requests[$target])) { $gaps[] = 'UNPROVED_CURRENT_REQUEST'; }
                else {
                    $original = $requests[$target];
                    if ($original->expires_at === null || clock($s->state->current_request->expires_at) !== clock($original->expires_at)) { $conflicts[] = 'CHANGED_ORIGINAL_EXPIRY'; }
                    $status = isset($decisions[$target]) ? substr($decisions[$target]->type, 8) : 'pending';
                    if ($s->state->current_request->status !== $status) { $gaps[] = 'UNPROVED_CURRENT_REQUEST_STATUS'; }
                }
                if ($s->state->current_request->status === 'pending' && clock($s->state->current_request->expires_at) === null) { $gaps[] = 'UNKNOWN_PENDING_EXPIRY'; }
            }
            foreach ($s->state->chat->segments as $segment) {
                $closed = clock($segment->closed_at); $end = clock($segment->access_expires_at);
                if ($segment->closed_at->precision === 'unknown' || $segment->access_expires_at->precision === 'unknown') { $gaps[] = 'UNKNOWN_SEGMENT_CLOCK'; }
                if ($closed !== null && ($end === null || (new DateTimeImmutable($closed))->modify('+7 days')->format('Y-m-d\TH:i:s.u\Z') !== $end)) { $conflicts[] = 'CLOSED_HISTORY_DEADLINE'; }
            }
            foreach (['low_to_high', 'high_to_low'] as $d) {
                if ($totals[$d] !== null && $s->state->counts->{$d} > $totals[$d]) { $conflicts[] = 'UNEXPLAINED_CURRENT_COUNT'; }
                if ($totals[$d] === 0 && $s->state->declines->{$d}) { $conflicts[] = 'DECLINE_WITH_ZERO_SENDS'; }
                $deadline = clock($s->state->cooldowns->{$d});
                if ($deadline !== null) {
                    $anchors = [];
                    foreach ($decisions as $id => $decision) {
                        if (!in_array($decision->type, ['request_cancelled', 'request_expired'], true) || !isset($requests[$id])) { continue; }
                        $direction = $requests[$id]->actor === $accounts[0] ? 'low_to_high' : 'high_to_low';
                        $trigger = $decision->type === 'request_expired' ? ($requests[$id]->expires_at === null ? null : clock($requests[$id]->expires_at)) : clock($decision->time);
                        if ($direction === $d && $trigger !== null) { $anchors[] = (new DateTimeImmutable($trigger))->modify('+12 hours')->format('Y-m-d\TH:i:s.u\Z'); }
                    }
                    if (!in_array($deadline, $anchors, true)) { $gaps[] = 'UNPROVED_COOLDOWN_ANCHOR'; }
                }
            }
            if (clock($s->state->match->last_restored_at) !== null) { $gaps[] = 'UNSUPPORTED_RESTORATION_HISTORY_PROOF'; }
            if ($endingCount !== null && $s->state->ending_count > $endingCount) { $conflicts[] = 'UNEXPLAINED_CURRENT_ENDINGS'; }
            if ($endingCount !== null && $s->state->ending_count === $endingCount && $endingCount > 0 && $lastEnd !== null && $lastEnd->actor !== null && clock($lastEnd->time) !== null && $s->state->last_end->actor !== null && clock($s->state->last_end->time) !== null
                && ($s->state->last_end->actor !== $lastEnd->actor || clock($s->state->last_end->time) !== clock($lastEnd->time))) { $conflicts[] = 'CURRENT_ENDING_CONTRADICTION'; }
            if ($lastEnd !== null && $s->state->last_end->actor !== null && clock($s->state->last_end->time) !== null && clock($lastEnd->time) !== null && clock($s->state->last_end->time) > clock($lastEnd->time)) { $conflicts[] = 'UNEXPLAINED_NEWER_ENDING'; }
        }
        $result['request_union'] = $totals; $result['ending_count'] = $endingCount;
        $result['known_lower_bounds'] = $lower;
        $result['last_end'] = $lastEnd; $result['unique_fact_count'] = count($unique); $result['duplicate_fact_count'] = $duplicates + count($facts) - count($unique);
        $result['fact_ids'] = array_keys($unique); sort($result['fact_ids'], SORT_STRING);
        $result['retention_ceiling'] = $ceilings === [] ? null : min($ceilings);
        $result['exclusions'] = set_values($c->exclusions);
        $result['preserved_state_fingerprint'] = $stateDigest;
        $result['reasons'] = array_values(array_unique(array_merge($conflicts, $gaps))); sort($result['reasons'], SORT_STRING);
        if ($conflicts !== []) { $result['classification'] = 'CONFLICTING'; $result['outcome'] = 'CONFLICT_NO_APPLY'; }
        elseif ($s !== null && ($b->expected->fingerprint !== $stateDigest || $b->expected->pair_version !== $s->state->pair_version)) {
            $result['outcome'] = 'REPLAN_REQUIRED'; $result['reasons'][] = 'STALE_FULL_STATE'; sort($result['reasons'], SORT_STRING);
        } elseif ($gaps === []) {
            $result['classification'] = 'COMPLETE'; $result['outcome'] = 'PLAN_NON_ENABLING';
            $result['proposed_actions'] = [
                ['kind' => 'stage_private_fact_references', 'count' => count($unique)],
                ['kind' => 'monotone_request_floors', 'counts' => $totals],
                ['kind' => 'monotone_ending_floor', 'count' => $endingCount],
                ['kind' => 'preserve_all_other_state', 'fingerprint' => $stateDigest],
            ];
        }
        return $result;
    } catch (InvalidInput $e) {
        $result['classification'] = 'INVALID'; $result['outcome'] = $e->outcome; $result['reasons'] = [$e->reason]; return $result;
    }
}
