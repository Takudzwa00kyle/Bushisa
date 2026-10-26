# L02e: mixed writers, engine retries and restricted-role compatibility

Baseline `734cc34d6be7eff697fb675761d447aa1d20e6a0`, branch
`codex/l02e-mixed-import-races-20261017`; clean starting tree/index, no applicable
AGENTS.md in the checkout or ancestors. Bounded local L02 engineering is complete;
production acceptance and independent review remain open. Authority remains the
[decisions](decisions.md), [L02b contract](l02b-legacy-history-import-contract.md),
[L02c offline scope](l02c-offline-history-planner.md),
[L02d evidence](l02d-synthetic-import-evidence-2026-10-17.md) and [backlog](backlog.md).
No policy choice, owner appointment or whole D-number approval changes.
Takudzwa Hondova remains the product reviewer. The historical acceptance instant
was not captured; these UTC/Harare test observations are not invented approval times.

## Engineering findings and focused repairs

The restricted HTTP role exposed a real defect: MySQL's trigger metadata was
invisible to it, so the old information_schema lookup refused valid native genesis.
Granting the application TRIGGER/DDL privileges is unnecessary. Additive
[migration 009](../../database/migrations/009_pair_trigger_health.sql) creates
`bushisa_pair_trigger_health()`: no arguments, READS SQL DATA, a fixed-schema
SQL SECURITY DEFINER procedure returning only current genesis-trigger presence
and its expected table/event/timing. The runtime receives only EXECUTE on that
procedure. Definer execution privileges are described in the
[MySQL 8.0 routine documentation](https://dev.mysql.com/doc/refman/8.0/en/create-procedure.html).
Its actual behavior under the restricted roles is tested here, not inferred from
a view or administrator query.

[pair_history.php](../../php/pair_history.php) still requires the original positive
genesis record, epoch, original-account membership and current trigger health.
Missing routine, missing EXECUTE or removed trigger denies new privileges. CALL
preserves the surrounding transaction. The routine reads no account/content
data and changes no pair/epoch/genesis state. Deployment must separately verify
the definer's continued metadata rights and the runtime's exact EXECUTE grant.
No production privilege/configuration change occurred.

The [migration runner](../../bin/migrate.php) binds the routine to its own schema.
It preserves an exact partially created definition and its grants; a different
definition, definer, parameter list or security/access characteristic stops the
migration instead of overwriting an existing object. Fresh, 008 upgrade, repeat,
exact partial retry and conflicting-object refusal are exercised with synthetic
users/text/budgets/cutoffs/blocks preserved. Migration 009 never activates writers
or certifies old accounts.

The [synthetic executor](../../php/synthetic_legacy_import.php) now reports a fixed
privilege-denial outcome after rollback. Internal attempt/error metadata cannot
opt a normal import into test controls: an authorized ordinary synthetic import
works with test controls disabled, while explicit hook selectors are refused.
Its CLI-only/default-deny destination guards, supported synthetic v1 grammar,
offline planner, immutable expiry, non-enabling write set and receipt semantics
are unchanged. No real-data importer or legacy activation adapter is added.

## Mixed concurrency and genuine engine failures

[integration_l02e_races.php](../../tests/integration_l02e_races.php) uses separate
HTTP, importer and fixture/migration/monitor SQL identities. Temporary web grants
cover the exercised application tables, with SELECT-only provenance metadata,
one EXECUTE capability and no DDL/TRIGGER/import-table access. Importer grants
remain L02d's confined SELECT/locking, four INSERT targets and column UPDATEs.
The cleanup enqueue statement additionally needs only its four ON DUPLICATE KEY
UPDATE columns; no cleanup-worker/schema privilege is granted to the web role.
This synthetic grant inventory is not approval of production identities or roles.

Each profile executes **17 mixed races**. Eight route families run both schedules:
request cancellation; a new native request in another pair sharing the sender's
global allowance; ordinary block; report attachment; versioned reviewer clearance;
unmatch; explicit chat ending; and refused new requests for incomplete legacy
history. A separate account-deletion race exercises non-cooperating row locks.
The other native pair has actual post-epoch synthetic genesis; the imported
original pair always stays `legacy_incomplete`.

The all-queued schedule records three distinct PIDs, three actual SQL connection
IDs and all three pending on the exact shared named lock in performance_schema.
The HTTP-first schedule proves the importer rechecks the HTTP-committed state,
including changes without a pair-version bump. The deletion case holds importer
rows after fact insertion, observes actual deletion SQL waiting on those rows,
then proves access/session denial survives commit and receipt replay. No staged
fact is visible to the independent reader before commit. Existing approved chat
survives unmatch; report clearance preserves the personal block; retries do not
duplicate native request events, reports, probation or import effects.

[Guarded barriers](../../php/http_test_barrier.php) require explicit disposable
settings, test flags and a matching connection/database. The shared guard retains
its explicit remote-test opt-in for existing Docker-to-host suites; the importer
controls separately remain loopback-only. This task executes loopback profiles. No header leaves ordinary traffic untouched;
unsafe settings are rejected before connection/file effects in the PDO-disabled
unit check. Barrier/retry records publish by atomic rename so concurrent readers
see complete JSON. A failed Linux pilot exposed that harness race; it receives no
PASS credit. Both final profiles and affected regressions passed after the fix.
The separate [CLI hook](../../tests/support/l02e_import_control.php)
also needs its permit and environment authorization. It accepts no SQL or path
from report/history input. Production paths receive no new active test behavior.

Each profile also executes four engine trials:

| Trial | Observed engine behavior / required outcome |
|---|---|
| deadlock_once | Real opposing pair/marker row locks produce **40001 / 1213**; the engine status identifies both SQL connections. Entire import rolls back, revalidates and commits once on attempt two. |
| deadlock_exhaust | Three real 1213 victims, bounded exhaustion and unchanged contracted pair/fact/key/receipt state; a later safe retry commits once. |
| row_timeout_once | An actual held row produces **HY000 / 1205**, distinct from deadlock and named-lock timeout; full rollback and one successful retry. |
| row_timeout_exhaust | Three actual row-lock timeouts, atomic exhaustion and subsequent safe retry. |

To choose the importer as the deadlock victim reproducibly, the synthetic opposing
transaction updates 1,000 unrelated fixture weight rows before requesting the
importer's marker. These are real InnoDB cycles through the actual importer
transaction/retry path, not simulated PDO exceptions or production-frequency/load
evidence. The competing fixture writes are outside the contracted import unit.
The observed transaction isolation is SERIALIZABLE on every trial. Forced-error
L02d checks, named-lock waits and retained old engine-status text are not credited
as additional genuine deadlocks. No import result is a finding or automatic ban.

## Exact execution and compatibility limits

[Machine-readable evidence](evidence/l02e-2026-10-17.json) records each assertion,
race participant/wait edge, actual error, 50 matching source hashes and remaining case scope.
Local tests ran on the working tree based on 734cc34. All data/accounts are
synthetic, mail is fake and guarded targets are disposable. Assertion counts can
differ with the safe all-queued ordering; both profiles execute the same 17 races
and four engine trials.

| Profile / declared relationship | Exact executed result |
|---|---|
| Windows PHP **8.5.11 NTS**, mysqlnd 8.5.11, MySQL **9.6.0**, 127.0.0.1:3342 | **182 assertions PASS**; 2026-10-17T06:17:02Z–2026-10-17T06:17:55Z; Africa/Harare 2026-10-17T08:17:02+02:00–2026-10-17T08:17:55+02:00. Installed local engine; not a production-major upgrade approval. |
| Linux PHP **8.4.26 NTS**, mysqlnd 8.4.26, MySQL **8.0.45**, container loopback | **182 assertions PASS**; 2026-10-17T06:17:03Z–2026-10-17T06:18:16Z; Africa/Harare 2026-10-17T08:17:03+02:00–2026-10-17T08:18:16+02:00. Matches CI’s PHP/MySQL lines locally, not a remote CI run or the ubuntu-latest runner itself. |
| Affected pair/report regressions | Windows **396/245 PASS**, 11/8 separate HTTP overlaps; Linux **396/245 PASS**, 11/8 overlaps. Exact separate times, databases, worker IDs and hashes are in the artifact. These older harnesses use their fixture identity; restricted-role proof comes from L02e. |
| Import regression | **100 PASS** on the final executor; its five distinct CLI overlaps remain separate from mixed HTTP evidence. |
| Pure/static checks | **8** guarded-barrier and **34** import-guard assertions PASS with PDO disabled; unchanged offline suite **105 PASS**; policy_safeguards_p0_flows, integration_core_p0_flows and unit_disposable_database_guard PASS. PHP lint and documentation/source-attribution checks recorded separately. |

Both engines default to REPEATABLE-READ, use lock-wait 50s and rollback-on-timeout
0; detection is enabled. Import transactions actually use SERIALIZABLE, and the
timeout probe alone uses a 1s session wait. SQL sessions use +00:00; the Windows
system reports South Africa Standard Time and Linux UTC. Both test databases use
utf8mb4_unicode_ci; server collation is utf8mb4_0900_ai_ci. lower_case_table_names
is 1 on Windows and 0 on Linux. All six used extensions are present. Local TLS
cipher is empty: these tests do not prove production TLS/network settings.

Removing each required importer capability fails atomically; restoring only its
original baseline grant succeeds. Both profiles deny account/certification/
epoch/import-table/DDL misuse. The trigger-DDL refusal is 1142 on the native
engine and 1419 under MySQL 8.0's enabled binary logging; the latter is a
privilege refusal, not a deadlock or permission to enable trust/superuser flags.
The artifact retains exact errors. The only added web enqueue grant covers four columns already required by its
existing fixed ON DUPLICATE KEY statement. No DDL/TRIGGER/SUPER or importer
write scope was added to make tests pass.

Composer declares PHP ^8.2; CI declares PHP 8.4/MySQL 8.0 and the release workflow
PHP 8.5. Untested PHP minors, actual managed-service settings, production/staging
versions, deployment roles and trusted TLS remain unverified. Docker images and
their observed digests are recorded; a floating CI tag is not exact runtime proof.
No remote run for this branch is claimed. Historical candidate CI remains head
`e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push `38546846225`, PR `38546851035`.
Historical L02a/L02c/L02d evidence and A01–A45 **24/19/2** statuses are unchanged.
An importer deadlock does not execute the report-service A24 deadlock variant.

## Case updates and remaining requirements

The separate LB01–LB33 matrix is now **21 PASS, 12 PARTIAL, 0 wholly NOT RUN**.
PARTIAL cases retain explicitly unexecuted variants; this is not full acceptance.

| Case | Current executed scope / remainder |
|---|---|
| LB11/LB12/LB28 | Genuine rollback/retry/exhaustion, duplicate-free receipts/facts and separate worker/connection proof supplement retained L02d evidence; PASS. |
| LB13 | New native send/global-window and target-ending HTTP writers, stale-plan refusal and both orderings; PASS. The legacy target itself cannot create a new request. |
| LB14 | Real block/report/clearance races, including unchanged pair version; PARTIAL for cleanup races assigned to L03. |
| LB15 | Actual deletion SQL overlap, advanced sessions and subsequent denial; PARTIAL for ban/consent writer workflows not executed here. |
| LB16/LB17 | Current authorization, unmatch/chat, clearance/block and duplicate report/probation behavior verified. Unsupported historical consent/review action proofs still remain INCOMPLETE, not newly supported or approved. |
| LB21/LB31 | Unsafe hooks/default-deny checks and least-privilege live health/no activation; PASS within the supported synthetic profile. |
| LB29 | All specified mixed HTTP families, genuine 1213 cycles and separately observed 1205/named-lock behavior; PASS for the executed synthetic scope. |
| LB32 | A receipt after actual deletion returns metadata only and restores no account/session/access; independent recovery remains NOT RUN, so PARTIAL. |
| Other LB cases | Retain their individually attributed L02c/L02d results and remainders; see the complete contract/artifact matrix. No A-case is relabelled. |

| Remaining class | Concrete requirement / owning backlog |
|---|---|
| Implementation defects | No known remaining defect in the bounded supported L02 synthetic path. Existing provisional/incomplete physical cleanup, staging/receipt expiry/compaction and independent replay remain **L03 implementation work**, including reconciliation with approved clocks. Unsupported real-source proof families and the conditional SQL planner adapter are intentionally absent, not hidden activation paths. |
| Executable tests | L03 media/object purge, full cleanup/hold/deletion/restore replay and its failure tests; additional runtime/service combinations if supplied. No further L02 phase is invented to replace these independent tasks. |
| External evidence | Authentic original-account/history custody, effective historical policy, observed all-writer B/W/H/C/cutover and continuous coverage; actual production versions/roles/definer/TLS; qualified privacy/security/safety review, retention-purpose approval, named operators/MFA/coverage and independently witnessed restore proof. Synthetic consistency/hashes do not establish these. |

**Next independent main-backlog task: L03 cleanup and independent recovery replay.**
Begin with a bounded inventory of writers/categories, clock precedence and replay
ownership; implement/test already approved categories without silently adopting
proposed retention values. No settled policy question needs reapproval for that
engineering inventory. Production migration 005/007/008/009 application remains
UNVERIFIED and an actual isolated restore rehearsal remains NOT RUN. No real data
import, legacy activation, production access, real email, push, merge or deployment.

## Checkpoint validation and resource closure

Final-source hashes match all retained runs. Eleven changed PHP files pass lint;
PDO-disabled guards create no connection or rejected-gate files. Documentation
links, case totals, time displays and unchanged approvals are checked separately;
these checks are not functional test or independent review evidence. Exact
validation counts are recorded in the JSON artifact.

Only owned synthetic MySQL instances were stopped after identity checks. The
final native PID 29860/launcher 27908 and port 3342, plus synthetic HTTP
listeners, were observed closed at 2026-10-17T06:23:04.5171843Z /
2026-10-17T08:23:04+02:00 Africa/Harare. The labelled MySQL 8 container
stopped at 2026-10-17T06:21:36.4448487Z / 2026-10-17T08:21:36+02:00
and auto-removed. Both fixture cycles are recorded in the artifact. Ignored
native fixture files remain local; server shutdown is not physical erasure.
