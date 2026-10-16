<?php
declare(strict_types=1);

// Synthetic-only builder. Never imported by the planner. Setup and planning are separate.
namespace Bushisa\LegacyHistory\Fixture;

require_once __DIR__ . '/../../php/legacy_history.php';
use stdClass;
use function Bushisa\LegacyHistory\{bundle_digest, decode, digest, encode, fact_id, proof_digest, set_values};

function obj(array $v): stdClass { return (object) $v; }
function time(?string $value): stdClass { return obj(['value' => $value, 'precision' => $value === null ? 'unknown' : (str_contains($value, '.') ? 'microsecond' : 'second')]); }
function absent(): stdClass { return obj(['value' => null, 'precision' => 'absent']); }
function event(string $key = 'syn:request121', int $position = 121, string $type = 'request_created', string $actor = 'syn:A1'): stdClass
{
    return obj(['identity' => obj(['incarnation' => 'syn:S', 'stream' => 'syn:commits', 'native_key' => $key, 'ordinal' => 0]),
        'type' => $type, 'actor' => $actor, 'recipient' => $type === 'request_created' ? 'syn:B1' : null, 'position' => $position,
        'time' => time('2026-10-15T10:00:00Z'), 'expires_at' => $type === 'request_created' ? time('2026-10-22T10:00:00Z') : null, 'retain_until' => time('2026-10-18T10:00:00Z'),
        'request_ref' => null, 'generation' => 1, 'source_ref' => 'syn:source']);
}
function fixture(): array
{
    $unknown = absent();
    $accounts = [obj(['source_ref' => 'syn:A1', 'destination_ref' => 'syn:DA1', 'created_at' => time('2026-01-01T00:00:00Z')]),
        obj(['source_ref' => 'syn:B1', 'destination_ref' => 'syn:DB1', 'created_at' => time('2026-02-01T00:00:00Z')])];
    $last = obj(['actor' => 'syn:A1', 'time' => time('2026-10-01T10:00:00Z')]);
    $e = event();
    $state = obj(['pair_version' => 4, 'history_state' => 'legacy_incomplete', 'accounts' => array_map(fn($a) => obj(['ref' => $a->destination_ref, 'created_at' => $a->created_at,
        'session_version' => 3, 'status' => 'active', 'consent_version' => 2, 'consent' => true]), $accounts),
        'counts' => obj(['low_to_high' => 3, 'high_to_low' => 0]), 'ending_count' => 1, 'last_end' => $last,
        'declines' => obj(['low_to_high' => false, 'high_to_low' => false]),
        'cooldowns' => obj(['low_to_high' => $unknown, 'high_to_low' => $unknown]),
        'match' => obj(['generation' => 1, 'matched' => false, 'low_unmatched' => true, 'high_unmatched' => false, 'low_agreement' => false, 'high_agreement' => false,
            'low_like' => false, 'high_like' => false, 'low_restore_at' => $unknown, 'high_restore_at' => $unknown, 'unmatched_at' => time('2026-10-16T09:00:00Z'), 'last_restored_at' => $unknown]),
        'chat' => obj(['grant' => false, 'suppressed' => true, 'segments' => [obj(['ref' => 'syn:closed1', 'closed_at' => time('2026-10-01T10:00:00Z'), 'access_expires_at' => time('2026-10-08T10:00:00Z')])]]),
        'current_request' => obj(['event_ref' => fact_id($e), 'status' => 'pending', 'expires_at' => $e->expires_at]),
        'blocks' => [obj(['ref' => 'syn:block1', 'creator' => 'syn:DA1', 'subject' => 'syn:DB1', 'links' => [obj(['case_ref' => 'syn:case1', 'submission_ref' => 'syn:submission1', 'scope_version' => 2, 'clearance_version' => null, 'reviewer_ref' => 'syn:reviewer1'])]])],
        'probations' => [obj(['actor' => 'syn:DA1', 'start' => time('2026-10-14T10:00:00Z'), 'end' => time('2026-10-21T10:00:00Z')])],
        'recovery_version' => 6, 'schema_ref' => 'syn:schema007', 'epoch_ref' => null]);
    $s = obj(['format' => 'bushisa.legacy-snapshot.v1', 'domain' => 'synthetic', 'destination' => obj(['mode' => 'offline-synthetic', 'incarnation' => 'syn:D']),
        'pair_ref' => 'syn:P', 'aliases' => ['syn:M1'], 'position' => 122, 'captured_at' => time('2026-10-16T10:00:00Z'), 'expires_at' => time('2026-10-18T10:00:00Z'), 'state' => $state, 'native_events' => [$e]]);
    $writers = [];
    for ($n = 1; $n <= 12; $n++) { $writers[] = obj(['id' => 'W' . str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'revision' => str_repeat('a', 64), 'from' => 100, 'through' => 122]); }
    $b = obj(['format' => 'bushisa.legacy-history.v1', 'domain' => 'synthetic', 'policy_semantics' => 'decisions-1-33-v1',
        'source' => obj(['ref' => 'syn:source', 'incarnation' => 'syn:S', 'stream' => 'syn:commits', 'schema_ref' => 'syn:schema007']),
        'destination_incarnation' => 'syn:D', 'pair_ref' => 'syn:P', 'accounts' => $accounts, 'aliases' => ['syn:M1'],
        'provenance' => obj(['collector_ref' => 'syn:collector', 'authority_ref' => 'syn:authority', 'custody_ref' => 'syn:custody', 'artifact_ref' => 'syn:artifact', 'tool_digest' => str_repeat('b', 64), 'captured_at' => time('2026-10-16T10:00:00Z'), 'expires_at' => time('2026-10-18T10:00:00Z')]),
        'checkpoint' => obj(['ref' => 'syn:K', 'position' => 100, 'basis' => 'certified_prefix', 'proof_ref' => 'syn:prefix-proof', 'counts' => obj(['low_to_high' => 2, 'high_to_low' => 0]), 'ending_count' => 1, 'last_end' => $last]),
        'coverage' => obj(['B' => 100, 'W' => 100, 'H' => 120, 'C' => 122, 'method' => 'synthetic_stream', 'writers' => $writers,
            'ranges' => [obj(['from' => 100, 'through' => 120, 'proof_ref' => 'syn:tail1']), obj(['from' => 120, 'through' => 122, 'proof_ref' => 'syn:tail2'])],
            'dimensions' => array_map(fn($name) => obj(['name' => $name, 'through' => 122, 'proof_ref' => 'syn:proof-' . $name]), \Bushisa\LegacyHistory\DIMENSIONS), 'exclusions' => []]),
        'events' => [$e], 'observations' => [obj(['request_ref' => fact_id($e), 'position' => 121, 'status' => 'pending', 'source_ref' => 'syn:source'])], 'equivalences' => [],
        'expected' => obj(['fingerprint' => digest($s, 'state'), 'pair_version' => 4])]);
    // Independent artifacts: no shared PHP object references between source and destination.
    $b = decode(encode($b)); $s = decode(encode($s));
    return [$b, $s, catalog($b, $s)];
}
function catalog(stdClass $b, stdClass $s): stdClass
{
    // A test oracle, deliberately labelled synthetic, not an authenticity signer.
    $proofs = [];
    $add = static function (string $kind, ?string $ref, mixed $claim) use (&$proofs, $b): void {
        if ($ref !== null) { $proofs[] = obj(['ref' => $ref, 'kind' => $kind, 'claim_digest' => proof_digest($kind, $b, $claim), 'expires_at' => time('2026-10-18T10:00:00Z')]); }
    };
    $add('account_mapping', $b->pair_ref, ['accounts' => $b->accounts, 'aliases' => set_values($b->aliases)]);
    $add('writer_inventory', $b->source->ref, set_values($b->coverage->writers));
    if ($b->checkpoint !== null) { $add('checkpoint', $b->checkpoint->proof_ref, $b->checkpoint); }
    foreach ($b->coverage->ranges as $r) { $add('range', $r->proof_ref, $r); }
    foreach ($b->coverage->dimensions as $d) { $add('dimension', $d->proof_ref, $d); }
    foreach ($b->equivalences as $e) { $add('equivalence', $e->proof_ref, $e); }
    return obj(['format' => 'bushisa.legacy-fixture-catalog.v1', 'domain' => 'synthetic', 'collector_ref' => 'syn:collector', 'authority_ref' => 'syn:authority',
        'custody_ref' => 'syn:custody', 'artifact_ref' => 'syn:artifact', 'source_incarnation' => 'syn:S', 'destination_incarnation' => 'syn:D',
        'tool_digest' => str_repeat('b', 64), 'bundle_digest' => bundle_digest($b), 'snapshot_digest' => digest($s, 'snapshot'),
        'raw_digests' => obj(['bundle' => [hash('sha256', encode($b))], 'snapshot' => [hash('sha256', encode($s))]]),
        'proofs' => $proofs,
        'expires_at' => time('2026-10-18T10:00:00Z')]);
}
