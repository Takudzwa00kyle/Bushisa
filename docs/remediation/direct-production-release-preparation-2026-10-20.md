# Controlled direct-production release preparation

Recorded 20 October 2026, Africa/Harare. **Recommendation: NO-GO for deployment
until the evidence gates below are met.** Preparation uses the existing B1 app
and MySQL service plus local disposable tests; it requires no second paid
environment. No production action in this procedure has been executed here.

## Authority, candidate and evidence boundaries

Takudzwa Hondova selected direct updating of existing resources and deferred
managed Azure staging. The [register amendment](decisions.md#release-approach-amendment-existing-resources-20-october-recording)
records its scope, with exact acceptance time unavailable. This is not adoption
of unrelated proposed values, a P0/P1 exit, or permission to deploy this package.
D03 still includes signup/verification, adult onboarding, discovery, reciprocal
and geographic matching, chat, confessions, moderation/safety and account management.

Recovered clean source: `423ec6cca47a67a6e8969f9e73f317f06208ab86`, on
`codex/guarded-staging-origin-carrier-20261020`. The new preparation branch is
`codex/direct-production-release-prep-20261020`. No applicable AGENTS.md exists
in the checkout/ancestors. PR [7](https://github.com/Takudzwa00kyle/Bushisa/pull/7)
was independently rechecked as draft/unmerged at that source, test merge
`1c1d2b997d49cb498beeadc98752838b5f4c836f`; remote main remains
`0bf311fb6d6a298712fd4cd16d0722305516edd4`. Preserve PR 7 and unrelated branches.

Historical source `423ec6c` passed five jobs in each of
[push 39383049280](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39383049280)
and [PR 39383053044](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39383053044).
These are not CI for new changes. `7326716`/`3dd1318` retain their original
four-job [push](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39224866889)
and [PR](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39224874180)
attribution. New release evidence must name source, checkout/merge, jobs and URLs.

AZS01-AZS18 remain **NOT RUN / DEFERRED**. No managed Azure execution, real mail,
SC26 production-storage proof, independent protection or witnessed restore is
claimed. Existing matrices are unchanged. Local simulated carrier/authority
success is not operational acceptance.

## Hosted identity and complete change boundary

The [owner observation ledger](evidence/azure-owner-observations-2026-10-20.json)
is owner-provided Portal/CLI evidence collected 19-20 October, not an independent
agent Azure capture. It establishes Linux B1/one instance/no slots, PHP CLI
8.5.11, MySQL major 8.0/private network, seven-day DB backups and a successful
no-work minute WebJob. Deployment UUID `25ae3df2-bf75-4dd4-a9ed-625a2b759c24`
is not a Git SHA. App/worker source, migration markers, schema drift, patch,
grants/definers, isolation and web-effective sessions remain **UNKNOWN**.

Azure CLI/Az.Accounts and a callable authenticated Azure connector are not
available in this checkout environment. The computer-use runtime was rechecked
and failed before app/window inventory with `failed to write kernel assets:
The system cannot find the path specified. (os error 3)`; no Portal state was
observed. No cloud credentials or production DB
credentials were retrieved; no live SQL connection was attempted. The owner
can collect the following now under the read-only authorization. Do not install
the candidate, invoke migrations, bootstrap a schema or run a worker to identify it.
The [capture availability record](evidence/direct-release-metadata-2026-10-20.json)
separates the independent GitHub observation from unknown production state.

1. Portal **Bushisa > Deployment Center > Logs**, select the active deployment;
   record UTC/offset, source commit if supplied, artifact/build ID, build and
   deploy path. Privately compare the actual deployed code/worker hashes with
   the corresponding release archive. In **Advanced Tools > WebJobs > bushisa-mail**
   identify the deployed `run.sh` and package separately: a copied WebJob can
   remain stale when the web app is updated. Do not run it or disclose logs with
   addresses/tokens. The observed counters match repository `0bf311f`'s worker,
   which is a useful lead, not source proof or evidence against a mixed artifact.
2. In an already authorized app SSH console, these are file metadata only:

   ```bash
   cd /home/site/wwwroot
   php -r 'echo PHP_VERSION," ",PHP_SAPI,PHP_EOL;'
   for p in index.php signin.php config/db.php php/operational.php php/signup_verification.php php/mail_delivery.php bin/migrate.php bin/cleanup_policy.php bin/dispatch_mail.php deploy/mail-webjob/run.sh; do
     if test -f "$p"; then sha256sum -- "$p"; else printf 'MISSING %s\n' "$p"; fi
   done
   ```

   Compare against known commit bytes (LF/CRLF differences must be explained).
   These probes alone do not attest the whole artifact. Obtain its complete
   immutable build/archive digest or a privately reviewed code-file manifest
   before claiming a complete source mapping. Never hash/read session files,
   uploads, `.env`, `config.local.php` or credentials to create that manifest.
3. Use an existing authorized **TLS-verified** metadata connection to
   `bushi-server.mysql.database.azure.com`, selected schema `bushi-database`.
   Run [read-only-metadata.sql](../../deploy/release/read-only-metadata.sql).
   It selects versions/settings, table/column/index/FK metadata, trigger/routine
   hashes and migration markers; no user rows/challenge fields. Run as the
   appropriate existing runtime, worker and migration principals separately to
   establish grants/definer visibility. Do not grant privileges for capture.

   ```bash
   # On the owner's trusted private-network workstation/console, credentials
   # entered through the client's password prompt, never in arguments/output.
   mysql --host=bushi-server.mysql.database.azure.com --port=3306 \
     --user='<existing authorized metadata principal>' --password \
     --ssl-mode=VERIFY_IDENTITY --ssl-ca='<verified CA bundle path>' \
     --connect-timeout=10 --batch bushi-database < read-only-metadata.sql
   ```

   Keep raw output private. Absence of marker table/hidden trigger bodies is
   UNKNOWN, not zero migrations or permission to certify history. A SQL read-only
   transaction does not replace a suitably authorized identity. No `mysql.user`,
   `SHOW CREATE USER`, data dumps or secret-bearing settings exports are requested.
4. Allowlisted Portal observations only: effective `PASSWORD_RESET_ENABLED`,
   reset throttles, `APP_BASE_URL`, `SESSION_COOKIE_SECURE`, `WEBJOBS_STOPPED`,
   package/build mode; presence/resolution of key references, not their values.
   All `BUSHISA_STAGE_*`, test/barrier, fake-carrier and synthetic consumer flags
   must be absent/disabled in production. Do not call appsettings-list or
   connection-string-list with client-side filtering: those retrieve secrets.

### Full delta to reconcile, not just the latest staging feature

Until capture, the complete **hosted-to-candidate** delta is UNESTABLISHED.
The reproducible comparison is **known remote main `0bf311f` to the proposed
candidate**, without asserting main is currently hosted. Use:

```bash
git diff --name-status 0bf311fb6d6a298712fd4cd16d0722305516edd4 <candidate-sha>
git diff --stat 0bf311fb6d6a298712fd4cd16d0722305516edd4 <candidate-sha>
git log --reverse --oneline 0bf311fb6d6a298712fd4cd16d0722305516edd4..<candidate-sha>
```

The [225-path change inventory](evidence/direct-release-change-inventory-2026-10-20.json)
pins that entire comparison to implementation candidate
`803a03a87d5dd73f1f130a5145c8672e447c911e`, including old/new Git blob IDs.
It is not just a diff from `423ec6c` and does not claim the comparator is deployed.
Evidence-only follow-up changes must still be included in the final artifact
manifest; they cannot change the application/worker code without new verification.

| Change family from that comparator | Release consequence |
|---|---|
| Prompt 01/02 guards, authorization, protective replay | Real route/session/deletion behavior and event precision change, not only reset forms. |
| L01 reports and Decisions 28-33 | Authorized attribution, deduplication, immutable receipts/probation and reported-block clearance; real reviewer/MFA/staffing still gated. |
| L02 pair ledger/restart/restore/unblock/import tools | Legacy history stays incomplete; lifetime limits/restarts fail closed without positive proof. Synthetic imports cannot enable real history. |
| L03a evidence, Q11, erasure, Q22, SC | New clocks, access revocation, cancellation, scoped cleanup and recovery interfaces. Production adapters remain unavailable; synthetic files are not Azure mounts. |
| L06 and migration 016 | Purpose-separated encrypted reset outbox, explicit POST single use and session-version revocation; default-off, no login/eligibility bypass. |
| Guarded stage profile/driver and this worker repair | Stage controls must never be supplied to production; mail-only scheduled hygiene replaces broad cleanup. |
| Migrations 005-016, helper changes to earlier definitions | Forward schema work, triggers, fixed health routines and new DML grants. Existing migration markers skip earlier scripts; never edit/delete markers to force old migrations. |
| Deployment, UI/API/browser and test changes | Review full root/API/JS package and protected-path exposure. A green reset suite alone is not the full D03 journey. |

Baseline `0bf311f` has only 001-004. Neither 005 nor 016 is declared applied in
production. Once hosted identity is known, substitute that exact source for
the comparator and reconcile every changed file plus untracked/manual drift.
Do not deploy until code and schema compatibility are understood.

## Local rehearsal and acceptance

[Release rehearsal test](../../tests/integration_release_upgrade.php) refuses
before connection unless the existing disposable guard and owned loopback
`bushisa_test_release_*` target pass. It extracts a fixed allowlist from exact
`0bf311f`, builds its real 001-004 schema, adds synthetic pending/active accounts
and approved chat, runs current 005-016 and a second migration pass. It uses the
observed SQL-mode set and UTC for migration connections, restoring the owned
server's globals afterward. It does not fabricate production records or anchors.

Local profile: **Linux PHP 8.5.11; MySQL 8.0.45**. Owner production CLI matches
PHP patch, while production MySQL patch remains unknown. Fixture isolation is
REPEATABLE-READ, server collation utf8mb4_0900_ai_ci, schema utf8mb4_unicode_ci;
production isolation/full collation are unknown. Fixture root migration identity
has administrative privileges, `log_bin=1`, trust-function-creators=0; this is
not the Azure restricted migration role. Loopback fixtures do not prove Azure
VERIFY_IDENTITY/private TLS; production secure-transport is ON. Existing SG
restricted-role checks and exact CI evidence remain separate.

| Cases | Expected result / current local result |
|---|---|
| DR01-DR05 | Exact historical schema 001-004, forward 005-016, all 16 markers and rerun: PASS. |
| DR06-DR09 | Preserve accounts/passwords/status/session versions and chat; legacy-incomplete ledger, inactive writer epoch, no invented genesis/attempt clocks: PASS. |
| DR10-DR11 | Missing signup anchors produce review requirement without deletion; incomplete legacy request permission refuses: PASS. |
| DR12-DR15 | Production broad CLI refuses pre-connection; fake mail rejected by mail-cleanup CLI; SMTP-mode hygiene has no account/chat effects: PASS. No SMTP dispatch occurs in this test. |
| DR16 | Owned test server SQL-mode/time-zone restoration verified: PASS. |
| DR17.1-DR17.4 | Actual Bash script in owned fixture: success, cleanup failure still dispatches, dispatcher failure propagates, both fail: PASS. Stub carrier only. |
| DR-P01 | Actual captured-production-schema upgrade, restricted grants/TLS/definers: NOT RUN, capture missing. |
| DR-P02 | Previous deployed artifact compatibility with forward schema and protective state: NOT RUN, artifact missing. |
| DR-P03 | Fresh production backup/media/config verification and witnessed recovery/replay: NOT RUN. |
| DR-P04 | Owner-controlled live smoke/delivery/worker checks after separate approval: NOT RUN. |

Commands, with privately injected **disposable** credentials and exact test
flags only (never use this environment in production):

```bash
php tests/unit_mail_worker_schedule.php
php tests/integration_release_upgrade.php
# Existing per-suite disposable profiles and synthetic flags from CI/evidence:
php tests/integration_l06_password_reset.php
node node_modules/@playwright/test/cli.js test --config=playwright.l06.config.js
php tests/integration_q11_signup_expiry.php
php tests/integration_verification_retention.php
php tests/integration_attempt_erasure.php
```

The release test adds no production migration. Initial local shell-fixture
execution exposed Windows checkout CRLF; the owned fixture now normalizes it
before Bash. Linux deployment packages must retain executable LF shell scripts.
See [local result](evidence/direct-release-local-2026-10-20.json) for exact
worktree/candidate attribution; new CI runs the rehearsal in its own sixth job.
The affected [local regressions](evidence/direct-release-regressions-2026-10-20.json)
also passed: three unit suites, 25 L06 HTTP cases, six rendered cases (including
two-session page/API revocation), Q11 74/74 assertions, Q22 75/75 and ordinary
attempt erasure 58/58. These development executions used the modified working
tree based on `423ec6c`, not clean historical `423ec6c`. Their ordinary test SQL
mode additionally includes NO_ENGINE_SUBSTITUTION; only the release-upgrade
rehearsal selects the owner's exact observed set. Existing matrix statuses and
external/unavailable variants are not upgraded by these repeated checks.

### Exact implementation-candidate CI

Source `803a03a87d5dd73f1f130a5145c8672e447c911e` passed **all six jobs** in
[feature push 39417740204](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39417740204)
and [draft PR run 39417838741](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39417838741).
Draft [PR 8](https://github.com/Takudzwa00kyle/Bushisa/pull/8) targets main without
merging. Its test checkout `4b18eed8cd73384df772b5cfbfd35105d642d92b` has parents
`0bf311f`/`803a03a` and the same tree as the feature source. The
[verified CI record](evidence/direct-release-hosted-ci-2026-10-20.json) includes
job URLs, source/checkout SHAs and independently checked artifact SHA-256 digests.
Both downloaded release reports have 16 PASS cases, clean worktrees, PHP 8.5.11,
MySQL 8.0.46, the observed SQL-mode set/UTC and verified fixture-global restoration.
The worker's four Bash variants run in that job too. Other jobs cover the
existing SMTP/outbox/lifecycle/pair/session, browser/race, L06 and stage guard
regressions. Four original jobs declare PHP 8.4 and the stage-guard job declares
8.5; only the release job's exact 8.5.11 patch is asserted by this report.

The follow-up checkpoint adds evidence/documentation only. Its final CI
attribution is kept with the matching draft PR and run artifacts; the committed
record above remains historical evidence for `803a03a`, never relabelled as a
later head. No run is Azure/staging/production acceptance, and no deployment
workflow was invoked. No further application change is authorized by recording CI.

## Scheduled behavior, legacy effects and backfill boundaries

Observed schedule stays `0 * * * * *`; no scheduling change is made here.
The deployed worker's actual package is unknown. The proposed
[run.sh](../../deploy/mail-webjob/run.sh) executes only:

1. [cleanup_mail.php](../../bin/cleanup_mail.php): bounded expiry/terminalization
   of signup challenge secrets and expired code-bearing payloads; no account,
   evidence, chat, pair ledger, file/session or metadata-table purge.
2. [dispatch_mail.php](../../bin/dispatch_mail.php): existing configured SMTP
   sender/transport, new state-machine validity/claims and bounded retries.
   Cleanup failure remains nonzero/visible but does not suppress dispatch;
   dispatcher failure is nonzero. No real email was sent in verification.

Broad `cleanup_policy.php` previously would expire anchored attempts, invoke
report evidence purge/audit cleanup, remove pair request/events/operations and
expired closed messages, plus reset challenge/mail metadata cleanup. It now
refuses non-development environments **before DB loading**, with no production
override switch. Separate attempt/Q22/session/evidence consumers are not added
to WebJobs; existing production adapter/authority/recovery gates still apply.
This prevents accidental scheduling, not a claim those retention duties are met.
Reads/explicit approved writes can still enforce access expiry/unsend and Q11
logical expiry. Live erasure and recovery obligations remain independently due.

Old pending signup mail without Q11 job/anchor proof is **not** transformed into
a new authorized delivery: current dispatcher rejects/terminalizes it. Drain
valid old work with the previous worker before maintenance, then quiesce it;
check counts without inspecting bodies. A still-pending old attempt needs trusted
creation/first-approval evidence and a reviewed continuation/recovery plan.
No fresh creation/approval time, generation, password or session binding may be
invented. Active legacy accounts remain active; missing attempt bindings do not
turn them into new attempts. Existing chat grants survive unmatch alone; bans,
blocks, revocations and account eligibility remain independent.

Migration 006 seeds known request counts/declines from surviving rows, not missing
history. Migration 007 labels existing pairs legacy-incomplete and leaves writer
epoch inactive. New requests/restarts/grants requiring completeness will refuse;
this can affect both old pairs and future pairs until an observed all-writer
cutover is accepted. This is a **release blocker for affected journeys**, not a
permission to zero counters or activate `pair_ledger_epoch` by assumption. L02
has no real-data enabling importer. Migrations 010-015 similarly do not backfill
case final resolution, signup anchors, attempt keys or session manifests.
No production backfill command is included.

Reset stays off: require effective `PASSWORD_RESET_ENABLED=false` before rollout
(deployed value currently UNKNOWN), leave the proposed throttle values unset,
and keep configured production origin `https://bushisa.me`. Do not set any
`BUSHISA_STAGE_*`, fake transport or synthetic/test flag to make checks pass.
Approved sender remains `DoNotReply@bushisa.me`, display Bushisa; temporary unset
Reply-To is approved while the monitored inbox is unverified. Existing working
SMTP settings/keys/domain DNS are preserved; no new reset DNS is implied.

## Maintenance procedure — PREPARED, NOT AUTHORIZED TO EXECUTE

The final approval request must attach the exact artifact/worker digests,
observed schema delta, migration/runtime identities, signed checklist, timed
rehearsal, explicit allowed configuration changes, start/end window, owner and
backup contacts, smoke accounts and rollback boundary. These fields remain
CONFIRM/VERIFY; do not invent downtime or appointment. This is a maintenance
update of the existing service, not an alternative to completing D03 or P0/P1.

| Order | Owner/operator action and expected result | Abort/stop condition |
|---|---|---|
| 0 — evidence lock | Capture metadata above, compare actual schema and all pending migrations against this candidate; verify exact candidate CI and code review. Freeze unrelated deployments. Choose one deployment path; inspect Deployment Center auto-build and repository main-push workflow together. | Unknown/mixed source, schema drift, privilege gap, unreviewed migration effects or critical auth defect. |
| 1 — tested artifact | Build exact reviewed source with locked `composer install --no-dev --prefer-dist --no-interaction`. Preserve SHA-256 of application and worker bundles privately. Exclude `.git`, `.env`, local config, test dependencies/fixtures, `.tmp`, logs and user files from the web artifact. Migrations/tools remain private outside web-served paths or explicitly denied. Verify NGINX denial for config/database/bin/deploy/vendor internals/docs/tests and uploads-as-code; `.htaccess` is not NGINX enforcement. | No proven private-path protection/artifact filtering, unverifiable dependency/build or unpinned artifact. |
| 2 — fresh recovery set | Before writes, verify a fresh provider backup/recovery point and retention/expiry/redundancy. Securely preserve the **actual previous** app AND worker packages/configuration references; immutable digest, access controls and expiry outside web root/Git. Cover actual DB, media/versions, uploads, protected session store/config and independent protective records. Do not dump secrets into this evidence. | Seven old visible backups alone, missing media/config inventory, missing previous artifact or no tested recovery route. |
| 3 — quiet window | Owner announces the approved window; block ingress/app writes with an operator-approved maintenance mechanism, stop/quiesce existing WebJob and verify no claimed/in-flight work/requests. Record worker state and queue counts privately. Leave reset off. | In-flight SMTP/requests cannot be reconciled, no maintenance/write fence or unknown automatic deployment path. |
| 4 — pre-write snapshot | While writes are stopped, complete fresh consistent backup/export within approved controlled storage, verify checksum/completion and recoverability; preserve latest independent block/deletion/consent state. Record UTC cutoff and expiry. | Backup error/incomplete copy, unverified replay obligations or storage/expiry breach. |
| 5 — migration | From the private candidate directory, supply the existing controlled migration identity (never runtime DDL or credentials in command history), run commands below. Keep old/new app and workers quiesced. Record each migration result; review partial DDL before retry. | Any SQL error, unexpected table/trigger/routine, wrong target/identity, timeout or changed metadata. No blind rerun/marker edits. |
| 6 — application/worker | Deploy exactly pinned app and separately pinned mail-only worker using the approved single route. No slot, new paid service or fake carrier. Verify effective prod/TLS/session/root protections and reset-off/test-flags-off before permitting traffic. | Source/digest mismatch, old worker still scheduled, public private paths, reset unexpectedly enabled or session/grant mismatch. |
| 7 — owner smoke | Consenting synthetic accounts only: login/logout, pending verification/adult restriction, discovery/match/chat/approved-unmatch, confessions, report/block routes and current old-session protected page/API rejection. Reset forms stay neutral/disabled; do not issue live reset proof. First mail checks use queue/status observation; any real test message needs a separately approved recipient/action. Check no code/body logging. | Any authorization/consent leak, unexpected purge, account/legacy lockout, queue failure, revoked session success or failed promised journey. |
| 8 — resume/observe | Resume only approved SMTP job after exact worker/role/config check, then ingress. Monitor existing job failure logs, useful deadlines, queue growth, app 5xx/latency and DB/storage health with named primary/backup. No paid monitor activation. | Failed dispatch/cleanup, expired-code authorization, unusual loss/duplicates or no accepted operator coverage: close traffic, stop workers and enter the relevant recovery procedure. |

The future migration commands (not executed on production):

```bash
cd '<verified private candidate directory>'
# Preconditions: correct APP_ENV/DB target/TLS, controlled migration identity,
# maintenance/write fence, current backup, expected exact pending-version list.
php bin/migrate.php --through=016
php bin/migrate.php --through=016
```

For the future approved recovery snapshot, an authorized owner may use the
following **only after** write quiescence, reviewed backup privileges and a
verified encrypted destination/recipient. It retrieves account data and is
**outside this task's metadata-only production authorization**. Do not paste
its output into evidence/chat or store plaintext on the app/web root. With
`set -euo pipefail`, private `umask 077`, verified `age` recipient and paths:

```bash
mysqldump --host=bushi-server.mysql.database.azure.com --port=3306 \
  --user='<existing authorized backup principal>' --password \
  --ssl-mode=VERIFY_IDENTITY --ssl-ca='<verified CA bundle path>' \
  --single-transaction --routines --triggers --events --hex-blob \
  --no-tablespaces --set-gtid-purged=OFF --column-statistics=0 bushi-database \
  | age --recipient '<verified recovery public recipient>' \
      --output '<private controlled snapshot path>.sql.age'
sha256sum -- '<private controlled snapshot path>.sql.age'
```

This is not an automatic substitute for provider backups or verified media and
protective records. Unknown export authority, key availability, expiry or a
failed pipeline stops the release. Prove decryption/restore locally under the
separate authorized recovery procedure without importing real users into
routine test fixtures. No new backup-retention value is adopted here.

Expect only the verified missing migrations on first pass and 16 `Skipping`
lines on second. DDL auto-commit means errors can leave a partial forward
schema. Never bootstrap with `database/bushisa.sql`, delete markers, relax TLS,
grant runtime root/DDL, or set function-trust globally to force success. For
Azure log-bin/trigger constraints, obtain the actual service value and approved
restricted migration proof first; synthetic root success is insufficient.

Expected downtime is **not yet established**: window = measured queue drain +
backup + exact-schema migration + app/worker replacement + smoke + rollback
reserve. Local synthetic timing does not estimate production locks, row volume,
network or backup duration. Name an owner-controlled abort time before approval.
Do not adopt the proposed 30-minute maintenance/SLO value as a proven capability.
Current main-push workflow has no required CI dependency and auto-deploys; do
not merge merely to prepare or stage the migration. No deployment command is
safe until artifact identity and the two observed deployment mechanisms agree.

## Application rollback versus database recovery

**Application rollback:** keep ingress/writers/worker stopped; preserve failed
candidate logs without content. Restore the privately preserved exact previous
app and **matching worker** artifact/config through the chosen reviewed route;
verify digests. Do not run down-migrations. An additive schema does not establish
old-code compatibility: old writers may violate new immutable guards or omit
protective state. DR-P02 must test that exact previous app on the forward schema
before it may serve users. If it cannot safely read current restrictions, remain
in maintenance and use reviewed forward repair/recovery instead. No data rewind
is part of code rollback; post-release protective actions cannot be discarded.

**Database recovery:** distinct incident authorization is needed. Freeze every
writer/mail dispatcher first; record the last acknowledged protective actions,
backup cutoff and any post-cutoff changes that would be lost. Rehearse the
specific verified backup/import on local disposable infrastructure before
approving any in-place restoration on the existing server. Only an independently
verified recovery procedure may replace data. Preserve newer restrictions and
apply independent deletion/block/consent/expiry replay **before** app reads or
mail resume; cancel stale challenges/queues and reconcile in-flight receipts.
Restore matching media/config and verify grants/definers, migration markers,
session versions and authorization, not merely database availability.

Azure managed PITR **creates a new server**, rather than rewinding the existing
one ([Microsoft procedure](https://learn.microsoft.com/en-us/azure/mysql/flexible-server/how-to-restore-server-cli)).
It is not a zero-cost in-place rollback or an authorized second environment.
If an approved existing-capacity logical recovery is unavailable, record that
as a recovery blocker; do not provision a server, silently approve spending,
delete a schema or improvise an untested dump restore. No destructive DB recovery
command is supplied for execution before the actual backup/target/replay plan
is reviewed. Witnessed restore and independent durability remain NOT RUN/open.

Recovery instructions survive the latest possible DB/media/export expiry plus
seven days and any longer active restriction, using actual copy bounds. The
approved conditional `7 + 35 + 7 = 49d` example is not the observed backup
setting; owner reports seven-day DB backups, media/extra copies unknown. Neither
maintenance, migration nor replay restarts an original retention deadline.

## Outstanding decisions/evidence and next action

| Type | Specific remaining prerequisite | Responsible role / next action |
|---|---|---|
| Owner execution information | Maintenance start/end/abort window, exact permitted change set, accepted primary/backup, consenting smoke accounts, existing recovery capacity | Takudzwa supplies actual choices; no invented appointments or budget. |
| Policy decisions | Production reset throttles before separate activation; Q40 session redesign still proposed; other register proposals unchanged | Owner selects only when needed. Direct release does not approve them; reset stays off. |
| Executable engineering | Actual schema/grant rehearsal; complete deployed-source diff; exact old-artifact rollback; legacy anchor/history/cutover compatibility; NGINX/private paths; full D03 routes/geography/media/retention completion | Engineering uses metadata capture; preserve incomplete history and existing approval boundaries. |
| External operational/review | Existing monitoring/alert receipt, SMTP/inbox/header acceptance, monitored support, operators/coverage, qualified security/privacy/safety, actual backup/media/session/replay and witnessed recovery | Named accepted operators/reviewers; synthetic fixtures and old green CI cannot close these. |

**Next concrete owner action:** return the private metadata/fingerprint results
from the read-only capture, with secrets and user content excluded. This needs
no paid environment and does not authorize the mutation sequence. Next bounded
engineering task is an exact observed-schema upgrade/previous-artifact rollback
rehearsal and resolution of any identified drift. Only after its evidence and
remaining release criteria are satisfied should a concrete production approval
be requested. Current recommendation remains **NO-GO**, without reopening any
settled product decisions.
