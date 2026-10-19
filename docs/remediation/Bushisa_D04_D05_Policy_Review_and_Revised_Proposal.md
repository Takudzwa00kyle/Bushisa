# Bushisa D04/D05 policy review and revised proposal

## Current owner-review supplement: 2026-10-16

This historical technical review is now supplemented by the actual Q01-Q48
owner response in [owner-policy-reconciliation-2026-10-15.md](owner-policy-reconciliation-2026-10-15.md)
and [decisions.md](decisions.md). Reviewer: Takudzwa Hondova. Exact acceptance
UTC and Africa/Harare date/time remain CONFIRM; drafting dates are not approval
times. Direct selections are product approvals only; `propose`, `candidate`
and `recommend` values are not adopted. Overall D04/D05, H03, implementation,
evidence, staffing and release acceptance remain open.

Use the [current category matrix](data-inventory-retention-matrix.md) for access,
live erase, immutable trigger, evidence exception, recovery and responsible
role. Earlier 30-day account, 90-day closed-chat, 24-month case, six-year or
indefinite consent suggestions below are historical; they are not accepted
current durations. Explicitly closed history now has a selected 7-day read-only
period and <=24h subsequent live purge; selected case-content/decision clocks
are 180/365 days after final resolution, with 30 days for unrelated/accidental
case content. Q16's linked active-chat schedule and Q23/Q26 proposed tables
remain unadopted. Q12 underage HMAC retention and long-lived ban suppression
require the stated privacy reviews.

The owner expressly replaced the no-renewed-agreement rule: Decision 27 now
requires fresh agreement from both before match restoration, with own-unmatch
control preserved. Temporary unset Reply-To is also approved until a monitored
inbox is verified. See the [amendment record](owner-conflict-decisions-2026-10-16.md).
Preserve immediate chat after qualifying blocker-initiated
unblock, the existing fixed probation and original-text deletion exception.
The single conflict/unknown table in the supplement records these boundaries;
no blanket rematch exception or scope reduction is introduced. Older code/task
directions below must be adapted to those current decisions before execution.

The [configuration checklist](configuration-impact-checklist-2026-10-15.md)
is read-only planning. L01-L07 in the backlog are later bounded tasks, not
authorization to run the historical implementation prompt during this task.

Prepared: **10 October 2026**, Africa/Harare.  
Input: the operator-supplied D04/D05 proposal dated 10 October 2026.  
Baseline: `Bushisa_P0_to_P2_Azure_Remediation_Playbook.md`, version 3, particularly P0-04, P0-05, P0-06, P0-07 and H03/H06/H07/H08.  
Status: **technical review complete; revised product and retention policy remains proposed.** This document does not approve D04/D05, verify repository implementation, or clear P0.

## 1. Review outcome

The original proposal supplies useful messaging consent, immediate block/unmatch restrictions, private reporting and restore protections. It needs the changes below before becoming a precise implementation contract. Retention numbers inherited from that proposal remain suggestions requiring a recorded purpose, responsible owner, approval and tested expiry.

The published Act text hosted by POTRAZ requires purpose-limited, proportionate processing and identifiable retention no longer than necessary [S1]. This supports requiring reasons for each retained category; it does not establish that Bushisa must retain every consent record for six years or every closed case for 24 months. The qualified privacy reviewer must check the current consolidated law, applicable regulations and actual processing. This review supplies engineering recommendations, not a legal determination.

| ID | Gap in original proposal | Required clarification or revision |
|---|---|---|
| R01 | A like could be read as creating a match; messaging checks mention active accounts without full eligibility. | Require eligible reciprocal likes. Recheck current student approval, adult eligibility, account state, pair restrictions and explicit chat approval on protected actions. |
| R02 | Unblock, cancellation, expired requests, report restrictions and rematching are undefined. | Specify states, who can change them and whether a new request is permitted. Decision 10 supersedes the earlier no-restoration-on-unblock proposal for the reviewed scenario. Decision 11 includes never-approved chat without requiring prior approval. Decision 12 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it. Rematch cannot clear Decision 9's second-ending cutoff. |
| R03 | Deleting match rows may cascade-delete messages and evidence. | Preserve authorized evidence before removing relationships. Test foreign keys, retries, mid-transaction failure and genuine HTTP races. |
| R04 | Unsend promises removal while retaining every original for 90 days. | Decision 15 selects deleting original text unless already preserved for an existing safety report and rejects blanket retention. Evidence access, retention deadlines and backup deletion remain pending under D05; Decision 16 approves 15 minutes from server-recorded sending time with denial after that window; Decision 17 approves entry removal from both ordinary chat views without a placeholder. Other projections, reviewed wording and implementation verification remain pending. |
| R05 | Approval codes remain for 30 days; mail says only that plaintext is removed. | Destroy verifier/code-bearing material when terminal. Encrypt queued production payloads, erase them after send/expiry, and retain only minimal code-free metadata if justified. |
| R06 | Swipes/matches wait until both accounts delete. | Purge the deleted account's ordinary relationship data after either account deletes. Keep only separately justified restriction metadata or case evidence. |
| R07 | Confessions survive deletion while author links are cleared. | Resolve the conflict with later owner deletion and moderator attribution. Recommended default: account deletion removes the user's public confession content/references; preserve only scoped case evidence. |
| R08 | Direct identifiers are called anonymised but other personal fields are omitted. | Inventory passwords/tokens, DOB, demographics, questionnaire answers, caches, ranking/exposure records and future location data. Random placeholders do not make retained content anonymous. |
| R09 | Six-year consent and 36-month deletion-ledger periods lack a stated purpose. | Minimize proof records; require an approved expiry. Keep restore protections while any relevant recoverable copy exists, then purge them unless another documented purpose applies. |
| R10 | Retention clocks and hold release are ambiguous. | Define immutable start events, UTC deadlines, final resolution, review dates and purge completion. Repeated reads, retries, edits and hold reviews cannot reset expiry. |
| R11 | Role names alone could authorize broad evidence browsing. | Add case-scoped permission or audited exceptional access. Keep report status, content, hidden authors and exports out of ordinary endpoints. |
| R12 | Backup replay covers accounts, but other protective changes can return. | Reconcile deleted content, unsend, blocks, suspensions, revoked consent and expired retained data before any restored traffic or mail/job processing. |

## 2. Revised D04: messaging and safety

### 2.1 Eligibility and chat consent

- One like does not create an established match. New matching requires reciprocal likes and the live mutual eligibility checks defined by the approved matching policy. Decision 27 now requires fresh agreement from both to restore the existing relationship; only Decision 20's no-renewed-agreement clause is superseded, while own-unmatch control remains.
- A match alone grants no chat access. One participant requests chat; only the other participant can approve that request. Sending a reverse request must not count as approval. Decisions 10-11 separately approve immediate chat privileges on blocker unblock, including when chat was never approved and without requiring prior approval.
- The proposed one-current-request/grant invariant must accommodate Decision 13: an already-approved chat continues after unmatch. Authorization must not require an active match merely to continue that chat. Decision 19 separately preserves pending requests on unmatch but requires a matched state before the recipient may approve a still-pending request. The original seven-day expiry is unchanged. Internal request/grant/match-generation representation remains implementation work; stale or revoked grants do not gain authority by implication.
- Request approval, send, read, receipts and conversation media require server-side ownership and current authorization. Recheck active/approved/adult account eligibility and any current block, suspension, deletion, revoked grant or pair-specific restriction.
- Decision 14 approves that existing approved chats remain available to both participants during discovery/profile pause. Pause alone does not revoke those chats, add an approved-chat ending or require a new request/cooldown to continue them. Decision 18 approves that discovery pause must not affect matching, requesting, approving or chatting: eligible matching, sending/receiving chat requests, recipient approval and starting/continuing approved chats remain available while paused. Normal independent eligibility, block/account restrictions, budgets, cooldowns, decline rules and the second-ending cutoff still apply; pause does not automatically approve requests or reset/bypass those rules. Exact discovery presentation and unrelated pause details remain unselected. Alignment verification and focused regression evidence remain pending.
- CSRF/session checks apply to the actual mutation routes. Revoking one session is insufficient for account deletion or a policy requiring all-session revocation.
- Notifications may say that a request or message exists. They contain no message body, sensitive preview or private evidence. Private chat payloads must not enter analytics, Azure/Datadog logs or unrelated API responses.
- Read state is visible only to authorized conversation participants. No public activity status is introduced.

### 2.2 Request states and repeat contact

Entries explicitly marked approved refer to recorded D04 owner decisions.
All other entries remain proposed.

| Event | Allowed actor | Result and consent rule |
|---|---|---|
| Create request | Eligible participant in the current match | `pending`; rate-limited; one current request for the pair. |
| Approve | Recipient of that pending request | `approved`; both participants may chat while all authorization conditions remain true. |
| Decline | Recipient | `declined`; no chat access. |
| Cancel pending request | Requester | `cancelled`; no chat access; cancellation cannot evade request-frequency limits. |
| Pending request expiry | Server/job | Approved by D04 Decision 1 on 2026-10-14: seven-day expiry from creation, measured in UTC; no automatic approval. |
| Re-request after decline | Same requester and reverse requester | Superseded by D04 Decisions 3 and 5 on 2026-10-14: after a recipient declines, the original requester cannot send a fresh request to that same recipient. Allowed further attempts in that requester-recipient direction are `0`; no cooldown automatically restores permission. The previous recipient may later send a reverse-direction request to the original requester. A recreated match clears prior decline restrictions only; it does not reset lifetime pair budgets or override blocking, authorization or account restrictions. |
| Retry after cancellation/expiry | Same requester | Superseded by D04 Decision 2 on 2026-10-14: 12-hour cooldown from cancellation timestamp or stored `expires_at`, measured in UTC. Approved request-frequency budgets are recorded separately in D04 Decision 4. |
| Request budgets | Service | Approved by D04 Decision 4 on 2026-10-14: sender maximum 3 requests across all matches per 12 hours; recipient maximum 10 incoming requests across all requesters per 12 hours; requester-recipient pair maximum 3 requests over the pair lifetime; reported sender fixed 7-day probation lasting 168 hours from activation in UTC, with reduced allowance of 3 requests per 24 hours rather than a sending ban. No review or support appeal may restore normal allowance early. After probation expires, restore 3 requests per 12 hours subject to other restrictions, without resetting pair lifetime budgets or overriding declined-request, blocking or authorization rules. The sender may contact support and submit a written mistaken-report appeal, but support handling must respect the no-early-restoration rule. Another report during the probation week leaves the active 168-hour probation period unchanged; it does not restart or extend that period. |
| Revoke approved chat | Either participant | Grant becomes `revoked`; restart actor and renewed approval are recorded separately below. |
| Restart an ended approved chat | Only the participant who ended it | Approved by D04 Decision 6 on 2026-10-15: that person sends a fresh request, which the other participant must approve before chat access resumes. D04 Decision 7 on 2026-10-15 approves a 12-hour UTC cooldown from ending/revoking that approved chat before the fresh restart request. D04 Decision 8 on 2026-10-15 approves that a second approved-chat end terminates re-request privileges for the rest of that match's lifetime. D04 Decision 9 on 2026-10-15 approves that the cutoff carries across rematches between the same two users; unmatching and matching again must not restore chat-request privileges after the second approved-chat ending. Decision 5's decline-only clearance does not clear this cutoff. No reset or exception to approved request budgets or contact restrictions is approved. Implementation, regression evidence and reviewed wording remain pending. |
| Block, deletion or relevant suspension | Authorized actor/service | Proposed revocation of pending requests/current grants. Decision 10 separately sets immediate chat privileges on blocker unblock in the reviewed scenario; it does not approve restoration after other closure causes. |
| Unmatch with an approved chat | Participant in the match | Decision 13 approved on 2026-10-15: both participants may keep chatting after unmatch. Unmatching alone does not close/revoke that chat, require a new request/cooldown or count as an approved-chat ending. Other active block/account restrictions and a separate chat ending remain applicable. Implementation and reviewed wording remain pending. |
| Unmatch with a pending request | Unmatch actor; request recipient for later approval | Decision 19 approved on 2026-10-15: unmatch preserves the pending request, but the recipient may approve it only after the pair is matched again and only if the same request is still pending. The original seven-day UTC expiry and independent restrictions are unchanged; unmatch/restoration does not pause/reset expiry or revive an expired request. Decision 20 separately approves restoration by the person who unmatched; remaining restoration details, implementation verification and reviewed wording remain pending. |
| Restore a match after one's own unmatch | Only the participant who performed that unmatch in the reviewed case | Decision 20's earlier no-agreement selection is historical and superseded by Decision 27: this participant's restoration requires fresh agreement from both. Decision 21 approved on 2026-10-15: only the person who unmatched may restore in the reviewed case; the other participant cannot restore that unmatch. A preserved pending chat request still requires its recipient's approval under Decision 19; restoration alone does not approve it. Original expiry, budgets, cooldowns, decline rules, blocks/account restrictions and the second-ending cutoff remain applicable. Decision 22 requires both participants to undo their own unmatch before the match can return when both unmatched. Remaining mechanics/timing/caps, implementation and reviewed wording remain pending. |
| Block and report | Participant choosing the combined action | Decision 23 approved on 2026-10-15: use this option for reporting with blocking. Clear pending pair requests and initially withhold unblock; unblocking becomes possible after safety review. Decision 24 requires explicit safety-reviewer approval; finishing review alone is insufficient. Reviewers may ban for policy or ethical violations or leave the pair blocked without a ban. Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone leaves the block in place. Decision 26 approves immediate chat after the explicitly approved, chosen unblock, without a fresh request or further chat approval; cleared requests and independent restrictions remain unchanged. Substantive criteria, role assignments, ban duration/appeals and review timing remain pending. Ordinary block/unblock, unmatch, fixed probation and budgets are preserved; request physical deletion and D05 evidence/retention remain unselected. |
| Ordinary blocker's unblock | User who owns the block | Decision 10 approved on 2026-10-15: automatically give the pair chat privileges immediately in the reviewed scenario without the second-ending cutoff, without a fresh mutual match or new request approval. Decision 11 on 2026-10-15 confirms this includes never-approved chat without requiring prior approval. Decision 12 on 2026-10-15 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it. Current code needs alignment; the cutoff and other block/account restrictions remain preserved. |

Decision 20's former no-renewed-agreement clause is superseded by Decision 27. Restoration of the existing relationship now requires fresh affirmative agreement from both, while preserving control of each person's own unmatch. This remains restoration, not a genuinely new match or decline-clearance event. Decision 21 limits restoration to that person in the reviewed case: if Alice unmatched Brian, Brian cannot restore Alice's unmatch. Decision 22 requires both participants to undo their own unmatch before the match can return when both independently unmatched; neither can undo the other's unmatch, and one undo alone is insufficient. Under Decision 19, a preserved pending chat request still needs its recipient's approval while matched and before original expiry; restoration alone does not approve it. Decision 13 preserves an already-approved chat after unmatch. Remaining restoration mechanics/timing/caps, matching-side like cleanup and internal representation remain pending. Existing blocks and pair-specific safety restrictions take precedence; no additional restriction clearance is approved.

Decision 23 selects a combined `Block and report` option for reporting in conjunction with blocking. It clears pending chat requests between that pair and initially provides no unblock privilege. Decision 24 requires a safety reviewer's explicit approval for unblocking; completing review alone is insufficient. Safety reviewers may ban users from the app for policy or ethical violations, or leave the pair blocked without a ban. Denying unblock does not automatically require a ban. Decision 25 restores the blocker's option to choose `Unblock` after explicit approval, without automatic removal of their block. Only that blocker's own block is affected by their choice. Decision 26 approves immediate chat after the explicitly approved, chosen unblock without a fresh request or further chat approval; cleared requests and independent restrictions remain unchanged. Substantive criteria, definitions, role assignments, ban duration/appeals and review timing remain pending. This does not select a permanent loss of the unblock privilege. Ordinary block/unblock and unmatch approvals, request budgets and fixed reported-sender probation remain unchanged. Request terminal-state names, physical deletion and D05 evidence/retention/backup rules are not selected. Standalone reporting, unmatch-only reporting and additional report restrictions outside the combined flow remain unresolved.

### 2.3 Block, unblock and unmatch

- The blocking user owns their block; either direction of block denies contact in both directions. A user cannot remove the other participant's block.
- Block immediately suppresses mutual discovery, swipes, requests, chat access and eligible future notification dispatch. It does not reveal the blocker or send a block notification.
- For ordinary blocking, Decision 10 supersedes the earlier proposal requiring fresh matching and approval after unblock. The blocker's unblock automatically gives the pair chat privileges immediately in the reviewed scenario without the second-ending cutoff. Decision 11 confirms this includes never-approved chat without requiring prior approval. Decision 12 excludes blocking an approved chat from the two-ending cutoff; repeated block/unblock cycles do not trigger it or reset separate chat endings. Internal restoration state and other block/unblock details remain pending. This does not approve overriding the cutoff, another participant's active block or account restrictions.
- Decision 13 supersedes the earlier proposal to remove approved chat access on unmatch. The pair may keep chatting without a new match, request approval or restart cooldown; unmatching alone is not an approved-chat ending. Decision 19 separately preserves pending requests on unmatch but denies their approval until the matched state returns, with the original seven-day expiry and independent restrictions unchanged. Unmatch/restoration does not pause/reset expiry or revive an expired request. Decision 27 requires fresh agreement from both for restoration while preserving own-unmatch control and the recipient's separate pending-request approval requirement. Remaining restoration mechanics/timing/caps, unmatch details and reporting access remain separate pending choices.
- Active blocks and account restrictions still deny protected contact. Unmatching alone must not deny continuing approved chat under Decision 13; the earlier blanket unavailable-conversation proposal after unmatch is superseded for that chat. Visibility and retention of genuinely closed conversations remain pending. Unauthorized APIs must not return private payloads.
- Previously displayed messages or notifications already handed to a delivery provider cannot be recalled by a database operation. Describe the actual future-access restriction accurately.
- Revoke authorization, update relationships, preserve any permitted case evidence and enqueue notification/media actions coherently. Never rely on a cascade that deletes protected evidence.
- Define a consistent transaction/locking order for sends, requests, block/unmatch, code state and account deletion. A successful block acknowledgment must mean later protected actions are denied. Test genuine overlap at the HTTP service boundary as well as independent DB connections.
- Mail/notification workers recheck current eligibility and pair restrictions before dispatch and discard obsolete queued notifications. They must not send a stale chat preview or resurrect revoked contact.

### 2.4 Unsend and moderator hiding

Decision 15 approves the original-text handling below. Decision 16 approves a
15-minute unsend window measured from server-recorded sending time, with denial
after that window. Decision 17 approves removing the entry entirely from both
participants' ordinary chat views, without a placeholder. Other projections
and reviewed notices remain pending; evidence access, retention deadlines and
backup deletion remain pending under D05. Other moderation/hold procedures
below remain proposed.

- **Approved action window, Decision 16:** 15 minutes from the message's server-recorded sending time. After that window, unsending is not allowed. Stored timestamp mapping and server-side post-window denial require verification. This timing choice does not approve evidence-retention periods, backup deletion, display/projections or notice wording.
- **Approved ordinary chat display, Decision 17:** after successful unsend, the message entry disappears entirely from both participants' ordinary chat views, without a `Message unsent` placeholder or tombstone. Both chat views require implementation alignment and regression evidence. This display choice preserves Decisions 15-16 and does not select other projections, attachment/cache handling or recall of delivered notifications.
- **Other projection work remains proposed:** review API/search/preview mappings, content-cache purging and revocation of ordinary attachment access. Decision 17 does not approve those additional details.
- **Approved original-text model, Decision 15:** delete the original text of an unsent message unless it has already been preserved as evidence for an existing safety report. The exception concerns an already-preserved report copy and does not approve a new copy merely because a message is unsent.
- **Blanket retention rejected:** do not retain every unsent original for moderation. The earlier blanket 90-day alternative is not selected.
- Evidence access, retention deadlines and backup deletion remain pending under D05. Decision 15 does not approve viewers, storage/retention periods or deletion of recoverable copies, and does not broaden the exception to unrelated hold/case proposals.
- Moderators may hide content under their authorized case procedure; record actor, reason, time, target and case. Hiding does not reset retention or imply unrestricted evidence retention.
- A hold does not authorize retaining content that must lawfully be removed. The designated operator/privacy owner decides applicable escalation and permitted evidence scope.

### 2.5 Reports and restricted evidence

The evidence-access rules below remain proposed under D05. Decision 15 selects
original-text deletion with an already-preserved safety-report evidence
exception; it does not approve who may access that evidence, its retention
deadlines or backup deletion.

- Reports can target the approved user/message/match/confession/reference types. Resolve target ownership and hidden authors server-side. Forged IDs or a claimed prior relationship cannot grant access.
- D04 Decision 23 supersedes the proposed separate report-after-block flow for reporting in conjunction with blocking: use `Block and report`, clear pending pair requests and initially withhold unblock; unblocking becomes possible after safety review. Decision 24 requires explicit safety-reviewer approval and permits app bans for policy or ethical violations, while allowing the pair to remain blocked without a ban. Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone leaves the block in place. Decision 26 approves immediate chat after the explicitly approved, chosen unblock, without a fresh request or further chat approval; cleared requests and independent restrictions remain unchanged. Substantive criteria, role assignments, ban duration/appeals and review timing remain pending. Standalone/unmatch-only reporting and assisted reporting outside this combined flow remain unresolved. D05 evidence access, retention deadlines and backup deletion remain pending; report submission does not itself approve private evidence access.
- Snapshot only relevant content/excerpts, target/time/context and the minimum participant identifiers necessary for the case. Record source IDs and immutable timestamps; do not copy unrelated histories or profiles.
- For live anonymous confessions, authorized moderators may resolve authors privately. If a post is removed on account deletion, preserve identifying context only in an already authorized case where needed; do not promise future attribution after genuinely erasing that linkage.
- Reporters may see only their own case's safe status: pending, reviewed, dismissed or action taken. Never expose another reporter, disciplinary history, private notes or unnecessary target identity.
- Evidence access requires an active named safety role and case assignment or an explicitly justified, audited exception. Audit access before releasing a sensitive payload; revoke access when role/case authority ends.
- Audit viewer, time, case, action/target and reason. Audit records contain no message body, code, password, raw DOB or location payload.
- Bulk export needs named operator approval, an approved scope and destination, encrypted transfer, access logging and an expiry for exported copies. Routine exports exclude credentials, codes, full DOB, precise location and unrelated personal data. Exceptional compelled disclosure requires separate qualified review.

## 3. Revised D05: deletion and retention

### 3.1 Immediate account deletion

1. Authenticate the request using the approved recent-authentication/confirmation rule. A report or hold does not prevent ordinary account deactivation/deletion; preserve only the evidence actually covered by it.
2. Commit a deleted state, all-session/token revocation and loss of discovery/chat/posting access. Enforce current state on every route, media request and worker. A stale session cannot authorize an action.
3. Remove direct identifiers and ordinary profile data in the same transaction where feasible. Inventory email/student number, password hash, reset/verification material, DOB, demographic fields, questionnaire answers, preferences, handles, biographies, contact details, location/consent state and ranking/exposure records. Any necessary asynchronous DB cleanup is inaccessible immediately, queued atomically and has a recorded deadline.
4. Use null direct-identifier fields if schema permits, or collision-resistant random deletion placeholders where a constraint requires a value. Preserve internal integrity without storing a digest of the old email/student number merely to call it anonymous. A separately approved anti-abuse identifier needs its own purpose, access and expiry.
5. Purge the deleted account's ordinary swipes, matches and recommendation data after **either** participant deletes, with no ordinary access during cleanup. Retain minimal restriction metadata only where its prevention/restore purpose is documented. Retain specific case evidence separately rather than preserving the whole relationship table.
6. Revoke profile-media access and queue all relevant Blob objects/derived images for deletion after commit. The proposed physical cleanup ceiling is 30 days; the job should run promptly. Include thumbnails, old versions, soft-deleted objects and application/CDN caches in the inventory and actual completion evidence.
7. **Recommended confession default:** account deletion removes the author's public confessions and relevant public references. Resolve owned content IDs before unlinking; present unavailable references neutrally. Preserve only authorized case snapshots under their own deadlines.
8. If the operator instead chooses continued public posts, approve a separate, genuinely anonymous retention and deletion-request model before enabling it. Clearing a foreign key alone is insufficient: text, quoted names and references may identify people, and an unlinked post may become impossible for its former owner to delete or for moderation to attribute. This alternative is not enabled by this review.
9. Write a durable, minimized deletion/protective-event record and cleanup outbox. Keep the replay source independent of any older restored application database. Do not acknowledge completion unless protective state is durably recorded; use recovery controls for partial external delivery.
10. Record logical restriction time, pending physical work, permitted held categories and actual cleanup completion. User-facing text distinguishes immediate access removal, live cleanup and backup expiry.

### 3.2 Proposed retention schedule

All durations below are **proposed**, not statutory periods or production approval. `purge_due_at` is derived from a specified event, never from last access. Calendar-month/year arithmetic must be documented; day durations use elapsed UTC time.

| Category | Proposed clock and deadline | Treatment and owner decision |
|---|---|---|
| Live approval-code verifier | While the current challenge is valid; default 10 minutes | Destroy on consume, expiry, supersession or exhausted attempts. Shared failure budgets survive verifier deletion for their defined window. No raw code remains. |
| Encrypted code-bearing production outbox payload | Pending send, bounded by challenge validity | Erase after successful send, expiry or terminal invalidation. Production metadata must not retain the encrypted secret indefinitely. |
| Code-free verification audit metadata | Up to 30 days from terminal event | Minimal outcome/times and needed opaque context; justify retained recipient/account fields. Never preserve a verifier or code in the audit record. |
| Synthetic fake-mail capture | Up to seven days from creation | Isolated synthetic development only; production configuration cannot enable this sink. |
| Transactional mail metadata | Up to 30 days from terminal delivery outcome | Minimize addresses/provider identifiers and justify diagnostic purpose; no bodies or code-bearing fields. Unfinished delivery has a finite retry/expiry contract. |
| Operational rate-limit keys/counters | Until the enforcement window and required race/retry margin finish | Remove raw identity/IP keys promptly. A separate code-free security-event record may use up to 30 days only with an approved purpose. Do not retain every operational counter for 30 days by default. |
| Pending account data | Requires an approved abandoned-signup deadline | No indefinite student email/DOB/password storage merely because signup was never completed. The expiry and underage-rejection handling are required D05 choices. |
| Active profile/preferences/questionnaire | Account lifetime while the purposes remain valid | Apply correction, withdrawal and approved inactive-account rules. Do not repurpose answers for unrelated analytics. |
| Deleted ordinary personal fields | Immediate access removal; live erasure promptly, maximum 30 days where asynchronous | Direct identifiers/credentials erased as above; holds cover only selected evidence, not the whole account. |
| Replaced/deleted profile images and derived objects | Maximum 30 days from replacement/deletion | Ordinary access revoked immediately; include versions/soft delete/caches and confirm physical expiry. |
| Ordinary swipes/matches/ranking/exposure data | While needed for active features; after either account deletes, promptly and within 30 days | Case evidence and minimal contact/restore guards have separate purposes and deadlines. |
| Active approved-chat messages | Current approved conversation lifetime, subject to user deletion and reviewed inactivity policy | Decide inactivity/maximum-age treatment and explain participant rights. No indefinite silent evidence archive. |
| Closed conversation content | Proposed maximum 90 days; qualifying closure event remains pending D05. Under Decision 13, unmatching alone does not close an already-approved chat. | The earlier blanket hide-after-unmatch/start-clock proposal is superseded for chat that continues. Purpose, access, actual clock and retention of genuinely closed conversations remain unapproved. Later events must not reset an approved clock. |
| Unsent originals | Decision 15: delete original text unless already preserved as evidence for an existing safety report | Blanket retention is rejected. Evidence access, retention deadlines and backup deletion remain pending under D05; no numerical deadline is approved by this entry. |
| Report evidence and case notes | Proposed 24 calendar months after recorded final resolution | Only relevant evidence; open cases require review, and unresolved status is not a licence for unattended indefinite storage. Holds are scoped and reviewed. |
| Security/moderator audit metadata | Proposed 12 months from the event | No private payloads; longer case-related retention needs a recorded reason and expiry. |
| Consent/notice proof | Minimum necessary proof during processing; post-deletion period requires an approved reason/deadline | Retain policy version, choice/change times and a minimized subject reference where needed. Do not automatically retain a full profile, DOB, email or location. Six years is not a verified requirement in the material reviewed. |
| Public confessions/references | User deletion/moderation request or account deletion under the recommended default | Hold-specific evidence is separate. Continued anonymous publication needs the alternative policy in 3.1. |
| Coarse location and location consent/caches | No production collection until D12 approval | Add field-level freshness, withdrawal and expiry decisions before use. Synthetic defaults remain synthetic only. |
| Deletion/protective replay ledger | While any recoverable copy could resurrect the protected data/action, plus an approved verification margin | Maintain backup/copy inventory and derive a purge eligibility date. An arbitrary 36 months may overretain or fail to cover longer recoverable copies. |
| Managed database backups | Proposed 30-day recovery window under D07 | Configure and verify actual Azure behavior. Backups are not ordinary searchable evidence. Include stopped/restored servers and all independent copies. |
| Application/telemetry logs | Proposed maximum 90 days from event | Code-free and private-payload-free; reduce retention where the purpose needs less. Configure Azure and Datadog separately and verify destinations. |
| Rights-request/export/support records | Requires purpose-specific approved deadline | Keep minimal completion proof; exported files have their own expiry. Do not create an unbounded second copy of a profile/chat history. |

### 3.3 Evidence holds and clocks

- Holds specify case, authority, reason, exact items, start, next review, release and applicable constraints. Review at least every proposed 90 days; escalate overdue reviews rather than treating absence of review as permanent approval.
- Keep an item's normal expiry alongside hold state. An active justified hold suspends purge of only its scope. It does not suspend ordinary account deletion, access revocation or unrelated cleanup.
- `final_resolution_at` means the documented case/appeal completion event under the approved process. The proposed case deadline is that event plus 24 calendar months.
- Releasing a hold does not start a fresh 24-month or 90-day term. If normal expiry has already passed, enqueue purge promptly; otherwise use the existing deadline. A genuinely reopened case requires a new justified decision, not an arbitrary edit that extends every item.
- Ordinary logs cannot become a copy of held evidence. Preserve necessary case records in the restricted store; perform audits without copying bodies into audit messages.

### 3.4 Rights handling and deletion verification

Implement authenticated access/export/correction handling and a secure assisted channel for people whose account is already deleted. Verify ownership without requiring indefinite storage of old identifiers. Redact other people's information and restrict moderator-only records according to the reviewed process. Record receipt, scope, decision, responsible person and completion. Response targets and any exceptions require qualified review rather than a made-up statutory deadline.

Cleanup workers require bounded batches, leases, idempotency, retry/backoff, observable failures and alert ownership. A queue entry is not proof that an image, secret-bearing payload or profile was erased. Retention must be executable and monitored across SQL, Blob, caches, exports, Azure telemetry and optional Datadog.

## 4. Azure backup/restore contract

Azure MySQL Flexible Server supports a configured 1–35-day recovery window; 30 days is within that range. Restore creates a new server. Recovery-window settings do not establish that every underlying copy is physically erased exactly on that deadline; Microsoft also documents retention behavior for stopped servers [S2].

- Inventory managed recovery points, stopped/restored servers, manual SQL dumps, media copies, Blob versions/soft deletion, exports and any existing long-lived archives. Approve actual expiry and access for each.
- Keep the protective replay source durable and independent of the database being restored. Use a high-water mark/checkpoint and verify that the latest accepted protective actions can be replayed. An unavailable/incomplete ledger blocks reopening the service.
- Restore into isolation with web traffic, email/notification workers and cleanup jobs controlled. Restore system credentials and UTC configuration deliberately; do not treat the restored instance as automatically ready.
- Replay account and content deletion, applicable unsend, blocks/suspensions, revoked consent and expiration rules. Remove obsolete queued messages/emails and media access paths. Scrub expired data, not just login state.
- Account for copies created by the restore itself when deciding ledger expiry. Do not export or reopen unreconciled restored data.
- Re-run authorization, privacy and content tests before controlled traffic. Record artifact, restore point, ledger checkpoint, runtimes, outcomes and reviewer.
- A production restore is an engineer/operator action with approved authority. AI may prepare/run authorized technical checks but cannot attest human acceptance or invent completion evidence.

## 5. Required engineering verification

Tests below use synthetic data and the actual route/service contract. Extend the existing suites instead of counting suite names as coverage. Pending-policy fixtures must be labelled provisional. Keep reported local DB overlap distinct from actual multi-worker HTTP evidence.

| Area | Required regression and evidence |
|---|---|
| Request consent | Unilateral like/request cannot authorize chat; reverse pending request does not autoapprove; only recipient approves; at most one current grant under overlap. |
| Repeat contact | Decline/cancel/expiry/re-request boundaries, persisted repeat counter, shared limits and explicit report restrictions. Assert Decision 9's cutoff across rematches and Decision 10's immediate chat on blocker unblock for the reviewed scenario. Include Decision 11's never-approved-chat unblock case. Assert Decision 12's exclusion of blocking from the two-ending cutoff and preservation of separate chat-ending counts and any existing cutoff. |
| Authorization | Pending, underage, revoked, suspended, deleted and role-removed sessions lose the required access in both form/API paths, including two independent sessions. |
| Block/unmatch races | Overlap block/unmatch with send/read/receipt/request and worker dispatch. Assert denial for active blocks and continued approved-chat access after unmatch under Decision 13. Assert Decision 19's pending-request preservation, approval denial while unmatched, eligible approval of the same still-pending request after match restoration and unchanged expiry during unmatch. Assert Decision 27's fresh agreement from both before restoration and preserved own-unmatch authority, while preserving the pending-request recipient's approval, original expiry and independent restrictions. Assert Decision 21's denial of restoration by the participant who did not unmatch in the reviewed case, while preserving the initiating participant's allowed restoration. Assert Decision 22's first own undo leaving the pair unmatched while the other unmatch remains, both own undo actions being required for restoration, denial of attempts to undo the other's unmatch, and unchanged request expiry/recipient approval. Verify ordering, SQL invariants and applicable notification behavior; alignment and focused regression evidence remain pending. |
| Block and report | Verify Decision 23's combined flow and cleared pending pair requests. Assert Decision 24: deny unblock without the safety reviewer's explicit approval even after review completion; allow the reviewer to leave the pair blocked without an app ban, with bans available for policy or ethical violations. Assert Decision 25: explicit reviewer approval without blocker action leaves the block in place; the blocker may choose whether to remove their own block, preserving independent restrictions. Assert Decision 26's immediate chat after explicit approval and the blocker's actual unblock, with no fresh request/approval and no revival of cleared pending requests; preserve independent restrictions. Preserve ordinary block/unmatch behavior, budgets and fixed sender probation. Substantive criteria, role assignments and ban duration/appeal fixtures remain provisional; terminal-state/storage/physical deletion and D05 evidence access/retention remain pending. |
| Evidence safety | Preserve scoped case snapshots through deletion/cascades; forged targets and unauthorized moderators cannot read them. Audit works without copying private bodies. |
| Discovery pause | Assert Decisions 14 and 18: pause preserves eligible matching, sending/receiving chat requests, recipient approval and starting/continuing approved chats. Cover requests and approvals while paused and preserve normal eligibility, block/account restrictions, budgets, cooldowns, decline rules and the second-ending cutoff. Alignment verification and focused regression evidence remain pending; exact discovery presentation and unrelated pause details remain unselected. |
| Unsend | Assert Decision 15's original-text deletion without an already-preserved safety-report copy, its narrowly scoped existing-copy exception and no blanket retention/capture on unsend. Assert Decision 16's 15-minute window from server-recorded sending time and denial after expiry; clock-source mapping and boundary evidence require verification. Assert Decision 17's entry removal from both ordinary chat views, with no placeholder; implementation alignment and focused regression evidence remain pending. Evidence-access, retention-deadline and backup-deletion acceptance remain pending under D05. |
| Codes/outbox | Verifier terminal destruction, encrypted queued payloads, send/expiry cleanup, shared limits and no code in metadata/logs/API. |
| Account erasure | Either participant's deletion triggers the approved relationship cleanup; remove credentials/DOB/answers/location as applicable; no private DTO leaks. |
| Confessions | Default account deletion removes own public posts and safe references; preserves only permitted case evidence; no broken ownership or author leaks. |
| Retention/holds | Injected UTC clock tests due-time boundaries, open/review/release cases and no clock reset by reads, retries or edits. Purge expired items after hold release. |
| Blob/jobs | Retry failed object deletion; include derived/versioned/soft-deleted objects; verify actual completion and alert on overdue deadlines. |
| Restore | Restore synthetic old data with deleted accounts/posts, unsent content, blocks and revoked consent. Reconcile independent ledger and expiry before any traffic/worker; missing ledger fails closed. |
| Azure compatibility | Test the exact Azure-bound artifact and selected PHP/MySQL versions, DB constraints, private connectivity, secrets and worker behavior. |

## 6. Human decisions and the next coding task

### 6.1 Record these operator/privacy/moderation choices

| Choice | Recommended direction | What must be recorded |
|---|---|---|
| Chat transitions | Use the recorded scopes of D04 Decisions 1-14 and 18-27, with the explicit Decision 20 supersession | Preserve all recorded contact approvals, including unaffected functions during discovery pause, Decision 19's preserved pending request with recipient approval only while matched, and Decision 27's fresh agreement from both before restoring the existing relationship, with own-unmatch control preserved. Decision 21 limits restoration to the person who unmatched in the reviewed case; the other participant cannot restore it. Decision 22 requires both own undo actions before the match can return when both unmatched; one undo alone is insufficient. Original expiry and independent restrictions are unchanged. Remaining restoration mechanics/timing/caps, unmatch/block/unblock details, discovery presentation, unrelated pause details, implementation evidence and reviewed wording remain pending. |
| Unsent originals | Decision 15: delete original text unless already preserved for an existing safety report; no blanket retention | Model selected only. Evidence access, retention deadlines and backup deletion remain pending under D05; reviewed wording and implementation verification remain pending. |
| Unsend action window | Decision 16: 15 minutes from server-recorded sending time; unsend denied after the window | Timing selected only. Clock-source/enforcement verification, focused boundary evidence and other projections/notices remain pending; D05 evidence access, retention deadlines and backup deletion remain pending. |
| Unsend display | Decision 17: entry disappears entirely from both ordinary chat views, without a placeholder | Ordinary chat display selected only. Current placeholder requires implementation alignment and regression evidence; other projections, attachment/cache handling and reviewed notices remain pending. |
| Block and report | Decisions 23-26: combined action clears pending pair requests; unblock needs explicit safety-reviewer approval; optional app bans for policy or ethical violations, and leaving the pair blocked without a ban is also allowed | Decision 25 leaves actual unblocking to the blocker's choice after explicit approval; approval alone leaves the block in place. Decision 26 approves immediate chat after the explicitly approved, chosen unblock without a fresh request/approval. Substantive criteria, role assignments, ban duration/appeals, review timing and reporting outside the combined flow remain unresolved; D05 evidence access, retention deadlines, request physical deletion and backup deletion remain pending. |
| Closed chat/report evidence | Provisional 90-day closed-chat and 24-month closed-case ceilings | Purpose, necessity, permitted access, case closure/appeal definition, actual storage and deletion capability. |
| Deleted confessions | Remove public posts/references on account deletion | Approve default or resolve the separate continued-publication/ownership/attribution model. |
| Abandoned/underage signup | Minimize and expire uncompleted/rejected signup data | Exact deadline and any minimal safety proof; never admit an underage account. |
| Consent proof | Minimal proof with a justified expiry | Specify fields, purpose and a reviewed post-deletion period; no automatic six-year rule. |
| Restore protections | Cover every recoverable copy, then purge when no longer needed | Actual Azure/Blob/copy horizons, safety margin, ledger owner and continuity mechanism. |
| Operations and rights | Named case/cleanup/support owners | Access authorization, lawful disclosure/hold authority, request handling and actual response/alert coverage. |

The complete pending questionnaire Q01-Q48 in the Prompt 03 packet records the owner's 2026-10-15 request to receive all remaining questions together. It covers D04-D12 and linked proposals without approving unanswered items.

Use `docs/remediation/decisions.md` with owner, decision, policy version, date, reason, fields/duration, reviewed notices and implementation/evidence links. Retain **pending** until the relevant people actually review and accept these choices. An operator's product approval does not replace the required specialist privacy review in H03.

### 6.2 Copy-ready next prompt for the coding agent

```text
Review the supplied D04/D05 proposal against this revised policy review and
the Azure remediation playbook. Preserve existing changes and test results.

Treat every revised rule and retention number as proposed until an actual
owner decision is recorded. Do not mark D04/D05 approved on my behalf.

First map R01-R12 to the current schema, shared services, actual form/API
routes, cleanup jobs and tests. Report reproduced gaps versus already working
behavior. Inspect foreign keys/cascades and the actual mail payload storage.

Prepare a revised repository policy-proposals-d04-d05.md and decision entries
with the unresolved choices in section 6.1. Keep the policy status pending.
Prepare additive migrations, cleanup/restore design and focused regressions.
You may implement and test clearly labelled synthetic-development behavior
and independent technical safeguards now; production policy must use accepted
values and reviewed user-facing wording.

Implement shared atomic permission checks, explicit chat/request transitions,
case-scoped evidence access, immediate deletion restrictions, and idempotent
outbox cleanup. Do not let a cascade remove protected evidence. Do not retain
expired verifier secrets or enable fake mail in production.

Replace the both-accounts-delete rule with either-account cleanup under the
chosen policy. Resolve confession erasure before unlinking authors. Inventory
credentials, DOB, questionnaires, preferences, media, caches, logs and future
location data. Record UTC purge deadlines without reset on read/retry/edit.

Prepare the independent protective replay ledger and an isolated synthetic
restore test covering deleted content, unsend, blocks and revoked consent as
well as accounts. Missing/currently incomplete replay evidence blocks reopening.

Extend the existing HTTP/browser/DB suites for section 5. Prepare genuine
multi-worker HTTP overlap checks using independent sessions and connections;
record evidence of application overlap, not merely parallel client requests.
Do not claim the Windows single-worker server proved HTTP concurrency.

Keep D12 production location use disabled pending approval; preserve its
labelled synthetic fixtures. Keep real sender/inbox verification pending.

Return the tested commit, runtime versions, changed files, migration ordering,
policy choices still pending, actual PASS/FAIL/NOT RUN results and evidence
paths for each P0 task. Do not claim P0 clearance or deploy a live service.
```

## 7. Source and review boundaries

- **[S1] POTRAZ-hosted Act No. 5 of 2021, published text:** [PDF](https://www.potraz.gov.zw/wp-content/uploads/2022/02/Data-Protection-Act-5-of-2021.pdf), sections 7, 9 and 13 checked 10 October 2026. This is the original publication hosted by the regulator; the specialist reviewer must verify the current consolidated Cyber and Data Protection Act and regulations. The limited statutory summary in section 1 supports purpose/necessity review; it does not certify any proposed duration, exemption or production compliance.
- **[S2] Microsoft Learn:** [Backup and restore in Azure Database for MySQL](https://learn.microsoft.com/en-us/azure/mysql/flexible-server/concepts-backup-restore), checked 10 October 2026. Supports recovery-window, new-server restore and stopped-server backup behavior. Operational recommendations about replay, copy inventory and human acceptance are this review's engineering recommendations.
- The original user proposal is the source of the 15-minute unsend, earlier
  30-day re-request, 90-day evidence, 24-month case, 30-day cleanup/backup and
  other provisional durations. Later D04 Decisions 1-3 approve seven-day
  pending request expiry, a 12-hour cancellation/expiry retry cooldown and zero
  further same-direction attempts after decline. D04 Decision 4 approves sender,
  recipient, pair-lifetime and reported-sender request budgets plus fixed
  7-day reported-sender probation, no early restoration and unchanged
  probation after repeat reports. D04 Decision 5 approves reverse-direction
  requests after decline and recreated-match clearance of prior decline
  restrictions. D04 Decision 6 (2026-10-15) approves restart only by the person
  who ended the approved chat, using a fresh request that the other participant
  must approve. D04 Decision 7 (2026-10-15) approves a 12-hour UTC cooldown from
  ending/revoking that approved chat before the fresh restart request.
  D04 Decision 8 (2026-10-15) approves that a second approved-chat end terminates
  re-request privileges for the rest of that match's lifetime. D04 Decision 9
  (2026-10-15) approves carryover of that cutoff across rematches between the
  same two users; unmatching and matching again must not restore chat-request
  privileges after the second approved-chat ending. Decision 5's decline-only
  clearance does not clear this cutoff. D04 Decision 10 (2026-10-15) approves
  immediate, automatic pair chat privileges on blocker unblock in the reviewed
  scenario without the second-ending cutoff, without fresh matching or new
  request approval. D04 Decision 11 (2026-10-15) confirms that this includes
  never-approved chat; prior chat approval is not required. D04 Decision 12
  (2026-10-15) excludes blocking an approved chat from the two-ending cutoff;
  repeated block/unblock cycles do not trigger it or reset separate chat endings.
  This supersedes the earlier no-restoration-on-unblock proposal for
  that scenario. D04 Decision 13 (2026-10-15) approves continued approved chat after unmatch;
  unmatching alone is not a chat ending or restart. This supersedes the earlier
  close/revoke and blanket hide-after-unmatch proposals for that chat. Other
  unmatch details and retention remain pending. D04 Decision 19 (2026-10-15)
  approves preserving pending requests on unmatch, with approval allowed only
  after the pair is matched again if the original request remains pending.
  Original seven-day expiry and independent restrictions are unchanged;
  D04 Decision 20 (2026-10-15) originally allowed restoration without renewed
  agreement. That clause is now superseded by Decision 27, requiring fresh
  agreement from both while preserving own-unmatch control. Restoration does not
  approve the preserved pending chat request. D04 Decision 21 (2026-10-15)
  approves that only the person who unmatched may restore in the reviewed case;
  the other participant cannot restore that unmatch. D04 Decision 22 (2026-10-15)
  requires each participant to undo their own unmatch before the match can
  return when both independently unmatched; one undo alone is insufficient.
  Remaining mechanics/timing/caps remain pending. D04 Decision 14 (2026-10-15)
  approves continued existing approved chat during discovery/profile pause.
  D04 Decision 18 (2026-10-15) approves that discovery pause does not affect
  matching, requesting, approving or chatting; normal independent contact
  restrictions still apply. Exact discovery presentation and unrelated pause
  details remain unselected. D04 Decision 15 (2026-10-15) selects
  deleting unsent original text unless already preserved for an existing safety
  report and rejects blanket retention. Evidence access, retention deadlines
  and backup deletion remain pending under D05. D04 Decision 16 (2026-10-15)
  approves a 15-minute unsend action window from server-recorded sending time,
  with denial after that window. It does not approve an evidence-retention or
  backup-deletion period. D04 Decision 17 (2026-10-15) approves removing the
  entry entirely from both participants' ordinary chat views, without a
  placeholder; other projections and reviewed notices remain pending.
  D04 Decision 23 (2026-10-15) selects combined block/report, clearing pending
  pair requests and initially withholding unblock; unblocking becomes possible
  after safety review. D04 Decision 24 (2026-10-15) requires the safety reviewer's
  explicit approval for unblocking. Reviewers may ban users from the app for
  policy or ethical violations or leave the pair blocked without a ban; refusal
  to approve unblock does not automatically require a ban. Substantive criteria,
  role assignments, ban duration/appeals and review timing remain pending.
  D04 Decision 25 (2026-10-15) leaves actual unblocking to the blocker's choice
  after explicit approval; approval alone leaves their block in place.
  D04 Decision 26 (2026-10-15) approves immediate chat after the explicitly
  approved, chosen unblock without a fresh request or further chat approval;
  cleared requests and independent restrictions remain unchanged. Reporting
  outside that flow remains pending.
  D05 evidence access, retention,
  physical request-record deletion and backup deletion remain pending. None of
  these numbers is presented as legally mandated.
- The user-reported local PASS results were not independently rerun here. Repository paths in this document are proposed integration targets, not claims that files were edited on the user's Windows machine. P0 remains pending until its full evidence and human gates pass.
