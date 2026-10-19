# L01b text-report implementation and local evidence

**Historical implementation at 901ea76.** The [L02a continuation and current individual acceptance status](l02a-implementation-evidence-2026-10-16.md) supersede the current-task/status pointers below. All original run timestamps, source hashes, CI attribution and unexecuted claims in this record remain historical.

This bounded slice continues f7e9f8c76e043a3dd3bc7bc3edc271ae3d584bbc on
codex/l01b-report-intake-20261016. The starting checkout was clean.
No applicable AGENTS.md was found in the checkout or ancestors. No work was reset.

**Implemented and verified locally for the text/API slice; independent review and
production readiness remain open.** Decisions 28–33 are requirements, not new
approvals. Their historical acceptance instant was not captured; no execution or
drafting time substitutes for it. Takudzwa Hondova remains product reviewer.

## Engineering review

| Area | Implemented result | Remaining limit |
|---|---|---|
| Target proof | Authorized discovery, message, own request-state and confession responses issue encrypted AES-256-GCM reporter-bound contexts containing selectors/version/provenance, never message text. No raw-ID mint endpoint or backend exposure-history archive. | Qualified privacy/security must accept the fields, key lifecycle and actual readers before production storage is enabled. |
| Source/actor | Scoped membership/container checks precede private-body lookup. Revision triggers detect direct text/author edits. Reference targets use the actual reference row and quoting parent/author, never reconstruct its source. Match conduct selects one proven participant; unknown/disputed attribution is accepted without guessed probation. | Unknown legacy chat segments allow only individual metadata reports, not text/neighbor capture. Missing physical sources/identity boundaries or overwritten request-event generations fail closed; L02/L03 must provide historical proof. |
| Authorization | Own-primary rejection, authorized own supporting messages, bounded same-segment context, unavailable projections and opaque own receipts. No receipt contains source text, private author, other reporter, raw case/evidence ID or media link. Reporting restores no access. | Human recovery, uploads/export and complete moderation UI remain separate. New cases cannot use the legacy admin-plus-reason evidence exception. |
| Deduplication | Reporter/key HMAC uniqueness spans mutating actions. Canonical intent excludes CSRF/transport ordering and normalizes equivalent proof envelopes to selectors. Changed intent conflicts. Reporter/object/version/event anchors separate new reports from information updates. Own-case links copy no evidence or permissions. | Unsupported stored versions/missing required keys fail closed. Retain unexpired lookup keys or rekey safely. No indefinite replay promise after lawful purge. |
| Atomicity | Per-database MySQL advisory lock plus transaction commits case, submission, evidence, probation, block effects and outcome together. Source locks serialize unsend/capture. Success follows commit. One bounded SQLSTATE 40001 retry reauthorizes with the invocation's original server receipt. | Coarse-lock throughput, managed-service compatibility and all legacy writer/read ordering need review. A fully rolled-back attempt has no accepted report/outcome; a client retry needs the same intent key and fresh authority. |
| Probation | Server receipt precedes barrier/lock wait. Activation/end differ by 168h. Active-period receipts/addenda never extend it, including validation/retry after expiry. New qualifying post-expiry events start one fresh period; committed retries cannot. Case remains pending; no automatic ban. | Human sanctions/appeals and conditional service targets are NOT RUN. No early normal-rate restoration path. |
| Request allowance | Successful sends enforce sender 3/12h or probation 3/24h, recipient 10/12h, directional lifetime pair 3. Window timestamps expire outside 24h; minimal pair counts survive. Seven-day pending expiry and 12h cancellation/expiry cooldown replace obsolete provisional values. Directional decline survives reverse requests. | Only known current requests/declines are backfilled. Every new request requires pair-history verification; migration leaves that marker unset. Guarded fixtures explicitly attest fresh synthetic pairs. L02 must establish real provenance before enabling creation; absence of a count/history record is never a fresh allowance. Genuine-new-match decline clearance, undo, endings/cutoff and eligible immediate-chat unblock need L02. Unverified revoked-chat restart fails closed. |
| Block removal | Every uncleared exact case/submission/block link prevents creator removal. Current assigned moderator/admin with server-authenticated MFA explicitly clears the exact HMAC-bound review scope; a newly attached submission invalidates stale clearance; block remains until its creator separately removes it. Other blocks/account restrictions remain independent. | Tests use trusted synthetic session fixtures, not real MFA enrollment or staffed review. Approved eligible-unblock chat effect/cutoff remains an L02 acceptance gate. |

Key interface: REPORT_CONTEXT_KEYS maps key IDs to base64 32-byte keys;
REPORT_CONTEXT_ACTIVE_KEY selects issuance. Missing/invalid keys or migration
make intake unavailable and readers omit usable contexts. No production key or
configuration was read or changed. Test keys are generated in memory. Never log
keys, proofs, report text or token-bearing URLs.

POST api/safety.php supplies report, block_and_report, report_addendum,
report_receipt and assigned-MFA block_report_review/clear_block_report. The review read returns a reviewer/session-bound scope version required for clearance. Mutating report intents need
a random 256-bit hexadecimal idempotency_key. Existing block/unblock share the
removal guard. Confession flag now submits an allegation without setting a global
flag; its browser caller keeps an in-memory retry key and shows acknowledgment.
**Browser interactions/reload-loss UX and the remaining report UI are NOT RUN.**

Unmatch alone preserves approved chat and pending requests; approval of a pending
request still requires a match. Message GET omits unsent entries. Unsend erases
ordinary text and retains only evidence already captured for an existing case.
This does not claim the whole L02/L03 lifecycle is implemented.

## Migration and retention

006_report_intake.sql is additive; CLI-only php/report_migration.php adds revision/
segment/source-version columns and triggers. bin/migrate.php --through=005 enables
a real pre-006 upgrade rehearsal. Existing rows are preserved; no retrospective
probation or silent legacy-case deadline replacement. Runtime DDL stayed disabled.
**Neither 005 nor 006 is certified production-applied.**

| Data | Minimum fields/purpose | Access and cleanup |
|---|---|---|
| Incident/submission/operation | Protected reporter/actor, target/version anchor, reason/explanation, receipt, keyed intent/key digest, original receipt, outcome reference | Current own authority only. Final resolution +180d, or +30d wholly unrelated/accidental, denies access and purges explanation/content-dependent hashes. Content-free references end at final resolution +365d. |
| New-case evidence | Authorized exact source/version and necessary context, case/item/capture metadata | Assigned current MFA reviewer; content-free audit. Ordinary expiry enforced even before cleanup. Text/hash purged at its immutable deadline except a specifically held item. No old creation +24-month default. |
| Hold interface | Case/item, authorized-record reference, immutable start/end/release | Reserved for L03's reviewed joint/urgent/legal workflow; no hold-grant endpoint here. Synthetic holds prove only scope, expiry and release. Release of already-expired text creates no new clock. |
| Probation/block links | Trigger/actor, immutable start/end; exact block/case/submission and clearance | No guessed actor or student disclosure. Active protections survive retries and unilateral removal. Full account/copy/protective-record cleanup and independent replay need L03. |
| Window/pair ledger | Successful sender/recipient/time; separate lifetime count, decline state and history-verification instant | Window data purged once outside 24h. Minimal pair state lasts for the original account pair, with deletion/recovery integration gated by L03. Reverse request/undo cannot clear a decline. |
| Audit/review links | Actor, case, fixed action/outcome/time; own-case link without content | Audit 365d from event; case links end at the earlier applicable decision expiry. No case text in routine failure logs. |

bin/cleanup_policy.php now invokes new-intake/text cleanup. Full legacy-case,
account/media/object cleanup, actual hold authority, scheduler/alerts and recovery
remain open. Access denial, live erasure and protected-copy expiry stay separate.
Copy settings remain proposals. Conditional **7d live erasure +35d recoverable
database copies +7d recovery margin =49d** remains an example, not a universal
constant or a measured restore. See the [inventory](data-inventory-retention-matrix.md).

## Exact local evidence

Final run: **2026-10-16T16:39:46Z to 2026-10-16T16:40:16Z UTC**;
**2026-10-16 18:39:46 to 2026-10-16 18:40:16 Africa/Harare (UTC+02:00)**. These are execution timestamps.

- PASS: **245 assertions** and eight barrier scenarios. The
  [JSON evidence](evidence/l01b-2026-10-16.json) contains every check, environment,
  barrier result and LF-normalized SHA-256 source manifest.
- Windows; PHP **8.5.11 NTS**; isolated MySQL **9.6.0**, loopback
  127.0.0.1:3319; guarded bushisa_test_l01b and bushisa_test_l01b_upgrade.
  These are not Azure runtime reports or production-upgrade approval.
- Two separate loopback PHP built-in servers on ports 8097/8098; actual barrier
  worker PIDs **25212 and 25388**. Same-key, same-incident/different-key, independent
  reporters, report/unsend, delayed receipt, renewal and two budget races each
  recorded two distinct HTTP workers. Six pairs returned 200/200; each budget
  race returned one 200 and one 429. No production worker-pool/load claim.
- M01–M08: fresh 001–006, rerun skip, actual 001–005 then 006 upgrade, unchanged
  user/message/legacy-case values, once-only known request backfill and direct-edit
  revision trigger. Preserving legacy values does not approve their policy.
- Injected SQLSTATE 40001 with a two-second delay proves internal retry keeps the
  original receipt across expiry. It is not an actual InnoDB deadlock cycle.
  A separate last-write fault proves rollback of all new effects while preserving
  a pre-existing block, followed by successful same-key retry.
- PASS: all 14 changed/new PHP syntax checks, Node vm.Script syntax for the
  confession adapter, unit_disposable_database_guard.php and the existing
  policy_safeguards_p0_flows.php on separate bushisa_test_l01b_regression with
  MAIL_PROVIDER=fake. The latter's fake-mail/restore-guard rows are not real mail
  or an actual restore.
- PASS: 38 local links including 9 heading references, one occurrence each of A01–A45 in both matrices (19 PASS, 23 PARTIAL, 3 NOT RUN), source manifest and git diff --check.
  These are documentation/static checks, not additional functional acceptance.

Re-run tests/integration_l01b_reports.php only with explicit APP_ENV=development,
APP_DEBUG=false, BUSHISA_ALLOW_RUNTIME_DDL=false, BUSHISA_TEST_DATABASE=true,
loopback DB_HOST/DB_PORT, disposable DB_NAME and test DB_USER/DB_PASSWORD.
Use DB_REQUIRE_TLS=false only for this isolated local test instance and
MAIL_PROVIDER=fake. The runner validates before reset/connection, refuses occupied
HTTP ports, creates only synthetic accounts and stops its own HTTP servers.
Fixture timestamp edits simulate elapsed periods; no application API exposes them.

## A01–A45 current status

PASS covers the executed text/API scenario. PARTIAL lists passed subcases and
the explicitly **NOT RUN** remainder. No whole case passes just from historic CI.

| ID | Result | Evidence / NOT RUN remainder |
|---|---|---|
| A01 | PASS | Actual discovery card, account report without match, no grant. |
| A02 | PASS | Authorized message/own context, scoped evidence, opaque receipt. |
| A03 | PARTIAL | Unmatched inbound pending/terminal event and unchanged expiry; purged/overwritten history needs L02. |
| A04 | PASS | Returned public confession, hidden author, no global flag/finding. |
| A05 | PASS | Actual reference/quoting author with erased source; no reconstruction. |
| A06 | PARTIAL | Shared unmatch preserves grant and HTTP history/report access; send/UI variants NOT RUN. |
| A07 | PARTIAL | Pre-block proof, attached own block, denied chat; opposite-direction report variant NOT RUN. |
| A08 | PARTIAL | Actual unsend/disappearing entry/metadata report; media/device variants NOT RUN. |
| A09 | PASS | Missing physical item denied; separately selected own pair fallback. |
| A10 | PARTIAL | CSRF, suspended/revoked sessions, assignment/MFA checks; deleted-reporter/real MFA variants NOT RUN. |
| A11 | PASS | Foreign/private and absent IDs have identical unavailable projection; no timing guarantee claimed. |
| A12 | PASS | Another reporter's proof and submitted attribution rejected. |
| A13 | PARTIAL | Wrong generation/cross-segment denied; all quote-parent/source substitutions NOT RUN. |
| A14 | PASS | Another reporter's receipt unavailable. |
| A15 | PARTIAL | Raw ID/no proof denied; explicit forged legacy view-row fixture NOT RUN. |
| A16 | PASS | Changed version does not capture later text. |
| A17 | PASS | Proof/source expiry enforced before purge, equality denied. |
| A18 | PASS | Erased source/retry and existing own-case link copy no evidence. |
| A19 | PARTIAL | Both serial unsend orders and worker overlap; account-deletion overlap NOT RUN. |
| A20 | NOT RUN | Joint proof-issuance/block/unmatch/session-revocation overlap needs L02/L03. |
| A21 | PARTIAL | Own context, cross-segment/11-preceding rejection and rollback; all following/new-arrival variants NOT RUN. |
| A22 | PARTIAL | Unicode ceiling, self-primary and authority/path rejection; exhaustive UTF-8/alias/upload variants NOT RUN. |
| A23 | PASS | Receipt after erasure/active-key rotation, equivalent proof, unsupported stored version fails closed. |
| A24 | PARTIAL | Two HTTP workers, rollback and forced 40001 retry; real InnoDB deadlock cycle NOT RUN. |
| A25 | PARTIAL | Explanation/action reuse conflicts; every transport/CSRF-only/changed-field variant NOT RUN. |
| A26 | PASS | Concurrent different keys, one reporter/anchor and no repeated information. |
| A27 | PARTIAL | Independent item/request-state events; complete old/new segment and purged-history variants need L02. |
| A28 | PASS | Independent reporters/cases/private receipts and one initial period. |
| A29 | PARTIAL | Authorized new item/addendum and own-case link; reviewed post-resolution reopening NOT RUN. |
| A30 | PARTIAL | Revocation, expired receipt, 180d/30d purge; every 365d metadata-purge variant NOT RUN. |
| A31 | PARTIAL | Actual probation sends, normal restoration, concurrent sender/recipient caps, pair lifetime after timestamp purge, reverse decline preserved; missing-history rejection passed; real old-history verification/all exact edges NOT RUN. |
| A32 | PARTIAL | Active reports/addenda keep start/end; dismissal/appeal workflows NOT RUN. |
| A33 | PASS | Committed old trigger cannot renew; new post-expiry report does. |
| A34 | PARTIAL | Combined retry after cleared creator removal never reblocks; full pending-clearance/eligible chat grant/cutoff acceptance needs L02. |
| A35 | PARTIAL | Invalid attachment and last-write fault roll back effects, preserve old block; fault at every statement NOT RUN. |
| A36 | PARTIAL | Text expiry/scoped hold/release/no-reset/access cutoff; actual restore/media/copy/replay/hold authority NOT RUN. |
| A37 | PASS | All five families resolve actual selected actor; allegation is not a ban. |
| A38 | PARTIAL | Null/disputed author, no guessed probation/unseen revision; deleted/protected-reference variants NOT RUN. |
| A39 | PARTIAL | Own primary denied, own supporting context allowed; human recovery NOT RUN. |
| A40 | PASS | Exact boundary helper, HTTP wait and internal retry preserve receipt; no exact-microsecond HTTP arrival claim. |
| A41 | PARTIAL | Assigned MFA, stale-scope rejection after a new attachment, creator action and independent reverse block; real MFA/coverage and eligible chat grant/cutoff remain gates. |
| A42 | NOT RUN | Human severity findings, bans and independent appeal/reversal outside this slice. |
| A43 | NOT RUN | Real staffing, target/overdue escalation and capacity outside this slice. |
| A44 | PASS | New item in existing case qualifies; narrative update does not. |
| A45 | PASS | Concurrent post-expiry reports create one fresh fixed period. |

## Remaining gates and next bounded task

**Owner policy:** no P1–P6 answer is outstanding. Settled consent and Reply-To
remain settled. Historical acceptance timestamps remain unavailable. No new
policy, default or appointment is accepted here.

**Engineering:** New request creation stays gated on verified pair provenance. L02 needs immutable pair/request/ending history and verified
legacy backfill, genuine-new-match decline-only clearance (never undo), original
ender restart after 12h, second-ending cutoff across rematches and approved
immediate chat on eligible blocker unblock. Audit legacy preview readers, shared
writers and session boundaries. L03 needs account/media/hold/deletion jobs and an
independent protective replay record, including bounded pair-state cleanup.
Run remaining matrix subcases, real runtime/trigger privilege compatibility and
browser/manual tests. No clean history is inferred from missing records.

**Named review/operations:** qualified privacy/security/safety and independent
appeal reviewers, real MFA, accepted operators/backups, monitored recovery/support,
cleanup schedules/alerts and measured coverage are open. Synthetic accounts
appoint nobody. D04/D05/D12 and operational release gates remain open.

**Next bounded implementation: L02a pair-policy event ledger and eligible
restart/unblock integration**, on guarded disposable systems using existing
approvals without resetting history. Separate slices supply human recovery,
sanctions/appeals, staffing and full L03 replay. Engineering can proceed without
reopening P1–P6; qualified sign-off and real staffing still require actual people.

Changed code/test paths are the 16 entries in the JSON manifest; accompanying
docs are this record, its JSON, [L01a](l01a-report-route-target-contract.md) and
the [backlog](backlog.md). Existing CI remains head
e30dcf844abaf499bcc657172c2c9aa1f4b4986d, push run 38546846225 and PR run
38546851035; none is L01b evidence. Actual isolated restore is NOT RUN.
No production configuration, DNS, secrets, billing, live database, real email,
push, merge or deployment was used.
