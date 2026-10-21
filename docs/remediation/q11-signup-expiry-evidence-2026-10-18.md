# Q11: signup/onboarding anchors and atomic challenge/mail cancellation

Continued from `be91e06004552004311f5bc6c312f20dbad04091` on
`codex/q11-signup-onboarding-expiry-20261017`. The starting index/tree were
clean; no applicable AGENTS.md was found in the checkout or ancestors. This is
local implementation and synthetic engineering evidence. Migration 011 has not
been applied to production; no scheduler, provider, DNS or deployment changed.

Authority: [decisions](decisions.md), [Q11/Q22/Q25 reconciliation](owner-policy-reconciliation-2026-10-15.md),
[retention matrix](data-inventory-retention-matrix.md), [retention policy](../data_retention_policy.md)
and [L03 inventory](l03-category-clock-writer-inventory.md). Q11's 72h/7d clocks
and expiry +24h live-cleanup obligation are approved. Existing configurable
challenge settings and immediate terminal secret erasure remain. Proposed Q22
resend/numeric defaults, Q12 anti-replay and Q16/Q23/Q26 values are not adopted.
Takudzwa Hondova remains product reviewer. The exact historical owner acceptance
instant was not captured; execution dates do not replace it or reopen approval.

## Result and scope

Separate **QS01–QS24: 24 PASS / 0 PARTIAL / 0 NOT RUN**, for the local variants
listed below. Both profiles execute **74 assertions and seven observed overlaps**.
This is not a complete account-erasure, provider-delivery or recovery acceptance.
The [machine-readable record](evidence/q11-2026-10-18.json) contains commands,
UTC times, environments, grants, per-case checks, worker/SQL identities, observed
row waits, migration results and hashes identifying the tested source.
Africa/Harare displays are UTC +02:00. These are execution timestamps only.

The historical matrices remain unchanged: A01–A45 **24/19/2**, LB01–LB33
**21/12/0**, LC01–LC18 **13/5/0**. Regression execution does not relabel them.
The new CI step is prepared locally; a candidate GitHub run is **NOT RUN**.

## Engineering review and implementation contract

### Anchors and account transitions

[Migration 011](../../database/migrations/011_signup_lifecycle.sql) adds four
tables without backfill or changes to existing enums/data. `signup_lifecycle`
records original registration, first approval, completion, expiry and cleanup
deadlines. `signup_mail_jobs` records delivery claims/outcomes.
`signup_expiry_obligations` is a stable, content-free account-expiry receipt and
pending cleanup/recovery obligation. `signup_lifecycle_alerts` exposes missing
anchors and overdue live cleanup; these SQL records are not staffed alert routing.

| Event/category | Trusted anchor and boundary | Writers / resulting behavior |
|---|---|---|
| New pending-email account | Server-persisted `users.created_at`, retaining its existing seconds precision; expiry = original creation +72 elapsed hours | [Registration](../../php/auth.php) calls `bushisa_signup_record_creation` inside the insert transaction. Queue failure rolls back account, clock and challenge/mail together. No resend/login/migration calls this as legacy backfill. |
| First successful email approval | SQL UTC(6) recorded once; onboarding expiry = that event +168h | [Verification](../../php/signup_verification.php) locks the account, checks its current stage, version, challenge and original deadline, then persists first approval and changes status in one transaction. Repeat approval, resends and reads cannot renew it. |
| Completed onboarding | Current verified/pending state, valid adult DOB and current version; profile lock acquired before final deadline recheck | Same service activates only a still-eligible account and records completion once. Old expiry selections recheck the locked account and skip active/completed accounts. Underage/suspended/deleted accounts cannot be activated by this path. No new underage retention policy is implemented. |
| Known expiry | Operations reject at or after the immutable deadline | [Lifecycle service](../../php/signup_lifecycle.php) locks the account first, cancels all challenge generations/mail, disables it using existing `deleted`/inactive state and increments session version once. `expired_at` remains the original deadline; `disabled_at` is actual execution time. This is access denial, not an account hard-delete. |
| Live erasure obligation | Original expiry +24h, even if discovery is late | Stable receipt keeps due time and cancellation counts. Retries neither duplicate it nor extend the deadline. Ordinary account/profile erasure remains `pending_consumer`; late obligations produce one stable overdue alert. |
| Unknown/contradictory legacy clock | NULL or inconsistent anchors are a review prerequisite | Existing pending rows are not assigned guessed creation/approval times. No automatic account deletion occurs. Resend/verification/onboarding continuation stays fail-closed pending an evidence-based legacy resolution. Existing active accounts are outside these timers. |

All account mutations use account-first row locks and bounded transactions.
Only server state establishes eligibility/version/clock; form timestamps, roles
or MFA assertions cannot create anchors. The successful event time is checked
against the original deadline before transition. Reads can trigger an already-due
transition but never renew a clock. Transaction ownership is respected; rollback
removes cancellation, transition and receipt effects together. The helper permits
at most three attempts for engine 1205/1213; this checkpoint does not claim an
executed Q11 deadlock trial.

[Login/session authorization](../../php/session_manager.php) checks pending-stage
expiry before continuing and rechecks current account/version. The actual
[verification](../../verify_email.php) and [onboarding](../../onboarding.php)
routes close their PHP session lock before the account transaction. A response
resumes only the same still-current session; it cannot recreate a logged-out
session or manufacture the next database session version. Signup pages explain
the deadlines, no renewal and the need to repeat eligibility after cleanup.
Exact account deadlines are shown only to an authenticated owner.

### Delivery cancellation and the external transport boundary

[Mail producer/adapter](../../php/mail_delivery.php) and
[job state machine](../../php/signup_mail_jobs.php) bind each job to its actual
account and challenge. Useful delivery expiry is immutable:
`min(queue +5m, challenge expiry -60s, account expiry)`. The ordinary challenge
TTL, code format and maximum attempts remain configured as before. Payloads are
encrypted while queued; verification uses keyed HMAC. No code/body/claim secret
is put into receipts, alerts or diagnostics.

`ready -> claimed -> inflight -> accepted/failed/uncertain` is explicit.
Claim, final preflight and receipt are separate short transactions. Preflight
checks current account state, generation, hash presence, attempts, claim owner,
job state and deadlines immediately before transport. No database row lock is
held across SMTP. A 60-second technical claim lease allows safe reclamation only
before transport starts. Stale claim tokens fail. An abandoned in-flight job or
ambiguous transport outcome becomes `uncertain`, erases its stored payload and
does not automatically resend. Known non-acceptance can retry only inside the
original useful deadline and attempt bound. Missing legacy job provenance is
not promoted into an authorized delivery.

Cancellation erases stored payloads and terminal challenge HMACs immediately.
Ready/claimed jobs become cancelled. An in-flight job retains its observed state
with `cancel_requested_at`; a later carrier acknowledgment is recorded as
`accepted_after_cancel`. It never restores an account or challenge. There is an
unavoidable gap between committed preflight and external transport: expiry or
supersession can occur while the carrier is sending. Database cancellation
cannot recall an already accepted message or erase its in-memory transport copy.
Every verification still checks current state/generation/deadline. Carrier
acceptance is not inbox arrival. All tested acknowledgments used a fake carrier.

[Cleanup CLI](../../bin/cleanup_policy.php) runs bounded account expiry,
challenge terminalization and job-aware payload cleanup. The former unconditional
`cleanup_outbox` time-based `done` update is removed: elapsed time is not evidence
of erasure. The compatibility terminal-cleanup helper now uses account/code
binding and the same lock/cancellation path instead of caller-supplied email.
Old untracked payload cleanup preserves original/unknown delivery timestamps;
it must not invent a historical acceptance time.

Safety evidence, holds, pair history, existing protective events, unrelated
accounts and recovery outboxes are outside the attempt-data cleanup scope and
remain intact. A scoped hold protects its evidence copy, not the ordinary
account. No ordinary-account hard-delete/cascade or broad media purge is added.
The local expiry obligation is **unpublished**, with `retain_until=NULL` while
copy inventory/recovery integration is unknown. It is not an independent journal
and does not prove restore protection. No universal 49-day timer is installed.

## Executed acceptance matrix

PASS applies only to the stated synthetic variants, on both captured profiles.
The test source is [integration_q11_signup_expiry.php](../../tests/integration_q11_signup_expiry.php),
with separately privileged [fixture setup](../../tests/support/q11_fixture.php)
and [CLI workers](../../tests/support/q11_worker.php).

| ID | Executed variant | Result |
|---|---|---|
| QS01 | Five unsafe environment/destination settings refused before connecting | PASS |
| QS02 | Fresh migration 011, then repeat run | PASS |
| QS03 | Existing 010 account upgrade and repeat; rows unchanged, no anchor backfill | PASS |
| QS04 | Actual registration anchor; queue failure rolls back account/clock | PASS |
| QS05 | 72h account deadline: -1µs, exact, +1µs | PASS |
| QS06 | 7d onboarding deadline: -1µs activation, exact/+1µs denial | PASS |
| QS07 | Resend leaves original clock, supersedes old generation, erases its mail | PASS |
| QS08 | First approval immutable under reads/repeat verification/resend | PASS |
| QS09 | Missing legacy creation/approval and contradictory clocks fail closed without deletion; orphan cleanup preserves unknown acceptance time | PASS |
| QS10 | Suspended pending account cannot be activated or have its restriction cleared | PASS |
| QS11 | Faults after cancellation/before commit roll back; retries/CLI preserve original receipt/version/due time and pending erasure | PASS |
| QS12 | Pending/unpublished obligation and overdue alert; evidence/hold/protective/recovery rows preserved, including through actual cleanup CLI | PASS |
| QS13 | Useful deadline formula, just before/exact/after boundary; no expired dispatch | PASS |
| QS14 | Known non-acceptance bounded retry; ambiguous outcome erases and never auto-resends | PASS |
| QS15 | Expired claim replacement rejects old owner; abandoned in-flight claim becomes uncertain | PASS |
| QS16 | Actual verify route versus expiry, both sides of account boundary; two overlapping processes/connections | PASS |
| QS17 | Actual onboarding route versus expiry, completion wins or exact expiry denies; overlapping processes/connections | PASS |
| QS18 | Actual resend route versus expiry; no live challenge/body survives | PASS |
| QS19 | Actual HTTP resend and expiry-triggering resend during separate fake-carrier transport; late acceptance recorded, obsolete code unusable | PASS |
| QS20 | Actual HTTP supersession cancels an existing delivery claim; its later preflight fails | PASS |
| QS21 | Actual protected route denies expired session; stale session version cannot activate | PASS |
| QS22 | Restricted worker cannot write the fixture clock or perform schema DDL | PASS |
| QS23 | Content-free assertion/race/grant diagnostics contain no secret payload fields | PASS |
| QS24 | Challenge deadline: -1µs, exact, +1µs; repeated terminal cleanup is idempotent | PASS |

Seven overlap observations are five HTTP/expiry pairs (QS16×2, QS17×2, QS18)
with both distinct SQL connections simultaneously waiting on the account row,
and two HTTP/in-flight fake-carrier pairs (QS19). Evidence includes real PIDs,
connection IDs and `performance_schema.data_lock_waits`. QS20 exercises the
claimed/preflight ordering but is not counted as an observed concurrent race.
Only guarded disposable destinations, synthetic accounts and fake mail were used.
The fixture clock is server-owned, SELECT-only to workers, and unavailable
outside the full Q11 synthetic guard. It is not an operational clock or authority.

Tested profiles: Windows PHP **8.5.11 NTS/mysqlnd 8.5.11, MySQL 9.6.0**;
Linux PHP **8.4.26 NTS/mysqlnd 8.4.26, MySQL 8.0.45**. Both use InnoDB,
native PDO, UTC and the captured transaction/SQL settings. Local MySQL 9.6 is
not an approved production upgrade. Actual Azure runtime/configuration remains
unverified. Eight affected unit/integration suites per profile cover signup,
mail configuration, SMTP outbox with injected fake carrier, core/policy,
report intake (245 assertions/eight overlaps) and pairs (396/eleven).
Exact final regression exits are in the JSON; historic CI attribution is unchanged.
The existing localhost-only SMTP fixture additionally passes on Windows for
trusted STARTTLS/AUTH and rejection of missing TLS/untrusted CA. Its first run
failed the trusted fixture; converting Cygwin's CA-file path for native Windows
PHP repairs the harness, with Linux path handling unchanged. That additional
transport-fixture variant was NOT RUN in the cached Linux image (`python3` unavailable).
It is distinct from the fake-carrier outbox suite passed on both profiles.

## Remaining work and release gates

| Class | Requirement / accountable role | Status |
|---|---|---|
| Next bounded engineering | Consume trusted Q11 expiry obligations to erase ordinary attempt identity/credentials/profile/preferences and covered live derivatives within expiry +24h. Inventory every affected FK/copy first; preserve scoped evidence, budgets and protective/recovery records. Record verified purge receipts and test safe retry/restore exclusions. Engineering/cleanup roles; actual operators unassigned. | NOT IMPLEMENTED / tests NOT RUN |
| Engineering | Approved challenge attempt-detail and code-free mail metadata cleanup, original terminal anchors, bounded legacy queue disposition and privacy-safe recovery publication adapter. No proposed Q23 retention value may be silently applied. | Separate bounded work |
| Legacy evidence | Establish genuine historical creation/first-approval provenance and writer cutover before any legacy continuation/expiry adapter. No owner reapproval of settled 72h/7d rules is required. | UNVERIFIED; fail-closed |
| Operational evidence | Accepted cleanup/mail primaries and backups, at-least-hourly approved scheduling, overdue alert recipients/coverage and measured dispatch/cleanup capacity; actual runtime, resources and provider processing | NOT VERIFIED; scheduling NOT ENABLED |
| Recovery / qualified review | Independent protective-record durability/copy inventory, startup/mail replay, expiry before restored access and witnessed isolated restore; privacy/security/safety review and staffing | Existing L03a/LC and release gates remain OPEN; operational variants NOT RUN |
| Provider acceptance | Consenting-recipient SMTP/inbox/header tests, bounded bearer/copy inventory and real inbound support contact | NOT RUN / UNVERIFIED; no real mail authorized here |

No new owner policy decision is needed to implement the next approved attempt-data
cleanup slice. Real names/coverage, legacy provenance, actual copy bounds and
qualified sign-offs remain evidence/appointment requirements, not permission to
invent dates or declare the broader L03 release gates complete.
