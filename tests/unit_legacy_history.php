<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/support/legacy_history_fixture.php';

use Bushisa\LegacyHistory\InvalidInput;
use function Bushisa\LegacyHistory\{bundle_digest, decode, digest, encode, evaluate, fact_id};
use function Bushisa\LegacyHistory\Fixture\{absent, catalog, event, fixture, obj, time};

$started = gmdate('c'); $checks = []; $at = '2026-10-16T12:00:00Z';
function check_lc(string $id, string $variant, bool $ok): void
{
    global $checks;
    $checks[] = ['case' => $id, 'variant' => $variant, 'result' => $ok ? 'PASS' : 'FAIL'];
    if (!$ok) { throw new RuntimeException($id . ': ' . $variant); }
}
function plan_lc(callable $mutate, bool $freshExpectation = false): array
{
    global $at;
    [$b, $s] = fixture(); $mutate($b, $s);
    if ($freshExpectation) { $b->expected->fingerprint = digest($s, 'state'); $b->expected->pair_version = $s->state->pair_version; }
    $before = encode([$b, $s]);
    $result = evaluate($b, $s, catalog($b, $s), $at);
    if ($before !== encode([$b, $s])) { throw new RuntimeException('Planner mutated its inputs'); }
    return $result;
}
function reason_lc(array $r, string $outcome, ?string $reason = null): bool
{
    return $r['outcome'] === $outcome && ($reason === null || in_array($reason, $r['reasons'], true)) && $r['actual_write_set'] === [] && $r['applies'] === false;
}
function rejected_json_lc(string $json): bool
{
    try { decode($json); return false; } catch (InvalidInput) { return true; }
}
function horizon_lc(stdClass $b, stdClass $s, int $c): void
{
    $b->coverage->C = $c; $s->position = $c;
    foreach ($b->coverage->writers as $w) { $w->through = $c; }
    foreach ($b->coverage->dimensions as $d) { $d->through = $c; }
    $b->coverage->ranges[1]->through = $c;
}

[$b, $s, $a] = fixture();
$normal = evaluate($b, $s, $a, $at);
check_lc('LB01', 'F: exact prefix plus overlapping native tail totals three', reason_lc($normal, 'PLAN_NON_ENABLING') && $normal['request_union'] == ['low_to_high' => 3, 'high_to_low' => 0] && $normal['ending_count'] === 1);
check_lc('LB01', 'No grant/session/runtime promotion and no production authenticity claim', !$normal['grants_chat'] && !$normal['changes_sessions'] && $normal['runtime_history_state'] === 'legacy_incomplete' && $normal['production_authenticity'] === 'NOT_VERIFIED' && !$normal['attests_production_cutover']);
check_lc('LB01', 'Deterministic repeated pure run', encode($normal) === encode(evaluate($b, $s, $a, $at)));
$zero = plan_lc(function ($b, $s) {
    $b->checkpoint->basis = 'account_origin'; $b->checkpoint->counts->low_to_high = 0; $b->checkpoint->ending_count = 0; $b->checkpoint->last_end = obj(['actor' => null, 'time' => absent()]);
    $b->events = []; $b->observations = []; $s->native_events = []; $s->state->counts->low_to_high = 0; $s->state->ending_count = 0; $s->state->last_end = $b->checkpoint->last_end; $s->state->current_request = null; $s->state->chat->segments = [];
}, true);
check_lc('LB02', 'Positive certified account-origin coverage may prove zero', reason_lc($zero, 'PLAN_NON_ENABLING') && $zero['request_union']['low_to_high'] === 0);
$missing = plan_lc(function ($b, $s) { $b->checkpoint = null; $b->events = []; $b->observations = []; $s->native_events = []; $s->state->counts->low_to_high = 0; }, true);
check_lc('LB02', 'Empty surviving rows without prefix never prove zero', reason_lc($missing, 'INCOMPLETE_NO_APPLY', 'NO_CERTIFIED_PREFIX') && $missing['request_union']['low_to_high'] === null);
$u = plan_lc(function ($b, $s) { $b->checkpoint = null; $s->state->counts->low_to_high = 1; }, true);
check_lc('LB03', 'Incomplete history retains positive lower bound with unknown exact total', reason_lc($u, 'INCOMPLETE_NO_APPLY') && $u['known_lower_bounds']['low_to_high'] === 1 && $u['request_union']['low_to_high'] === null);
$marker = plan_lc(fn($b, $s) => $s->state->history_verified_at = '2026-10-01T00:00:00Z');
check_lc('LB03', 'Unrecognized verified-marker field cannot certify history', reason_lc($marker, 'REJECT_INPUT', 'SCHEMA_FIELDS'));
$mapping = plan_lc(fn($b, $s) => $b->accounts[1]->destination_ref = $b->accounts[0]->destination_ref);
check_lc('LB04', 'Ambiguous original-account mapping rejected', reason_lc($mapping, 'REJECT_INPUT', 'CANONICAL_ACCOUNT_MAPPING'));
$mapping = plan_lc(fn($b, $s) => $b->aliases = ['syn:unrelatedMatch']);
check_lc('LB04', 'Alias mismatch is conflicting, never a new match', reason_lc($mapping, 'CONFLICT_NO_APPLY', 'ACCOUNT_OR_ALIAS_MAPPING'));
$mapping = plan_lc(fn($b, $s) => $b->source->incarnation = 'syn:reusedDatabase');
check_lc('LB04', 'Reused source incarnation conflicts with independent catalogue', reason_lc($mapping, 'CONFLICT_NO_APPLY', 'CONTRADICTORY_SOURCE_MAPPING'));
$conflict = plan_lc(function ($b, $s) { $copy = clone $b->events[0]; $copy->actor = 'syn:B1'; $copy->recipient = 'syn:A1'; $b->events[] = $copy; });
check_lc('LB05', 'One immutable identity with different actor conflicts', reason_lc($conflict, 'CONFLICT_NO_APPLY', 'FACT_PAYLOAD_CONFLICT'));
$order = plan_lc(function ($b, $s) {
    $x = event('syn:approve', 122, 'request_approved', 'syn:B1'); $x->request_ref = fact_id($b->events[0]); $x->identity->ordinal = null;
    $y = event('syn:end', 122, 'chat_ended'); $y->identity->ordinal = null; $b->events = array_merge($b->events, [$x, $y]);
});
check_lc('LB06', 'Equal timestamp and missing within-commit order stays incomplete', reason_lc($order, 'INCOMPLETE_NO_APPLY', 'AMBIGUOUS_COMMIT_ORDER'));
check_lc('LB07', 'Unknown ender is not replaced with importer identity', reason_lc(plan_lc(fn($b, $s) => $b->checkpoint->last_end->actor = null), 'INCOMPLETE_NO_APPLY', 'UNKNOWN_LAST_END'));
check_lc('LB07', 'Unknown ending time remains unknown', reason_lc(plan_lc(fn($b, $s) => $b->checkpoint->last_end->time = time(null)), 'INCOMPLETE_NO_APPLY', 'UNKNOWN_LAST_END'));
check_lc('LB07', 'Unknown current cooldown blocks completeness', reason_lc(plan_lc(fn($b, $s) => $s->state->cooldowns->low_to_high = time(null), true), 'INCOMPLETE_NO_APPLY', 'UNKNOWN_CURRENT_COOLDOWN'));
$repeat = plan_lc(function ($b, $s) { $b->events[] = $b->events[0]; });
check_lc('LB08', 'Duplicate source copy does not alter canonical bundle or projected count', $repeat['bundle_digest'] === $normal['bundle_digest'] && $repeat['request_union'] === $normal['request_union'] && $repeat['unique_fact_count'] === 1);
$reordered = decode(json_encode(array_reverse(get_object_vars($b), true), JSON_THROW_ON_ERROR));
check_lc('LB08', 'JSON property ordering preserves bundle digest and full output', bundle_digest($reordered) === bundle_digest($b) && encode(evaluate($reordered, $s, $a, $at)) === encode($normal));
check_lc('LB09', 'Source/native same identity counts once', $normal['unique_fact_count'] === 1 && $normal['duplicate_fact_count'] === 1);
$equivalent = plan_lc(function ($b, $s) {
    $e = clone $s->native_events[0]; $e->identity = clone $e->identity; $e->identity->native_key = 'syn:nativeAlias'; $s->native_events = [$e];
    $b->equivalences = [obj(['left' => fact_id($b->events[0]), 'right' => fact_id($e), 'proof_ref' => 'syn:equivalence-proof'])];
}, true);
check_lc('LB09', 'Explicit evidence-backed alias equivalence deduplicates', reason_lc($equivalent, 'PLAN_NON_ENABLING') && $equivalent['request_union']['low_to_high'] === 3);
$unproved = plan_lc(function ($b, $s) { $b->equivalences = [obj(['left' => fact_id($b->events[0]), 'right' => str_repeat('0', 64), 'proof_ref' => null])]; });
check_lc('LB09', 'Unproved overlap cannot enable a plan', reason_lc($unproved, 'INCOMPLETE_NO_APPLY', 'UNPROVED_EQUIVALENCE'));
check_lc('LB10', 'Unexplained current higher count conflicts without reduction', reason_lc(plan_lc(fn($b, $s) => $s->state->counts->low_to_high = 4, true), 'CONFLICT_NO_APPLY', 'UNEXPLAINED_CURRENT_COUNT'));
$five = plan_lc(function ($b, $s) { horizon_lc($b, $s, 124); $b->events[] = event('syn:request122', 122); $b->events[] = event('syn:request123', 123); $s->native_events = $b->events; $s->state->counts->low_to_high = 5; }, true);
check_lc('LB10', 'Proved union above lifetime budget stays five, not clamped or reset', reason_lc($five, 'PLAN_NON_ENABLING') && $five['request_union']['low_to_high'] === 5);
$stale = plan_lc(fn($b, $s) => $s->state->pair_version++);
check_lc('LB13', 'Changed pair version requires replan', reason_lc($stale, 'REPLAN_REQUIRED', 'STALE_FULL_STATE'));
$stale = plan_lc(fn($b, $s) => $s->state->blocks[0]->links[0]->scope_version++);
check_lc('LB14', 'Changed report attachment scope with same pair version requires replan', reason_lc($stale, 'REPLAN_REQUIRED', 'STALE_FULL_STATE') && $stale['current_pair_version'] === 4);
$stale = plan_lc(fn($b, $s) => $s->native_events = []);
check_lc('LB14', 'Native cleanup changes full fingerprint even without pair-version bump', reason_lc($stale, 'REPLAN_REQUIRED', 'STALE_FULL_STATE'));
$stale = plan_lc(fn($b, $s) => $s->state->match->low_like = true);
check_lc('LB14', 'Changed active like with same pair version requires replan', reason_lc($stale, 'REPLAN_REQUIRED', 'STALE_FULL_STATE'));
$stale = plan_lc(fn($b, $s) => $s->state->accounts[0]->session_version++);
check_lc('LB15', 'Session increment requires replan and never refreshes sessions', reason_lc($stale, 'REPLAN_REQUIRED') && $stale['changes_sessions'] === false);
$deleted = plan_lc(fn($b, $s) => $s->state->accounts[0]->status = 'deleted', true);
check_lc('LB15', 'Deleted original account rejects destination', reason_lc($deleted, 'REJECT_DESTINATION', 'DELETED_TARGET'));
foreach (['ban' => fn($b, $s) => $s->state->accounts[0]->status = 'suspended', 'withdrawal' => fn($b, $s) => $s->state->accounts[0]->consent = false] as $label => $mutator) {
    $preserve = plan_lc($mutator, true);
    check_lc('LB16', 'Preserve independent ' . $label . ' without access decisions', reason_lc($preserve, 'PLAN_NON_ENABLING') && !$preserve['grants_chat'] && $preserve['actual_write_set'] === []);
}
$unsafe = plan_lc(fn($b, $s) => $b->grant_chat = true);
check_lc('LB16', 'Supplied grant authority rejected', reason_lc($unsafe, 'REJECT_INPUT', 'SCHEMA_FIELDS'));
$two = plan_lc(function ($b, $s) { $b->checkpoint->ending_count = 2; $s->state->ending_count = 2; }, true);
check_lc('LB16', 'Two-ending cutoff cannot become access through planning', reason_lc($two, 'PLAN_NON_ENABLING') && $two['ending_count'] === 2 && !$two['grants_chat']);
$probation = plan_lc(fn($b, $s) => $s->state->probations[0]->end = time('2026-10-22T10:00:00Z'), true);
check_lc('LB17', 'Invalid extended probation conflicts, never adjusted by planner', reason_lc($probation, 'CONFLICT_NO_APPLY', 'PROBATION_DURATION'));
$reportOnly = plan_lc(function ($b, $s) { $e = event('syn:report122', 122, 'report_accepted', 'syn:B1'); $b->events[] = $e; $b->events[] = $e; $s->native_events[] = $e; }, true);
check_lc('LB17', 'Duplicate historical report cannot activate, extend or shorten a period', reason_lc($reportOnly, 'INCOMPLETE_NO_APPLY', 'UNSUPPORTED_REVIEW_ACTION_PROOF') && $reportOnly['actual_write_set'] === [] && !$reportOnly['grants_chat']);
$gap = plan_lc(fn($b, $s) => $b->coverage->dimensions = array_slice($b->coverage->dimensions, 0, 6));
check_lc('LB18', 'Pair-only proof cannot certify global windows/probation', reason_lc($gap, 'INCOMPLETE_NO_APPLY', 'DIMENSION_GAP_GLOBAL_WINDOWS_PROBATION'));
$gap = plan_lc(fn($b, $s) => $b->coverage->writers = array_slice($b->coverage->writers, 0, 11));
check_lc('LB19', 'Omitted cleanup/direct writer is incomplete', reason_lc($gap, 'INCOMPLETE_NO_APPLY', 'MISSING_WRITER'));
$gap = plan_lc(fn($b, $s) => $b->coverage->ranges[1]->from = 121);
check_lc('LB19', 'Missing snapshot/catch-up commit interval is incomplete', reason_lc($gap, 'INCOMPLETE_NO_APPLY', 'SNAPSHOT_TAIL_GAP'));
$gap = plan_lc(fn($b, $s) => $b->coverage->W = 101);
check_lc('LB19', 'Claimed all-writer epoch after snapshot cannot cover the gap', reason_lc($gap, 'CONFLICT_NO_APPLY', 'BOUNDARY_ORDER'));
check_lc('LB20', 'Synthetic committed suffix includes post-B native event exactly once', reason_lc($normal, 'PLAN_NON_ENABLING') && $normal['request_union']['low_to_high'] === 3);
$badDest = plan_lc(fn($b, $s) => $s->destination->mode = 'mysql');
check_lc('LB21', 'Every database destination mode is unsupported', reason_lc($badDest, 'REJECT_DESTINATION', 'OFFLINE_DESTINATION_ONLY'));
$badDest = plan_lc(fn($b, $s) => $s->destination->incarnation = 'syn:otherD');
check_lc('LB21', 'Wrong destination incarnation cannot be planned', reason_lc($badDest, 'REJECT_DESTINATION', 'DESTINATION_IDENTITY'));
$badDest = plan_lc(fn($b, $s) => $s->destination->host = 'example.mysql.database.azure.com');
check_lc('LB21', 'Host/connection fields are not accepted by offline schema', reason_lc($badDest, 'REJECT_INPUT', 'SCHEMA_FIELDS'));
foreach (['{"x":1,"x":2}', '{"x":1,"\u0078":2}', '{"x":1.2}', '{"x":1e2}', '{"x":2147483648}', '{"x":01}', '{"x":true,}', "{\"x\":\"\xFF\"}", '{"x":"\uD800"}', str_repeat('[', 26) . '0' . str_repeat(']', 26), str_repeat(' ', 1048577)] as $i => $bad) {
    check_lc('LB22', 'Bounded parser rejection ' . $i, rejected_json_lc($bad));
}
foreach ([fn($b, $s) => $b->format = 'unknown', fn($b, $s) => $b->domain = 'production', fn($b, $s) => $b->accounts[0]->source_ref = 'student@example.invalid', fn($b, $s) => $b->checkpoint->counts->low_to_high = 1.5, fn($b, $s) => $b->events[0]->time = time('2026-02-30T00:00:00Z'), fn($b, $s) => $b->events[0]->time = time('2026-10-15T10:00:00+02:00'), fn($b, $s) => $b->sql = 'DROP TABLE users', fn($b, $s) => $b->events[0]->body = 'private', fn($b, $s) => $b->path = 'https://example.invalid'] as $i => $mutator) {
    check_lc('LB22', 'Strict schema rejection ' . $i, reason_lc(plan_lc($mutator), 'REJECT_INPUT'));
}
$tampered = clone $a; $tampered->bundle_digest = str_repeat('0', 64);
check_lc('LB23', 'Modified artifact/semantic digest fails catalogue check', reason_lc(evaluate($b, $s, $tampered, $at), 'REJECT_INPUT', 'CATALOG_BUNDLE_MISMATCH'));
$custody = plan_lc(fn($b, $s) => $b->provenance->custody_ref = 'syn:untrusted-custody');
check_lc('LB23', 'Contradictory custody is a conflict', reason_lc($custody, 'CONFLICT_NO_APPLY', 'CONTRADICTORY_CUSTODY'));
$source = plan_lc(fn($b, $s) => $b->provenance->authority_ref = null);
check_lc('LB23', 'Missing source authority stays incomplete even with matching digest', reason_lc($source, 'INCOMPLETE_NO_APPLY', 'MISSING_PROVENANCE'));
$missingProof = clone $a; $missingProof->proofs = [];
$missingProofResult = evaluate($b, $s, $missingProof, $at);
check_lc('LB23', 'Named proof labels without scoped records are incomplete and never labelled covered', reason_lc($missingProofResult, 'INCOMPLETE_NO_APPLY', 'MISSING_REFERENCED_PROOF') && array_filter($missingProofResult['dimensions'], fn($d) => $d['covered']) === []);
$wrongProof = decode(encode($a)); $wrongProof->proofs[0]->claim_digest = str_repeat('0', 64);
check_lc('LB23', 'Proof from wrong pair/boundary/mapping scope conflicts', reason_lc(evaluate($b, $s, $wrongProof, $at), 'CONFLICT_NO_APPLY', 'PROOF_SCOPE_CONFLICT'));
$expired = plan_lc(fn($b, $s) => $b->provenance->expires_at = time('2026-10-16T12:00:00Z'));
check_lc('LB25', 'Expiry boundary is exclusive; no import/read renewal', reason_lc($expired, 'REJECT_INPUT', 'SOURCE_EXPIRED'));
$expired = plan_lc(fn($b, $s) => $b->events[0]->retain_until = time('2026-10-16T12:00:00Z'));
check_lc('LB25', 'Own original event-detail expiry cannot be reset by fresh bundle', reason_lc($expired, 'REJECT_INPUT', 'EVENT_DETAIL_EXPIRED'));
$earlyProof = decode(encode($a)); $earlyProof->proofs[0]->expires_at = time('2026-10-17T00:00:00Z');
$limited = evaluate($b, $s, $earlyProof, $at);
check_lc('LB25', 'Earliest proof expiry also bounds proposed plan retention', reason_lc($limited, 'PLAN_NON_ENABLING') && $limited['retention_ceiling'] === '2026-10-17T00:00:00.000000Z');
$native = plan_lc(fn($b, $s) => $s->state->history_state = 'complete_from_genesis', true);
check_lc('LB31', 'Native genesis destination cannot be downgraded/imported', reason_lc($native, 'REJECT_DESTINATION', 'LEGACY_TARGET_ONLY'));
check_lc('LB31', 'Epoch authority supplied in bundle rejected', reason_lc(plan_lc(fn($b, $s) => $b->writer_epoch = 'syn:fake'), 'REJECT_INPUT', 'SCHEMA_FIELDS'));
$observations = plan_lc(function ($b, $s) {
    $approval = event('syn:approval122', 122, 'request_approved', 'syn:B1'); $approval->request_ref = fact_id($b->events[0]); $b->events[] = $approval;
    $s->native_events[] = $approval; $s->state->current_request->status = 'approved';
    $b->observations[] = obj(['request_ref' => fact_id($b->events[0]), 'position' => 122, 'status' => 'approved', 'source_ref' => 'syn:source']);
}, true);
check_lc('LB33', 'Pending then approved observations count one creation with separate actual action', reason_lc($observations, 'PLAN_NON_ENABLING') && $observations['request_union']['low_to_high'] === 3 && $observations['unique_fact_count'] === 2);
$observations = plan_lc(function ($b, $s) {
    $b->observations[] = obj(['request_ref' => fact_id($b->events[0]), 'position' => 122, 'status' => 'approved', 'source_ref' => 'syn:source']);
});
check_lc('LB33', 'Capture time does not invent missing actual approval action', reason_lc($observations, 'INCOMPLETE_NO_APPLY', 'MISSING_TRANSITION_ACTION'));

// Adversarial cross-field checks: a consistent JSON shape is not a consistent history.
$ambiguous = plan_lc(function ($b, $s) { $b->events[0]->actor = null; $s->native_events[0]->actor = null; }, true);
check_lc('LB07', 'Unknown request actor never guesses a direction/count', reason_lc($ambiguous, 'INCOMPLETE_NO_APPLY') && $ambiguous['request_union'] === ['low_to_high' => null, 'high_to_low' => null]);
$missingTail = plan_lc(function ($b, $s) { $b->events[] = event('syn:unproved122', 122); });
check_lc('LB19', 'Post-H fact needs the captured destination tail', reason_lc($missingTail, 'INCOMPLETE_NO_APPLY', 'MISSING_DESTINATION_TAIL_FACT'));
$quiescence = plan_lc(fn($b, $s) => $b->coverage->method = 'synthetic_quiesced');
check_lc('LB19', 'Quiescence label cannot replace a frozen boundary proof', reason_lc($quiescence, 'INCOMPLETE_NO_APPLY', 'QUIESCENCE_NOT_ESTABLISHED'));
$prefixConflict = plan_lc(function ($b, $s) {
    $b->checkpoint->counts->low_to_high = 0; $b->events[0]->position = 99; $s->native_events[0]->position = 99; $s->state->counts->low_to_high = 0;
}, true);
check_lc('LB10', 'Observed prefix send contradicts certified zero', reason_lc($prefixConflict, 'CONFLICT_NO_APPLY', 'PREFIX_COUNT_CONTRADICTION'));
$changedExpiry = plan_lc(fn($b, $s) => $s->state->current_request->expires_at = time('2026-10-23T10:00:00Z'), true);
check_lc('LB07', 'Snapshot cannot replace a request original expiry', reason_lc($changedExpiry, 'CONFLICT_NO_APPLY', 'CHANGED_ORIGINAL_EXPIRY'));
$badPolicy = plan_lc(function ($b, $s) { $b->events[0]->expires_at = time('2026-10-23T10:00:00Z'); $s->native_events[0]->expires_at = $b->events[0]->expires_at; $s->state->current_request->expires_at = $b->events[0]->expires_at; }, true);
check_lc('LB07', 'Declared approved policy cannot validate an eight-day request', reason_lc($badPolicy, 'CONFLICT_NO_APPLY', 'DECLARED_REQUEST_POLICY_CONFLICT'));
$deadline = plan_lc(fn($b, $s) => $s->state->cooldowns->low_to_high = time('2026-10-17T10:00:00Z'), true);
check_lc('LB07', 'Stored cooldown without its original terminal anchor stays incomplete', reason_lc($deadline, 'INCOMPLETE_NO_APPLY', 'UNPROVED_COOLDOWN_ANCHOR'));
$decline = plan_lc(function ($b, $s) { $b->checkpoint->counts->high_to_low = 1; $s->state->counts->high_to_low = 1; $s->state->declines->high_to_low = true; }, true);
check_lc('LB16', 'Reverse pending request does not clear existing directional decline', reason_lc($decline, 'PLAN_NON_ENABLING') && $decline['actual_write_set'] === [] && !$decline['grants_chat']);
$badClock = plan_lc(fn($b, $s) => $s->state->chat->segments[0]->access_expires_at = time('2026-10-09T10:00:00Z'), true);
check_lc('LB26', 'Closed-history read deadline cannot be extended by snapshot', reason_lc($badClock, 'CONFLICT_NO_APPLY', 'CLOSED_HISTORY_DEADLINE'));
$causal = plan_lc(function ($b, $s) {
    $approval = event('syn:badApproval', 122, 'request_approved'); $approval->request_ref = fact_id($b->events[0]);
    $b->events[] = $approval; $s->native_events[] = $approval;
});
check_lc('LB33', 'Only recipient may be the actual approval actor', reason_lc($causal, 'CONFLICT_NO_APPLY', 'TRANSITION_ACTOR'));
$causal = plan_lc(function ($b, $s) {
    $approval = event('syn:earlyApproval', 119, 'request_approved', 'syn:B1'); $approval->request_ref = fact_id($b->events[0]); $b->events[] = $approval;
});
check_lc('LB33', 'Approval before creation cannot prove a valid transition', reason_lc($causal, 'CONFLICT_NO_APPLY', 'TRANSITION_CAUSAL_CONFLICT'));
$causal = plan_lc(function ($b, $s) {
    $approval = event('syn:approve122', 122, 'request_approved', 'syn:B1'); $approval->request_ref = fact_id($b->events[0]);
    $decline = event('syn:decline122', 122, 'request_declined', 'syn:B1'); $decline->request_ref = fact_id($b->events[0]); $decline->identity->ordinal = 1;
    $b->events = array_merge($b->events, [$approval, $decline]); $s->native_events = $b->events;
});
check_lc('LB33', 'Contradictory decisions on same request are not reconciled by last writer', reason_lc($causal, 'CONFLICT_NO_APPLY', 'MULTIPLE_REQUEST_DECISIONS'));
$forgotten = plan_lc(function ($b, $s) { $b->observations[0]->request_ref = str_repeat('f', 64); });
check_lc('LB33', 'Unknown observation target supplies no private lookup or permission', reason_lc($forgotten, 'INCOMPLETE_NO_APPLY', 'UNPROVED_OBSERVATION'));
$restore = plan_lc(function ($b, $s) { $e = event('syn:restore122', 122, 'restored'); $b->events[] = $e; $s->native_events[] = $e; }, true);
check_lc('LB16', 'Bare restoration action lacks both fresh consents and stays gated', reason_lc($restore, 'INCOMPLETE_NO_APPLY', 'UNSUPPORTED_MUTUAL_CONSENT_PROOF'));
$clearance = plan_lc(function ($b, $s) { $e = event('syn:clear122', 122, 'block_cleared'); $b->events[] = $e; $s->native_events[] = $e; }, true);
check_lc('LB16', 'Historical clearance action cannot stand in for current reviewer authority', reason_lc($clearance, 'INCOMPLETE_NO_APPLY', 'UNSUPPORTED_REVIEW_ACTION_PROOF'));
$overflow = plan_lc(function ($b, $s) { $b->checkpoint->counts->low_to_high = 2147483647; });
check_lc('LB22', 'Computed count union is also bounded', reason_lc($overflow, 'REJECT_INPUT', 'COUNT_UNION_OVERFLOW'));
check_lc('LB22', 'NUL object property rejected without PHP property exception', rejected_json_lc('{"\u0000x":1}'));
check_lc('LB22', 'Node budget limits shallow but massive arrays', rejected_json_lc('[' . implode(',', array_fill(0, 20001, '0')) . ']'));
$tooMany = plan_lc(function ($b, $s) { $b->events = array_fill(0, 1001, $b->events[0]); });
check_lc('LB22', 'Fact-list budget enforced before reconciliation', reason_lc($tooMany, 'REJECT_INPUT', 'SCHEMA_LIST'));

// Real CLI processes with php -n: no database adapter; probe drivers separately below.
$runDir = dirname(__DIR__) . '/.tmp/l02c-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
mkdir($runDir, 0700, true);
foreach (['bundle' => $b, 'snapshot' => $s, 'catalog' => $a] as $name => $input) { file_put_contents($runDir . '/' . $name . '.json', encode($input)); }
$inputPaths = glob($runDir . '/*.json');
$beforeFiles = array_map(fn($p) => hash_file('sha256', $p), $inputPaths);
function cli_lc(array $arguments, array $ini = []): array
{
    $pipes = [];
    $p = proc_open(array_merge([PHP_BINARY, '-n'], $ini, [dirname(__DIR__) . '/bin/legacy_history.php'], $arguments), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
    if (!is_resource($p)) { throw new RuntimeException('CLI failed'); }
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($p), $out, $err];
}
$args = ['plan', '--bundle=' . $runDir . '/bundle.json', '--snapshot=' . $runDir . '/snapshot.json', '--catalog=' . $runDir . '/catalog.json', '--at=' . $at];
putenv('DB_HOST=never-connect.invalid'); putenv('DB_PASSWORD=synthetic-unused'); putenv('APP_ENV=production'); putenv('MAIL_PROVIDER=synthetic-unused');
$denyEffects = ['-d', 'disable_functions=stream_socket_client,fsockopen,pfsockopen,mail,file_put_contents,mkdir,unlink,rename,copy,touch,chmod,exec,shell_exec,system,passthru,proc_open,dl', '-d', 'disable_classes=PDO,mysqli'];
// Local fopen('rb') and stdout are the planner's only I/O; database classes are disabled.
[$exit, $out, $err] = cli_lc($args, $denyEffects);
check_lc('LB24', 'Actual CLI works with database classes and network/mail/process/mutation functions disabled', $exit === 0 && $err === '' && json_decode($out, true, 512, JSON_THROW_ON_ERROR)['outcome'] === 'PLAN_NON_ENABLING');
[$exit2, $out2, $err2] = cli_lc($args, $denyEffects);
check_lc('LB24', 'Actual repeat run byte-identical and input snapshot/catalogue unchanged', $exit2 === 0 && $out === $out2 && $err2 === '' && $beforeFiles === array_map(fn($p) => hash_file('sha256', $p), $inputPaths));
foreach ([['apply'], array_merge($args, ['--apply=true']), array_merge($args, ['--database=production']), array_merge($args, ['--snapshot=http://example.invalid'])] as $i => $badArgs) {
    [$exit, $out, $err] = cli_lc($badArgs);
    $r = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
    check_lc('LB21', 'Actual CLI rejects apply/database/wrapper/duplicate options ' . $i, $exit === 2 && $err === '' && $r['actual_write_set'] === [] && $r['outcome'] === 'REJECT_INPUT');
}
[$exit, $out, $err] = cli_lc(['validate', '--bundle=' . $runDir . '/bundle.json', '--catalog=' . $runDir . '/catalog.json', '--at=' . $at]);
check_lc('LB24', 'Offline bundle validation cannot certify absent destination', $exit === 2 && $err === '' && json_decode($out, true, 512, JSON_THROW_ON_ERROR)['outcome'] === 'INCOMPLETE_NO_APPLY');
check_lc('LB26', 'Closed deadline snapshot remains unchanged by planner', $beforeFiles === array_map(fn($p) => hash_file('sha256', $p), $inputPaths) && $normal['preserved_state_fingerprint'] === digest($s, 'state'));
// Changing bytes without updating custody fails even when JSON semantics are unchanged.
file_put_contents($runDir . '/bundle.json', encode($b) . "\n");
[$exit, $out, $err] = cli_lc($args);
check_lc('LB23', 'Actual CLI checks raw bytes separately from semantic bundle digest', $exit === 2 && $err === '' && json_decode($out, true, 512, JSON_THROW_ON_ERROR)['reasons'] === ['RAW_CUSTODY_DIGEST_MISMATCH']);
$a->raw_digests->bundle[] = hash('sha256', encode($b) . "\n"); file_put_contents($runDir . '/catalog.json', encode($a));
[$exit, $out, $err] = cli_lc($args);
check_lc('LB08', 'Separately catalogued layout change has same semantic projection', $exit === 0 && $err === '' && $out === $out2);
file_put_contents($runDir . '/bundle.json', encode($b)); file_put_contents($runDir . '/catalog.json', encode(catalog($b, $s)));
$wrapperArgs = $args; $wrapperArgs[2] = '--snapshot=php://filter/resource=config/db.php';
[$exit, $out, $err] = cli_lc($wrapperArgs);
check_lc('LB22', 'Actual local reader rejects wrappers before file access', $exit === 2 && $err === '' && json_decode($out, true, 512, JSON_THROW_ON_ERROR)['reasons'] === ['LOCAL_FILE_REQUIRED']);
// Probe the actual child interpreter separately; do not infer extension absence from its command alone.
$pipes = []; $probe = proc_open([PHP_BINARY, '-n', '-r', 'echo json_encode(["extensions"=>get_loaded_extensions(),"pdo_drivers"=>class_exists("PDO")?PDO::getAvailableDrivers():[]]);'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($probe)) { throw new RuntimeException('Probe failed'); }
fclose($pipes[0]); $probeResult = json_decode(stream_get_contents($pipes[1]), true, 512, JSON_THROW_ON_ERROR); $extensions = $probeResult['extensions']; $pdoDrivers = $probeResult['pdo_drivers']; $probeError = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $probeCode = proc_close($probe);
check_lc('LB24', 'Observed child has zero PDO drivers and no pdo_mysql/curl/mysqli modules', $probeCode === 0 && $probeError === '' && $pdoDrivers === [] && array_intersect(array_map('strtolower', $extensions), ['pdo_mysql', 'mysqli', 'curl']) === []);

$caseStatus = []; $partial = [8,13,14,15,16,17,19,20,21,23,24,25,26]; $notRun = [11,12,27,28,29,30,32];
for ($i = 1; $i <= 33; $i++) { $caseStatus['LB' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)] = in_array($i, $notRun, true) ? 'NOT RUN' : (in_array($i, $partial, true) ? 'PARTIAL' : 'PASS'); }
$sources = [];
foreach (['php/legacy_history.php', 'bin/legacy_history.php', 'tests/support/legacy_history_fixture.php', 'tests/unit_legacy_history.php', '.github/workflows/ci.yml'] as $file) { $sources[$file] = hash('sha256', str_replace("\r\n", "\n", file_get_contents(dirname(__DIR__) . '/' . $file))); }
$evidence = ['format' => 'bushisa.l02c-execution.v1', 'started_at' => $started, 'ended_at' => gmdate('c'), 'php' => PHP_VERSION, 'os' => PHP_OS_FAMILY,
    'test_pid' => getmypid(), 'assertion_count' => count($checks), 'checks' => $checks, 'sources_lf_sha256' => $sources,
    'contract_case_status' => $caseStatus, 'status_scope' => 'Synthetic validator/offline planner only. PARTIAL keeps application and real-world variants open.',
    'planner_mode' => 'offline-synthetic', 'database_connections' => 0, 'database_adapter' => 'ABSENT', 'observed_child_extensions' => $extensions, 'observed_pdo_drivers' => $pdoDrivers, 'isolation_ini_options' => $denyEffects,
    'input_files_sha256_before' => $beforeFiles, 'input_files_sha256_after' => array_map(fn($p) => hash_file('sha256', $p), $inputPaths),
    'overlap_claim' => 'NONE; no apply/HTTP workers', 'production_authenticity' => 'NOT_VERIFIED'];
$reportPath = $runDir . '/execution.json'; file_put_contents($reportPath, json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
fwrite(STDOUT, 'unit_legacy_history PASS: ' . count($checks) . ' assertions; evidence ' . $reportPath . "\n");
