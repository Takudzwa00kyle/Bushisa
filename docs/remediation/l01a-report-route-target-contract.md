# L01a: report-route and target-authorization contract

Design revision: 2026-10-16, Africa/Harare. Original source inspection capture:
2026-10-15T22:02:14Z / 2026-10-16 00:02:14 Africa/Harare (UTC+02:00).
This is a documentation timestamp, not a policy acceptance timestamp.

**Current L01b continuation:** the separately authorized text/API implementation now has guarded local evidence. See [L01b implementation and exact A01–A45 status](l01b-implementation-evidence-2026-10-16.md). Qualified review, real MFA/staffing and the remaining release gates stay open. The historical L01a source map and documentation-only statements below describe their captured checkpoint, not current runtime evidence.

Historical owner-decision continuation baseline: `237b7d1b5ebf88b7adb067c4c897fe51a72ed816`,
on `codex/prompt03-owner-decisions-20261014`, initially clean and four local
commits ahead of its tracking branch. The original route map was captured at `e0a0066`;
relevant source paths are unchanged at this continuation baseline. No applicable AGENTS.md was found in the checkout
or parent folders. Source files were read, not executed; no HTTP, database,
mail, production configuration, migration, merge, push or deployment action.

**Design status: ready for review.** Independent review/sign-off has not occurred.
The bounded L01b slice is implemented locally; remaining acceptance and independent review are recorded in the linked evidence.
The requirements below trace existing product approvals. Wire vocabulary,
idempotency/proof storage and the additive implementation slice are **engineering
proposals for review**, not new product approvals. Current locally implemented
engineering choices are recorded in L01b evidence; production acceptance remains open.
Takudzwa Hondova remains the product reviewer. Named safety/privacy/security/
engineering operators and backups remain UNASSIGNED. L01a originally changed
no approval status. Later Decision 27 and the D11 temporary-unset amendment
below record explicit owner changes. Exact historical acceptance timestamps
were not captured and remain unavailable; no drafting/inspection time is
substituted and no already-approved choice is reopened. Qualified-review gates
stay open. The historical documentation continuation recorded P1-P6 in the decision register,
contract and backlog only; that checkpoint performed no implementation or runtime acceptance.
The [P1-P6 approval record](decisions.md#p1-p6-explicit-owner-decisions) now
governs the previously unresolved/proposed treatment of those choices.

## Requirements and independent boundaries

| Authority | Required behavior carried into L01a |
|---|---|
| [Register](decisions.md), Q01/Q02 in the [acceptance supplement](owner-policy-reconciliation-2026-10-15.md) | Authenticated reporting without block, after unmatch and onto an ordinary block; approved target families; minimal input and <=2,000-character explanatory text; text-only reporting works. Reporting never grants contact or private-content access. |
| Q03/Q04/Q19 | Allegation differs from finding; durable sanctions require human review; personal block belongs to its creator. Assigned-case access, controlled exceptions, redacted disclosure and independent appeals are separate permissions. |
| D04 Decisions 4 and 23-33; P1-P6 | Accepted authorized non-self reports with clear attribution trigger fixed 168-hour probation; new qualifying reports received after expiry start a fresh period, with no active-period extension or early restoration. Report-attached ordinary blocks need reviewer clearance plus creator choice. Self-content primary reports are rejected, with separate human recovery policy. Severity bans require human findings; service targets are conditionally adopted with staffing gates open. Combined-action request clearing and earlier unblock/chat effects remain. |
| D04 Decisions 13/19-22/27; Q06-Q09 | Approved chat survives unmatch; pending request keeps original expiry but approval waits for matched state. Reporting must not require undo, new likes, renewed chat approval or resuming discovery. |
| D04 Decisions 15-17; Q10/Q18/Q20 | No new capture of erased unsent originals; already-preserved existing-report evidence is separate. Capture only relevant available evidence; context maximum is necessary 10 preceding and 10 following messages. Item-scoped holds do not preserve entire chats/accounts. |
| Q17-Q27 and [retention matrix](data-inventory-retention-matrix.md) | Separate filing permission, ordinary read permission and assigned evidence access; enforce original expiry despite unpurged rows. Final-resolution/appeal/hold clocks and independent recovery protections remain unchanged. |

The two previously pending conflicts were expressly resolved by the owner in
[the amendment record](owner-conflict-decisions-2026-10-16.md). Neither report
creation nor duplicate handling itself restores a match or configures mail:

| Resolved conflict | Earlier approval | Latest explicit owner selection |
|---|---|---|
| Renewed consent: resolved | Decision 20 formerly allowed restoration without renewed agreement; Decisions 21-22 control own unmatch/undo. | Owner selected replacement. Decision 27 requires fresh affirmative agreement from both participants; either may refuse. Own-unmatch control, both own undos where applicable and the separate one-success/rolling-24h cap remain. |
| Reply-To: resolved | D11 formerly required matching Reply-To without a temporary-unset exception. | Owner permits temporary unset Reply-To until a real monitored receiving inbox is verified. `support@bushisa.me`, its operator and actual inbox/header evidence remain unverified; no live mail setting changes. |

Report-only actions do not send acknowledgment email in this contract, so the
permitted temporary-unset policy does not delay an in-app receipt design. This does not waive
the separate monitored support/privacy-contact or staffing release gates.

## Existing route and caller map

Historical source map at e0a0066. The [L01b review](l01b-implementation-evidence-2026-10-16.md#engineering-review) records the implemented replacements; do not treat this old route observation as current test evidence.

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
cannot depend on them. P4 now rejects own-content primary reports while
permitting relevant authorized own messages as supporting context. Compromise
or disputed authorship uses a separate human-reviewed support/recovery route.

## Target proof and content availability

This is the complete proposed engineering contract for review, not an existing
proof store or a new retention approval. A target ID selects a candidate; it
never establishes permission. Profile/item filing uses a context from an actual
authorized response or retained action; the report lookup itself cannot mint
synthetic prior-access proof from a guessed ID. Separate these server decisions:

- **Filing proof:** this actor has a legitimate current or retained historical
  relationship to this exact target/event/version.
- **Capture permission:** particular still-retained material may be copied
  privately into this case, within its original lifecycle and scope.
- **Ordinary read / case view:** independently authorized through the ordinary
  route or Q19 case assignment. Successful filing grants neither permission.

### Proof sources by target

| Target | Current proof the server may establish | Historical proof and deletion outcome |
|---|---|---|
| Account/person | Reuse the approved profile/discovery visibility predicate for this actor before issuing a report context. Resolve only the permitted profile projection; a raw user lookup or a search hit in another person's session is insufficient. | A retained own pair/request event, or an actor-bound record of an authorized profile response, identifies the encountered account privately. After unmatch/block, use that record without fetching the profile again. After deletion, use only a still-permitted protected reference; no old profile, new identity lookup or restored account. |
| Individual message | Scope metadata lookup to original conversation participants and the correct immutable conversation segment. Then require the message's authorized current availability, or an actor-bound record that this exact version was made available to that actor. Merely sharing a match ID does not prove exposure to every message. | A prior authorized response record identifies the message/version/segment even if the pair is now blocked/unmatched. Only that previously available version may be captured if still permitted; later edits or later messages are not included. Unsend, source deletion or elapsed expiry forbids new original capture even when an unpurged row exists; retain only an authorized metadata/text allegation. |
| Match/request behavior | An immutable server event records the reporter as the affected match participant, sender or recipient, with pair and event/generation. A received request event can be reported without ever approving chat. | Use the retained event, not the mutable row's current values or a new rematch sharing its ID. Terminal/blocked/unmatched events remain reportable while their authorized record survives. Once it is gone, a different valid own pair/person context can support that target instead, never an invented reconstruction of the missing event. |
| Confession | Apply the same live visibility/deletion predicate as the confession reader for this actor and issue context for the specific post/version actually included in the authorized response. Public visibility does not reveal its private author. | Prior actor-bound response proof is required for a now-hidden item. If its original has been erased, accept only the proven encounter and reporter's account of it while proof remains valid. Do not read a backup, old generated preview or retained case snapshot as a new source. |
| Confession reference/quote | Verify the actual reference row, its quoting post/version and the reporter's legitimate access to that quote. A reference context names the reference and parent; it is not just the referenced source confession's ID. | Prior proof concerns the actual displayed quote/reference. Any source is separately checked for lawful availability and scope; lack of source access does not invalidate reporting the quote. Erased source text and its author remain unavailable. Deletion of both leaves only a report supported by still-valid encounter metadata. |

The proposed account/message/post-parent mapping for legacy photo input adds no
new family. Conversation input can map only to a verified pair/event, never all
messages. Unsupported aliases fail closed. P4 rejects own-content-as-primary
reports; already-authorized own messages can still be necessary bounded context
for a report about another actor.

### Minimal actor-bound report context

A proposed content-free server record contains: a random opaque context ID;
protected reporter reference; canonical target family and protected object/event
reference; object version and conversation/pair generation where relevant;
private subject reference if legitimately known; proof source/action; server
occurrence time; policy version; and immutable proof expiry. No body, excerpt,
raw URL, media path, address, IP, location or questionnaire data is needed.

Only an authorized service writes it, as part of making that object/version
available in a permitted response or recording the actual pair/request action.
Include only the items/versions in that response page, not the whole query or
thread. A successful authorization/response record means the item was made
available; it is not proof that the person read it. Client acknowledgments,
read flags and a submitted context ID cannot create the underlying proof.
Bind every lookup to the authenticated reporter. An opaque ID is a selector,
not a transferable bearer permission; possession by another account grants
nothing. Do not log it or treat its pseudonymous fields as anonymous.

Use existing trustworthy action records where sufficient. The present
message `is_read` flag has no actor/version/time proof, and the GET handler
marks a range of messages, not a specific verified exposure. Existing
`confession_views` is also insufficient: its GET branch inserts the supplied
confession ID before the visible result query, without establishing that this
post was returned. Do not backfill proof from these flags, client IDs, old
screenshots, generic logs or an assumed prior view. New response instrumentation
and immutable event identity are L01b/L02 implementation gaps.

Proof retention is bounded, not a new indefinite access history. Assign
`proof_expires_at` from the earliest applicable approved purpose/category
retention deadline, source expiry and ordinary account-deletion purge deadline.
For example, profile-impression proof cannot outlive Q13's 30-day impression
limit; account deletion cannot create a separate ordinary history beyond Q14.
Where no approved category authorizes a standalone proof record, do not create
one: use an adequate existing record or the fallback below until privacy review
accepts the mapping. Q23's proposed ledger/metadata limits and Q16's proposed
active-chat clocks are not adopted as defaults here. Q18 authorizes only a
necessary, case-scoped proof copy after a legitimate report; it does not
retroactively extend an unrelated exposure log. Reads/retries do not renew
proof. L03 applies erasure, scoped holds and independent restore protection.

### Resolver outcomes and no-proof fallback

The resolver returns one internal result, never a raw content object to intake:

| Internal result | Required handling |
|---|---|
| Proven target, original capturable | Create/link the report; copy only the proven available version and necessary authorized context into private evidence. A block may deny ordinary reads while permitting this narrowly scoped historical report capture. |
| Proven target, original not capturable | Create/link a metadata-only report with the reporter's own explanation. Record privately that no original was captured; do not reveal whether it was deleted, expired or held in another case. |
| Target not proven, another own pair/person target is proven | Return the common unavailable response for the submitted target. Offer the general option to report an interaction separately, with explicit selection of the valid own context; never silently retarget or copy the submitted private item. |
| No adequate proof | Common unavailable result, no case/evidence/probation change. Do not confirm existence or infer that the allegation is false. An assisted reporting route for accounts unable to authenticate is separately gated. |

Reporter-entered recollections/quotes are allegations, not authenticated source
copies or proof of access. They cannot trigger a raw-content fetch or serve as
an excuse to recreate an erased original. No image, arbitrary URL or filesystem
path is accepted by the text-only design.

## Reporter authorization and state handling

**Required:** actor comes from the authenticated session, checked against current
account/session state; mutating requests need CSRF. A personal block, unmatch or
discovery pause is not a reason to require contact restoration before a safety
report. Reporting conveys no exemption from account bans/deletion/session
revocation, and an unused numerical allowance never authorizes a target.

**Engineering proposal:** apply the proof and fallback contract above. Reuse
neither the current active-match chat guard for filing nor a blind raw-by-ID
loader. A valid historical report never makes a source ordinarily readable.

| State at report time | Required handling |
|---|---|
| Eligible actor, no block and no match | Person/visible-public-content report remains possible; private targets still require their own proof. Never create a match/request/chat as a side effect. |
| Unmatched with continued approved chat | Preserve that chat grant under Decision 13, but separately authorize the reported item. Do not count a chat ending or perform undo. |
| Unmatched with a pending request | Report the retained pair/request behavior without approving/cancelling it; original expiry and matched-state approval condition remain. |
| Ordinary block in either direction | Allow authorized historical reporting without contact restoration. Once the report is attached to an existing ordinary block, P3 requires reviewer clearance before that specific block is eligible for removal; clearance alone leaves it blocked and only its creator chooses removal. Preserve other blocks, bans and account restrictions. |
| Explicit Block and report | Combined atomic action records the report/block, clears pending pair requests and preserves review-gated unblock. Ordinary reporting/blocking remain separately available; no fresh approval or rate restoration is inferred. |
| Source erased, deleted, expired or capture-prohibited | Never restore source text/media or links. A metadata-only allegation about a verifiable retained interaction can proceed; an already-preserved report copy remains private to its authorized case. Ordinary read denial from block/unmatch is separately assessed under the historical-capture rules, not treated as permission to reopen contact. |
| Actor cannot authenticate / has revoked session or account restriction | Existing endpoint guards remain. Future appeal/rights/assisted reporting needs named operators and reviewed limited authorization; it does not reactivate the account or relax eligibility. Do not advertise an unverified support address. |

P3 / Decision 30 resolves the prior ordinary-block transition: attaching a
report requires reviewer clearance before that block becomes eligible for
removal. Model the exact existing block and authorized report linkage, not a
blanket pair unblock. Clearance cannot remove a block, supply the creator's
choice or override an independent restriction. Replayed old attachments cannot
reapply a cleared gate; new reports need their own current clearance scope.
This does not create a new block or clear pending requests merely by reporting;
the separately approved combined action keeps its own request-clearing effects.
Eligible creator unblock keeps the separately approved immediate-chat effects
under Decisions 10-12/25-26, subject to independent restrictions; attachment or
clearance alone does not grant chat. Implementation/clearance-authority evidence
remains pending.

### Authorization sequence

1. Authenticate the current account/session and CSRF; reject removed/revoked
   authority. Validate target shape and enabled family without looking up
   private content. Use parameterized, actor-scoped selectors, never a submitted
   reporter, subject, table/column name or evidence path.
2. Bind a completed retry to its original actor and operation as specified
   below; it needs no source reload. For a new operation, resolve only minimal
   target/relationship/version metadata through the scoped proof predicate.
3. Recheck proof, current actor status, deletion/expiry and capture permission
   under the shared transaction locks. Canonical subject comes only from that
   permitted metadata; a missing retained subject stays unknown and cannot
   receive a probation/sanction effect through guessed attribution.
4. Pass only an internal authorized-target descriptor to capture. Each context
   item and attachment has its own membership, version, lifecycle and scope
   check. A valid message ID cannot authorize a different thread, a hidden
   author, a parent photo path or a reference's private source.
5. Project an allowlisted own receipt after commit. Filing does not invoke
   read/receipt marking, profile lookup, discovery refresh, match restoration,
   chat grant/restart, unblock or notification to the reported person.
   An attached-block selector must resolve the exact existing block for this
   authorized interaction; it cannot select an unrelated block or impersonate
   its creator. Explicit combined blocking and P3's review-clearance gate are
   the only approved block-state changes here; neither
   grants contact by itself.

Denials for nonexistent, someone else's, expired and unsupported private targets
share the same outward shape/status without body, identity, counts or lifecycle
clues. Do not turn logs, previews, redirects, timing or batch responses into a
second lookup channel. Own-case/addendum selectors are actor-scoped too;
reported-person disclosure and assigned evidence views remain separate Q19
operations, never a permission inherited from an input case ID.

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
| Add information / retry | Proposed `report_addendum` resolves an own opaque receipt and authorizes any new item. A retry keeps its original operation key and cannot become an addendum. See the complete idempotency/incident contract below. |
| Own receipt/status | Proposed POST `report_receipt` is authenticated/CSRF-checked and actor-scoped, with no target-body reload. It returns only a retained own submission/safe outcome projection, not an internal case/evidence record. No such operation currently exists. |
| Evidence / decision / assignment / export | Separate case-scoped operations; no permission merely from owning a report, being its subject, having cloud/DB admin access or providing a reason string. |

Receipt allowlist proposal: own opaque receipt reference, own submission ID/time,
received/pending status and a safe reviewed summary. Own submitted text may be
shown subject to its lifecycle. Never echo source bodies/context, hidden authors,
private IDs/paths, snapshot hashes, assignments, other reporters or media URLs.
Any later allegation/decision notice uses Q19 redaction and review; ordinary
filing does not reveal the reporter to the other person.

Malformed input may get a validation response, but nonexistent, unauthorized,
expired/erased and out-of-scope private targets use a common outward unavailable
result without existence, lifecycle or author details. Proposed HTTP vocabulary:
400 invalid shape, 401 invalid session, 403 invalid CSRF, 404 unavailable
target/case, 409 conflicting retry, 429 separately approved abuse control, generic
5xx failure (including retryable contention). These
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
   the entire chat. Order by server sent-time then ID within that segment; use
   the nearest necessary authorized items on each side, never a receipt/ID alone
   as proof for the other context items. Wider scope needs documented
   justification and authorized hold.
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

Q02 permits additional information in the same case without repeated sanctions.
It does not make every report against one person the same incident. The following
idempotency/incident model is the proposed engineering design, not new policy.

### Operation identity and storage

Use the same shared service behind POST `api/safety.php`, with proposed actions
`report`, `block_and_report`, `report_addendum` and `report_receipt`. The last
is a read-only own-receipt operation despite using POST; all require current
authentication/CSRF. No student action exposes a raw internal case/evidence ID.
An addendum names an own opaque receipt plus authorized new target context if
it introduces another item. New material must pass that item's proof checks.
For a broad account allegation without an immutable incident anchor, a create
request must explicitly choose a new incident; the server allocates its incident
ID from that operation. Additional information uses `report_addendum` instead.
No client can select an internal case/incident or make a new incident by merely
changing an accused account ID.

Every mutating submission carries one random 256-bit idempotency key, created
for that deliberate intent and reused by the client after timeout/lost response.
A new intentional submission uses a new key. Store a keyed digest of the key;
uniqueness is `(reporter, key digest)` across all mutating report operations.
Store operation kind and contract version with the record, not as a way to
create a second key namespace. Changing operation or target with the same key
must conflict, not become a second report. A key is never authentication.

Store an operation ID, protected actor, operation/contract version, keyed request
fingerprint and key version, completion/outcome reference, original UTC time
and immutable expiry. Fingerprint the validated intent: operation kind, target
family/ID, context/event/version selectors, own receipt if an addendum, reason,
exact explanation, explicit new-incident intent, attached-block selector and
combined-action choice.
Reject unknown authority fields, invalid UTF-8 and oversize input; normalize line endings to LF and
field ordering, without case-folding or silently trimming the explanation.
Exclude CSRF/session tokens and server-generated times/subject/case IDs.
The 2,000-character limit counts Unicode code points after line normalization.
Fingerprinting is deterministic across form/JSON transports and protected as
potentially personal data; it must not leak through receipts/logs.
Keep key-digest lookup stable across key rotation and deployment: retain
lookup support for every unexpired key version or rekey atomically. Compare
retries using their stored contract version; an unknown/unsupported old version
is unavailable, never a fresh operation. Rotation or a version rollout must not
turn a retry into a new case/effect.

Completion stores only a reference to the actor's safe receipt projection,
not a serialized source response or a second copy of submission text. The
idempotency guarantee lasts through the linked submission's permitted retention,
with an explicit returned operation expiry. No new numerical retention is
approved: content-dependent fingerprints/proof copies expire no later than
their Q18 submission/evidence category (including the shorter accidental-case
rule), subject to scoped holds. Content-free decision/audit fields cannot be
used to hide a longer-lived explanation fingerprint. Privacy review must accept
the field/category mapping before storage is enabled. An expired retained key
is unavailable, never an instruction to create again. Once records are purged,
there is no indefinite replay promise: a key alone cannot recreate anything;
a deliberately new report still requires a currently valid authorized context.

### Retry and incident classification

| Situation | Transaction result and case/effect boundary |
|---|---|
| Same actor/operation/key, same fingerprint | Return the original own receipt after current account/ownership/lifecycle checks. Do not resolve or recapture the source, write an addendum, re-block someone, repeat a qualifying effect or send a new notification. If receipt content has expired, redact it or return unavailable rather than restoring an old response. |
| Same scoped key, changed fingerprint | Conflict (proposed 409) without target lookup, case creation or other effects. Key changes cannot select another person's case. |
| New key, same reporter and same immutable incident anchor | Reuse that reporter's case/receipt. Identical information creates no repeated evidence/effect; changed information becomes an attributed addendum. For an item, the anchor is canonical family/object/version; for behavior it is the immutable pair/request event, not merely the current match row or account. A unique reporter/anchor link serializes concurrent first reports with different keys. |
| Explicit addendum to an own case | Add a new submission once; retain its provenance and only newly authorized evidence. Keep original evidence, notice, appeal and resolution clocks. A resolved case does not reopen automatically; genuinely new material is routed for reviewed reopening. Do not accept content beyond the case's permitted content deadline merely because decision metadata survives. |
| Distinct event/version against the same person | Independent report/incident; do not reject it because another pending report names that person. For broad account reports with no single immutable event, require an explicit new-incident intent or own-case addendum; free-text similarity/person ID alone cannot prove duplication. Human review can link genuine duplicates later, without multiplying effects. |
| Different reporters, even the same incident | Independent submissions and private receipt references. No automatic cross-reporter case merge. Assigned reviewers may cluster related cases internally; clustering never joins viewer permissions, exposes reporter counts/IDs or makes allegations findings. |
| Another reporter's case/receipt, forged context or unknown actor | Common unavailable/authentication result; no new case, evidence, addendum or effect. Do not disclose whether that other case exists. |

Within one proven incident, compare normalized reason/explanation and authorized
evidence item/version references for identical information, excluding transport
keys or equivalent proof-context IDs. A protected case-scoped information
fingerprint and unique case/fingerprint link serialize that check; they share
the submission's expiry, never a global text history. Do not re-copy an existing
snapshot. An addendum item already anchored to another own case is linked for
review, not silently reassigned or used to extend either case's evidence life.

The client must retain its key until an outcome is known. The server cannot
infer whether different keys and an unanchored narrative represent a retry or
a genuinely separate incident. Preserve the independent allegation for human
classification rather than inventing a same-person/time/text rule or dropping
it. Duplicate classification never creates another qualifying event or durable
sanction; genuinely new accepted reports follow P1/P2 below, even within an
existing case.
Internal duplicate links keep each original expiry and redacted access scope;
copying into a newer case cannot extend evidence life.

### Atomic commit and concurrent writers

1. Authenticate and validate shape; compute the fingerprint without fetching
   a target body. Claim the unique operation key inside one database transaction.
   A concurrent same-key caller waits for that outcome, or receives a generic
   retryable response with no effect if its bounded wait fails. No success is
   returned for uncommitted work.
2. For a completed same-key operation, verify fingerprint, current actor/receipt
   authority and expiry, then return its safe projection. Never repeat the
   combined block action after the blocker later chose to unblock.
3. For a new operation, lock affected account IDs in ascending order, then the
   canonical pair (if any), source container and item/event in stable ID order,
   then the reporter/incident link and case. Probation state is protected by
   the already-locked subject account. All source erasure/unmatch/block
   writers must follow the same shared ordering; L02/L03 alignment is mandatory.
   An untrusted subject cannot choose these locks: only scoped metadata resolves
   it. Recheck actor status, target proof, version and expiry under those locks.
4. At the final locked authorization/capture decision, use server UTC time;
   new access/capture at or after its deadline is denied even if cleanup has
   not run. This is the report's linearization point against unsend/deletion.
   Report first may copy a permitted original into the case; erasure first
   leaves only the proven metadata allegation. No delayed retry can backdate it.
5. Create/reuse the reporter's incident case, submission and necessary bounded
   evidence. Commit the operation outcome, content-free audit and any once-only
   approved qualifying event together. For an explicit combined action, commit
   the block, pending-pair-request clearance and review-before-unblock state in
   that transaction too. For a report attached to an existing ordinary block,
   atomically link the case and apply P3's reviewer-clearance requirement.
   Clearance and creator removal are separate guarded actions. Report-only/
   addendum operations do not otherwise change blocks, clear pending requests,
   restore matches/chat grants, reset likes or alter request-budget counters.
6. A constraint failure/deadlock rolls back all new records/effects. Retry the
   whole operation with the same key and fresh authorization; an existing block
   remains. No orphan case/snapshot or success receipt may survive a rollback.
   Ordinary blocking stays separately available if combined reporting fails.
   External work uses a uniquely keyed post-commit job, never a pre-commit mail
   or notification. The text-only slice sends no acknowledgment mail.

Text snapshots and audit rows can be committed with the database state. A media
pointer/job is not proof of a durable private evidence copy. Any later media
capture needs a scoped, authorized object version, durable-copy confirmation,
tracked failure/cleanup and original deadlines; a queued job does not qualify
as already-preserved evidence when unsend/deletion wins. Never fetch arbitrary
URLs or keep the original pending forever. Upload activation remains gated.

### Accepted reports, attribution and fixed probation

P1/P2 are now approved as D04 Decisions 28-29 in the
[register](decisions.md#p1-p6-explicit-owner-decisions). Every new accepted,
authorized, non-self report with unambiguous attribution qualifies for the
already-approved reduced sending allowance. Validated means valid submission
and attribution; the allegation need not be proven. A reason code or client
subject ID alone still cannot establish authorization/attribution. Bans remain
separate, human-reviewed findings under Decision 32.

| Target | Server-verified reported actor |
|---|---|
| Message | The actual message author, from its authorized object/version metadata. |
| Confession | The actual confession author, retained privately; no deanonymization in the receipt. |
| Reference/quote | The author of the actual reference/quoting content being reported, not automatically the original source author. |
| Account | The reported account identified through authorized account context. |
| Match/request behavior | The particular participant whose conduct the submission identifies, verified against the legitimate pair/event. A participant selector is checked against server metadata, never accepted as arbitrary attribution; never penalize both participants automatically. |

If filing proof is valid but attribution is ambiguous, accept the case for human
review without guessing an actor or automatically assigning probation. A missing
source body is not itself ambiguous attribution when trustworthy retained
metadata identifies the author. Known self-content is rejected as a primary
ordinary report under P4. Disputed authorship uses the separate human route;
it does not create automatic self-probation. Missing filing authorization still
uses the common unavailable result; accepting attribution ambiguity is no
permission to probe inaccessible content.

Commit the immutable accepted-report event, its original server receipt time,
case link and once-only qualifying effect atomically with serialized subject
probation state. Use the report's server-recorded receipt for renewal eligibility,
never a later retry, worker/dispatch timestamp or client clock. Keep the approved
UTC activation clock: each period ends exactly 168 hours after its activation,
with 3 requests/24h and all independent restrictions still enforced. Engineering
review must verify receipt capture/order and delayed-processing behavior; this
is no new midnight-reset or retention rule.

| Subject state / event | Required effect |
|---|---|
| First accepted authorized non-self report with clear attribution | Activate one fixed seven-day period. Simultaneous reports serialize; once a period is active, later reports cannot create competing or extended periods. |
| New report received during the active period | Keep its original start/end and request counters. No restart, extension or early restoration, including dismissal/appeal. A processing delay past expiry does not turn that original receipt into post-expiry renewal. |
| A previously accepted event or deduplicated retry replayed after expiry | No new period; replay the safe receipt only. A new key or case link cannot make an old report new. |
| New qualifying report received at/after the previous expiry | Start a fresh fixed seven-day period once; do not reuse/revive the previous start/end. Equality is outside the expired period under the unchanged expiry-boundary rule. Concurrent post-expiry reports serialize into one new active period. |
| Authorized case with ambiguous attribution | Accept for human review, no automatic probation to a guessed actor. A later reviewed correction/attribution must have an explicit audited event; never invent a past activation or infer guilt. |
| Invalid/unproven target or known own-content primary | No ordinary report/probation effect; preserve legitimate supporting-context and separate recovery options. |

A genuine new qualifying report can belong to an existing case. Reusing a case
is not sufficient reason to suppress it, and a new transport key/case is not
proof of a new report. Retries, identical-information duplicates and addenda to
an already accepted report retain that report's original event; they cannot
renew probation. L01b must distinguish a new accepted report/event from an
informational update without redefining P1/P2 or multiplying sanctions.

Normal allowance returns at each original period expiry unless a new qualifying
receipt activates another period, always subject to other approved limits.
No lifetime pair count, decline, chat-ending cutoff, account restriction or
consent state resets. Do not infer a clean history from missing protective
records; L03 replay/continuity and reviewed category retention remain gates.
No new numerical report-rate limit or request budget is adopted here.

### Human-reviewed compromise and disputed authorship

P4 / Decision 31 rejects own-content primary reports, while allowing necessary
authorized own messages as supporting context about another actor. Reporting
someone else's impersonation or quotation targets that other actual account/
content; it cannot assign an accused identity chosen by the client.

Provide a separate human-reviewed support/recovery route for compromised
accounts or disputed authorship. Its restricted identity/authority proof,
reviewer assignment, safe notices and any recovery action need engineering,
security/privacy review and a real monitored contact/operator. Do not invent
an inbox or identity-document requirement, relax ordinary session guards,
automatically reactivate an account or apply self-probation through this route.
This route is approved policy but not an existing implemented/staffed endpoint.

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

## Acceptance-test matrix for later execution

At the f7e9f8c documentation checkpoint every row was NOT RUN. The separately
authorized L01b continuation now records PASS, PARTIAL and NOT RUN by exact ID
in the [current execution table](l01b-implementation-evidence-2026-10-16.md#a01a45-current-status).
The fixtures/observations below remain the complete acceptance requirements;
partial evidence never closes an unexecuted variant or qualified review gate.
A and B are original participants, C an outsider, R1/R2 separate reporters.

| ID | Fixture / operation | Acceptance observation |
|---|---|---|
| A01 | A reports an account from its actually returned authorized profile card, never matched | Own receipt, canonical subject privately derived; no new like/match/chat, private profile detail or disclosure of reporter identity. |
| A02 | A reports B's message with correct current response proof/segment | One case/submission, only authorized version and necessary private evidence; receipt contains no message body/media/context. |
| A03 | A reports an inbound pending/terminal request event, including while unmatched | Correct immutable event accepted without chat approval; original status/expiry, pair counts and restart restrictions unchanged. |
| A04 | A reports a legitimately returned confession | Accepted without exposing its private author, account link or extra post data; global flag/finding not set by filing. |
| A05 | A reports a displayed reference whose source is inaccessible/erased | Actual reference/quoting parent accepted; no source resurrection, hidden author or source preview. |
| A06 | A unmatches B with approved chat, then reports retained content/event | Valid historical report; Decision 13 chat stays as independently authorized, with no undo or new grant/ending. |
| A07 | A reports after an ordinary block in each direction, with valid pre-block proof | Own receipt; block remains, ordinary read stays denied, no profile/contact reopening or post-block content capture. If attached to that block, P3 requires reviewer clearance before creator-only removal. |
| A08 | Original unsent/deleted, valid proof still within authorized retention | Metadata/own-allegation report only; no original/attachment/preview lookup or erased text in receipt/logs. |
| A09 | Private item unprovable, but A has a valid own pair context | Item gets common unavailable; only a separately selected pair/person report may proceed. No silent retarget or unverifiable item snapshot. |
| A10 | Missing CSRF, stale/revoked session, banned/deleted actor; removed reviewer assignment/MFA | Required action denied; no new case/evidence/effect or evidence disclosure. Disabled UI cannot bypass server authorization. |
| A11 | C guesses real private message/pair/request ID; compare nonexistent/deleted ID | Same unavailable shape/status, no existence/author/timing-count disclosure or stored report/evidence. |
| A12 | C submits A's valid context/receipt or forged reporter/subject fields | No transfer of proof/case access; actor and subject derive from authorized server data, never submitted attribution. |
| A13 | Valid item with wrong conversation generation/version, quote parent or source ID | Denied or original-unavailable outcome based only on valid own proof; never capture another segment/parent/source. |
| A14 | A submits another reporter's case/receipt to add/read information | Same unavailable result as absent case; no other submission, snapshot, reporter ID or case-count leak. |
| A15 | Raw visible-object ID without a legitimate response/action context; legacy read/view flag only | No synthetic prior-view proof from the report lookup or unvalidated confession-view row; no private capture. |
| A16 | Exact content version changed since A's proof | Never capture the later version through old proof; use permitted old version only if it legitimately survives, otherwise metadata alone. |
| A17 | At proof/source expiry, including unpurged rows and delayed jobs | At `t >= proof_expires_at` proof is unusable; expired original never captured. Only a separately valid own context permits a fallback. |
| A18 | Another case already holds the erased original | New filing cannot select/copy that case's evidence or extend its expiry. Existing assigned-case access remains independently scoped. |
| A19 | Report overlaps unsend/account deletion; exercise both serial orders | Report-first authorized evidence or erasure-first absence; no torn/untracked snapshot, retrospective hold, content resurrection or lost protective action. |
| A20 | Block/unmatch/actor revocation overlaps proof issuance and filing | Shared locked decision observes one authorized ordering; context covers only actually permitted items. Filing itself never restores access. |
| A21 | Relevant context crosses segments, has >10 preceding/following items, or new arrivals | At most necessary 10 before/10 after, deterministic sent-time/ID order in the same segment and authorized exposure scope; no unrelated or future-message subscription. |
| A22 | Invalid/oversize explanation, forged reason, unsupported alias/self-primary, image/path/URL authority input | Validate without truncation/fetch; reject known own-content primary under P4 while allowing relevant authorized own context and separate human recovery. Unsupported aliases stay gated. |
| A23 | Same key/payload retried after response loss, source erasure, key rotation or contract rollout | Same retained safe receipt; one operation/submission/case/effect, no source reload or new retention. |
| A24 | Concurrent same-key first submissions, plus retry after rollback/deadlock | One committed outcome; wait/retry never returns uncommitted success. Failed transaction leaves no orphan case/snapshot/effect. |
| A25 | Same scoped key with changed operation kind, target, reason or explanation | 409 conflict before private lookup; no additional write/effect or existence detail. Different transport field ordering/CSRF token alone still matches. |
| A26 | Different keys concurrently report the same immutable incident for A | One reporter/anchor case; identical information not copied, changed information attributed once per new intent; no repeated qualifying effect. |
| A27 | Same person, distinct request events/segments or explicit unanchored new incident | Independent allegations retained; legacy pending-user dedupe does not discard them. Similar text/person ID alone does not merge them. |
| A28 | R1/R2 independently report the same item during simultaneous initial qualification | Private independent receipts/cases, no reporter/count disclosure. Authorized non-self reports with clear attribution qualify under P1, serialized into one fixed active period. |
| A29 | Own-case addendum includes another item or arrives after resolution | New item requires separate proof; no automatic reopening, renewed old evidence/appeal deadline or content retention past its category. Expired content cannot be restored from the receipt. |
| A30 | Retried receipt after account revocation, evidence purge or operation expiry | Current authority/lifecycle wins; redacted own projection or common unavailable, never an old cached sensitive response. A key alone recreates nothing. |
| A31 | Accepted authorized non-self report with unambiguous attribution, plus concurrent retries | Exactly one qualifying event/effect; immutable UTC activation/end separated by 168h; approved 3/24h allowance and all other limits preserved. No finding of guilt is required for probation or inferred for a ban. |
| A32 | Further reports/addenda/dismissal/appeal during active probation | Original start/end and consumed request budgets unchanged; no extension, restart or early restoration. |
| A33 | Old trigger replay and new qualifying report after probation expiry | Old event/duplicates never reactivate. A genuinely new qualifying report received after expiry starts a fresh seven-day period under P2, without early restoration, lifetime resets or overriding other restrictions. |
| A34 | Successful combined action retried after a later legitimate unblock | Replay its own receipt only: no new block, cleared-request replay, chat grant or source recapture. Initial combined action applies Decisions 23-26 atomically. |
| A35 | Combined action fails at each write/capture boundary | All new combined effects roll back, pre-existing blocks remain, no misleading success; ordinary blocking remains independently usable. |
| A36 | Evidence hold/release, read/export/retry and restored stale copies | Original deadlines/assignment still apply; no clock reset or ordinary resurrection. Verify scoped replay/erasure separately with L03; no claim that an actual restore or 005 production application occurred. |

| A37 | Parameterize valid authorized non-self reports across all five target families, without a proven allegation | Accepted report triggers the actual author/account/specific conduct participant's probation once. Reference author differs from source author where applicable; never penalize both match participants. |
| A38 | Valid target proof, ambiguous/deleted attribution or an unknown reference author | Accept for human review without guessed probation or newly disclosed private identity; missing attribution is distinct from missing filing authorization. |
| A39 | Known own-content primary; own-message context about another actor; compromise/disputed authorship | Reject self-primary, permit only relevant authorized supporting context, and route compromise/dispute to separate human review without automatic self-probation. No invented recovery access or inbox. |
| A40 | Qualifying receipts just before, exactly at and after probation expiry; delayed validation/worker and retry | Original receipt controls eligibility: active-period report cannot extend; at/after expiry a genuinely new qualifying receipt starts one fresh activation+168h period. Retry/worker time cannot manufacture renewal. |
| A41 | Attach report to an ordinary block, then attempt removal before clearance, after clearance alone and by the wrong/right creator | Apply review requirement atomically; deny before clearance and without creator choice. Clearance alone leaves the block. Correct creator's eligible removal still cannot override another block, ban or account restriction. |
| A42 | Substantiated moderate first, repeated/serious and severe violation findings; temporary expiry and permanent reversal | Human-reviewed 7d/30d/permanent defaults apply with documented severity/skip reasons; no automatic ban from allegation. Temporary UTC expiry is enforced; own-appeal review is denied and permanent reversal requires a second independent reviewer. |
| A43 | Conditional report/appeal targets with missing versus demonstrated coverage and overdue cases | No staffed/achieved public claim without accepted coverage/capacity/alert evidence. Due/overdue escalation and approved appeal deadlines are verified only in later staffed acceptance; no automatic unblock, ban lift or probation shortening. |
| A44 | Genuine new qualifying report linked to an existing case versus retry/identical information/addendum on an old report | Case reuse cannot suppress a new accepted qualifying event; a new key/case alone cannot create one. Duplicate/update keeps its original event and cannot reactivate probation. No evidence-retention reset. |
| A45 | Two new qualifying reports received concurrently after previous expiry | Serialize subject state into one fresh fixed period, preserve each private report/receipt, and apply the active-period non-extension rule to the other report; no competing or extended periods. |

## P1-P6 owner decisions recorded

The owner has expressly answered all six choices. The authoritative
[register entry](decisions.md#p1-p6-explicit-owner-decisions) records D04 Decisions
28-33. The earlier [48-answer reconciliation](owner-policy-reconciliation-2026-10-15.md)
is retained as the scope of that original message; its proposed/unresolved
labels for these points are superseded only by the approvals below. No P1-P6
policy answer is still awaited, and none is implementation or reviewer sign-off.
Exact acceptance UTC/Harare timestamps were not captured and are not invented.

| Choice / authority | Owner-approved rule | Implementation / review gate |
|---|---|---|
| P1 / Decision 28; Q01-Q03 | Every accepted authorized non-self report with unambiguous actor attribution qualifies; submission/attribution validation is not a finding. Resolve actual author/account/specific conduct participant; accept ambiguous attribution for human review without guessed probation. | Server proof, actor resolver, unknown/self routing and one accepted-report event/effect. |
| P2 / Decision 29; Q01 | A new qualifying report received after expiry starts a fresh seven-day period; active reports never reset/extend it, duplicate retries never renew, no early rate restoration. | Immutable received/event identity, 168h activation clocks, boundary/delay/concurrency/replay tests; budgets and independent restrictions unchanged. |
| P3 / Decision 30; Q01/Q03 | A report attached to an ordinary block requires reviewer clearance before eligibility to remove; only the creator chooses actual removal, with bans/other restrictions independent. | Exact case/block linkage, current clearance scope, atomic attachment and creator-only removal guards; attachment/clearance alone grants no chat, while eligible creator unblock keeps its earlier approved effect. |
| P4 / Decision 31; Q02 | Reject own-content primary reports, allow relevant own-message context about others, and provide separate human-reviewed compromise/disputed-authorship support/recovery without automatic self-probation. Impersonation/quotation targets the other actual account/content. | Restricted recovery authorization, safe identity evidence/attribution, named human reviewers and monitored route; none is operationally verified. |
| P5 / Decision 32; Q04 | Adopt severity-based 7d/30d/permanent bans with documented findings and human review; independent appeal and second independent review for permanent reversal. | Findings/notices/sanction clocks/appeal authority and enforcement tests; real reviewers and Q18 privacy gates remain open. |
| P6 / Decision 33; Q05 | Adopt report/appeal service targets conditionally on demonstrated staffing/coverage; do not advertise present staffing or achievement. Previously approved appeal rights/clocks remain. | Real accepted primary/backup/on-call coverage, monitored queues, overdue escalation and measured capacity; operational readiness open. |

P5 uses the proposed severity defaults exactly: 7d for a substantiated moderate
first violation requiring more than a warning; 30d for repeated/more serious
harassment or deliberate safety-limit evasion; permanent for substantiated severe
exploitation, credible serious threats, persistent ban evasion or repeated serious
abuse after previous sanctions. Severity may skip shorter stages. Temporary bans
expire at the recorded UTC deadline unless separately justified action intervenes.
The initial reviewer cannot decide their own appeal; permanent reversal needs a
second independent reviewer. Ban identifiers and long-lived suppression still
require Q18 privacy review; no indefinite evidence/identity blacklist is approved.

P6's conditional targets are report acknowledgment 24h, credible immediate-danger
triage 1h with actual on-call, ordinary first review 72h and decision 7d, with
one reasoned/notified complex extension to 14d total. Appeal remains available
14d from effective notice, acknowledgment 2d and independent decision 14d from
receipt, with one documented/notified complex extension of 14d. Escalate at the
deadline to safety lead/backup, and urgent overdue cases to the accountable owner.
Overdue review never unblocks, lifts bans or shortens probation. These are not
claims of achieved response or available people, and no public promise is made.

Five target families, the 2,000-character ceiling, evidence/hold/access rules,
Decision 27 and temporary unset Reply-To remain as approved. Wire aliases,
reason-code mapping, normalization, lock/index design, schema and HTTP codes
remain engineering review. A standalone photo family beyond an authorized
parent is outside this slice. No other policy proposal, retention value or
appointment is accepted through P1-P6.

### Engineering and named-reviewer acceptance

| Gate | Remaining engineering work / evidence | Reviewer / acceptance status |
|---|---|---|
| Target proof and actor attribution | Review authorized-response/event proof, actual reference author versus source, selected conduct participant, accepted ambiguous cases, rejected self-primary cases and category/deletion deadlines. Define the restricted recovery route separately. | Engineering/security/privacy/safety reviewers UNASSIGNED; Takudzwa remains product reviewer. |
| Idempotency and transactional effects | Review immutable logical-report identity versus informational update, actor/key uniqueness and rotation, per-reporter incident links, server receipt/activation ordering, simultaneous renewals and once-only effects. Validate source-erasure lock ordering and exact block/clearance linkage with L02/L03. | Named engineering/security/safety review not performed; source/schema compatibility and no-repeat-effect evidence required. |
| Review/recovery and privacy workflows | Review evidence minimization/redaction, limited recovery authority, assignment/MFA, sanctions/notices/independent appeals and permanent-reversal second review. Raise only a specific genuinely new purpose/deadline if current approved categories are inadequate. | Qualified privacy/security/safety and independent appeal reviewers UNASSIGNED; H03/H10 open. |
| Implementation and verification | The separately authorized L01b text/API slice is implemented and locally tested; complete remaining PARTIAL/NOT RUN variants and L02/L03 shared-history/replay integration. | Current results are in the L01b evidence; named independent engineering/security/safety/privacy acceptance remains open. |
| Operations and release | Demonstrate real monitored recovery/support routes, case assignment, primary/backup/on-call coverage, due/overdue alerts and measured report/appeal capacity. Verify cleanup/copy/replay separately. | Owners/reviewers/backups UNASSIGNED; P6 staffing condition and D04/D05/D12/operational release gates remain open. |

**Next bounded implementation task, L02a:** establish immutable pair/request/ending
history and verified legacy backfill, then align already-approved eligible restart
and immediate-chat unblock without clearing independent restrictions or lifetime
cutoffs. Use guarded disposable systems and existing approvals. Human recovery,
full sanctions/appeals, real MFA/staffing and L03 restore replay remain separate
reviewed slices. No production operation follows from this local checkpoint.

## Documentation checks

### Current P1-P6 recording from 237b7d1

PASS: `git diff --check`; 38 local links including ten heading references
across the three affected Markdown files; paired fences/no trailing whitespace;
A01-A45 each present once; six adopted choices mapped once each in register and
contract. Unrelated decision rows and the register's historical body are
unchanged. Original Q01-Q48 reconciliation, owner packet/candidate CI evidence,
retention matrix, policy proposals, geography, retention policy, playbook and
operational-feasibility records remain unchanged; new register approvals govern
only the superseded P1-P6 points in those historical records. No retention
period or unrelated approval/gate is changed.

Changed files: `decisions.md`, `l01a-report-route-target-contract.md` and
`backlog.md`. All 45 acceptance cases remain NOT RUN. No DB/browser suite,
application/migration/configuration, live resource, real mail, merge, push or
deployment was performed. This is author self-validation, not independent
engineering/security/privacy/safety review, staffed coverage or release evidence.

### Historical design checkpoint 237b7d1

The validation below belongs to the design checkpoint before the P1-P6 owner
answers. Its unchanged-register and 36-case statements are historical, not
current acceptance claims after these express approvals.

PASS: `git diff --check`; 31 local links including eight heading references
across the two affected documents; paired code fences and no trailing whitespace;
A01-A36 and P1-P6 each present exactly once. Protected decision register,
Q01-Q48 reconciliation, owner packet/candidate CI attribution, retention matrix,
owner amendment record, policy proposals and geographic document are unchanged
from the continuation baseline. Approved clocks and the conditional 7+35+7=49d
recovery example are preserved; no new proof/idempotency retention is adopted.
Only this contract and backlog change. All matrix cases remain NOT RUN; no
DB/browser suite or live resource was accessed for this documentation task.
This is author self-validation, not independent engineering/safety/privacy
review, implementation evidence or release acceptance.

### Historical original checkpoint

The results below belong to original L01a checkpoint `93314b2`, before the
later owner amendments. Linked policy documents now intentionally reflect those
amendments; this historical baseline comparison is not a fresh unchanged-policy
claim for the later checkpoint.

PASS for the original L01a documentation checkpoint: `git diff --check`; 37 local links and
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
