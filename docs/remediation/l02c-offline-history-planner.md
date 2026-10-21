# L02c: offline legacy-history validator and synthetic planner

Continued from `b4a041b067606437f43caa485eedb33768500d83` on
`codex/l02c-offline-history-planner-20261016`, with a clean tree/index and no
applicable AGENTS.md in the checkout or ancestors. This bounded implementation
is complete locally; qualified review and production/operational gates remain open.
The [L02b contract](l02b-legacy-history-import-contract.md), current
[decisions](decisions.md) and [backlog](backlog.md) remain authoritative.
No policy selection or earlier A01–A45 execution result changes. Historical owner
acceptance UTC/Harare instants remain unavailable, never inferred from this run.
Takudzwa Hondova remains the product reviewer; this does not appoint qualified
reviewers, operators or an independent appeal reviewer.

## Engineering review and implementation boundary

| Contract gap | Implemented resolution | Remaining gate |
|---|---|---|
| No concrete wire grammar, finite limits or parser behavior | Closed synthetic-only v1 profile; bounded parser distinguishes objects/lists, rejects duplicate decoded keys, bad UTF-8/calendar/UTC, floating/overflow numbers and unknown fields. | A real-source profile needs separately reviewed provenance/capture adapters; arbitrary exports are not supported. |
| Hashes or named proof labels might appear to certify history | Separate fixture catalogue binds raw-byte and semantic digests, custody/authority/source identity and typed, scoped proof records. Missing records stay incomplete; conflicting scopes conflict. Every output says synthetic, production authenticity NOT_VERIFIED and no cutover attestation. | A caller-created catalogue is a fictional test oracle, not a signature, independent evidence review or production trust root. No production verification mechanism is implemented. |
| Immutable creation identity versus mutable status | Domain-separated native identities and payload digests; creation counted once across source/native/equivalent copies. Observations require their actual causal action; capture time never supplies an approval time. Incompatible actions/actors conflict. | Receipt/application deduplication and source-specific native-key authenticity remain separate. |
| Snapshot readers could perform writes or connect through ambient configuration | Offline snapshots only. CLI/library import no application bootstrap, .env, PDO, mail or ledger helper. Destination permits only mode offline-synthetic and a bound incarnation; all connection/remote/apply options are rejected. | No database inspection adapter exists. Database guard/SQL read-only-identity acceptance is therefore NOT RUN, rather than claimed from a file test. |
| Pair version misses report, cleanup, session and consent changes | Fingerprint covers the entire validated synthetic snapshot (not a physical SQL capture), including current restrictions, account/session/consent versions, native facts, aliases, schema and recovery version. Changed expectation requires replan; no updates are emitted for execution. | Actual concurrent writes, locks, commit receipts and independent recovery are unimplemented. |
| An unsupported transition proof could silently confer eligibility | Bare mutual-match/restoration/review actions and unproved cooldown/restoration anchors remain INCOMPLETE. Current blocks, bans, declines, closed clocks and probation are preserved; no access decision or policy reset is made. | Extend reviewed source proof profiles where needed. This limitation does not reopen approved restoration/unblock policy. |

The library is [php/legacy_history.php](../../php/legacy_history.php); the CLI is
[bin/legacy_history.php](../../bin/legacy_history.php). No existing application
route, ledger writer, account permission, migration or production setting changes.
Proposed actions are data describing private fact staging and monotone count/end
floors; `actual_write_set=[]`, `applies=false`, `grants_chat=false` and
`changes_sessions=false` on every outcome. Runtime history stays legacy_incomplete.
There is no apply mode, including for synthetic data.

## Executable wire profile

This narrows the L02b conceptual v1 format to **one synthetic original pair** per
invocation. Numeric commit positions are fictional positions in one declared
incarnation/stream, not an Azure/binlog/CDC capability claim. All reference values
must match `syn:[A-Za-z0-9][A-Za-z0-9._:-]{0,90}`. No student contacts, message
bodies, attachments, secrets, coordinates, SQL, URLs or artifact paths are fields
in the profile. Unknown fields are rejected, including purported permissions,
verified markers and complete/genesis flags.

The exact key/type checks are in the library's `bundle_schema`, `snapshot_schema`,
`state_schema`, `event_schema` and `catalog_schema`; the independently loaded
[fixture builder](../../tests/support/legacy_history_fixture.php) constructs
reviewable valid examples. It is never loaded by the planner.

| Record | Required content and checks |
|---|---|
| Bundle | format bushisa.legacy-history.v1, domain synthetic, policy_semantics decisions-1-33-v1; source ref/incarnation/stream/schema; destination incarnation; pair ref; exactly two source/destination original-account mappings with creation clocks; aliases; provenance; nullable checkpoint; coverage; events; observations; equivalences; expected fingerprint/pair version. Source accounts are canonically ordered and destination mapping is injective. |
| Provenance | Collector, authority, custody and artifact references; tool digest; original capture/expiry clocks. Explicit null references are missing evidence. Catalogue contradictions conflict. Expiry at or before processing time rejects; capture/import never extends it. |
| Checkpoint | Ref, B position, certified_prefix or account_origin basis, proof ref, both directional totals, ending count and last end actor/time. No checkpoint means unknown exact totals, not zero; observed counts remain lower bounds. A zero requires positive scoped prefix/origin proof and continuous coverage. Origin basis requires zero counts/endings; observations cannot contradict its prefix. |
| Coverage | B/W/H/C, synthetic_stream or synthetic_quiesced method, W01–W12 revision/range inventory, continuous half-open `(from,through]` ranges, all seven completeness dimensions, scoped proofs and explicit exclusions. W must cover B; H lies between B and C. Frozen quiescence requires B=W=H=C. Gaps/exclusions fail closed. Post-H events need matching native-tail identities/equivalence through C. |
| Completeness dimensions | requests; request_state; endings; restoration; blocks_clearance; account_consent_sessions; global_windows_probation. Each references a separate, scope-bound catalogue record through C. A pair-only proof never fills the global dimension. These are fictional oracle assertions, not verification of actual global windows. |
| Event | Identity tuple incarnation/stream/native_key/nullable ordinal; type; actual actor/recipient; nullable commit position/generation; actual time, original expiry where applicable, immutable retain_until; request reference where applicable; source ref. Same identity/different immutable payload conflicts. Unknown actor/order/time stays unknown. No historical event is generated to explain an aggregate. |
| Event types | request_created/approved/declined/cancelled/expired, chat_ended, unmatched, restored, mutual_matched, block_created/cleared, report_accepted, probation_started. Unsupported mutual-consent/review proof shapes stay incomplete even though their envelope can be parsed. Approved/declined actions require the actual recipient; cancellation requires the requester. |
| Observation | Request identity, snapshot position, observed status and source reference. An observation does not create another send. Non-pending status needs the actual corresponding action; actions before creation or contradictory decisions conflict. No array/hash tie-break invents causal order. |
| Snapshot | format bushisa.legacy-snapshot.v1, domain synthetic; destination mode/incarnation; pair/aliases, C, capture/expiry, native events and full state. It represents existing synthetic metadata, never creates missing rows. Native-genesis/deleted targets are rejected; unknown history is not promoted. |
| Full state | Pair version/history state; two account incarnations/session/status/consent versions; counts/endings/last end; directional declines/cooldowns; match generation/own-unmatch/active likes/agreements and exact restoration/unmatch clocks; underlying chat/suppression/segment clocks; current request; creator/subject blocks and exact report scope/clearance/reviewer; probation; schema/epoch and recovery version. Block/session actors use destination references; event/last-end actors use original source references. |
| Catalogue | format bushisa.legacy-fixture-catalog.v1, domain synthetic; custody/collector/authority/artifact, source/destination incarnations, tool and semantic bundle/snapshot digests; separate allowed raw-byte digests; expiry; proof records with ref, kind, exact scope-bound claim digest and expiry. Kinds: checkpoint, range, dimension, equivalence, writer_inventory, account_mapping. It supplies no production signature or legal authority. |

Technical limits: **1 MiB per file, nesting depth 24, 20,000 parsed values,
1,000 facts per event/observation list, 256 ordinary list entries, 16 raw encodings,
12 writers and seven dimensions**. All applicable limits must hold simultaneously.
Integers/computed counts are bounded to 0–2,147,483,647; counts never clamp to the
three-request budget. Limits are engineering parser bounds, not owner spending
or service commitments.

Clocks are `{value,precision}`: second uses `YYYY-MM-DDTHH:MM:SSZ`, microsecond
adds exactly six fractional digits; unknown and absent use null. **Unknown** means
missing evidence; **absent** means the synthetic state explicitly has no such
deadline/action. Unknown cooldown or required event time cannot imply permission.
UTC instants are normalized for comparison; source precision is retained.
The declared fixture policy checks seven-day requests/probation and seven-day
closed reads, while preserving stored original instants. A real history with an
unproved effective policy cannot adopt this declaration as evidence.

Canonical bundle digests sort object fields and declared sets, preserving native
identity/causal meaning. Raw JSON encoding has a separate custody digest; a
reformatted artifact must be separately catalogued. Fingerprints bind the entire
snapshot, so even a new capture or reordered snapshot set can require conservative
replanning. Imported-source facts never overwrite a newer snapshot or reduce a
counter. Original detail/source/proof/snapshot expiries remain ceilings; no retention
clock restarts and no ordinary content is recovered.

## CLI and repeatability

`validate` takes bundle/catalogue files and reports missing destination evidence.
`plan` also requires a snapshot. Both accept only bounded local regular files,
read once as bytes; no URI/UNC wrapper, network fetch, connection string or
manifest-selected file is followed. Output is JSON to stdout, with fixed reason
codes rather than input text. No diagnostic file is created by the planner.

Fixture setup is separate: `php tests/unit_legacy_history.php` creates a new
owned synthetic directory and prints its name. Example using the executed fixture:

```powershell
$fixtureDir = '.tmp/l02c-20261016-212031-91f060'
php bin/legacy_history.php plan "--bundle=$fixtureDir/bundle.json" "--snapshot=$fixtureDir/snapshot.json" "--catalog=$fixtureDir/catalog.json" --at=2026-10-16T12:00:00Z
```

Use the directory printed by a later setup run if recreating fixtures. `--at` is
the explicit **fictional processing clock** for repeatable tests; it is not an
event, approval time or public service guarantee. Without it, CLI uses current UTC.
Same bytes and processing clock yield byte-identical output. Exit 0 means only
PLAN_NON_ENABLING; exit 2 covers incomplete/conflicting/stale/rejected input.
Database/apply/force/unknown/duplicate options are rejected. Ambient production
environment values do not select a database: there is no configuration loader
or connection adapter. Database-guard claims from L02b are not exercised here.

## Executed evidence and distinct LB01–LB33 results

[Machine-readable local execution](evidence/l02c-2026-10-16.json): **105 assertions
PASS**, `2026-10-16T21:20:31Z`–`21:20:32Z`, displayed in Africa/Harare as
**23:20:31–23:20:32, 2026-10-16 (UTC+02:00)**. Windows PHP **8.5.11 NTS**;
synthetic in-memory values and owned local JSON files only. Five LF-normalized
source hashes bind CLI/library/test/fixture/workflow to that run.

The actual CLI runs under `php -n` with database classes and network/mail/process/
mutation functions disabled. A separate actual extension probe found **zero PDO
drivers** and no pdo_mysql/mysqli/curl modules; core PDO is compiled in. Before/
after hashes of the source bundle, destination snapshot and catalogue match.
Only bounded `fopen(...,'rb')` and stdout are needed by the planner. No adapter,
SQL, transaction, fixture reset or database connection is executed. This proves
the offline path's lack of database effects; it is not a SQL read-only-role test.
No HTTP overlap, actual deadlock or production concurrency is claimed.

The separate LB matrix totals are **13 PASS, 13 PARTIAL, 7 NOT RUN**. PASS concerns
only the applicable synthetic validator/planner observation. PARTIAL explicitly
leaves application/real-world variants unexecuted. The earlier **A01–A45 totals
remain 24 PASS, 19 PARTIAL, 2 NOT RUN** and their execution artifacts are unchanged.

| Case | Status | Exact executed scope / unexecuted remainder |
|---|---|---|
| LB01 | PASS | F: certified prefix + same native tail = 3; original ending; no grant/session/promotion; repeat deterministic. Synthetic model only. |
| LB02 | PASS | Positive origin/empty interval proves synthetic zero; empty surviving rows without prefix leave exact total null. |
| LB03 | PASS | Missing prefix yields known lower bound and unknown total; unknown verified-marker field cannot certify it. |
| LB04 | PASS | Ambiguous destination mapping, unmatched alias and changed incarnation reject/conflict. |
| LB05 | PASS | Same immutable identity/different actual actor conflicts, with no write set. |
| LB06 | PASS | Equal-time same-commit actions without an ordinal remain incomplete; hash order supplies no permission. |
| LB07 | PASS | Unknown ender/time/cooldown, changed expiry, declared eight-day policy and missing cooldown anchor reject or stay incomplete. |
| LB08 | PARTIAL | Duplicate copies/JSON ordering and separately catalogued raw encoding preserve semantics; committed receipts/retry effects NOT RUN. |
| LB09 | PASS | Source/native identity and proved equivalence deduplicate; unproved overlap stays incomplete. No persistent import is claimed. |
| LB10 | PASS | Unexplained current four conflicts; explained five stays five; observed prefix event contradicts a claimed zero. |
| LB11 | NOT RUN | No application transaction/fault rollback implementation. |
| LB12 | NOT RUN | No commit receipt or lost-response apply implementation. |
| LB13 | PARTIAL | Changed snapshot pair version requires replan; actual native write/apply overlap NOT RUN. |
| LB14 | PARTIAL | Report-scope/native cleanup changes fingerprint without pair bump; actual apply race NOT RUN. |
| LB15 | PARTIAL | Session version requires replan; deleted destination rejects; actual deletion/ban/withdrawal overlap NOT RUN. |
| LB16 | PARTIAL | Bans/withdrawal/declines/cutoff preserved without granting; unsupported restoration/clearance proofs remain incomplete. Actual runtime/apply enforcement NOT RUN. |
| LB17 | PARTIAL | Extended probation conflicts; duplicate historical report stays gated without effects. Report intake/application renewal variants NOT RUN. |
| LB18 | PASS | Missing global-window/probation dimension stays incomplete despite pair counts. |
| LB19 | PARTIAL | Missing writer, catch-up interval, native tail and false quiescence/cutover claims reject/gate in fixtures; actual deployed writers/long transaction capture UNVERIFIED. |
| LB20 | PARTIAL | Synthetic post-B committed native event counts once; actual consistent capture, deletion and long-running transaction observation NOT RUN. |
| LB21 | PARTIAL | SQL destination/host/mismatched incarnation and CLI database/apply options rejected before access; disposable DB connection-guard variant NOT RUN because no adapter exists. |
| LB22 | PASS | Duplicate/escaped keys, malformed UTF-8/calendar/offset/numbers, complexity/size/list/union bounds, unknown authority/content/path fields and actual reader wrappers reject. |
| LB23 | PARTIAL | Actual raw-byte mismatch, changed semantic digest, custody/scope conflicts and missing referenced proofs reject/gate; genuine custody/signer authenticity UNVERIFIED. |
| LB24 | PARTIAL | Actual isolated CLI, deterministic repeats, unchanged files, zero drivers and no adapter; missing-ledger SQL read-only-identity variant NOT RUN. |
| LB25 | PARTIAL | Exact source/event expiry denies and does not renew; actual erasure/staging/purge/replacement NOT RUN. |
| LB26 | PARTIAL | Snapshot bytes preserved and extended closed deadline rejected; actual newer-segment/import/cleanup-job behavior NOT RUN. |
| LB27 | NOT RUN | No receipt/frontier retention or independent restore/recovery implementation. |
| LB28 | NOT RUN | No simultaneous applying importer processes. |
| LB29 | NOT RUN | No importer/HTTP-worker overlap or real deadlock. |
| LB30 | NOT RUN | No multi-pair application batch. |
| LB31 | PASS | Native-genesis target and supplied epoch authority reject; no eligibility adapter exists. |
| LB32 | NOT RUN | No inverse-rollback/apply/recovery operation. |
| LB33 | PASS | One creation plus pending/approved observations needs actual recipient action/order; missing action stays incomplete, wrong actor/early/contradictory decisions conflict. |

PHP lint passed for the four new PHP files. The existing CI unit job now includes
the new suite; no remote CI run for this branch is claimed. Historical candidate
CI remains exactly head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push
`38546846225`, PR `38546851035`; it proves nothing about L02c.

Documentation validation PASS: 81 local links and six heading targets across the
three changed Markdown files; both LB matrices match the 33-case artifact and
13/13/7 totals; all 105 recorded assertions pass, with no execution credited to
NOT RUN cases; five source hashes match; the original A-case totals remain
24/19/2; whitespace/diff checks pass. Conditional 7+35+7=49 backup/recovery
arithmetic and the original evidence/approval clocks are unchanged. These are
documentation/attribution checks, separate from functional test execution.

## Remaining gates and next bounded task

No settled owner-policy question is reopened. Actual legacy identity/source
custody, effective historical policy, typed provider commit positions, all-writer
cutover/continuous coverage and managed-service compatibility remain UNVERIFIED.
Named qualified engineering/security/privacy/safety reviewers, retention/purpose/
access approval, actual operators/backup/MFA/coverage and full L03 independent
deletion/block/consent replay remain open. No owner appointment or sign-off is invented.

**Next bounded task, L02d:** review/freeze this supported synthetic wire profile,
then implement a guarded **non-enabling synthetic** forward schema, pair-atomic
fact/receipt import and safe retry/replan behavior. Exercise fresh/upgrade/second
migration runs and LB11/LB12/LB28–LB30 plus applicable PARTIAL transaction variants
on guarded disposable databases, with distinct workers for overlap claims. Keep
unsupported proof families incomplete; no production capture/adapter, legacy
eligibility promotion, actual restore or real-data import follows from L02c.

No database migration or application suite was needed for this isolated offline
module. No production access/configuration change, real import, legacy activation,
real email, push, merge or deployment occurred. Migration 005/007 production
application and actual restore evidence remain unchanged/unverified or NOT RUN.
