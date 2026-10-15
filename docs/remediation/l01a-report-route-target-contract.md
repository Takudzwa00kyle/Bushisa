# L01a: report-route and target-authorization contract

Documentation review: 2026-10-16, Africa/Harare. Source inspection capture:
2026-10-15T22:02:14Z / 2026-10-16 00:02:14 Africa/Harare (UTC+02:00).
This is a documentation timestamp, not a policy acceptance timestamp.

Baseline: `e0a0066e1ebb77772c54fb7eac11b09a684f2eac`, on
`codex/prompt03-owner-decisions-20261014`, initially clean and one local commit
ahead of its tracking branch. No applicable AGENTS.md was found in the checkout
or parent folders. Source files were read, not executed; no HTTP, database,
mail, production configuration, migration, merge, push or deployment action.

**L01a documentation is complete; L01 implementation and acceptance are pending.**
The requirements below trace existing product approvals. Wire vocabulary,
idempotency/proof storage and the additive implementation slice are **engineering
proposals for review**, not newly approved product rules or implemented features.
Takudzwa Hondova remains the product reviewer. Named safety/privacy/security/
engineering operators and backups remain UNASSIGNED. No existing approval status,
missing acceptance timestamp or qualified-review gate changes in this task.

## Requirements and independent boundaries

| Authority | Required behavior carried into L01a |
|---|---|
| [Register](decisions.md), Q01/Q02 in the [acceptance supplement](owner-policy-reconciliation-2026-10-15.md) | Authenticated reporting without block, after unmatch and onto an ordinary block; approved target families; minimal input and <=2,000-character explanatory text; text-only reporting works. Reporting never grants contact or private-content access. |
| Q03/Q04/Q19 | Allegation differs from finding; durable sanctions require human review; personal block belongs to its creator. Assigned-case access, controlled exceptions, redacted disclosure and independent appeals are separate permissions. |
| D04 Decisions 4 and 23-26 | Fixed 168-hour qualifying-report probation, no early rate restoration or active-period extension. Combined Block and report clears pending pair requests and requires explicit reviewer clearance plus blocker's choice before unblock; its earlier immediate-chat effect is preserved. |
| D04 Decisions 13/19-22; Q06-Q09 | Approved chat survives unmatch; pending request keeps original expiry but approval waits for matched state. Reporting must not require undo, new likes, renewed chat approval or resuming discovery. |
| D04 Decisions 15-17; Q10/Q18/Q20 | No new capture of erased unsent originals; already-preserved existing-report evidence is separate. Capture only relevant available evidence; context maximum is necessary 10 preceding and 10 following messages. Item-scoped holds do not preserve entire chats/accounts. |
| Q17-Q27 and [retention matrix](data-inventory-retention-matrix.md) | Separate filing permission, ordinary read permission and assigned evidence access; enforce original expiry despite unpurged rows. Final-resolution/appeal/hold clocks and independent recovery protections remain unchanged. |

The two existing conflicts do not block documenting or implementing a separately
authorized text-only report intake. Neither report creation nor duplicate
handling decides them:

| Conflict (status unchanged) | Earlier approval | Conflicting proposal and practical choice |
|---|---|---|
| Renewed consent: pending | Decisions 20-21: only the unmatcher restores without the other's renewed agreement. Decision 22: if both unmatched, each undoes their own action. | Q08 requires the other's current consent, allowing refusal. Owner must choose whether to retain approved own-undo restoration or explicitly replace its no-renewed-agreement rule. The separate selected one-success-per-pair/rolling-24h cap is not reopened. |
| Reply-To: pending | D11 requires an owned sender, approved From and matching Reply-To. | Q45 proposes no Reply-To until a real monitored receiving mailbox is verified. Owner must allow that temporary unset state or retain verified matching Reply-To as a mail-release requirement. `support@bushisa.me` and its operator remain unverified proposals. |

Report-only actions do not send acknowledgment email in this contract, so the
Reply-To conflict need not delay an in-app receipt design. This does not waive
the separate monitored support/privacy-contact or staffing release gates.

## Existing route and caller map

Line references describe the baseline source, not executed behavior or proof
that a route is enabled safely in production.

| Existing surface | Source observation | Gap against approved requirements |
|---|---|---|
| POST [api/safety.php](../../api/safety.php), lines 17-42 | POST-only; `require_login()`; JSON/form body; session-derived actor; header/form CSRF. Actions are only `block`, `unblock`, `report`. | No `block_and_report` action, case-update/own-receipt read route, explanation field or report-specific atomic duplicate/probation mechanism in this handler. Existing auth/CSRF alone does not authorize a target. |
| Same route, `report`, lines 91-191 | Accepts `user`, `photo`, `message`, `match`, `confession`, `confession_reference`, `conversation`. `user` derives subject from target; confession derives author; message joins its match to either participant. Other types lack equivalent target-specific resolver branches and can retain client `reported_user_id`. Inserts report and new case in one transaction; returns pending/case ID. | A recognized enum or positive ID is not permission. Message membership alone does not check unsend/expiry/prior access; confession lookup does not check visibility/deletion. Match/reference/photo/conversation need real ownership/exposure validation. No client-supplied subject attribution. Each valid retry currently inserts another case. |
| Same route, `block`/`unblock`, lines 44-88; [block helper](../../php/block_handler.php) | Block uses insert-ignore and closes pair relationships transactionally; unblock deletes the actor's block row. | Separate ordinary and combined-report block state/clearance is missing. Reporting must not call unblock, restore chat or treat a block as proof that all target content may be read. Existing unblock drift belongs to L02, not L01a policy changes. |
| [php/report_handler.php](../../php/report_handler.php), `submit_report`, lines 18-93 | User-only helper; validates user existence, truncates reason to 255, rejects any pending report against the same user and can store an `evidence_path`. No active caller was found by the PHP/JS caller search. | Not equivalent to the API: no canonical target authorization, case/snapshot link or new-information update; pending-user duplicate scope can discard distinct incidents. A supplied file path is not an approved upload. Consolidate rather than activate it as a second intake. |
| Same helper, `get_reports_by_user` / `get_reports_against_user`, lines 102-132 | Returns raw report fields, including reporter IDs and evidence paths; viewer checks are not inside these helpers. No HTTP callers found by the search. | Never wire these rows straight to a student DTO. Reported-person disclosure must be a controlled redacted allegation/outcome, not the reporter/evidence table. |
| POST [api/confessions.php](../../api/confessions.php), `flag`, lines 108-135; [JS caller](../../js/confessions.js), lines 667-694 | Authenticated/CSRF-checked flag path directly sets `is_flagged=1`; no moderator/case-assignment check, report case or evidence snapshot in that branch. The JS labels it reporting. | Do not equate a student's report with an authorized global hide/finding. Route ordinary reporting through common intake; reserve hide/flag for reviewed moderator authority or a separately approved narrow protective measure. Reading this JS does not establish that the legacy UI is loaded/live. |
| [php/policy_safeguards.php](../../php/policy_safeguards.php), lines 261-336 | Always creates a new case; copies ordinary message/confession body by ID, clips to 1,000 characters and writes a snapshot/hash; other accepted types get no such body resolver. Case/evidence expiry uses provisional creation-time +24 months. | Resolver is not a target authorization check; no contextual visibility/expiry proof, dedupe or explicit justification. Align approved final-resolution 180d/30d/365d clocks with L03; hashing a snapshot does not make it anonymous. |
| Same file, `bushisa_case_evidence_for_viewer`, lines 338-385 | Current role plus assignment or admin/nonempty-reason exception; audits a view, then reads unpurged evidence. Caller search finds tests, not an HTTP evidence-view route. | An admin's free-text reason is not Q19 privacy/security authorization. No proof here of MFA, full audit actions, immutable expiry denial or an implemented assigned-case UI/API. |
| GET/POST [admin/reports.php](../../admin/reports.php), lines 17-133 | Current login/moderator role and POST CSRF; lists reports without assignment scope; can dismiss/review/suspend via server branches. Buttons render disabled. | Disabled controls do not remove server actions. Case assignment, redaction, findings/notices/appeals and independent review are not enforced by this page. `reviewed` is not automatically final resolution. |
| GET/POST [admin/moderation.php](../../admin/moderation.php); [moderation helper](../../php/moderation.php) | Role/CSRF-gated unflag/delete/unsuspend branches; lists flagged content/suspended accounts. Disabled buttons coexist with server branches. | No case-scoped permission or independent appeal gate in these handlers; direct hard-delete and raw queues require evidence/lifecycle alignment. No authority to remove another person's personal block. |
| [php/session_manager.php](../../php/session_manager.php), lines 174-270; profile/chat/confession pages | Current session/account/role version checks; pending/underage/suspended/deleted accounts are restricted; several student controls show under-development states. | Do not remove account guards or require a new active match to report a retained interaction. Reporting/appeal access for people who cannot sign in needs its own reviewed assisted/restricted route, not silent reuse of discovery eligibility or a fabricated inbox. |

Existing [Prompt 02 route tests](../../tests/browser/prompt02-chat-routes.spec.js),
lines 275-310, describe outsider-message denial, participant reporting and a
report snapshot surviving a subsequent unsend. They were **read, not rerun**.
The existing candidate attribution remains head
`e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push run `38546846225`, PR run
`38546851035`; it proves none of the new contract's unexecuted cases.

## Permitted targets and server authorization

The target families are approved; the suggested wire names/mappings are
engineering proposals. The server must resolve a canonical object/event and
subject from authoritative data. IDs, case references, guessed URLs, possession
of an old screenshot or caller-supplied subject IDs are not authorization by
themselves. No target family grants general browsing or a full-conversation dump.

| Approved target family | Required reporter/target relationship | Permitted scoped evidence; response restrictions |
|---|---|---|
| Person/account (`user`) | A currently authorized visible profile or server-verifiable prior legitimate encounter/interaction. No mutual match prerequisite. A blocked/unmatched/now-restricted target need not become discoverable/active again. | Resolve canonical account privately; take only relevant legitimately available material and submission text. Do not return whether hidden/deleted accounts exist or accept arbitrary account enumeration. |
| Individual message (`message`) | Correct original conversation participant plus current legitimate access, or verifiable prior legitimate access/receipt within the applicable retention window. Being in some match or knowing the message ID is insufficient. | Exact message and necessary bounded same-interaction context, only if still lawfully available. No reading erased/expired originals, unrelated threads or new post-block content. Never return a source body/preview/media link in the report receipt. |
| Match/request behavior (`match`, request/event mapping to be specified) | Reporter is the affected pair participant/request sender or recipient, with the correct retained event/generation. Match state may be unmatched/blocked or the request terminal. | Minimized event facts such as permitted request/decline/cancel/end history. No chat-body capture by choosing a pair target. A reused mutable request row is not stable proof of the earlier event. |
| Confession (`confession`) | Currently legitimate visible content, or a verifiable legitimate prior encounter within retention. Author identity resolves privately if retained; a missing author must not force deanonymization or invented attribution. | Relevant still-available post/attachments; no hidden author, profile link, student identity or suppressed source excerpt in any public/reporter response. |
| Confession reference/quote (`confession_reference`) | Correct reference row and accessible quoting post, or verifiable prior legitimate encounter. `confession_references.id` is distinct from the quoting/source confession IDs. | Report the actual reference/quote. Resolve the quoting actor privately; source content is separately authorized and available or stays unavailable. Never recreate an erased original from the relationship or disclose its author. |

Existing `conversation` may be a compatibility alias for narrowly defined
pair/request behavior, **not** a new all-message capture permission. Standalone
`photo` is accepted by current code but not expressly selected as an additional
Q02 target family: proposed mapping is to its authorized account/message/post
parent. Independent photo target scope and legacy aliases need safety/product
review before activation. Supporting-image uploads remain gated; text intake
cannot depend on them. Preserve existing self-target guards until their handling
of legitimate own-content/context reports is explicitly reviewed; do not invent
a new owner-approved self-report policy from current code.

## Reporter authorization and state handling

**Required:** actor comes from the authenticated session, checked against current
account/session state; mutating requests need CSRF. A personal block, unmatch or
discovery pause is not a reason to require contact restoration before a safety
report. Reporting conveys no exemption from account bans/deletion/session
revocation, and an unused numerical allowance never authorizes a target.

**Engineering proposal:** separate `can_file_report`, `can_read_ordinary_content`
and `can_view_case_evidence` decisions. A report resolver may record a legitimate
historical interaction without giving the reporter a fresh content read. Reuse
neither a chat guard that requires active matching nor a blind raw-by-ID loader.
For historical content, verify an existing encounter/event/authorized-receipt
record bound to this reporter and target/version; design its minimal fields,
integrity and expiry under D05 review. Do not start an indefinite content-access
log. Where no reliable proof remains, accept an authorized person/pair report
with the reporter's own explanation if possible; do not fetch an unverifiable
private item merely to complete a form. Missing proof is a case-context limit,
not permission to claim that an alleged incident is false.

| State at report time | Required handling |
|---|---|
| Eligible actor, no block and no match | Person/visible-public-content report remains possible; private targets still require their own proof. Never create a match/request/chat as a side effect. |
| Unmatched with continued approved chat | Preserve that chat grant under Decision 13, but separately authorize the reported item. Do not count a chat ending or perform undo. |
| Unmatched with a pending request | Report the retained pair/request behavior without approving/cancelling it; original expiry and matched-state approval condition remain. |
| Ordinary block in either direction | Allow authorized historical report and preserve all blocks; no unblock, new source access, recipient notification or resumed contact. Adding a report does not itself decide the separate unblock-clearance policy. |
| Explicit Block and report | Combined atomic action records the report/block, clears pending pair requests and preserves review-gated unblock. Ordinary reporting/blocking remain separately available; no fresh approval or rate restoration is inferred. |
| Unsent, deleted, expired or no longer accessible source | Never restore source text/media or links. A metadata-only allegation about a verifiable retained interaction can proceed; an already-preserved report copy remains private to its authorized case. No new case may use that copy to widen access automatically. |
| Actor cannot authenticate / has revoked session or account restriction | Existing endpoint guards remain. Future appeal/rights/assisted reporting needs named operators and reviewed limited authorization; it does not reactivate the account or relax eligibility. Do not advertise an unverified support address. |

Whether adding a report to an **existing ordinary block** also attaches the
combined flow's review-before-unblock restriction is a focused unresolved L02
transition detail. Intake can record the report while preserving the block;
it must not silently reclassify the block or expose an unblock action. This
dependency does not change either of the two owner conflicts or approve a new
transition. Actual combined-action implementation remains pending.

## Proposed intake and response contract

Reuse POST `api/safety.php` through one common authorization/intake service for
API and later form callers. This is a proposed extension, not an implemented
new endpoint. Product-required inputs are target family/identifier, reason
category and an explanation where the category is insufficient. Optional
explanation is <=2,000 characters; reject overlength/invalid text rather than
silently truncating it into the existing 255-character reason column. Preserve
text-only filing. Wire names, aliases, reason-code taxonomy and body limits need
engineering/safety review. A reason category is never a finding or sanction.

The session supplies reporter identity; server supplies UTC time, case and
interaction IDs, canonical subject and event/version. Client `reported_user_id`
is not authority. A student cannot choose a filesystem evidence path, arbitrary
media URL, role/assignment, sanction, case owner or retention deadline. Images
need the separately gated private upload flow, not an arbitrary URL fetch.

| Proposed operation | Authorization and result |
|---|---|
| Create report | Authenticate, check CSRF, authorize canonical target and atomically link submission/case/permitted evidence; return only an own-submission receipt. Existing `report` action needs alignment. |
| Explicit combined action | Proposed `block_and_report` uses the same intake plus approved block/pending-request changes in one transaction, not two client calls. Current route does not accept this action. |
| Add information / retry | Resolve the reporter's own case/idempotency scope; intentional information is a bounded attributed addendum, transport retry has no second effect. Endpoint/path remains to be selected. |
| Own receipt/status | Authenticated owner only; limited submission and safe reviewed outcome. A case number is not a bearer credential. No existing HTTP receipt/status route was established by the source search. |
| Evidence / decision / assignment / export | Separate case-scoped operations; no permission merely from owning a report, being its subject, having cloud/DB admin access or providing a reason string. |

Receipt allowlist proposal: own case reference, own submission ID/time,
received/pending status and a safe reviewed summary. Own submitted text may be
shown subject to its lifecycle. Never echo source bodies/context, hidden authors,
private IDs/paths, snapshot hashes, assignments, other reporters or media URLs.
Any later allegation/decision notice uses Q19 redaction and review; ordinary
filing does not reveal the reporter to the other person.

Malformed input may get a validation response, but nonexistent, unauthorized,
expired/erased and out-of-scope private targets use a common outward unavailable
result without existence, lifecycle or author details. Proposed HTTP vocabulary:
400 invalid shape, 401 invalid session, 403 invalid CSRF, 404 unavailable
target/case, 429 separately approved abuse control, generic 5xx failure. These
are engineering choices, not observed behavior or approved numerical limits.
Avoid timing/count/error differences that make intake a private-content probe.

## Evidence capture and expiry

1. Authorize before raw content lookup and recheck during the locked snapshot
   transition: reporter, canonical object/event/version, applicable expiry and
   existing case scope. Membership-only and raw-ID loaders are insufficient.
2. Capture the reported available item, relevant attachments/times/IDs and
   necessary safety state. Necessary context is at most 10 preceding and 10
   following existing, legitimately available messages in the same interaction,
   bounded by their own lifecycle. Do not subscribe to future messages or copy
   the entire chat. Wider scope needs documented justification and authorized hold.
3. Never recapture erased unsent originals from cache, reference, backup or
   shadow field. A report snapshot that preceded unsend stays in that authorized
   case; it does not become ordinary chat or unrestricted material for a new
   case. An expired source awaiting a worker is unavailable for new capture;
   an existing evidence hold protects only its already-authorized evidence.
   Filing a new report does not create a retroactive erasure exception.
4. Order report versus unsend/deletion/expiry explicitly. Report first may
   preserve an authorized pre-erasure copy; erasure first leaves no original.
   A valid retained interaction can support a metadata/text allegation without
   content. Distinguish deliberate absence from storage failure; exceptions
   must not commit partial/untracked capture or a misleading combined action.
5. Keep evidence private and outside ordinary cascades. Attachments require
   authorized access, not raw/public links. Audit actor, case/item, action,
   UTC time, purpose and outcome without duplicating content into logs.
6. Later implementation replaces provisional creation +24 months with selected
   final-resolution clocks: case content +180d, wholly unrelated/accidental
   content <=30d, content-free decisions +365d; access audit is event +365d.
   Effective notice, appeal window and timely appeal determine final resolution;
   legacy status `reviewed` does not establish that event.
7. Q20 holds remain scoped: urgent reviewer <=24h before joint approval;
   ordinary safety-lead/privacy authorization <=30d, reviewed at least every
   14d. Review/release never resets normal expiry; overdue items purge <=24h
   after release. Open cases need real assigned review/escalation, not infinite
   retention. L01a invents no additional open-case service deadline.
8. Replay original expiry and current protections before restored reads. The
   conditional 7+35+7=49d recovery example and proposed copy settings remain
   unchanged. L03 must prove actual erasure and independent continuity. Isolated
   restore is NOT RUN; migration 005 is not declared production-applied.

## Duplicate submissions, new information and probation

Approved requirement: duplicates can add information to the same case without
multiplying sanctions. Engineering proposal: server-enforced idempotency scoped
to authenticated reporter/operation, with canonical target/event/version and
incident linkage. Exact storage/expiry requires D05 review, not indefinite
request logs or a newly invented retention period.

- Same transport operation/payload replays its own safe receipt with one
  report/case/evidence/effect, including concurrent retries or lost responses.
  Do not fetch source text again after erasure to answer a retry.
- Same key with changed content/target is a conflicting retry, not authority
  to attach information to another case. Final error code is a reviewed
  engineering choice.
- Intentional new information about the same incident appends a bounded,
  attributed own-case submission. The new event never resets old evidence or
  decision clocks. It does not automatically reopen a final case; material
  reopening requires a justified human decision.
- Distinct incidents against one person must not be discarded by the legacy
  pending-user duplicate rule. Different reporters never learn each other's
  case IDs/submissions through dedupe. Internal clustering needs case-scoped
  access and does not automatically merge viewer permissions.
- Invalid/unauthorized requests create no case, evidence or sanction effect.
  A qualifying first probation trigger must be once-only and atomic with its
  authoritative case event. The qualifying-report definition needs safety
  review; an enum value or accepted HTTP request alone does not establish it.
  Duplicates/addenda never extend the fixed active 168h period or shorten it
  through appeal. Post-expiry renewal and numeric ban defaults remain proposed.
- No new numerical report-rate limit is selected. Chat-request allowances
  neither authorize a report target nor become a reporting sanction/budget;
  independent authorization and proportionate reviewed abuse controls apply.

Submission/case linking, authorized evidence, any once-only qualifying effect
and explicit combined block/pending-request changes require transactionality
and retry safety. Internal failure rolls back new effects; it must not remove
an already-existing block. Ordinary blocking remains available without an
accusation. No acknowledgment mail is sent in the proposed text-only slice.

## Role and disclosure contract

| Actor | Permitted scope | Denied without separate authority |
|---|---|---|
| Authenticated reporter | Own submission/receipt and safe reviewed status; authorized new report/addendum | Snapshots, unrelated content, hidden authors and other reporters/cases. |
| Reported person | Reviewed redacted allegation/rule/finding sufficient to appeal | Direct `get_reports_against_user()` rows, raw reporter IDs/paths or unrestricted case browsing. |
| Assigned safety reviewer | Assigned cases only; current account/role/assignment, required MFA and access audit | Unassigned cases, bulk export, another user's unblock, own appeal or manufactured consent. |
| Safety lead | Recorded assignment/revocation and authorized safety decisions | Global content browsing merely by title; evidence still needs assigned/exception scope. Holds use Q20 authority. |
| Privacy/security approver | Recorded exceptional access/export scope, purpose, expiry and named encrypted recipient; export <=24h | Routine access from DB/cloud admin status or a free-text exception reason. |
| Independent appeal reviewer | Properly assigned/redacted case under appeal authority; permanent reversal needs second independent reviewer | Initial reviewer deciding their own appeal or clearing a personal block/chat by review alone. |

Named appointments, MFA and coverage remain unverified/unassigned. The current
assigned-view helper is partial support; it does not prove the complete Q19
view/download/assignment/export/change/delete audit or access workflow.

## Minimum later additive implementation slice

This is an engineering proposal, not a migration or code change:

- One shared target resolver with proper reference/source IDs, nullable hidden
  subject attribution, state/expiry checks and minimized historical event proof.
- Separate reason/explanation, immutable submission time, reporter/canonical
  target/event generation, explicit report-to-case link, idempotency uniqueness
  and bounded addenda. Current report/case rows alone do not establish this.
- Case-scoped role/exception authority, audit actions and decision/effective
  notice/appeal/final-resolution fields. Existing status values are insufficient.
- Authorized bounded snapshots, private absence metadata, original deadlines,
  scoped holds and recovery instructions coordinated with L03; no silent reuse
  of the old +24-month creation-time default.
- Text-only initial scope; unresolved aliases, uploads and missing authority
  fail closed. Later release still covers every approved target family: this
  task does not remove features from D03.
- Choose a genuinely free forward migration identifier after inventory/rehearsal;
  `005_protective_replay_event_precision.sql` already exists. No runtime DDL,
  reset/seed or schema file is created here.

## Synthetic negative-case plan for later execution

Every row is **NOT RUN in L01a**. These are later evidence requirements, not
tests written/run here or claims about the older candidate CI.

| Case | Expected observation |
|---|---|
| Forged reporter/subject/case ID | Session actor/server subject win; no other reporter's case, receipt, evidence or identity. |
| Missing CSRF, stale session, removed role/assignment | Deny with no new case/evidence/effect; disabled UI provides no server bypass. |
| Outsider guesses message/match/request IDs | Common unavailable response, no snapshot/write or private existence detail. |
| Valid message paired with wrong thread/event | Reject mismatched membership/generation; no second-conversation capture. |
| Legitimate report after unmatch/either-direction block | Own receipt only; no source body/contact restoration, block/match/chat change or pending-request expiry reset. |
| Paused discovery / never-matched visible profile | No required discovery resumption or new mutual like; independent target authorization still holds. |
| Explicit combined versus report-only action | Combined clears pending pair requests with review-gated blocker choice; report-only does not silently become unblock/restart/combined state. |
| Forged confession/reference/photo/conversation alias | Correct authorized parent/reference/event only; no hidden author/source, arbitrary path or full-chat capture. |
| Deleted/flagged/expired/unsent original; worker not run | No new original capture, media URL or reconstruction; a valid limited allegation may proceed without the source. |
| Report-vs-unsend/delete/expiry overlap | Authorized pre-erasure copy or no original; no partial capture, resurrection, clock reset or lost protective event. |
| Context beyond 10 before/after or unrelated messages | Bounds enforced; wider scope needs justification/hold; no future-message subscription. |
| Same transport retry/concurrent duplicate/lost response | Same own receipt; one case/evidence/effect, no duplicate sanction or renewed clock. |
| Same key changed payload; new material; distinct incident | Conflicting retry denied; own addendum attributed; distinct incident not dropped by user-only dedupe; no automatic reopening. |
| Report during probation / after expiry | Active start/end unchanged; qualifying effect once-only; unadopted post-expiry renewal inactive. |
| Unassigned moderator/admin reason / raw against-user helper | No routine evidence or reporter-identity disclosure; actual assignment/qualified exception and audit required. |
| Oversized/invalid explanation; image/path/URL input | <=2,000 characters and safe rendering; no silent truncation or file fetch; text-only reporting remains usable. |
| Expiry/appeal/hold/retry/replay/export | Original clocks and role access enforced independently of jobs; no ordinary resurrection or fresh retention from reads. |

## Remaining gates and next bounded task

| Gate | Remaining fact or decision; status unchanged |
|---|---|
| Product/safety scope | Qualifying-report trigger, legacy target/reason/self-target handling and report-added-to-ordinary-block transition need review. Post-expiry probation renewal, ban defaults and report service targets remain proposed. |
| Engineering | Accept target/event/proof/idempotency/response/schema design and L02/L03 interfaces; implement the mapped gaps in a later authorized task. |
| People/process | Name and accept safety lead, reviewers/independent appeals, privacy/security, engineering/operations and backups. Verify MFA, assignment, redaction, overdue alerts and actual capacity before public deadlines. |
| Data/privacy | Qualified H03 review of historical proof, minimized evidence, sensitive fields, immutable retention/holds and protected pseudonyms; actual cleanup/copy/independent replay evidence. |
| Verification/release | Later guarded route, ownership, dedupe/race and lifecycle tests plus required human acceptance. D04/D05/D12 and operational gates remain open; no new mail, production-applied 005 or completed restore claim. |

**Next bounded task, L01b:** review this engineering design and prepare the
smallest common text-report resolver/intake slice on guarded synthetic systems
under a separately authorized implementation task. Start with target proof,
server attribution and once-only own-case receipts, including authorized
historical reports and denial cases. Do not activate uploads, invent qualifying
reports, adopt ban/renewal proposals, change personal blocks or promise public
service deadlines. Both owner conflicts stay pending and independent.

## Documentation checks

PASS for this documentation checkpoint: `git diff --check`; 37 local links and
anchors across the four affected Markdown files; presence of all 11 contract
sections once; paired code fences and no trailing whitespace in the new file.
Scope/status checks confirm only this contract, backlog, navigation README and
policy implementation-map note changed. The decision register, owner acceptance
supplement, original owner packet/CI attribution, retention/configuration records
and application/schema/test/configuration paths match baseline `e0a0066`.

Changed files: `l01a-report-route-target-contract.md`, `backlog.md`, `README.md`
and `policy-proposals-d04-d05.md`, all under `docs/remediation/`. The planned
negative/race cases remain NOT RUN; no DB/browser suite, PHP handler, live
resource, mail or deployment action was executed. Documentation completion is
not approval of engineering proposals or clearance of the listed gates.
