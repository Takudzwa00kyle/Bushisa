# Bushisa D04/D05 policy review and revised proposal

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
| R02 | Unblock, cancellation, expired requests, report restrictions and rematching are undefined. | Specify states, who can change them and whether a new request is permitted. Unblock/rematch must never silently restore old chat consent. |
| R03 | Deleting match rows may cascade-delete messages and evidence. | Preserve authorized evidence before removing relationships. Test foreign keys, retries, mid-transaction failure and genuine HTTP races. |
| R04 | Unsend promises removal while retaining every original for 90 days. | Choose and disclose one evidence model. This revision recommends keeping an original only for an existing scoped report/hold; blanket retention remains an alternative requiring explicit justification. |
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

- One like does not create an established match. A match requires reciprocal likes and the live mutual eligibility checks defined by the approved matching policy.
- A match alone grants no chat access. One participant requests chat; only the other participant can approve that request. Sending a reverse request must not count as approval.
- At most one pending request or current approved chat grant exists for a pair's active match generation. Historical requests do not authorize access to a later match.
- Request approval, send, read, receipts and conversation media require server-side ownership and current authorization. Recheck active/approved/adult account eligibility and any current block, suspension, deletion, revoked grant or pair-specific restriction.
- Discovery pause affects new recommendations/matches. Whether an otherwise eligible existing chat remains available must be stated explicitly in the approved account-pause policy; do not infer it from an unrelated preference flag.
- CSRF/session checks apply to the actual mutation routes. Revoking one session is insufficient for account deletion or a policy requiring all-session revocation.
- Notifications may say that a request or message exists. They contain no message body, sensitive preview or private evidence. Private chat payloads must not enter analytics, Azure/Datadog logs or unrelated API responses.
- Read state is visible only to authorized conversation participants. No public activity status is introduced.

### 2.2 Request states and repeat contact

These are proposed decisions for the operator, not already approved values.

| Event | Allowed actor | Result and consent rule |
|---|---|---|
| Create request | Eligible participant in the current match | `pending`; rate-limited; one current request for the pair. |
| Approve | Recipient of that pending request | `approved`; both participants may chat while all authorization conditions remain true. |
| Decline | Recipient | `declined`; no chat access. |
| Cancel pending request | Requester | `cancelled`; no chat access; cancellation cannot evade request-frequency limits. |
| Pending request expiry | Server/job | Proposed seven-day expiry from creation; no automatic approval. Operator must approve this newly proposed period. |
| Re-request after decline | Same requester | At most one additional attempt after 30 elapsed UTC days, provided a valid match and no current restriction exist. Keep the counter across match recreation for the same accounts. |
| Retry after cancellation/expiry | Same requester | Proposed minimum 24-hour cooldown from closure, plus the approved shared request-frequency budget. Exact budget remains a required D04 value. |
| Revoke approved chat | Either participant | Grant becomes `revoked`; proposing a new chat requires fresh recipient approval and the approved contact limits. |
| Block, unmatch, deletion or relevant suspension | Authorized actor/service | Revoke pending requests/current grants. A later unblock or new match cannot reuse them. |

Unmatch clears prior likes that could immediately recreate the relationship. A new match requires fresh eligible reciprocal actions. Existing blocks and pair-specific safety restrictions take precedence.

A report creates a moderation case. The reporting flow offers immediate blocking; report creation itself must not silently establish an unspecified permanent contact ban. Any additional report-related restriction must have explicit scope, approving authority and review/release behavior. Dismissing a case does not remove a user-created block or restore revoked chat consent.

### 2.3 Block, unblock and unmatch

- The blocking user owns their block; either direction of block denies contact in both directions. A user cannot remove the other participant's block.
- Block immediately suppresses mutual discovery, swipes, requests, chat access and eligible future notification dispatch. It does not reveal the blocker or send a block notification.
- Unblock removes only that user's block. It restores neither the previous match, previous likes nor previous chat grant. New mutual matching and chat approval are required.
- Unmatch removes the match and chat access for both participants without creating a misconduct case by itself. A scoped safety report remains available through the reporting/support process where authorized evidence still exists.
- After block/unmatch, ordinary chat endpoints do not return the hidden conversation, even to previous participants. An already-open page must leave the conversation or show its unavailable state on the next authorized check. Unauthorized APIs return the route's documented denial status without private payloads.
- Previously displayed messages or notifications already handed to a delivery provider cannot be recalled by a database operation. Describe the actual future-access restriction accurately.
- Revoke authorization, update relationships, preserve any permitted case evidence and enqueue notification/media actions coherently. Never rely on a cascade that deletes protected evidence.
- Define a consistent transaction/locking order for sends, requests, block/unmatch, code state and account deletion. A successful block acknowledgment must mean later protected actions are denied. Test genuine overlap at the HTTP service boundary as well as independent DB connections.
- Mail/notification workers recheck current eligibility and pair restrictions before dispatch and discard obsolete queued notifications. They must not send a stale chat preview or resurrect revoked contact.

### 2.4 Unsend and moderator hiding

- The sender can unsend their own message for 15 minutes after immutable server-set `created_at`, using UTC and a live authorization check. Retries are idempotent and edits do not extend the window.
- The public conversation projection becomes `Message unsent` for both participants, including API, search/preview and applicable attachment projections. Purge content caches and revoke ordinary attachment access.
- **Recommended evidence model:** if a permitted report snapshot or scoped hold already covers the message, retain the required original only in restricted evidence with a deadline. Otherwise purge the original from live storage rather than silently creating a universal shadow archive.
- **Alternative requiring explicit approval:** retain every unsent original for at most 90 days from `unsent_at` solely for a documented safety-reporting purpose. Require privacy review, upfront user notice, restricted encrypted storage, case-scoped access, auditable expiry and the capacity to actually purge it. This alternative is not enabled by this review.
- Unsend does not erase an existing case snapshot. Retaining a reported original is governed by D05, not by renewed ordinary chat access.
- Moderators may hide content under their authorized case procedure; record actor, reason, time, target and case. Hiding does not reset retention or imply unrestricted evidence retention.
- A hold does not authorize retaining content that must lawfully be removed. The designated operator/privacy owner decides applicable escalation and permitted evidence scope.

### 2.5 Reports and restricted evidence

- Reports can target the approved user/message/match/confession/reference types. Resolve target ownership and hidden authors server-side. Forged IDs or a claimed prior relationship cannot grant access.
- Accept authorized reports after a block/unmatch through a scoped reporting endpoint or assisted support channel while relevant evidence remains available. Do not reopen the conversation to enable a report.
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
| Closed conversation content | Proposed maximum 90 days from first block, unmatch, relevant deletion or grant revocation | Hidden from ordinary UI immediately. Retain only if the safety-reporting purpose and access model are approved; otherwise purge promptly. Later events cannot reset the clock. A fresh chat never exposes this archive. |
| Unsent originals | Immediate live purge unless an existing scoped case/hold requires them | Optional blanket 90-day alternative requires the explicit separate approval in 2.4. |
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
| Repeat contact | Decline/cancel/expiry/re-request boundaries, persisted repeat counter, shared limits and explicit report restrictions. Rematch/unblock cannot restore an old grant. |
| Authorization | Pending, underage, revoked, suspended, deleted and role-removed sessions lose the required access in both form/API paths, including two independent sessions. |
| Block/unmatch races | Overlap block/unmatch with send/read/receipt/request and worker dispatch; verify acknowledgment ordering, denial, SQL invariants and suppression of obsolete notifications. |
| Evidence safety | Preserve scoped case snapshots through deletion/cascades; forged targets and unauthorized moderators cannot read them. Audit works without copying private bodies. |
| Unsend | Server-time boundary, idempotency, every projection/attachment, case-held versus unreported content and no ordinary access to retained originals. |
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
| Chat transitions | Explicit states in 2.2; no silent consent restoration | Approve expiry/cooldowns/request budget, re-request scope, revoke/rematch and account-pause behavior. |
| Unsent originals | Purge unless an existing scoped case/hold requires them | Choose this model or explicitly justify the blanket 90-day alternative and approve the actual notice. |
| Closed chat/report evidence | Provisional 90-day closed-chat and 24-month closed-case ceilings | Purpose, necessity, permitted access, case closure/appeal definition, actual storage and deletion capability. |
| Deleted confessions | Remove public posts/references on account deletion | Approve default or resolve the separate continued-publication/ownership/attribution model. |
| Abandoned/underage signup | Minimize and expire uncompleted/rejected signup data | Exact deadline and any minimal safety proof; never admit an underage account. |
| Consent proof | Minimal proof with a justified expiry | Specify fields, purpose and a reviewed post-deletion period; no automatic six-year rule. |
| Restore protections | Cover every recoverable copy, then purge when no longer needed | Actual Azure/Blob/copy horizons, safety margin, ledger owner and continuity mechanism. |
| Operations and rights | Named case/cleanup/support owners | Access authorization, lawful disclosure/hold authority, request handling and actual response/alert coverage. |

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
- The original user proposal is the source of the 15-minute unsend, 30-day re-request, 90-day evidence, 24-month case, 30-day cleanup/backup and other provisional durations. The newly proposed seven-day request expiry and 24-hour cancellation/expiry cooldown need operator approval. None of these numbers is presented as legally mandated.
- The user-reported local PASS results were not independently rerun here. Repository paths in this document are proposed integration targets, not claims that files were edited on the user's Windows machine. P0 remains pending until its full evidence and human gates pass.
