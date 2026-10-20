# L02a implementation and local evidence

Continuation of 901ea76 on codex/l02a-pair-history-20261016. Engineering review and bounded implementation are complete locally; independent review, operational readiness and release acceptance remain open. The [pair contract](l02a-pair-history-contract.md) defines provenance, routes and transition rules. The [machine-readable evidence](evidence/l02a-2026-10-16.json) records each executed assertion, source hash, worker PID and result.

No new owner decision is requested. Decisions already recorded, including 27–33, remain unchanged. Historical exact owner acceptance UTC/Harare timestamps were not captured and are not reconstructed from drafting or test times.

## Dependency and engineering review

| Finding at 901ea76 | L02a result | Remaining boundary |
|---|---|---|
| Current request rows were overwritten; old pair history was uncertified. | Additive 007 supplies ordered original-account-pair state, retained immutable request creation identities, native grant segments and versioned retry receipts. Old rows stay legacy_incomplete. | No real legacy certification or invented missing events. Original accounts need actual provenance before new requests/restart approval/grants. |
| Migration/genesis alone could be mistaken for proof that every worker used the ledger. | Migration leaves the writer epoch inactive. Only post-cutover account inserts obtain positive genesis records; the local activation fixture is CLI-only and disposable/local guarded. Missing epoch/genesis/trigger/counters rejects new privileges. | Actual all-writer cutover, deployment/runtime/trigger capture and a production activation procedure remain release work; no production activation performed. |
| Restart, undo/new-match and eligible-unblock paths were incomplete. | Original-ender 12h restart, second-ending cutoff across generations, fresh mutual restoration/own undos/rolling 24h cap, decline-only genuine-rematch clearing and eligible creator-unblock grants are integrated. | Qualified safety/security review, remaining UI/manual projections and complete legacy evidence remain open. |
| Legacy chat form writes and preview queries bypassed the API authorization. | API and server pages now use shared current-session/account/block/grant/history authorization. Actual message reads and pair/report writes serialize through the shared transaction. Legacy duplicate match rows cannot bypass pair-wide suppression/cutoff. | Browser/manual acceptance and scale/load review remain open; existing UI development gates were retained. |
| Whole-second columns rounded immutable microsecond deadlines. | Forward precision widening preserves existing instants and aligns request expiry/cooldowns and closed-message deadlines. Old zero-fraction segment identity is retained. | Azure service/version/trigger-privilege compatibility remains VERIFY. Local MySQL 9.6 is not production evidence. |

An accepted report still means a valid allegation/attribution, never a proven violation or automatic ban. Report-linked block clearance remains bound to its exact current case/submission/block scope; clearance does not remove the block. Neither a retry nor another block/rematch resets budgets, probation, endings or an immutable deadline.

## Exact execution evidence

Code was tested on Windows PHP 8.5.11 NTS and an owned, isolated MySQL 9.6.0 server bound to 127.0.0.1:3320. All databases use explicit disposable names and guards before bootstrap/connection/reset; APP_ENV=development, APP_DEBUG=false, runtime DDL disabled, MAIL_PROVIDER=fake. No messages were queued. No live database or real mail was used.

| Check | Exact result/environment |
|---|---|
| L02a route suite | 396 assertions PASS; 2026-10-16T18:45:36+00:00 to 2026-10-16T18:46:44+00:00; 2026-10-16 20:45:36 Africa/Harare (UTC+02:00) to 2026-10-16 20:46:44 Africa/Harare (UTC+02:00). 11 overlaps, actual HTTP PIDs 17928/24804. Database bushisa_test_l02a. |
| Report regression suite | 245 assertions PASS; 2026-10-16T18:46:08+00:00 to 2026-10-16T18:47:28+00:00; 2026-10-16 20:46:08 Africa/Harare (UTC+02:00) to 2026-10-16 20:47:28 Africa/Harare (UTC+02:00). 8 overlaps, actual HTTP PIDs 8220/26260. Database bushisa_test_l02a_reports. |
| Fresh migration | Actual 001–007 application on guarded fresh schema, then a second run. |
| Existing-schema upgrade | Actual 001–006 schema with pre-007 synthetic users/current request/text; 007 preserves users, text and the request instant. Second migration run creates no duplicate/backdated genesis or pair certification. |
| Writer provenance | Migration alone never attests writers; synthetic activation timestamp is immutable on rerun, accounts before activation remain uncertified; lost positive evidence/old verified marker cannot confer eligibility. |
| Policy and matching regressions | policy_safeguards_p0_flows PASS; integration_core_p0_flows PASS; unit_disposable_database_guard PASS. Guarded bushisa_test_l02a_regression, exec session 37262; exact start/end were not captured. |
| Static checks | 23 changed PHP files lint PASS; discovery JavaScript Node vm.Script syntax PASS. Syntax checks are not browser execution. |
| Source attribution | LF-normalized SHA-256 matches 31 unique code/schema/test sources across both run manifests; all 25 changed implementation/test sources match the final L02a run. |

The two independent server processes in each HTTP suite share its database/session directory; barrier evidence records actual PIDs and response codes. Same-session requests close the PHP session before rendezvous. The session-revocation case holds the exact shared advisory lock until both worker rendezvous records exist, commits revocation, then releases it before either authorization proceeds. No single-worker sequential simulation is described as overlap.

Observed overlap families: same-key request and explicit-end retries; competing end intents; concurrent restore consent; proof/block, proof/unmatch and filing/unmatch; proof/filing versus revoked session; different-key request creation; report attachment versus cleared creator-unblock; and fresh reciprocal likes. The report regression retains its eight separately recorded overlap scenarios. Forced retry evidence remains distinct from an actual InnoDB deadlock cycle, which is NOT RUN.

Boundary fixtures use server UTC and explicitly accelerated synthetic elapsed timestamps. They do not claim a real 7/12/24-hour wait or exact-microsecond HTTP arrival. Covered edges include original seven-day expiry, delayed expiry processing, 12h cancellation/expiry and original-ender restart, 24h restoration cap, three lifetime sends, 30-day terminal expiry, seven-day closed reads and stale receipts/versions after purge.

## Retention and migration limits

The ledger retains minimal original-pair counts/decline/cooldown/ending state independently of mutable request/window rows. Terminal request metadata uses the actual terminal/expiry +30d deadline, never cleanup time. Bounded retry receipts expire without weakening the original version check. Read-only closed text stops at its seven-day deadline; the text worker erases only selected old message rows/segments. Actual tests prove later-session messages survive. Existing private report evidence is separate and retains its own scoped category/hold rules.

Production migration 005 or 007 application is **UNVERIFIED**. An actual isolated restore rehearsal is **NOT RUN**. No production writer epoch was activated. Full account/media erasure, protected backup expiry, independent safety/deletion/consent replay, hold authority and cleanup schedules/alerts remain L03/readiness gates. The conditional 7+35+7=49-day recovery arithmetic is unchanged; no platform retention or global recovery constant is approved here.

## A01–A45 current status

Current totals: **24 PASS, 19 PARTIAL, 2 NOT RUN**. A03, A06, A07 and A34 move from PARTIAL to PASS; A20 moves from NOT RUN to PASS. PASS covers the executed text/HTTP acceptance observation. PARTIAL explicitly keeps its unexecuted variants open. Unchanged rows retain the report suite’s individual evidence; policy documentation/old CI never substitutes for execution.

| ID | Result | Evidence / NOT RUN remainder |
|---|---|---|
| A01 | PASS | Actual discovery card, account report without match, no grant. |
| A02 | PASS | Authorized message/own context, scoped evidence, opaque receipt. |
| A03 | PASS | L02a A03: pending/terminal proofs while unmatched; old event survives replacement/current-row purge; expiry and missing history fail closed. B01/R02 preserve original request expiry. |
| A04 | PASS | Returned public confession, hidden author, no global flag/finding. |
| A05 | PASS | Actual reference/quoting author with erased source; no reconstruction. |
| A06 | PASS | L02a A06: actual API read/send and server-rendered chat after unmatch, retained-content report, no new grant/ending. Browser/manual interaction remains a separate gate. |
| A07 | PASS | L01b own-block attachment plus L02a incoming-block report; no unseen later revision capture, restored access or removal of the other block. |
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
| A20 | PASS | L02a A20: distinct-worker proof/block, proof/unmatch, filing/unmatch overlaps; held shared lock proves current session revocation precedes both rejected decisions. No account-deletion or restore claim. |
| A21 | PARTIAL | Own context, cross-segment/11-preceding rejection and rollback; all following/new-arrival variants NOT RUN. |
| A22 | PARTIAL | Unicode ceiling, self-primary and authority/path rejection; exhaustive UTF-8/alias/upload variants NOT RUN. |
| A23 | PASS | Receipt after erasure/active-key rotation, equivalent proof, unsupported stored version fails closed. |
| A24 | PARTIAL | Two HTTP workers, rollback and forced 40001 retry; real InnoDB deadlock cycle NOT RUN. |
| A25 | PARTIAL | Explanation/action reuse conflicts; every transport/CSRF-only/changed-field variant NOT RUN. |
| A26 | PASS | Concurrent different keys, one reporter/anchor and no repeated information. |
| A27 | PARTIAL | L02a A03/A27/B04-B05: durable overwritten/purged request events and native old/new message segments; full independent report-case combinations across all native segments/generations NOT RUN. |
| A28 | PASS | Independent reporters/cases/private receipts and one initial period. |
| A29 | PARTIAL | Authorized new item/addendum and own-case link; reviewed post-resolution reopening NOT RUN. |
| A30 | PARTIAL | Revocation, expired receipt, 180d/30d purge; every 365d metadata-purge variant NOT RUN. |
| A31 | PARTIAL | Both suites: sender/recipient/probation limits, directional lifetime three across undo/rematches and timestamp purge, durable cooldowns, original-ender restart/cutoff and legacy fail-closed. Real legacy audit/certification/import and all managed-service/exact-boundary variants NOT RUN. |
| A32 | PARTIAL | Active reports/addenda keep start/end; dismissal/appeal workflows NOT RUN. |
| A33 | PASS | Committed old trigger cannot renew; new post-expiry report does. |
| A34 | PASS | L02a A34/B06-B09: combined pending clearance, exact reviewer clearance plus creator removal, immediate eligible chat without a new request, unchanged probation and receipt-only replay; cutoff remains dominant. |
| A35 | PARTIAL | Invalid attachment and last-write fault roll back effects, preserve old block; fault at every statement NOT RUN. |
| A36 | PARTIAL | Existing scoped text holds/expiry plus L02a B14-B16/L08/L13 text/event cleanup preserve new sessions and lifetime state. Actual restore, media/copy/replay and full hold-authority workflow NOT RUN. |
| A37 | PASS | All five families resolve actual selected actor; allegation is not a ban. |
| A38 | PARTIAL | Null/disputed author, no guessed probation/unseen revision; deleted/protected-reference variants NOT RUN. |
| A39 | PARTIAL | Own primary denied, own supporting context allowed; human recovery NOT RUN. |
| A40 | PASS | Exact boundary helper, HTTP wait and internal retry preserve receipt; no exact-microsecond HTTP arrival claim. |
| A41 | PARTIAL | L01b exact assigned-MFA/stale-scope guards plus L02a concurrent report/unblock, creator choice, immediate eligible grant, independent reverse block, suspension and cutoff. Real MFA enrollment, qualified review/coverage NOT RUN. |
| A42 | NOT RUN | Human severity findings, bans and independent appeal/reversal outside this slice. |
| A43 | NOT RUN | Real staffing, target/overdue escalation and capacity outside this slice. |
| A44 | PASS | New item in existing case qualifies; narrative update does not. |
| A45 | PASS | Concurrent post-expiry reports create one fresh fixed period. |

## Remaining gates and next bounded task

**Owner policy:** no settled decision needs reapproval. Exact historical acceptance timestamps remain unavailable. Named qualified reviewers/operators/backups and accepted real coverage are still required owner/operational information; this implementation appoints nobody.

**Engineering:** preserve fail-closed legacy history until actual counts, declines, endings, grants and generation provenance can be proved. L02b is a guarded legacy-history evidence/import contract with synthetic complete/incomplete manifests, repeat/concurrent import and no fabricated events. Remaining native-segment report combinations, browser/manual restoration/block projections, runtime/privilege/load acceptance and the individually listed matrix variants still need evidence.

**Qualified/operational release gates:** privacy/security/safety review; real MFA and independent appeal reviewers; accepted staffing/support/recovery contacts and measured capacity; all-writer production cutover capture; full L03 erasure/media/hold/recovery work and independently witnessed restore. These are separate from owner-approved policy and do not remove any D03 feature.

Historical candidate CI remains exactly head e30dcf844abaf499bcc657172c2c9aa1f4b4986d, push run 38546846225 and PR run 38546851035. None is L02a CI. No CI run for this branch is claimed.

No push, merge, deployment, production configuration/DNS/secrets/billing changes, production activation, live database access or real email occurred. All owned HTTP workers ended in their suite finally blocks. The owned MySQL process 8040 was identity-checked against its synthetic data directory/port and sent shutdown at 2026-10-16T18:54:30Z and exited normally at 18:54:32Z (20:54:32 Africa/Harare); ignored synthetic data directories remain.

## Changed files and documentation validation

The 25 code/schema/test paths are listed individually in the JSON source manifest. Documentation changes are this file, its JSON, the new pair contract and focused continuation links/status in the backlog, L01a contract and historical L01b evidence. No decision register or unrelated policy rewrite was needed.

Documentation validation PASS: 53 local links, 10 heading targets, 45 unique acceptance IDs and 24/19/2 status totals; request/ending retention-clock consistency and conditional 7+35+7=49 arithmetic. These are documentation checks, not functional tests. The original 38-link L01a check remains historical documentation validation. Final git diff --check is recorded with the commit checkpoint.
