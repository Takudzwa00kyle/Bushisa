# Direct-release handover: worker, roles, cutover and recovery

**26 October current handoff:** [route/worker/recovery evidence](release-routes-recovery-evidence-2026-10-26.md)
records restricted-runtime real-route testing, the candidate LF wrapper with
actual restricted-worker CLIs, and local synthetic restore/replay. It advances
HR04 only for enumerated implemented routes and provides local recovery effects,
not a production replay adapter or Azure recovery acceptance. No historical
case result is rewritten. Actual grants/definers, full D03, legacy cutover,
safe traffic fencing, compatible fallback and independently durable/witnessed
recovery remain release gates. Stop/drain the installed old job before either
package changes; old wrapper plus candidate still stops dispatch at guard 78.
Appointments remain PENDING. PR 8 remains draft; current CI is attributed in
the new evidence rather than inferred from the older paragraph below.

**22 October local engineering follow-up:** [role/worker evidence](release-roles-worker-evidence-2026-10-22.md)
records 58 strict-profile checks, 337 affected-regression assertions and focused
worker/configuration checks. Worker-reachable reads and validation locks
were narrowed and synthetic connection TLS honored; fixture table-wide users
UPDATE is no longer treated as least-privilege proof. HR01–HR03 have bounded
local evidence; HR04/full-D03 runtime route grants remain PARTIAL. Actual Azure
roles, legacy cutover and recovery remain open. See the [cutover worksheet](legacy-cutover-preparation-2026-10-22.md)
and [existing-resource recovery options](existing-resource-recovery-options-2026-10-22.md).
Reviewer/operator assignments stay PENDING without blocking local engineering.
No production operation, real mail, staging acceptance or recovery acceptance
is claimed. PR 8 stays draft; hosted CI for these changes is NOT RUN.


Prepared locally after [capture 09 reconciliation](code-gap-reconciliation-2026-10-21.md),
from `de7547a` plus this documentation checkpoint. **Review packet only.** It
does not authorize grants, configuration, migrations, worker execution, backup
creation/restore or deployment. Existing-resource direct release is retained;
no paid staging environment is required. See the [maintenance/recovery package](direct-production-release-preparation-2026-10-20.md).

## Worker compatibility and permitted activation

| Application / actual installed wrapper | Static behavior and disposition |
|---|---|
| Observed baseline / old wrapper | Runs `bin/cleanup_policy.php`, then `bin/dispatch_mail.php` under `set -euo pipefail`. Owner evidence establishes a successful no-work run, not delivery or deadline acceptance. |
| Candidate / old wrapper | Candidate broad cleanup rejects production with exit 78 before DB connection; `set -e` prevents mail dispatch. **Incompatible; do not resume this combination.** |
| Old application / candidate wrapper | Candidate `bin/cleanup_mail.php` is absent from the baseline. Wrapper records the cleanup failure and attempts dispatch; this mixed combination is not an accepted release/fallback. |
| Candidate / candidate wrapper | Bounded `cleanup_mail.php`, then SMTP `dispatch_mail.php`; cleanup failure remains a nonzero job result without suppressing otherwise valid dispatch. Requires accepted schema, worker identity/TLS, legacy continuation and operational evidence. Not installed or run here. |

Sources: [candidate wrapper](../../deploy/mail-webjob/run.sh),
[worker binding](../../php/mail_worker_config.php),
[mail-only cleanup](../../bin/cleanup_mail.php),
[dispatcher](../../bin/dispatch_mail.php),
[broad cleanup guard](../../bin/cleanup_policy.php).
The installed wrapper is separately identified in 04, not inferred from 09's
source copy. Its settings remain the semantically equivalent minute schedule.

The bounded mail path can still invoke
`bushisa_signup_mail_rows_locked` / `bushisa_signup_expire_locked` for selected
accounts. A trusted overdue Q11 anchor can disable the attempt, increment its
session version, invalidate challenge/mail copies and enqueue erasure/session/
recovery obligations. This is consequential state change, not read-only hygiene.
It does not activate broad erasure, evidence/session consumers or production
recovery. Missing historical anchors/jobs are not permission to fabricate them,
reissue old mail, extend a code deadline or erase the account.

During a separately approved maintenance operation, stop and drain the actual
installed old schedule before replacing either app or worker. Preserve exact
old app/worker/config privately; migrate 005–016 under the controlled principal;
verify candidate and separately installed wrapper digests/permissions/config;
only then consider SMTP resumption after accepted checks. A rollout cannot
leave the old job executing against candidate files. This is ordering, not
authorization or an executed maintenance action. SMTP-accepted mail cannot be
recalled by DB cancellation; reconcile in-flight claims/results before resuming.

## Role separation: existing proof and its limits

The configured production DB user is owner-observed globally administrative
with GRANT OPTION. New principals/grants are **not established**. Candidate
worker binding requires a complete dedicated `MAIL_WORKER_DB_USER` /
`MAIL_WORKER_DB_PASSWORD` pair in production; it rejects root, the same runtime
username and invalid/unresolved credentials before changing process bindings.
Web requests retain `DB_USER` / `DB_PASSWORD`; the migrator must receive a
separate private CLI credential context. Shared App Service settings/process
access do not by themselves isolate secrets. No password/grant command is
included for live execution before review.

[Historical observed-schema fixture](../../tests/integration_owner_schema.php)
`os_role` is a limited tested route profile, **not an approved final least-privilege
contract**. Its migration role has schema-scoped SELECT/INSERT/UPDATE/DELETE,
CREATE/ALTER/INDEX/REFERENCES/TRIGGER, CREATE ROUTINE/ALTER ROUTINE/EXECUTE;
no DROP, global admin, SUPER or GRANT OPTION. Its worker profile includes:

| Fixture rights | Scope / limitation |
|---|---|
| SELECT, UPDATE | `users`, `student_email_approval_codes`, `mail_outbox`, `signup_mail_jobs`, `session_bindings`, `password_reset_mail_jobs`. **UPDATE is table-wide, including users.** |
| SELECT, INSERT, UPDATE | `signup_lifecycle`, `verification_retention`. |
| SELECT, INSERT | `signup_lifecycle_alerts`, `signup_expiry_obligations`, `signup_attempt_erasure_jobs`, `session_copy_obligations`, `verification_cleanup_alerts`, `verification_late_results`, `verification_recovery_obligations`. |
| SELECT | `signup_attempt_identities`, `session_copy_coverage`. |
| SELECT, INSERT; column UPDATE | `cleanup_outbox`; UPDATE only `payload_json,status,run_after,last_error`. |
| Fixed EXECUTE | Health procedures `bushisa_pair_trigger_health`, `bushisa_attempt_schema_health`, `bushisa_verification_schema_health`. Validate definer lifecycle and body on Azure; do not grant arbitrary routines. |

OS13 proves atomic missing-mail-outbox-permission failure; OS15 proves tested
DDL denial and immutable-attempt-key protection. **Neither proves denial of
password or suspension changes by the worker.** Table-wide UPDATE on users
permits more than the intended mail operation; broad SELECT also exposes more
columns than minimally needed. No old PASS is relabelled or withdrawn: its
claim is bounded to the checks actually executed. Runtime fixture reads all
original D03 stores but does not establish complete D03 write-path grants.

Proposed next bounded engineering slice, using existing approvals: derive and
test a mail-only column grant contract from its full reachable SQL call graph.
For example [Q11 expiry](../../php/signup_lifecycle.php)
`bushisa_signup_expire_locked` updates exactly
`users.account_status,is_active,is_verified,session_version,deleted_at`.
These five are a **candidate UPDATE allowlist**, not a claim that tracing is
complete or an approved live GRANT. Distinguish worker-invoked operations from
web issuance/verification routes before deciding further rights. Review SELECT
columns separately; existing `SELECT u.*` paths may require a focused code change.
Do not broaden permissions merely to make a suite pass.

[Reset dispatcher](../../php/password_reset_mail_jobs.php)
`bushisa_reset_dispatch_one` reads users/profiles, and can invalidate a queued
reset challenge even while issuance is off. Code-free password-change notices
remain separately deliverable. The fresh empty-016/no-work worker test does
not prove grants for every pending reset job or notice. Inventory these paths,
fail atomically on missing rights and keep separate activation gates. Never
use a signup challenge reference for reset or turn reset on to test production.

| Proposed local case | Required result | Status |
|---|---|---|
| HR01 | Mail-only role runs guarded synthetic cleanup/claim/completion, due Q11 transition and cancellation with exact required rights; preservation of other users/generations. | PASS bounded local RP worker operations; see 22 October evidence |
| HR02 | Password hash, suspension/other administrative columns, DROP/DDL, arbitrary routines and another schema are denied; denied transaction has no partial side effects. | PASS listed local engine denials; final privilege review open |
| HR03 | Pending reset cancellation and notice paths have an explicit minimum contract or remain fail-closed; no SMTP, proof/body logging or activation. | PASS bounded local purpose paths; production activation gates open |
| HR04 | Complete D03 runtime write paths and immutable-trigger/definer negatives run under the proposed runtime role, separate migrator only; settings/TLS/UTC match recorded profile. | PARTIAL; bounded runtime/migration checks pass, full D03 route coverage incomplete |

The original proposed checks above now carry bounded local status, not additions marked PASS in A/LB/LC or a
claim of actual Azure grants. Migration privilege compatibility must be tested
on the Azure service under accepted principals; upstream MySQL alone cannot
prove managed-service definer behavior.

## Legacy continuation and all-writer boundary

[L02b writer inventory](l02b-legacy-history-import-contract.md) W01–W12 remains
the starting checklist, not evidence of production cutover. Include matching
and restoration/read side effects, swipes, requests/chat/lazy expiry, reports/
blocks/clearance, messages/unsend, deletion/consent/replay, moderator/admin writes,
signup/onboarding/authentication, profile/preferences, cleanup, migrations,
restore/direct SQL/support/importers and retired workers. Explicitly include
L06 password/session-version paths and Q11/Q22/SC consumers added later.

| Cohort / state | Required continuation; evidence still needed |
|---|---|
| Verified active legacy account | Preserve verification, adult/onboarding status, sessions subject to existing revocation rules, restrictions and approved chat. Missing historical signup generation/anchors must be handled without invented timestamps. Test login/protected routes under proposed privileges. |
| Legacy pending attempt / old queued signup mail | Missing Q11 anchor or purpose/generation-bound job proof remains unresolved. Prepare a bounded continuation design for this cohort; do not equate old mail with a new job, reset issuance/attempt clocks or automatically delete. No synthetic metadata becomes production provenance. |
| Legacy pair | 007 records `legacy_incomplete`; no fabricated events or zero lifetime counts. Request/restart permissions needing history remain fail-closed. Approved chat surviving unmatch stays a separate rule. |
| Genuinely new post-cutover pair | Needs observed active writer epoch, trusted account genesis and reliable events from every writer. Migration alone does not establish this. |

[Pair resolver](../../php/pair_history.php) `bushisa_pair_complete` checks
`complete_from_genesis`, participant-bound genesis, active epoch/writer reference
and health checks; otherwise creation requiring history refuses. Migration 007
does not activate the epoch. With no observed all-writer cutover, new requests
can remain blocked for both existing and newly created pairs. This is a D03
release blocker, not permission to reduce the approved feature scope.

Prepare an engineering-reviewed boundary sheet with snapshot boundary **B**,
every-writer-reliable boundary **W**, captured interval end **H** and destination
snapshot/version **C** as defined in L02b. For each writer record deployed
artifact/entry point, stop-and-drain evidence, in-flight disposition, last
pre-boundary and first reliable event evidence, reviewer and remaining gaps.
Wall-clock/schema markers alone cannot certify it. Missing/conflicting history
remains closed; the synthetic importer/activation controls are not production
cutover tools. Completion needs an approved non-enabling evidence design or a
separately reviewed enabling adapter, guarded rehearsals and authentic evidence.
No new history policy decision is requested here.

## Recovery and reopening traffic

OS17 reproduced an unsafe fallback: old code can add a request without the new
durable pair ledger on the forward schema. Thus reverting app files does not
undo migrations and can break lifetime/safety provenance. Keep ingress and
**all writers** stopped during diagnosis: APIs/pages with lazy writes, auth/
signup/reset, admin/moderator/support scripts, scheduled mail/cleanup, imports,
direct SQL and restore automation. Do not treat read-looking GET routes as
guaranteed read-only. Rejected writes alone do not prove the old app safe.

The actual previous app archive remains missing; a new Git archive cannot be
called that historical artifact. Before a separately authorized change, privately
preserve the actual application, installed worker and necessary configuration
with digests/capture time; include media/session and independent protective-state
coverage without putting secrets or content in Git. Preserve unknown files
privately rather than executing/printing them. This future backup is not now
created or proof of recoverability. Observe source/worker drift and differences
from the known baseline explicitly.

| Acceptance item | Required evidence before traffic can reopen | Status |
|---|---|---|
| Compatible fallback or forward repair | Exact artifact on forward schema; no missing pair events, permission bypass, reset/session revival or stale mail. All D03 promised routes remain usable under accepted roles. OS17 baseline is INCOMPATIBLE; unknown actual previous artifact DR-P02 remains NOT RUN. | OPEN |
| Database recovery method using existing resources | Accepted recovery destination/capacity, measured restore/reconciliation and abort criteria. Azure PITR may require a new server/cost; it is not assumed free or authorized. No destructive down-migration. | DR-P03 NOT RUN |
| Recovery coverage | Verified fresh DB backup plus media, actual app/worker/configuration, session invalidations, independent deletion/block/consent record and queue/result disposition. Seven-day observed DB backups do not prove media/session coverage or restore success. | OPEN |
| Protective replay / old copies | Latest acknowledged protective actions applied before reads/mail; retain independent instructions until latest possible recoverable-copy expiry plus seven days. Compute from actual live purge/copy bounds; neither a seven-day configured backup nor the proposed 35-day policy proves a universal 49-day clock. | OPEN |
| Acceptance / witnesses | Engineering executes controlled synthetic rehearsal, operations records timing, privacy/safety witnesses replay/access, owner accepts evidence after actual reviewers are named. No independent storage durability or witnessed restore is established here. | NOT RUN |

Do not reopen writes based on hash coverage, backup existence, schema migration
success or old green CI alone. Reconcile all in-flight work and prove complete
current restrictions/session versions/pair budgets before reopening. Actual
maintenance window, accepted primary/backup, rollback authority, smoke accounts,
monitored contact and qualified reviewer sign-offs remain unassigned/unverified
where recorded; the repository owner is not automatically an independent
appeal reviewer or privacy officer.

## Next owner action and independent engineering work

**No further production file collection is requested now.** In one reply, name
the accepted DB/security reviewer and maintenance/recovery primary plus backup
(or state which are still unavailable), and identify the existing-resource
recovery method/capacity they can validate. They should review this packet's
worker-role restriction, old-wrapper stop condition, legacy cutover and recovery
stop/reopen criteria. This requests real responsibility/capability information,
not approval to perform the production change, buy resources or enable features.

Engineering has completed the bounded local HR01–HR03 checks; HR04 remains
PARTIAL for full runtime route coverage. The cutover worksheet and recovery
options are prepared. Continue those engineering gaps while real appointments
remain pending. Existing expiry clocks, consent/restrictions and lifetime budgets do not
need reapproval. Reset throttles before future activation and unresolved Q40
settings remain separate owner policy questions; reset stays off. Qualified
security/privacy/safety and staffing acceptance remain external gates. All
managed-stage cases stay DEFERRED/NOT RUN; SC26 production topology stays PARTIAL.

The original packet was static preparation. Its 22 October follow-up executed
only guarded local synthetic tests; no production session/DB access, live
grants/settings, real mail or recovery operation occurred.
