# D04/D05 Policy Proposals

Updated: 2026-10-16, Africa/Harare.

## Current owner selections after Q01-Q48

[Q01-Q48 reconciliation](owner-policy-reconciliation-2026-10-15.md) is the current
acceptance supplement to [decisions.md](decisions.md); the
[inventory/retention matrix](data-inventory-retention-matrix.md) is its compact
data contract. Takudzwa Hondova approved direct product selections in that
message only. Exact acceptance UTC/Harare time remains CONFIRM. Explicit
proposals and required qualified/operational reviews remain pending; D04/D05
are not approved overall, implemented, tested or deployed by this record.

Accepted additions include separate/after-unmatch/after-block reporting,
minimized targets and case handling, personal-block ownership, the existing
own-undo design with the separately selected rolling restoration cap, pause
visibility and unsend projections. New-match clearance applies to decline
restrictions only. The owner has now expressly selected Q08 renewed consent:
Decision 27 requires fresh agreement from both for restoration, superseding
only Decision 20's no-agreement clause. Own-unmatch control remains. Temporary
unset Reply-To is also approved until a monitored inbox is verified; see the
[amendment record](owner-conflict-decisions-2026-10-16.md).

Current selected lifecycle values include pending-signup expiry 72h/7d;
underage content purge 24h; account/affected ordinary content erase 7d/media
24h; explicit closed history read-only 7d then purge within 24h; case evidence
180d/accidental-case evidence 30d and structured decisions 365d after final
resolution; access audit 365d; scoped hold 24h urgent/30d ordinary with 14d
review; privacy-reviewed consent proof 365d after the applicable terminal event;
exports 24h; location freshness 7d with 24h purge. Immutable clocks, original
text deletion and earlier independent contact restrictions are preserved.

**Still proposed:** post-expiry probation renewal; numeric sanction/report
service defaults; underage anti-replay 365d; candidate account fields; linked
active-chat 365d/90d schedule; Q23 metadata table; DB/media/export copy limits
35d/7d/24h. Values selected separately in other questions retain that selected
status. Recovery protection is computed from actual copy expiry plus 7d; the
conditional example is 7+35+7=49d, not a universal production setting.

The following mapping and earlier proposals are retained for traceability.
Older 30d account cleanup, 90d closed-chat and 24-month case defaults describe
earlier proposals/current code drift, not the newly selected policy. See L01-L07
in the backlog for implementation/evidence, named approvers and release gates.

Status: **pending proposal with partial D04 approvals recorded separately**.
This file records proposed D04/D05 rules and the current repository mapping. It
does not approve overall production policy, P0 clearance, live deployment,
user-facing wording, or any retention number. Accepted values must be recorded
in `docs/remediation/decisions.md` by the actual owner/reviewer before
production use.

## 1. R01-R12 Implementation Map

The [L01a report-route/target contract](l01a-report-route-target-contract.md)
now provides the source-level report/case/role map and later negative-case plan
for R02/R03/R04/R11. It separates approved requirements from proposed engineering
details and identifies missing target resolvers, duplicate/case linkage, private
evidence/expiry and admin assignment/exception controls. Documentation is complete;
implementation, runtime evidence and qualified/named acceptance are not. L01a originally changed no approval status. The later explicit owner amendment
now resolves renewed consent and temporary unset Reply-To; other gates stay open.

| Rule | Current mapping | Working behavior | Reproduced gap or pending evidence |
|---|---|---|---|
| R01 eligible reciprocal likes and explicit chat approval | `api/swipe.php`, `php/matching/service.php`, `api/message_requests.php`, `api/messages.php`, `php/policy_safeguards.php`, `matches`, `message_requests` | Swipe path checks reciprocal matching inputs before creating a match. Current message guard requires active match plus approved request. | Prompt 02 records reciprocal-swipe and approve-vs-revoke multi-worker evidence for its exact candidate, not the new policy. Align continued approved chat after unmatch and the recorded unblock exceptions; new semantics need their own later evidence. |
| R02 request states and rematch/unblock | `message_requests.status`, `expires_at`, `closed_at`, `purge_due_at`; `api/message_requests.php`; `blocks`; `matches.match_status` | States include `pending`, `approved`, `declined`, `cancelled`, `expired`, `revoked`; unblock removes only the blocker row. | D04 Decisions 1-5 approve seven-day pending expiry, 12-hour cancellation/expiry same-direction retry, zero further attempts after same-direction decline, request budgets, fixed reported-sender probation, reverse-direction requests after decline and recreated-match clearance of prior decline restrictions. Implementation still needs alignment from the earlier provisional 24-hour/30-day/20-per-hour model. D04 Decision 6 approves restart only by the person who ended an approved chat, through a fresh request requiring the other participant's approval; implementation and regression evidence remain pending. D04 Decision 7 approves a 12-hour UTC cooldown from ending/revoking that chat before the fresh restart request. D04 Decision 8 approves that a second approved-chat end terminates re-request privileges for the rest of that match's lifetime. D04 Decision 9 approves carryover of the second-ending cutoff across rematches between the same two users; a new match does not restore chat-request privileges after that cutoff. Decision 5's decline-only clearance does not clear it. D04 Decision 10 approves immediate, automatic pair chat privileges on blocker unblock in the reviewed scenario without the second-ending cutoff, without a fresh mutual match or new request approval. D04 Decision 11 confirms that immediate chat on blocker unblock includes never-approved chat; prior chat approval is not required. D04 Decision 12 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it or clear a cutoff from separate chat endings. D04 Decision 13 approves continued approved chat after unmatch, without treating unmatch as a chat ending or restart. D04 Decision 14 approves continued existing approved chat during discovery/profile pause. D04 Decision 18 approves that discovery pause does not affect matching, requesting, approving or chatting; normal independent contact restrictions still apply. D04 Decision 19 preserves pending requests on unmatch but denies approval until the matched state returns, provided the same request is still pending; original expiry and restrictions are unchanged. Decision 27 supersedes Decision 20's no-agreement clause: restoration now requires fresh agreement from both, while own-unmatch authority remains; restoration alone still does not approve the pending chat request. D04 Decision 21 limits restoration to the person who unmatched in the reviewed case; the other participant cannot restore it. D04 Decision 22 requires each participant to undo their own unmatch before the match can return when both unmatched; one person's undo is insufficient. D04 Decision 23 selects combined block/report, clearing pending pair requests and initially withholding unblock; unblocking becomes possible after safety review. D04 Decision 24 requires a safety reviewer's explicit approval, permits app bans for policy or ethical violations and allows the pair to remain blocked without a ban. D04 Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone leaves the block in place. D04 Decision 26 approves immediate chat after the explicitly approved, chosen unblock, without a fresh request or further chat approval; cleared pending requests and independent restrictions are preserved. Substantive criteria, role assignments, ban duration/appeals and review timing remain pending. Remaining mechanics/timing/caps, reporting outside the combined flow, other unmatch/block/unblock details, discovery presentation and unrelated pause details remain pending. Current code, including its active-match authorization guard and pause handling, requires alignment verification. |
| R03 cascades must not remove protected evidence | Legacy FKs in `database/bushisa.sql`: `messages.match_id ON DELETE CASCADE`, `reports.reported_user_id ON DELETE CASCADE`; `php/block_handler.php`; `api/safety.php` | Block now closes match rows instead of deleting them; message rows survive. Scoped snapshots live in `moderation_evidence`, which has no FK cascade to users/messages. | Existing legacy FK remains dangerous if future code hard-deletes matches/users. Tests cover the new shared path, not every ad hoc SQL path. |
| R04 unsend evidence model | `api/messages.php`, `messages.unsent_at`, `moderation_body`, `ordinary_body_purged_at`, `moderation_evidence` | Unsend is idempotent, sender-only, and bounded to 15 minutes by server UTC time; ordinary body is purged instead of blanket shadow-retained. | Decision 15 approves deleting unsent original text unless already preserved as evidence for an existing safety report, and rejects blanket retention. Decision 16 approves a 15-minute unsend window from server-recorded sending time, with denial after that window. Decision 17 approves removing the entry entirely from both ordinary chat views, without a placeholder. Clock-source/enforcement and ordinary chat-view alignment verification, other attachment/cache projections and wording remain pending. Evidence access, retention deadlines and backup deletion remain pending under D05. |
| R05 verifier and mail payload storage | `php/signup_verification.php`, `php/mail_delivery.php`, `mail_outbox`, `student_email_approval_codes`, `bin/cleanup_policy.php` | Terminal verifier/body erasure and encrypted SMTP outbox/CLI dispatcher exist; fake mail is development-only. | The 11 October continuation records operator SMTP/inbox success. Current headers, receiving contact, useful dispatch deadlines, purpose-separated reset flow and remaining D11 acceptance still need evidence; no fresh live verification here. |
| R06 either-account relationship cleanup | `php/anonymiser.php`, `php/policy_safeguards.php`, `swipes`, `matches`, `message_requests`, `cleanup_outbox`, `protective_replay_events` | Account deletion closes either-account matches with `deleted_cleanup`, revokes requests, deletes ordinary swipes, and queues cleanup/replay. | Final production retention purpose/deadline remains D05 pending. |
| R07 confession deletion before author unlink | `api/confessions.php`, `confessions.deleted_at`, `purge_due_at`, `confession_references`, `php/anonymiser.php` | Account deletion now records owned confession deletion, erases ordinary public body, marks references unavailable, then existing author unlinking can occur. | Continued anonymous-publication alternative is not implemented or approved. |
| R08 personal-data inventory | `users`, `profiles`, `preferences`, `find_match_answers`, `profile_images`, `mail_outbox`, `audit_log`, `logs/`, future D12 fields | Deletion now clears credentials, DOB, preferences, questionnaire answers, profile-image metadata and owned public confession bodies through the shared deletion path. | Application logs, upload derivatives, caches, Blob versions and future location data need environment inventory before P0 clearance. |
| R09 consent/deletion proof minimization | `consent_records`, `protective_replay_events`, `cleanup_outbox` | Replay ledger is independent of ordinary relationship rows and records minimized protective events. | Consent retention period and ledger expiry are pending; no six-year or 36-month value is approved. |
| R10 UTC purge deadlines without reset | `purge_due_at`, `evidence_purge_due_at`, `closed_at`, `terminal_at`; `bin/cleanup_policy.php` | New rows record UTC terminal/closure/deadline fields and do not reset on read. Cleanup is idempotent for signup/outbox entries. | Full retention worker for messages/evidence/media is designed but not complete. |
| R11 case-scoped evidence access | `moderation_cases`, `moderation_case_assignments`, `moderation_evidence`, `evidence_access_audit`, `bushisa_case_evidence_for_viewer()` | Ordinary users and unassigned moderators cannot read evidence; assigned moderators or audited admin exceptions can. Access is audited without copying private bodies into audit rows. | No user-facing moderation UI exists yet for case assignment or evidence viewing. |
| R12 protective restore replay | `protective_replay_events`, `restore_replay_runs`, `tests/policy_safeguards_p0_flows.php` | Synthetic restore check fails closed when replay evidence is unavailable/incomplete. Ledger covers account deletion, match closure, confession deletion and case opening. | Actual Azure restore rehearsal and isolated restored DB/media replay are not run. |

## 2. Proposed D04 Rules

These rules remain partial until D04 is accepted in the decision register.

- A like creates only a swipe. The current matching service requires eligible reciprocal likes to create a match. Decision 27 now requires fresh mutual agreement for restoration while Decisions 21-22 retain own-unmatch control; the former Decision 20 no-agreement clause is superseded and implementation requires alignment.
- A match alone does not authorize chat. The chat-request flow requires
  recipient approval. Decisions 10-11 separately approve immediate chat
  privileges on blocker unblock, including when chat was never approved and
  without requiring prior chat approval.
- Message request states are `pending`, `approved`, `declined`, `cancelled`, `expired` and `revoked`.
- Decision-backed request timing/contact rules are seven days for pending
  request expiry, 12 hours after cancellation or expiry before same-direction
  retry, and zero further same-direction attempts after the recipient declines.
  No cooldown automatically restores same-direction permission after decline.
- The decline restriction applies only to the original requester-recipient
  direction. D04 Decision 5 separately approves that the previous recipient may
  later send a reverse-direction request to the original requester, and that a
  recreated match clears prior decline restrictions.
- Recreated matches clear decline restrictions only; they do not reset lifetime
  pair budgets or override blocking, authorization or account restrictions.
- Decision-backed request budgets are: sender maximum 3 requests across all
  matches per 12 hours; recipient maximum 10 incoming requests across all
  requesters per 12 hours; requester-recipient pair maximum 3 requests over the
  pair lifetime; reported sender reduced allowance of 3 requests per 24 hours
  rather than a sending ban.
- Reported-sender probation lasts a fixed 7 days, meaning 168 hours from
  probation activation measured in UTC. No review or support appeal may restore
  the normal allowance early. After probation expires, the normal sender
  allowance of 3 requests per 12 hours returns, subject to other applicable
  account restrictions.
- Restoring the sending rate does not reset lifetime pair budgets or override
  declined-request, blocking or authorization rules.
- The sender may contact support and submit a written appeal explaining a
  mistaken report, but support handling must respect the no-early-restoration
  rule.
- Another report during the probation week leaves the active 168-hour probation
  period unchanged; it does not restart or extend that period.
- D04 Decision 6 approves that only the person who ended an approved chat may
  send a fresh restart request. The other participant must approve it before
  chat access resumes.
- D04 Decision 7 approves a 12-hour cooldown from ending/revoking the approved
  chat, measured in UTC, before the fresh restart request may be sent.
- D04 Decision 8 approves that a second approved-chat end terminates re-request
  privileges for the rest of that match's lifetime. Waiting or remaining
  request budget does not restore those privileges within that match.
- D04 Decision 9 approves carryover of the second-ending cutoff across
  rematches between the same two users. Unmatching and matching again must not
  restore chat-request privileges after that cutoff. Decision 5's decline-only
  clearance does not clear it. No reset or exception to approved request budgets
  or contact restrictions is approved.
- Send, read, receipt and unsend routes must recheck current account
  eligibility, block state and approved-chat authorization. Decision 13 requires
  continuing an already-approved chat after unmatch without an active-match
  prerequisite; the current guard requires implementation alignment.
- Proposed block behavior suppresses discovery/contact both ways, closes active
  matches, revokes requests and removes ordinary swipes.
- D04 Decision 23 selects a combined `Block and report` option for reporting
  with blocking. It clears pending chat requests between the pair and initially
  provides no unblock privilege. The clarified choice allows unblocking after
  safety review; Decision 24 below requires the safety reviewer's explicit
  approval. Substantive criteria, role assignments, review timing and later
  unblock/chat effects remain pending. Clearing requests selects no
  terminal-state name or physical
  record-deletion method. Ordinary block/unblock and unmatch approvals remain
  unchanged, as do fixed sender probation and request budgets. Reporting
  outside the combined flow, including unmatch-only reporting, remains
  unresolved. D05 evidence access, retention deadlines, physical request-record
  deletion and backup deletion remain pending. Alignment and regression
  evidence remain pending.
- D04 Decision 24 requires a safety reviewer's explicit approval for unblocking
  after `Block and report`. Finishing review alone is insufficient. Safety
  reviewers may ban users from the app for policy or ethical violations, but
  may also leave the pair blocked without a ban; denying unblock does not
  automatically require a ban. Substantive approval/ban criteria, definitions,
  named role assignments, ban duration/appeals and review timing remain
  pending. Decisions 25-26 below settle the blocker's choice to unblock and
  immediate chat after that action. Decision 23's cleared pending
  requests, ordinary block/unmatch approvals, fixed sender probation and
  request budgets remain unchanged. D05 evidence access, retention deadlines,
  physical request-record deletion and backup deletion remain pending.
  Alignment and focused regression evidence remain pending.
- D04 Decision 25 approves that, after the safety reviewer's explicit approval,
  the blocker chooses whether to press `Unblock`. Approval restores that option
  but leaves the block in place until the blocker chooses to remove their own
  block. Other active blocks and account restrictions remain applicable.
  Decision 26 below settles immediate chat after that action. Ordinary
  block/unblock approvals and all earlier budgets, cooldowns, decline rules,
  the second-ending cutoff and fixed sender probation remain unchanged.
  Alignment and focused regression evidence remain pending.
- D04 Decision 26 approves immediate chat availability once the safety
  reviewer explicitly approves unblocking and the blocker chooses `Unblock` in the
  combined block/report flow. No fresh request or further chat approval is
  required. Cleared pending requests remain cleared; budgets are not reset.
  Other active blocks/account restrictions and the second-ending cutoff remain
  applicable. Ordinary block/unblock approvals and fixed sender probation are
  unchanged. Alignment and focused regression evidence remain pending.
- D04 Decision 10 approves that, for ordinary blocking, the blocker's unblock
  automatically gives the pair chat privileges immediately in the reviewed scenario without the
  second-ending cutoff, without a fresh mutual match or new request approval.
  Decision 11 confirms this includes never-approved chat; prior chat approval
  is not required. Decision 12 excludes blocking an approved chat from the
  two-ending cutoff; repeated block/unblock cycles do not trigger it or reset
  separate chat endings. This does not approve overriding the cutoff, another
  participant's active block or account restrictions, or resetting budgets.
  Earlier no-restoration-on-unblock proposals are superseded for this scenario;
  current code only removes the block row and still requires alignment.
- D04 Decision 13 approves continued approved chat after unmatch. Unmatching
  alone must not close/revoke that chat, require fresh matching/request
  approval or a restart cooldown, or add an approved-chat ending. The earlier
  close/revoke-on-unmatch proposal is superseded for approved chat. Other
  unmatch details and the user-facing implementation remain pending.
- D04 Decision 19 approves that unmatching alone does not cancel a pending
  chat request. The recipient may approve that same request only after the pair
  returns to a matched state, if it is still pending and normal independent
  restrictions allow approval. Decision 1's original seven-day UTC expiry is
  unchanged; unmatch/restoration does not pause/reset it or revive an expired
  request. Existing budgets, cooldowns, decline restrictions, block/account
  restrictions and the second-ending cutoff remain applicable. Decision 13's
  continued already-approved chat remains preserved. Decision 20 below settles
  restoration by the person who unmatched; Decision 22 below settles both
  participants' own undo requirement when both unmatched. Remaining
  mechanics/timing/caps remain pending. Alignment verification and
  regression evidence remain pending.
- D04 Decision 20 originally allowed restoration without renewed agreement.
  Decision 27 expressly supersedes that clause: restoring the existing
  relationship requires fresh affirmative agreement from both participants,
  while each retains control of their own unmatch. In Decision 19's
  pending-request scenario, the recipient must still approve the same
  still-pending request before chatting begins; restoration alone does not
  approve it. Original expiry and normal independent eligibility, block/account
  restrictions, budgets, cooldowns, decline rules and the second-ending cutoff
  remain applicable. No new reset or restriction clearance is selected.
  Decision 21 below resolves restoration by the other participant, and
  Decision 22 below requires both own undo actions when both unmatched.
  Remaining mechanics/timing/caps and matching-side cleanup remain pending;
  alignment and regression evidence remain pending.
- D04 Decision 21 approves that only the person who unmatched may restore in
  the reviewed case. If Alice unmatched Brian, only Alice may undo her unmatch,
  but restoring the pair now also requires Brian's fresh agreement under
  Decision 27. Brian cannot undo Alice's unmatch.
  Decision 19's recipient approval and unchanged expiry for the preserved pending
  request remain applicable, as do all existing contact restrictions.
  Decision 22 below settles behavior when both independently unmatch. Actor
  authorization and denial for the participant who did not unmatch require
  alignment and focused regression evidence; no new cooldown or cap is selected.
- D04 Decision 22 approves that, when both participants unmatch, each must undo
  their own unmatch before the match can return. One person's undo leaves the
  pair unmatched while the other person's unmatch remains; neither may undo the
  other's unmatch. Decision 19's pending-request approval requirement, original
  expiry and all independent restrictions remain unchanged. Decision 13 still
  preserves already-approved chat while unmatched. Internal representation and
  any unspecified restoration cooldown/cap remain pending. Implementation
  alignment and focused regression evidence remain pending.
- D04 Decision 14 approves keeping existing approved chats available to both
  participants during discovery/profile pause. Pause alone does not revoke
  chat, add an approved-chat ending or require a new request/cooldown to
  continue it. Decision 18 below also settles matching, requesting and
  approving during pause; active blocks, account restrictions and separate
  chat endings still apply.
- D04 Decision 18 approves that discovery pause must not affect matching,
  requesting, approving or chatting. Eligible matching, sending/receiving chat
  requests, recipient approval and starting/continuing approved chats remain
  available while paused. Normal independent eligibility, block/account
  restrictions, budgets, cooldowns, decline rules and the second-ending cutoff
  still apply; pause does not automatically approve requests or reset/bypass
  those rules. Exact discovery presentation and unrelated pause details remain
  unselected. Alignment verification and focused regression evidence remain
  pending.
- D04 Decision 15 approves deleting unsent original text unless already
  preserved as evidence for an existing safety report. Blanket retention of
  every unsent original for moderation is rejected; no evidence capture is
  approved merely because of unsend. Evidence access, retention deadlines and
  backup deletion remain pending under D05.
- D04 Decision 16 approves a 15-minute unsend window measured from the message's
  server-recorded sending time. After that window, unsending is not allowed.
  This is an action window, not an evidence-retention or backup-deletion period.
  Server-side clock mapping and post-window denial require verification.
- D04 Decision 17 approves removing an unsent message entry entirely from both
  participants' ordinary chat views, without a `Message unsent` placeholder.
  This display choice preserves Decisions 15-16; other projection details,
  attachment/cache handling and recall of delivered notifications are not
  selected. Implementation alignment and regression evidence remain pending.
- Notifications must not include private message bodies or evidence payloads.

## 3. Proposed D05 Rules

These rules remain proposed until D05 is accepted in the decision register.

- Account deletion immediately revokes sessions by changing account/session state.
- Ordinary relationship cleanup is triggered when either account deletes, not when both accounts delete.
- Deleted-account swipes, preferences, questionnaire answers, DOB and direct identifiers are removed or overwritten in ordinary storage.
- Owned public confessions are resolved before author unlinking: ordinary public bodies are erased and references are marked unavailable. Scoped case evidence is separate.
- Expired or consumed verifier material must not retain raw or derived code-bearing secrets. Code-bearing fake mail is synthetic-development only and disabled in production.
- `purge_due_at` and related UTC deadlines are derived from the original terminal event and must not reset on read, retry or edit.
- Evidence is case-scoped and accessed only through assigned/audited paths. Audit records must not contain message bodies, passwords, raw DOB, approval codes or location payloads.
- Restore must replay an independent protective ledger for deleted accounts/content, unsend, blocks, revoked consent and expired data before any traffic or worker resumes. Missing or incomplete replay evidence blocks reopening.

## 4. Current Technical Safeguards

- `php/policy_safeguards.php` centralizes active chat authorization, match closure, moderation evidence snapshots, account-deletion cleanup/replay and terminal signup material erasure.
- `php/block_handler.php` and `api/safety.php` close matches instead of deleting them, preventing the legacy `messages.match_id ON DELETE CASCADE` from firing in the normal block path.
- `api/messages.php` uses the shared authorization guard and implements sender-only 15-minute UTC unsend without blanket original-body retention.
- `php/anonymiser.php` calls the shared deletion-preparation path before soft-deleting account fields.
- `bin/cleanup_policy.php` performs idempotent synthetic signup/outbox cleanup.
- `database/migrations/003_restore_replay_safeguards.sql` records restore replay rehearsal decisions separately from restored application data.

## 5. Current Limitations

- D04/D05 are still pending overall. D04 Decisions 1-26 approve only the
  specific request handling, budgets, probation, decline/rematch behavior and
  restart actor/fresh approval, 12-hour UTC restart cooldown and second-end
  match-lifetime cutoff with carryover across rematches, and immediate chat on
  blocker unblock including never-approved chat, with blocking excluded from
  the two-ending cutoff, and continued approved chat after unmatch and during
  discovery pause, plus original-text deletion on unsend except already-
  preserved evidence for an existing safety report, recorded in `decisions.md`.
  Blanket unsent-original retention is rejected. Decision 16 approves 15 minutes
  from server-recorded sending time for unsend, with denial after that window.
  Decision 17 approves removing the entry entirely from both ordinary chat
  views without a placeholder. Decision 18 approves that discovery pause does
  not affect matching, requesting, approving or chatting; normal independent
  restrictions still apply. Decision 19 preserves pending requests on unmatch,
  with approval denied until matched again and original expiry unchanged.
  Decision 27 supersedes Decision 20's no-agreement clause: restoration needs
  fresh agreement from both, with own-unmatch control preserved; it does not approve a pending
  chat request. Decision 21 limits restoration to the person who unmatched in
  the reviewed case; the other participant cannot restore it. Decision 22
  requires both participants to undo their own unmatch before the match can
  return when both unmatched. Decision 23 selects combined block/report,
  clearing pending pair requests and initially withholding unblock; unblocking
  becomes possible after safety review. Decision 24 requires the safety
  reviewer's explicit approval and permits an app ban for policy or ethical violations,
  while also allowing the pair to remain blocked without a ban. Decision 25
  leaves actual unblocking to the blocker's choice after explicit approval;
  approval alone keeps the block in place. Decision 26 approves immediate chat
  after that chosen unblock without a fresh request or further chat approval,
  preserving cleared requests and independent restrictions. Substantive
  criteria, role assignments, ban duration/appeals, review timing, reporting
  outside that flow and remaining restoration details,
  discovery presentation, other projections and wording remain pending.
  Evidence access, retention deadlines and backup deletion remain
  pending D05.
- `api/message_requests.php` still permits the earlier provisional declined
  re-request path after 30 days and still uses the earlier 24-hour
  cancellation/expiry cooldown. It also still reflects the older 20-per-hour
  technical request default rather than D04 Decision 4. It must be aligned to
  D04 Decisions 2-14 and 18-26 before production use.
- Decisions 15-17 require original-text, server-clock/window and ordinary
  chat-entry removal alignment verification with focused regression evidence
  across applicable projections.
  They do not approve evidence access, retention deadlines or backup deletion
  under D05.
- Encrypted SMTP outbox/dispatch exists in source; the 11 October continuation
  records operator-reported sender/SMTP/inbox success. This task did not
  independently reverify live delivery. Remaining H06/D11 acceptance and the
  purpose-separated password-reset flow are open.
- Full media/blob/cache/log cleanup is inventoried but not complete.
- Prompt 02 records three Linux four-worker overlap cases for candidate
  `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, with push run `38546846225` and
  PR run `38546851035` retained in the owner packet. New policy semantics need
  new regression evidence later. Windows single-worker output is not HTTP
  concurrency evidence.
- D12 production location use remains disabled pending approval; synthetic D12 fixtures remain labelled provisional.

## 6. Pending Owner Choices

The owner requested all remaining questions together on 2026-10-15. Pending
questionnaire Q01-Q48 is listed in full in the Prompt 03 owner packet's Prioritized
Owner Questions section, covering D04-D12 and the linked proposals. It records
no additional approvals or production action.

### 6.1 Required Section 6.1 Choices

| Choice | Pending decision |
|---|---|
| Chat transitions | Request expiry, cancellation/expiry retry, same-direction declined retry, request budgets, fixed reported-sender probation, reverse-direction requests after decline and recreated-match clearance of prior decline restrictions are partially approved by D04 Decisions 1-5. D04 Decision 6 approves restart only by the person who ended an approved chat, with a fresh request and the other participant's approval. D04 Decision 7 approves a 12-hour UTC cooldown from ending/revoking that chat before the fresh restart request. D04 Decision 8 approves that a second approved-chat end terminates re-request privileges for the rest of that match's lifetime. D04 Decision 9 approves carryover of the second-ending cutoff across rematches between the same two users; a new match does not restore chat-request privileges after that cutoff. Decision 5's decline-only clearance does not clear it. D04 Decision 10 approves immediate, automatic pair chat privileges on blocker unblock in the reviewed scenario without the second-ending cutoff, without a fresh mutual match or new request approval. D04 Decision 11 confirms that immediate chat on blocker unblock includes never-approved chat; prior chat approval is not required. D04 Decision 12 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it or clear a cutoff from separate chat endings. D04 Decision 13 approves continued approved chat after unmatch, without treating unmatch as a chat ending or restart. D04 Decision 14 approves continued existing approved chat during discovery/profile pause. D04 Decision 18 approves that discovery pause does not affect matching, requesting, approving or chatting; normal independent contact restrictions still apply. D04 Decision 19 preserves pending requests on unmatch but denies approval until the matched state returns, provided the same request is still pending; original expiry and restrictions are unchanged. Decision 27 supersedes Decision 20's no-agreement clause: restoration now requires fresh agreement from both, while own-unmatch authority remains; restoration alone still does not approve the pending chat request. D04 Decision 21 limits restoration to the person who unmatched in the reviewed case; the other participant cannot restore it. D04 Decision 22 requires each participant to undo their own unmatch before the match can return when both unmatched; one person's undo is insufficient. D04 Decision 23 selects combined block/report, clearing pending pair requests and initially withholding unblock; unblocking becomes possible after safety review. D04 Decision 24 requires a safety reviewer's explicit approval, permits app bans for policy or ethical violations and allows the pair to remain blocked without a ban. D04 Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone leaves the block in place. D04 Decision 26 approves immediate chat after the explicitly approved, chosen unblock, without a fresh request or further chat approval; cleared pending requests and independent restrictions are preserved. Substantive criteria, role assignments, ban duration/appeals and review timing remain pending. Remaining mechanics/timing/caps, reporting outside the combined flow, other unmatch/block/unblock details, discovery presentation and unrelated pause details remain pending. Current code, including its active-match authorization guard and pause handling, requires alignment verification. |
| Unsent originals | Decision 15 selects original-text deletion unless already preserved for an existing safety report; blanket retention is rejected. Evidence access, retention deadlines and backup deletion remain pending under D05. |
| Unsend action window | Decision 16 approves 15 minutes from the message's server-recorded sending time, with unsend denied after the window. Clock-source mapping, enforcement and focused boundary evidence remain pending. |
| Unsend display | Decision 17 approves that the entry disappears entirely from both participants' ordinary chat views, without a `Message unsent` placeholder. The current placeholder requires implementation alignment and regression evidence; other projections and reviewed notices remain pending. |
| Block/report review | Decision 24 requires explicit safety-reviewer approval for unblocking and allows app bans for policy or ethical violations; the pair may remain blocked without a ban. Decision 25 leaves unblocking to the blocker's choice after explicit approval. Decision 26 approves immediate chat after the explicitly approved, chosen unblock without a fresh request/approval. Substantive criteria, role assignments and ban duration/appeals remain pending. |
| Closed chat/report evidence | Approve purpose, access model, final-resolution definition, deadlines and deletion capability. |
| Deleted confessions | Approve deletion-on-account-removal default or separately approve continued anonymous-publication model. |
| Abandoned/underage signup | Approve exact expiry and minimal safety proof. |
| Consent proof | Specify fields, purpose and post-deletion period. No automatic six-year rule is approved. |
| Restore protections | Approve Azure/Blob/copy horizons, safety margin, owner and ledger continuity mechanism. |
| Operations and rights | Name case, cleanup, support, privacy and escalation owners. |

## 7. Verification Targets

Focused regressions now cover:

- case-scoped evidence access and audit;
- block close/revoke without message cascade;
- either-account deletion cleanup;
- deleted-confession erasure before author unlink;
- terminal verifier and fake-mail payload erasure;
- synthetic restore fail-closed behavior when replay evidence is missing.

Required but not yet complete:

- new policy-specific multi-worker HTTP overlap, beyond the three previously
  recorded candidate cases, with independent sessions/connections and actual
  application-side overlap evidence;
- Azure MySQL/version compatibility;
- real sender/inbox verification;
- complete media/blob/cache/log purge proof;
- isolated Azure restore rehearsal.
