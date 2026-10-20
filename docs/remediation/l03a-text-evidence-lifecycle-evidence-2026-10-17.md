# L03a: new-intake text expiry, scoped holds and recovery interfaces

Local implementation continued from `b521b678e307e06097b2b60c7bbeb333e93bf32e`
on `codex/l03a-text-evidence-lifecycle-20261017`. The starting index/tree were
clean. No applicable AGENTS.md was found in the checkout or its ancestors.
This checkpoint contains implementation and guarded synthetic execution, not
production application, independent review or release acceptance.

Authority: [decisions](decisions.md), [Q01–Q48 reconciliation](owner-policy-reconciliation-2026-10-15.md),
[retention matrix](data-inventory-retention-matrix.md), [retention policy](../data_retention_policy.md)
and the [L03 inventory/acceptance plan](l03-category-clock-writer-inventory.md#5-next-bounded-implementation-l03a-text-evidence-expiry-and-scoped-holds).
Decisions 28–33, full D03 scope, fresh mutual restoration, temporary unset
Reply-To and all other recorded approvals are unchanged. Takudzwa Hondova
remains product reviewer. The exact historical acceptance instant was not
captured; neither these execution times nor this document date substitutes for it.

## Result and limits

The approved new-intake text/hold core is implemented locally. The separate
**LC01–LC18 matrix is 13 PASS / 5 PARTIAL / 0 wholly NOT RUN**. Each partial case
below identifies its unexecuted dependency. All 18 have executed synthetic checks.
PASS here means the stated local engineering variant, never operational staffing,
legal authority, deployed compatibility or independent durability.

Both profiles pass **122 lifecycle assertions**, **seven observed overlaps**,
**13 preconnection guard checks**, and affected regressions of **245 report
assertions / eight HTTP overlaps** and **396 pair assertions / eleven HTTP
overlaps**. The seven lifecycle observations comprise six two-process shared-lock
overlaps and one actual engine row-lock timeout. The latter is **1205**, exhausted
at three attempts, followed by successful retry; it is not a 1213 deadlock.

The [machine-readable evidence](evidence/l03a-2026-10-17.json) contains original
per-profile execution times, commands, case-labelled assertions, PIDs, SQL
connection IDs, observed pending locks/row waits, grants, migration results and
matching source hashes. It includes only synthetic identities and content-free
diagnostics. Historical [A evidence](l02a-implementation-evidence-2026-10-16.md)
remains **24/19/2**, and [LB evidence](l02e-mixed-races-compatibility-evidence-2026-10-17.md)
remains **21/12/0**. Passing regressions do not relabel those matrices or complete A36.

## Engineering review and resulting contract

### Trusted anchors and immutable obligations

The previous cleanup path accepted a raw `moderation_cases.final_resolution_at`
as destructive authority. That is replaced for contract-v1 intake by
`evidence_case_clocks`. New intake registers its case and exact text-copy identities
inside the existing receipt/probation transaction. Existing received/captured
times remain intact. Registration/observation time never becomes final resolution.

The supported server events are effective notice, timely appeal receipt,
independent appeal disposition and finalization. Event times come from SQL UTC;
there is no client timestamp, resolution flag or historical-date import API.
The 14-day filing interval is `[notice, notice+14d)`. Final resolution is the
later of that boundary and a timely appeal's disposition. An early disposition
waits for the original filing boundary. Pending timely appeals prevent finalization.
The appeal reviewer's final outcome selects ordinary **180d** or wholly
unrelated/accidental **30d** content retention; an initial classification does not
prematurely determine an overturned finding's retention. The distinct structured
decision anchor is final resolution +365d.
Finalization also copies that trusted outcome into the legacy case read projection;
the early-appeal regression checks ordinary receipt availability past an initial
accidental classification's obsolete 30-day boundary.

Finalization can run later, but derives deadlines from those stored events,
never the cleanup time. The original item expiry is written once, including for
supporting context subsequently captured into that case. Notice/disposition
cannot be overwritten through a new key, routine read, migration or retry.
Missing notice, missing appeal boundary, contradictory anchors and uncertified
old final values produce explicit review prerequisites and content-free alerts.
No missing record is treated as a resolved case or approval for endless retention.
Legacy intake is excluded. Reopening/complex extension requires a later trusted,
documented event integration; unsupported actions currently fail closed.

Ordinary receipt/evidence access is denied at or after the stored expiry without
waiting for the worker. These deadlines do not revoke or create chat grants.
This slice does not implement broader structured-case/skeleton deletion merely
because its separate deadline is now calculable.

### Authority, scopes and holds

`Context::fromServerSession` accepts server-loaded state only. Current proof,
case assignment, role, active/suspended/account state and session version are
rechecked inside serialization. Authority reads use shared row locks; workers
cannot mint assertions, change the test clock or create schemas.

**No operational trusted MFA, effective-notice or appeal-disposition issuer exists
in the repository.** The production factory returns
`AUTHORITY_INTEGRATION_UNAVAILABLE` before connecting through the new endpoint.
Session/client `mfa_verified`, role strings and resolution/timestamp flags cannot
establish L03a authority. The only wired adapter requires explicit development,
loopback disposable-database, synthetic and fake-mail flags, a server-session
assertion, and fixture-owned proof records bound to current actor/version/case.
Those fixtures are not operational MFA, appointments, staffing or qualified review.
The older L01 review/clearance integration still needs its recorded operational
MFA review; it is not silently certified by this adapter.

- Assigned-reviewer urgent holds bind one captured evidence item and revision,
  last at most 24h, and cannot be rolled into successive urgent periods for that
  same item. Further protection requires joint authorization.
- Ordinary holds require a safety-lead proposal plus a different privacy approver;
  the first authority is rechecked at second approval. A proposal alone protects
  nothing. A grant lasts at most 720h; next review is at most 336h away, bounded
  by expiry. Review changes no expiry. A new justified grant requires a fresh
  proposal/approval, rather than an automatic renewal action.
- Release records the lead's request and a different privacy approver's current
  authorization. A late release uses the earlier actual expiry, never the later
  click as a fresh clock. Active overlapping holds remain independent.
- The closed necessity codes are `CASE_CONTEXT`, `SAFETY_CASE`, `APPEAL_CONTEXT`
  and `IMMEDIATE_RISK`. Free text cannot enter diagnostic reason fields.
- Scope cannot encompass a case, source chat, another case's evidence or intake
  explanation. An explanation needing preservation must first become a separately
  authorized evidence item before expiry; there is no explanation-preservation
  bypass here. Erased originals are never recaptured.
- A pre-existing unexpired `report_evidence_holds` row is conservatively preserved
  and flagged `UNCERTIFIED_LEGACY_HOLD`; it confers no new authority or held-view
  permission. No legacy hold is fabricated or certified.

At ordinary expiry, ordinary access stays denied even if one captured item is
held. The separate held-view interface requires current assigned authority,
an exact active item hold, an allowed review purpose and the independent-recovery
gate. It audits the access without its body; the authorized API response uses
`Cache-Control: no-store, private`. It does not restore profile/chat access.
Qualified legal-hold instructions remain an unavailable reviewed integration,
rather than a client-selectable unlimited hold.

### Bounded physical purge and retry behavior

The worker recalculates eligibility under the same shared report serialization
used by intake/pair transitions. Ordinary expiry stays immutable. If the final
hold ends after that expiry, the physical obligation is due no later than that
actual last end +24h; purge can occur immediately once eligible. Early release
keeps the original deadline. Reads/reviews/retries do not move either anchor.

Each item transaction checks its copy/revision/fingerprint and current holds,
then atomically clears the selected text/hash, completes its stable purge
obligation, writes a content-free audit and appends a recovery publication
obligation. Explanation cleanup also clears its information fingerprint and the
dependent operation-intent digest. Evidence cleanup clears excerpt and evidence
fingerprint. Fingerprints of erased text are not retained in the lifecycle item.
Newer/mismatched sources are excluded with `SOURCE_CHANGED`, not overwritten.

Same-key operations require matching intent and current authority before returning
the original safe receipt. Conflicting reuse fails. Same purge identity/revision
has one completion event; a lost result can be discovered through its persisted
receipt/state. Engine 1205/1213 retries are bounded at three complete attempts with
fresh validation. This run exercises 1205; injected rollback faults are identified
separately, and no new deadlock coverage is claimed.

Batches default to 25 items and reject limits outside 1–100. The persisted scan
cursor prevents missing-anchor/held items from starving later items. A scan
watermark is **not** a purge-completion watermark: each item's receipt/status and
failure remains authoritative. Failed units are not marked complete. Alerts record
missing anchors, uncertified holds, overdue reviews, source conflicts and due
purges using IDs/reason/deadline/time only. Actual alert delivery/cadence/owners
are unverified; no production schedule is enabled.

The compatibility `bushisa_report_cleanup` calls the bounded service and retains
the existing approved rolling-send timestamp and evidence-access audit cleanup.
It no longer deletes case/idempotency/probation skeletons using an uncertified
old final time. Wider metadata schedules, safe skeleton/import compaction and
physical structured-decision expiry remain separate work with their own purposes.
The old global 365d skeleton assumption does not approve all Q23 categories.
No pair event, session version, request budget, probation, block/clearance,
ordinary source/new chat text or private import fact/receipt is erased here.

### Recovery/publication boundary

The SQL outbox records protected item/revision, action, effective time, ordinary
expiry and aggregate preserve-until, without content, secrets or content hashes.
It starts **unpublished**, with copy inventory `unverified` and `retain_until=NULL`.
That NULL is an unresolved obligation, not a perpetual retention approval or an
independent acknowledgment. SQL completion is not external durability.

The separate synthetic adapter exercises namespace binding, monotonic sequence,
checkpoint/hash-chain continuity, duplicate/conflict detection, publication
acknowledgment, lost-response retry, stale-copy purge replay and a current-head
read/dispatch-release gate. Publication is separate from local marking. An
interruption between journal and checkpoint replacement fails closed and needs
verified continuity repair. The adapter uses a bounded 4MiB fixture file and
separate checkpoint outside the simulated SQL restore set, **on local test
storage**. It is not a production independent journal, power-loss guarantee or
backup/restore proof. Its SHA-256 consistency checks do not prove authenticity.

Replay does not accept a caller's watermark or another namespace. It reapplies
exact old-copy purge/hold-release state and original deadlines without granting
authority or restoring permissions. Later valid transitions supersede replayed
projections. The allocator advances above the independent frontier after an old
SQL-outbox restore, without runtime DDL. Missing/truncated/foreign journal or
unpublished newer obligations keep release closed. Production provider and
startup/mail consumer integration remain absent; this interface does not assert
that all existing production routes already consult an independent journal.

`recovery_bound` requires complete copy facts and verified purge. Conditional
7d live erasure + proposed 35d DB copies +7d margin is **49d**, while a later
copy can give 51d and active protection can require longer. Unknown copies return
no release bound. No actual 35d/7d provider setting is approved or verified here,
and no copied backup receives a fresh lifetime.

## Changed source and inventory delta

| File | Bounded change |
|---|---|
| [010 migration](../../database/migrations/010_text_evidence_lifecycle.sql) | Eleven additive lifecycle/hold/obligation/alert/recovery/cursor tables; no historical backfill, authority grant, pair activation or production-applied claim. |
| [Lifecycle service](../../php/evidence_lifecycle.php) | Trusted-context boundary, case/item anchors, holds, idempotent actions, purge transactions, bounds and recovery gate. |
| [Recovery interface](../../php/evidence_recovery.php) | Guarded separate synthetic publication/replay adapter; no production driver. |
| [Report intake/cleanup](../../php/report_intake.php) | Register exact copies atomically, preserve ordinary deadlines, replace untrusted-final cleanup. |
| [Lifecycle API](../../api/evidence_lifecycle.php) | Bounded POST/CSRF/server-context contract; production authority unavailable. |
| [Synthetic CLI](../../bin/evidence_lifecycle_synthetic.php) | CLI-only, guard before connection; cleanup/publish/replay/gate only on explicit synthetic destinations. |
| [Lifecycle suite](../../tests/integration_l03a_lifecycle.php) | LC-labelled integration, both runtime profiles, actual process/SQL observations. |
| [Fixtures](../../tests/support/l03a_fixture.php) / [worker](../../tests/support/l03a_worker.php) | Fixture-only clock/authority/setup and separate restricted processes; no body in worker diagnostics. |
| [Guard tests](../../tests/unit_l03a_guards.php) | Preconnection refusals with PDO disabled, including actual CLI. |
| [Report regression](../../tests/integration_l01b_reports.php) | Explicit labelled synthetic expiry anchors instead of trusting raw historical dates; drain bounded units. Historical results are not rewritten. |
| [Evidence JSON](evidence/l03a-2026-10-17.json), this document, [inventory](l03-category-clock-writer-inventory.md), [backlog](backlog.md) | Current scope/results/gates and baseline links. Decisions, policy, retention proposals and A/LB artifacts remain unchanged. |

The eleven new tables extend inventory categories C20/C21 and recovery/worker
metadata; they are implemented local stores, not externally observed stores.
Their source identities do not add new ordinary account/chat retention schedules.
Required table-by-table grants, current role checks and atomic missing-grant
failure are in the evidence. Fixture/migration credentials are separate from the
generated restricted API/worker identity, which has SELECT, bounded lifecycle
INSERT/UPDATE and selected report-table UPDATE, with no DDL or fixture writes.
The older ordinary-review regression uses its fixture privileges rather than
broadening these new worker grants. Production grants remain unverified.

## Execution commands and case-level results

Commands below run only with explicit disposable destination settings,
`APP_ENV=development`, `APP_DEBUG=false`, runtime DDL disabled, test DB and
L03a synthetic flags true, and both mail selectors `fake`. The harness separates
fixture setup/migrations from restricted API/worker execution. Report/pair
regressions use other disposable names with the L03a synthetic adapter disabled.

```text
U: php -n -d disable_classes=PDO tests/unit_l03a_guards.php
C: php tests/integration_l03a_lifecycle.php
R: php tests/integration_l01b_reports.php
P: php tests/integration_l02a_pairs.php
```

C executes the documented migration fresh, `--through=009` upgrade, final and
repeat commands, and distinct restricted worker processes. Each LC row below
ran C on **both** profiles; LC18 additionally ran U on both. The JSON maps every
case to its exact assertion names and observations. No browser/live-user suite
was used. R/P are affected regressions, not extra L03 acceptance-case approvals.

| ID | Status | Executed result / remaining variant |
|---|---|---|
| LC01 | PASS | Missing notice/boundary, untrusted historical final and contradictory disposition remain explicit; no destructive inference. |
| LC02 | PASS | Before/exact UTC expiry, ordinary denial, exact 180d/30d purge and separate 365d decision anchor. |
| LC03 | PARTIAL | Timely pending/later/early appeal, outcome reversal and no late/retry reset pass. Trusted complex extension/genuine reopening integration NOT RUN. |
| LC04 | PARTIAL | Assignment/role/proof expiry, wrong item/version/case, client-field and real HTTP checks pass. Operational MFA/notice issuer unavailable; fail-closed boundary passes. |
| LC05 | PARTIAL | Urgent24h/no rollover, proposal/joint30d/review14d, revoked first authority and no automatic renewal pass. Real joint authority/legal-instruction integration NOT RUN. |
| LC06 | PASS | Early/late/natural expiry and overlap retain original due and actual last-end +24h ceiling. |
| LC07 | PASS | Exact/conflicting retries, concurrent identical release and concurrent purge have one effect/receipt. |
| LC08 | PASS | Only held E1 survives; E2/explanation/fingerprints purge, ordinary access stays denied, scoped fixture view is audited. |
| LC09 | PASS | Separate PIDs/connections actually wait: expired hold versus purge, existing hold/joint authorization versus purge; no recapture. |
| LC10 | PASS | Revoked-session/deleted reviewer queued against purge is denied; sessions/account/grants remain revoked. |
| LC11 | PASS | Four injected rollback points, lost committed result, genuine 1205/three attempts, then safe retry; no 1213 claim. |
| LC12 | PARTIAL | Read/retry/review/migration and actual capture-writer deadline invariance pass. Full export/edited-evidence and complete addendum-route expiry variants NOT RUN. |
| LC13 | PASS | Concurrent bounded batches, failure/resume, scan progress, one completion and content-free due/missing-anchor alerts. |
| LC14 | PASS | Nonempty restarted segment/new text, versions, three-request floor, block/clearance, probation and import facts/receipt unchanged; newer copy mismatch excluded. |
| LC15 | PARTIAL | Synthetic stale-copy/outbox replay, namespace/frontier/ack retry and missing/truncated-source refusal pass. Independent durability, production startup/mail integration and witnessed restore NOT RUN. |
| LC16 | PASS | Conditional49d/later51d/protective state and unknown-copy refusal; arithmetic/interface only, actual inventory UNVERIFIED. |
| LC17 | PASS | Fresh/current009→010/repeat, retained unknown final rows and explicit exclusion; no production application. |
| LC18 | PASS | Thirteen preconnection guards/profile, actual CLI with PDO disabled, restricted fixture/DDL refusals and missing-outbox-grant atomic rollback; no mail. |

| Profile | Exact local execution environment | Executed outcome |
|---|---|---|
| Native | Windows; PHP 8.5.11 NTS, mysqlnd 8.5.11; owned MySQL 9.6.0 on loopback3351 | U13, C122/7 overlaps, R245/8 HTTP overlaps, P396/11 HTTP overlaps PASS. |
| CI-aligned compatibility | Linux container; PHP 8.4.26 NTS, mysqlnd 8.4.26; owned MySQL 8.0.45 | Same totals PASS. Cached images `bushisa-l02e-php84:local` / `mysql:8.0`; exact image hashes retained below. No new CI run. |

Both use session UTC, InnoDB and REPEATABLE-READ. Exact SQL modes and server UUIDs
are in the JSON; PDO/pdo_mysql, OpenSSL, cURL, mbstring and fileinfo were captured.
The MySQL image ID is
`sha256:529b4afa138686871cf698617ef6e347a857179fa2c58e6811b6c20862a1cd6a`;
the PHP image ID is
`sha256:bd6bba8057dca1623714f20a89f3dd5e7bee845658fe653cdfd8a7bdff5524e1`.
These are local profiles, not guessed Azure versions. Source hashes in all
selected execution artifacts match the checkpoint source. Documentation/static
validation is separate from the functional results above.

Documentation/static validation observed `2026-10-17T10:47:08.536050Z` /
`2026-10-17 12:47:08.536050 Africa/Harare`: 138 local links, six heading
targets, all 18 current status rows and the preserved initial NOT RUN plan,
eleven additive table definitions, clock arithmetic and source-hash comparisons
pass. All ten changed PHP files pass native syntax checks. Git diff checks pass;
decisions, retention policies/proposals and historical A/LB JSON match `b521b67`.
These are check times/results, not an approval time or additional functional cases.
The identity-checked owned MySQL instance/container and test HTTP workers were
stopped after execution; the seven owned loopback test listeners are closed.
No unrelated Docker resource/service was stopped. Local synthetic fixture files
are not described as production erasure or independent backup disposal.

## Remaining gates and next bounded task

| Class | Remaining requirement |
|---|---|
| Implementation/integration | Trusted operational MFA/notice/appeal and joint-authority providers; authorized complex extension/reopening with separate immutable obligations; production journal adapter and startup/read/dispatch consumers; full export/edit/addendum expiry variants; broader structured-decision/metadata/object cleanup and safe import compaction. Existing approvals permit engineering; none is a new P1–P6 question. |
| VERIFY evidence | Deployed schema/runtime/grants/UTC/cadence, independent store durability/key custody/outside-restore boundary and high-water acknowledgment, complete database/media/export copy inventory and actual expiry/purge bounds. Unknown continuity prevents release. |
| CONFIRM / qualified review | Actual safety/privacy/security/operations/backup primaries and backups, accepted assignments/MFA, independent appeal reviewer, monitored contacts, coverage and lawful hold/purpose/rights acceptance. Takudzwa is not inferred to fill these roles. Decision33 service targets remain conditional. |
| Existing policy questions | Q12 anti-replay/later-adult, Q13 purpose/field allowlist and exact warning offset, Q16 active-chat schedule, Q23 metadata and Q26 copy settings stay pending. They are not prerequisites for the approved text/hold core and are not silently adopted. |
| Separate execution/release | Real independent/witnessed restore NOT RUN, production migration010 UNVERIFIED, no real-data/legacy cleanup activation, production scheduling, configuration changes or release authorized. Candidate CI stays head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`, push `38546846225`, PR `38546851035`. Migration005 is not declared production-applied. |

**Next independent bounded implementation:** Q11 incomplete-signup/onboarding
anchor and secret-cancellation slice, using the [inventory's G01/G04/G05](l03-category-clock-writer-inventory.md#4-clock-and-erasure-findings-to-carry-into-implementation).
Persist original creation/first-email-approval expiry, deny expired attempts,
atomically invalidate challenges and cancel useful-mail work without resend/read
clock renewal, then test guarded synthetic boundary/concurrency/unknown-anchor
cases. Preserve existing immediate terminal secret erasure and approved challenge
settings; do not adopt proposed Q22 numeric defaults, an underage HMAC duration,
the pending inactivity-warning offset or broad account/media purge. Independent
journal/qualified-review work remains parallel gated work, not an invented next
L02 phase or an instruction to remove D03 features.
