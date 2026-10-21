# Bushisa P0–P2 continuation prompts

Prepared 11 October 2026; revised the same day to add the requested password-reset workflow. Operational dates use Africa/Harare; stored application times use UTC.

Repository reviewed: [Takudzwa00kyle/Bushisa](https://github.com/Takudzwa00kyle/Bushisa). Current remote `main` at this review: **`3e5988023b0a85d8de21a05fb49a9e15b32edfa3`**, “Update PDO constants for PHP 8.5”, committed at 17:22:38 UTC / 19:22:38 Harare. A later session must fetch and identify its actual starting commit rather than assume this remains the latest.

**Resume point:** finish the D04/D05 implementation-and-evidence handoff, beginning with a safe, disposable MySQL test environment and genuine route/session/concurrency checks. Several P0 safeguards already exist. Today's deployment, encrypted SMTP integration, database connection, migrations and successful account journey advance that work; they do not establish P0 clearance or the P1 launch gate.

This is a continuation pack for the existing PHP/MySQL app. It supplies bounded prompts and a dated checkpoint; it does not apply patches, change Azure settings, approve policy or certify launch.

**New request — T15:** add password reset using the working `DoNotReply@bushisa.me` sender. This is **requested, not implemented or deployed**. Continue P1-05 through Prompts 15A and 15B, with the [workflow](#password-reset-workflow) and [Azure/Namecheap instructions](#azure-and-namecheap-instructions). Existing signup verification and SMTP success do not prove account recovery.

## Navigation

- [Where work stopped](#where-work-stopped)
- [11 October checkpoint](#11-october-checkpoint)
- [Decision register reconciliation](#decision-register-reconciliation)
- [Execution order](#execution-order)
- [Shared instructions](#shared-instructions)
- [P0 continuation prompts](#p0-continuation-prompts)
- [P1 continuation prompts](#p1-continuation-prompts)
- [P2 continuation prompts](#p2-continuation-prompts)
- [Password reset workflow](#password-reset-workflow)
- [Azure and Namecheap instructions](#azure-and-namecheap-instructions)
- [Handoff template](#handoff-template)
- [Sources](#sources)

Use the shared instructions plus **one** numbered prompt per implementation session. Prompt 00 is the starting prompt. Each session must leave the next prompt and remaining blockers in its handoff. The original playbook remains the detailed acceptance specification.

## Where work stopped

The latest implementation record is [d04-d05-implementation-evidence.md][evidence]. It maps R01–R12 to safeguards added for messaging consent, evidence-preserving block/deletion, terminal code-material destruction, case-scoped access and protective restore replay. Its database-backed checks were **NOT RUN** because MySQL was unavailable. Genuine multi-worker HTTP overlap and an isolated Azure restore were also unproven.

The [decision register][decisions] approves D01 student eligibility and D02 adult onboarding. D04/D05 remain pending; the [revised policy review][policy-review] section 6.1 lists the choices that need actual owners. Technical safeguards and provisional fixtures do not approve retention periods or user-facing promises.

The 9 October [backlog][backlog] contains earlier partial repairs and local passing results. Preserve that history, but attach the tested commit, runtime and environment to each result. An earlier local PASS and a later NOT RUN are separate observations; neither establishes that today's deployed artifact passed the entire suite.

The [operational report][operational] is an older contract audit. The [production study][study] is pinned to `ecf6217ceda74cc257d75483f253862b932dfe9d`. Use them as finding and acceptance references, then inspect current code. Their historical automatic-verification, placeholder-CI and missing-mail descriptions must not be repeated as current findings without reproduction.

The updated [playbook][playbook] makes **U02/P1-01G geographic matching required P1 work**, despite the older study describing geography as a possible P2 enhancement. It also makes **U01/P1-04T confession timestamps required P1 work when confessions remain active**. Existing timestamp serialization is a partial repair, not completion of the pinned display contract.

### Newly identified test and release gaps

These were inspected in current `main`; no destructive suite was executed during this review.

| Current source observation | Consequence for continuation |
|---|---|
| `tests/browser/p0-rendered-flows.spec.js` invokes `DROP DATABASE` and imports the bootstrap schema without an explicit disposable-database preflight. | Add and test fail-closed guards before running it. Inspect the schema's database selection as well as the caller's selected name. |
| `integration_p0_flows.php`, `integration_core_p0_flows.php`, `policy_safeguards_p0_flows.php` and `overlap_db_p0_flows.php` write fixtures without the explicit production/test-database guard used by `integration_smtp_outbox.php`. | Protect every fixture writer and worker, not just SMTP tests. Isolate test credentials and ensure cleanup never touches unrelated data. |
| The browser harness includes database command arguments in failure messages; those arguments can include a password. | Redact diagnostics and avoid exposing credentials or code-bearing mail bodies in evidence. |
| Current [CI][ci] uses PHP 8.4 and `mysql:8.0`. Live application CLI output shows PHP 8.5.6; the Azure MySQL server version has not been captured here. | Record the real server version and test the approved production-bound versions. A client version is insufficient. |
| CI runs signup, policy and SMTP-outbox integration, but omits `integration_core_p0_flows.php`, `overlap_db_p0_flows.php` and the browser suite. | Close meaningful release coverage gaps after harness isolation is safe. |
| Some core tests insert matches, approval rows and messages directly. | These fixtures test selected DB/service behavior; they do not prove the real swipe, approval, send or read routes succeed. |
| Native App Service source deployment is active; GitHub Actions was last observed blocked by billing. | Oryx build success is not the full regression gate. Verify current billing/check status and prevent untested commits from becoming releases. |

### Finding navigation

These are continuation leads, not fresh claims that every historical defect still exists.

| Finding | Current progress/evidence boundary | Continuation prompts |
|---|---|---|
| F01 — Identity/adult onboarding | Code and happy-path delivery/account journey now work; negative, concurrent and acknowledgment checks remain. | 01, 02, 04, 07 |
| F02 — Native PDO failures | Earlier SQL repairs and today's PHP 8.5 SSL fix exist; real route coverage on the selected MySQL server is still needed. | 01, 02, 04, 11 |
| F03 — Schema/upgrades/roles | Fresh Azure base and 001–004 migration execution succeeded; legacy upgrades, interruption and restricted roles remain to prove. | 01, 06 |
| F04 — Current authorization | Shared guards/session-version work exists; logout/login is not two-session revocation evidence. | 02, 04, 15 |
| F05 — Safety/privacy | Shared safeguards are mapped under R01–R12; route, cascade, privacy and overlap evidence remain. | 02, 04, 05 |
| F06 — Deletion/retention | Protective/deletion scaffolding exists; D05, full cleanup and real restore remain incomplete. | 03, 05, 12, 17 |
| F07 — Release safety | Native Azure build, TLS and mail deployment now work; safe web root, meaningful gates and staging/recovery are not established by that success. | 06, 07, 08, 17 |
| F08 — Retrieval/paging | Earlier cursor-offset repair is recorded; stable paging and complete 5,000-profile retrieval still need verification. | 09, 10, 16 |
| F09 — Match/request races | Historical DB overlap evidence is distinct from actual multi-worker form/API overlap. | 02, 11 |
| F10 — Media durability/quota | Actual private Blob, processing, aggregate quota and cleanup evidence have not been supplied. | 12, 16, 17 |
| F11 — UI/API contract | The account happy path was reported working; all advertised safety/product states and device acceptance still need evidence. | 13, 14, 18 |
| F12 — Recovery/abuse/operations | Working SMTP is the transport foundation. T15 now explicitly requests password reset from `DoNotReply@bushisa.me`; no recovery endpoint/proof flow was found in reviewed source. Recovery, limits, histories, alerts and ownership remain open. | 15, 15A, 15B, 16, 17, 22 |
| U01 — Confession times | Stored UTC serialization has a partial repair; complete display/clock/accessibility acceptance remains. | 14, 18 |
| U02 — Geographic proximity | D12 remains pending; verified geography alongside reciprocal matching is required P1. | 03, 09, 10, 16, 18 |

### R01–R12 verification queue

The latest repository handoff did not run the database-backed safety checks. Today's migration and mail observations supersede only their specific blocked items; they do not complete this queue.

| Rule | Evidence still required | Continuation prompts |
|---|---|---|
| R01 | Eligible reciprocal match plus explicit recipient chat approval through real callers. | 01, 02, 04, 11 |
| R02 | Accepted transitions/budgets and no consent restoration on reverse request, unblock or rematch. | 02, 03, 04, 11 |
| R03 | Evidence survives cascades and atomic block/delete failures under genuine overlap. | 02, 04, 05 |
| R04 | Sender-only unsend boundary, every projection and accepted scoped evidence treatment. | 02, 03, 04 |
| R05 | Encrypted code payloads now exist; prove terminal verifier/payload erasure, limits and operational delivery failure handling. | 02, 07 |
| R06 | Either-account deletion triggers correct ordinary relationship cleanup. | 02, 05 |
| R07 | Owned confession erasure precedes author unlink; references and case evidence remain safe. | 02, 05 |
| R08 | Complete environment-specific personal-data inventory and actual purge/access effects. | 05, 06, 12 |
| R09 | Accepted minimal consent proof and independent replay horizon covering all recoverable copies. | 03, 05, 17 |
| R10 | Immutable UTC deadlines, hold/release rules and complete retention-worker execution. | 03, 05 |
| R11 | Case-scoped evidence authorization/audit and usable moderation assignment/access flow. | 02, 04, 13 |
| R12 | Complete independent protective replay, fail-closed reopening and actual isolated Azure restore. | 05, 17 |

## 11 October checkpoint

Evidence labels distinguish **source inspected**, **terminal/log shown**, and **operator reported**. Cloud facts below come from this conversation; this review did not independently query Azure. Save sanitized evidence in the repo's dated checkpoint during Prompt 00. Do not copy credentials, approval codes, personal mailbox addresses or private content from screenshots.

| ID | Work completed or observed today | Evidence and remaining boundary |
|---|---|---|
| T01 | Azure App Service `Bushisa`, resource group `takudzwaportfolio`, Basic Linux, South Africa North; subscription shown as Azure for Students. | Portal/operator evidence. Subscription funding, permitted use, ownership and approved budget still require D06/H04 records. Basic does not provide deployment slots; retain the separate-staging option. |
| T02 | Merged SMTP integration and Composer dependencies, encrypted outbox, CLI dispatcher and migration `004_smtp_outbox`. | Current source inspected. Preserve the integration; do not reapply the old SMTP patch. Delivery failure and concurrency regressions still need current-runtime evidence. |
| T03 | Switched deployment to App Service Build Service/Oryx because GitHub Actions jobs were blocked by billing. Deployment succeeded after an earlier SCM restart interruption. | Azure/operator evidence. The old `5f3db01` deployment was superseded in the reported later sync. Capture the full active deployment SHA before the next release. Successful deployment is not a test-suite PASS. |
| T04 | Corrected Kudu's malformed Git origin from a GitHub API repository URL to the repository clone URL; later sync was reported successful. | Operator evidence. Verify saved Deployment Center source/branch, webhook authorization and a subsequent controlled update. Repairing the cached origin alone does not prove durable automatic sync. |
| T05 | Latest main contains the PHP 8.5 `Pdo\Mysql` SSL-constant compatibility fix with the older-PHP fallback. | Current source inspected at `3e59880`. Application SSH showed PHP 8.5.6. Do not reapply the PDO patch or disable certificate verification. |
| T06 | Connected to `bushi-server.mysql.database.azure.com`, database `bushi-database`, after correcting authentication settings. | Terminal: connection OK; cipher `TLS_AES_256_GCM_SHA384`. Verified TLS does not prove least-privilege DB roles, private-only networking or server-version compatibility. |
| T07 | CA bundle exists in the application container at `/etc/ssl/certs/ca-certificates.crt`; explicit `DB_*` settings override supported `AZURE_MYSQL_*` fallbacks. | Terminal/source evidence. `AZURE_MYSQL_FLAG` is not PDO TLS configuration. Keep actual credentials out of prompts and diagnostics. |
| T08 | Initialized the then-empty database with 10 base tables and applied migrations 001, 002, 003 and 004 successfully. | Terminal: “Migrations complete.” This database now contains working accounts. Do not rerun the destructive schema import or seed/reset it. Migration completion does not prove legacy upgrades or all application regressions. |
| T09 | Configured production environment, HTTPS domain `bushisa.me`, secured bindings, HTTPS-only and TLS settings. | Portal/operator evidence. `APP_ENV=production`, debug disabled and runtime DDL disabled were configured. Verify the effective settings safely; private-path exposure and session persistence remain unproven. |
| T10 | ACS resources `bushisa-communication` and `bushisa-email` use the custom domain and registered sender `DoNotReply@bushisa.me`. | Portal/operator evidence. ACS data location shown as United States while the app is in South Africa North; record the actual data flow for D05/D06/H03 review. Do not infer residency approval. |
| T11 | SMTP configuration, connection and authentication succeeded using `smtp.azurecomm.net`, port 587 and STARTTLS. | Terminal: “Mail configuration: OK” and “SMTP connection and authentication: OK.” Keep SMTP credentials, HMAC key and encryption key stable and private. |
| T12 | Configured scheduled WebJob `bushisa-mail`, minute schedule `0 * * * * *`, cleanup followed by dispatch. Storage/Always On/Kudu-agent settings were part of the working setup. | Operator evidence. Record `WEBSITES_ENABLE_APP_SERVICE_STORAGE=true`, `WEBSITE_SKIP_RUNNING_KUDUAGENT=false` and Always On in the runbook, then capture actual scheduled runs/restart behavior. One successful email does not prove scheduler reliability. |
| T13 | An authorized test inbox received a Bushisa approval-code email; code entry and verification succeeded. | Inbox screenshot plus operator confirmation. Retain redacted proof of the actual approved recipient route, sender/authentication headers, expiry/resend and failure outcomes. SMTP acceptance alone is not delivery or approval. |
| T14 | Adult-onboarding/account journey, logout and signing back in were reported working. | Operator reported. This is a successful happy-path check, not proof of two-session revocation, underage boundaries, forged requests or safety races. |
| T15 | Requested password reset using the existing `DoNotReply@bushisa.me` configuration. | Requested, not implemented/deployed/tested. The inspected dispatcher is signup-specific and rejects verified users; extend its purpose contracts before enabling reset requests. Prompts 15A/15B and the configuration instructions below track this work. |

Current scheduler commands are `php bin/cleanup_policy.php` followed by `php bin/dispatch_mail.php`. **The cleanup script currently handles signup/code/mail cleanup; it is not the complete D05 retention worker** for messages, evidence, media, caches, logs and restore copies.

Use **SSH — Application** for PHP CLI checks. **SSH — Kudu** is useful for deployment metadata and cached repository diagnostics; its shell previously had no `php` executable. Temporary build logs may disappear with build-container restarts; retained deployment records are the evidence source.

### Record corrections needed

- Update the backlog and implementation evidence with T01–T15, exact commits and evidence labels; preserve historical failures and earlier environment results. T01–T14 are today's implementation/operator checkpoint; T15 is a new requested feature, with its own NOT RUN acceptance evidence.
- Update `policy-proposals-d04-d05.md` R05/limitations: encrypted production mail now exists and real delivery was observed. D04/D05 approval and the rest of R01–R12 remain separate.
- Update `docs/azure-smtp-deployment.md`: the patch is deployed, native Oryx is the current provider, PHP 8.5 compatibility is present, persistent App Service storage was required, and signup cleanup is limited. Its “not pushed/deployed” paragraph is historical.
- Keep D11 partial until the remaining sender, header, support/Reply-To, delivery-diagnostics and owner acceptance are documented. Do not mark all D06–D11 approved because infrastructure exists.
- Reconcile `docs/data_retention_policy.md` and public notices with accepted D05 decisions. Existing indefinite-consent and both-accounts-delete wording conflicts with the revised proposal; neither wording is automatically approved by deployment.

## Decision register reconciliation

| Decision | Recorded state | What continuation must do |
|---|---|---|
| D01 | Approved | Preserve the approved student domain/number/email relationship and alumni/inactive eligibility. Mailbox proof is not independent proof of current enrolment or identity. |
| D02 | Approved | Preserve the >=18 boundary, DOB after email approval and underage-blocked state. Execute boundary and current-authorization tests. |
| D03 | Pending | Choose the actual launch scope, including photo slots, confessions and notifications. Remove genuinely deferred features from active UI/API promises. U02 requires explicit scope-change approval if removed. |
| D04 | Pending | Resolve expiry/cooldowns/request budgets, cancel/decline/revoke/rematch/unblock/pause semantics, unsend evidence model and closed-chat access/wording. Record the proposed recovery TTL/cooldown/budget and restricted-account handling without treating this document as owner approval. |
| D05 | Pending | Approve field-level lifecycle, signup/underage expiry, confession erasure, consent proof, holds, deadlines, replay-copy horizon, rights handling and cleanup owners. Add recovery verifier destruction, encrypted reset-mail erasure and bounded code-free event retention. Proposed durations remain proposed. |
| D06 | Pending | Record the actual Azure subscription, app/ACS locations and resources; resolve ownership, continuing funding, budget and hosting/data-flow review. |
| D07 | Pending | Define availability, RPO and RTO; measure them in an isolated restore/rollback exercise. |
| D08 | Pending | Record PHP 8.5.6 and capture Azure MySQL server version, extensions, network topology and session strategy; approve the deployment/test contract. |
| D09 | Pending | Name release, moderation, support, privacy, cleanup, incident and backup responders with coverage/escalation. |
| D10 | Pending | Approve log fields/redaction, telemetry destinations, retention, alert recipients and cost limits. |
| D11 | Partial | Add today's sender, SMTP and inbox facts and T15's decision to reuse `DoNotReply@bushisa.me` for recovery. Finish header/DNS acceptance, monitored delivery, a real support/Reply-To route and secret/scheduler ownership. Same sender does not constitute completed recovery acceptance. |
| D12 | Pending | Approve the area catalogue, consent, precision/freshness, lifecycle, public explanations and bounded proximity parameters before production location collection/use. |

For every owner decision record person, date, reason, policy/version, chosen values, reviewed notice and implementation/evidence links. Facts can be updated immediately; unmade decisions remain pending. Product approval does not substitute for H03 specialist review.

## Execution order

| Step | Prompt | Original task/finding | Proceed when |
|---|---|---|---|
| Record the actual checkpoint | 00 | B00; F01–F12; U01/U02 | Now |
| Make the test rig safe and reproducible | 01 | B01; P0-01/P0-02 | After 00 |
| Execute route/session/safety evidence | 02 | P0-02/P0-04/P0-05/P0-06; R01–R12 | Isolated guarded harness ready |
| Prepare and record owner choices | 03 | D03–D12; H01–H06/H10/H13 | Can be prepared while tests are repaired; owners decide |
| Close reproduced identity/auth/safety gaps | 04 | P0-04/P0-05/P0-06 | Relevant decisions recorded; tests identify gaps |
| Complete lifecycle and cleanup | 05 | P0-07; R08–R12 | D05 accepted for production behavior |
| Finish protected build/staging foundation | 06 | P0-03/P0-08; F07 | Safe test rig and approved infrastructure contract |
| Verify the existing mail integration operationally | 07 | P0-04/P0-08; D11/H06 | Isolated checks and approved test inbox available |
| Review P0 evidence | 08 | P0 exit checklist | All preceding required evidence available |
| Finish public-launch product contracts | 09–14 | P1-01, P1-01G, P1-02, P1-03, P1-04, P1-04T | P0 clearance; relevant scope decisions |
| Add password reset and campus-aware limits | 15, 15A, 15B | P1-05; F04/F12; T15; D04/D05/D11 | P0 clearance for progression; safe implementation preparation can occur earlier, with production issuance disabled |
| Measure/rehearse/prepare launch | 16–18 | P1-06/P1-07/P1-08 | Complete candidate and responsible owners |
| Select measured improvements | 19–22 | P2-01/P2-02/P2-03/P2-04 | Approved pilot and actual evidence |

Do not recode every earlier P0 task from scratch. Complete the missing evidence and repair reproduced gaps. A blocked decision or external check blocks its dependent gate; it does not prevent independent safe code/test preparation.

## Shared instructions

Paste this block before the selected prompt. Attach this document or the dated repository checkpoint created by Prompt 00.

```text
Continue the existing Bushisa PHP/MySQL remediation. Read repository instructions,
current branch/HEAD, working-tree changes and docs/remediation/decisions.md first.
Then read the relevant playbook task and current evidence/backlog. Preserve
unrelated staged/unstaged changes and working features. Refresh the current
remote main/release identity; do not assume an old audit commit is current.

Treat the 11 October checkpoint as dated evidence with stated limits. The SMTP
integration, PHP 8.5 SSL-constant fix, migrations 001-004 and working email/login
journey already exist. Inspect and reuse them. Do not reapply obsolete patches
or repeat cloud setup merely to reproduce earlier instructions. T15 requests
password reset through the same sender; it is not present in the reviewed
release. New reset setting names below are proposed application contracts,
not settings the current code already consumes.

Keep source facts, synthetic execution, operator observations and human decisions
separate. Use PASS / FAIL / NOT RUN / BLOCKED / OUT OF SCOPE for specific checks.
A deployment, migration, syntax pass or suite name cannot stand in for route,
privacy, concurrency, restore or policy acceptance. Record exact artifact,
versions, commands, timestamps and sanitized evidence paths. OUT OF SCOPE needs
an actual approved scope decision and removal from active UI/API promises.

Use only explicitly disposable synthetic databases for resets, fixtures, load,
fault injection and destructive suites. Guard every writer before DB connection,
including subprocesses and browser resets; reject production, missing/ambiguous
targets and unapproved database names. Never import database/bushisa.sql, seed,
reset or run these suites against the working Azure bushi-database. Use additive
migrations, native PDO prepares and verified TLS; do not disable protections to
make a test pass. Keep production runtime DDL disabled and DB roles separated.

No real student records, private messages, precise locations, passwords, codes,
keys or SMTP responses in fixtures/logs/artifacts. Use fake mail or a local TLS
fixture in isolated tests. Real delivery checks use an owner-approved recipient.
Do not rotate production HMAC/outbox keys blindly or enable fake mail there.

D04/D05/D12 values are provisional until actual owners accept them. Preserve
D01/D02 approvals. Do not invent legal clearance, owner decisions, CI results
or external access. Prepare all independent technical work; list missing inputs
and the affected gates precisely. Keep production location collection/use off
until D12 is accepted; verify the effective state rather than assuming it.

Changes pushed to the native deployment branch can go live. Prepare and test
changes on a suitable branch; make the release candidate concrete and reviewable
before any live rollout governed by the release plan. Avoid management changes
that restart SCM during an active deployment. PHP CLI is in SSH—Application;
deployment metadata is accessible through Kudu. Never dump the environment.

Return: task/finding IDs, starting and tested commit/artifact, changed files,
migrations/order, actual check results, pending decisions/external evidence,
rollback implications and one exact next prompt. Update the dated handoff and
backlog without overwriting historical evidence or broadening this task.
```

## P0 continuation prompts

### Prompt 00 — Reconcile the checkpoint and add navigation

```text
Run a continuation of B00, not a fresh implementation of the old audit. Read
decisions.md, backlog.md, d04-d05-implementation-evidence.md, the revised D04/D05
review, the P0-to-P2 playbook and both feasibility reports. Compare current main
and the intended deployed artifact to the 11 October checkpoint in this pack.

Create docs/remediation/checkpoint-2026-10-11.md with T01-T15, evidence type,
commit/environment, completed work and remaining checks. Add a navigation index
in docs/remediation/README.md linking decisions, backlog, policy proposals,
implementation evidence, geography, confession-time contract and this checkpoint.
Link it from the existing README and planning records where useful.

Update stale status paragraphs about SMTP, migrations, PHP 8.5 and deployment.
Retain historical audit/test versions and failures. Update facts in decisions.md
without approving pending decisions. Record native Oryx versus the proposed
GitHub OIDC release baseline, WebJobs versus proposed job infrastructure and the
unverified durability of the repaired deployment source/webhook. Add T15 as
requested password-reset work using DoNotReply@bushisa.me; link P1-05 and
Prompts 15A/15B without marking recovery as already implemented.

Give every F01-F12, U01/U02 and R01-R12 a current status, evidence lead and next
playbook task. Mark today’s account/mail checks as observations with limits.
Document the unsafe browser reset/unguarded fixture writers and CI omissions.
No application repair, production test execution or launch certification in
this task. End by handing off Prompt 01 and the pending owner-choice list.
```

**Done:** a dated, linked checkpoint lets another developer find the real halt and today's changes without rereading the entire deployment conversation.

### Prompt 01 — Protect and restore the MySQL regression environment

```text
Continue B01/P0-01/P0-02 from the blocked MySQL handoff. Before executing tests,
inspect every reset/seed/fixture script and worker. Add a shared fail-closed
disposable-database guard to the PHP suites, browser reset and child workers.
Check APP_ENV, explicit test opt-in and a narrowly allowed test target. Inspect
bootstrap CREATE/USE/DROP statements; they must not select an unrelated DB.
Redact database arguments/code-bearing diagnostics and give fixtures deterministic
cleanup. Prove production, absent opt-in and ambiguous targets are rejected
without any write. Preserve existing production settings and data.

Capture PHP/extensions and SELECT VERSION() from the selected Azure MySQL server
through an authorized read-only check; keep secrets private. Prepare a disposable
matching-version MySQL environment and PHP 8.5-compatible runtime. If unavailable,
provide a reproducible runner and label that execution NOT RUN; do not silently
substitute MariaDB or call the older PHP8.4/MySQL8.0 CI matrix exact compatibility.

Rehearse fresh install, additive 001-004 migrations, rerun/interruption recovery,
representative legacy upgrade and restricted runtime DML privileges. Guarded
bootstrap is for the isolated test target only. Preserve users/messages in upgrades.
Run the existing unit/signup/core/policy/outbox/DB-overlap checks and record actual
results. Determine which tests exercise services versus manual fixture SQL.
Do not claim route or true HTTP overlap coverage from those results.

Deliver the safe runner, version/migration evidence and minimal reproduced failures
for Prompt 02. Update R01-R12 evidence against the tested artifact.
```

**Done:** unsafe targets are rejected, selected-version DB tests can execute reproducibly, and migration/legacy/runtime-privilege results have evidence or explicit blockers.

### Prompt 02 — Prove real routes, two sessions and genuine overlap

```text
Extend the guarded B01 harness for actual form/API authorization and R01-R12.
Use isolated synthetic accounts and at least two independent browser sessions
and DB connections. Run a real multi-worker HTTP server, such as the selected
staging Nginx/PHP-FPM setup. Record process/request timing and application-side
barriers proving overlap; parallel clients against single-worker php -S are
not concurrency evidence. No debug barrier may be enabled in production.

Exercise pending/underage/active/suspended/deleted/role-removed accounts across
protected pages and APIs. Test two live sessions after suspension, deletion,
password change/reset and eligibility changes; assert denial and zero forbidden
writes. Test CSRF, ownership, stale session roles and JSON 401/403 contracts.

Use actual swipe/request/approve/send/read/unsend/block/unmatch/report routes.
Cover reverse requests without autoapproval, current grant generations, retries,
request expiry/cooldowns, overlapping reciprocal likes, approve/revoke and
send/read/block/deletion ordering. Inspect SQL invariants and side effects.
Test mid-transaction failure, evidence survival, unrelated/unassigned moderator
denial, anonymous target resolution and suppressed confession references.

Verify signup wrong/expired/replayed/superseded/wrong-purpose/cross-account codes,
leading zeroes, resend/address-correction races and budgets across generations.
Map each result to the precise route and R rule. Provisional-policy tests stay
labelled provisional. Repair bounded technical defects revealed by these cases,
or hand them off explicitly to Prompt 04. Keep an isolated-restore gap visible;
asserting a seeded fail-closed flag is not an actual replay exercise.
```

**Done:** route-level evidence separates working paths, reproduced defects, provisional policy and missing external checks; overlap and two-session claims are substantiated.

### Prompt 03 — Prepare the owner decision packet

```text
Prepare the concrete decision packet for D03-D12 from current code and the revised
D04/D05 review section 6.1. Preserve approved D01/D02. For each pending choice,
state current implemented behavior, proposed alternative, affected fields/routes,
user notice, tradeoff, responsible reviewer and the task it blocks.

For D04 resolve request expiry, cancellation/decline/re-request cooldowns and
shared budgets, unblock/rematch/pause, unsend originals and closed-chat access.
For D05 include abandoned/underage signup, either-account cleanup, confession
erasure, minimized consent proof, immutable UTC deadlines, holds/release, rights
requests and replay coverage across all recoverable copies. No proposed duration
becomes a legal or approved production requirement by appearing in source.

Include actual Basic Linux/Students subscription, South Africa North app versus
United States ACS data flow, funding/owners, RPO/RTO, runtime/session/network
choices, telemetry/alerts and moderation/support coverage. D11 adds today's real
sender/delivery but lists remaining header/Reply-To/diagnostic acceptance. D12
needs catalogue, consent, freshness/granularity, lifecycle and bounded bonus
approval before collecting production location.

Prepare reviewable decision entries and wording; leave unmade choices pending.
When owners supply decisions, record exact person/date/version/reason and links.
Separate product authority from H03 specialist review. Identify technical tasks
that may safely proceed meanwhile. Do not manufacture approvals or execute live
policy changes as part of preparing the packet.
```

**Done:** owners can decide concrete choices, with all affected gates visible. Preparing the packet is not completing the decisions.

### Prompt 04 — Close identity, current authorization and safety gaps

```text
Continue P0-04/P0-05/P0-06 using the reproduced failures from Prompt 02 and actual
D04/D05 decisions from Prompt 03. Inspect existing services before editing;
preserve today's working SMTP and approval-code flow. Implement only missing
behavior and repair independently established technical defects.

Keep code-entry approval distinct from mail acceptance, membership, age and
policy acknowledgments. Prove approved D01/D02 grammar/domain/age boundaries,
pending restrictions, immutable verification purpose and policy-version records.
Review legacy automatically verified accounts and email pre-reservation/restart
without overwriting passwords or autoapproving identities.

Apply current account/role/session-version checks to every protected caller;
revocation must affect both live sessions. Unify form/API chat and safety
transitions, request budgets, pause/unblock/rematch behavior and sender-only
unsend under accepted values. Blocking/revocation success prevents later sends.
Preserve scoped evidence before cascades; unrelated or unassigned users must
not read it. Finish reachable unmatch/report/evidence-access paths where required.
Use explicit public DTO allowlists and neutral suppressed references.

Repeat only the affected meaningful route/transaction/concurrency tests and
required shared regressions. Record accepted-policy versus provisional cases,
migration/rollback compatibility and remaining moderator/UI work. No missing
policy decision may be silently replaced with a convenient code default.
```

**Done:** current identity/auth/safety failures are repaired and tested across entry points; policy-dependent behavior is tied to real decisions.

### Prompt 05 — Finish lifecycle, retention and protective replay

```text
Continue P0-07 and R08-R12 under accepted D05 decisions. Reconcile
docs/data_retention_policy.md, policy proposals and public notices with actual
behavior. Inventory SQL fields, media/derivatives/versions, sessions, caches,
logs, exports, mail metadata and every recoverable copy. Assign erase/anonymize/
retain-with-purpose-and-expiry plus owner to each category.

Keep immediate deletion restrictions and all-session revocation. Verify either-
account relationship cleanup and owned confession erasure before author unlink.
Preserve only authorized scoped evidence; ordinary DTOs remain inaccessible.
Complete the missing bounded, leased, idempotent retention/cleanup jobs with
retry/backoff, deadlines, completion counters and overdue alerts. The existing
cleanup_policy.php signup cleanup is not sufficient. External media cleanup is
queued after DB commit; do not delete current files before a rollbackable write.

Use fake-clock UTC boundary and hold/release tests; reads/retries/edits cannot
restart retention. Add authenticated rights/export/correction handling with
reviewed access, minimal completion proof and expiry for generated exports.

Implement a durable protective replay source independent of an older restored
application DB, with checkpoint completeness and fail-closed reopening. Prove
synthetic replay of deletion/content erasure, unsend, blocks/suspensions, revoked
consent and expired data; control restored mail/jobs. Leave the actual Azure
restore to Prompt 17 and H08. A row named ledger is not continuity proof.

Return field inventory, job/replay tests, purge evidence, accepted notice changes
and unresolved external storage/log purge verification.
```

**Done:** lifecycle decisions are executable, testable and owned; incomplete cleanup/replay cannot be represented as deletion completion.

### Prompt 06 — Protect the build, web root and staging foundation

```text
Continue P0-03/P0-08 against the actual Basic Linux App Service setup. Preserve
existing cloud resources/settings and build from a pinned commit/lockfile.
Validate selected PHP/extensions, startup configuration, private writable paths,
protected persistent sessions and bounded health/readiness checks.

Inspect actual Nginx startup/configuration and prove that config, internal PHP
helpers, SQL, docs, logs, repository metadata and executable uploads are denied.
Apache .htaccess is not Nginx protection. Preserve public routes deliberately;
test HTTP/TLS/proxy cookies, missing-secret failure and restart persistence.

Record/validate private MySQL routing/DNS, verified TLS, runtime DML versus
migration credentials and actual grants. Verify scoped secret resolution and
runtime identities rather than claiming Key Vault/managed identity from a draft.
Prepare versioned infrastructure/environment configuration and cost inventory
for approved gaps. Use Basic with a separate staging app, or an explicitly
approved slot-capable plan; do not assume slots or a new paid upgrade.

Extend CI after safe isolation: core, policy, SMTP, DB-overlap and applicable
browser tests on approved runtime versions. Prove a deliberately broken relevant
assertion fails the release check. Check whether GitHub billing/check execution
is now unblocked. Until then, prepare and execute an equivalent controlled,
artifact-linked test/release process; Oryx alone is not that process. Define
how native source deployments are prevented from bypassing it.

Prepare the exact staging candidate, migrations, scoped deployment permissions
and rollback procedure. Record live checks unavailable to this agent as NOT RUN;
do not treat a generated template as verified infrastructure.
```

**Done:** private-path, configuration, DB/session and meaningful build-gate evidence exists for an isolated staging candidate, with actual external gaps stated.

### Prompt 07 — Verify mail operation and durable deployment sync

```text
Verify the existing integration, rather than reconfigure SMTP from zero. Compare
remote main, Kudu repository HEAD, full active deployment ID and the running
release/artifact. Inspect saved Deployment Center clone URL/branch and webhook
authorization without disclosing credentials. Confirm a controlled, approved
update reaches staging and record whether automatic sync remains durable after
restart/reconnection. Keep temporary origin repair separate from saved config.

Update azure-smtp-deployment.md with native Oryx, PHP 8.5, migrations 001-004,
Always On, WEBSITE_SKIP_RUNNING_KUDUAGENT=false and
WEBSITES_ENABLE_APP_SERVICE_STORAGE=true. Verify root run.sh/settings.job and
the minute schedule. Capture at least successive scheduled executions, missed-
run/backlog recovery and a controlled worker restart in isolation.

Run current-runtime outbox/TLS failure tests with synthetic transport. Using an
owner-approved inbox, record actual sender and SPF/DKIM/DMARC header outcomes,
code entry, resend/expiry and safe provider-failure/bounce visibility. Verify
payload/verifier erasure and recipient privacy. Measure queue age within code
TTL and actual provider quota; no code or SMTP response appears in diagnostics.
After Prompt 15A, recheck the same scheduler with mixed signup/reset/notification
rows and legacy envelopes. This prompt alone does not add reset functionality.

SMTP acceptance is not delivery; a crash after acceptance before DB commit may
duplicate mail despite stable Message-ID. Do not promise exactly-once delivery.
Prove duplicate/replayed mail cannot double-approve an account. Finish D11/H06
ownership, support/Reply-To decision and alerts; don't invent an inbox that exists
only as a send-only ACS address. Leave full D05 retention status separate.
```

**Done:** artifact sync and scheduled mail behavior have dated evidence; the existing successful email is preserved and remaining D11 checks are resolved or explicitly pending.

### Prompt 08 — Review the P0 exit evidence

```text
Review the exact candidate against the playbook's P0 exit checklist and F01-F07.
Read the dated checkpoint, decisions and R01-R12 evidence. For every requirement
list artifact/version/environment, exact check, PASS/FAIL/NOT RUN/BLOCKED and
evidence link. Include migrations/legacy upgrades, restricted runtime SQL,
code-entry negative cases, two-session revocation, atomic safety/DTO privacy,
field-level deletion/cleanup, protected web root, isolated Azure staging,
sender operation, persistence and required human decisions H01-H07.

Make a concrete blocker list, assign each to its existing task and propose the
next smallest repair. Do not replace unavailable execution with source inspection
or mark an essential safety/privacy requirement OUT OF SCOPE. Today's happy path
and deployment success are individual evidence items only.

Prepare docs/remediation/p0-exit-evidence.md for engineer review. Do not approve
P0 or public launch on behalf of the operator. If the engineer accepts P0 with
all required evidence, hand off Prompt 09; otherwise name the exact repair prompt.
```

**Done:** the engineer can assess a specific P0 candidate and blockers. P0 clearance permits P1 work; it does not authorize public launch.

## P1 continuation prompts

### Prompt 09 — P1-01: Complete retrieval and stable paging

```text
Complete F08/P1-01 in the existing matching service. Reproduce current retrieval
and cursor behavior first; preserve earlier offset fixes. Apply mutual hard
eligibility, active/adult/approved state, discovery choice, pause, blocks and
prior decisions before ranking where practical. Reach an eligible profile after
500 ineligible accounts; increasing the old limit is not a complete repair.

Implement a bounded stable keyset or expiring private snapshot cursor bound to
requester/preferences/catalog/scoring version. Recheck live eligibility/safety
when serving cached results. Define expiry, tampering and refresh semantics;
swipes between requests must not cause repeated/skipped candidates beyond that
documented contract. Validate questionnaire versions/unknown/needs_review answers
on reads; retain truthful heuristic score labels and approved variety rules.

Test 5,000 synthetic profiles, late eligible profiles, tied ranks, empty pools,
swipes/blocks/pauses, stale cursors and private DTOs. Record plans/query counts.
Prepare the ranking/cursor contract for Prompt 10 without replacing the existing
reciprocal matcher or collecting production locations before D12 acceptance.
```

**Done:** stable distinct pages and complete eligible retrieval pass under the current scoring/version contract.

### Prompt 10 — P1-01G: Add required geographic proximity

```text
Complete U02/P1-01G after D12/H13 decisions and stable retrieval. Audit active
fields/services/UI first and update geographic-matching.md with verified status.
A location column alone is not geographic ranking. Reuse the existing reciprocal
matcher, approved questionnaire factors and variety strategy.

Implement approved manual-area choice and optional one-off browser approximate
location, explicit application consent, server validation/coarsening, freshness,
withdrawal and ordinary-matching fallback. No raw fixes, movement history,
IP inference or background tracking. Denied/stale/absent location must not block
signup or default soft-mode matchmaking.

Apply mutual eligibility first, then the accepted bounded proximity bonus.
The playbook's development example is R + 0.10/(1+d_km/10); its weight, scale,
coarse cells and 24-hour device lifetime require D12 approval. Preserve far
stronger matches and unknown-location candidates. Add hard mutual distance
limits only if approved. Bind private snapshots to location/scoring versions
and invalidate derived use on withdrawal/deletion.

Test independent unit/axis/distance fixtures, equal-score near/far cases, stronger
far pairs, blocked nearby users, missing/stale/withdrawn fixes, permissions,
cursor changes and sparse-area/probing privacy. Keep coordinates/cells/exact
distances out of public HTML/JSON/cursors/logs. Use PHP/MySQL local distance
computation and measured plans; no per-pair vendor calls. Extend lifecycle/
restore/load cases and prepare actual-device H13 checks. U02 is required P1,
not an enhancement to postpone silently to P2.
```

**Done:** approved location-aware ranking complements reciprocal matching, with privacy, fallback, lifecycle, paging and H13 evidence.

### Prompt 11 — P1-02: Finish match and permission races

```text
Complete F09/P1-02 using Prompt 02's route-overlap evidence and accepted D04.
Inspect existing pair locks/services before editing. Define one authoritative
transition model for like/pass, mutual match and every request/chat state.
Use canonical ordering, first-row creation handling, compatible locks, unique
constraints and bounded deadlock retries. Unique matches alone do not prove
two uncommitted reciprocal likes produce a match.

Make notifications/outbox side effects idempotent and retry responses truthful.
Preserve original transition timestamps, current generation and approved contact
budgets. Form/API callers share send/read/length/rate and current permission checks.

Use barriers with independent DB connections and real HTTP workers for reciprocal
likes, repeated same-like, opposite requests, approve/revoke, rematch/unblock and
send/read/block/deletion races. Prove exactly one intended match/notification and
no newly authorized send after acknowledged revoke/block. Preserve scoped evidence
and suppress obsolete notifications. Return linearization rules and real evidence,
not a claim based only on direct fixture INSERTs.
```

**Done:** retries and overlaps preserve exactly one intended match outcome and current chat authorization across all callers.

### Prompt 12 — P1-03: Make media durable and cleanup safe

```text
Complete F10/P1-03 under D03/D05's approved slot count and quota. A truthful
single-photo launch is possible if explicitly selected; otherwise implement
the advertised slots. Integrate durable private Azure Blob access through the
current upload/profile/deletion services and verified scoped identity/adapter.
Store opaque object keys; do not commit credentials or permanent SAS URLs.

Decode/re-encode every allowed format, strip metadata and bound pixels, bytes,
CPU/memory and output size. Verify required image extensions. Enforce aggregate
quota under concurrent replacement, including the approved treatment of pending
and derivative objects. Stage upload, commit reference, then queue old-object
cleanup; failures preserve the current image and clean compensating orphans.

Test malformed/oversized/spoofed files, every allowed format, metadata removal,
fourth-slot/quota attempts, concurrent updates, DB/Blob failures, retry/restart,
blocked/deleted media access and versions/soft-deleted copies. Inventory tracked
uploads before removing them; do not rewrite history without an actual decision.
Integrate completion/overdue evidence into the D05 worker and restore plan.
```

**Done:** private media survives restart, quota holds under overlap and failure recovery does not lose current images or leave uncontrolled copies.

### Prompt 13 — P1-04: Finish active UI/API and safety journeys

```text
Complete F11/P1-04 against actually loaded pages/scripts. Preserve the existing
server-rendered model with progressive enhancement unless an approved decision
changes it. Inventory method/auth/CSRF/request/response contracts. Remove stale
scripts or satisfy their real contracts; merely loading dormant JS is insufficient.

Finish approval-code/resend/correction/onboarding states, discovery empty reasons,
like/match, chat request/approve/revoke, bounded send/read/unsend/history,
block/report/unmatch, deletion, moderation evidence access and promised notification
read/preferences. Shared services own limits and error states. Remove genuinely
deferred controls/claims and implementation notes from product text. Integrate
Forgot password, reset entry and return-to-login states only when Prompt 15A's
real routes and server contracts exist; execute Prompt 15B's acceptance matrix.

Complete manual-area/location denial/expiry/withdrawal UI under D12, without
requiring GPS for registration. Complete confession-time integration through
Prompt 14 if confessions stay active. Bound polling, back off on errors and pause
hidden tabs while keeping forms usable.

Run guarded browser checks at 320/390/768/1024/1440 CSS px, keyboard/focus and
slow/interrupted networks. Record actual device/assistive acceptance still needed
under H09. Validate visible behavior, server enforcement and private projections.
```

**Done:** advertised active journeys execute through consistent contracts and usable safety controls; remaining H09 judgments are explicit.

### Prompt 14 — P1-04T: Complete confession timestamps

```text
Complete U01/P1-04T without discarding the existing created_at/UTC serialization
repair. Trace a synthetic post through DB, create/feed/detail API, server HTML
and active formatter. Preserve the original server-set instant across reload,
edit, reaction and retry; edited_at is separate. Verify PHP/MySQL UTC semantics
and client-time rejection. Unknown legacy times stay unknown; never backfill
them with migration time or label invalid values now.

Implement the playbook's pinned X-style boundaries through one injectable-clock
formatter: now below 1 second, seconds below 60, minutes below 60 minutes, hours
below 24 hours, then original Mon D / Mon D, YYYY. Provide full posting time on
focus/hover/detail/touch, timezone-aware English formatting and truthful no-JS
fallback. Current contract notes about yesterday/empty invalid values must be
reconciled with the newer pinned specification, not treated as completion.

Test 0/.999/1/59/60/3599/3600/86399/86400 seconds, New Year, leap day, viewer
midnight, Harare and a DST zone, invalid/offset-free/future dates, documented
small skew, cached server time, tab sleep/resume, dynamic cards and timer cleanup.
No per-label network polling. Capture DB/API/browser invariance and H07 clock
evidence; prepare H09's dated English X comparison without asserting parity.
```

**Done:** saved instants remain stable, labels advance correctly and full times are accessible. U01 blocks launch of active confessions until accepted.

### Prompt 15 — P1-05: Recovery and campus-aware abuse controls

```text
Complete F12/P1-05 in three bounded sessions: Prompt 15A implements T15 password
reset, Prompt 15B proves it and prepares its Azure handoff, and this parent
prompt completes shared abuse controls and authenticated password change.
Use DoNotReply@bushisa.me with the existing SMTP transport and session guards.

Unify account/IP-aware throttles for login/signup/code/recovery/upload/report/
chat/discovery and approved location changes. Campus NAT is shared; successful
login must not erase unrelated users' failure history. Validate ingress/proxy
IP trust, body/length limits, bounded pruning and shared-store requirements
before scale-out. Keep identities/coordinates out of telemetry.

For authenticated password change, require current-password/recent-auth proof,
CSRF and the shared password policy; revoke prior sessions and competing reset
proof atomically. Recovery cannot substitute for a suspension appeal, mailbox
change, missing enrolment evidence or adult-onboarding clearance.

Test IP spoofing, enumeration and legitimate shared-IP traffic versus attacks.
Prepare an owned assisted-recovery route for people who cannot access their
registered inbox. Do not invent a support mailbox or bypass identity checks.
Record residual decisions and integrate failure/queue alerts into Prompt 17.
```

**Done:** Prompts 15A/15B pass; password change and campus-aware limits preserve account restrictions and have an owned recovery/escalation procedure.

### Prompt 15A — P1-05: Implement password reset with the existing sender

Use the [password reset workflow](#password-reset-workflow) and [configuration contract](#azure-and-namecheap-instructions). Routes, migration names and new environment keys below are proposed; inspect current main and choose the next available migration number.

```text
Implement T15 for the current Bushisa app, using MAIL_FROM_ADDRESS=
DoNotReply@bushisa.me and the existing PHPMailer/ACS SMTP credentials. Keep
signup-code verification working. Do not create another mail provider.

Inspect php/auth.php, php/password_policy.php, php/mail_delivery.php,
bin/dispatch_mail.php, bin/cleanup_policy.php, session guards, schema/migrations
and actual login routes. The current outbox binds approval_code_id and
Bushisa/signup-email/v1 context; its dispatcher requires pending_email and
is_verified=0. Reusing that queue for verified users cannot implement recovery.

Add real Forgot password/reset forms and protected POST handlers. Build links
from validated APP_BASE_URL=https://bushisa.me, never a request Host/header.
Return the same existence-neutral request outcome for known, unknown and
ineligible addresses, with comparable response work and bounded request sizes.
Throttles must not disclose account existence. Never lock out/change a password
just because somebody requested mail. Target the server-owned registered
verified recipient, not a caller-selected destination or account ID.

Create an additive reset-proof migration (005_password_recovery.sql only if
005 is still free). Use a separate password_reset purpose, 32 random bytes,
SHA-256 token digest, account/recipient binding, issuance generation, expiry
and consumed/invalidated state. These are proposed implementation choices.
Use shared password validation/hash helpers. Serialize issuance per account;
accept only its current unexpired generation. Reuse neither the signup code
nor SIGNUP_CODE_HMAC_KEY. Add validated reset config and .env.example values;
default issuance off until the compatible schema/worker is ready.

Version mail envelopes and add explicit signup_approval/password_reset/
password_changed contracts. Bind authenticated encryption to purpose, proof/
event ID and recipient, with distinct Message-ID purposes. Preserve pending
legacy v1 signup rows. Extend outbox dispatch and cleanup for each purpose;
never make a fake approval_code_id or relax signup eligibility to send resets.
Use MAIL_OUTBOX_KEY for encrypted reset bodies and discard obsolete rows.
Erasure must cover sent, expired, superseded and consumed link payloads.

Proposed link transport uses /reset_password.php#token=<opaque-proof>.
A small first-party script moves the fragment into the form and clears the
visible URL without submitting. Provide an accessible manual-key form when
JS is unavailable; include the same copyable key in the mail. Do not put proof
in query/access logs, analytics, local/session storage or third-party scripts.
Use no-store/no-referrer and a restricted script policy on recovery pages.
A GET, scanner visit or form display cannot consume/reset anything.

Only explicit CSRF-protected new-password POST may succeed. Lock/revalidate
account state, recipient, proof generation, expiry and session version. Commit
password_hash update, proof consumption, competing-proof/outbox invalidation,
session_version increment and a code-free password-changed notification event
atomically. Two overlapping valid requests may produce only one accepted
password change. Dispatch notifications after commit; SMTP failure must not
reverse a committed reset. Do not auto-login; return to normal sign-in.

D01/D02/account restrictions stay enforced. Reset cannot verify email, finish
DOB, unsuspend, undelete or change the registered address. Default-deny deleted,
underage-blocked and otherwise disallowed accounts. Document the proposed
verified-but-onboarding and suspended-account handling for actual owner review.

Prepare a mixed-version rollout with PASSWORD_RESET_ENABLED=false: compatible
migration and dispatcher first, prove the upgraded WebJob is running, then
enable issuance on the approved release. An old signup-only worker must never
see new reset rows. Keep production runtime DDL off and preserve the live DB.
Update decisions/backlog, recovery contract, mail guide and handoff, separating
source completion, synthetic results and still-unperformed cloud checks.
```

**Done:** a purpose-separated recovery implementation is reviewable; signup behavior and legacy queue compatibility are preserved. Production reset acceptance still requires Prompt 15B.

### Prompt 15B — P1-05: Prove recovery and prepare its Azure rollout

```text
Verify Prompt 15A on the guarded, disposable environment from Prompt 01, with
the approved PHP/MySQL runtime and synthetic mail. Execute real forms/routes,
not direct fixture inserts alone. Record the exact tested release and migration.

Cover equal known/unknown/ineligible request responses, bounded/campus-aware
throttles, forged destination/account/Host headers, CSRF and password-policy
failures. Exercise expired, superseded, used, cross-account and wrong-purpose
proof; signup codes cannot reset and reset proof cannot approve email.
GET/scanner/link-preview requests must leave password/proof state unchanged.
Verify both the fragment-link path and accessible manual-key fallback.

Use two genuine application sessions and overlapping POST workers: exactly one
reset commits, the old password fails, the new password works, and every old
session is denied by protected routes. Recovery never changes adult/verified/
suspended/deleted state or grants access to a restricted account. Email-change
or account-state races invalidate stale proof; refreshing a preview does not.

Prove fake/local-TLS delivery failures, retries, worker restart, old/new envelope
coexistence, backlog expiry, encryption tampering and terminal payload/verifier
erasure. Notification retries cannot repeat the password change. Provider
acceptance/Message-ID is not inbox delivery or exactly-once email. Check web/
proxy/application/provider diagnostics for proof and credential leakage.

Prepare the concrete additive migration, worker/code order, flag settings and
rollback instructions below. Rollback disables new issuance without restoring
old passwords, reactivating proof or dropping recovery tables. Update the real
public route allowlist without exposing config/helpers/CLI directories.

On an operator-approved candidate and recipient, record an actual reset email
from DoNotReply@bushisa.me, complete reset, verify session revocation/new sign-in
and receive the password-changed notice. Preserve redacted sender/authentication
headers and timestamp/artifact evidence, not the token or mailbox credentials.
Report cloud checks NOT RUN/BLOCKED until they occur. Keep D04/D05/D11 owner
values, monitored support and H07/H08/H09 judgments explicit.

Refresh Prompt 07's scheduler evidence and Prompt 18's release matrix. Hand off
the remaining parent Prompt 15 abuse/password-change work; don't declare the
whole P1 launch gate passed from a successful reset.
```

**Done:** recovery has route/concurrency/session/mail evidence on one candidate, and the operator has an exact, ordered rollout with remaining approvals visible.

### Prompt 16 — P1-06: Measure query work and capacity

```text
Complete P1-06 with deterministic synthetic 5,000-account data and realistic
questionnaires, swipes, reports, notifications and conversation histories.
Inspect EXPLAIN/query counts for discovery/matches/chat/admin; bound histories
and aggregates, remove N+1 work and add only justified indexes. Preserve all
authorization/privacy regressions if retrieval or cache behavior changes.

Include the approved geography implementation with clustered/sparse/far/unknown/
stale locations and concurrent updates. Prove complete soft-mode fallback and
compare cost/latency with and without the bonus. No real student coordinates.

Agree the staging workload and D07 targets before running controlled load.
The study suggests 100 mixed concurrent users and 500 only if needed; 5,000
registered accounts is not 5,000 concurrent users. Model actual polling, worker
cadence and mail quotas. Proposed p95 <1 second is a target for owner acceptance,
not an existing SLA.

Report p50/p95/p99, throughput, unexpected errors, CPU/memory, DB connections,
query latency/deadlocks, job backlog and measured usage. Separate expected denials
from faults and require zero authorization/integrity failures. Identify remaining
headroom and bottlenecks before recommending more infrastructure.
```

**Done:** representative staging measurements substantiate the selected capacity and agreed targets; estimates are distinguished from measured results.

### Prompt 17 — P1-07: Rehearse release, alerts and restore

```text
Complete P1-07 against the tested artifact and approved D07/D09/D10. Finish a
gated staging-to-production runbook compatible with native Oryx/current repository
plan, or the approved replacement pipeline. Separate provisioning, deployment,
runtime and migration permissions. Revalidate durable source sync, tests,
secret resolution, worker configuration and artifact identity. No DB resets.

Configure redacted release-tagged logs and owned alerts for errors/latency,
DB health/storage/connections, mail/backlog, missed jobs, overdue cleanup and
backup freshness. Verify an injected staging failure reaches the actual primary/
backup responder and produces a usable triage decision. A configured alert is
not proof of receipt. Use approved telemetry fields/retention/cost limits.

Rehearse artifact rollback and Azure MySQL restore into an isolated new server,
with matching media/config state and no real traffic/outbound mail. Replay the
independent protective ledger and expiry before any traffic or worker resumes.
Prove deleted accounts/posts, unsent data, blocks/suspensions and revoked consent
cannot return; missing/incomplete replay fails closed. Test job retry/overlap,
endpoint/credential rebind and secret rotation safely. Measure recovery time/data
loss against RTO/RPO and record actual H08 witness evidence.

Code rollback is not DB rollback; a slot swap does not undo writes. Keep Basic
artifact rollback and any approved maintenance plan truthful. Return tested
procedures, artifacts, alert receipt and unresolved recovery gaps.
```

**Done:** actual staging release, alert receipt, rollback and data/media restore prove the operating procedure and preserve protective state.

### Prompt 18 — P1-08: Prepare the launch decision

```text
Assemble docs/remediation/release-evidence.md for one exact candidate. Trace
F01-F12, U01/U02, every active feature, P0/P1 checklist and H01-H13 to dated
artifact-specific evidence. Use PASS/FAIL/NOT RUN/BLOCKED/OUT OF SCOPE honestly.
Approved exclusions require removal from active UI/API promises; required
safety/privacy and U02 cannot be silently waived.

Include migration order, configuration names without secrets, runtime/SKU/
regions, real signup and T15 recovery mail/worker evidence, two-session revocation
for password reset, concurrency/privacy tests,
geography, active confession-time acceptance, media/lifecycle, approved load,
alert responders, rollback/restore and policy/support/moderation readiness.
Record P0 revalidation on this same candidate rather than an older version.

Prepare a concrete go/no-go memo for the responsible engineer/operator: cohort,
coverage, observation period, success measures, abort criteria, rollback authority
and remaining blockers. Unauthorized disclosure, safety-control failure, corrupt
state or inability to recover are stop conditions, not engagement tradeoffs.

Do not self-approve launch or declare legal compliance. Capture actual H11
acceptance if supplied. Otherwise return the reviewable decision and exact
blocked items. P2 begins only after an authorized controlled pilot produces
the evidence its selected task needs.
```

**Done:** a named owner can decide a concrete launch with all mandatory evidence and operating coverage visible.

## P2 continuation prompts

P2 is selected from pilot evidence; it is not permission to defer P0/P1 safeguards. Any new safety/correctness regression returns to a blocking repair. If D07 requires HA before launch, promote that work to P1.

### Prompt 19 — P2-01: Evaluate reciprocal and exposure quality

```text
Use approved minimized pilot metrics and synthetic fixtures to evaluate eligible
pool coverage, empty results, exposure distribution, mutual-like outcomes and
accepted meaningful-connection/safety measures. Preserve mutual eligibility,
blocks, age, consent and the already-complete P1-01G proximity baseline.

Select one small scoring/rotation/geographic-weight improvement justified by
evidence. Version parameters/catalogs, evaluate offline and prepare a limited
reversible experiment with fallback, cost bounds and stop rules. Require the
applicable owner decision for new data use/live experimentation. Do not collect
precise tracking, private message training data or infer romantic-success gains
from a small biased sample. Test missing location, uneven exposure and hard
constraints. Implement only the accepted change, behind a reversible flag.
```

**Done:** one measured improvement preserves safety, privacy and bounded cost, with genuine H12 review.

### Prompt 20 — P2-02: Complete one approved optional feature

```text
Select exactly one pilot-supported enhancement approved under D03: additional
photos, notification preferences, browser push or another bounded feature.
Check whether it was already promised at launch; if so, fix its P1 gap rather
than relabel incomplete launch behavior P2. State user value/dependencies.

Reuse media quota/access/cleanup for photos and authorized deduplicated events
for notifications. Push needs explicit permission, private subscriptions,
unsubscribe/revoke and safe lock-screen defaults; declined push must leave
in-app functions usable. Finish real UI/API, duplicate/failure, privacy,
accessibility/mobile and deletion cases. Provide feature flag, rollback,
support and cost implications. Do not silently turn optional choices into
required onboarding or advertise still-disabled behavior.
```

**Done:** the selected feature works end to end, honors choices and is reversible.

### Prompt 21 — P2-03: Scale or add HA only when justified

```text
Use observed workload/incidents and accepted D07 targets to decide whether
tuning, additional app instances or supported regional HA is warranted. If
the target was required for launch, move the task to P1. Verify current Azure
SKU/region availability, service lifecycle, PHP client support and costs from
primary sources before proposing a concrete change.

Before scale-out prove shared sessions/revocation, distributed throttles,
stateless durable media and single-claim jobs. Exercise cross-instance auth,
instance loss, DB reconnect/failover, repeated jobs, quota enforcement and
rollback. Distinguish HA from backup/region recovery; report remaining single
points of failure. Prepare a tested, reviewable plan within approved budget;
retain the simpler deployment when measurements support it.
```

**Done:** a justified availability/capacity change meets measured targets without new cross-instance authorization or duplicate-work defects.

### Prompt 22 — P2-04: Establish the operating cadence

```text
Create an owned cadence for PHP/MySQL/dependency/runtime patches, permission
reviews, stable-key rotation, retention cleanup, restore drills, moderation/
rights handling and runbook review. Record responsible people, next dates,
evidence needed and escalation when work is missed.

Build a metered cost model covering app, DB, staging, storage/egress, jobs,
email, telemetry, recovery copies and human support. Verify offer entitlement/
expiry and provider lifecycle from current primary sources; do not assume
student credits or alerts guarantee funding or stop spend. Review the existing
ACS provider's long-term plan without replacing today's working integration
solely because an old document is stale.

Use pilot evidence to reduce noisy alerts, unnecessary polling, slow queries
and orphan copies. Preserve reviewed telemetry/retention and human case judgment.
Document safe upgrades and rehearsed rollback; move new security/correctness
problems back into release-blocking work.
```

**Done:** operations, funding and maintenance have actual owners and dated evidence rather than an unexecuted checklist.

## Password reset workflow

**Status:** T15 is requested P1-05 work. Reviewed main still has working signup mail, but no implemented password-reset route/proof lifecycle. The proposed flow below becomes a product promise only after Prompts 15A/15B and the relevant release checks pass.

| Stage | Intended user behavior | Server contract |
|---|---|---|
| Request | Select **Forgot password?** from sign-in and submit the registered email. | CSRF-protected, bounded request; existence-neutral response. Do not change password/account state merely on request. |
| Email | Receive **Reset your Bushisa password** from `Bushisa <DoNotReply@bushisa.me>`. | Queue an encrypted recovery message through existing ACS SMTP, addressed to the verified recipient owned by the account. |
| Open | Follow the HTTPS reset link, or use its copyable recovery key on the accessible form. | Proposed `/reset_password.php#token=<opaque-proof>`; opening/previewing never consumes it. Fragment handling is first-party only. |
| Confirm | Enter and confirm a new password, then explicitly submit. | Separate recovery proof; atomic validation/change; existing password policy and account restrictions apply. |
| Finish | See success and return to the regular sign-in page. | Consume proof, invalidate competitors, revoke prior sessions and queue a password-changed notification. No automatic login. |
| Expired/reused | Request a fresh email through the same flow. | Clear retry wording without exposing accounts or accepting obsolete proof. Retried email delivery does not extend proof expiry. |

The request response should say, for example, **“If an eligible account matches, a reset email will be sent.”** It must not claim that delivery occurred merely because a row was queued. Email text must identify Bushisa, explain the actual expiry and how to ignore an unrequested email, and use an approved receiving support route if one exists. No password is sent in either message.

The workflow follows the general request/reset separation in [OWASP recovery guidance][reset-guidance]. Bushisa-specific choices—proof size, generation rules, queue purposes, proposed limits, fragment transport and release order—are implementation proposals in this pack. The [session guidance][session-guidance] and [logging guidance][logging-guidance] inform session revocation and secret-free evidence. The browser does not send a URL fragment with its resource request ([MDN][fragment-guidance]); the first-party script must still protect the proof from client telemetry and submit it only in the intended POST.

### Proposed recovery contract

| Item | Proposed implementation contract | Acceptance boundary |
|---|---|---|
| Recipient | Current server-owned, verified registered email. | A supplied destination/account ID cannot redirect recovery. Email changes invalidate prior proof. |
| Proof | `random_bytes(32)`, encoded for the link/form; store its SHA-256 digest and binding metadata. | Distinct from signup codes. The raw proof exists only for the user's message/form and encrypted pending mail; never in ordinary logs. |
| Expiry | 1,800 seconds (30 minutes) from issuance, not dispatch. | Proposed D04/D05 value; delayed delivery cannot refresh it. |
| Issuance | Latest generation only; 60-second resend cooldown; proposed limit five issuances per account/address per hour. | Record accepted values; apply comparable unknown-address behavior and campus-aware ingress/global budgets. Requests cannot revoke valid login sessions. |
| Redemption | One explicit protected POST; password and session-version change commit with proof invalidation. | Real overlapping requests and two-session tests, not source inspection alone. |
| Restricted accounts | Preserve verification, DOB/age, suspension, deletion and other authorization state. | Document allowed-state behavior. Deny unsupported states by default; owner review cannot be replaced by mailbox possession. |
| Mail purposes | Separate `signup_approval`, `password_reset` and `password_changed` contracts with legacy signup compatibility. | Existing dispatcher is signup-specific; schema/worker changes are essential. |
| Cleanup | Erase obsolete recovery verifier material and secret-bearing mail payloads; retain only approved bounded metadata. | Extend cleanup/restore protections and D05 schedules; do not treat present signup cleanup as coverage. |
| Feature gate | Issuance remains off until compatible schema/worker and acceptance evidence are ready. | Environment variables alone do not create routes, migrate tables or upgrade a worker. |

### Mail compatibility and release boundary

The inspected [mail implementation][mail-source] authenticates encrypted bodies with `Bushisa/signup-email/v1`, links rows to `student_email_approval_codes`, creates signup-specific Message-IDs and dispatches only while the user is unverified/`pending_email`. A verified-account reset row routed through that code would be discarded. Extend the contracts by purpose; keep the signup restrictions intact.

Deploy the compatible code and additive schema with reset issuance disabled. Ensure old worker processes have stopped and the scheduled WebJob runs the new dispatcher before creating recovery rows. Preserve already queued legacy signup mail. Treat email delivery as retryable: a duplicate message cannot cause a second password change. Complete the controlled rollout through Prompt 15B and include recovery in Prompt 18's release evidence.

## Azure and Namecheap instructions

**For this document update, no cloud setting has been changed.** Reusing the already working verified sender/domain means no additional SMTP identity or Namecheap record is normally required solely because the email now contains a reset link. This is an inference from the existing deployment and [Microsoft's verified-sender SMTP contract][azure-smtp-reference], not an independent export of your Azure/DNS settings.

### Azure — keep the existing mail configuration

In **App Services → Bushisa → Environment variables → App settings**, retain the working settings below. Show only non-secret values when checking them; do not paste credential values into a ticket or handoff.

| Existing setting | Value/action |
|---|---|
| `MAIL_PROVIDER` | `smtp` |
| `MAIL_FROM_ADDRESS` | `DoNotReply@bushisa.me` |
| `MAIL_FROM_NAME` | `Bushisa` |
| `SMTP_HOST` | `smtp.azurecomm.net` |
| `SMTP_PORT` | `587` |
| `SMTP_ENCRYPTION` | `tls` (the app's existing STARTTLS configuration) |
| `SMTP_USERNAME`, `SMTP_PASSWORD` | Reuse the current working ACS SMTP identity/secret; no second identity is needed just for reset messages. |
| `APP_BASE_URL` | `https://bushisa.me`; validate as the canonical link origin in code. |
| `MAIL_OUTBOX_KEY` | Preserve the existing encryption key; version the purpose/context rather than blindly replacing the key. |
| `SIGNUP_CODE_HMAC_KEY` | Preserve signup's independent key; the proposed high-entropy recovery digest does not require reusing it. |
| `MAIL_REPLY_TO` | Only an actual receiving address, if supported and configured; otherwise keep unset until the D11 support decision. A send-only address is not a monitored support inbox. |

The existing SMTP host, port and verified-domain sender are documented by Microsoft. The remaining application setting names/behavior come from the inspected repo and today's setup, not from Azure-defined password-reset functionality.

In **Email Communication Services → bushisa-email → Provision domains → bushisa.me → MailFrom addresses**, check that the registered sender is still available. Check the domain remains verified and connected to **bushisa-communication**. If signup mail still works through these resources, there is no need to create a second resource, register another sender or change Entra credential permissions for the same outbound identity.

### Azure — add reset settings with the implementation

These names are **proposed, currently unimplemented application settings**. Prompt 15A must wire them into validated configuration and `.env.example`. Do not add them expecting today's deployed code to provide reset.

| Proposed setting | Initial rollout value | After accepted recovery checks |
|---|---|---|
| `PASSWORD_RESET_ENABLED` | `false` | `true` to enable new issuance on the approved candidate |
| `PASSWORD_RESET_TTL_SECONDS` | `1800` | Accepted expiry value; document any change |
| `PASSWORD_RESET_RESEND_COOLDOWN_SECONDS` | `60` | Accepted cooldown |
| `PASSWORD_RESET_MAX_REQUESTS_PER_HOUR` | `5` | Accepted per-account/address budget; separate campus/global limits remain part of Prompt 15 |

Adding these app settings does not require a new DNS name, OAuth callback, Azure end-user login provider or SMTP secret. This is the PHP application's recovery flow. Verify the final setting names against the actual implementation before applying them.

Use this order for the concrete release prepared by Prompts 15A/15B:

1. Finish the guarded synthetic tests, migration compatibility and exact candidate evidence. Confirm an isolated rehearsal and the operator's release/backup procedure; keep the production feature off.
2. Add the implemented reset settings with `PASSWORD_RESET_ENABLED=false`. Let application/SCM management changes settle and confirm health before starting deployment; do not apply more settings during an active Oryx build.
3. Temporarily pause the scheduled mail worker for the reviewed migration/deployment window; signup mail may queue during this short window. Deploy the compatible code, preserving live data and existing credentials. Record the exact active artifact, not only the cached Git HEAD.
4. In **SSH — Application**, use the reviewed migration command to apply only pending additive migrations. For the existing runner this is:
   ```bash
   cd /home/site/wwwroot
   php bin/migrate.php
   ```
   Confirm the selected new migration succeeded. Do not import `database/bushisa.sql`, recreate the database or run fixture suites on `bushi-database`.
5. Verify the new cleanup/dispatcher supports all purposes and old envelopes, then resume **bushisa-mail**. Keep the minute schedule, Always On, `WEBSITE_SKIP_RUNNING_KUDUAGENT=false` and `WEBSITES_ENABLE_APP_SERVICE_STORAGE=true`. If `run.sh` still calls the same two scripts, no new job or changed schedule is needed:
   ```bash
   cd /home/site/wwwroot
   php bin/cleanup_policy.php
   php bin/dispatch_mail.php
   ```
   Update the uploaded job only if the command/entry-point contract changes. Prove scheduled execution of the upgraded worker before enabling issuance.
6. Include the actual public recovery routes in the reviewed web-root allowlist; keep config, helpers, migrations and CLI paths private. Confirm recovery-page cache/referrer/script policy and secret redaction. No reset proof should be present in access logs or exception reports.
7. After the release prerequisites are accepted, enable issuance and run Prompt 15B's authorized inbox check. Capture the real sender headers, reset, old-session denial, new sign-in and password-changed message. Confirm queue ages remain below expiry and combined signup/reset traffic stays within actual ACS limits.
8. Record rollback behavior. Disabling issuance must not restore old passwords or resurrect consumed proof. Keep the compatible dispatcher/schema during a feature rollback; reverting to the old signup-only worker requires first resolving all new-purpose pending rows safely. Do not drop recovery tables as an emergency rollback.

Do not create a second scheduled worker just to send resets. The current job can serve the new purposes after its code is upgraded. Extend redacted queue/expiry/failure monitoring and support ownership through Prompts 07/17; a single inbox success does not establish continuous delivery.

### Namecheap — normally no change

Keep the working `bushisa.me` website records, Azure ownership verification and mail-authentication records. A new path such as `/reset_password.php` is application routing, not a new DNS host. Sending a second type of message from the same approved address does not require buying a mailbox or adding another SPF/DKIM record.

Only revisit DNS if domain verification fails, received headers show an authentication problem, the sender/domain changes, or you deliberately add a receiving support service:

| Condition | Instruction |
|---|---|
| Existing Azure verification/SPF/DKIM needs repair | Read the exact records generated for `bushisa.me` in Azure's domain-verification view. At the authoritative DNS provider, enter the provided TXT/CNAME types, hosts and targets. Do not invent a selector or substitute records for another mail vendor. |
| Namecheap provides authoritative DNS | For BasicDNS/PremiumDNS/FreeDNS, use **Domain List → Manage (bushisa.me) → Advanced DNS → Host Records**. Namecheap's Host field is relative: use `@` or the specified selector without appending `.bushisa.me` again. Use Azure's actual record type. See [Namecheap's instructions][namecheap-dns-reference]. |
| Another provider supplies the nameservers | Edit records at that provider. Registration at Namecheap alone does not make Advanced DNS the authoritative zone; do not switch nameservers just to add a reset feature. |
| Another mail sender is being authorized | Keep a single SPF policy per hostname; merge authorized senders into that policy instead of creating two `v=spf1` records. Recheck SPF lookup limits and alignment ([SPF standard][spf-reference]). |
| DMARC is absent or failing | Treat it as the existing D11 deliverability task. Use the domain owner's reviewed policy and actual monitored report address, if reports are configured. Do not replace an existing quarantine/reject policy with a weaker example merely to make a reset test pass. |
| A real `support@`/Reply-To mailbox is wanted | Configure the chosen receiving service and its own MX records, then set the app's Reply-To to that real address. No receiving service was established by registering ACS MailFrom; do not point MX at `smtp.azurecomm.net`. |

With the same functioning sender, start by checking an authorized received reset email's authentication headers. **No Namecheap update is required solely for the password-reset workflow described here.** Any conditional DNS repair should also be checked against existing signup mail and website resolution.

## Handoff template

Every prompt ends with this record, linked from the checkpoint/index. The tested commit can differ from the final documentation commit; record both when necessary.

```text
Task / prompt:
Finding IDs / R rules / decisions / H workflows:
Started from commit + branch; working-tree context:
Implemented/tested commit or artifact:
Documentation commit, if different:
Environment; PHP/extensions; MySQL server; web runtime; UTC/Harare timestamps:
Changed files:
Migrations/order + compatibility/rollback limits:
Checks: command | expectation | PASS/FAIL/NOT RUN/BLOCKED | evidence path:
Operator observation: person/date/context + evidence boundary:
Pending owner decisions/external checks:
Remaining blockers and affected gate:
Release/deployment changes actually made, if any:
Next exact prompt and prerequisites:
```

## Sources

All repository links below are pinned to the reviewed `main` commit. The documents themselves reference older audit/test commits; preserve those distinctions. Cloud progress uses the 11 October conversation, not an independent Azure configuration export. External provider/security references were checked on 11 October 2026; they do not approve proposed Bushisa limits or demonstrate cloud changes.

| Source | How this pack uses it |
|---|---|
| [decisions.md][decisions] | Actual approved/pending D01–D12 register |
| [P0-to-P2 Azure remediation playbook][playbook] | Task order, gates, U01/U02 and H01–H13 acceptance |
| [Operational feasibility report][operational] | Historical schema/route/UI contract findings |
| [Production feasibility study][study] | Historical F01–F12, capacity scenarios and release criteria |
| [backlog.md][backlog] | Earlier partial repairs and evidence leads |
| [D04/D05 implementation evidence][evidence] | Latest recorded technical halt and NOT RUN checks |
| [Policy proposals D04/D05][policy] | R01–R12 implementation map and unresolved lifecycle work |
| [Revised D04/D05 review][policy-review] | Section 6.1 decisions and stronger safety/retention/replay contract |
| [Data retention policy][retention] | Existing wording requiring decision-backed reconciliation |
| [Geographic matching status][geo] | D12 and U02/P1-01G blocker |
| [Confession time contract][time] | Partial timestamp repair and remaining display acceptance |
| [Azure SMTP deployment guide][smtp] | Implemented mail transport/configuration and stale deployment paragraphs |
| [Current CI][ci] | Actual runtime matrix, executed suite list and missing release coverage |
| [Tests directory][tests] | Existing regressions, destructive browser bootstrap and overlap workers |
| [Current mail implementation][mail-source] | Signup-specific envelope, proof binding and dispatcher constraints behind T15 |
| [OWASP recovery guidance][reset-guidance] | General recovery-request/reset separation; Bushisa-specific values remain proposals |
| [OWASP session guidance][session-guidance] / [logging guidance][logging-guidance] | Session lifecycle and secret-free evidence |
| [MDN URL fragments][fragment-guidance] | Browser fragment transport for the proposed reset-link design |
| [Microsoft ACS SMTP][azure-smtp-reference] | Existing host/port/verified-sender prerequisites |
| [Namecheap DNS instructions][namecheap-dns-reference] / [SPF standard][spf-reference] | Conditional DNS edits and single-policy SPF constraint |

[decisions]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/decisions.md
[playbook]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/Bushisa_P0_to_P2_Azure_Remediation_Playbook.md
[operational]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/Bushisa_Operational_Feasibility_Report.md
[study]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/Bushisa_Production_Feasibility_Study.md
[backlog]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/backlog.md
[evidence]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/d04-d05-implementation-evidence.md
[policy]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/policy-proposals-d04-d05.md
[policy-review]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md
[retention]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/data_retention_policy.md
[geo]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/geographic-matching.md
[time]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/remediation/confession-time-contract.md
[smtp]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/docs/azure-smtp-deployment.md
[ci]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/.github/workflows/ci.yml
[tests]: https://github.com/Takudzwa00kyle/Bushisa/tree/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/tests
[mail-source]: https://github.com/Takudzwa00kyle/Bushisa/blob/3e5988023b0a85d8de21a05fb49a9e15b32edfa3/php/mail_delivery.php
[reset-guidance]: https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html
[session-guidance]: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
[logging-guidance]: https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html
[fragment-guidance]: https://developer.mozilla.org/en-US/docs/Web/URI/Reference/Fragment
[azure-smtp-reference]: https://learn.microsoft.com/en-us/azure/communication-services/quickstarts/email/send-email-smtp/send-email-smtp
[namecheap-dns-reference]: https://www.namecheap.com/support/knowledgebase/article.aspx/317/2237/how-do-i-add-txtspfdkimdmarc-records-for-my-domain/
[spf-reference]: https://www.rfc-editor.org/rfc/rfc7208.html
