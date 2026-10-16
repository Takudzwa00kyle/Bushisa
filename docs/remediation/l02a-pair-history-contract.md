# L02a pair-history and relationship contract

Continuation of `901ea76` on `codex/l02a-pair-history-20261016`.
The bounded backend slice is implemented locally. [Execution evidence and the
current A01–A45 table](l02a-implementation-evidence-2026-10-16.md) distinguish
tested behavior from remaining variants and external acceptance. This is an
engineering review, not independent privacy/security/safety review or release
approval. Takudzwa Hondova remains the product reviewer. The exact historical
owner acceptance instant was not captured; the Africa/Harare display preference
does not supply a date/time, and no settled decision is reopened.

## Authority and dependency

Requirements are the existing [decision register](decisions.md), D04 Decisions
1–19, 21–33, with Decision 27 replacing only Decision 20's no-renewed-consent
clause, and the [Q01–Q48 reconciliation](owner-policy-reconciliation-2026-10-15.md).
No new policy value, appointment, amnesty or budget reset is adopted here.

| L01a dependency | L02a responsibility | Separate gate |
|---|---|---|
| A03, A27 | Durable creation identities for retained requests; old event selection/report proof after replacement or mutable-row purge; distinct native chat segments. | Missing/purged legacy events cannot be fabricated. Remaining report combinations stay PARTIAL. |
| A06, A20 | Approved chat and pending expiry survive unmatch; proof issuance, filing and relationship mutations share serialization and current session checks. | Account-deletion/restore/media overlap belongs to L03; real browser/manual tests remain open. |
| A31 | Original-pair counts, directional declines/cooldowns, original-ender restart and second-ending cutoff. | Real legacy provenance and complete boundary/platform acceptance remain open. |
| A34, A41 | Eligible creator unblock grants chat; exact report-linked clearance, creator choice, other blocks/bans and cutoff dominate. | Real MFA, accepted reviewer assignments/coverage and qualified review remain open. |
| A08/A19/A30/A36/A38–A43 | Only the explicitly recorded text/route subcases are affected. | Full erasure/recovery, human recovery, findings/appeals, privacy and staffing are not pair-ledger completion criteria. |

## Provenance and durable state

Migration [007](../../database/migrations/007_pair_history.sql) adds a canonical
ordered original-account-pair ledger, immutable request creation identities with
terminal projections, chat segments and content-free retry receipts. It widens
request timestamps and message history deadlines to microsecond precision while
preserving existing instants. Old pair rows are `legacy_incomplete`; ending
counts in that state are known lower bounds, not reconstructed totals.
The old `history_verified_at` marker alone authorizes nothing.

Applying 007 does **not** activate complete-history certification. An immutable
writer epoch must first document that all pair writers use this contract. Only
accounts actually inserted after that cutover obtain trigger-backed genesis
records. A pair first established through the guarded writer path, with positive
account-genesis evidence and no contradictory old pair/count rows, can be
`complete_from_genesis`. Existing accounts are never retroactively stamped new.
An account-genesis/epoch/trigger failure, missing counters, known old rows or
incomplete history fails closed for new requests, restart approval and new
unblock grants. Absence of old rows is not itself proof.

There is no production activation or legacy-certification utility in this slice.
[The activation fixture](../../tests/support/activate_pair_ledger.php) is CLI-only,
requires an explicitly guarded local disposable database and accepts only a
synthetic writer reference. It cannot certify a live deployment. Actual cutover,
runtime/trigger privilege compatibility and any historical evidence/import need
their own reviewed release evidence. Existing approved legacy chat can continue
while independently authorized; a later block/revocation cannot be bypassed via
an unproved grant or a duplicate legacy match row.

## Route and mutation contract

| Route | Contract |
|---|---|
| `GET api/pairs.php?match_id=…` | Own-pair metadata only: current pair version, provenance, generation, known ending count and completeness label, own unmatch/block and restart deadline. No profile, message or new grant. An eligible never-matched target may be prepared with `user_id`. |
| `POST api/pairs.php` | `unmatch`, `restore`, `refuse_restore`; actor derives from current session, membership from server rows. |
| `POST api/swipe.php` | Fresh `like`/`pass`, current reciprocal eligibility and independent restrictions. Two fresh likes after unmatch advance the generation; an undo never does. |
| `GET/POST api/message_requests.php` | Shared request policy, retained own-event metadata and report proof. Optional read `event_key` selects only a retained event in that own match. |
| `POST api/safety.php` | Ordinary block/unblock uses versioned pair intent; unblock also names the exact current own `block_id`. Report intake retains its separate L01b operation identity and versioned reviewer clearance. |
| `api/messages.php`, `api/matches.php`, `chat.php`, `matches.php` | Shared current authorization, closed-history deadlines and approved segments; no legacy form write/preview bypass. Existing UI development gates remain. |

Every mutation uses authenticated current session version, CSRF, server time and
the shared database advisory lock plus transaction. The lock is acquired before
the transaction; PHP session locks are released before HTTP overlap rendezvous.
Unmatch/block writers and report intake use the same ordering. This deliberately
coarse lock bounds this implementation; load/scale review remains necessary.

Pair mutations require a 64-character random hexadecimal `idempotency_key` and
integer `pair_version`. The canonical intent excludes CSRF/transport ordering.
Same committed key/intent replays a content-free receipt; changed intent is 409.
A first delivery with stale pair version is 409. A receipt expiring or being
physically purged never makes its old version eligible again. No cached receipt
authorizes current chat/profile access. The discovery adapter retains a retry's
key/version on its original card; it does not silently refresh stale consent.

## Approved transitions

Requests keep the seven-day elapsed UTC expiry, sender 3/12h or fixed reported
probation 3/24h, recipient 10/12h, and directional original-pair lifetime 3.
Cancellation/expiry establishes a durable directional 12h deadline, anchored to
the actual cancellation or stored expiry, even if the current request row is
later overwritten/purged. The recipient can decline; a recipient cannot disguise
that as requester cancellation. Reverse requests do not erase the other decline.
Only a genuinely new mutual match clears declines, and clears nothing else.

An explicit approved-chat ending records its actor and time once, closes that
segment, disables sending and starts its seven-day read-only deadline. Only that
ender may send the next restart request, after 12h and while otherwise eligible;
the other participant still approves. A second ending cuts off requests/grants
across every later generation of the same original account pair. Ordinary block
cycles and unmatching never count as explicit endings.

Unmatching clears both active swipe/like rows and the active discovery relation.
Approved chat continues and pending expiry is unchanged; pending approval still
requires a matched state. Each unmatcher must undo their own action, and both
participants must freshly agree. Either may refuse. A new unmatch invalidates
the accumulated restoration agreements. Only return to matched state consumes
the one-success-per-rolling-24h restoration allowance; no cap limits reporting,
blocking or withdrawal. Restoration keeps generation, declines, counts, endings
and request clocks. Automatic recommendations exclude the pair for seven days;
current eligibility/restrictions still apply to later rediscovery.

Only the block's creator removes it. Every report attachment requires clearance
of the current exact case/submission/block scope by its assigned MFA-authorized
reviewer; stale review scope cannot clear a new attachment. Clearance never
removes the block. The creator's eligible unblock grants chat immediately,
including never-approved chat, without spending a new request. No block removal
overrides the other person's block, a ban/account restriction, unknown necessary
history or the second-ending cutoff. Removal of a personal block may succeed
while an independent restriction still denies chat; it is not an access receipt.
Report acceptance remains an allegation, not a finding or automatic ban.

## Retention and remaining acceptance

Request events contain IDs, generation, sender/recipient, status and UTC clocks,
never message bodies. Terminal events/ordinary requests expire within 30 days
of the actual terminal event; delayed expiry processing uses the original stored
expiry. Retry receipts have a bounded 30-day technical retention, subject to
privacy review, and never reset the pair version. Minimal counts, declines,
cooldowns and ending/cutoff state remain separate for original-pair enforcement.

Closed message access stops at its immutable seven-day deadline. The bounded
text cleanup selects per-message deadlines, not a whole match, so old cleanup
cannot delete new-session messages. Separately preserved report evidence is not
ordinary chat history and retains its own scoped hold/expiry. This implements no
media/object purge, full account lifecycle, independent recovery store or actual
restore. Those L03 gates, schedules/alerts and qualified retention review stay
open. Pair deletion must eventually follow the bounded recovery record; this
slice does not authorize indefinite history after account deletion. Conditional
7-day live erase +35-day backup +7-day recovery margin remains 49 days only under
those assumptions, not a universal setting.

The [L02b evidence/import contract](l02b-legacy-history-import-contract.md) is now
prepared, with LB01–LB33 all NOT RUN. It separates evidence completeness from
runtime eligibility; no imported fact can enable a legacy pair. The next bounded
implementation is **L02c: offline validation and a guarded read-only synthetic
planner**, without apply or legacy activation. Later synthetic apply/schema,
UI/manual restoration/block projections and remaining matrix variants need evidence.
No settled owner policy requires reapproval. Qualified reviewers, real MFA,
accepted operators/coverage, production writer capture and L03 replay remain
release gates, not fictional appointments. No push, merge, deployment, live
database, production activation or real email is authorized by this checkpoint.
