# L02d: guarded synthetic import transactions and receipts

Continued from `12b8ff70301fbad8c8eb8da20bd0f7982a7ba5ae` on
`codex/l02d-synthetic-import-20261016`. The starting tree/index were clean;
no applicable AGENTS.md was found in the checkout or ancestors. The implementation
is complete for this bounded synthetic scope. Qualified review and the gates
below remain open. The [decisions](decisions.md), [L02b contract](l02b-legacy-history-import-contract.md)
and [backlog](backlog.md) remain authoritative. No policy choice is reopened.
Takudzwa Hondova remains the product reviewer; no qualified reviewer/operator
appointment is inferred. Exact historical owner acceptance UTC/Harare timestamps
were not captured and are not reconstructed from these test times.

## Supported format and engineering review

The [L02c offline planner](l02c-offline-history-planner.md), its CLI and validator
are unchanged. Its `actual_write_set=[]` and historical offline-only LB results
still describe that module. The separate executor is
[bin/synthetic_legacy_import.php](../../bin/synthetic_legacy_import.php), using
[php/synthetic_legacy_import.php](../../php/synthetic_legacy_import.php).
It does not give the offline planner an apply mode or database adapter.

| Record | Supported closed profile |
|---|---|
| Bundle | `bushisa.legacy-history.v1`, domain `synthetic`, policy semantics `decisions-1-33-v1`; the exact source/incarnation/stream/schema, destination, original-account mappings, pair/aliases, provenance, checkpoint, coverage, events, observations, equivalences and expected fingerprint/version fields validated by L02c. One pair per invocation. |
| Snapshot/catalogue/plan | Existing `bushisa.legacy-snapshot.v1`, `bushisa.legacy-fixture-catalog.v1` and `bushisa.legacy-plan.v1` formats. Snapshot mode remains `offline-synthetic`. Raw bytes must match the catalogue; the entire supplied plan is recomputed, allowing only its processing timestamp and the known optional synthetic custody label to differ. No independently trusted production catalogue exists. |
| Event | The existing closed identity tuple, type, actor/recipient, position, time, original expiry, immutable retain-until, request reference, generation and source. Supported event names are `request_created`, `request_approved`, `request_declined`, `request_cancelled`, `request_expired`, `chat_ended`, `unmatched`, `restored`, `mutual_matched`, `block_created`, `block_cleared`, `report_accepted`, `probation_started`. Grammar acceptance is separate from complete proof: bare restoration/mutual-match/review/probation actions remain INCOMPLETE under the existing validator. No new permissive proof family is added. |
| Destination permit | `bushisa.synthetic-import-permit.v1`: synthetic domain, explicit host/port/database/user, incarnation, actual server UUID, private random nonce, immutable UTC expiry and boolean test-controls permission. Unknown fields/versions are rejected. It authorizes an owned fixture, not real history. |

The existing parser limits each local artifact to 1 MiB, depth 24 and 20,000
nodes, with 1,000 events/observations and the existing 256-item general list bound.
It reads/hashes the same bytes, rejects
duplicate decoded JSON keys, unknown fields, unbounded inputs and executable or
private-content fields. SQL is static/prepared; supplied content is never run.
Missing/contradictory evidence, unsupported proof families and native-genesis
targets cannot become applying plans. A digest or internally consistent fictional
catalogue is not authentic or complete production provenance.

| Engineering gap | Implemented resolution / practical limit |
|---|---|
| Ambient configuration could select a live destination | CLI-only executor; no .env or application bootstrap. Require explicit development/debug/test/runtime-DDL/fake-mail flags, separate import enable flag, literal loopback, explicit port, `bushisa_test_l02d...` or `bushisa_ci_l02d...` name and `l02d_...` SQL identity. Remote override, root executor identity and force/production/apply options are rejected before connection. A test-like name alone is insufficient. |
| A valid file might point to the wrong local server/database | Preconnection permit/config agreement; then an explicitly provisioned, unexpired database marker bound to nonce hash, destination incarnation and actual server UUID. Each pair also needs a separately provisioned snapshot/live-state binding. Migration 008 creates neither marker nor binding. |
| Pair version does not cover every writer | Recheck the complete validated offline snapshot and a separately bound SQL metadata fingerprint inside a SERIALIZABLE transaction. Locked reads cover account/session/consent/eligibility, pair/match/alias/count/decline/window state, native events/segments, message metadata, blocks/report links/assignments/probation, schema/epoch/genesis and replay metadata. Bound reads stop at 5,000 rows/category. Profiles/preferences/answers are read temporarily only in these synthetic fixtures; only the digest is persisted. No message/case body or credential is captured by this guard. This is not a production capture adapter or observed writer cutover. |
| Duplicate representation versus a different action | Canonical bundle plus effect projection defines intent. JSON formatting, field order, diagnostic duplicate counts and processing time do not create effects. Raw reformatting must still be separately catalogued. Same scoped key/different intent conflicts. Same pair/bundle under another key links to the existing receipt atomically. Original fact identities/payload digests deduplicate across bundles; immutable-content/expiry conflicts reject. |
| Retry after an uncertain result | Check current permit/binding/registry continuity, then existing key/receipt before old pre-state comparison. Status needs only permit/pair/key, never the vanished original files. It returns content-free committed metadata or NOT_COMMITTED for an intact authorized registry. Missing/expired/corrupt continuity fails closed, not NOT_COMMITTED followed by reapplication. |
| A co-located registry could be mistaken for restore protection | Monotone receipt/fact/key/equivalence cardinality frontiers detect the tested missing-detail cases; receipt digests validate stored results. They do not prove independent durability or detect every consistently restored stale copy. L03 independent deletion/block/consent replay remains required. |

## Atomic writes, retries and retained restrictions

[Migration 008](../../database/migrations/008_synthetic_legacy_import.sql) adds
separate destination/pair bindings, private fact/equivalence tables, import keys
and receipts. It is additive and preserves existing data. No active marker,
genesis, epoch change or certification is created by fresh/upgrade/repeat runs.
Fixture setup in [synthetic_import_fixture.php](../../tests/support/synthetic_import_fixture.php)
is separate from execution and creates only fictional accounts/data in guarded
disposable databases.

The executor acquires the exact existing database-specific report mutex before
BEGIN, then locks the marker/binding and metadata ranges. It recomputes validity
using server UTC and checks actual current account/session, pair/count and complete
metadata fingerprints. A stale plan cannot override newer state. All private
facts/equivalences, permitted monotone request/ending floors, one pair-version
increment, receipt, key and registry-frontier increments commit together.
The count is the validated prefix/disjoint-identity union, never a blind addition,
counter reset or clamp to three. Every other field remains untouched.

Imports never write ordinary reportable events, report intake/evidence, probation,
findings, sanctions, consent, blocks/clearance, chat/request grants or session
versions. They do not fill missing ending actor/time or change original deadlines.
An accepted synthetic receipt keeps `runtime_history_state=legacy_incomplete`,
`grants_chat=false`, `changes_sessions=false`, `production_authenticity=NOT_VERIFIED`.
The real request/message/unblock routes still enforce their independent guards.
No eligibility adapter or production import mode exists.

The CLI accepts `import` with permit/bundle/snapshot/catalogue/plan files and a
64-hex-character key, or `status` with permit/pair/key only. Exit 0 returns the
scoped result, exit 2 rejects input/state/authority, and exit 3 directs an uncertain
failure to status. There is no caller-selected apply clock. Test faults/barriers
require both a test-enabled permit and a separate environment flag.

Runtime SQL privileges in the fixture are SELECT plus database-confined LOCK
TABLES (needed by the observed local engine's locking reads), INSERT on the four
new fact/equivalence/receipt/key tables, and column-scoped UPDATE only on import
frontiers, request counts and pair ending-count/version. Tests prove denial of
account/block/certification writes and DDL. The temporary account is scoped to
the server-observed client host, without a wildcard host grant. This local result is not
Azure/other-engine compatibility proof.

Lock acquisition waits at most five seconds. Engine errors 1205/1213 roll back
and retry the whole guarded transaction at most three attempts; exhaustion or
changed state requires replan. An uncertain commit error directs the caller to
status instead of blind replay. Actual fault tests cover four precommit write
stages. The lost-result test exits the CLI with status 75 and no output after a
real commit; it does not claim an operating-system crash or network partition.
The forced 40001 test is explicitly separate from a real InnoDB deadlock.

Each fact/receipt/key/equivalence expiry is bounded by the original validated
source/proof/snapshot/detail ceiling and the shorter fixture permit/pair expiry.
Retries do not extend it. The test permit lasts two hours; that is a synthetic
capability limit, not approval of a new real-data retention category. Minimal
pair floors keep their existing original-pair lifecycle. Expired bindings/receipts
deny access/reapplication; this does not claim physical erasure. Staging/receipt
purpose approval, cleanup/frontier compaction, account erasure and independent
restore replay remain L03/privacy work. Conditional 7+35+7=49-day recovery
arithmetic is unchanged and is never a universal import retention constant.

## Exact local verification

[Machine-readable evidence](evidence/l02d-2026-10-17.json) contains individual
checks, source hashes, actual process/connection IDs and scoped results.
These tests ran on a working tree based on 12b8ff7; LF-normalized source hashes
bind the tested code to this checkpoint. Local Windows PHP **8.5.11 NTS**, MySQL
**9.6.0**, owned server **127.0.0.1:3331**, explicit disposable databases and
fake mail only. These versions are not Azure runtime evidence. Test/observation
times below are not owner acceptance times.

| Check | Executed result and attribution |
|---|---|
| [Import suite](../../tests/integration_l02d_imports.php) | **100 assertions PASS**, 2026-10-16T22:45:25Z–22:46:53Z; 2026-10-17 00:45:25–00:46:53 Africa/Harare (UTC+02:00). Database `bushisa_test_l02d_main`; five CLI overlap scenarios with distinct PIDs and actual CONNECTION_IDs. |
| [Pure guards/preflight](../../tests/unit_synthetic_import_guard.php) | **34 assertions PASS**, 2026-10-16T22:21:34Z; 2026-10-17 00:21:34 Africa/Harare. Actual `php -n -d disable_classes=PDO`; no connection API invoked. Unsafe/missing settings, wrong permits, forged plans and incomplete/unsupported inputs rejected. |
| Unchanged offline suite | **105 assertions PASS**, 2026-10-16T22:47:08Z–22:47:11Z / 2026-10-17 00:47:08–00:47:11 Africa/Harare. Its nested LB statuses remain scoped to the unchanged offline module; the combined current matrix is below. |
| Affected pair-route regression | **396 assertions PASS**, 11 two-worker overlaps, 2026-10-16T22:22:13Z–22:22:50Z / 2026-10-17 00:22:13–00:22:50 Africa/Harare; `bushisa_test_l02d_pairreg`. |
| Affected report regression | **245 assertions PASS**, eight two-worker overlaps, 2026-10-16T22:24:22Z–22:25:01Z / 2026-10-17 00:24:22–00:25:01 Africa/Harare; `bushisa_test_l02d_reportreg`. |
| Other affected checks | Fresh guarded `bushisa_test_l02d_regression`: policy_safeguards_p0_flows, integration_core_p0_flows and unit_disposable_database_guard PASS, exit 0. Exact per-command start/end were not captured. Five new PHP files lint PASS. |
| Migration rehearsals | Fresh 001–008 plus repeat; existing 001–007 with synthetic users/text/budgets/cutoff/block upgraded through 008 plus repeat, preserving exact pre-existing rows. Upgrade database `bushisa_test_l02d_main_upgrade`. |

Five importer overlaps record PID/connection pairs: 26980/48 and 32192/49;
23992/50 and 972/51; 25464/52 and 3976/53; 19644/55 and 28028/54;
23052/57 and 8016/56. Both participants reach a pre-lock rendezvous while
the parent holds the exact shared mutex. Tests then release it, recording
same-key replay, distinct-key deduplication, competing-intent conflict, competing
bundle replan, or a session increment committed before both current-state checks.
This is genuine CLI/process/connection overlap, not HTTP-worker coverage.
Separate regressions retain their own HTTP-worker evidence. The two HTTP servers
used by the new import suite serve serial authorization checks only.

The owned test server was identity-checked and stopped at 2026-10-16T22:47:51Z /
2026-10-17 00:47:51 Africa/Harare. Fixture files/data remain ignored synthetic
artifacts; shutdown is not a claim of physical erasure or a restore rehearsal.

The CI workflow now includes pure guards, the guarded import suite and its cURL
extension requirement. No remote
CI result for this branch is claimed. Historical candidate CI remains exactly
head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push `38546846225`, PR
`38546851035`; none is L02d execution evidence. Historical L02a/L02c artifacts and
A01–A45 statuses remain unchanged: **24 PASS, 19 PARTIAL, 2 NOT RUN**.

Documentation validation PASS: 89 local links, six heading targets, 33 consecutive
LB cases with matching contract/artifact statuses, 49 matching LF-normalized
source hashes and unchanged historical decisions/offline/A-case artifacts.
Whitespace/diff checks pass; conditional 7+35+7=49 arithmetic and the unavailable
historical approval instant remain unchanged. These are documentation/attribution
checks, separate from functional execution and independent review. The older
38-link check remains documentation validation only.

## Current LB01–LB33 results

**19 PASS, 14 PARTIAL, 0 wholly NOT RUN.** PASS is limited to the stated synthetic
observation. Fourteen PARTIAL cases retain explicitly NOT RUN or UNVERIFIED
variants; the matrix is not complete. Inherited offline checks were rerun and
remain separately attributed. No LB test is credited to A01–A45.

| Case | Status | Executed scope / remaining gate |
|---|---|---|
| LB01 | PASS | Existing complete fictional prefix/tail plan; actual import yields three, preserves fixed clocks and legacy state, no grant/epoch. |
| LB02 | PASS | Rerun offline positive-origin zero versus missing-prefix unknown. |
| LB03 | PASS | Rerun offline incompleteness; applying incomplete source has whole-database zero-write verification. |
| LB04 | PASS | Rerun offline original-account/incarnation/alias ambiguity rejection. |
| LB05 | PASS | Rerun immutable conflict checks; actual conflicting input/stored immutable expiry rejected with whole-database state unchanged. |
| LB06 | PASS | Rerun equal-time/no-causal-order fixture remains incomplete. |
| LB07 | PASS | Rerun unknown ender/time/deadline and invalid policy/anchor checks. |
| LB08 | PASS | Actual identical retry, catalogued JSON reordering/reformatting, duplicate copies and new-key alias return one receipt without repeated effects; changed valid intent conflicts. |
| LB09 | PASS | Rerun overlap proof; actual equivalent source/native identities persist three raw facts/one equivalence but count one request beyond the prefix. Retry has no effects; fresh current bundle stages zero repeated facts. |
| LB10 | PASS | Rerun unexplained four/explained five; actual overlapping floor is three, not four. |
| LB11 | PASS | Four real precommit rollback stages; no receipt/effects; safe retries. Forced first 40001 rolls back then commits once on attempt two, not real-deadlock evidence. |
| LB12 | PASS | Actual postcommit CLI exit/no response; status discovers commit; retry preserves it. Unknown key, key alias and receipt integrity checks executed. |
| LB13 | PARTIAL | Offline stale version plus actual competing bundles: one commits, other replans without overwriting. Native request/ending HTTP overlap NOT RUN. |
| LB14 | PARTIAL | Offline report/purge fingerprint; actual clearance change without pair bump rejects. Concurrent attachment/cleanup/clearance HTTP variants NOT RUN. |
| LB15 | PARTIAL | Actual parent-committed session increment precedes two rejected importer guards; offline deletion rejects. Deletion/ban/consent writers racing inside apply NOT RUN. |
| LB16 | PARTIAL | Captured restrictions/grants/session metadata unchanged; unsupported restoration/review facts rejected; real creator-unblock with exact block ID still requires clearance. All historical action-proof families and mixed HTTP variants NOT RUN. |
| LB17 | PARTIAL | Exact existing case/probation state preserved; report/probation source actions remain unsupported/non-applying; no mail. A reviewed historical action-proof profile and its application variants NOT RUN. |
| LB18 | PASS | Rerun global-window/probation coverage gap stays incomplete. |
| LB19 | PARTIAL | Rerun missing-writer/boundary/tail/quiescence checks. Actual deployed writers/cutover and long-transaction capture UNVERIFIED. |
| LB20 | PARTIAL | Rerun synthetic committed-tail union. Actual snapshot/change-stream, deletion and long-transaction observation NOT RUN. |
| LB21 | PASS | Preconnection guards with PDO disabled; unsafe production environment and wrong marker zero writes; actual HTTP executor returns empty 404; no force/production mode. |
| LB22 | PASS | Rerun bounded parser suite plus actual malformed duplicate-key/unsupported input with whole-database zero writes and pure forged-plan rejection. |
| LB23 | PARTIAL | Rerun raw bytes/custody/scope/proof mismatch checks; bytes revalidated before connection. Real custody/signer authenticity UNVERIFIED. |
| LB24 | PARTIAL | Offline path remains isolated/read-only and deterministic. SQL read-only planner adapter is absent; its missing-ledger/SQL-identity variant NOT RUN. |
| LB25 | PARTIAL | Rerun original expiry tests; source valid at plan time but expired at server-time apply rejects with zero writes. Physical staging/detail purge and compaction NOT RUN. |
| LB26 | PARTIAL | Original clocks/native metadata preserved; imported private facts cannot mint an ordinary report proof; affected closed-text regression passes. Full import/media/new-segment/cleanup race NOT RUN. |
| LB27 | PARTIAL | Actual missing fact/receipt/key frontier and corrupt receipt fail closed. Independent stale-restore/deletion/consent replay NOT RUN; co-located counts are not restore proof. |
| LB28 | PASS | Five distinct CLI PID/connection rendezvous scenarios; same/different keys, conflicting intents/bundles and fresh-state rejection. No HTTP overlap claim. |
| LB29 | PARTIAL | Actual five-second advisory timeout, no writes, status NOT_COMMITTED. Importer plus two HTTP workers across request/block/review/unmatch/end and actual InnoDB deadlock NOT RUN. |
| LB30 | PASS | Caller-managed first pair commits; invalid different pair is unchanged; first receipt survives. Each invocation is pair-atomic, no whole-batch success claim. |
| LB31 | PASS | Rerun native-genesis/authority rejection; SQL identity cannot alter certification/accounts/blocks/DDL; real request denies incomplete history despite unused budget, messages deny chat and imported private facts issue no report proof. |
| LB32 | PARTIAL | Old committed receipt after newer suspension/session increment returns metadata only, with whole-database no-write proof. Independent restore, inverse-SQL proposal review and operational recovery NOT RUN. |
| LB33 | PASS | Rerun one creation/pending/approved observations, true actor/action/time and contradictory/missing-action checks. |

## Remaining gates and next bounded task

No additional owner policy decision is required for this synthetic implementation.
Existing approved request/restart/restoration/unblock/report rules remain in force.

Engineering can proceed with **L02e: mixed-writer concurrency and lock/retry
verification** on guarded synthetic databases: importer plus two distinct HTTP
workers for the LB29 variants, actual deadlock/timeout distinctions, current-state
row/range coverage and runtime privilege/engine portability. Extend an unsupported
proof family only with its own bounded engineering review; missing proof stays
incomplete. L03 must separately implement privacy-reviewed staging/receipt expiry,
safe frontier compaction, physical purge and independent recovery replay.

Authentic original-account/history custody, actual all-writer snapshot/cutover,
effective historical policy and continuous source coverage remain UNVERIFIED.
Qualified privacy/security/safety review, retention/purpose/access approval,
actual named operators/reviewers, MFA/coverage and independent recovery witnesses
remain externally dependent gates. No synthetic hash, SQL receipt or local code
review closes them. Production migration 005/007/008 application is UNVERIFIED;
an actual isolated restore rehearsal is NOT RUN. No real history import, legacy
activation, production access, real email, push, merge or deployment occurred.
