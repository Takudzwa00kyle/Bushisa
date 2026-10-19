# Bushisa — Prompt 02 coding-agent handoff

Prepared 12 October 2026, Africa/Harare; revised 13 October following the 02:27 CI-success screenshot and independent PR/run/job verification. Attach this file to a coding agent with repository access, or copy the complete prompt below.

Current CI status is **PASS** for both jobs on [draft PR #3](https://github.com/Takudzwa00kyle/Bushisa/pull/3), head `c27ea640c382c32792989611cfe682cf2a414520`. [Run 38327168338](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38327168338), attempt 1, completed successfully after the pull-request event. The earlier missing-run gap is resolved for this PR validation. Browser, two-session revocation, actual HTTP overlap, production-version acceptance and policy/release gates remain open. Begin Prompt 02 from the recorded checkpoint on a task branch; keep PR #3 in draft while the new evidence is developed.

## Copy-ready prompt

```text
Continue Bushisa P0–P2 remediation with Prompt 02: prove real routes, two
independent sessions and genuine HTTP overlap. Implement and execute the
focused regressions, make bounded technical repairs, and record the evidence.

STARTING ARTIFACT AND READ ORDER

Repository: https://github.com/Takudzwa00kyle/Bushisa
Confirmed remote branch: codex/prompt01-db-validation-20261012
Checkpoint commit: c27ea640c382c32792989611cfe682cf2a414520
Parent/local-test-time baseline: 6ed126d31ce6d2a5e046cf0ace98744345a8e934
CI PR: https://github.com/Takudzwa00kyle/Bushisa/pull/3 (open, draft, unmerged)
CI run: https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38327168338 (attempt 1, both jobs PASS)
PR base/main snapshot: 0bf311fb6d6a298712fd4cd16d0722305516edd4
Fetched PR test-merge object: f3c1b1cdc49850566b6e913d7aac6cfa9769f7a3

Read applicable AGENTS.md instructions. Inspect current branch/HEAD, remote
identity and staged/unstaged/untracked work before changing files. Preserve
unrelated work. Use a separate task branch or worktree based on the confirmed
checkpoint, such as codex/prompt02-route-session-evidence-20261013. The CI PR base is
0bf311fb6d6a298712fd4cd16d0722305516edd4; its only change after 6ed126d is README.md. The fetched
PR merge object has parents main then c27ea64 and differs from c27ea64 only
in README.md. Preserve/reconcile this documentation update on the task branch,
and refresh the actual remote identities at startup. If the operator's checkout
has advanced, reconcile its actual diff rather than resetting it.

Read these actual repository files:
1. docs/remediation/decisions.md and docs/remediation/backlog.md.
2. docs/remediation/prompt01-local-db-execution-2026-10-12.md and
   docs/remediation/d04-d05-implementation-evidence.md.
3. docs/Bushisa-P0-P2-Continuation-Prompts-2026-10-11.md, shared instructions,
   Prompt 01E and Prompt 02. Its committed navigation predates this handoff.
4. docs/Bushisa_P0_to_P2_Azure_Remediation_Playbook.md, especially B01 and the
   affected P0 tasks.
5. docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md,
   especially R01–R12 and the required engineering verification table; also
   docs/remediation/policy-proposals-d04-d05.md.
6. Relevant sections of docs/Bushisa_Operational_Feasibility_Report.md,
   docs/Bushisa_Production_Feasibility_Study.md and
   docs/structural_feasibility_report.md.
Then inspect schema/migrations, shared authorization/services, actual form/API
callers, workers, playwright.config.js and the existing regression suites.

INHERITED WORK AND EVIDENCE

The 20-file c27ea64 checkpoint is committed and pushed. All 16 changed code,
configuration and test files were independently fetched and source-reviewed;
that review is distinct from execution. Preserve:
- tests/support/disposable_database.php and disposable-database.js.
- Guarded tests/bootstrap_database.php and guards before connections in all
  five DB suites, tests/workers/db_race_worker.php and database/seed.php.
- Browser schema rewriting and password/code-bearing-body diagnostic redaction.
- CI's bushisa_test_ci target, guarded bootstrap, guard unit and added core/
  DB-overlap regressions.
- The settings-only public CA fixture in tests/unit_mail_configuration.php.
  This certificate is test material, not a real connection trust bundle.
- SMTP fixture's explicit 127.0.0.1 client and certificate SAN containing
  DNS:localhost and IP:127.0.0.1. Preserve TLS verification and negative cases.

The operator supplied local PASS outputs for guarded bootstrap, migrations
001–004 and their idempotent second run, unit_mail_configuration,
smtp_transport_test, integration_p0_flows, integration_core_p0_flows,
policy_safeguards_p0_flows, integration_smtp_outbox and overlap_db_p0_flows.
Earlier guard/signup unit and changed-file syntax passes were also reported.
The five DB suites ran on the modified 6ed126d working tree before committing
c27ea64. The subsequent PR CI successfully executed its PHP/unit/mail checks and
guarded DB regression steps; details below supersede the old CI NOT RUN state.
Rendered-browser, two-session revocation and genuine HTTP-overlap evidence
remain NOT RUN. The core suite
uses direct DB/service calls; the overlap suite uses separate PHP DB workers.
Those suites cannot substitute for actual HTTP/controller/session evidence.

Authorized local test target: 127.0.0.1:3306/bushisa_test_local_20261012.
Dedicated principal: bushisa_test_20261012@localhost, with privileges scoped to
that exact disposable schema. The operator demonstrated PHP CLI 8.5.11,
MySQL server 9.6.0 and Python 3.13.5 on Windows Git Bash, with D:\php\php.ini,
OpenSSL and PHPMailer 7.1.1 installed from the lock file. Credentials are private.
This fixture principal is not proof of a restricted production runtime role.
The configured CI matrix is PHP 8.4/MySQL 8.0. Azure PHP was earlier observed
as 8.5.6; the Azure MySQL server version and D08 production matrix remain open.

Use the existing guarded disposable DB and fake mail. Read each guard contract
before execution. Require APP_ENV=development, APP_DEBUG=false,
BUSHISA_TEST_DATABASE=true, explicit DB_HOST/PORT/NAME/USER and
BUSHISA_ALLOW_RUNTIME_DDL=false. Enter a missing local test password through
a private shell/environment mechanism; never ask for it in chat or record it.
For the local run clear inherited DB_PASS, DB_SSL_CA and remote-test opt-in;
preserve correct TLS trust if using a separately authorized remote test target.
Recheck selected database and authenticated principal read-only. Use the
guarded bootstrap only for an intentional fixture reset. A new verified local
disposable container service is an alternative when needed; any remote-host
opt-in must apply only to that explicitly isolated synthetic service.

The live Azure bushi-database, production credentials, real student data and
ACS delivery are outside these tests. Keep existing working SMTP/signup/login
behavior. Application Insights integration/collection remains deferred until
D10. Password reset through DoNotReply@bushisa.me remains requested work for
the later recovery task; this prompt does not implement that new feature.

CI SUCCESS AND ITS LIMITS

The operator's 13 October screenshot and independent GitHub API reads confirm
PR #3 is open, draft and unmerged, with head c27ea640c382c32792989611cfe682cf2a414520.
CI run 38327168338, attempt 1, used the pull_request event and completed with
conclusion success. Started 2026-10-13T00:25:31Z (02:25:31 Harare); the run's
last update is 00:26:44Z (02:26:44 Harare).

lint-and-unit: PASS, job 115068412682, 00:25:33Z–00:25:54Z.
smtp-outbox-integration: PASS, job 115068412732, 00:25:33Z–00:26:43Z.
All reported job steps succeeded, including syntax/unit checks, dependency
installation, isolated container/bootstrap and signup/outbox regressions.
Run URL: https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38327168338
Job URLs: append /job/115068412682 or /job/115068412732 to that run URL.

The configured matrix is PHP 8.4, MySQL image mysql:8.0 and runner label
ubuntu-latest. Exact resolved patch/OS versions and checkout SHA were not
separately read from logs. The API head SHA is c27ea64; the fetched PR test-merge
object is f3c1b1cdc49850566b6e913d7aac6cfa9769f7a3, with parents
0bf311fb6d6a298712fd4cd16d0722305516edd4 and c27ea640c382c32792989611cfe682cf2a414520.
The workflow uses default actions/checkout behavior; distinguish the PR head,
PR merge object and an actual logged checkout when recording artifacts. The
runtime/config/test files are identical between the two fetched trees;
README.md is their sole difference.

The checked workflow executes PHP syntax, JavaScript syntax under js/,
signup/mail/guard units, the local TLS SMTP fixture, guarded DB bootstrap,
migrations twice, and the five CLI DB suites. It does not execute Playwright,
real route/session revocation or true HTTP overlap. It does not establish the
approved Azure production-version matrix, restricted runtime role, legacy/
interrupted migration, isolated restore, D04/D05 approval or P0/P1 clearance.

Retain the earlier absent push-run/billing/settings discussion as dated history.
Its cause remains undetermined; the new PR event successfully ran CI, so do not
reopen an already resolved current CI prerequisite or repeatedly rerun it without
new changes/failures. Test any Prompt 02 code changes on their own named artifact.
Keep PR #3 in draft; continue local implementation on the task branch.

FIRST REPAIR: GUARDED HTTP/BROWSER STARTUP

At c27ea64, playwright.config.js still supplies fallback APP_DEBUG=true,
runtime DDL=true, DB_NAME=bushisa_db, DB_USER=root and DB_PORT=3317, assigns
them into process.env and starts php -S before the test's reset guard runs.
Fix this harness configuration first. Validate explicit disposable settings
before launching any application server or readiness request. Require false
debug/runtime-DDL values for this run and pass the validated target consistently
to the PHP server, CLI/migration subprocesses and browser helpers. Prevent
configuration defaults from defeating required explicit selection or silently
overriding an intentionally supplied test environment. Cover missing opt-in,
production, missing/wrong database and disallowed target rejection before
connection, server startup, fixture writes or schema reset.

Validate the HTTP base URL as an owned isolated test server, and establish that
it uses the same selected disposable database. Permit a documented external
multi-worker test server without launching the default server too. Use a safe
loopback test APP_BASE_URL where required. Preserve existing bootstrap guards.

CURRENT SERVER LIMIT AND REQUIRED OVERLAP PROOF

The current Playwright server is php -S 127.0.0.1:8097 -t . with one application
worker. It can support labelled serial rendered/HTTP checks. Changing Playwright
workers or issuing Promise.all requests does not prove application concurrency.

Use a supported isolated multi-worker server for overlap, preferably local
Nginx/PHP-FPM or an equivalent available Linux/container setup. Native Windows
does not support PHP_CLI_SERVER_WORKERS. Record actual PHP/MySQL/server versions
and worker configuration. If a multi-worker runtime is unavailable, execute
the serial routes/session checks and label overlap BLOCKED with the exact
prerequisite; prepare a runnable isolated configuration instead of inventing PASS.

Use separate browser contexts/cookie jars, independent DB connections, actual
HTTP requests and deterministic coordination. Establish that both requests
entered the relevant application/transaction phase before either completed;
retain sanitized request/barrier timing and worker/connection evidence. Client
start times alone are insufficient. Test hooks must be narrowly enabled for
guarded synthetic development, fail closed in production and not expose secrets.
Coordinate actual application work rather than only direct DB worker calls.

ROUTE, SESSION AND R01–R12 COVERAGE

Map each existing form/API route, parameters, controller/service, SQL invariant
and side effect before writing tests. Use synthetic identities and actual
callers. Record baseline behavior, then add focused assertions and repairs.

A. Authorization and two sessions:
   Exercise pending/unverified, pending-DOB, underage, active, paused/revoked,
   suspended, deleted, moderator and role-removed states where implemented.
   Use two independently logged-in sessions for one account. Following
   suspension, deletion, eligibility/approval revocation and role removal,
   both sessions must lose the affected page/API access. Cover existing password
   change/session invalidation where supported; record missing reset/change
   paths as explicit gaps without claiming the planned reset feature exists.
   Test forged ownership/targets, stale cached roles, cross-account access,
   missing/invalid CSRF, expected form redirects and JSON 401/403 contracts.
   Assert both denial and zero forbidden writes/notifications/private fields.

B. Matching and consent:
   Exercise actual swipe, request, cancel, decline, approve and revoke routes.
   Cover reciprocal/duplicate likes, reverse pending requests without
   autoapproval, overlapping approvals, grant generations, retries,
   expiry/re-request/cooldown/budget boundaries and rematch/unblock.
   Assert no unilateral authorization and no silent restoration of old consent.

C. Message/evidence/lifecycle ordering:
   Exercise actual send/read/receipt/unsend/block/unmatch/report/deletion routes
   and applicable synthetic dispatch. Coordinate send/read/request with block,
   unmatch, revoke and deletion. Assert acknowledgement ordering, SQL invariants,
   idempotency and obsolete-notification suppression. Cover mid-transaction
   failure/rollback, cascades and survival of permitted scoped evidence.
   Deny unrelated/unassigned moderators and ordinary users access to retained
   originals, hidden authors, case bodies and exports. Cover forged anonymous
   targets, confession deletion/suppressed references and current UTC deadlines.

D. Signup/code/outbox routes:
   Cover wrong, expired, replayed, superseded, cross-account and wrong-purpose
   codes where supported; leading zeroes; resend/address-correction races;
   budgets across generations; terminal verifier/payload erasure and minimal
   code-free metadata. Obtain synthetic codes privately in test memory.
   Keep production payload encryption/TLS behavior and negative transport tests.

Read R01–R12 rather than treating this list as a substitute. Make an explicit
requirement-to-route/case matrix. Retention/unsend/expiry/cooldown expectations
derived from pending D04/D05 proposals must be labelled provisional, with the
source assumption and unresolved owner choice attached. D01/D02 are approved;
D03–D10/D12 and the unfinished D11 acceptance remain open. Do not approve policy
or invent owners, durations, geographic consent or release clearance.

Prepare bounded technical repairs revealed by the tests and re-run affected
cases. Larger defects belong in a precise documented Prompt 04 handoff.
Preserve the existing confession-time tests and any relevant eligibility/
private-field regressions. Keep unimplemented features and genuine external
restore/Blob/production-matrix checks explicit. A seeded fail-closed flag or
direct ledger call is not an isolated restore/replay rehearsal for R12.

EVIDENCE AND COMPLETION

Provide deterministic setup/cleanup, exact local commands, clear Windows versus
multi-worker instructions and a machine-readable case summary. Retain existing
regressions. Redact DB/SMTP passwords, cookies/session IDs, CSRF/bearer tokens,
approval/reset codes, code-bearing mail and private message/report bodies from
stdout, errors, HTML/JSON reports, screenshots, traces and exported artifacts.
Review Playwright's retained-failure traces for sensitive auth content; use safe
capture settings and sanitized evidence. Use synthetic data only.

Create docs/remediation/prompt02-route-session-evidence-2026-10-13.md (or the
actual execution date if later) and link
it from backlog, implementation evidence and continuation navigation. Include
the inherited c27ea64 commit, exact tested artifact/diff and final implementation
commit separately, runtime/worker configuration, selected test DB/principal,
commands, UTC timings plus Harare report dates, route/actor/session/case IDs,
expected outcome, observed outcome, invariants and evidence paths. Use specific
PASS/FAIL/NOT RUN/BLOCKED statuses; justify any OUT OF SCOPE mapping to a later
task. Preserve earlier failures as dated history and make the current result
easy to find. Append the verified Prompt 01 CI result to its dated checkpoint/backlog and
include the run URL/head/base/merge identities, attempt and job outcomes. Record
new Prompt 02 CI results separately; preserve historical failed/absent runs.

Run checks appropriate to the changed code. Re-run the affected existing DB,
mail, browser and route cases when shared behavior changes. Keep genuine
executed failures failing; avoid permanent skips or success masking.

Finish with code/tests and a reviewable feature-branch commit, changed-file
summary, exact commands/results, R01–R12 coverage, remaining CI/environment/
policy/restore/version gaps and the next exact remediation task. A documentation
or source review is not execution evidence. Keep main publication, production
deployment and live database operations outside this development task.
```

## What is already established

| Item | Evidence status |
|---|---|
| Remote Prompt 01 artifact | `c27ea640c382c32792989611cfe682cf2a414520` independently fetched; parent `6ed126d31ce6d2a5e046cf0ace98744345a8e934`. |
| Local DB/mail regressions | Operator PASS outputs preserved in the dated checkpoint; executed on the modified baseline before the commit. |
| Committed guard changes | Changed code/configuration/test files source-reviewed; no independent PHP rerun established. |
| CI | PASS: both jobs on draft PR #3, run 38327168338 attempt 1; head `c27ea640c382c32792989611cfe682cf2a414520`. API job/step results independently checked. |
| Browser / two-session / HTTP overlap | NOT RUN; this is Prompt 02's execution work. |
| D04/D05 and P0/P1 gates | Open; provisional development rules are not owner approval. |
| Telemetry / password reset | Telemetry deferred for D10; new password reset remains a separate requested feature. |

## CI completion and next action

[Draft PR #3](https://github.com/Takudzwa00kyle/Bushisa/pull/3) now has both successful CI checks. The earlier action-permission screenshots were compatible with this workflow; a pull-request event produced the successful [run](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38327168338). The reason the earlier push produced no matching run is still unknown.

Keep PR #3 in draft. Give this complete handoff to the coding agent and start Prompt 02 on a separate task branch. Preserve the passed guard/mail/DB work and the README-only main update; develop actual route/session/browser/HTTP-overlap evidence. Production merging/deployment and policy approval remain separate gates.

## 2026-10-14 continuation result

The later Prompt 02 continuation ran on branch `codex/prompt02-completion-20261013` from `438397e4656d8506c9144134eb53c48110ffb7fc`. The current candidate adds focused chat/request/session/race tests and repairs, with evidence in `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`.

Local guarded evidence now includes:

- 7-test browser route run passing `p0-rendered-flows`, `prompt02-session-routes` and `prompt02-chat-routes`.
- Actual API coverage for missing CSRF, forged ownership, request/approve/send/read/report/unsend/revoke, block cleanup, account deletion stale-session denial and moderator role removal.
- Linux Docker multi-worker HTTP overlap passing reciprocal swipe, approve-vs-revoke and send-vs-block with two distinct PHP worker PIDs at each guarded barrier.
- Guarded disposable DB, migration, unit, mail, SMTP fixture, core, DB-overlap, policy, integration and outbox checks passing against `127.0.0.1:3318/bushisa_test_prompt02_local`.

The exact candidate CI run is still pending until branch push/PR execution. Do not treat this as production clearance, D04/D05 approval, R12 restore evidence, real-mail evidence or deployment approval.

Optional updated PR description:

> The test harness now requires an explicit disposable database before fixture writes, schema resets and seeding. Bootstrap SQL is rewritten to the selected target; CI uses `bushisa_test_ci` and includes the core and DB-overlap regressions. Windows mail configuration and SMTP fixture portability fixes are included.
>
> Local operator evidence records passing bootstrap/migration rerun, mail configuration, TLS SMTP fixture and five DB suites. Both CI jobs passed on PR #3 in run 38327168338, attempt 1, with head `c27ea64`. Rendered browser/session/HTTP-overlap evidence, production compatibility and remaining release gates are pending. D04/D05 policy approval remains open.

Primary references for these operational boundaries:

- [GitHub repository Actions settings](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/enabling-features-for-your-repository/managing-github-actions-settings-for-a-repository).
- [GitHub manual workflow execution](https://docs.github.com/en/actions/how-tos/manage-workflow-runs/manually-run-a-workflow).
- [GitHub draft pull-request creation](https://docs.github.com/en/pull-requests/how-tos/create-pull-requests/creating-a-pull-request).
- [PHP built-in server and Windows worker limitation](https://www.php.net/manual/en/features.commandline.webserver.php).
