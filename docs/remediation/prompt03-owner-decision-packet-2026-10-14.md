# Prompt 03 Owner Decision Packet

Prepared: 2026-10-14, Africa/Harare.

Owner review updated: 2026-10-15, Africa/Harare.

## Q01-Q48 response and current approval scope

The owner has supplied all 48 answers. The current acceptance supplement is
[owner-policy-reconciliation-2026-10-15.md](owner-policy-reconciliation-2026-10-15.md),
incorporated into the [decision register](decisions.md). It preserves every
earlier approval, accepts direct product selections only, keeps explicitly
proposed values unadopted and records conflicts/unknowns in one concise table.
Takudzwa Hondova is the product reviewer. **Exact acceptance date/time is
CONFIRM in UTC and Africa/Harare**; the timezone-only reply is not a timestamp.

Current supporting artifacts:

- [Data inventory, retention and immutable-clock matrix](data-inventory-retention-matrix.md).
- [Read-only Azure/Namecheap impact and costing checklist](configuration-impact-checklist-2026-10-15.md).
- [Later bounded implementation backlog L01-L07](backlog.md#q01-q48-implementation-backlog).

D03 remains the full next-production-ready feature scope with existing Azure
hosting. D04/D05/D12 have partial product selections and remain pending overall;
D06-D10 remain pending and D11 partial. No application behavior, specialist
sign-off, deployment, migration 005 production application or actual isolated
restore is claimed. The exact candidate CI evidence below is preserved.

The earlier per-decision entries and choice tables below are retained as the
review history. Older pending answers/provisional values are superseded only
to the express scope recorded in the new acceptance supplement. In particular,
Q08's conflicting renewed-consent undo rule does not replace Decisions 20-22;
Q45's Reply-To fallback remains unresolved against D11, and blocker-initiated
unblock retains its earlier immediate chat effect.

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

Status: pending, with D04 Decisions 1 through 26 approved only for pending
request expiry, the cancelled/expired request retry cooldown, declined
same-direction retry denial, request budget ceilings, reported-sender probation
behavior, reverse-direction/rematch behavior after decline and restart actor/
fresh approval, the 12-hour UTC cooldown and the second-end match-lifetime
cutoff after ending an approved chat, including its carryover across rematches
between the same two users, and immediate chat privileges on blocker unblock,
including when chat was never approved. Blocking an approved chat is excluded
from the two-ending cutoff. Already-approved chat continues after unmatch.
Decision 19 preserves a pending request on unmatch but allows its recipient to
approve only after the pair is matched again, if the request is still pending;
the original seven-day expiry remains unchanged. Decision 20 approves that the
person who unmatched may restore the match without additional agreement from
the other participant. Restoration does not approve the pending chat request.
Decision 21 limits restoration to the person who unmatched in the reviewed
case; the other participant cannot restore it. Decision 22 requires each
participant to undo their own unmatch before the match can return when both
unmatched; one person's undo is insufficient. Other restoration details remain
pending. Decision 23 selects `Block and report` for reporting with blocking:
pending pair requests are cleared and no unblock privilege is initially
available. Decision 24 requires a safety reviewer's explicit approval for
unblocking; completing review alone is insufficient. Reviewers may ban users
from the app for policy or ethical violations, or leave the pair blocked without
a ban. Decision 25 lets the blocker choose whether to press `Unblock` after
explicit reviewer approval; approval alone leaves the block in place.
Decision 26 makes chat immediately available after the explicitly approved,
chosen unblock, without a fresh request or further chat approval. Cleared
pending requests remain cleared; independent blocks/account restrictions and
the recorded second-ending cutoff remain applicable. Substantive criteria,
role assignments, ban duration/appeals and review timing remain pending.
Earlier ordinary block/unblock and unmatch approvals remain unchanged.
Approved chat also continues during discovery pause. Decision 18 approves that
discovery pause does not affect any matching, requesting, approving or chatting
functionality; the normal independent restrictions still apply. Unsent original text is
deleted unless already preserved for an existing safety report; blanket
moderation retention is rejected. A 15-minute unsend window from the message's
server-recorded sending time is approved, with unsending denied after that
window. An unsent message entry disappears entirely from both participants'
ordinary chat views, without a placeholder. Evidence access, retention deadlines
and backup deletion remain pending under D05, as do other unresolved policy
details.

D04 Decision 1 recorded approval:

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-14.
- Approved value: pending message requests expire seven days after creation,
  measured in UTC.
- Scope: applies only to requests in `pending` state.
- Non-approval boundary at the time of Decision 1: cancellation/expiry
  cooldowns, decline re-request cooldowns, shared budgets, revoke behavior,
  block/unblock/rematch, unmatch, account pause, unsent-original handling and
  message/evidence retention remained separate pending decisions.

D04 Decision 2 recorded approval:

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-14.
- Approved value: the same requester may send a fresh request to the same
  recipient after a 12-hour cooldown following cancellation or expiry.
- Measurement: all times are measured in UTC. Cancellation cooldown starts at
  the cancellation timestamp. Expiry cooldown starts at the stored `expires_at`
  timestamp.
- Scope: applies only to requests in `cancelled` or `expired` state.
- Non-approval boundary at the time of Decision 2: declined-request cooldowns
  and request budget ceilings were not yet approved. D04 Decision 3 later
  resolves same-direction declined retry denial only. D04 Decision 4 later
  resolves the approved request budget ceilings only. Existing authorization
  and blocking safeguards continue to apply.

D04 Decision 3 recorded approval:

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-14.
- Approved value: after a recipient declines, the original requester cannot
  send a fresh request to that same recipient.
- Allowed further attempts in that requester-recipient direction: `0`.
- Measurement: no cooldown automatically restores permission after decline.
- Scope: applies only to the original requester-recipient direction.
- Non-approval boundary: reverse-direction behavior, rematch behavior and
  request budget ceilings remained separate pending decisions at the time of
  Decision 3. D04 Decision 4 later resolves the approved request budget
  ceilings only. D04 Decision 5 later resolves reverse-direction and recreated
  match decline-restriction behavior only. Existing authorization and blocking
  safeguards continue to apply.

D04 Decision 4 recorded approval:

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-14.
- Approved sender budget: a sender may create at most 3 requests across all
  matches per 12 hours.
- Approved recipient budget: a recipient may receive at most 10 incoming
  requests across all requesters per 12 hours.
- Approved pair budget: a requester-recipient pair may have at most 3 requests
  over the pair lifetime.
- Approved reported-sender budget: a reported sender receives a reduced
  allowance of 3 requests per 24 hours rather than a sending ban.
- Approved reported-sender probation duration: fixed 7-day probation lasting
  168 hours from probation activation, measured in UTC.
- Approved probation behavior: during probation, the sender remains limited to
  3 requests per 24 hours.
- Approved clearance behavior: no review or support appeal may restore the
  normal allowance early. After probation expires, restore the normal sender
  allowance of 3 requests per 12 hours, subject to other applicable account
  restrictions.
- Approved non-reset boundary: restoring the sending rate does not reset
  lifetime pair budgets or override declined-request, blocking or authorization
  rules.
- Approved support contact boundary: the sender may contact support and submit
  a written appeal explaining a mistaken report, but support handling must
  respect the no-early-restoration rule.
- Approved repeat-report behavior: another report during the probation week
  leaves the active 168-hour probation period unchanged; it does not restart or
  extend that period.
- Non-approval boundary: reverse-direction declined-request behavior, rematch
  behavior, revoke behavior, block/unblock, account pause, unsent-original
  handling and message/evidence retention remained separate pending decisions
  at the time of Decision 4. D04 Decision 5 later resolves reverse-direction
  and recreated-match decline-restriction behavior only.

D04 Decision 5 recorded approval:

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-14.
- Approved reverse-direction behavior: after a recipient declines a request,
  that recipient may later send a reverse-direction request to the original
  requester.
- Approved recreated-match behavior: a recreated match clears prior decline
  restrictions.
- Non-reset boundary: recreating a match clears decline restrictions only; it
  does not reset lifetime pair budgets or override blocking, authorization or
  account restrictions.
- Non-approval boundary: revoke behavior, block/unblock, account pause,
  unsent-original handling and message/evidence retention remain separate
  pending decisions.

D04 Decision 6 recorded approval (restart actor and fresh approval only):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "Only the person who ended the approved chat can restart it."
- Clarified restart: that person sends a fresh message request. The other
  participant must approve it before chat access resumes.
- Example: Alice ends her approved chat with Brian. Only Alice may send the
  fresh restart request; Brian must approve it before they can chat again.
- Scope: ending/revoking an approved chat. This does not decide block/unblock,
  unmatch or account-pause behavior, access to earlier messages, or retention.
- Existing approvals: D04 Decisions 1-5 remain unchanged. No reset or exception
  to approved request budgets, decline restrictions, blocking or authorization
  rules is approved.
- Pending values: no revoke-specific cooldown or additional restart cap has
  been selected. The approved 12-hour cancellation/expiry cooldown is not a
  decision about restarting an ended approved chat.
- Reason and policy version: record the reviewer's explicit selection of the
  restart actor and renewed recipient approval in Prompt 03 D04 Decision 6.
- Affected data: chat grants and message-request state/actor. No retention
  category or duration is approved by this decision.
- Notices and review: user-facing wording and required H03 specialist review
  remain pending; this product choice does not provide privacy acceptance.
- Implementation/evidence: documentation only. Alignment of
  `api/message_requests.php` and authorization in
  `php/policy_safeguards.php`, with focused route/regression evidence, remains
  pending. Prompt 04/R02 records this accepted behavior, but no release gate is
  cleared and D04, D05 and D12 remain pending overall.

D04 Decision 7 recorded approval (restart cooldown only):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: the person who ended the approved chat must wait 12 hours
  before sending the fresh restart request.
- Measurement: the 12-hour elapsed cooldown starts when the approved chat is
  ended/revoked, measured in UTC.
- Example: Alice ends the approved chat at 08:00 UTC. She may send the fresh
  restart request no earlier than 20:00 UTC, subject to the existing request
  limits and restrictions. Brian must still approve before chat access resumes.
- Scope: resolves only the restart cooldown left pending at Decision 6.
  Decision 6's restart actor and fresh-approval requirements remain unchanged.
- Existing approvals: D04 Decisions 1-6 remain unchanged. Waiting 12 hours does
  not reset request budgets or override decline restrictions, blocking or
  authorization rules.
- Non-approval boundary: additional restart caps, block/unblock, unmatch,
  account pause, access to earlier messages and retention remain separate
  pending choices.
- Reason and policy version: record the reviewer's explicit 12-hour wait in
  Prompt 03 D04 Decision 7.
- Affected data: approved-chat end/revocation time and fresh-request creation
  time. No retention category or duration is approved by this cooldown choice.
- Implementation/evidence and notices: documentation only. Implementation
  alignment and focused route/regression evidence remain pending, as do
  user-facing wording and required H03 specialist review. No release gate is
  cleared; D04, D05 and D12 remain pending overall.

D04 Decision 8 recorded approval (second-end match-lifetime cutoff only):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: a second approved-chat end terminates re-request privileges
  for the rest of that match's lifetime.
- Example: Alice ends an approved chat, waits 12 hours, sends a fresh request
  and Brian approves it. If Alice ends the approved chat again, no further
  restart request is allowed for the rest of that match's lifetime, even if
  another 12 hours pass or request budget remains.
- Scope: the lifetime of that match. Only approved-chat endings are addressed;
  this decision does not change pending-request cancellation, expiry or decline
  rules.
- Existing approvals: D04 Decisions 1-7 remain unchanged. Decision 6's restart
  actor and fresh approval and Decision 7's 12-hour UTC wait continue to govern
  an otherwise permitted restart. Existing request budgets, probation, decline
  restrictions, blocking and authorization rules remain applicable.
- Pending boundary: restart eligibility and cutoff behavior after a later
  recreated match are not decided here. Decision 5's recreated-match approval
  clears decline restrictions only and does not itself resolve this cutoff.
- Other pending choices: block/unblock, unmatch, account pause, access to
  earlier messages, evidence/retention and reviewed user-facing wording.
- Reason and policy version: record the reviewer's explicit second-end cutoff
  in Prompt 03 D04 Decision 8.
- Affected data: approved-chat ending history, the match identity/lifetime and
  re-request eligibility. No new retention period or budget reset is approved.
- Implementation/evidence and review: documentation only. Implementation
  alignment, focused route/regression evidence and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.

D04 Decision 9 recorded approval (second-end cutoff carryover across rematches only):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: the second approved-chat ending cutoff carries over across
  rematches between the same two users. Unmatching and matching again must not
  restore chat-request privileges after that cutoff has been triggered.
- Example: the second approved-chat ending triggers the cutoff for Alice and
  Brian. If they later unmatch and match again, that new match does not restore
  their permission to send chat requests to each other, even if request budget
  remains.
- Scope: carryover of the triggered Decision 8 cutoff between the same two
  users across recreated matches.
- Relationship to Decision 5: a recreated match still clears prior decline
  restrictions only. That clearance does not clear the second-ending cutoff,
  reset lifetime pair budgets or override blocking or authorization rules.
- Existing approvals: D04 Decisions 1-8 remain unchanged. Decision 6's restart
  actor and fresh approval and Decision 7's 12-hour UTC wait still apply to an
  otherwise permitted restart. Request expiry, cancellation/expiry cooldowns,
  decline restrictions, request budgets and seven-day probation are preserved.
- Pending choices: block/unblock behavior, the unmatch flow, account pause,
  access to earlier messages, evidence/retention and reviewed user-facing
  wording remain separate decisions.
- Reason and policy version: record the reviewer's explicit rematch carryover
  instruction in Prompt 03 D04 Decision 9.
- Affected data: pair-level cutoff eligibility and approved-chat ending
  history across match identities. This does not approve a storage design,
  evidence-retention period or budget reset.
- Implementation/evidence and review: documentation only. Implementation
  alignment, focused route/regression evidence and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.

D04 Decision 10 recorded approval (immediate chat on blocker unblock in the reviewed scenario only):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: for the reviewed pair without the second-ending cutoff, the
  blocker's unblock must automatically give the pair chat privileges
  immediately.
- Fresh consent flow: in that reviewed scenario, the pair need not wait for a
  fresh mutual match or a new chat request and approval.
- Example reviewed: Alice blocks Brian, then unblocks him. With no second-ending
  cutoff applying, the unblock immediately gives the pair chat privileges.
- Pending eligibility: whether the pair must have had an approved chat before
  the block, or whether unblock can also grant chat when it was never approved,
  is unresolved. This entry does not select between those scopes.
- Pending counting: whether blocking counts as an approved-chat ending for
  Decisions 8-9 is unresolved. This entry does not reset or redefine the count.
- Existing approvals: D04 Decisions 1-9 remain unchanged. This does not approve
  overriding the second-ending cutoff, another participant's active block or
  account restrictions, or resetting request budgets or probation.
- Superseded proposal: the earlier proposal requiring a fresh mutual match and
  new chat approval after every unblock is rejected for the reviewed scenario.
  This does not select an internal match/grant restoration design or restore
  previous likes or old message access by implication.
- Other pending choices: remaining block/unblock/unmatch details, account
  pause, access to earlier messages, evidence/retention and reviewed wording.
- Reason and policy version: record the reviewer's explicit immediate-unblock
  choice in Prompt 03 D04 Decision 10.
- Affected data: block ownership/state and chat authorization for the pair.
  No evidence-retention period or budget reset is approved.
- Implementation/evidence and review: documentation only. Current code still
  removes only the block row on unblock and requires alignment to this choice.
  Focused route/regression evidence and required H03 specialist review remain
  pending. No release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 11 recorded approval (unblock eligibility includes never-approved chat):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: immediate, automatic chat privileges on blocker unblock also
  apply when chat was never approved. Prior chat approval is not required.
- Example: Alice blocks Brian before approving his first chat request, then
  unblocks him. The unblock immediately gives the pair chat privileges without
  a fresh mutual match, new chat request or recipient approval, subject to the
  preserved cutoff and other active block/account restrictions.
- Scope: resolves the prior-approval eligibility left pending at Decision 10.
  It does not require a previously approved chat to restore and does not select
  an internal match/grant representation.
- Existing approvals: D04 Decisions 1-10 remain unchanged. The second-ending
  cutoff across rematches, other participants' active blocks, account
  restrictions, request budgets and seven-day probation remain preserved.
  No budget reset or exception to those restrictions is approved.
- Pending counting: whether blocking an approved chat counts as an
  approved-chat ending for Decisions 8-9 remains unresolved. No ending-counter
  reset or classification is selected here.
- Other pending choices: remaining block/unblock/unmatch details, account
  pause, access to earlier messages, evidence/retention and reviewed wording.
- Reason and policy version: record the reviewer's explicit inclusion of
  never-approved chat in Prompt 03 D04 Decision 11.
- Affected data: block state and pair chat authorization, including cases with
  no prior approved chat. No evidence-retention period or budget reset is
  approved.
- Implementation/evidence and review: documentation only. Current code still
  needs alignment with Decisions 10-11. Focused route/regression evidence and
  required H03 specialist review remain pending. No release gate is cleared;
  D04, D05 and D12 remain pending overall.

D04 Decision 12 recorded approval (blocking excluded from the two-ending cutoff):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: blocking an approved chat does not count as an approved-chat
  ending toward the two-ending cutoff. Repeated block/unblock cycles do not
  trigger that cutoff.
- Example: Alice and Brian have an approved chat. Alice blocks and unblocks
  Brian twice. Those blocks do not trigger the two-ending cutoff; her unblock
  gives the pair immediate chat privileges under Decisions 10-11, subject to
  the preserved cutoff from any separate chat endings and other active
  block/account restrictions.
- Scope: resolves the blocking/ending-count question left pending at
  Decisions 10-11. The owner's WhatsApp reference selects the stated blocking
  behavior; other blocking details and product behavior remain unspecified.
- Existing approvals: D04 Decisions 1-11 remain unchanged. The separate
  approved-chat ending/restart flow remains governed by Decisions 6-9.
  Blocking/unblocking does not reset those endings, clear an already-triggered
  cutoff or reset request budgets or probation.
- Unblock behavior preserved: automatic, immediate chat privileges also apply
  when chat was never approved, without a fresh mutual match or new chat-request
  approval, subject to the existing restrictions.
- Other pending choices: unmatch behavior and its ending-count classification,
  remaining block/unblock details, account pause, access to earlier messages,
  evidence/retention and reviewed wording.
- Reason and policy version: record the reviewer's explicit exclusion of
  blocking from the cutoff in Prompt 03 D04 Decision 12.
- Affected data: block state, pair chat authorization and approved-chat ending
  classification. No storage design, evidence-retention period or budget reset
  is approved.
- Implementation/evidence and review: documentation only. Implementation must
  distinguish blocking from the separate approved-chat ending event. Alignment,
  focused route/regression evidence and required H03 specialist review remain
  pending. No release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 13 recorded approval (approved chat continues after unmatch):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: when a pair with an approved chat unmatches, they may keep
  chatting. Unmatching alone must not close or revoke that approved chat.
- Example: Alice and Brian have an approved chat. Alice unmatches Brian; both
  may continue chatting without a fresh match, new chat request or approval.
- Consent and counting: the existing approved chat continues, so unmatching
  alone is not an approved-chat ending or restart. No restart cooldown applies
  merely to continuing that chat, and unmatching does not add an approved-chat
  ending toward the cutoff.
- Scope: continuation of an already-approved chat after unmatch. This decision
  does not select behavior for a pair whose chat was never approved. Internal
  match/grant representation, discovery/like cleanup and other unmatch details
  are not selected here.
- Existing approvals: D04 Decisions 1-12 remain unchanged. Active blocks,
  account restrictions and separately ending/revoking the approved chat still
  apply. Request expiry, retry rules, budgets, probation, the second-ending
  cutoff across rematches and the approved unblock behavior remain preserved.
  No budget or ending-count reset is approved.
- Superseded proposal: using the shared close/revoke path to remove approved
  chat access solely because of unmatch is rejected. An active match must not
  be a prerequisite for continuing this approved chat after unmatch.
- Pending choices: remaining unmatch and block/unblock details, account pause,
  access to genuinely closed conversations, evidence/retention and reviewed
  wording remain separate decisions. No retention period or purge clock is
  approved by this choice.
- Reason and policy version: record the reviewer's explicit continued-chat
  choice in Prompt 03 D04 Decision 13.
- Affected data: match relationship state and continuing approved-chat
  authorization. Storage and grant/match binding remain implementation work.
- Implementation/evidence and review: documentation only. The documented
  active-match authorization guard and unmatch close/revoke proposal require
  alignment. Focused route/regression evidence and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.

D04 Decision 14 recorded approval (approved chat continues during discovery pause):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: pausing a profile in discovery keeps existing approved chats
  available to both participants.
- Example: Alice pauses her profile in discovery; Alice and Brian can still
  use their existing approved chat.
- Consent and counting: discovery pause alone does not revoke the approved chat
  or count as an approved-chat ending. Continuing it requires no fresh match,
  request approval or restart cooldown.
- Scope: existing approved chats during a discovery/profile pause. New
  requests, matching behavior and other pause effects remain unspecified.
- Existing approvals: D04 Decisions 1-13 remain unchanged. Active blocks,
  account restrictions and a separate chat ending still apply; the ending
  cutoff across rematches, request budgets, probation and other recorded
  contact rules are preserved. No budget or ending-count reset is approved.
- Reason and policy version: record the reviewer's explicit continued-chat
  choice in Prompt 03 D04 Decision 14.
- Affected data: discovery/profile pause state and existing approved-chat
  authorization. No storage design, retention period or purge clock is approved.
- Implementation/evidence and review: documentation only. Authorization and
  regression evidence must distinguish discovery pause from account
  restrictions that deny chat. Implementation alignment, focused evidence,
  reviewed wording and required H03 specialist review remain pending. No
  release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 15 recorded approval (unsent-original deletion with an existing-report-evidence exception):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: delete the original text of an unsent message unless that
  text has already been preserved as evidence for an existing safety report.
- Rejected model: do not retain every unsent original for moderation. The
  earlier blanket-retention alternative, including its proposed 90-day value,
  is not selected.
- Example: Alice unsends a message with no already-preserved report copy; its
  original text is deleted. If an existing safety report had already preserved
  a copy, that evidence copy is the exception; its access and lifecycle remain
  pending under D05.
- Exception boundary: this covers text already preserved for an existing
  safety report. It does not approve a new evidence copy merely because a
  message is unsent, or broaden the exception to unrelated hold/case proposals.
- D05 boundary: evidence access, retention deadlines and backup deletion
  remain pending under D05. No viewer/role, retention period, backup-deletion
  deadline or completed backup purge is approved by this choice.
- Scope: original-text handling on unsend only. The unsend window, attachment/
  cache projections and reviewed notice wording are not approved here.
- Existing approvals: D04 Decisions 1-14 remain unchanged, including request
  expiry/retry rules, decline restrictions, budgets, seven-day probation,
  chat-ending cutoff, unblock behavior and chat continuation choices.
- Reason and policy version: record the reviewer's explicit deletion model
  and rejection of blanket retention in Prompt 03 D04 Decision 15.
- Affected data: unsent message original text and any already-preserved
  evidence copy for an existing safety report. Storage/access design and
  recoverable-copy handling remain subject to pending D05 decisions.
- Implementation/evidence and review: documentation only. The documented route
  purges ordinary message bodies and uses separate case snapshots; alignment
  verification across applicable projections and focused regression evidence
  remain pending. Reviewed wording and required H03 specialist review remain
  pending. No release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 16 recorded approval (15-minute unsend window from server-recorded sending time):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Approved value: a message may be unsent during a 15-minute window measured
  from that message's server-recorded sending time. After that window,
  unsending is not allowed.
- Clock source: the message's recorded server sending timestamp; the deadline
  is that timestamp plus 15 minutes. This does not select a client/device time
  or a later activity timestamp as the start.
- Example: Alice's message is recorded as sent at 10:00 UTC. Its window ends at
  10:15 UTC; an unsend attempted after that window, such as 10:16 UTC, is denied.
- Scope: product action timing and denial after expiry. Mapping to the stored
  timestamp and server-side enforcement require implementation verification;
  no storage schema, attachment/display behavior or notice wording is selected.
- Existing approvals: D04 Decisions 1-15 remain unchanged. Decision 15's
  original-text deletion rule and already-preserved safety-report evidence
  exception remain applicable; blanket original retention remains rejected.
- D05 boundary: evidence access, retention deadlines and backup deletion remain
  pending under D05. The 15 minutes is an unsend action window, not an evidence
  retention period or a backup-deletion deadline.
- Reason and policy version: record the reviewer's explicit 15-minute window,
  server clock source and post-window denial in Prompt 03 D04 Decision 16.
- Affected data: message server-recorded sending time and unsend eligibility.
  No evidence-retention period or budget reset is approved.
- Implementation/evidence and review: documentation only. The documented route
  uses a 15-minute development timer; clock-source mapping, server-side
  post-window denial and focused route/boundary regression evidence still
  require alignment verification. Reviewed wording and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.

D04 Decision 17 recorded approval (unsent message entry disappears entirely):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "Message entry disappears entirely".
- Approved value: after a successful unsend, the message entry disappears
  entirely from both participants' ordinary chat views. Neither participant
  sees a `Message unsent` placeholder or tombstone for that entry.
- Example: Alice unsends her message to Brian within the approved 15-minute
  window. The message entry disappears from Alice's and Brian's chat views.
- Scope: ordinary chat display after unsend. This does not select other
  projections, attachment/cache handling or recall of delivered notifications.
- Existing approvals: D04 Decisions 1-16 remain unchanged. Decision 15's
  original-text deletion rule and already-preserved existing safety-report
  evidence exception remain applicable; blanket original retention remains
  rejected. Decision 16's server-recorded sending time, 15-minute window and
  denial after that window remain unchanged.
- D05 boundary: evidence access, retention deadlines and backup deletion remain
  pending under D05. Removing an ordinary chat entry does not approve changing
  the already-preserved report-evidence exception.
- Reason and policy version: record the reviewer's explicit selection of entry
  removal in Prompt 03 D04 Decision 17.
- Affected data: ordinary message projections after successful unsend; no new
  evidence copy or retention period is approved.
- Implementation/evidence and review: documentation only. The documented
  current route returns `Message unsent`; removing the entry from both ordinary
  chat views requires implementation alignment and focused regression evidence.
  Other reviewed notices and required H03 specialist review remain pending.
  No release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 18 recorded approval (discovery pause preserves matching, requesting, approving and chatting):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "Pausing discovery must not affecting any matching, requesting,
  approving or chatting functionality."
- Approved value: pausing discovery must not affect any matching, requesting,
  approving or chatting functionality. Matching remains available; chat
  requests can be sent and received, recipients can approve them, and approved
  chats can start or continue while discovery is paused.
- Example: Alice and Brian are matched but have never approved chat. While
  Alice has discovery paused, Brian may send her a request, Alice may approve
  it and both may chat without Alice resuming discovery, provided the normal
  independent contact rules allow it. Discovery pause also imposes no extra
  restriction on eligible matching.
- Scope: effects of discovery pause on those four functions. Exact discovery
  presentation and unrelated pause details are not selected.
- Existing approvals: D04 Decisions 1-17 remain unchanged. This extends
  Decision 14's continued existing approved chat to the full matching,
  requesting, approving and chatting functionality specified by the owner.
  Normal eligibility, block/account restrictions, request budgets, cooldowns,
  decline rules and the second-ending cutoff still apply. Pause neither
  automatically approves a request nor resets or bypasses those rules.
- D05/D12 boundary: evidence access, retention deadlines and backup deletion
  remain pending under D05. No geographic matching policy or production
  location use is approved under D12.
- Reason and policy version: record the reviewer's explicit selection of
  unaffected functionality in Prompt 03 D04 Decision 18.
- Affected data: discovery-pause state and matching, request, approval and chat
  authorization paths. No schema, budget reset or retention period is selected.
- Implementation/evidence and review: documentation only. Matching, request,
  approval and chat paths require alignment verification and focused pause
  regression evidence. Reviewed notices and required H03 specialist review
  remain pending. No release gate is cleared; D04, D05 and D12 remain pending
  overall.

D04 Decision 19 recorded approval (unmatch preserves pending request; approval waits for matched state):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "Unmatching mustn't cancel pending request, so Brian can still
  approve it only when the unmatch is switched back to match."
- Approved value: unmatching alone does not cancel the pending chat request.
  The recipient cannot approve it while the pair is unmatched. Once the pair
  returns to a matched state, the recipient may approve that same request if
  it is still pending and the normal independent restrictions allow approval.
- Example: Alice sends Brian a request, then they unmatch before he approves.
  The request survives, but Brian cannot approve while they are unmatched.
  If their matched state returns before the request expires, Brian may approve
  the preserved request; no fresh request is required merely because of unmatch.
- Existing approvals: D04 Decisions 1-18 remain unchanged. Decision 1's
  seven-day UTC expiry from original creation continues; unmatching and
  restoring the match do not pause/reset that clock or revive an expired
  request. Existing budgets, cooldowns, decline restrictions, blocks/account
  restrictions and the second-ending cutoff remain applicable.
- Approved-chat boundary: Decision 13 still allows an already-approved chat to
  continue after unmatch. Decision 19's matched-state prerequisite concerns
  approval of a pending request, not continuation of already-approved chat.
- Pending semantics: who can switch the pair back to matched, and whether that
  requires renewed agreement by both users, remain unresolved. No unilateral
  undo, fresh reciprocal-like mechanism, new cooldown or schema is selected.
- D05 boundary: evidence access, retention deadlines and backup deletion remain
  pending under D05; preserving this pending request selects no new retention
  period.
- Reason and policy version: record the reviewer's explicit pending-request
  preservation and matched-state approval condition in Prompt 03 D04 Decision 19.
- Affected data: pending request identity/state, original expiry and pair match
  state at approval. Internal mapping across match restoration remains
  implementation work.
- Implementation/evidence and review: documentation only. Pending-request
  preservation, approval denial while unmatched and approval after matched-state
  restoration require alignment verification and focused regression evidence,
  including expiry during unmatch. Reviewed notices and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.

D04 Decision 20 recorded approval (person who unmatched may restore without extra agreement):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "She may restore the match hereself. No need for extra agreements".
- Approved value: the person who performed the unmatch may restore the match
  herself without additional agreement from the other participant. Renewed
  reciprocal agreement is not required for this restoration.
- Example: Alice unmatched Brian. Alice may switch the pair back to matched
  without Brian agreeing to the match again. If Alice's chat request remains
  pending, Brian must still approve that request under Decision 19 before
  chatting begins in that pending-request scenario.
- Scope: restoration by the person who unmatched. This does not decide whether
  the other participant may also restore the match or what happens when both
  participants have independently unmatched.
- Existing approvals: D04 Decisions 1-19 remain unchanged. Decision 19's
  matched-state requirement for approval of the same still-pending request,
  original seven-day UTC expiry and denial of expired-request revival remain
  applicable. Restoration alone does not approve the pending chat request.
  Decision 13's continued already-approved chat after unmatch remains intact.
  Normal eligibility, blocks/account restrictions, request budgets, cooldowns,
  decline rules and the second-ending cutoff remain applicable; no new reset or
  exception is approved.
- Pending values: no match-restoration cooldown or count cap is selected.
  Other restoration actors/cases, matching-side cleanup and internal state
  representation remain pending. Decision 5's recorded decline/rematch rule is
  unchanged; this decision approves no additional restriction clearance.
- D05 boundary: evidence access, retention deadlines and backup deletion remain
  pending under D05. No new retention period is selected.
- Reason and policy version: record the reviewer's explicit restoration actor
  and lack of renewed agreement in Prompt 03 D04 Decision 20.
- Affected data: unmatch actor, restored pair match state and preserved pending
  request. No schema or request-budget reset is selected.
- Implementation/evidence and review: documentation only. The earlier proposal
  requiring fresh reciprocal actions for restoration is superseded for this
  scenario. Actor authorization, restoration without renewed agreement and
  preserved request/expiry/contact restrictions require implementation
  alignment and focused regression evidence. Reviewed notices and required H03
  specialist review remain pending. No release gate is cleared; D04, D05 and
  D12 remain pending overall.

D04 Decision 21 recorded approval (only the person who unmatched may restore):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "Only Alices has ability".
- Approved value: in the reviewed case where Alice unmatched Brian, only Alice
  may restore that match. Brian cannot restore Alice's unmatch.
- Scope: restoration of an unmatch performed by one participant. Behavior when
  both participants independently unmatch remains unresolved; this decision
  does not select how their restoration actions would combine.
- Existing approvals: D04 Decisions 1-20 remain unchanged. Decision 20 still
  allows Alice to restore without Brian's renewed match agreement. Under
  Decision 19, a preserved pending chat request still requires its recipient's
  approval while matched and before the original seven-day UTC expiry.
  Restoration does not automatically approve chat, reset expiry or revive an
  expired request. Decision 13's continued already-approved chat after unmatch
  and all recorded budgets, cooldowns, decline rules, block/account restrictions
  and the second-ending cutoff remain applicable.
- Pending values: behavior when both users unmatch and remaining restoration
  mechanics/timing/caps remain pending; no new cooldown or cap is selected.
- D05 boundary: evidence access, retention deadlines and backup deletion remain
  pending under D05.
- Reason and policy version: record the reviewer's explicit denial of
  restoration by the participant who did not unmatch in Prompt 03 D04 Decision 21.
- Affected data: unmatch actor and authorization for restoring that unmatch.
- Implementation/evidence and review: documentation only. Actor authorization
  and denial of Brian's attempt to restore Alice's unmatch require alignment
  and focused regression evidence. Reviewed notices and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.

D04 Decision 22 recorded approval (both must undo their own unmatch before the match returns):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "They must both undo thier own unmatch before match can return."
- Approved value: if both participants independently unmatch, each must undo
  their own unmatch before the match can return. One person's undo is
  insufficient, and neither may undo the other participant's unmatch.
- Example: Alice and Brian both unmatch. Alice undoes her own unmatch, but
  Brian's unmatch remains, so the pair stays unmatched. The match cannot return
  until Brian also undoes his own unmatch, subject to the existing independent
  eligibility and contact restrictions.
- Existing approvals: D04 Decisions 1-21 remain unchanged. Decisions 20-21
  continue to govern who may undo an unmatch. Decision 19's preserved pending
  request cannot be approved until the pair is matched again and only while
  that original request is still pending. Its seven-day UTC expiry is not
  paused/reset by either undo, and expired requests are not revived.
  Restoration alone does not approve the pending chat request. Decision 13
  still preserves already-approved chat while unmatched.
- Scope and pending values: the condition for match restoration when both
  users unmatch. Internal representation, remaining restoration details and
  any unspecified cooldown or cap remain pending. All recorded request budgets,
  cooldowns, decline rules, blocks/account restrictions and the second-ending
  cutoff remain applicable; no new reset or exception is approved.
- D05 boundary: evidence access, retention deadlines and backup deletion remain
  pending under D05.
- Reason and policy version: record the reviewer's explicit requirement for
  each participant's own undo in Prompt 03 D04 Decision 22.
- Affected data: each participant's unmatch/undo state and eligibility for the
  pair's matched state. No schema or storage duration is selected.
- Implementation/evidence and review: documentation only. Actor authorization,
  one undo leaving the pair unmatched and requiring both own undo actions
  before restoration need implementation alignment and focused regression
  evidence, including unchanged request expiry and approval requirements.
  Reviewed notices and required H03 specialist review remain pending. No
  release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 23 recorded approval (combined block/report; pending requests cleared; unblock after safety review):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "No if one wants to report a person in conjuction to blocking
  they should choose a block and report option, this option should not allow
  unblock priviledge or keep any pending requests as well."
- Clarified unblock choice: "Available after safety review".
- Approved value: use a combined `Block and report` option when reporting a
  person in conjunction with blocking them. This flow initially provides no
  unblock privilege and clears any pending chat requests between that pair.
  Unblocking becomes possible after safety review; permanent loss of the
  unblock privilege is not selected.
- Example: Alice chooses `Block and report` for Brian. Pending requests between
  Alice and Brian are cleared, and Alice cannot unblock before safety review.
  The unblock privilege can become available after that review; the required
  review outcome and authority remain unresolved.
- Request scope: pending requests between this pair must not remain pending
  or available for approval in this combined flow. The terminal-state name,
  storage representation and physical deletion of request records are not
  selected; retention/deletion details remain pending under D05.
- Existing approvals: D04 Decisions 1-22 remain unchanged. Ordinary block/
  unblock Decisions 10-12 and unmatch Decisions 13 and 19-22 are preserved;
  the combined action has the separate restriction recorded here. Clearing
  pending requests does not reset request budgets or the pair-lifetime count.
  Decision 4's fixed 168-hour reported-sender probation and prohibition on early
  restoration of the normal sending allowance remain unchanged; review of
  this pair's unblock privilege does not approve early rate restoration.
- Pending semantics: safety-review outcome criteria, reviewer authority,
  review timing, automatic versus user-initiated unblock and subsequent
  chat-restoration effects remain unresolved. No review deadline or automatic
  clearance is selected. Reporting outside the reviewed combined flow,
  including unmatch-only reporting, remains unresolved; no general permission
  for separate later reporting after block/unmatch is approved here.
- D05 boundary: evidence access, retention deadlines, physical request-record
  deletion and backup deletion remain pending. Decision 15's original-text
  deletion and already-preserved safety-report evidence exception are unchanged.
- Reason and policy version: record the owner's combined-action choice and
  clarified safety-review prerequisite in Prompt 03 D04 Decision 23.
- Affected data: block/report action, pair pending-request state and eligibility
  for a later unblock option. No schema, retention period or budget reset is
  selected.
- Implementation/evidence and review: documentation only. Combined-action
  behavior, pending-request clearing, denial of unblock before review and
  separation from ordinary block/unmatch need alignment and focused regression
  evidence. Review-outcome and later-unblock fixtures remain provisional.
  Reviewed notices and required H03 specialist review remain pending. No
  release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 24 recorded approval (explicit safety-reviewer unblock approval; optional app ban):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "A safety reviewer must explicitly approve unblocking,
  otherwise they can ban any policy/ethical attacker from the app."
- Clarified ban choice: "Reviewer may leave blocked without a ban".
- Approved unblock requirement: after `Block and report`, a safety reviewer
  must explicitly approve unblocking. Merely finishing a review does not
  restore the unblock option; without that approval the pair remains blocked.
- Approved ban authority: safety reviewers may ban users from the app for
  policy or ethical violations. This is a separate review action. A reviewer
  may deny unblocking and leave the pair blocked without imposing an app ban;
  refusal to approve unblocking does not automatically require a ban.
- Example: Alice blocks and reports Brian. The safety review ends without
  explicit approval to unblock, so the pair remains blocked. The reviewer may
  leave that restriction in place without banning Brian, or use the approved
  option to ban a user from the app for policy or ethical violations.
- Scope and pending values: the required approval outcome and safety-reviewer
  role, plus the optional app ban action. Definitions and substantive approval/
  ban criteria, named role assignments and permission mapping, ban duration,
  appeals/reversal, review timing, actual unblock trigger and subsequent chat
  restoration remain pending. No automatic unblock, ban duration, appeal rule
  or additional report-triggered ban is selected.
- Existing approvals: D04 Decisions 1-23 remain unchanged. Decision 23's pending
  pair requests are cleared and are not restored as pending by this approval.
  Ordinary block/unblock and unmatch approvals, account restrictions, request
  budgets, cooldowns, decline rules and the second-ending cutoff are preserved.
  Decision 4's fixed 168-hour reported-sender probation is unchanged; review
  does not reset it or authorize early restoration of the normal sending rate.
- D05 boundary: evidence access, retention deadlines, physical request-record
  deletion and backup deletion remain pending. This product authority does
  not approve a new evidence-access model or retention period.
- Reason and policy version: record explicit reviewer approval for unblocking,
  the option to ban a user from the app and the clarified ability to leave blocked without a ban
  in Prompt 03 D04 Decision 24.
- Affected data: safety-review outcome, eligibility for a pair's unblock option
  and an account-ban decision. No schema, case-assignment model or duration is
  selected.
- Implementation/evidence and review: documentation only. Explicit-approval
  enforcement, denial after review without approval, authority to impose an app ban
  and the outcome that leaves the pair blocked without a ban need alignment and focused regression
  evidence. Unselected criteria/duration and later-unblock fixtures remain
  provisional. Reviewed notices and required H03 specialist review remain
  pending. No release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 25 recorded approval (blocker chooses whether to unblock after reviewer approval):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "She can choose whether to press unblock".
- Approved value: after the safety reviewer's explicit approval under Decision
  24, the blocker chooses whether to press `Unblock`. Approval restores the
  option; it does not automatically remove the block.
- Example: the reviewer explicitly approves Alice unblocking Brian. Alice may
  still choose to keep him blocked. Her block stays in place until she chooses
  `Unblock`; only her own block is affected by that action.
- Scope: the action that removes this block after approval in the combined
  `Block and report` flow. Chat restoration after the actual unblock remains
  unresolved; neither automatic chat nor a fresh-request requirement is
  selected here. Ordinary block/unblock Decisions 10-12 remain unchanged.
- Existing approvals: D04 Decisions 1-24 remain unchanged. Explicit reviewer
  approval is still required; a completed review alone is insufficient.
  Decision 24's option to impose an app ban or leave the pair blocked without a
  ban remains unchanged. Decision 23's cleared pending requests are not restored as pending
  by approval or this choice. Independent account restrictions, other active
  blocks, request budgets, cooldowns, decline rules, the second-ending cutoff
  and fixed 168-hour reported-sender probation remain applicable.
- Pending values: chat effects after the chosen unblock, substantive approval/
  ban criteria, named role assignments, ban duration/appeals, review timing and
  other unspecified policy details remain pending.
- D05 boundary: evidence access, retention deadlines, physical request-record
  deletion and backup deletion remain pending.
- Reason and policy version: record the reviewer's explicit choice to leave
  actual unblocking to the blocker in Prompt 03 D04 Decision 25.
- Affected data: eligibility for the unblock option and the blocker's explicit
  action on their own block. No schema, retention period or budget reset is
  selected.
- Implementation/evidence and review: documentation only. Reviewer approval
  without blocker action must leave the block in place; the optional blocker
  action and preservation of independent restrictions require implementation
  alignment and focused regression evidence. Fixtures for subsequent chat effects
  remain provisional. Reviewed notices and required H03 specialist review remain
  pending. No release gate is cleared; D04, D05 and D12 remain pending overall.

D04 Decision 26 recorded approval (immediate chat after reviewed, chosen unblock):

- Reviewer: Takudzwa Hondova.
- Decision date: 2026-10-15, Africa/Harare.
- Owner choice: "Chat must become immediately available."
- Approved value: in the combined `Block and report` flow, once the safety
  reviewer explicitly approves unblocking and the blocker chooses `Unblock`,
  chat becomes immediately available. No fresh chat request or further chat
  approval is required.
- Example: Alice used `Block and report` for Brian. The reviewer explicitly
  approves unblocking, and Alice later chooses `Unblock`. Chat is immediately
  available, subject to the existing independent restrictions.
- Existing approvals: D04 Decisions 1-25 remain unchanged. Reviewer approval
  alone does not remove Alice's block; her actual choice under Decision 25 is
  still required. Decision 23's cleared pending requests remain cleared; this
  access does not revive them or reset request budgets or pair-lifetime counts.
  Other active blocks, account restrictions, decline rules where applicable,
  the second-ending cutoff and fixed 168-hour reported-sender probation remain
  applicable. No additional reset or exception is approved.
- Scope: chat availability after the reviewed, chosen unblock. Grant/storage
  representation is implementation work; no schema or data-retention duration
  is selected. Ordinary block/unblock approvals remain intact.
- Pending values: substantive approval/ban criteria, named role assignments,
  ban duration/appeals, review timing and other unselected policies remain
  pending.
- D05 boundary: evidence access, retention deadlines, physical request-record
  deletion and backup deletion remain pending. Immediate chat does not restore
  deleted unsent originals or change the existing report-evidence exception.
- Reason and policy version: record the owner's explicit selection of immediate
  chat availability in Prompt 03 D04 Decision 26.
- Affected data: pair block state, explicit safety-review approval, the
  blocker's chosen unblock and chat authorization. No cleared request is
  re-created by this decision.
- Implementation/evidence and review: documentation only. Explicit approval,
  blocker choice, immediate chat after that choice, cleared-request handling
  and preservation of independent restrictions require implementation alignment
  and focused regression evidence. Reviewed notices and required H03 specialist
  review remain pending. No release gate is cleared; D04, D05 and D12 remain
  pending overall.
- Review format: the owner requested all remaining questions together.
  Questionnaire Q01-Q48 below contains pending questions, not additional
  approvals or permission to execute policy.

Current implemented behavior and evidence:

- A match does not authorize chat. `api/message_requests.php`,
  `api/messages.php` and `php/policy_safeguards.php` require approved chat for
  message read/write.
- Message request states currently include `pending`, `approved`, `declined`,
  `cancelled`, `expired` and `revoked`.
- Implemented timer now approved by D04 Decision 1: pending request expiry at
  seven days after creation, measured in UTC.
- Approved but not yet implementation-aligned D04 Decision 2: 12-hour retry
  after cancellation or expiry, measured in UTC from the cancellation timestamp
  or stored `expires_at` timestamp.
- Approved but not yet implementation-aligned D04 Decision 3: zero further
  same-direction attempts after decline. Current code still reflects the older
  provisional 30-day declined-request retry path.
- Implemented provisional timer still pending alignment: current code uses a
  24-hour retry after cancel/expiry.
- Approved but not yet implementation-aligned D04 Decision 4: sender budget of
  3 requests across all matches per 12 hours, recipient budget of 10 incoming
  requests across all requesters per 12 hours, pair lifetime budget of 3
  requests, reported-sender probation budget of 3 requests per 24 hours and
  fixed 7-day probation lasting 168 hours from activation in UTC. No review or
  support appeal may restore the normal allowance early, and normal allowance
  restoration after probation does not reset lifetime pair budgets or override
  declined-request, blocking or authorization rules. Another report during the
  probation week leaves the active 168-hour probation period unchanged; it does
  not restart or extend that period.
- Approved but not yet implementation-aligned D04 Decision 5: after a recipient
  declines a request, that recipient may later send a reverse-direction request
  to the original requester, and a recreated match clears prior decline
  restrictions. Recreated matches do not reset lifetime pair budgets or override
  blocking, authorization or account restrictions.
- D04 Decision 6 approves restart only by the person who ended the approved
  chat, through a fresh request that the other participant must approve.
  Implementation alignment and regression evidence remain pending.
- D04 Decision 7 approves a 12-hour UTC cooldown from ending/revoking the
  approved chat before sending the fresh restart request. Implementation
  alignment and regression evidence remain pending.
- D04 Decision 8 approves that a second approved-chat end terminates re-request
  privileges for the rest of that match's lifetime. Implementation alignment
  and regression evidence remain pending.
- D04 Decision 9 approves carryover of the second-ending cutoff across
  rematches between the same two users. Unmatching and matching again cannot
  restore chat-request privileges after that cutoff. Decision 5's decline-only
  clearance does not clear the cutoff. Implementation alignment and regression
  evidence remain pending.
- D04 Decision 10 approves immediate, automatic pair chat privileges on the
  blocker's unblock in the reviewed scenario without a second-ending cutoff.
  Prior-approval eligibility is resolved by Decision 11, and blocking's
  exclusion from the ending cutoff is resolved by Decision 12. Implementation
  alignment and regression evidence remain pending.
- D04 Decision 11 approves immediate chat privileges on blocker unblock even
  when chat was never approved; no prior chat approval is required.
  Implementation alignment and regression evidence remain pending.
- D04 Decision 12 excludes blocking an approved chat from the two-ending
  cutoff. Repeated block/unblock cycles do not trigger it or clear an existing
  cutoff from separate chat endings. Implementation alignment and regression
  evidence remain pending.
- D04 Decision 13 approves continued approved chat after unmatch. The
  documented active-match authorization checks and unmatch close/revoke design
  require alignment; implementation and regression evidence remain pending.
- D04 Decision 19 approves preserving pending requests on unmatch while
  denying approval until the matched state returns. The original expiry and
  other restrictions remain unchanged. Decision 20 approves restoration by
  the person who unmatched without new agreement from the other participant.
  Decision 21 denies restoration by the other participant in that case.
  Decision 22 requires both participants to undo their own unmatch before the
  match can return when both unmatched; one person's undo is insufficient.
  Remaining mechanics/timing/caps, implementation alignment verification and
  regression evidence remain
  pending.
- D04 Decision 14 approves continued use of existing approved chats while a
  profile is paused in discovery. Decision 18 extends this to matching,
  requesting, approving and chatting: discovery pause must not affect those
  functions. Normal independent restrictions remain applicable; implementation
  alignment verification and focused pause regression evidence remain pending.
- D04 Decision 15 approves deleting unsent original text unless already
  preserved for an existing safety report, and rejects blanket moderation
  retention. Alignment verification and regression evidence remain pending;
  evidence access, retention deadlines and backup deletion remain pending D05.
- D04 Decision 16 approves 15 minutes from the message's server-recorded
  sending time for unsend, with denial after that window. Clock-source mapping,
  server-side enforcement and focused regression evidence require verification.
- D04 Decision 17 approves removing an unsent message entry entirely from both
  participants' ordinary chat views, without a placeholder. The current
  `Message unsent` behavior requires implementation alignment and regression
  evidence.
- D04 Decision 23 selects a combined `Block and report` action, clearing
  pending pair requests and initially withholding unblock; unblocking becomes
  possible after safety review. Decision 24 requires a safety reviewer's
  explicit approval; completing review alone is insufficient. Reviewers may
  impose an app ban for policy or ethical violations or leave the pair blocked
  without a ban. Substantive criteria, role assignments, ban duration/appeals
  and later unblock effects remain pending. Ordinary block/unmatch approvals
  and fixed sender probation remain unchanged. Alignment and regression
  evidence remain pending.
- D04 Decision 25 approves that explicit reviewer approval restores the
  blocker's choice to press `Unblock`; approval alone leaves the block in place.
  Decision 26 approves immediate chat after that chosen unblock, without a
  fresh request or further chat approval; cleared requests and independent
  restrictions remain unchanged. Alignment and regression evidence remain
  pending.
- Rate limits currently include `message_request` 20 per hour and `message`
  30 per minute per user.
- Either participant can revoke an active permission. Block closes matches,
  revokes chat grants, deletes ordinary swipes, suppresses obsolete chat
  notifications and prevents later sends.
- Current implemented unblock removes only the block row; it does not restore
  match or chat consent. This differs from Decisions 10-11 and requires
  implementation alignment.
- Current development behavior allows sender-only unsend for 15 minutes from
  server time when the message columns exist. It purges ordinary body visibility
  and returns `Message unsent`. Decision 16 now approves the time window and
  server-recorded sending-time source; mapping and post-window denial still
  require alignment verification. Decision 15 selects original-text handling.
  Decision 17 supersedes the placeholder for ordinary chat views; the current
  route and both participants' chat views require alignment.
- Prompt 02 route and overlap evidence passed request/approve/revoke, non-sender
  unsend denial, sender unsend, block cleanup, approve-vs-revoke overlap and
  send-vs-block overlap.

Recorded choices and remaining decisions:

| Choice | Recorded approval or pending choice |
|---|---|
| Reverse/rematch after decline | D04 Decision 5 approves reverse-direction requests after decline by the previous recipient, and approves that a recreated match clears prior decline restrictions. Required implementation alignment remains. Recreated matches do not reset lifetime pair budgets or override blocking, authorization or account restrictions. |
| Reported-sender repeat report | D04 Decision 4 approves a reported-sender reduced allowance of 3 requests per 24 hours instead of a sending ban, with fixed 7-day probation lasting 168 hours from probation activation in UTC. No review or support appeal may restore the normal allowance early; support may accept a written mistaken-report appeal only within that boundary. After probation expires, normal sender allowance returns subject to other restrictions, without resetting pair lifetime budgets or overriding declined-request, blocking or authorization rules. Another report during the probation week leaves the active 168-hour probation period unchanged; it does not restart or extend that period. |
| Revoke/restart behavior | D04 Decision 6 approves restart only by the person who ended the approved chat, using a fresh request that the other participant must approve. D04 Decision 7 approves a 12-hour UTC cooldown from ending/revoking the approved chat before the fresh restart request. D04 Decision 8 approves a second approved-chat end terminating re-request privileges for the rest of that match's lifetime, even if request budget remains. D04 Decision 9 approves carryover of that cutoff across rematches between the same two users; a new match must not restore chat-request privileges after the second approved-chat ending. Decision 5's decline-only clearance does not clear this cutoff. Decision 12 excludes blocking an approved chat from the two-ending cutoff. Current code's lack of a separate revoke-specific cooldown still needs implementation alignment. Implementation alignment and regression evidence remain pending. |
| Block/unblock/rematch | Ordinary block/unblock: Decision 10 approves immediate, automatic chat privileges when the blocker unblocks in the reviewed scenario without a second-ending cutoff; no fresh mutual match or new chat-request approval is required in that scenario. Decision 11 confirms this also applies when chat was never approved; prior chat approval is not required. Decision 12 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it. Other block/unblock details remain pending. Current code only removes the block row and still needs alignment. Decision 9's cutoff is preserved. |
| Block and report | Decision 23 selects the combined action for reporting with blocking, clearing pending pair requests and initially providing no unblock privilege. Decision 24 requires a safety reviewer's explicit approval; completing review alone is insufficient. Reviewers may ban for policy or ethical violations or leave the pair blocked without a ban. Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone keeps the block in place. Decision 26 approves immediate chat after that chosen unblock, without a fresh request or further chat approval; cleared requests remain cleared and independent restrictions apply. Substantive criteria, role assignments, ban duration/appeals and review timing remain pending. Ordinary block/unblock approvals remain unchanged. Reporting outside the combined flow and D05 evidence access/retention/physical deletion remain unresolved. |
| Unmatch | Decision 13 preserves already-approved chat after unmatch without a new request/cooldown or approved-chat ending. Decision 19 preserves a pending request on unmatch but denies approval until the pair is matched again, provided that same request is still pending; its original seven-day expiry is unchanged. Decision 20 approves that the person who unmatched may restore without new agreement from the other participant; restoring does not approve the pending chat request. Decision 21 limits restoration to the person who unmatched in the reviewed case; the other participant cannot restore it. Decision 22 requires both participants' own undo actions before the match can return when both unmatched. One person's undo is insufficient. Remaining mechanics/timing/caps, discovery/like cleanup, storage details and implementation/regression evidence remain pending. The documented authorization guard must distinguish pending-request approval from continued approved chat. |
| Account pause | Decision 14 preserves existing approved chat. Decision 18 approves that discovery pause does not affect matching, requesting, approving or chatting, including new requests and approvals while paused. Normal independent contact restrictions remain applicable. Exact discovery presentation and unrelated pause details remain unselected. Current protected actions check account eligibility without an explicit pause policy; alignment verification and regression evidence remain pending. |
| Unsent originals | Decision 15 approves deleting original text unless already preserved as evidence for an existing safety report. Blanket retention of every unsent original for moderation is rejected. Evidence access, retention deadlines and backup deletion remain pending under D05. The current ordinary-body purge and separate snapshot behavior require alignment verification. Decision 16 separately approves the action window; notice wording remains pending. |
| Unsend action window | Decision 16 approves 15 minutes from the message's server-recorded sending time; after that window, unsending is not allowed. Server-side clock mapping, post-window denial evidence, other projections and reviewed wording remain pending. |
| Unsend display | Decision 17 approves that the message entry disappears entirely from both participants' ordinary chat views, without a `Message unsent` placeholder. The current placeholder requires implementation alignment and regression evidence; other projection details remain unspecified. |
| Closed-chat access | Access and retention of genuinely closed conversations remain pending under D05; the earlier 90-day ceiling is proposed only. Evidence access, retention deadlines and backup deletion are not approved by Decision 15. Decision 13 preserves an approved chat after unmatch, so unmatch alone does not close it. |

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
originals; engineering reviewer for implementation. Named reviewer for D04
Decisions 1-26: Takudzwa Hondova. Other reviewer/owner roles remain unassigned.
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
| R02 | Partial pass. Request/approve/revoke and approve-vs-revoke overlap passed. D04 Decision 1 now approves pending request expiry at seven days after creation, measured in UTC. D04 Decision 2 now approves a 12-hour cancellation/expiry retry cooldown measured in UTC from cancellation time or stored `expires_at`. D04 Decision 3 now approves zero further same-direction attempts after decline. D04 Decision 4 now approves sender, recipient, pair-lifetime and reported-sender request budgets plus fixed 7-day reported-sender probation, no early restoration and no probation restart/extension for another report during the probation week. D04 Decision 5 now approves reverse-direction requests after decline and recreated-match clearance of prior decline restrictions. D04 Decision 6 approves restart only by the person who ended an approved chat, through a fresh request requiring the other participant's approval. D04 Decision 7 approves a 12-hour UTC cooldown from ending/revoking that chat before the fresh restart request. D04 Decision 8 approves a second approved-chat end terminating re-request privileges for the rest of that match's lifetime. D04 Decision 9 approves carryover of that cutoff across rematches between the same two users; Decision 5's decline-only clearance does not clear it. D04 Decision 10 approves immediate chat privileges on blocker unblock in the reviewed scenario without that cutoff. D04 Decision 11 confirms this includes never-approved chat; prior chat approval is not required. D04 Decision 12 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it. D04 Decision 13 approves continued approved chat after unmatch without treating unmatch as a chat ending. D04 Decision 14 approves continued existing approved chat during discovery/profile pause. D04 Decision 18 approves that discovery pause does not affect matching, requesting, approving or chatting. D04 Decision 19 preserves pending requests on unmatch but denies approval until the matched state returns, with original expiry unchanged. D04 Decision 20 allows the person who unmatched to restore without renewed agreement from the other participant. D04 Decision 21 limits restoration to that person in the reviewed case; the other participant cannot restore it. D04 Decision 22 requires each participant to undo their own unmatch before the match can return when both unmatched. D04 Decision 23 selects combined block/report and clears pending pair requests. D04 Decision 24 requires explicit safety-reviewer approval for unblocking and permits app bans for policy or ethical violations; a reviewer may instead leave the pair blocked without a ban. D04 Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone keeps the block in place. D04 Decision 26 approves immediate chat after the explicitly approved, chosen unblock, without a fresh request or further chat approval. Substantive criteria remain pending. | Implementation alignment and regression evidence for Decisions 2-14 and 18-26 remain required, including authorized restoration without renewed agreement, pending-request preservation, denial while unmatched, recipient approval after restoration, unchanged expiry/contact restrictions, continued approved chat after unmatch and the four functions during discovery pause. Remaining mechanics/timing/caps, substantive review/ban criteria, role assignments, ban duration/appeals, reporting outside the combined flow, other unmatch/block/unblock details, discovery presentation and unrelated pause details remain pending D04. Decision 22's requirement for both own undo actions needs alignment and regression evidence. Decision 21's denial for the participant who did not unmatch requires alignment and regression evidence. |
| R03 | Partial pass. Block route and send-vs-block overlap passed; messages/evidence survive normal close path. | Account deletion overlap beyond covered stale-session route, rollback injection, legacy cascade audit and production retention remain open. |
| R04 | Pass for current actual unsend route. Decision 15 approves original-text deletion except already-preserved evidence for an existing safety report, and rejects blanket retention. Decision 16 approves a 15-minute unsend window from server-recorded sending time, with denial after expiry. Decision 17 approves removing the entry entirely from both ordinary chat views, without a placeholder. | Clock-source/enforcement and ordinary chat-view alignment verification, focused regression evidence, other attachment/cache projections and reviewed wording remain pending. Evidence access, retention deadlines, backup deletion and closed-chat lifecycle/access remain pending under D05. |
| R05 | Partial pass. Fake-mail signup route, units, SMTP fixture and outbox regressions passed. | Real sender/inbox/ACS delivery, reset delivery and full terminal delivery-failure handling remain open. |
| R06 | Partial pass. Account deletion route marks deleted, revokes stale session, cleans relationships and records protective events. | Full personal-data inventory, media/cache/log purge and D05 owner acceptance remain open. |
| R07 | Partial. Existing DB/service confession erasure and rendered timestamp evidence remain. | Owner decision on deletion vs continued anonymous publication, suppressed-reference browser coverage and public notice remain open. |
| R08 | Not run. | Environment-specific personal-data inventory and actual purge/access effects remain D05/D06/D12 work. |
| R09 | Partial. Cleanup/replay rows are asserted for deletion and moderation cases. | Independent replay horizon, consent proof fields/deadline and all recoverable copies remain unapproved/unrehearsed. |
| R10 | Partial. UTC rendered timestamp and purge due fields are covered. | Full retention worker, hold release, fake-clock deadlines and no-reset proof remain open. |
| R11 | Partial pass. Outsider report denial/evidence capture and role-removal sessions passed. | Assigned-moderator case evidence UI/API, access assignment and audit acceptance remain open. |
| R12 | Not run. | No isolated Azure restore or independent protective replay rehearsal has occurred. Seeded fail-closed tests are not actual restore proof. |

## Prioritized Owner Questions

**Questionnaire history:** the wording below is the issued Q01-Q48 question
list, not a claim that all answers remain missing. Current answers, approved
portions, proposed defaults and remaining real-world inputs are in the linked
acceptance supplement. Do not launch a fresh 48-question sequence.

Owner request on 2026-10-15: present all remaining questions together. This
batch covers the currently unresolved owner choices in D04-D12 and the linked
policy/retention/location proposals. D03's full feature scope and D04 Decisions
1-26 are preserved. Questionnaire references Q01-Q48 are not new approvals.

Answer by question number; an unanswered item or an explicit `pending` remains
pending. Related fields are grouped so they can be answered together. Proposed
retention durations elsewhere in the repository are not accepted by this list.
D05 evidence access, retention deadlines and backup deletion remain pending.

1. **Q01 [D04] Reporting routes.** Which routes should be allowed outside the combined Block and report flow: reporting without blocking, reporting after unmatching, or adding a report to an existing ordinary block? For example, Alice unmatched Brian and now wants to report him.

2. **Q02 [D04] Report targets.** What can users report: a person, a message, a match, a confession, or a reply/reference to a confession? What information must a report contain?

3. **Q03 [D04] Moderation criteria.** What behaviour qualifies as a policy or ethical violation, and what findings justify approving unblocking, keeping a pair blocked, or imposing an app ban?

4. **Q04 [D04] Ban length and reversal.** Can app bans be temporary, permanent, or both? Specify durations or selection criteria and who may lift a ban.

5. **Q05 [D04/D09] Reviews and appeals.** What response deadlines, overdue escalation and appeal process apply to block/report and ban decisions? The already-approved fixed seven-day sending probation remains unchanged.

6. **Q06 [D04/D05] Unmatch visibility and restoration.** After unmatching, should the pair appear in each other's discovery results, and should their earlier likes be cleared or retained? Restoring the match still follows the approved own-undo rules. Does restoring the old match count as a recreated match for Decision 5's already-approved clearing of decline restrictions?

7. **Q07 [D04] New requests while unmatched.** When a pair has no currently approved chat or pending request, may they send a new chat request while unmatched, or must they restore the match first? Existing cooldowns and budgets still apply.

8. **Q08 [D04] Undo limits.** Should undoing one's own unmatch have any waiting period or count limit? If so, specify the exact limits; otherwise choose none. This does not change the approved chat-ending restart cooldown.

9. **Q09 [D04] Discovery-pause visibility.** What exactly should discovery pause change about who sees the profile or recommendations? Matching, requesting, approving and chatting must remain available as already approved.

10. **Q10 [D04/D05] Unsend beyond the chat entry.** What should unsend remove or revoke from attachments, download links, previews, caches and in-app notifications? The approved 15-minute window, disappearing entry and original-text deletion exception remain unchanged.

11. **Q11 [D05] Incomplete signup.** When should pending-email accounts and email-approved but unfinished onboarding accounts expire, and which event starts each clock?

12. **Q12 [D05] Underage records.** Which minimal records may remain after a permanently blocked underage attempt, for what purpose and for how long? This concerns stored data, not the approved adult-eligibility rule.

13. **Q13 [D05] Active and inactive account data.** Which profile, preference, questionnaire and ranking/exposure fields are needed, for what purposes and how long? Should account inactivity trigger cleanup, and after what interval?

14. **Q14 [D05] Account deletion.** Should ordinary relationship cleanup happen after either account deletes, as proposed? What becomes inaccessible immediately, what is the live-erasure deadline, and which minimal safety/restriction records may remain for a stated purpose?

15. **Q15 [D05] Confessions after deletion.** When an author deletes their account, should their public confessions and references disappear, or may some remain anonymously published? If they remain, how are later deletion rights and moderation attribution handled?

16. **Q16 [D05] Active-chat retention.** How long may messages in an active approved chat remain? Should maximum age or inactivity trigger cleanup, and what starts that clock?

17. **Q17 [D05] Closed-chat access.** After a genuinely closed chat, who may read the old history and for how long before deletion? What event starts the clock? For example, Alice explicitly ends chat with Brian; unmatching alone still preserves approved chat.

18. **Q18 [D05] Report evidence.** Which limited evidence and case-note categories may be retained, for how long during and after a case, and what counts as final resolution when an appeal is possible?

19. **Q19 [D05] Evidence access and exports.** Who may view case evidence, who assigns access, and which exceptional/admin/export access is allowed? What may the reporter or reported person see, and what access audit is required?

20. **Q20 [D05] Evidence holds.** Who may authorize a hold, which exact items may it cover, how often must it be reviewed, and when must overdue data be purged after release?

21. **Q21 [D05] Consent proof.** Which consent/notice-proof fields may remain, for what purpose and how long after account deletion? Include policy version, choice/change times and any minimized account reference.

22. **Q22 [D05/D11] Verification and mail lifecycle.** How long should a verification challenge remain valid, what delivery/retry expiry applies, and which code-free verification/delivery metadata may remain for how long? Secret-bearing material must follow the existing terminal-erasure safeguards.

23. **Q23 [D05/D10] Other metadata retention.** Set allowed fields and retention periods for security/moderator audits, application/telemetry logs, rate-limit/security counters, support/rights-request records and generated exports.

24. **Q24 [D05] Physical cleanup.** What deletion deadlines apply to media, thumbnails, old/soft-deleted versions, caches and cleared/expired request records? Who verifies actual removal and handles overdue cleanup?

25. **Q25 [D05] Retention clocks.** For the remaining retention categories, which original events start expiry, what UTC/calendar arithmetic applies, and may reads, retries, edits or hold reviews ever reset a deadline? Already-approved request/unsend clocks are unchanged.

26. **Q26 [D05/D07] Backups and recovery records.** How long may database backups, restored servers, media versions and manual exports survive? What physical-expiry rules and safety margin determine how long the independent deletion/block/consent replay record must remain?

27. **Q27 [D05] Privacy requests.** How should access, export, correction and deletion requests work, including ownership verification after account deletion, response deadlines, redaction, export delivery/expiry and exception handling?

28. **Q28 [D12] Location source.** Should geographic matching use manually selected coarse areas, optional device location converted to a coarse area, both, or another source? Geographic matching remains in the approved full feature scope.

29. **Q29 [D12] Area catalogue and maps.** Which campus/area catalogue and representative points are approved, who maintains it, and will a map provider be used? If so, name its owner and budget.

30. **Q30 [D12] Location consent.** Is sharing location optional? What must the opt-in notice explain, what happens if permission is refused, and what does withdrawal do to existing matches/chats?

31. **Q31 [D12] Location precision and disclosure.** How coarse must stored location be, may exact device coordinates ever be stored, and what area/distance information may other users see? Include sparse-area and location-probing protections.

32. **Q32 [D12/D05] Location lifetime.** How long is location fresh, when does it expire, and how quickly must old/withdrawn location and related caches be deleted? What additional consent proof is needed under the general D05 rules?

33. **Q33 [D12] Geographic ranking.** Use a soft proximity bonus or a hard distance filter? Specify bonus cap/distance scale or radius, unknown/stale-location handling, and whether stronger distant matches may outrank weaker nearby ones.

34. **Q34 [D12] Results after a location change.** When location changes, should the current discovery list refresh/reorder immediately or remain fixed until the next search? What behaviour should users see while paging through results?

35. **Q35 [D06] Subscription and funding.** Keep the student subscription or use a funded production subscription? Name the account/billing owner, continuity funding after credits or eligibility change, monthly spending cap and budget-alert recipients.

36. **Q36 [D06] Hosting regions and data flow.** Which regions and data flows are approved for the app, database, email, media and diagnostics? Is the recorded South Africa North app plus United States ACS email proposal accepted after specialist review, or what must change?

37. **Q37 [D07] Availability and releases.** What uptime target and planned/unplanned downtime are acceptable? Which hosting/staging approach, release stop conditions and rollback approach should the operations owner approve for that target?

38. **Q38 [D07] Recovery acceptance.** What maximum data loss and recovery time are acceptable across database, media, configuration and queues? Who owns and witnesses the isolated restore rehearsal and accepts its results?

39. **Q39 [D08] Runtime versions.** Which exact PHP/MySQL versions and extensions form the production/staging/CI compatibility target, and which engineering/security reviewer approves that matrix?

40. **Q40 [D08] Sessions and scaling.** Will production use one or multiple app instances, and file-based or shared sessions? Which session-expiry, cookie and trusted-proxy settings should the engineering/security reviewer approve?

41. **Q41 [D08] Database and secret access.** Which private-network, encrypted-connection, restricted application-user, separate migration-user, runtime-schema-change and secret/deployment-access rules are approved? Name the security approver.

42. **Q42 [D09] Named operators and coverage.** Name the operations, safety-review/moderation, support, privacy, cleanup and engineering/release owners, their backups, coverage hours and escalation contacts. Who assigns/revokes reviewer roles and authorizes holds, exports and disclosures?

43. **Q43 [D10] Telemetry collection.** Which server/browser events and data fields may be collected, by which provider and in which region? Specify redaction/sampling, cookie/notice requirements and opt-out behaviour.

44. **Q44 [D10] Monitoring budget and alerts.** What monitoring spending caps, alert conditions/thresholds, recipients and escalation response are approved? Metadata retention is covered in question 23.

45. **Q45 [D11] Sender and support inbox.** Confirm the production sender domain/resources, exact From address and display name; is the recorded candidate DoNotReply@bushisa.me approved? What monitored Reply-To/support inbox and owner should be used, or should Reply-To remain unset?

46. **Q46 [D11] Delivery acceptance and failures.** Name owners and approved recipients for future inbox/header/DNS acceptance tests. Who handles bounces, terminal failures, mail scheduling, secret rotation and delivery alerts, and what should users be told on failure?

47. **Q47 [D11] Password reset.** May password reset reuse the approved sender? What token validity, single-use/invalidation and delivery/support rules must the reset flow satisfy?

48. **Q48 [D04/D05/D09/D12] Specialist and final acceptance.** Who provides the required privacy/security/safety reviews, approves final policy/consent/email wording, witnesses mobile location/permission checks and makes the final release go/no-go decision?

Implementation alignment, final notice drafts and required specialist/human
acceptance still require their own evidence and review. This questionnaire
does not clear D04/D05/D12 overall or authorize implementation, merge,
deployment, live-database access or real email.

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
