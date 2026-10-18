# Q11 ordinary attempt-data erasure consumer

Continued from `455f1fc78c03f86b72bf076f786267337904a7ac` on
`codex/q11-attempt-erasure-20261018`, with a clean starting index/tree and no
AGENTS.md in the checkout or ancestors. This is a bounded consumer for newly
recorded Q11 attempts, not general account deletion, a legacy backfill or an
operational release. No production scheduling, data, provider or configuration
was accessed. Named reviewers/staffing and independent recovery gates remain open.

Authority is the [decision register](decisions.md), [48-answer reconciliation](owner-policy-reconciliation-2026-10-15.md),
[retention policy](../data_retention_policy.md), [retention matrix](data-inventory-retention-matrix.md),
[L03 inventory](l03-category-clock-writer-inventory.md),
[Q11 implementation](q11-signup-expiry-evidence-2026-10-18.md) and
[L03a recovery interfaces](l03a-text-evidence-lifecycle-evidence-2026-10-17.md).
Q11 approves expiry +24h live cleanup after creation +72h or first approval +7d.
Q20/Q21/Q24–Q26 protect scoped evidence, consent and recovery instructions.
Q12, Q16, Q23 and Q26 proposals are unchanged. No new policy duration, minimum
account allowlist approval or general deletion rule follows from this work.
Takudzwa Hondova remains product reviewer. His exact historical acceptance
instant remains unavailable; execution/capture timestamps are not approvals.

## Permitted scope and retained records

The verified scope is **`erase_attempt_v1`**, bound to one immutable registration
generation, original expiry receipt and expiry account/clock versions. The
deadline is the original Q11 expiry +24h. Late discovery, claims, retries,
storage failures and protected-file review never extend it. Completion verifies
the stores below; it does not claim physical erasure from every provider/copy.

| Category / approved purpose | Permitted operation | Preserved / excluded |
|---|---|---|
| New never-completed attempt identity/credentials, Q11 | Replace `users.nust_student_id` and `email` with unique non-deliverable opaque placeholders; erase `password_hash` | Never DELETE `users`. Keep only ID, random attempt key, original creation/denial/status/version/role/restriction fields needed to bind retained safety/consent/recovery records. It remains inactive/deleted/unverified; no credentials or original contact are retained in the placeholders. This protected tombstone is potentially personal, not anonymous, and awaits purpose/copy-bound review. |
| Ordinary profile, Q11 | Delete that account's `profiles` row: name/handle/bio/gender/DOB/faculty/year/photo pointer | Separately preserved report/evidence copies are untouched. Unknown profile/user columns block the job pending schema review. This does not approve new collection. |
| Ordinary preferences, questionnaire and notification settings, Q11 | Delete its `preferences`, `find_match_answers` and `notification_preferences` rows, including current JSON/answer values | Maximum 200 rows per category in this contract; excess requires review. Do not apply proposed general metadata expiry to other tables. |
| Profile-media index, Q11/Q24 | Capture up to four distinct safe owned locators, then delete its `profile_images` rows | Manifest commits before removing pointers. No recursive directory deletion, filename guessing, orphan sweep, URL/geocoder access or Azure object assumptions. |
| Challenge/outbox contact copies, Q11/Q22 | In chunks of at most 100, replace code-row email and linked outbox recipient; fixed template subject; remove outbox metadata JSON | Select only by bound account -> code IDs -> outbox IDs, never email. Preserve code-free challenge/outcome/count/time and job receipt/claim state. Q11 has already erased hashes/bodies and denied access. Residual challenge detail <=24h and mail metadata +7d need their separate original-terminal-clock consumer. No Q23 timer is invented. |
| Manifested media, Q11/Q24/Q26 | Version-checked deletion through the isolated synthetic storage adapter; acknowledge only verified absence | A report `evidence_path`, another current profile reference, unsafe locator, link, changed file generation or unavailable provider prevents completion. Actual storage/private-access/index completeness is UNVERIFIED. No production storage adapter is supplied. |
| Safety, holds, consent, pair budgets and recovery, Q18–Q21/Q26 | Preserve all existing rows and clocks | `reports`, cases/evidence/holds, consent receipts, blocks/clearance, pair ledgers/counts, audits and earlier recovery records are not erased or reset. An evidence hold does not preserve ordinary credentials/profile data. File references covered by evidence stay incomplete pending their separate authorized disposition. |
| Other stores | No mutation | Existing swipes/matches/messages/confessions/notifications on an allegedly never-completed account block it as unexpected feature data. Logs/rate counters, unlinked mail/legacy cleanup payloads, replaced/orphan files, PHP session files, provider copies and unspecified stores are outside this receipt; unresolved purposes/retention/copy inventory remain explicit gates. |

The current schema's `users` deletion would cascade into profile/preferences,
swipes, matches/messages, reports and consent; confession/audit FKs have other
side effects. The consumer avoids that operation entirely. It changes no key or
FK value, and directly deletes only the five ordinary child tables above.
Current PHP sessions can contain an email copy. Version revocation denies use
but is not physical erasure of that file. There is no reliable account/generation
to session-object inventory/provider here, so no global session-directory sweep
or claim of erased session copies is made. Completing that mapping is an
engineering/operational prerequisite to claiming Q11 erasure across all live
copies; this receipt explicitly attests only `erase_attempt_v1` stores.

## Implementation and transaction boundaries

[Migration 012](../../database/migrations/012_signup_attempt_erasure.sql) adds
five tables and a nullable `users.signup_attempt_key`; existing rows remain
unbound. [The controlled migration helper](../../php/attempt_erasure_migration.php)
installs an immutable-key trigger and schema-bound, read-only definer procedure.
It adds indexes for account/code address paging and evidence-path lookup; their
actual production build cost/locking impact still needs a staged release review.
The restricted worker receives EXECUTE, not TRIGGER/DDL privileges. The procedure
detects unknown ordinary columns, incoming child-table FKs and unreviewed
UPDATE/DELETE triggers, including a missing/changed generation guard. Missing
privilege or a failed health check cannot authorize erasure. Partial DDL retries
validate existing definitions without replacing them or backfilling data.

[Q11 registration/expiry](../../php/signup_lifecycle.php) binds a cryptographic
random generation only inside the actual new-registration transaction, then
queues its erasure job inside the expiry transaction. Migration/reads/resends
never manufacture a generation. Older Q11 obligations are discoverable but
blocked `GENERATION_PROOF_REQUIRED`. No earlier approval needs reopening.

[Consumer](../../php/signup_attempt_erasure.php) and
[CLI](../../bin/erase_signup_attempts.php) are separate from the existing cleanup
CLI; no scheduler is enabled. The CLI requires an explicit server-side enable
flag before connecting. It accepts bounded work/status parameters, not SQL,
client clocks, roles, holds or authorization assertions.

The durable stages are `core -> codes -> mail -> objects -> verify -> complete`.
A batch selects at most 25 jobs, each at most 25 steps; defaults are 10/20.
Claims use random hashed tokens and a 60s technical lease. Every step locks the
account first, then clocks, original obligation, identity and job. It rechecks
ID/generation/creation, exact account and clock versions, original receipt hash,
expiry/deadline, deleted/inactive state and absence of completed onboarding.
Neither email reuse nor numeric-ID reuse retargets a job. Stale leases cannot
mutate, and a new version after database progress also blocks external deletion.

Before ordinary content changes, the same SQL transaction writes a minimal
recovery intent and media manifest. A rollback loses all those transaction
effects together. Core pointers can then disappear while the manifest remains
durable. Address stages persist their cursor/counts atomically. The database
receipt appears only after all contracted database fields are checked; the
final receipt requires verified object absence as well. Duplicate workers and
retries cannot duplicate receipts, renew deadlines or change a completed result.
Interrupted clients can query `--status=<receipt>` for the committed outcome.

The recovery provider is deliberately unavailable outside the fully guarded
synthetic integration. Every destructive stage, including resumed stages,
fails closed with `RECOVERY_PROVIDER_REQUIRED`. An enable flag, SQL receipt or
local file is not an independent acknowledgment. The read-only recovery adapter
returns minimal action/version/reference/expiry/verified-purge instructions;
publication stays `unpublished`, copy inventory `unverified`, `retain_until=NULL`.
It never silently asserts a 35d backup setting, universal 49d timer or restore
clearance. Independent durability, replay before reads/mail and a witnessed
restore remain separate requirements.

The conditional Q11 example is **1d live allowance + proposed 35d copies +7d
margin =43d from expiry**, only if that allowance is met and those copy bounds
are actually selected/verified. General Q14's different example remains
**7+35+7=49d from deletion**. Actual later purge, surviving copies or longer
protective state require a computed later bound; neither number is hard-coded.

The only implemented storage provider is rooted in a dedicated `qe-storage-*`
directory within the guarded test workspace. It accepts exact owned
`uploads/<account>/photo_*` locators, rejects links/traversal/oversized objects,
and uses file identity/stat plus content digest as its fixture generation.
It persists `deleting` intent before unlink, then separately verifies absence.
SQL rollback cannot restore an unlinked file: a crashed worker reconciles the
persisted intent and absence on retry. A changed object is preserved. Private
immutable namespaces and conditional-delete semantics for a real provider still
need implementation/review; the fixture is not proof of those capabilities.

Failures surface fixed reason codes, original deadlines, bounded counts and
durable stage state; no personal content/credential/code is logged. Recoverable
storage errors wait 30s technically; unresolved authority/schema/generation/scope
conditions block. An overdue SQL alert uses the original deadline. Named alert
recipients, actual coverage/escalation and measured service capacity are open.

## Acceptance cases and evidence

The [machine-readable evidence](evidence/q11-attempt-erasure-2026-10-18.json)
records per-profile commands/times, assertions, PIDs/SQL connections and observed
locks, variants, migration results, grants and matching source hashes.
UTC and Africa/Harare (UTC+02:00) displays identify execution only.
The [suite](../../tests/integration_attempt_erasure.php),
[fixture](../../tests/support/attempt_erasure_fixture.php) and
[worker](../../tests/support/attempt_erasure_worker.php) use synthetic accounts,
fake mail and isolated storage only. Fixture privilege is separate from worker
privilege. No real user export, production database or real email was used.

| ID | Executed contract variant | Current result |
|---|---|---|
| QE01 | Default-deny CLI before connection | PASS |
| QE02 | Fresh/011 upgrade/repeat; partial DDL retry and exact-definition validation | PASS |
| QE03 | Pending-email fields erased; only protected account identity/status remains | PASS |
| QE04 | Approved-email unfinished onboarding data erased under its original clock | PASS |
| QE05 | Reports/evidence/holds, consent, blocks, pair count/genesis preserved | PASS |
| QE06 | Missing anchors/generations and old 011 obligations cannot be backfilled into permission | PASS |
| QE07 | Newer account version and altered deadline block erasure | PASS |
| QE08 | Replacement account sharing original email/student ID survives old continuation | PASS |
| QE09 | Reused ID/different generation blocked; stored generation cannot be updated | PASS |
| QE10 | Unknown child cascade blocks before mutation | PASS |
| QE11 | SQL intent/content rollback; retry, overdue alert and original obligation retained | PASS |
| QE12 | Bounded address chunks, durable cursor and idempotent resumption | PASS |
| QE13 | Separate workers/SQL connections actually overlap; one completion | PASS |
| QE14 | Expired lease and stale token cannot mutate a newer claim | PASS |
| QE15 | Client crash before core commit rolls back; lease recovery succeeds | PASS |
| QE16 | Client crash after core commit reconciles persisted progress | PASS |
| QE17 | Manifested fixture files erased; unavailable operational adapter leaves work incomplete | PASS |
| QE18 | One file erased, later storage failure; no false completion, safe retry | PASS |
| QE19 | Crash after unlink/before SQL acknowledgment reconciles intent and absence | PASS |
| QE20 | Replaced object generation stays untouched | PASS |
| QE21 | Unsafe locator blocked; hardlink target protected; Linux symlink target protected | PARTIAL |
| QE22 | Evidence file stays protected; synthetic reference release retains original deadline | PASS |
| QE23 | Separate in-flight fake carrier overlaps address erasure; late acceptance grants no access | PASS |
| QE24 | Actual onboarding HTTP and stale consumer overlap; completed account/profile survive | PASS |
| QE25 | Newer version after database progress prevents resumed external deletion | PASS |
| QE26 | Restricted grants, operational recovery gate and resumed-stage gate | PASS |
| QE27 | Crash after completion commit/before reply; CLI receipt and retry find same result | PASS |
| QE28 | Recovery interface is read-only; no publication/copy bound is invented | PASS |

Aggregate: **27 PASS / 1 PARTIAL / 0 wholly NOT RUN**. QE21's Windows symlink
variant is **NOT RUN** because fixture link creation was unavailable; Windows
hardlinks and Linux symlinks/hardlinks pass. No Windows capability or production
storage safety is inferred from the Linux result. Other cases mean the stated
local variant, not external acceptance.
The final runs pass **57 assertions on Windows and 58 on Linux**, plus Q11's
74 assertions/seven overlaps and all eight affected unit/integration suites on
each profile. Report/pair regressions retain their 245/396 assertion counts.

Three genuine overlaps per profile comprise duplicate erasure workers (QE13),
consumer versus in-flight carrier (QE23), and actual HTTP onboarding versus
stale erasure (QE24). QE13/QE24 show two engine-observed simultaneous row waits
and distinct process/connection IDs. QE23 records a separate carrier process
waiting outside a database transaction while the consumer commits. SQL faults
and process exit 77 are documented separately; they are not deadlock claims.

Runtime profiles remain Windows PHP 8.5.11/MySQL 9.6.0 and Linux PHP 8.4.26/
MySQL 8.0.45 with native PDO, UTC and the captured transaction settings. Local
versions are not production-version approval. Current Q11 and affected
regression commands/results are in the JSON. Existing A **24/19/2**, LB
**21/12/0**, LC **13/5/0** and historical QS evidence are unchanged. Candidate
GitHub CI is **NOT RUN**; the added step has no executed run attribution.

## Remaining requirements and next bounded task

- Operational recovery provider, independent publication/copy inventory,
  startup/mail replay and witnessed restore: OPEN; erasure stays fail-closed.
- Real media inventory, safe private access, immutable object generations,
  conditional deletion, orphan/derivative/provider copies and Windows symlink
  variant: OPEN / unexecuted as listed. No SQL transaction proves external erasure.
- Legacy provenance/generation adapter, general account deletion, underage
  lifecycle and proposed retention categories: outside this scope; no policy
  choices inferred. Logs/audits/unlinked mail and physical PHP session copies
  need their own reviewed mapping/consumer; unrelated sessions must survive.
- Named operations/backup, monitored overdue alerts, actual scheduling/capacity,
  privacy/security/safety and qualified sign-offs: OPEN. These are real-world
  evidence/appointment requirements, not another request to approve settled clocks.

Next independent bounded engineering task: **Q22 original terminal anchors and
residual challenge/code-free mail metadata cleanup** (approved <=24h/7d clocks),
preserving in-flight receipt reconciliation, Q11/QE tombstones and recovery
instructions. It can proceed with existing approvals. Operational provider and
review gates remain separate prerequisites to any production erasure/release.
