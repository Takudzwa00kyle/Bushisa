# Restricted routes, worker pairing and local recovery

Continuation from `867585d` on `codex/direct-production-release-prep-20261020`.
This is local engineering evidence for the existing-resource direct-release
approach, not permission to deploy. Captures 01–09 and their previous
reconciliation are reused unchanged. PR [8](https://github.com/Takudzwa00kyle/Bushisa/pull/8)
remains draft. Hosted results for this candidate are recorded separately below;
historical green CI is not transferred to these changes.

## Implemented permission repair

Actual HTTP testing reproduced MySQL 1142 when a runtime with SELECT/INSERT,
but no UPDATE on immutable receipts, reached an exclusive receipt read.
`bushisa_pair_operation` now uses a shared locking read; the exclusive pair-row
lock, report-service mutex, transaction, version check and unique receipt key
remain. Report receipt, incident, submission, probation-history and assignment
reads similarly use shared locks. The reported actor's exclusive lock still
serializes probation activation. Intake registration reads the case through a
shared lock; qualified final-resolution/hold/purge write paths retain their
own authority checks and exclusive locks. No retention clock changed.

The review profile in [release_roles.php](../../tests/support/release_roles.php)
grants individual stores used by implemented web writers. Account INSERT is
column-scoped; account UPDATE excludes role and creation time. Epoch/genesis
are read-only. Immutable receipts/probation periods are SELECT/INSERT only;
the case-clock exception permits UPDATE of `unresolved_code`, not resolution
timestamps. Cleanup enqueue permits only its existing columns. Runtime gets
no schema DDL, global administration, GRANT OPTION, arbitrary procedure,
import execution or physical mail-row deletion. Worker grants remain separate
and do not include password, suspension, identity/generation edits or private
chat/evidence reads. These grants do not provide row-level authorization: the
real routes must still authenticate and check each actor/target/transition.
They are fixture GRANT statements, not a live grant installer or final qualified
least-privilege approval.

## D03 coverage and boundaries

The [guarded HTTP/recovery suite](../../tests/integration_release_routes_recovery.php)
separates fixture/migration administration from application execution. Actual
loopback PHP routes run as the proposed restricted runtime, not root.

| D03 journey | Executed route group / remaining boundary |
|---|---|
| Signup and email approval | `register.php`, `verify_email.php`, private CLI decryption of synthetic proof, pending-state assertions. No proof endpoint or real inbox. |
| Adult onboarding / authentication | Real login and `onboarding.php`; pending email approval never grants adult access. Active legacy login preserves unknown generation/anchors. |
| Profile and preferences | `profile.php` text/preferences POST and persisted-value check; missing UPDATE denial leaves data unchanged. Physical image upload/storage permissions are NOT RUN in this suite. |
| Questionnaire / discovery | Rendered discovery, questionnaire GET/replacement POST, reciprocal discovery API, swipe/mutual match. Geographic location remains a required D03 feature with its existing unimplemented/review gates; this is not geographic acceptance. |
| Relationship and approved chat | Request, approval, cancellation, decline, send/read, unauthorized-reader refusal, unmatch preserving chat, mutual restoration, genuinely recreated mutual match retaining budget, block, creator removal after versioned reviewer clearance, explicit ending and closed/unmatched restart refusal. Legacy missing history refuses new requests. Existing L02 regressions retain exact cooldown/lifetime-boundary coverage. |
| Confessions | Rendered/feed reads, create, reference and related notifications. Attachments and the full approved deletion/retention pipeline are not established by text-route permissions. |
| Reporting/moderation | After-block proof-based report and same-key retry without renewed probation, receipts restricted to their reporter, unassigned reviewer refusal, assigned synthetic-MFA review/clearance, and preservation of the personal block until creator action. Synthetic server-session assertions are not operational MFA, qualified review or staffing. Legacy admin pages are read; its physical confession-delete POST is denied and preserves the row. Safe moderated erasure and policy-complete bans/appeals remain separate engineering gates; DELETE was not granted to make that legacy path pass. |
| Account and reset | Real deletion with password confirmation; reset off by default, then explicitly enabled only for guarded synthetic route tests, purpose-separated fake delivery, manual-key consumption and old-session API refusal. No production throttle value is adopted. |
| Notifications | Read, mark-read and preference writes through real API routes. |

Any unimplemented feature has no verified runtime/store permission contract.
In particular geographic consent/catalogue/ranking, private chat/report media,
production session-copy handler/storage, general account retention/cleanup,
independent recovery publication and policy-complete moderation need their
existing bounded work. Do not grant schema-wide DML to obscure these gaps.
HR04 advances for the enumerated current routes but stays **PARTIAL for full
D03/release acceptance**. Approved product scope is unchanged.

## Local recovery execution

The test reconstructs accepted owner-observed 001–004 metadata, upgrades through
016 under a separate migrator, reruns migrations, and creates synthetic data.
It dumps that candidate schema/data using a logical backup client, restores
into a second guarded database on the same owned local MySQL server, and checks
migration/trigger preservation and fixed health-routine execution. This is a
candidate logical-backup rehearsal, not a production backup or an Azure PITR
exercise. Definer grants are not carried by the dump: the controlled fixture
migrator is explicitly authorized for the restore destination before replay.
No immutable trigger is disabled to accept a contradictory transition.

Minimal synthetic instructions are held outside the dump by this runner. Two
transactional replay passes reapply deletion access denial, an ordinary block,
consent withdrawal and session-version revocation. Old application cookies are
then exercised through the real protected API against the restored database.
Deleted/revoked/withdrawn sessions are denied; blocked contact is denied even
after a new login; consent state and lifetime pair/ending state are checked.
These fixture instructions are **not a general production replay adapter,
authentic all-writer journal, independently durable record, complete physical
erasure, or witnessed acceptance**. Category-specific erasure/hold/recovery
obligations and production consent enforcement remain governed by their current
contracts. Reads remain closed during the restore/replay phase.

Restored HTTP acceptance uses fake mail configuration, reset issuance disabled
and no scheduled workers. An explicit restored dispatcher attempt is refused
with network carrier functions disabled; queue-state aggregates remain
unchanged, including a queued synthetic verification job. Prior synthetic
accepted rows are compared, not misreported as zero historical delivery.

The local engine is upstream MySQL 8.0.46, not `8.0.46-azure`; PHP CLI is 8.5.11.
The recorded profile includes verified TLS, UTC, REPEATABLE-READ, the accepted
strict SQL mode, binary logging and server collation. The local backup-client
version, measured upgrade/dump/restore/replay times and dump byte size are in
the machine report. They are observations, not an Azure RTO/RPO or capacity
guarantee. Laptop failure, cloud IAM/definer differences, private-network access,
media/config recovery, independent durability and production storage fencing
are outside this execution. SC26 production topology/storage stays PARTIAL.


Local final results: **131 route/worker/recovery assertions PASS**, **58 role/guard assertions PASS**,
and affected regressions **l01b 245/245, l02a 396/396, l03a 122/122, q11 74/74, erasure 58/58, session 105/105, l06 25/25**. WC01, DR17.1–4 and MW01–12 passed separately.
Q22's earlier 75 assertions are reused because its implementation was unchanged;
they are not counted as newly executed here. Full cases, process/connection
overlap evidence, UTC execution times and source fingerprints are in the
[machine report](evidence/release-routes-recovery-local-2026-10-26.json).

Measured final local upgrade: 9.599 s; dump: 0.757 s / 169373 bytes;
restore: 9.893 s; two replay passes: 0.026 s.
The dump was verified against every table's bounded synthetic row digest before
replay. Event clocks come from fixture-event creation on the server and survive
repeat replay; restore/read time does not renew them.
The backup client was `mysqldump  Ver 10.19 Distrib 10.11.19-MariaDB, for debian-linux-gnu (x86_64)`; this is not an Azure build claim.

L03a initially failed on native prepared CREATE USER (1064). Its guarded
fixture now quotes the generated password using PDO, retains string-fetch
semantics and honors verified TLS without reverting to emulated SQL. The final
122 lifecycle assertions passed. This fixture repair does not change a live role.

## Worker/package pairing and deployment order

The candidate LF wrapper is materialized only in the owned fixture directory,
with `/home/site/wwwroot` replaced by the checkout path. Its PHP calls execute
the actual candidate cleanup/dispatch CLIs under `MAIL_WORKER_DB_USER` /
`MAIL_WORKER_DB_PASSWORD`, with socket functions disabled and an already drained
synthetic queue. The report records canonical wrapper and materialized hashes,
exit status and code-free counters. Its runtime password is intentionally invalid,
so successful CLI database queries require the dedicated worker binding rather
than silently succeeding as the web runtime. Nonempty worker claim, cleanup,
expiry/cancellation, reset and notice behavior is separately exercised by the
RP suite's injected synthetic carriers. Empty-wrapper execution alone does
not prove nonempty SMTP delivery. Real delivery acceptance remains NOT RUN.

The installed old wrapper still exits at candidate broad-cleanup guard 78 and
never reaches dispatch (WC01). Stop/drain the actual old WebJob before changing
either package. While ingress/all writers remain closed, preserve actual
artifacts/configuration, migrate with the controlled principal, install the
reviewed candidate application **and separately installed LF worker**, verify
their identities and dedicated credentials/TLS, then consider bounded mail
resumption only after the remaining release gates. Neither mixed old/new pairing
is an accepted fallback. Broad consumers, test/fake staging controls and reset
activation remain off in production. No live worker was executed here.

## Commands, results and current candidate

Local commands use the existing owned containers, inspected labels/network,
disposable-prefix checks and a server UUID verified before fixture writes:

```powershell
powershell -NoProfile -File .tmp/run-release-roles.ps1 -Suite tests/integration_release_roles.php
powershell -NoProfile -File .tmp/run-release-roles.ps1 -Suite tests/integration_release_routes_recovery.php
powershell -NoProfile -File .tmp/run-release-roles.ps1 -Suite .tmp/run-release-route-regressions.php
powershell -NoProfile -File .tmp/run-release-affected.ps1
docker exec bushisa-observed-php85-20261020 php tests/unit_legacy_worker_compatibility.php
docker exec bushisa-observed-php85-20261020 php tests/unit_mail_worker_schedule.php
docker exec bushisa-observed-php85-20261020 php -n tests/unit_mail_worker_config.php
```

The `.tmp` commands are private local orchestration, not deployment commands.
CI invokes tracked suites directly with explicit disposable configuration.
Local evidence is recorded with LF source fingerprints, exact case/variant
results and environment in the linked machine report. Existing A/LB/LC/SC
matrices retain their original attribution; this run is additional regression
evidence, not a rewrite of their totals. Native Windows syntax checks are
separate from Linux DB/HTTP execution. Azure execution and all 18 managed-stage
cases remain **DEFERRED / NOT RUN**.

Initial unsuccessful runs are not PASS evidence: expired synthetic TLS was
renewed without disabling verification; harness page names and onboarding login
were corrected; the incomplete synthetic-origin guard was not weakened (the
trusted configured link builder is used without navigating its domain);
Windows CRLF shell materialization was corrected to explicit LF packaging.
A failed fixture replay left a transaction blocking its own cleanup; owned
transaction rollback, bounded metadata-lock waits and an exclusive fixture
lock now prevent that self-wait. No production conclusion follows from these
fixture corrections.

## Release gates and exact owner input

Hosted implementation candidate `319c2a6655047fb943f404db130582be7391b136`
initially failed the RP-G.production_name no-PDO guard probe in
[push CI](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/40399529135)
and [PR CI](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/40399533718).
The hosted PHP build separates ctype into a shared extension; `php -n` omitted
that prerequisite before the database-name guard. The fixture now loads only
shared ctype for these no-PDO probes, without weakening any target guard.
Hosted role evidence reports **PHP 8.5.12**, despite the workflow's requested
8.5.11. Local execution above remains exactly 8.5.11; neither is a new Azure
runtime observation. These failed initial runs are retained as failed evidence.

Follow-up `cfc3e2f709acddf2f5ebf32dd1e1daeee2e2c6ec` confirmed ctype as
`shared-extension-required`; 58 role assertions and 131 route/recovery assertions
passed on hosted PHP 8.5.12 / MySQL 8.0.46. The lifecycle test step also succeeded.
Its [push run](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/40402016226)
and [PR run](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/40402025914)
still failed overall because the upload pattern named `results.json` while the
suite writes `evidence.json`. That artifact-only path is corrected; these runs
are not recorded as green. Hosted MySQL service reports secure transport OFF;
it does not replace the verified-TLS local profile or Azure TLS acceptance.

**Recommendation remains NO-GO.** Engineering can continue without appointments:
legacy pending-attempt continuation and authentic all-writer cutover/adapters;
ingress/write fencing; remaining D03 implementations and permission tests;
production replay/storage/cleanup interfaces; compatible forward repair and
backup/artifact coverage. Old baseline code on the upgraded schema remains an
unsafe traffic-serving rollback (OS17). Keep ingress, lazy GET mutations,
signup/onboarding/reset, admin/support/direct SQL, mail/cleanup/imports and
retired writers stopped until accepted repair/restore, protective replay,
role/definer checks and authorization smoke evidence permit reopening.

| Still required | Type / authority |
|---|---|
| Explicit production reset throttle values before separate activation | Owner policy decision; keep reset off meanwhile. Q40 redesign remains proposed, not a prerequisite to silently invent session settings. |
| Existing-resource recovery method, verified headroom, permitted downtime/abort window and exact change set | Owner/operator operational choice after capacity evidence; no new paid environment or automatic spending. |
| Named primary/backup, privacy/security/safety/independent appeal reviewers and accepted coverage; monitored support/privacy contact | Owner-supplied appointments plus real qualified sign-off/operational evidence. Names remain PENDING. |
| Actual restricted principals, effective web sessions/TLS, runtime/worker artifacts, delivery acceptance, maintenance fence and Azure recovery/storage behavior | Technical/operator evidence and separate authorization to make live changes. Local passing fixtures cannot close these. |

DR-P01 remains PARTIAL for actual service acceptance; DR-P02 actual previous
artifact compatibility, DR-P03 witnessed production recovery and DR-P04 live
smoke/delivery remain NOT RUN. No settled policy decision is reopened.

Next smallest owner action: collect **only capacity metadata** to decide whether
same-server logical recovery fits the existing service. In the existing MySQL
prompt, run the following read-only aggregate and save it privately with UTC
capture time; pair it with the Portal's current used/free storage metric for
the existing server. Do not export rows or create another database.

```sql
SELECT UTC_TIMESTAMP(6) AS captured_utc,
       ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)/1048576,2) AS allocated_mib,
       ROUND(MAX(DATA_LENGTH + INDEX_LENGTH)/1048576,2) AS largest_table_mib
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE();
```

This does not repeat schema captures 01–09. Temporary/index/binlog growth,
media/config/artifacts and protected journal capacity still need margins;
compressed dump size alone does not establish sufficient space or recovery
acceptance. No live change is requested by this collection step.
