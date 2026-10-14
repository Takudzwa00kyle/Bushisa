# Prompt 03 Owner Decision Packet

Prepared: 2026-10-14, Africa/Harare.

Branch: `codex/prompt03-owner-decisions-20261014`.

Starting checkpoint: `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`.

Draft PR kept open and draft: https://github.com/Takudzwa00kyle/Bushisa/pull/4.

This packet prepares D03-D12 for owner review. D03 is now recorded as approved
product scope for Bushisa's next production-ready release by reviewer Takudzwa
Hondova on 2026-10-14. This does not approve D04, D05, D12 or any other pending
policy, apply policy to production, deploy code, merge PR #4, run against the
live database or mark P0/P1 cleared. D01, D02 and D03 are the approved
decision-register entries.

## Current Evidence And Non-Claims

### Preserved approvals

| Decision | Preserved state |
|---|---|
| D01 | Approved student domain and student-number grammar remain unchanged in `docs/remediation/decisions.md`. |
| D02 | Approved adult eligibility of `age >= 18` remains unchanged in `docs/remediation/decisions.md`. |
| D03 | Approved full feature scope for the next production-ready release: signup, adult onboarding, discovery, reciprocal matching, geographic matching, approved chat, confession posting, safety/moderation and account-management workflows. |

### Completed deletion repair recorded from Prompt 02

| Item | Evidence |
|---|---|
| Forward migration | `database/migrations/005_protective_replay_event_precision.sql` upgrades `protective_replay_events.occurred_at` and `replayed_at` to `TIMESTAMP(6)`. |
| Cause repaired | Prompt 02 reproduced a same-second `uq_replay_event` collision when block and account deletion both recorded `match_closed` for the same match. |
| Fresh bootstrap | Disposable fresh bootstrap plus two migration runs applied/skipped `001` through `005` and reported replay timestamp precision `6`. |
| Upgrade rehearsal | Disposable old schema with `001` through `004` pre-marked applied upgraded through `005` and recorded precision `6`. |
| Local route/session checks | Repaired deletion case, full chat-route spec and rendered/session regressions passed on guarded disposable DBs. |
| HTTP overlap checks | Three Linux four-worker overlap cases passed: reciprocal swipe, approve-vs-revoke and send-vs-block. |
| Boundary | Migration `005` has not been applied to production in this packet and must not be described as production-applied. |

### Candidate CI runs retrieved for PR #4 head

Both accessible candidate runs test head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d` on branch
`codex/prompt02-completion-20261013`. Actor and triggering actor are
`Takudzwa00kyle`. Each run reports the three CI jobs as successful.

| Event | Run | Jobs |
|---|---|---|
| `push` | https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546846225 | `smtp-outbox-integration` https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546846225/job/115794890717; `browser-route-session-overlap` https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546846225/job/115794891193; `lint-and-unit` https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546846225/job/115794891288 |
| `pull_request` | https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546851035 | `browser-route-session-overlap` https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546851035/job/115794906697; `smtp-outbox-integration` https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546851035/job/115794906745; `lint-and-unit` https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38546851035/job/115794906746 |

Tested identities are synthetic CI principals and disposable databases:
`root` against MySQL 8.0 service databases `bushisa_test_ci` and
`bushisa_test_ci_browser`, `MAIL_PROVIDER=fake`, `APP_ENV=development`,
`APP_DEBUG=false`, `BUSHISA_TEST_DATABASE=true`, and
`BUSHISA_ALLOW_RUNTIME_DDL=false`. CI uses PHP 8.4, Node 20, Chromium and
`PHP_CLI_SERVER_WORKERS=4` for browser/overlap.

Passing CI confirms the candidate jobs above. It does not approve policy,
establish production compatibility, verify the live Azure MySQL server version,
prove a least-privilege runtime identity or establish an actual isolated restore.

## Decision Recording Rules

For every owner decision, record all of the following in
`docs/remediation/decisions.md` or a linked dated acceptance artifact:

- Person or role, date, reason and policy/version.
- Chosen values, expiry periods and affected data categories.
- Reviewed user notice wording and whether H03 specialist review is still
  required.
- Implementation links, evidence links and release task unblocked.

Unknown owners remain unassigned. Product authority can select product behavior;
privacy/legal/security/operations review remains separate where the playbook
requires H03 or other specialist acceptance.

## D03 - Next Production-Ready Release Product Scope

Status: approved product scope only.

Reviewer: Takudzwa Hondova, 2026-10-14.

Accepted scope:

- Bushisa's next production-ready release targets the full feature set, not a
  narrowed pilot scope.
- Included features are signup and student-email approval, adult onboarding,
  discovery, reciprocal matching, geographic matching, approved chat,
  confession posting, safety/moderation workflows and account-management
  workflows.
- The existing Azure deployment must be preserved while changes are prepared.
  This scope decision does not authorize live production mutation, migration
  against production, PR merge, or deployment by itself.
- D04 messaging/safety policy, D05 retention/lifecycle policy and D12
  geographic matching policy remain pending. The whole application is not
  production-ready until those and the other open release gates are accepted.

Current implemented behavior and evidence:

- Core account signup, email approval-code entry, adult onboarding, discovery,
  reciprocal swipes, chat request/approval, messages, unsend, blocking,
  confessions, moderation pages and account deletion have some implementation.
- Prompt 02 route evidence covers signup/onboarding, chat request/approve/send,
  unsend, report evidence capture, block cleanup, account deletion stale-session
  denial and moderator role removal on synthetic routes.
- U01 confession time has partial repair and a rendered timestamp test, but
  fake-clock boundaries and H09 human comparison remain open.
- U02 geographic matching is required for the next production-ready release and
  remains pending D12.

Implementation work, tests and dependent decisions:

| Feature | Implementation work required | Required tests/evidence | Dependent decisions |
|---|---|---|---|
| Signup and student-email approval | Complete production mail transport, exact sender settings, code expiry/resend/error UX and least-privilege runtime configuration. | Unit, DB and browser signup flows; real student-inbox delivery; leading-zero/delayed/replayed code cases; fake-mail denial in production. | D06, D08, D10, D11. |
| Adult onboarding | Keep DOB collection after email approval, enforce `age >= 18`, block underage accounts and remove raw DOB from public DTOs. | DOB boundary tests, pending-state route restrictions, stale-session denial and accessibility/device acceptance. | D02 approved; D05 retention for DOB/underage proof. |
| Discovery | Finish stable candidate retrieval, filtering, pagination/cursors, empty states, privacy-safe DTOs and representative pool behavior. | Browser/API discovery tests, 5,000-account or agreed representative dataset, cursor stability and blocked/ineligible exclusion. | D03 approved; D08 runtime matrix; D10 telemetry allowlist. |
| Reciprocal matching | Preserve mutual eligibility, questionnaire scoring, swipes, match uniqueness, block exclusion and overlap-safe writes. | Transaction/concurrency tests for reciprocal swipes, duplicate/replayed requests, match cleanup and rollback paths. | D04 for block/unmatch/rematch effects; D05 for cleanup after deletion. |
| Geographic matching | Add approved coarse-location catalogue, consent/update endpoints, proximity score integration, fallback behavior and privacy-safe responses. | Nearer-equal-compatible ranking, far-stronger-compatible ranking, missing/stale/withdrawn location, blocked/ineligible near users, cursor/cache invalidation, load/query-plan and H13 mobile permission evidence. | D12 required; D05 retention/withdrawal; D10 location-safe logging. |
| Approved chat | Keep chat gated behind explicit approved message request; complete request lifecycle, revoke/block effects, unmatch behavior, closed-chat access and notification cleanup. | Request/approve/send/read/revoke, send-vs-block, approve-vs-revoke, forged IDs, CSRF, stale sessions, rate limits and closed-chat cases. | D04 required; D05 for message/evidence retention. |
| Confession posting | Complete posting, immutable UTC times, feed/detail display, references, reporting, deletion-on-account-removal behavior and moderation controls. | API/DB/rendered timestamp tests, fake-clock boundary tests, reference/report privacy tests, suppressed-reference cases and H09 comparison. | D05 confession lifecycle; D04/D09 moderation/evidence access. |
| Safety and moderation | Finish shared safety service behavior for reports, blocks, case evidence, moderator access, audit trails, role removal and appeals/outcomes. | Cross-route report/block tests, evidence access denial, moderator role-removal sessions, abuse/rate-limit tests and case UI/API tests. | D04, D05, D09, D10. |
| Account management | Complete profile/media settings, account deletion, consent withdrawal, rights/export/delete handling, session revocation and cleanup/replay jobs. | Deletion stale-session tests, personal-data inventory, media/cache/log purge, restore replay rehearsal and rights-request acceptance. | D05 required; D06/D07 for Azure backup/recovery; D12 for location data. |

Affected routes, data, notices and operations:

- Routes: `discover.php`, `matches.php`, `chat.php`, `confessions.php`,
  `profile.php`, `verify_email.php`, `onboarding.php`, `api/swipe.php`,
  `api/message_requests.php`, `api/messages.php`, `api/confessions.php`,
  `api/safety.php`, `api/account.php`, future location endpoints and admin
  routes.
- Data: `users`, `profiles`, `preferences`, `find_match_answers`, `swipes`,
  `matches`, `message_requests`, `messages`, `confessions`,
  `confession_references`, reports/evidence, notifications, media, consent,
  logs, telemetry and future location fields.
- Notices: terms, privacy, safety, deletion, messaging consent, confession
  visibility, location-use and support wording must match the full accepted
  scope and any remaining policy approvals.
- Operations: staffing, alerts, cleanup jobs, Azure backup/restore, support,
  moderation and privacy request handling must cover every feature in scope.

Responsible reviewer role: product owner, with engineering release reviewer and
privacy/moderation reviewer for included safety/data features. Named reviewer:
Takudzwa Hondova.

Blocks now clarified: D03 no longer blocks feature inclusion. D04, D05, D12 and
the remaining production evidence still block production readiness for the
included features.

## D04 - Messaging And Safety Policy

Status: pending.

Current implemented behavior and evidence:

- A match does not authorize chat. `api/message_requests.php`,
  `api/messages.php` and `php/policy_safeguards.php` require approved chat for
  message read/write.
- Message request states currently include `pending`, `approved`, `declined`,
  `cancelled`, `expired` and `revoked`.
- Implemented provisional timers: pending request expiry at seven days,
  24-hour retry after cancel/expiry and 30-day retry after decline.
- Rate limits currently include `message_request` 20 per hour and `message`
  30 per minute per user.
- Either participant can revoke an active permission. Block closes matches,
  revokes chat grants, deletes ordinary swipes, suppresses obsolete chat
  notifications and prevents later sends.
- Unblock removes only the block row; it does not restore match or chat consent.
- Sender-only unsend is available for 15 minutes from server time when the
  message columns exist. It purges ordinary body visibility and returns
  `Message unsent`.
- Prompt 02 route and overlap evidence passed request/approve/revoke, non-sender
  unsend denial, sender unsend, block cleanup, approve-vs-revoke overlap and
  send-vs-block overlap.

Pending choices:

| Choice | Proposed options and tradeoffs |
|---|---|
| Request expiry | Keep seven days as proposed; choose shorter expiry for lower unwanted-contact risk; choose longer expiry for less re-request friction. |
| Cancellation/expiry cooldown | Keep 24 hours; shorten for usability; lengthen to reduce repeated contact. |
| Decline re-request cooldown | Keep 30 days with at most one extra attempt; allow no re-request for stronger safety; allow more attempts only with explicit anti-harassment budget. |
| Shared budgets | Approve per-user, per-pair and per-hour/day ceilings; current 20/hour request limit is a technical default, not policy. |
| Revoke behavior | Decide whether any fresh request after revoke is allowed and under what cooldown. Current code permits request after revoked without a separate revoke-specific cooldown. |
| Block/unblock/rematch | Current code: block closes match/revokes chat; unblock restores only the block row; new chat requires fresh match and approval. Approve this or specify a different model. |
| Unmatch | Shared close path supports `unmatched`, but a complete user-facing unmatch route still needs decision-backed implementation. |
| Account pause | Define whether existing chats remain available while discovery is paused. Current protected actions check account eligibility, not an explicit pause policy. |
| Unsent originals | Current route purges ordinary body and clears `moderation_body` on unsend unless a case snapshot already exists elsewhere. Alternative blanket original retention up to 90 days requires explicit privacy review, notice, encryption, access and purge capability. |
| Closed-chat access | Decide whether closed conversation content is retained up to 90 days for safety, purged promptly absent case evidence, and who can access/report it after block/unmatch. |

Affected routes, data, notices and operations:

- Routes: `api/message_requests.php`, `api/messages.php`, `chat.php`,
  `api/safety.php`, `api/swipe.php`, future unmatch/report/evidence routes,
  notification workers.
- Data: `message_requests.expires_at`, `closed_at`, `revoked_at`,
  `purge_due_at`, `matches.match_status`, `closed_at`, `closed_reason`,
  `messages.unsent_at`, `unsent_by`, `ordinary_body_purged_at`,
  `moderation_evidence`, `notifications`, `blocks`, `swipes`,
  `protective_replay_events`.
- Notices: messaging consent, block/unmatch effects, unsend limits, report
  evidence, closed-chat availability and "already delivered messages may have
  been seen" wording.
- Operations: moderation evidence access, stale notification suppression,
  cleanup job ownership and overlap regression maintenance.

Responsible reviewer role: product/safety owner for contact rules; moderation
owner for report/evidence access; privacy specialist for retention and unsent
originals; engineering reviewer for implementation. Named owners: unassigned.
This is the next owner-review decision after approved D03.

Blocks: Prompt 04 identity/current authorization/safety work, final P0-05
safety acceptance, user-facing notices, retention cleanup workers and any
release that enables chat for real users.

## D05 - Data Lifecycle And Retention

Status: pending.

Current implemented behavior and evidence:

- Account deletion route requires CSRF, login and password confirmation before
  calling `anonymise_user()`.
- Deletion sets user deleted/inactive/suspended flags where columns exist,
  increments `session_version`, rewrites direct identifiers, removes
  preferences, clears DOB/profile fields, revokes consent records and destroys
  the current session.
- `bushisa_prepare_account_deletion()` closes either-account relationships with
  reason `deleted_cleanup`, revokes message requests, queues cleanup and records
  protective replay events.
- Owned confession deletion is prepared before author unlinking through the
  policy safeguard path, but the older `anonymiser_detach_confessions()` still
  demonstrates why owner-approved confession semantics must be explicit.
- Verifier terminalization clears signup `code_hash` and code-bearing fake-mail
  bodies; production SMTP outbox payloads are encrypted and erased on terminal
  outcomes.
- Provisional purge helpers compute UTC deadlines: 30 days for message requests
  and account cleanup, 90 days for closed conversations, 24 months for case
  evidence and seven days for synthetic mail.
- Migration `005` and `UTC_TIMESTAMP(6)` protective writes repair replay-event
  timestamp precision.

Pending choices:

| Choice | Proposed options and tradeoffs |
|---|---|
| Signup abandonment | Choose expiry for incomplete pending-email and pending-onboarding accounts. Shorter expiry minimizes stored student emails; longer expiry reduces user friction. |
| Underage rejection | Decide minimal retained proof and expiry for blocked underage attempts. Do not retain full DOB longer than necessary without specialist approval. |
| Either-account cleanup | Current proposed direction cleans ordinary relationship data after either account deletes. Alternative both-accounts-delete retention conflicts with current safety proposal and needs explicit justification. |
| Confession deletion | Recommended default removes public posts/references on account deletion. Alternative continued anonymous publication needs owner deletion rights, attribution and moderation model. |
| Retained evidence | Decide scoped case evidence categories, final-resolution definition, 24-month proposal, appeal handling and deletion capability. |
| Consent proof | Specify minimal fields, purpose and post-deletion deadline. Existing `docs/data_retention_policy.md` says indefinite consent retention, but the revised proposal says no automatic six-year or indefinite rule is approved. |
| UTC deadlines | Approve immutable clock start events and calendar arithmetic. Repeated reads, retries, edits and hold reviews must not reset expiry. |
| Holds and release | Define authority, scope, review period, release behavior and purge-after-release behavior. |
| Rights requests | Assign support/privacy owners, verification method for deleted users, response targets and export redaction rules. |
| Recoverable copies | Inventory Azure MySQL backups, restored servers, Blob versions/soft delete, manual exports, logs and caches. Set replay ledger horizon to cover all recoverable copies plus safety margin. |

Affected routes, data, notices and operations:

- Routes: `api/account.php`, `profile.php`, signup verification routes,
  `api/confessions.php`, `api/messages.php`, `api/safety.php`, cleanup CLI/jobs,
  future rights-request endpoints.
- Data: `users`, `profiles`, `preferences`, `find_match_answers`,
  `profile_images`, `swipes`, `matches`, `message_requests`, `messages`,
  `confessions`, `confession_references`, `student_email_approval_codes`,
  `mail_outbox`, `consent_records`, `audit_log`, `moderation_cases`,
  `moderation_evidence`, `cleanup_outbox`, `protective_replay_events`,
  `restore_replay_runs`, uploads/media, logs, telemetry and future location.
- Notices: privacy policy, deletion flow, retention table, consent wording,
  support/rights wording, backup/recovery limitations and confession deletion
  copy must be reconciled with accepted D05.
- Operations: cleanup worker, media/blob deletion, cache purge, support queue,
  privacy request handling, restore replay and alert ownership.

Responsible reviewer role: privacy specialist/H03, product owner, moderation
owner, support owner, engineering/release owner. Named owners: unassigned.
This remains pending and is the next owner-review decision after D04.

Blocks: Prompt 05 lifecycle execution, public deletion promises, cleanup/restore
worker release, retention notices, isolated restore acceptance and any launch
that processes real personal data.

## D06 - Azure Ownership, Region, Funding And Data Flow

Status: pending.

Current implemented behavior and evidence:

- Continuation pack records Azure App Service `Bushisa`, resource group
  `takudzwaportfolio`, Basic Linux, South Africa North and subscription shown as
  Azure for Students.
- ACS resources `bushisa-communication` and `bushisa-email` use custom domain
  `bushisa.me` and registered sender `DoNotReply@bushisa.me`; ACS data location
  is recorded as United States while the app is South Africa North.
- Azure MySQL connection to `bushi-server.mysql.database.azure.com` and database
  `bushi-database` was previously reported with TLS cipher
  `TLS_AES_256_GCM_SHA384`.
- This packet did not access Azure, deploy or query the live database.

Options and tradeoffs:

| Option | Tradeoff |
|---|---|
| Continue Azure for Students | Lower cost short term, but production eligibility, continuity and credit exhaustion remain unresolved. |
| Move to paid production subscription | Higher cost, clearer continuity and ownership. |
| Keep ACS United States | Uses current sender setup, but requires privacy/data-flow acceptance for cross-region email processing. |
| Re-provision email in an approved region if available | May improve residency alignment, but requires new verification and delivery testing. |

Affected routes, data, notices and operations:

- Runtime config: `APP_ENV`, `APP_BASE_URL`, `DB_*`, `AZURE_MYSQL_*`,
  `DB_SSL_CA`, `MAIL_*`, `SMTP_*`, `MAIL_OUTBOX_KEY`.
- Data flow: student emails, approval codes inside encrypted/purged payloads,
  SMTP metadata, diagnostic failures and possible support/reply messages.
- Notices: privacy/subprocessor/hosting-region language and email-provider
  processing.
- Operations: subscription owner, budget alerts, billing continuity, RBAC,
  deployment federation, Key Vault/secret custodians and data-flow review.

Responsible reviewer role: operator/account owner, privacy specialist, finance
owner and engineering release owner. Named owners: unassigned.

Blocks: Azure provisioning acceptance, production data-flow approval, sender
approval, budget guardrails and launch go/no-go.

## D07 - Availability, RPO And RTO

Status: pending.

Current implemented behavior and evidence:

- Playbook recommends managed MySQL backups, separate Blob/configuration
  recovery and restore rehearsal.
- No actual isolated Azure restore or measured recovery rehearsal has been run.
- Basic App Service has no deployment slots, so a separate staging environment
  or maintenance-aware release is still required if Basic remains selected.

Options and tradeoffs:

| Option | Tradeoff |
|---|---|
| Single Basic instance plus managed backups | Lower cost; downtime during deployment/restore likely; must be accepted explicitly. |
| Standard/Premium with slots and stronger operational controls | Higher cost; smoother release/rollback and potential availability improvements. |
| RPO based on managed MySQL backup window only | Simpler; does not cover media, configuration, mail queues or protective replay by itself. |
| RPO/RTO measured by full synthetic restore | Higher effort; required for credible launch evidence. |

Affected routes, data, notices and operations:

- All app routes; database, media, mail queue, cleanup queue, logs and secrets.
- User notices: downtime/maintenance support, deletion/backups wording and
  incident response expectations.
- Operations: backup owner, restore owner, release owner, alert responder,
  runbook and rehearsal evidence.

Responsible reviewer role: engineering operations owner and operator. Named
owners: unassigned.

Blocks: production topology, restore acceptance, P1 release candidate, launch
stop/rollback criteria and R12 closure.

## D08 - Runtime, Network And Session Strategy

Status: pending.

Current implemented behavior and evidence:

- Local Prompt 02 used PHP 8.5.11 and local MySQL 9.6.0 disposable DBs.
- Docker overlap used PHP 8.3.6, MySQL client 8.0.46 and `PHP_CLI_SERVER_WORKERS=4`.
- CI uses PHP 8.4 and MySQL 8.0 services.
- Continuation pack records live app PHP 8.5.6 from SSH output, but the Azure
  MySQL server version is still unrecorded here.
- `require_login()` reloads account state and `session_version`; deletion and
  suspension can revoke stale sessions in covered route tests.
- Runtime DDL can be disabled with `BUSHISA_ALLOW_RUNTIME_DDL=false`; CI and
  Prompt 02 tests use that setting.

Options and tradeoffs:

| Option | Tradeoff |
|---|---|
| Approve PHP 8.4/MySQL 8.0 as release test matrix | Matches CI and supported Azure MySQL service; still must compare with actual App Service runtime. |
| Approve PHP 8.5 if App Service uses it | Matches observed app runtime; requires CI/staging compatibility evidence on the same version. |
| Single-instance file sessions | Simple and possibly acceptable for a small pilot; does not support multi-instance scaling without sticky routing or shared store. |
| Shared session store | More operational work; needed before multiple app instances or stronger revocation/locking guarantees. |
| Public DB access for deployment convenience | Not acceptable without explicit security review; private connectivity is the playbook baseline. |

Affected routes, data, notices and operations:

- All authenticated routes, migration runner, cleanup jobs, mail dispatcher and
  browser tests.
- Configuration: PHP extensions, `DB_SSL_CA`, runtime/migration DB users,
  public web root, protected paths, cookies and trusted proxy headers.
- Notices: none directly, except downtime/session-expiry behavior.
- Operations: migration identity, runtime least privilege, private network,
  staging parity and exact production-bound artifact.

Responsible reviewer role: engineering release owner/security reviewer. Named
owners: unassigned.

Blocks: production compatibility acceptance, restricted runtime role, migration
procedure, deployment artifact lock and multi-instance plan.

## D09 - Operations, Moderation, Support And Escalation

Status: pending.

Current implemented behavior and evidence:

- Moderator role-removal route evidence exists: both existing moderator sessions
  lost admin access after DB role removal.
- Case-scoped evidence tables/helpers exist, but user-facing case assignment and
  evidence viewing UI remain incomplete.
- Password reset is requested for `DoNotReply@bushisa.me` but not implemented.
- No named support, moderation, privacy, engineering or escalation owners are
  recorded in the decision register.

Options and tradeoffs:

| Option | Tradeoff |
|---|---|
| Small named team with explicit shifts/backup | Required for credible reports, rights requests and alerts; may delay launch until staffed. |
| Operator-only support | Faster to start, but high single-person operational risk. |
| Disable features requiring active moderation | Reduces coverage burden; must remove related UI/API promises from scope. |

Affected routes, data, notices and operations:

- Admin and moderation routes, report/block routes, evidence access, support
  inbox, password reset, privacy/rights requests and incident handling.
- Data: reports, moderation cases, audit logs, exports, support records.
- Notices: support contact, response expectations, report outcomes and appeals.
- Operations: on-call coverage, queue ownership, escalation and disclosure
  authority.

Responsible reviewer role: operator, moderation owner, support owner, privacy
owner and engineering owner. Named owners: unassigned.

Blocks: D10 alert recipients, D05 rights handling, D04 evidence access,
password reset release, moderation acceptance and launch staffing.

## D10 - Telemetry, Alerts And Monitoring

Status: pending.

Current implemented behavior and evidence:

- Telemetry integration/collection remains on hold for D10.
- Continuation notes mention App Insights resource creation and a prepared
  browser SDK/CSP patch on another branch, but that is not pushed/deployed here
  and not accepted as telemetry policy.
- CI/local tests use logs and synthetic diagnostics, not production telemetry.

Options and tradeoffs:

| Option | Tradeoff |
|---|---|
| Server-only platform and application error metrics first | Lower privacy risk; less product analytics. |
| Browser telemetry with explicit approved field allowlist | Better UX/error visibility; requires cookie/notice, redaction, region, retention and budget decisions. |
| Datadog/App Insights integration | Useful alerts; requires entitlement, cost and data destination review. |

Affected routes, data, notices and operations:

- All routes may emit errors/latency; mail/cleanup jobs need failure alerts.
- Forbidden telemetry fields: passwords, tokens, message bodies, questionnaire
  answers, raw DOB, student IDs, precise/coarse location payloads unless
  explicitly approved, approval codes and private evidence.
- Notices: cookie/telemetry/privacy language and opt-outs where required.
- Operations: alert recipients, escalation targets, retention and spending caps.

Responsible reviewer role: privacy specialist, engineering operations owner and
operator/budget owner. Named owners: unassigned.

Blocks: production monitoring, alerting acceptance, incident runbooks, D12
location-safe logging and launch observability.

## D11 - Sender, Reply-To And Delivery Acceptance

Status: partial.

Current implemented behavior and evidence:

- Decision register partially approves using a Bushisa-owned sender domain and
  fake mail only for labelled local/synthetic development until ACS is ready.
- Continuation pack records custom domain `bushisa.me` and registered sender
  `DoNotReply@bushisa.me` under ACS resources `bushisa-communication` and
  `bushisa-email`.
- `php/mail_delivery.php` supports `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`,
  optional `MAIL_REPLY_TO`, SMTP settings, encrypted outbox payloads, retry
  handling and payload erasure.
- `docs/azure-smtp-deployment.md` says `MAIL_REPLY_TO` should be an existing
  receiving address, preferably on `bushisa.me`, or left blank if none is
  configured.
- Password reset using the same sender is requested, not implemented or tested.

Pending choices:

| Choice | Proposed options and tradeoffs |
|---|---|
| Production From | Use exact registered `DoNotReply@bushisa.me` MailFrom if confirmed; any other address requires ACS/domain verification. |
| Reply-To | Leave unset until a monitored receiving inbox exists, or configure a real support address with owner and response process. A send-only address is not support. |
| Header/DNS acceptance | Record SPF/DKIM/DMARC/header outcomes from owner-approved inbox tests; do not weaken existing domain policy to pass a test. |
| Delivery diagnostics | Decide what ACS metadata/bounce data is retained, where alerts go and how failures are handled. |
| Password reset reuse | Reuse sender for reset only after reset flow, token lifecycle, queue purpose and monitoring are implemented. |

Affected routes, data, notices and operations:

- Routes/jobs: signup verification, future password reset, mail dispatcher,
  cleanup policy.
- Data: `student_email_approval_codes`, `mail_outbox`, encrypted payloads,
  SMTP/ACS metadata, delivery failures.
- Notices: verification/reset email language, support contact and no-reply copy.
- Operations: sender ownership, secret rotation, scheduler ownership, delivery
  alert ownership and support mailbox.

Responsible reviewer role: operator/domain owner, support owner, privacy
reviewer and engineering owner. Named owners: unassigned except reported domain
owner evidence from operator remains outside this packet.

Blocks: real sender/inbox acceptance, password reset, production signup email,
delivery monitoring and D06 data-flow approval.

## D12 - Location Collection And Geographic Matching

Status: pending.

Current implemented behavior and evidence:

- `docs/remediation/geographic-matching.md` records no verified active
  geographic ranking signal in the authoritative matching service.
- Current matching in `php/matching/service.php` loads profile/preference rows,
  excludes the viewer, already swiped users, existing matches and either-way
  blocks, requires verified/active adult profiles and then applies reciprocal
  gender/age preferences plus hard questionnaire incompatibility checks.
- Ranking uses `matching_score_pair()` from `php/matching/scoring.php` as the
  primary rank value. It then adds only a small faculty/year profile tie breaker
  and sorts by rank descending, then `user_id` ascending. It does not join a
  location table, calculate distance, read device coordinates or apply a
  proximity weight.
- The base schema has a legacy `profiles.campus_zone` text column, but the
  authoritative matcher does not currently use it for discovery ranking and no
  approved catalogue or consent/lifecycle model is attached to it.
- Production location use remains disabled/pending; no approved catalogue,
  consent flow, lifecycle or proximity weight exists.

Options and tradeoffs:

| Option | Tradeoff |
|---|---|
| Manual coarse campus/area selection only | Lower privacy and permission friction; less precise proximity. |
| Optional browser geolocation converted to coarse area/cell | Better proximity signal; requires consent, HTTPS permission UX, freshness, withdrawal and telemetry safeguards. |
| No production location in the next production-ready release | Not selected under approved D03 full scope; would avoid privacy and data-flow risk, but U02/P1-01G would remain unsatisfied because geography is now in scope. |
| Soft bounded bonus | Reduces overfitting and avoids hard exclusion by location; owner must approve weight/scale and disclosure. |
| Hard radius | Stronger location behavior but high risk of false exclusion, sparse areas and privacy probing. |

Remaining D12 choices for owner review:

- Location source: manual area, optional browser geolocation converted to a
  coarse area/cell, both, or another approved source.
- Consent: exact opt-in wording, whether location is optional, denial fallback,
  withdrawal behavior and whether existing matches/chats are affected.
- Granularity: campus/area labels, representative points, approximate cell
  size, whether precise coordinates are ever stored, and what appears in public
  responses.
- Retention: freshness period, expiry, deletion/withdrawal deadline, backup
  treatment, consent proof fields and D05 alignment.
- Weighting: soft bonus cap, distance scale, hard-radius prohibition or limit,
  treatment of unknown/stale locations and whether far stronger reciprocal
  matches can outrank closer weaker matches.
- Stability and privacy: cursor/cache invalidation on location change,
  telemetry redaction, anti-probing behavior, sparse-area disclosure and H13
  human/mobile acceptance.

Affected routes, data, notices and operations:

- Routes: future location consent/update endpoints, `discover.php`,
  `api/discover.php`, `matches.php`/Find Match if location-ranked, profile
  settings and deletion/rights endpoints.
- Data: catalogue version, selected area, optional coarse device location,
  freshness/expiry, consent records, withdrawal timestamp, ranking/exposure
  records and telemetry redaction.
- Notices: location purpose, optionality, freshness, withdrawal, approximate
  distance/area disclosure and non-use of exact live position.
- Operations: catalogue owner, privacy review, cache/log cleanup, Maps provider
  budget if used and representative mobile permission testing.

Responsible reviewer role: product owner, privacy specialist/H13, engineering
owner and catalogue/local-area reviewer. Named owners: unassigned.
This remains pending and is the next owner-review decision after D05.

Blocks: U02/P1-01G, location collection, geographic ranking, location-related
retention/rights handling and the next production-ready release scope because
geography is included by D03.

## R01-R12 Remaining Gaps

Passing Prompt 02 CI and local tests narrows several technical gaps, but it does
not close policy approval, production compatibility or restore proof.

| Rule | Current state after Prompt 02 | Remaining gap |
|---|---|---|
| R01 | Partial pass. Signup/onboarding and reciprocal swipe overlap passed; chat approval through real routes passed. D03 now approves the full feature scope. | Broader matching catalogue, D04/D05/D12 policy acceptance and production-bound compatibility remain open. |
| R02 | Partial pass. Request/approve/revoke and approve-vs-revoke overlap passed. | Expiry, cooldowns, budgets, revoke/re-request, rematch/unblock and pause policy remain pending D04. |
| R03 | Partial pass. Block route and send-vs-block overlap passed; messages/evidence survive normal close path. | Account deletion overlap beyond covered stale-session route, rollback injection, legacy cascade audit and production retention remain open. |
| R04 | Pass for current actual unsend route. | Final D04 unsent-original retention wording, attachment projections and closed-chat access remain pending. |
| R05 | Partial pass. Fake-mail signup route, units, SMTP fixture and outbox regressions passed. | Real sender/inbox/ACS delivery, reset delivery and full terminal delivery-failure handling remain open. |
| R06 | Partial pass. Account deletion route marks deleted, revokes stale session, cleans relationships and records protective events. | Full personal-data inventory, media/cache/log purge and D05 owner acceptance remain open. |
| R07 | Partial. Existing DB/service confession erasure and rendered timestamp evidence remain. | Owner decision on deletion vs continued anonymous publication, suppressed-reference browser coverage and public notice remain open. |
| R08 | Not run. | Environment-specific personal-data inventory and actual purge/access effects remain D05/D06/D12 work. |
| R09 | Partial. Cleanup/replay rows are asserted for deletion and moderation cases. | Independent replay horizon, consent proof fields/deadline and all recoverable copies remain unapproved/unrehearsed. |
| R10 | Partial. UTC rendered timestamp and purge due fields are covered. | Full retention worker, hold release, fake-clock deadlines and no-reset proof remain open. |
| R11 | Partial pass. Outsider report denial/evidence capture and role-removal sessions passed. | Assigned-moderator case evidence UI/API, access assignment and audit acceptance remain open. |
| R12 | Not run. | No isolated Azure restore or independent protective replay rehearsal has occurred. Seeded fail-closed tests are not actual restore proof. |

## Prioritized Owner Questions

Continue owner review in this order: D04 first, then D05, then D12. The
questions below intentionally leave D04, D05 and D12 pending until their exact
answers are recorded.

1. Who owns D04 messaging/safety choices, and should the current proposed
   seven-day request expiry, 24-hour retry, 30-day decline retry, 15-minute
   unsend and non-restoring unblock/rematch model be accepted or changed?
2. Who owns D05 privacy/retention review, and what are the exact deadlines for
   abandoned signup, underage attempts, consent proof, closed chats, case
   evidence, media cleanup, logs and recoverable-copy replay?
3. For D12, will Bushisa collect manual area, device location, both or neither,
   and what catalogue, consent wording, granularity, freshness, retention,
   withdrawal behavior, public disclosure and score weight are approved?
4. Is the Azure for Students subscription acceptable for real production use,
   and who funds/owns the service after credits or eligibility change?
5. Is the South Africa North app plus United States ACS email data flow accepted
   after specialist review?
6. What RPO/RTO and downtime are acceptable for the next production-ready
   release pilot, and who will witness an isolated restore rehearsal?
7. Which production PHP/MySQL/session/network matrix is approved for the
   Azure-bound artifact?
8. Who receives alerts, support requests, privacy requests, moderation cases and
   delivery failures?
9. Is `DoNotReply@bushisa.me` accepted as the production From, and will a real
   monitored Reply-To/support inbox be configured?

## Technical Tasks That Can Proceed Meanwhile

- Keep PR #4 draft and unmerged while reviewers inspect the Prompt 02 candidate.
- Add tests around D04 boundaries that do not require changing policy values:
  forged ownership, CSRF, stale sessions, block/revoke denial and notification
  suppression.
- Prepare but do not activate user-facing unmatch/report/evidence-access routes
  behind pending D04/D05 wording.
- Inventory all personal data fields, media paths, logs and future location
  stores for D05 review without purging production data.
- Build an isolated restore rehearsal plan and scripts using synthetic data only.
- Capture actual Azure MySQL server version, App Service runtime, network path
  and runtime DB privileges through approved read-only operator checks.
- Draft privacy/retention/support wording variants for owner review without
  publishing them.
- Extend CI/reporting docs to keep CI success, production compatibility and
  restore proof visibly separate.
