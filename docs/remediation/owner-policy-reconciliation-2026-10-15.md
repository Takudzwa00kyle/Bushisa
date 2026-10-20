# Owner policy reconciliation: Q01-Q48

Document date: 2026-10-15. This is a documentation checkpoint, not a release.

Latest owner amendments recorded on 2026-10-16, Africa/Harare: both named
conflicts are resolved by express adoption in
[owner-conflict-decisions-2026-10-16.md](owner-conflict-decisions-2026-10-16.md).
Decision 27 replaces only Decision 20's no-renewed-agreement rule; fresh
agreement from both is required. Temporary unset Reply-To is now permitted
until a monitored receiving inbox is verified. Earlier acceptance/validation
capture dates below remain historical and are not timestamps for these new
approvals; their exact actual acceptance time remains CONFIRM.

Reviewer: **Takudzwa Hondova**. Source: the owner's message beginning
`Shared conventions`, containing answers 01-48 and the required documentation
outputs. Direct selections in that message are product approvals only to their
stated extent. `Propose`, `proposed`, `candidate` and `recommend` do not approve
the associated values. Required qualified reviews remain open.

**Actual acceptance timestamp: CONFIRM**, in both UTC and Africa/Harare.
The follow-up answer `Africa/Harare` confirms the display timezone, not a date
or time. The first reconciliation clock capture was 2026-10-15T20:24:13Z /
2026-10-15 22:24:13 Africa/Harare (UTC+02:00); that is a recording timestamp,
**not** an invented acceptance timestamp. Historical approval dates are unchanged.

Starting branch: `codex/prompt03-owner-decisions-20261014`; HEAD:
`6d3ea7b0f884251ce89ae1cef253f81a7232c0f1`. It descends from Prompt 02
`e30dcf844abaf499bcc657172c2c9aa1f4b4986d`. The five pre-existing modified
remediation documents were preserved. No production, application, migration,
DNS, secret, billing, live-database, mail, merge or deployment action is part of
this checkpoint.

## Authority and status

- [Decision register](decisions.md) and its earlier D03 / D04 Decisions 1-26
  remain authoritative. This record adds the accepted portions of Q01-Q48;
  it does not mark an entire D-number approved.
- **Approved** below means the owner's express product selection. It does not
  mean implemented, tested, deployed, legally cleared or operationally staffed.
- **Proposed** requires a later express owner adoption. **CONFIRM** needs real
  owner information; **VERIFY** needs technical evidence; **REVIEW GATE** needs
  qualified review and actual acceptance. None is a fictitious sign-off.
- Product reviewer: Takudzwa Hondova. Engineering, operations, safety lead,
  independent appeal reviewer, privacy/security approvers, backups, billing and
  mail/support operators are **UNASSIGNED**, except where a future accepted
  operations register names them. Suggested appointments in Q35/Q42/Q45/Q48
  are not appointments.
- Every new behavior below needs the implementation and evidence work in the
  [bounded backlog](backlog.md#q01-q48-implementation-backlog). No new behavioral
  PASS result is asserted here. Public service promises require a named owner,
  an overdue alert and demonstrated capacity.

## Reconciliation and missing information

| Point | Existing approval / missing fact | Disposition |
|---|---|---|
| Q08 renewed consent | Decision 20 previously allowed restoration without renewed agreement; Decisions 21-22 control own unmatch/undo. | **Resolved by explicit replacement: D04 Decision 27.** Restoring the existing relationship now requires both participants' fresh agreement; either may refuse. Own-unmatch control, both own undos when both unmatched and the one-success/rolling-24h cap remain. |
| Q03 chat restoration | D04 Decisions 10-12 and 25-26 provide immediate chat on the blocker's qualifying unblock, including never-approved chat; moderator clearance alone does not unblock. | No moderator may supply a user's choice. Preserve the already-approved immediate chat effect after the blocker's action. Any broader reading requiring fresh approval after every unblock is **pending conflict**. |
| Q06 recreated match | Decision 5 clears decline restrictions on a recreated match only; Decisions 9 and 20 do not reset other restrictions or grant extra clearance on undo. | Adopt the clarification: restoring the existing relationship is not a new match; a genuinely new match requires fresh mutual interest and clears decline restrictions only. No pair-budget, probation, block, account or second-ending reset. |
| Q45 Reply-To | D11 previously required matching Reply-To without the temporary-unset exception. | **Resolved by explicit approval:** temporary unset Reply-To is permitted until a real monitored inbox is verified. Selected From/display name are unchanged. `support@bushisa.me`, operator and receiving/header verification remain pending; no live setting is changed. |
| Q22 / Q46 verification | Existing entered-code signup and terminal secret-erasure safeguards; Q46 mentions verification links. | Preserve entered-code approval and existing immediate terminal erasure. A navigation link never verifies signup. Any link-only signup change is **pending conflict**. Q22's 24-hour/one-hour ceilings do not authorize delaying existing immediate erasure; keep keyed HMAC for low-entropy codes, not an unkeyed hash. |
| Q12 / Q13 clocks | Later fresh adult eligibility is not selected. Q13's 11-month warning lacks an elapsed-time definition under Q25. | Keep the rejected attempt permanently non-activatable; a later fresh adult application remains **CONFIRM/REVIEW GATE**. Treat the 12-month inactivity year as 365 elapsed days under Q25; the exact 11-month warning offset is **CONFIRM**, not an invented 330-day value. |
| Acceptance and people | Only the timezone was supplied; no exact acceptance instant, accepted role appointments, independent reviewers, approved recipient list or monitored contact. | **CONFIRM** exact acceptance UTC/Harare time and real personnel/contact/coverage. Unstaffed gates stay open. No further 48-question sequence is required. |
| External inputs | Funding/cap/reserve, current resource locations/versions/quotas, backup inventory, inbound mailbox/DNS and real area catalogue/points are unverified. | **CONFIRM/VERIFY/REVIEW GATE** in the [configuration checklist](configuration-impact-checklist-2026-10-15.md); no guessed prices, coordinates, capabilities or legal conclusions. |

## Shared conventions adopted

New sliding limits use rolling elapsed time, server timestamps, atomic checks
and explicit boundaries. No midnight reset is introduced. Earlier approved
request clocks, seven-day pending expiry, cancellation/expiry 12-hour retry,
directional decline/rematch rules, sender/recipient/pair budgets, fixed
168-hour probation, chat-ending/restart rules, disappearing unsent entry and
original-text deletion remain intact. Another report during active probation
does not restart or extend it and appeals cannot shorten it.

An allowance never overrides an independent block, ban, deletion, eligibility,
authorization or consent restriction. Distinguish **access denial**, **live
erasure** and **protected-backup expiry**. Scoped holds affect specified evidence
only. New expiry timestamps are immutable; jobs, reads, exports, retries,
migration/replay, copies and routine hold reviews cannot renew them. HMACs and
other pseudonyms remain potentially personal data with protected keys, access,
expiry and review. The [inventory and clock contract](data-inventory-retention-matrix.md)
records the category-specific deadlines and conditional recovery arithmetic.

## D04: reporting, safety and relationships

### Q01 Reporting routes

**Approved:** allow reporting without blocking, after unmatching, and onto an
existing ordinary block, without resuming contact or removing that block.
After-unmatch reports concern prior legitimately accessed interactions/content
within retention; they grant no renewed private-content access. Keep ordinary
blocking without an accusation and the combined `Block and report` action.
Reporting alone neither identifies the reporter to the other person nor reopens
contact. Preserve the existing qualifying-report probation and no-extension rule.
**Proposed only:** a qualifying report after probation expiry starts a new
seven-day probation; no new renewal rule is approved. Confirmed violations may
separately support reviewed sanctions. Implementation: L01, L02 below.

### Q02 Report targets and inputs

**Approved:** account/person, individual message, match/request behavior,
confession and confession reference/quote targets. A reference report cannot
recreate an erased original or obtain an inaccessible source. Require an
authenticated reporter, target type/ID, reason and an explanation where the
category is insufficient. Server supplies time, reporter ID, case ID and
relevant interaction IDs. Optional explanation is at most 2,000 characters;
ordinary reporting requires no phone, government ID or extra sensitive history.
Text reporting remains usable. Images stay gated until private validation,
scanning and metadata stripping exist. Merge duplicate information into the
same case without multiplying sanctions; prevent unrelated-message disclosure
and probing. Implementation/evidence: L01; upload activation REVIEW GATE.

### Q03 Conduct, findings and unblocking

**Approved conduct rules:** prohibit threats, stalking, coercion, repeated
unwanted contact, hate-directed abuse, doxxing, impersonation, scams,
exploitation, sexual content sent without consent, public explicit sexual
media, child sexual exploitation material and deliberate safety evasion.
Harmless disagreement, rejection, protected characteristics and a moderator's
personal moral preferences are insufficient punishment grounds. A report is
an allegation; consider evidence, context and severity. Durable sanctions need
human review. Automatic action is limited to the approved probation and narrowly
documented protective measures, not an unspecified automatic-sanction power.
Keep personal blocks separate from platform restrictions; clearance does not
remove a block, and credible unresolved contact risk preserves it. Require the
blocker's choice plus required review clearance. Apply the chat-restoration
reconciliation above, preserving Decisions 10-12 and 25-26. L01; safety review open.

### Q04 Bans

**Approved:** temporary and permanent account bans; a temporary ban has a
recorded UTC expiry unless separately justified action intervenes. Designated
reviewers may lift/reduce through appeal; the original reviewer cannot decide
their own appeal and permanent reversal needs a second independent reviewer.
Permanence means no automatic account reactivation, not perfect identity-level
suppression. **Proposed sanction ladder:** 7 days for a substantiated moderate
first violation needing more than a warning; 30 days for repeated/more serious
harassment or safety evasion; permanent for severe exploitation, credible
serious threats, persistent ban evasion or repeated serious abuse, with severity
allowing skipped stages. These defaults/thresholds are not adopted. Long-lived
identifiers require Q18 privacy approval; probation remains a different measure.
L01/L03; named reviewers and sanction criteria REVIEW GATE.

### Q05 Reviews and appeals

**Proposed report targets:** acknowledgment 24 hours; credible immediate danger
initial triage 1 hour with real on-call coverage; ordinary first review 72
hours; decision 7 days; one complex-case extension to 14 days with reason and
safe notice. **Approved appeal process:** appeal within 14 days of effective
decision notice, acknowledgment within 2 days, independent decision within 14
days of receipt; one documented complex appeal extension of 14 days with notice.
New material evidence may justify documented reopening, not routine repeat
appeals or reset retention. Escalate at the deadline to safety lead/backup,
and urgent overdue cases also to the accountable owner. Overdue cases do not
unblock, lift bans or shorten probation. All public deadlines remain blocked by
UNASSIGNED reviewers/coverage and capacity/alert evidence. L01/L07.

### Q06 Unmatch and new-match definition

**Approved:** ordinary unmatch removes the active discovery-match relationship
and both active like states, keeping minimal safety/lifetime history. Existing
approved chat continues and no chat-ending counter increments. Exclude the pair
from automatic discovery for 7 days after unmatch, then allow independently
eligible rediscovery; deliberate undo may occur earlier under preserved own-undo
rules. Persist each event/expiry; do not restart it on reads. Representation of
overlapping unmatch events requires an explicit technical contract before code.
Undo of the existing relationship is not a recreated match and clears no
decline restriction. Fresh mutual interest creates a genuinely new match;
Decision 5 clears decline restrictions only, never other safety history. L02.

### Q07 New requests while unmatched

**Approved:** require an eligible mutual match for a new request if neither an
approved chat nor a valid pending request exists. Previous contact alone is
insufficient. Preserve Decision 13's continuing approved chat and Decision 19's
pending request with unchanged expiry and approval only once matched again.
Current active-match authorization needs separation by action; no code changed.
L02 and existing Decisions 1-26 alignment remain required.

### Q08 Undo limits

**Approved portion:** immediate undo attempt and at most one successful pair
restoration per rolling 24 hours, atomically enforced. A successful restoration
is the return to matched state, not each unsuccessful own-undo attempt.
**New approved amendment, Decision 27:** fresh affirmative agreement from both
participants is now required; either may refuse. This expressly supersedes
Decision 20's no-renewed-agreement clause. Keep control of each person's own
unmatch and both own undos when both unmatched under Decisions 21-22. An
immediate attempt is not completed restoration without both agreements. The cap never
limits block/report/deletion/consent withdrawal, changes the ended-chat restart
cooldown or resets lifetime limits. L02.

### Q09 Discovery pause

**Approved:** visibility/recommendation pause removes the profile from new
recommendations and clears cached recommendation responses within 5 minutes;
subsequent server responses enforce visibility. Own browsing, eligible matching,
requests, approvals and chat remain available under Decisions 14/18. Pause is
not deletion, ban, location withdrawal or revocation of underlying discovery
participation consent. Prior cards/screenshots cannot be recalled. L02/L04;
cache ownership/latency evidence is a release gate.

### Q10 Unsend projections

**Approved:** preserve the approved 15-minute server-sending-time window,
disappearing entry and original-text deletion except an already-preserved
existing-report copy. Extend removal to attachments/previews with that narrow
exception; do not create new evidence merely on unsend. Deny subsequent app
access immediately and erase ordinary text in the successful operation.
Invalidate media links/cache references; remove tied in-app notifications within
60 seconds. Live attachment originals/thumbnails/previews are erased within
24 hours, subject only to valid scoped evidence preservation. A residual bearer
URL needs a measured bounded lifetime and repair before immediate revocation
can be claimed. No message bodies in push/email; no device/download/screenshot
recall promise and no visible unsent placeholder. L02/L03; backup rules are Q26.

## D05: lifecycle, evidence and privacy

### Q11 Incomplete signup

**Approved:** pending-email accounts expire 72 hours after creation, unaffected
by resends. Approved-email unfinished onboarding expires 7 days after first
successful email approval, unaffected by visits/retries. Deny access at expiry;
complete live cleanup within 24 hours. Keep only allowed short-lived code-free
delivery/rate data. Fresh signup inherits neither verification nor eligibility
bypasses; show expiry/recovery guidance. L03/L06; qualified notice review open.

### Q12 Underage attempts

**Approved:** the rejected account/attempt stays permanently non-activatable;
erase entered profile content, photos, answers and raw DOB within 24 hours.
**Proposed:** retain for 365 days from rejection only rejection ID, necessary
keyed normalized-student/email HMACs, reason, rejection/expiry UTC times and
policy version, limited to eligibility/safety reviewers. No documents, exact
DOB, location or contact history. REVIEW GATE: lawful finite anti-replay purpose,
D01 compatibility, key custody and the unresolved later fresh adult application.
Do not infer indefinite child tracking or expand D02. L03.

### Q13 Account data and inactivity

**Approved:** use fields only for the disclosed account/matching purpose; no
speculative extra fields, sensitive inferences or new health/document data.
Delete replaced values within 24 hours. Keep individual impressions 30 days,
unmatched non-safety like history 90 days, and genuinely non-identifying
suppressed daily aggregates 13 calendar months. A smaller pair-policy ledger
has its own purpose. No identifiable embeddings/collaborative training or new
profiling without separate approval. Intentional authenticated foreground
activity starts the inactivity clock; polling/webhooks/admin reads/jobs do not
reset it. Schedule deletion at the one-year/365-day inactivity deadline through
Q14; warn at 11 months, whose precise elapsed offset remains CONFIRM.
**Candidate field allowlist, pending purpose/consent review:** internal ID;
private normalized student number/email/verification; display name; necessary
private DOB with only age/approved band public; voluntary photo/bio/faculty/year;
explicit gender and partner-age/gender/intention preferences; current disclosed
questionnaire answers; necessary creation/activity/status times. Credentials,
sessions and safety data are separate. Schema mapping is in the inventory. L03.

### Q14 Account deletion

**Approved:** either account's deletion triggers ordinary relationship cleanup.
Immediately disable the account, revoke sessions/tokens, suppress profile and
discovery, prevent interactions, deny affected ordinary history/media, stop
nonessential pending mail and invalidate recovery/verification challenges.
Erase ordinary live account/profile/preferences/answers and affected ordinary
relationship/chat data within 7 days; live media within 24 hours. This is no
public grace period. Keep only separately authorized evidence, consent proof
and narrow enforcement/recovery records on their own clocks. URL changes,
restoration and old sessions cannot restore access; content-free audits record
exceptions/outcomes. Do not delete already-preserved case evidence merely
because an account deletes. L03; D05 specialist acceptance remains open.

### Q15 Confessions

**Approved:** on account deletion immediately remove public confessions and
attribution; live erasure within 7 days, media within 24 hours. No default
anonymous-publication archive. Remove generated references/previews/excerpts;
independent replies remain only without erased content or identifying material,
otherwise redact/remove it. Report snapshots are private evidence. Active
authors can delete their confessions; provide a verified post-deletion route
for missed copies. Legal/abuse evidence is separately reviewed. L03.

### Q16 Active chats

**Proposed schedule:** ordinary messages at most 12 months (one year = 365
elapsed days under Q25) from server sending time, and conversation history
after 90 days without a new human-sent message, whichever deletes earlier.
This linked active-chat schedule remains proposed, not a new production timer.
**Approved safeguards for a selected schedule:** reads/polling/delivery retries
do not reset it; a new message cannot renew older messages' maximum age.
Explain expiry and warn before inactivity cleanup where feasible. History
expiry does not revoke an otherwise valid chat grant; evidence is separate. L03.

### Q17 Genuinely closed chats

**Approved:** explicit approved-chat ending/revocation starts 7 days of read-only
history for the two original participants, subject to their current authority,
blocks/bans/deletion and any earlier applicable expiry. Sending is denied;
legitimately accessible items remain reportable. At the deadline deny ordinary
history and erase live history/media within 24 hours except scoped evidence.
Unmatch alone starts no such clock. Restart neither renews old history nor
recovers erased messages. Separate old and new message segments so old cleanup
cannot erase new-session messages; preserve restart actors/cooldowns/cutoff. L02/L03.

### Q18 Evidence and resolution

**Approved:** capture only reported items, relevant attachments/times/IDs,
necessary safety state and, where necessary, at most 10 preceding and 10
following legitimately available messages. Wider scope needs case justification
and an authorized hold; never resurrect erased originals or copy all chats by
default. Content expires 180 days after final resolution; content-free structured
decisions/reasons/notices/appeal dates/reviewer IDs expire after 365 days.
Wholly unrelated/clearly accidental case content is removed within 30 days of
final resolution, except a justified minimal audit/abuse record. A timely appeal
prevents treating the initial finding as final.
Final resolution is the later of effective decision plus appeal window or final
disposition of a timely appeal. Genuine reopening needs material reason and
a separate documented resolution, never a routine clock reset or recovery of
already-erased evidence. Long-lived active-permanent-ban suppression is
**REVIEW GATE**, requiring explicit privacy approval, minimal case/sanction/HMAC/
start/review fields, annual necessity review and no indefinite content. Remove
that record within 30 days after overturning/lawful enforcement end, then apply
backup expiry. No unrestricted identity blacklist or perfect enforcement claim.
L01/L03.

### Q19 Evidence access and disclosure

**Approved:** assigned reviewers access only assigned cases; safety lead assigns;
privacy/security approver authorizes exceptions/exports. Named separate accounts,
MFA, least privilege and departure revocation are required; cloud/DB admin is
not ordinary evidence permission. Reporter gets own submission/reference/safe
summary; reported person gets enough redacted allegation/rule/finding to appeal.
No absolute anonymity promise against qualified lawful disclosure decisions.
Audit actor, case/item, action, time, purpose and outcome for view/download/
assignment/export/change/deletion, without content, for 365 days from event.
Exports require scoped approval, encryption, named recipient and 24-hour expiry;
no ordinary-moderator bulk download. L01/L03; named approvers REVIEW GATE.

### Q20 Evidence holds

**Approved:** safety lead plus privacy approver jointly authorize case/item/
reason/authority/start/expiry/next-review holds. Assigned reviewer urgent holds
last at most 24 hours before joint authorization is required. Ordinary
authorizations last at most 30 days and are reviewed at least every 14 days;
renewal needs a fresh necessity decision. Legal holds follow verified legal
instructions and controlled review. At release/expiry purge within 24 hours if
ordinary expiry passed, else keep the original expiry. A review/release is no
clock reset; retain minimal content-free recovery/audit instructions. L01/L03.

### Q21 Consent proof

**Approved fields:** subject/account/protected reference, purpose, notice/version,
affirmative grant/withdrawal, UTC time, method, event ID. No coordinates, full IP,
message text or questionnaire answers. Receipts while necessary processing/account
continues, then 365 days after the applicable deletion/processing-end event,
**subject to privacy review** of each purpose and its terminal trigger. Keep
minimal active withdrawal state while needed to prevent prohibited processing
and independent recovery protection under Q26. Old consent never defeats later
withdrawal. This is not indefinite or automatically anonymous retention. L03/L04.

### Q22 Verification and delivery

**Proposed settings:** ten-minute single-use challenge, five failures, atomic
supersession, 60-second resend, 3 issuances/rolling hour and 10/rolling day per
normalized account/email with proportionate campus-aware controls. Prior
approved settings win; defaults in code/playbooks are not owner approvals.
Keep existing keyed-HMAC storage, protected external key and terminal erasure.
**Approved delivery/lifecycle controls:** job deadline is the earlier of queue
time + 5 minutes or challenge expiry - 60 seconds; bounded transient retries and
a validity recheck before dispatch. Verifier/attempt cleanup ceiling is 24
hours after use/expiry, without weakening existing immediate secret erasure.
Erase code-bearing bodies immediately at terminal delivery/failure/expiry, no
later than one hour; encrypt queued bodies with dispatcher-only access, exclude
routine exports, minimize backups. Keep only code-free message ID/template/
outcome/count/times for 7 days; protect/minimize any address. No raw code/token
URL/body logs or exports. Provider acceptance is not inbox arrival or recall.
L06; no real mail in this task.

### Q23 Metadata table

The owner's **proposed table**, reproduced with allowed fields and triggers in
the inventory, remains proposed: operational logs 30 days without archives;
justified raw IP 7 days / minimized security events 30 days; privileged audits
365 days; rate events longest window + 24 hours; probation active + 30 days;
pair ledger while original pair exists then bounded recovery only; support 90
days after closure; rights proof 365 days after closure; exports 24 hours;
suppressed non-identifying aggregates 13 months. Separately adopted Q13/Q19/
Q21/Q22/Q24/Q27 values remain adopted; this proposal does not reopen them.
**Approved protection:** redact before transmission; ordinary logs/analytics
exclude passwords, sessions/tokens, student identifiers, messages, locations and
raw answers. Longer provider minimums require explicit reconciliation, not a
false claim of enforceable configuration. L03/L05/L07.

### Q24 Physical cleanup

**Approved:** cleanup at least hourly with an independent urgent denial path.
Media/thumbnails 24 hours; ordinary deleted account data 7 days; confession and
history use earlier category clocks; invalidate cache references immediately
with stated client refresh bounds. Expired/cancelled/declined ordinary request
rows are erased within 30 days of their terminal event, after minimal lifetime
policy state is preserved; this does not erase the decline/cutoff/budget ledger.
Log category/count/deadline/error/completion watermark, never erased content.
Operations executes, engineering repairs, privacy samples exceptions/results;
all named owners remain UNASSIGNED. Alert at breach; escalate after 24 hours
unresolved. Failed jobs or missing object indexes are not erasure proof. L03/L07.

### Q25 Clocks

**Approved for new deadlines:** UTC server events; hour = 60 minutes; day = 24
hours; a one-year retention period = 365 days; only the aggregate category uses
13 calendar months. Store expiry instants; reject new operations at or after
expiry. Preserve earlier approved request and unsend anchors/boundaries exactly;
verify the precise existing 15-minute edge rather than silently redefining it.
Use each category's original event; reads/retries/edits/background work/exports/
copies/routine hold reviews do not reset clocks. A legitimate independent event
has its own record, not an extension of the old one. L02/L03/L04/L06.

### Q26 Backups and independent recovery

**Proposed copy limits:** DB/PITR 35 days with no extra long-term archive;
private media backups/versions 7 days if recovery targets can be met; approved
encrypted troubleshooting exports 24 hours. These are not current Azure
settings or proof of physical deletion. Inventory every location/expiry; copying
does not renew the source lifetime. **Approved restore controls:** isolated
rehearsal servers cannot serve users, send mail, touch production dependencies
or run cleanup against production; expire within 72 hours of creation or an
earlier source deadline. An actual restore is a separate authorized task.

**Approved recovery rule, conditional calculation:** retain a minimal independent
instruction until the latest expiry of any recoverable copy plus 7 days, and
while a still-live restriction needs it. With proposed maximum 7-day live cleanup
and 35-day DB copies after the last possible content-bearing backup, the
conservative horizon is **7 + 35 + 7 = 49 days from deletion**. It is not 42
days, not 49 days from an arbitrary read and not a universal deletion timer.
Use later verified purge/copy expiry or active protective state where needed.
Unknown copies block restore/release. Store protected subject/resource references,
action/version, effective time, verified purge and retain-until times; no deleted
content, coordinates or raw secrets, and keep it outside the restore set.
Replay the latest protections and expiry before app reads or dispatch. L03/L07.

### Q27 Privacy requests

**Approved:** authenticated route and genuinely monitored privacy/support contact;
reauthenticate sensitive exports/deletion. After deletion use the original
verified contact plus proportionate minimal corroboration, without a new full
account or default documents. Refer unsafe identity ambiguity to privacy owner.
Acknowledge within 2 days, aim to complete within 30 days or a shorter verified
mandatory deadline. No invented automatic extension; exceptions need reason,
categories, permitted duration and safe explanation/appeal route. Redact third
parties/protected evidence; encrypted authenticated exports expire after 24 hours,
never mail a raw dump. Preserve necessary correction/dispute audit. Verified
deletion starts Q14 immediately. L03; contact/staff/capacity/privacy REVIEW GATE.

## D12: geographic matching

### Q28 Source

**Proposed source:** manual coarse-area selection plus optional one-time device
conversion through an offline/local catalogue and user confirmation. **Approved
constraints on any selected flow:** backend/mail/logs/telemetry receive only
coarse area ID/version, confirmation time and necessary consent; no raw device
coordinates or continuous/background tracking. If local conversion would expose
precise coordinates to a third party, use the reviewed manual path first and
leave device conversion gated. D03 still requires geographic matching. L04.

### Q29 Catalogue and provider

**Approved direction:** versioned reviewed NUST/Bulawayo coarse catalogue with
stable IDs/labels/bounds/public representative centroid/provenance/history and
broad elsewhere-in-Zimbabwe, outside-Zimbabwe and unknown fallbacks; no rooms,
houses or small residential blocks. No paid map/geocoder initially; later
provider needs owner/budget/licence/data-flow review. **Proposed widths:** urban
zones approximately at least 5 km across, sparse zones at least 10 km. Actual
names/bounds/points remain CONFIRM/VERIFY with Takudzwa or an accepted local
reviewer; no coordinates are invented in this checkpoint. L04; H13 open.

### Q30 Consent

**Approved:** optional separate location opt-in explains source, coarse area,
ranking, visibility, lifetime, recipients and change/withdrawal. Browser
permission is insufficient alone. Refusal preserves ordinary matching,
requests/chat; withdrawal immediately removes ranking/disclosure and invalidates
cursors/caches, with live removal within 24 hours, without ending matches/chats.
Only user action asks for a new fix; no repeated permission nagging. L04; final
wording, consent mechanism and H13 REVIEW GATE.

### Q31 Precision and inference

**Approved:** only current area ID/catalogue version/confirmation/expiry/consent;
public catalogue centroids are not user coordinates. No exact distance, pins,
directions, travel/last-seen trail or residential-area disclosure. Broad nearby
label only where safe: at least 10 eligible opted-in users in the disclosure
cohort; merge sparse areas or suppress both label and bonus. This reduces risk,
not proof of anonymity. At most 2 successful location changes/rolling 24 hours;
withdrawal always immediate. Control repeated searches/probing; no hidden-area
filters, coordinates or exact distance ordering. L04; inference/H13 evidence open.

### Q32 Location lifetime

**Approved:** fresh 7 days from explicit confirmation; at expiry stop ranking/
disclosure immediately and erase live area/cache state within 24 hours. Replace
the current value without movement history; withdrawal uses the same denial/
purge rule. Passive reads do not renew freshness. Minimal Q21 proof excludes
area/coordinates; replay current consent/protections before restored use. L03/L04.

### Q33 Ranking

**Approved:** soft bonus of at most 5 points after mutual eligibility/safety,
using a 0-100 normalization for comparison without redesigning other matching
strategies. No mandatory radius; distant stronger matches may outrank nearer
weaker ones. Unknown/stale/withdrawn/sparse-suppressed or unusably broad city
areas get zero, without exclusion. Keep questionnaire compatibility distinct
from proximity rank and disclose only approved safe labels.
**Candidate band schedule, not adopted:** 0-5 km +5; >5-15 +3; >15-30 +1;
>30 +0 using approximate catalogue centroids. Verify examples, fairness,
reciprocal filters and probing; D12 is not certified. L04.

### Q34 Updates and pagination

**Approved:** confirmed change reorders the next response, invalidates old
cursor/snapshot and offers `results updated`; do not mix rankings mid-sequence.
Stable ordering/tie-breaks within a snapshot; always recheck withdrawal,
blocks/bans/deletion and safety on returned/actioned cards. Do not keep exposing
old geography until the next user-initiated search. L04; test two tabs,
duplicates/missing cards, change/expiry/withdrawal during paging.

## D06-D11: operational selections and proposals

### Q35 Funding

**Proposed:** funded production subscription/reserve with student credit only
as monitored bridge; Takudzwa as billing owner only if he accepts. **CONFIRM:**
actual owner, offer/credit/expiry, funding, total USD/local-equivalent cap,
reserve and recipients. Cost worksheet covers app/staging/DB/backups/network/
media/egress/mail/telemetry/domain/monitoring; no invented affordable cap.
**Approved guardrail:** approved-cost alerts at 50%, 80%, 100% and forecast
100%; alerts are notifications, not hard caps. At cap stop optional new paid
provisioning/escalate, never auto-delete data or safety/recovery infrastructure.
Any suspension needs its own approved runbook. L07; no charges/resource changes.

### Q36 Regions

**Proposed:** South Africa North app/DB/primary media/diagnostics where supported.
Recorded United States ACS processing remains a candidate flow, not clearance.
**Approved review requirement:** inventory actual service/backup/control-plane/
support/email/DNS/CDN/SDK flows; authentication mail excludes chats/answers/
location. Qualified privacy/security review must address actual Zimbabwe and
foreign processing, rather than assuming South Africa is domestic or consent
settles all transfers. No resource migration. L07; H03 open.

### Q37 Availability and releases

**Proposed:** 99.5% monthly product availability including planned downtime;
planned maintenance at most 30 minutes/month with notice. No SLA/capability
claim. **Approved requirements:** separate synthetic staging app/config/DB/
media/mail, slots only after verified tier support, otherwise price separate
staging. Stop release for required CI failure, destructive migration uncertainty,
missing recovery proof, access failures/critical security defects, unstaffed
workflows or unapproved location flow. Keep a last-known-good code/config
artifact and rehearse rollback with forward-compatible migrations; code rollback
does not reverse schema/protective actions. L07; no release/restore authorized.

### Q38 Recovery

**Proposed:** DB/DB-queue RPO 15 minutes; media RPO 1 hour; config RPO last
approved release; service RTO 4 hours from incident declaration. Validate/fund
targets; they do not guarantee meeting the monthly SLO. **Approved requirement:**
lose no acknowledged safety/deletion/consent action or fail closed with verified
recovery; reconcile queues to avoid stale auth mail/duplicate dispatch. Future
synthetic rehearsal: engineering executes, operations measures, privacy/safety
witness, Takudzwa accepts evidence after reviewers are named. Actual witnesses
and backup owner CONFIRM. Rehearsal remains NOT RUN. L03/L07.

### Q39 Runtime matrix

**VERIFY, then approve:** exact current Azure/staging PHP/MySQL versions,
OS/extensions, candidate packages/images and managed-service behavior. Historical
PHP 8.5.6 reported on 11 October is not a current capture; local MySQL 9.6 and
Linux/CI images are not production evidence. Preferred PHP 8.5 supported security
line and future GA LTS MySQL preference are proposals, not guessed patch/upgrade
approvals. Preserve the current supported DB major until a rehearsed approved
upgrade. Verify PDO/pdo_mysql, OpenSSL, cURL, mbstring, fileinfo and declared
dependencies; auth/TLS/UTC/collation/SQL modes/TIMESTAMP(6)/locks/migrations.
Takudzwa plus named engineering/security reviewer must accept the matrix. L07.

### Q40 Sessions

**Proposed configuration:** one app instance, shared DB-backed sessions without
a Redis purchase; student idle 30 minutes/absolute 12 hours; privileged idle
15 minutes/absolute 4 hours with MFA; Secure/HttpOnly/SameSite=Lax, narrow scope,
rotation and trusted Azure ingress. Current source uses PHP sessions, Strict
cookies and a 1,800-second timeout refreshed on protected calls, with no explicit
DB save handler in `php/session_manager.php`; deployment handler is VERIFY.
Do not claim `/home` means shared storage or relax Strict automatically.
**Approved constraints:** intentional foreground activity only renews idle time;
no persistent remember-me before separate design; current account/session-version
checks and reset/logout-all/ban/deletion revocation. L06/L07; security gate open.

### Q41 Security configuration

**Proposed target:** private verified-TLS DB access, scoped runtime/migration/test
identities, controlled release-only migration credentials, platform/Key Vault
secrets, MFA/named least privilege and 90-day credential reviews. **Preserved
requirements:** runtime DDL disabled, disposable guards before mutation, no secret
Git/screenshots/logs, no broad firewall exception silently introduced. Verify/cost
networking and any time-bounded exception; rotate on compromise/departure.
Evaluate SCM basic publishing retirement only after replacement deployment works.
Named security approver CONFIRM; no settings changed. L07.

### Q42 Operators

**Proposed appointments/coverage only:** Takudzwa for product/engineering-release/
billing coordination; routine support 08:00-20:00 Africa/Harare daily with separate
urgent on-call path. All actual primaries/backups/monitored contacts/coverage
remain UNASSIGNED until accepted. Prepare a private operations register later;
do not put real contact details in public docs or appoint a DPO/independent
appeal reviewer by inference. Safety lead assigns; privacy/security authorizes
scoped holds/exports/disclosures. Staffing gates do not remove D03 features. L07.

### Q43 Telemetry

**Proposed:** Azure Monitor/Application Insights in reviewed diagnostics geography;
fixed route/status/error/latency/dependency/deployment/environment/correlation
fields, 10% ordinary trace sampling, bounded error counts and app-side aggregate
product counters. Optional browser events/page names and random first-party
visitor ID rotate/expire within 30 days; not authenticated identity or a count
of all registered people. **Approved privacy constraints:** optional browser
collection only after consent and stop on withdrawal; no automatic token URLs,
queries, form/user text, sensitive path fragments, identity, raw answers or
location. No replay/ads/profiling. Distinguish samples, aggregate totals and
small-cohort suppression; notices/configuration/redaction need review. D10
remains pending. L05.

### Q44 Monitoring

**Proposed, unfunded:** telemetry allowance US$5/month inside the overall budget;
actual priced caps/minimums required, no exact-dollar guarantee from data caps.
Candidate alerts: 3 successive one-minute availability failures; >=5 errors
and >2% 5xx/5 minutes; p95 >2 seconds/10 minutes with enough traffic; CPU >80%/
15 minutes; DB connections >80%/10 minutes; storage <20% free; backup objective
miss; overdue cleanup/dispatch/useful mail deadline; unusual signup/report/reset
rates. Named operations/backup/safety recipients are missing. Proposed urgent
acknowledgment within 15 minutes while staffed and escalation at alert +15
minutes without acknowledgment; routine review within 4 hours of coverage.
Tune with baseline traffic and content-free channels. No billable monitor
activation; funding/coverage/capability evidence open. L07.

### Q45 Sender and receiving contact

**Approved exact product selection:** transactional From `DoNotReply@bushisa.me`,
display name `Bushisa`, reusing the recorded verified sender/ACS resources,
including password reset. Existing verification/delivery is historical
operator-supplied evidence, not reverified in this task.
**Proposed:** `support@bushisa.me` and Takudzwa as its operator, conditional on
acceptance and real external receiving/monitoring proof. Outbound ACS is not
proof of inbound mailbox existence. The owner now expressly permits temporary
unset Reply-To until a real monitored receiving inbox is verified; the fallback
conflict is resolved. This does not approve `support@bushisa.me` or its operator
or change live settings. No invented inbox goes in notices/templates. L06/L07.

### Q46 Delivery acceptance

**Approved acceptance plan:** engineering/email operations checks DNS/headers/
inbox; support handles complaints, operations schedules/alerts, security owns
secrets. Actual people and consenting recipients CONFIRM. Test From/Reply-To,
SPF/DKIM/DMARC alignment, TLS, useful arrival, entered signup codes and single-use
reset links, expiry, bounce/failure and content-free logs; redact screenshots.
Do not treat provider acceptance as inbox delivery. Generic safe queued/retry/
failure UX cannot enumerate accounts; permanent failures stop retries/alert.
Document later DNS/provider changes; no real messages or DNS mutation now.
Signup-link ambiguity remains reconciled above. L06/L07.

### Q47 Password reset

**Approved:** reuse selected sender/transport; CSPRNG token >=256 bits, hash-only
challenge storage, 15-minute issuance lifetime, atomic single use, new challenge
supersedes old, password success invalidates all remaining reset tokens. Request
alone changes/locks no account or password; generic unknown/ineligible response.
Trusted configured HTTPS origin only; no Host/redirect injection, token logging,
referrers or third-party collection. Existing password policy; revoke all
sessions, generic security notice and ordinary login after success; no bypass
of verification/adult/bans/onboarding.
**Proposed resend limits:** 60 seconds, 3/rolling hour and 10/rolling day per
account/email plus proportionate controls. **Approved delivery/cleanup:** useful
job deadline min(queue +5 minutes, token expiry -60 seconds), password unchanged
on failure; reset hashes erased within 24 hours of use/expiry, encrypted queued
token bodies immediately terminalized with a one-hour cleanup ceiling. Hash-only
challenge storage is not a claim that encrypted redeemable mail contains no token.
Need expiry/replay/race/enumeration/revocation/failure tests on guarded systems
later. Dispatcher is currently signup-specific; no recovery flow is certified.
L06; no live token/mail exercise.

### Q48 Final review

**Required open gates:** named qualified privacy, security, safety, independent
appeal and operational backup reviewers. Privacy checks actual Zimbabwe/foreign
processing, dating/profile sensitivity, underage records, rights/retention and
any applicable licensing/DPO/notification duties without assumed legal clearance.
Security covers sessions/auth/uploads/tokens/races/network/logs/replay; safety
covers conduct/evidence/fairness/coverage. Takudzwa reviews final product/consent/
email wording after those reviews; no wording is signed off by this packet.
Use consenting testers/synthetic accounts with Android and iOS browsers where
supported; witness location grant/refuse/withdraw/expiry/coarse conversion/
sparsity/paging/probing without recording exact coordinates. Witnesses missing.
**Proposed final go/no-go role:** Takudzwa, pending acceptance. D04/D05/D12 and
operational gates stay open until criteria, actual implementation/evidence and
named sign-offs are met. Old CI, existing Azure hosting and SMTP do not substitute
for them; release remains separate authorization. L01-L07.

## Later implementation and evidence

| Workstream | Questions | Implementation gap and evidence needed | Named acceptance / release gate |
|---|---|---|---|
| L01 Reports/roles | 01-05, 18-20 | Authorized target routes, text-first intake, case dedupe, human sanctions, assignment/MFA/audits/independent appeals and demonstrated timed queues. | Product Takudzwa; safety/privacy/security UNASSIGNED; H03/H10. |
| L02 Pair/terminal history | 06-10, 17, 25 | Split active match from chat grants, own-undo state and atomic rolling cap; decline-only recreated-match clearance; old/new chat segments; pause/cache and unsend projections. | Product Takudzwa; engineering/safety UNASSIGNED; R01/R02/R04, Decision 27 alignment and D04 review. |
| L03 Lifecycle/replay | 11-27 | Inventory all live/copy stores, immutable deadlines, scoped holds, full workers/media index, measured erasure and independent restore source. | Privacy/operations/engineering UNASSIGNED; H03/H08, D05. |
| L04 Coarse geography | 28-34 | Reviewed real catalogue, consent/current area only, cohort protection, 2/24-hour atomic limit, score normalization and cursor invalidation. | Local/privacy/engineering witnesses UNASSIGNED; H13/D12; proposed source/widths/bands unadopted. |
| L05 Telemetry | 23, 43 | Pre-send allowlist/redaction, optional-consent gate, sample/aggregate separation and verified provider retention. | Privacy/security/operations UNASSIGNED; D10/H03. |
| L06 Recovery mail | 11, 22, 40, 45-47 | Separate reset purpose/outbox from signup; trusted origins, encrypted payload/hashed challenge distinction, useful dispatch and revocation. | Product Takudzwa; mail/security/support UNASSIGNED; H06, D11 inbox/header verification. |
| L07 Operations/config | 05, 24, 26, 35-44, 46, 48 | Read-only capture, priced topology, accepted owners/funding/targets, monitor feasibility, actual staged restore and final acceptance evidence. | All operational appointments UNASSIGNED; H04-H11; no infrastructure mutation here. |

Existing candidate CI remains attributed exactly to head
`e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push run `38546846225` and
pull-request run `38546851035`, as recorded in the owner packet. It is not a
test of these policy choices or the current documentation checkpoint. Migration
005 is not certified production-applied; isolated restore remains NOT RUN.

## Documentation validation and changed files

Validation capture: **2026-10-15T21:11:41Z / 2026-10-15 23:11:41
Africa/Harare**. This is a documentation-check time, not the owner's missing
acceptance timestamp.

- PASS: `git diff --check` (only normal Git line-ending conversion notices).
- PASS: all 48 numbered answer sections present once, no missing/duplicate Q IDs.
- PASS: 67 local Markdown links/anchors across the 14 affected documents resolve.
- PASS: prior D04 Decisions 1-26 approval section, earlier register body and
  the complete candidate CI section match the in-memory pre-task baseline
  after line-ending normalization. This preserves exact commit/run/job attribution.
- PASS: conditional 7+35+7=49-day recovery arithmetic, 7-day closed-history plus
  24-hour purge =192 hours, and fixed probation =168 hours.
- Scope check: all changed/untracked paths are documentation; no application,
  migration, production configuration, live DB/browser suite or real mail action.
- PASS: new-file whitespace, 15 approval/proposal status assertions and paired
  Markdown fenced-block checks across the 14 documents. No synthetic behavior
  is labelled newly tested.

Updated existing documents:

- `docs/remediation/decisions.md`
- `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`
- `docs/remediation/policy-proposals-d04-d05.md`
- `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`
- `docs/remediation/backlog.md`
- `docs/remediation/README.md`
- `docs/remediation/geographic-matching.md`
- `docs/data_retention_policy.md`
- `docs/Bushisa_P0_to_P2_Azure_Remediation_Playbook.md`
- `docs/Bushisa_Operational_Feasibility_Report.md`
- `docs/azure-smtp-deployment.md`

New documents:

- `docs/remediation/owner-policy-reconciliation-2026-10-15.md`
- `docs/remediation/data-inventory-retention-matrix.md`
- `docs/remediation/configuration-impact-checklist-2026-10-15.md`

Next bounded task: **L01a**, the report-route/target-authorization contract in
the backlog. Missing real-world inputs stay in the single reconciliation table;
in particular, a reliable priced total is unavailable until actual SKU/usage/
billing inputs and owner funding are supplied. No invented cost total, contact,
catalogue coordinate or operational appointment fills those gaps.
