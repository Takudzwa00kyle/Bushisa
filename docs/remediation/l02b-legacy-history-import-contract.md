# L02b: guarded legacy-history evidence and import contract

**Historical L02b design checkpoint, ready for engineering review.** Baseline:
`600149761d43af10e69944b8390796387e68a688`, branch
`codex/l02b-legacy-history-contract-20261016`. No applicable AGENTS.md was found
in the checkout or its ancestors. The starting tree and index were clean.
No importer, schema change, import, legacy activation or production inspection
was performed in L02b; every case was NOT RUN at b4a041b. The subsequent
[L02c offline implementation and exact results](l02c-offline-history-planner.md)
and [L02d synthetic transactions/evidence](l02d-synthetic-import-evidence-2026-10-17.md)
now update only the applicable synthetic case scopes below. L02d's separate
executor cannot enable pairs; production provenance is unverified.

Authority: the current [decisions](decisions.md), including Decision 27's scoped
supersession and Decisions 28–33, the [48-answer reconciliation](owner-policy-reconciliation-2026-10-15.md),
the [L02a contract](l02a-pair-history-contract.md) and its
[implementation evidence](l02a-implementation-evidence-2026-10-16.md).
Takudzwa Hondova remains the product reviewer; qualified reviewers/operators
are not appointed by this document. Exact historical owner acceptance timestamps
were not captured. UTC/Harare drafting or observation times cannot replace them.
No settled policy needs reapproval and no whole D-number is approved here.

## 1. Scope and non-enabling boundary

This specifies a CLI validator, planner and later guarded synthetic importer.
L02c supplies offline validation and synthetic snapshot planning. Any future
database inspection must enforce explicit local-disposable/read-only guards;
that adapter is not part of L02c.
L02d implements the separately guarded synthetic executor; it does not add an
apply/database mode to the offline planner. Real-data
capture/import, production writer activation and permission to enable legacy
pairs remain separate, unauthorized operations.

Distinguish these independent states:

| State | Meaning | Permitted result |
|---|---|---|
| COMPLETE evidence | All safety-relevant dimensions and the continuous source/destination coverage described below are proved. | A non-enabling import plan may be prepared. This is neither runtime eligibility nor qualified sign-off. |
| INCOMPLETE evidence | Some facts/lower bounds are trustworthy, but identity, coverage, order, timing or current safety state is missing. | Report the known bounds and gaps; no applying plan or certification. Legacy requests/grants remain fail-closed. |
| CONFLICTING evidence | Authenticated observations cannot coexist, or a required identity/overlap cannot be reconciled. | Reject that pair atomically, preserve destination state and require a new evidence package after qualified investigation. Never choose the permissive account of events. |
| INVALID/UNTRUSTED input | Format, authenticity, scope, custody or destination guards fail. | Reject before mutation; it cannot supply facts, privileges or an automatic sanction. |

L02c uses these labels only for its fictional synthetic model. Its COMPLETE
result is not proof of authentic or complete production history; every output
keeps production authenticity unverified and all external prerequisites open.

At `6001497`, [bushisa_pair_complete](../../php/pair_history.php) accepts only
`complete_from_genesis` backed by a post-epoch account record and trigger. There
is no supported legacy-certified state or importer schema. A future forward
migration must represent import provenance separately; it must not set
`complete_from_genesis`, manufacture a genesis account, edit the writer epoch,
or treat `history_verified_at` as proof. COMPLETE imported evidence leaves the
runtime pair `legacy_incomplete`. Any future eligibility adapter requires its
own reviewed implementation and evidence; it is outside this contract's apply.

The approved three-request directional lifetime budget, sender/recipient
windows, fixed seven-day probation, original-ender 12h restart, second-ending
cutoff across rematches, creator-only unblocking and versioned clearance remain
requirements. Unmatching preserves approved chat and a pending request's original
expiry. Restoration needs both fresh agreements and each person's own undo;
only genuine fresh mutual matching can clear declines. Importing an old consent,
approval or clearance is never a new user action or reviewer decision.

## 2. Admissible history and minimum provenance

An evidence source must have a private custody record: stable source-system and
database-incarnation references; collector and authorized purpose/scope;
collection method and tool/code digest; original artifact digest and protected
location; schema/version and deployed writer versions; capture times, UTC/clock
precision, transaction/commit boundaries and coverage exclusions; retention and
access authority. A digest detects changes; it does not establish authenticity
or completeness. A signature requires an independently trusted signer/key record,
not a public key supplied solely by the same untrusted bundle.

| Source | What it may establish | Limitation / required corroboration |
|---|---|---|
| Retained native immutable request/transition events | Identified committed actions, actors, pair, creation identity and original clocks. | `pair_request_events` has immutable creation identity but mutable status/deadlines and bounded expiry. It is not an exhaustive transition journal. L02a does not record every writer's commit sequence. |
| A contemporaneous protected enforcement checkpoint plus an uninterrupted transaction/change history | Exact historical count prefix and necessary state, then a provably disjoint committed suffix. | Verify checkpoint semantics, account incarnations, initial coverage, deletions, all writers and every interval through the reconciliation horizon. No assumed binlog/CDC availability, retention or Azure capability. |
| Authorized consistent snapshots/backups or earlier controlled exports | State at a documented boundary; corroboration of surviving events/restrictions. | A snapshot's absence of a row cannot prove that an earlier request never existed. Multiple snapshots are not an exhaustive event stream. Restore only under separate authorization; no private-body recovery for this task. |
| Current mutable request/match rows, counts, decline rows, blocks and case/block links | Surviving observations and lower bounds; present restrictions. | A row may have been reused, overwritten, deleted or backfilled. COUNT of current rows, `MAX(id)`, `generation=1`, default ending count zero or a verified marker cannot prove lifetime completeness. |
| `message_request_sends` / migration 006 backfill | Known sends retained in the rolling window and an observed current request. | Window cleanup removes timestamps; a reused request ID is not a distinct lifetime request identity. Backfill is not proof of all sends. |
| Protective replay/audit/case records | A specific recorded action with its actual authority, identity and clock. | The current co-located replay table is not independently durable coverage. Logs may be sampled, incomplete or expired; case material needs separately authorized access. A report is an allegation, not an extra request or proven ban. |
| Operator/user recollection, screenshots, UI messages, mail/notification records, old green CI | Leads or corroboration for a particular observation. | Never proof of zero omissions, approved restart, mutual consent or reviewer clearance. No invented event/time or new sanction from recollection. CI says nothing about a real pair's history. |

For each original account pair, establish a stable identity mapping scoped to
source incarnation and original account creation, including every legacy match
alias/orientation. Email, student number, display name or a reused database ID
alone cannot rebind history to a new account. A new account with a similar contact
route is not a rematch of the original account pair. Missing/deleted accounts or
ambiguous aliases block an import; do not recreate them or add an identity blacklist.

The completeness vector must separately prove:

1. Both directional lifetime request totals, identity overlap and counts already
   represented by compact checkpoints. A zero requires positive coverage from a
   proved point when at least one original account did not yet exist, or an
   equivalent authenticated complete checkpoint—not an empty query result.
2. Current pending/terminal request identity, original expiry, cancellation/expiry
   cooldowns and directional decline state; any genuine-rematch exemption must
   have both fresh post-unmatch interests. Undo cannot stand in for that evidence.
3. Approved-chat endings and their actors/order; the latest ending's actual time
   when a restart clock matters; lifetime cutoff, chat segments and restoration
   history/cap. A revoked row alone cannot distinguish block from explicit ending.
4. Current blocks in both directions, exact report-linked clearance scope,
   account ban/deletion/eligibility, consent withdrawal and session versions.
   Historical clearance never overrides an unseen new attachment or current block.
5. The continuous interval through the destination reconciliation horizon, plus
   account-wide sends/receipts across **other pairs** for the longest applicable
   rolling window and current probation. Pair-complete evidence alone cannot
   certify the sender/recipient allowance. Unknown probation clocks stay unknown;
   importing a report cannot start, extend or shorten a seven-day period.

Store actual UTC instants and their source precision. A seconds-only timestamp
can be encoded with zero fractional digits plus `precision=second`; this does
not prove a microsecond ordering. Unknown zone/offset or time stays unknown.
Do not use collection/import time, row order, the current policy's duration or
an arbitrary tie-break to invent an old deadline or ordering. A derived deadline
needs the proved original trigger **and** evidence that the applicable rule or
recorded deadline was effective for that action. An obsolete API default is not
an owner approval. If uncertainty can affect a limit/restart/expiry, classification
is INCOMPLETE (or CONFLICTING for incompatible observations), not permission.

## 3. Writer inventory at 6001497

This is a repository source inventory, not an observed production topology.
Real schedules, deployed revisions, identities and enabled paths are **VERIFY**.

| ID | Entry points and implementation | Relevant writes / capture requirement |
|---|---|---|
| W01 | [api/pairs.php](../../api/pairs.php), [pair_history.php](../../php/pair_history.php) | Unmatch, own undo/fresh restoration/refusal, generation/version/flags. GET preparation can call `bushisa_pair_ensure` and create metadata. Include readers with side effects in a freeze. |
| W02 | [api/swipe.php](../../api/swipe.php), pair matcher; discovery client | Fresh likes/passes, match insertion/reuse, generation and decline-only clearing. Include both orientations, old clients/retries and every serving worker. |
| W03 | [api/message_requests.php](../../api/message_requests.php), pair request service; [chat.php](../../chat.php) forms | Sends, lazy expiry, approve/decline/cancel/revoke, counts/window events, cooldowns, endings/segments and notifications. GET can observe/backfill a retained current event. |
| W04 | [api/safety.php](../../api/safety.php), [block_handler.php](../../php/block_handler.php), [report_intake.php](../../php/report_intake.php) | Ordinary/combined blocks, pending clearance, report attachment, probation, review/clearance, creator removal and eligible grants. Attachment/clearance can change without a pair-version bump. Include assigned-reviewer/MFA and case-assignment writers. |
| W05 | [api/messages.php](../../api/messages.php), [pair_messages.php](../../php/pair_messages.php), [api/matches.php](../../api/matches.php), [matches.php](../../matches.php), chat page | Message/segment references, unsend, read flags, proof issuance and previews. Never recover bodies or use ordinary import to mint report access proofs. Matches page also saves questionnaire answers. |
| W06 | [api/account.php](../../api/account.php), [account_delete.php](../../account_delete.php), [anonymiser.php](../../php/anonymiser.php), [policy_safeguards.php](../../php/policy_safeguards.php) | Deletion, account/session changes, consent revocation, pair closure/aliases, likes and cleanup/replay records. Anonymisation owns a separate transaction; hard-delete helper can remove identity/foreign-key rows. Its deployment callers/permissions need capture; no live hard-delete route is inferred. |
| W07 | [admin/reports.php](../../admin/reports.php), [admin/moderation.php](../../admin/moderation.php), [moderation.php](../../php/moderation.php) | Suspension/unsuspension and report state. These use direct user updates, not a complete pair event journal. Their coverage cannot be inferred from the pair mutex. |
| W08 | [register.php](../../register.php), [public/register.php](../../public/register.php), [auth.php](../../php/auth.php), [verify_email.php](../../verify_email.php), [onboarding.php](../../onboarding.php), [signup_verification.php](../../php/signup_verification.php) | Account creation/incarnation, verification/adult eligibility, status and session-version changes. `public/register.php` only redirects to the main register route, not a separate creation writer. Include the 007 account-genesis trigger and actual epoch; a table's mere existence proves no cutover. |
| W09 | [profile.php](../../profile.php), [db.php](../../php/db.php), [api/questionnaire.php](../../api/questionnaire.php), [api/find_match.php](../../api/find_match.php), [consent_manager.php](../../php/consent_manager.php), session/auth helpers | Eligibility preferences, questionnaire, purpose grants/withdrawals and authentication state. Some helpers have no proved active route or journal; inventory callable privileged paths rather than assume none. Import never writes them. |
| W10 | [bin/cleanup_policy.php](../../bin/cleanup_policy.php), pair/report cleanup | Pending expiry, request/event/receipt removal, current-event pointer clearing, rolling timestamps and old segment/text purge. Several changes do not bump pair version. Cleanup is a writer even with no human traffic. |
| W11 | [bin/migrate.php](../../bin/migrate.php), [operational.php](../../php/operational.php), [006](../../database/migrations/006_report_intake.sql), [007](../../database/migrations/007_pair_history.sql), [pair_migration.php](../../php/pair_migration.php) | DDL, known-row backfills, precision changes, legacy defaults and account trigger. Capture schema/trigger definitions and DDL principals; runtime DDL must be disabled. A migration timestamp is not an event time or deployment-wide epoch. |
| W12 | Restore/manual SQL, support scripts, release jobs, retired workers, future importers; [tests](../../tests/) and [synthetic activation](../../tests/support/activate_pair_ledger.php) | Enumerate actual database-writing identities, tools and destinations, including non-repository tools and test/reset access. Direct SQL/restore can bypass application locks. Test fixtures and synthetic replay records cannot attest production history. No production restore/import tool is evidenced in the current bin inventory. |

The shared [bushisa_report_transaction](../../php/report_intake.php) lock is
`bushisa-report:` plus the first 32 hexadecimal SHA-256 characters of the current
database name. It is acquired before the transaction. It serializes cooperating
L01b/L02a paths, not all account/admin/DDL/restore writers above. The pair version
also does not version every restriction. A source digest plus current pair
version alone is therefore insufficient for a consistent capture or apply.

## 4. Snapshot, cutover and reconciliation boundaries

Use source commit positions/order, not wall-clock timestamps, to describe the
boundaries. Let **B** be the consistent snapshot boundary, **W** the first proved
all-writer reliable-history boundary, **H** the end of its complete captured
suffix, and **C** the destination state locked for a particular plan/apply.
These are typed source-specific positions; there is no assumed globally numeric
position or comparison across different database incarnations.

An authorized future capture must establish one of two methods:

| Method | Evidence required |
|---|---|
| Quiesced capture | Inventory and stop/drain every mutator, including GET side effects, admin, jobs, DDL and direct access. Prove no in-flight commits at B; take a consistent all-table snapshot with its schema/clock/custody. Document the explicitly empty interval until W, deploy/verify every writer, then independently observe the effective cutover before resuming. A declared maintenance flag alone proves none of this. |
| Consistent snapshot plus complete catch-up | Demonstrate how the engine/service ties snapshot B to a continuous committed change stream. Capture all commits in `(B,H]`, including transactions begun before B and committed after it, deletions and non-pair writers. Prove deployed writer coverage by W and continuity through H/C. Verify provider retention/permissions/transactionality and restart/rotation behavior; if this capability is absent or a gap exists, use quiescence or keep the history incomplete. |

The cutover packet needs actual app/slot/worker/job inventory, exact revisions
and writer schema/protocol, process start/drain and in-flight outcomes, database
incarnation/schema/trigger captures, privilege and configuration records, boundary
method/positions and artifact hashes, ordered tail/dedup rules, known exclusions,
and named authorized collectors/reviewers with their actual observation times.
A PHP runtime report, one worker PID, a green CI run, migration 007's date,
`pair_ledger_epoch.activated_at`, an account timestamp or a synthetic activation
label alone cannot establish W. Neither capture method was performed here.

For each future plan, link source history through H with the current destination
overlay through C using stable event identities and observed writer boundaries.
An event committed after B is counted exactly once, whether it appears in a later
snapshot, native ledger or tail. Long-running transactions belong to their commit
boundary. Equal timestamps do not order approve/end/unblock; require causal or
transaction ordering. Missing intervals, ambiguous order or unsupported cross-
incarnation reconciliation block COMPLETE classification.

Compare all relevant current-state rows and absence/range proofs, not merely
`pair_policy_history.version`: accounts/session versions, both directional counts
and cooldowns/declines, match aliases/generations and flags, active segments and
current request/event references, both blocks, exact report links/clearance,
probation and consent/recovery state, plus relevant schema/epoch. Preserve current
tail facts even if they are newer than the imported source. If a non-cooperating
writer or missing range/journal coverage makes this overlay unprovable, apply is
disabled. Actual production B/W/H/C and legacy provenance remain **UNVERIFIED**.

## 5. Evidence bundle and stable identities

Format identifier: `bushisa.legacy-history.v1`. The linked L02c wire profile now
accepts only its offline synthetic subset; no production/applying input is
supported. The validator must reject
unknown authority-bearing fields, duplicate JSON keys, malformed UTF-8/UTC,
overflow/fractional counters, unsupported versions and unbounded input. Finite
byte/fact/pair limits must be explicit in the reviewed implementation/config;
missing limits are a configuration rejection, not an unlimited parser.

| Section | Required data / validation |
|---|---|
| Envelope | Format/policy-semantic versions, original bundle digest, source/capture references, artifact digest list, declared pair scope and protected retention expiry. No database URL, credentials, SQL, network fetch, executable serializer or arbitrary filesystem path. |
| Account/pair mapping | Source incarnation and immutable original-account references; explicit destination-incarnation/account mapping and all match aliases; ordered canonical pair plus separate directional keys. Mapping authorization is external evidence, never an email-based inference. |
| Coverage | Account-origin or certified compact checkpoint, B/W/H positions, continuous interval proofs, writer inventory digest, exclusions and complete/incomplete/conflicting vector. A producer's `complete=true` is only a claim for the validator to test. |
| Facts | Source event identity, type, pair/direction and actual actor, causal/commit references, observed UTC/precision or explicit unknown, relevant original expiry, source artifact location by approved reference, and whether a fact is already represented by a checkpoint/native event. No message bodies, attachments, coordinates, student contacts or secrets. |
| Reconciliation | Explicit identity-equivalence/overlap map, checkpoint-covered sets/ranges, destination native tail and its coverage, computed lower/exact bounds and every unresolved conflict. Do not count an observation and its represented event twice. |
| Review references | Existing collection/purpose/retention authorization and named evidence-review records if available. Missing records stay missing. Import supplies no review signature, contact consent, ban finding or clearance. |

Stable fact identity is a canonical tuple of source incarnation, immutable stream,
native event key and within-transaction ordinal (only where actually recorded).
Represent it with a versioned, domain-separated SHA-256 identity; retain the tuple
privately for collision/conflict checking. The canonical payload digest is
separate. Same identity/same payload is a duplicate; same identity/different
payload is a conflict. Hashes/pseudonyms remain potentially personal data.

Apply that identity rule to an immutable fact's fields, not an entire mutable
row. A request observed pending at B and approved at H can be one creation fact
plus two consistent, boundary-linked state observations. Do not count two sends
or reject the legitimate status change as an immutable-payload conflict. An
approval/end/clearance action needs its own native causal identity and actual
time where the policy needs one; the later snapshot's capture time cannot supply
it. If observations establish only that a transition happened somewhere in an
interval, retain that uncertainty. A required missing action/order/time keeps
the relevant completeness dimension INCOMPLETE.

A mutable request-row ID is not a native creation-event key. L02a's current-row
observation key identifies an observation, not every prior use. A timestamp,
row number, import-run ID, body hash or newly generated UUID must not invent a
missing historical event identity. Multiple artifacts of one event need an
explicit evidence-backed equivalence mapping. If identity cannot be established,
the event may be described as an uncounted lead/lower bound in the diagnostic,
but cannot support a COMPLETE/applying plan. An authenticated compact checkpoint
may replace expired detail only when it certifies the covered prefix and required
state; do not fabricate individual events to explain its count.

Canonicalization fixes field types/order, normalizes only semantically equivalent
representations, and sorts sets by stable identity without reordering causal
sequences. Reordering JSON fields or duplicating an identical source copy must
not create another event. Changing account mapping, source, coverage, actor,
timing or represented-set membership changes the bundle/intent digest.

Keep the raw artifact digest used for custody distinct from the canonical bundle
digest used for deduplication. The latter covers semantic facts, mapping,
coverage, source and authority references, not JSON layout or collection order.
Each differently encoded artifact still needs its own valid custody/digest
check. The pair intent binds that canonical bundle, original pair and exact
allowed projection; the plan also binds C's pre-state fingerprint. The receipt's
pair/bundle identity uses the canonical digest. A raw-byte encoding change alone
cannot evade that identity or create another effect.

## 6. Guarded validation and dry-run plan

Original L02b phases (not execution claims): L02c implements offline validation
and snapshot planning only, with its supported wire profile linked above. No SQL
inspection adapter is implemented; the connection/read-only-role variants below
remain NOT RUN.

1. **Validate offline.** Read only a bounded local, scope-approved evidence file;
   no database/network access or runtime bootstrap. Check format, digests,
   provenance, identity, causality, deadlines, completeness vector and exclusions.
2. **Plan read-only.** Before loading connecting application configuration,
   enforce CLI-only/local-disposable destination guards. Require explicit
   `APP_ENV=development`, `APP_DEBUG=false`, `BUSHISA_TEST_DATABASE=true`,
   `BUSHISA_ALLOW_RUNTIME_DDL=false`, `MAIL_PROVIDER=fake`, explicit loopback
   host/port/user and a disposable database
   name using the [existing guard](../../tests/support/disposable_database.php).
   Also bind a known synthetic destination incarnation and reject a mismatch,
   remote/production aliases and unsafe fallback environment settings before
   application queries. A test-looking name alone is not a production safeguard.
   This importer version has no production/remote/force/enable override.
3. **Read consistent destination state.** Use a read-only adapter; never call
   `bushisa_pair_ensure`, request-read/backfill, ordinary HTTP GETs, migrations,
   report intake or notification/mail helpers. Dry-run must not create a row,
   bump a version, claim a review, queue work or update an audit table. A protected
   local diagnostic file is its only output mutation.
4. **Produce a deterministic pair plan.** Include bundle/pair/source/destination
   digests; classification and per-dimension evidence; B/W/H/C references and
   current-state fingerprint; overlap identities; old/current/proposed count and
   restriction projections; exact allowed write set; no-write invariants; expected
   pair and account/session versions; retention ceilings; rejection/replan reasons
   and required reviewer/activation gates. Separate processing `observed_at` from
   historical clocks. No public eligibility or private content in output.

Plan results are `PLAN_NON_ENABLING`, `INCOMPLETE_NO_APPLY`, `CONFLICT_NO_APPLY`,
`REPLAN_REQUIRED`, `REJECT_INPUT` or `REJECT_DESTINATION`. No flag can skip a
failed dimension. A plan is valid only for its exact evidence, destination
fingerprint/boundary and applicable source deadlines. At apply, recheck current
server time and expiry, not a guessed review TTL or the dry-run timestamp.

## 7. Non-enabling merge and transaction contract

Future apply v1 is scoped to already existing `legacy_incomplete` pairs on a
guarded synthetic destination. Missing/deleted accounts/pairs, a native-genesis
target, unresolved conflicts or incomplete coverage reject the pair. It creates
no account, match, active request, grant, message or false birth record. An
unexpected claimed legacy history on a native-genesis pair requires investigation,
not an automatic downgrade, privilege change or new sanction from untrusted input.

Proposed forward schema needs separate import bundles, immutable fact identities/
payload digests and alias/coverage references, plus pair-atomic apply receipts.
These were design concepts at L02b. L02d's additive migration 008 and exact
synthetic schema/receipts are linked above. Imported facts
are not inserted into ordinary reportable events, and generate no report context
or retrospective report/probation. L02d rehearses fresh/upgrade/second-run 008
locally; no production migration application is inferred.

| Destination concern | Allowed conservative result |
|---|---|
| Request totals | Preserve `current_count`. If a certified prefix P and suffix T have proved identity coverage, compute `P.count + count(unique T outside P)`. A compact count is never blindly added to overlapping snapshots/native counts. Reconcile every unexplained current excess; never reduce or clamp counts to three. An overlap that cannot be proved is INCOMPLETE/CONFLICT, not an additive guess or a zero. |
| Endings/cutoff | Increase only to the reconciled known/exact union floor, never decrement. Preserve a current later ending; fill missing actor/time only from proved applicable facts/order. Missing latest-ender/time keeps eligibility unresolved. Two known endings remain a cutoff even if older details expire. |
| Cooldowns and restoration cap | Preserve later/current applicable deadlines; a historical import/read cannot restart one from processing time. Any proposed historical deadline needs its original proved anchor. Never set fresh restoration agreement flags or undo someone else's unmatch. |
| Declines | Current active restriction dominates historical clearance. A proven necessary missing restriction can be proposed/added with its original fact; no clearing an active row, no treating undo as a new match. Historical clearing can be retained privately as fact only; it cannot clear live state in import v1. |
| Current native events/requests/segments | No overwrite/delete/relabel of native events, request pointers, current pending expiry, later-generation state, segments or old-history deletion deadlines. Historical facts are separate; expired details are not resurrected in ordinary tables. |
| Blocks, clearances, safety and sessions | Both blocks, link/submission scope, clearance/reviewer assignments, bans, account/consent status, probation periods and session versions are read/rechecked but never modified by import. No `chat_suppressed` clearing, unsuspension, unblock, consent grant, unmatch restoration, session decrement/refresh or auto-ban. |
| Rolling allowances | Keep native send/receive events and current probation intact. Stage only evidence-backed distinct window facts for a future reviewed adapter; do not insert duplicate timestamps or enable allowances from a pair-only checkpoint. Global-window reconciliation remains an explicit future activation gate. |
| History state | Remains `legacy_incomplete`, even for a COMPLETE evidence receipt. No genesis/epoch edits or `history_verified_at` shortcut. Runtime certification/activation is a separate reviewed implementation. |

One pair is the atomic apply unit; no partially accepted pair or statement-by-
statement checkpoint. A multi-pair run records individual committed/rejected
outcomes and never describes the entire batch as atomic. Apply steps:

1. Revalidate destination guards, local source authority/deadlines and exact
   bundle/plan digests. No manifest-controlled connection or post-validation file
   replacement; parse/hash the same bytes that will be used.
2. Acquire the existing database-specific report advisory lock **before** the
   transaction. Use the reviewed canonical lock order for account rows, pair,
   counters and restriction/alias ranges. Relevant non-cooperating writers must
   be covered by proved quiescence or a tested compatible row/range/journal
   protocol; the advisory mutex alone is insufficient. Exact engine/isolation/
   lock ordering and account-deletion deadlock behavior remain implementation tests.
3. Under the lock, check the committed pair/bundle receipt and apply-key intent
   after current destination/authority checks, before comparing the old plan's
   pre-state. An identical committed import returns its content-free outcome;
   no effects/version bump repeat. Its own committed version increment must not
   make a lost-response retry fail the old pre-state check. A reused apply key
   with different intent conflicts; no receipt response grants current access.
4. For an uncommitted import, lock and reread C, including account/session
   versions and restrictions that do not bump pair version. Any changed
   fingerprint, unseen event or missing range/coverage returns REPLAN_REQUIRED
   with no projection writes. Check stable fact keys: different immutable payload
   for one identity conflicts; duplicates across bundles link to the original
   identity and are never counted again. A planner cannot automatically override
   a newer native action.
5. Insert admissible private facts/references and only the allowed monotone
   count/restriction projections. Preserve every no-write invariant above.
   Increment the pair version once for a newly committed import, never reset it;
   this invalidates old client intents without conferring permission.
6. Commit the pair receipt, fact membership, resulting version and after-state
   digest in that same transaction, then release the mutex in `finally`. Receipt
   uniqueness binds destination incarnation, original pair and bundle; an apply
   key also binds the exact plan/intent. A new plan needs a new intent key, but
   cannot bypass global fact or committed pair/bundle deduplication.

Fault before commit rolls everything back; retry first checks whether a receipt
actually committed. A response lost after commit returns the existing receipt
after current destination/authority checks, not a cached access grant. Deadlock,
lock timeout or a changed destination requires bounded retry/replan and renewed
guard/state validation; never replay stale updates or report uncommitted success.
The exact retry ceilings must be finite, documented and tested in implementation.

Do not recover by restoring a whole database snapshot, rolling counts backward,
deleting receipt keys or running inverse SQL over newer events. An operator
finds the durable receipt and verifies its committed digest. A mismatch or lost
receipt/fact/recovery continuity is a release/import gate until investigated.
Recovery after a real restore additionally needs the independent deletion/block/
consent record and expiry replay; the co-located import registry is not that
independent record. No restore rehearsal was executed here.

## 8. Privacy, retention and evidence output

Use the [current inventory/retention matrix](data-inventory-retention-matrix.md),
not older standalone proposals. Collection/import does not restart request,
evidence, closed-chat, consent or backup clocks. Expired source artifacts cannot
be copied under a fresh capture/import date; aggregate proof may survive only
under its separately documented permitted purpose/bound. No message body,
erased original, coordinates, raw identity contact or redeemable secret belongs
in the bundle, ordinary logs or dry-run output. Case evidence is not a substitute
for authorized historical pair coverage, and requires its own scoped access.

Record minimal source/fact digests, coverage and decisions needed for lifetime
enforcement; discard detail once its applicable original deadline passes. An
imported old terminal request beyond its 30-day detail deadline is represented
only by an admissible minimal checkpoint/count fact, not a resurrected event row.
If receipt or dedup detail expires, retained coverage/frontier protection must
still reject replay of its counted prefix; lack of that protection fails closed.
Do not retain an unbounded per-event blacklist to compensate for missing design.
Raw staging, rejection artifacts and new receipt/checkpoint retention need a
purpose/category/expiry and qualified privacy review before real data use; no
new indefinite retention period or hold is approved by this document.

The future runner uses counts/category/deadline/outcome and protected references
in diagnostics, private least-privilege storage and named access/overdue handling.
These roles and workflows remain unassigned/unverified. Account deletion must
eventually remove original-pair import material under its permitted lifecycle,
preserving only justified independent recovery instructions. Conditional
7-day live erasure +35-day backup +7-day recovery margin equals 49 days only
under those proposed copy assumptions; it is not a new universal import timer.

## 9. Concrete synthetic examples and acceptance cases

All identities, source positions, times and actions here are **fictional test
fixtures**. They are not Bushisa records, appointments or owner acceptance times.
UTC examples display in Africa/Harare at UTC+02:00: `2026-10-01T10:00:00Z` is
12:00 that day. Import time is a separate processing field.

Common fixture F: original accounts `syn:A1`/`syn:B1`, pair P in synthetic
incarnation S, source checkpoint K at B=commit 100, reliable writer boundary
W=100, continuous suffix through H=120 and a current destination overlay C.
K has audited exact counts A→B=2/B→A=0 and one ending by A at
`2026-10-01T10:00:00Z`; its discarded event detail is represented by its certified
prefix, not fabricated rows. Source event `S/request/121` at commit 121 is an
A→B request with proved original expiry; the destination native tail contains
that **same** event, mapped by explicit equivalence, at C=122. Valid union count
is 3, not 4; ending time/restart anchor remains 10:00Z. Runtime history stays
`legacy_incomplete`; no import makes restart immediately available.

Fixture U instead has one surviving mutable request and an unexplained prior
interval: known lower bound ≥1, exact total unknown. Fixture X has two authenticated
payloads for the same source event, one attributed to A and the other to B.
U is INCOMPLETE; X is CONFLICTING. Neither is a request budget of zero or an
applying plan. Fixture Z can certify zero only with positive original-account
origin coverage plus a continuous, authenticated empty action interval.

Historical illustrative projection for F (output excerpt, **not an input
manifest or an executed result**; the current CLI uses the linked L02c plan
format). The complete plan also needs the provenance,
coverage, state fingerprint and write-set fields in section 6:

```json
{
  "format": "bushisa.legacy-history.v1",
  "fixture": "F",
  "classification": "COMPLETE",
  "outcome": "PLAN_NON_ENABLING",
  "request_union": {"A_to_B": 3, "B_to_A": 0},
  "ending_count": 1,
  "last_ended_at": "2026-10-01T10:00:00Z",
  "runtime_history_state": "legacy_incomplete",
  "grants_chat": false,
  "changes_sessions": false,
  "attests_production_cutover": false
}
```

**Current combined L02c/L02d scope: 19 PASS, 14 PARTIAL, 0 wholly NOT RUN.**
Fourteen PARTIAL cases retain explicitly NOT RUN or UNVERIFIED variants; this
is not a completed matrix. The operations and expected
observations below remain the original contract requirements. See the
[individual execution/remainder table](l02d-synthetic-import-evidence-2026-10-17.md#current-lb01lb33-results)
for what actually ran. PASS is limited to the stated synthetic validation/planning/transaction observation;
PARTIAL leaves its listed application/real-world variants unexecuted. No L02a
assertion is relabelled as an import test. L02c's historical offline-only totals
remain 13/13/7 in its unchanged evidence; current results include L02d separately.

| ID | Synthetic operation | Required observation | Status |
|---|---|---|---|
| LB01 | F with exact prefix, all mappings and continuous tail through C. | COMPLETE evidence; PLAN_NON_ENABLING count 3, fixed ending clock; runtime remains legacy_incomplete and no chat/request/epoch activation. | PASS |
| LB02 | Z's positive account-origin/empty interval versus an empty current query without it. | First can prove zero; second is INCOMPLETE, never silently zero. | PASS |
| LB03 | U; old history_verified_at set and generation/default ending count zero. | Known lower bound only; no verified marker/default supplies completeness or restart. | PASS |
| LB04 | Missing original-account mapping, reversed duplicate match aliases or reused ID from another incarnation. | Reject ambiguous mapping; never merge a new account based on email/HMAC or invent match generations. | PASS |
| LB05 | X: same stable event identity, different actor/time/meaning. | CONFLICT_NO_APPLY; neither last-writer-wins nor duplicate INSERT IGNORE; destination unchanged. | PASS |
| LB06 | Source has equal-time approve/end or unmatch/new-match observations without causal order. | INCOMPLETE; timestamps/array order do not grant restart or decline clearing. | PASS |
| LB07 | Unknown latest ending actor, UTC offset or relevant cooldown deadline. | Explicit unknown and INCOMPLETE; no import-time or guessed policy-derived timestamp. | PASS |
| LB08 | Same bundle/key retried, including JSON field reordering. | One pair receipt/fact set/count adjustment/version increment; committed retry returns no access grant. | PASS |
| LB09 | Same event reappears in another bundle/snapshot or overlaps K/native tail. | Stable identity/equivalence dedup; F remains 3, never 4 or aggregate+aggregate. Unproved overlap is incomplete. | PASS |
| LB10 | Destination count 4 but F explains only 3; or count 5 explains a proved larger disjoint tail. | Unexplained excess is conflict and never reduced; explained larger union stays 5, not clamped to lifetime budget 3. | PASS |
| LB11 | Failure after fact insertion/before floor update, or after floor update/before receipt. | Entire pair rolls back; no torn count, unreceipted apply or success. Retry revalidates fresh state. | PASS |
| LB12 | Commit succeeds but process/HTTP-equivalent response is lost. | Durable receipt resolves uncertainty; retry applies no second effects and rechecks destination/authority. | PASS |
| LB13 | New native request/ending occurs between read-only plan and apply. | REPLAN_REQUIRED, no overwriting native tail or decrementing any count/version. | PARTIAL |
| LB14 | New block/report attachment/clearance/cleanup occurs with unchanged pair version. | Full fingerprint detects the change; no removal, stale clearance or recreated event after purge. | PARTIAL |
| LB15 | Account deletion, ban, consent withdrawal or session_version increment overlaps apply. | Reviewed account/range protocol serializes or rejects; sessions/status/restrictions never restored or decremented. | PARTIAL |
| LB16 | Old approved/never-approved chat, two-ending cutoff, one-sided undo and historical clearance supplied as import facts. | No reopened grant, like, restored consent, cleared block/suppression or cutoff bypass. Clearance still needs exact live review and creator action. | PARTIAL |
| LB17 | Source report/probation fact is imported twice or during an active seven-day period. | No report intake, finding, automatic ban, new probation, extension, shortened period or allowance reset. | PARTIAL |
| LB18 | Pair counts complete but global send/receipt window or probation coverage has a gap. | Global dimension remains incomplete; no certification/activation from pair-only evidence. | PASS |
| LB19 | Capture misses a writer, hidden GET side effect, retired worker or transaction begun before B but committed later. | No valid W/coverage; INCOMPLETE until actual boundary/catch-up evidence exists. | PARTIAL |
| LB20 | Snapshot and every commit `(B,H]` include that long transaction and a deletion exactly once. | Deterministic union, no missed/double action; successful capture proof is fixture evidence only. | PARTIAL |
| LB21 | Production APP_ENV, missing guards, remote/aliased live target, misleading test DB name or wrong destination incarnation. | REJECT_DESTINATION before unsafe connection/mutation; no force/production mode. | PASS |
| LB22 | Duplicate JSON keys, unknown schema/authority fields, malformed types, huge/unbounded data, URL/SQL/path/secret/body input. | Bounded REJECT_INPUT, no execution/network fetch, private lookup or sensitive diagnostic. | PASS |
| LB23 | Artifact modified after validation; digest valid but custody/signer untrusted. | Recheck same input bytes/authority; reject rather than call an ordinary checksum completeness proof. | PARTIAL |
| LB24 | Dry-run on missing ledger rows with read-only database identity. | No ensure/backfill/schema/audit/receipt/version/mail write; deterministic plan/rejection only. | PARTIAL |
| LB25 | A terminal detail deadline already passed, or source expires between plan/apply. | No fresh retention or ordinary-event resurrection; reject expired artifact, or use only separately admissible minimal checkpoint proof. | PARTIAL |
| LB26 | Imported ending/retry clocks plus a live newer segment and old deletion job. | Original deadlines unchanged, old body stays erased and later segment preserved; no restore of text/media/report proof. | PARTIAL |
| LB27 | Receipt/detail purged or a stale restored import registry lacks counted-prefix frontier/independent deletion record. | Fail closed; no recount, old block/consent rollback, traffic opening or pretend restore success. | PARTIAL |
| LB28 | Two distinct CLI importer processes apply the same pair/bundle; two different bundles share facts. | Record both CLI PIDs and a rendezvous proving overlap. Unique receipts/facts and canonical locking yield one effect per identity; conflicting concurrent intent rejected/replanned. This alone is not HTTP-worker coverage. | PASS |
| LB29 | Importer overlaps two actual HTTP workers across request, block/review, unmatch and explicit-end variants; also exercise real deadlock/timeout separately. | Record importer CLI PID, distinct HTTP worker PIDs, pre-lock rendezvous and actual commit outcomes; exclude session serialization. A forced retry exception is not evidence of a real InnoDB deadlock. | PARTIAL |
| LB30 | One pair in a multi-pair run conflicts; another is already committed. | No partially applied pair or misleading whole-batch success; earlier committed pair preserved and rejected pair unchanged. | PASS |
| LB31 | Evidence attempts to label a legacy pair complete_from_genesis, write epoch or target a native-genesis pair. | Domain/allowlist rejection; no fabricated birth/certification or activation adapter. | PASS |
| LB32 | Rollback/recovery operator retries an old plan after newer safety events or proposes inverse SQL/full snapshot recovery. | Existing committed receipt or replan/gate; no inverse restoration of old budgets, sessions, grants or cleared restrictions. | PARTIAL |
| LB33 | Same request creation is pending at B and approved at H; compare with changed immutable sender and with missing actual transition time. | One send plus consistent observations, not two sends or a false payload conflict. Changed immutable sender is CONFLICTING; a policy-required missing action/time remains INCOMPLETE. | PASS |

## 10. Review gates, next task and executed documentation checks

| Requirement | State / actual evidence needed |
|---|---|
| Owner product policy | Settled rules preserved; no new policy answer requested. Historical acceptance instant remains unavailable. |
| Production legacy sources and original identities | UNVERIFIED; authorized minimal source inventory/custody, actual gaps and admissible mapping/coverage. L02b read repository files only; L02d inspected only owned synthetic test data. |
| Snapshot, all-writer cutover and native tail | UNVERIFIED; actual B/W/H/C, deployments/jobs/direct-access inventory, capture-method compatibility and independent observation. |
| Import implementation | L02c offline parser/planner and L02d guarded synthetic schema/facts/receipts/transactions implemented/tested locally. SQL planner inspection adapter and legacy eligibility adapter remain absent. Mixed-writer/real-deadlock and lifecycle variants stay open. |
| Qualified review/roles | REVIEW GATE: named engineering/security/privacy/safety reviewers, operators/backup and lawful purpose/retention/access review. No staffing, coverage, DPO appointment or legal conclusion is supplied. |
| Recovery and operations | Full L03 independent replay/erasure, actual restore and scheduling/alerts remain open. Migration 005/007/008 production application is unverified; actual isolated restore remains NOT RUN. |

**L02d local checkpoint:** [synthetic transaction evidence](l02d-synthetic-import-evidence-2026-10-17.md)
records 100 import assertions, 34 pure guard assertions, the 105-assertion offline
rerun and current 19/14/0 scoped LB statuses. **Next bounded task, L02e:** guarded
mixed-writer concurrency and lock/retry verification, including LB29's actual
HTTP/importer and real-deadlock variants. Actual production sources/cutover,
legacy eligibility, qualified review and independent recovery remain separate.

The [backlog](backlog.md#next-bounded-task) links this contract. Historical L02a
results remain **24 PASS, 19 PARTIAL, 2 NOT RUN**, with the exact 396/245 assertions,
run times, worker PIDs and source hashes in its [JSON evidence](evidence/l02a-2026-10-16.json).
They do not execute LB01–LB33; the separately linked L02c/L02d runs update those cases. Historical candidate CI remains head
`e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push `38546846225`, PR `38546851035`;
none is L02b implementation evidence. The old 38-link check is documentation only.

Historical L02b documentation validation PASS, observed `2026-10-16T20:13:20Z` / 22:13:20
Africa/Harare (UTC+02:00), on local Windows PowerShell/Node with repository files
only: 73 local links and five heading targets across the three changed Markdown
files; 33 unique consecutive LB IDs all NOT RUN; 12 unique writer IDs; the
illustrative JSON's syntax/non-enabling projection; unchanged 45 historical A-case
rows and 24/19/2 totals; no trailing whitespace; conditional 7+35+7=49 arithmetic
and missing-acceptance-time limitation. `git diff --check` passed. Diff review
confirmed no changes to the register, original L02a execution evidence/JSON or
code/schema/configuration. These are documentation checks, not import/functional
test execution or independent review; the observation time is not owner acceptance.

The L02b documentation task ran no database/browser suite; subsequent L02c
unit/CLI checks are recorded separately above. No push,
merge, deployment, production access, writer activation, real-data import or
real email is authorized or performed.
