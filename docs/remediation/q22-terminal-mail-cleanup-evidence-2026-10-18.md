# Q22 terminal challenge and code-free mail cleanup

Continued from `1c3f5874d4e2e5e832275b916bbda245ab5d31db` on
`codex/q22-terminal-mail-cleanup-20261018`. The starting index/tree were clean;
no AGENTS.md was present in the checkout or ancestors. This checkpoint concerns
new writer-bound signup verification records. It is not general account deletion,
legacy-history approval, operational scheduling or a production release.

Authority: [decisions](decisions.md), [Q22/Q24–Q26 reconciliation](owner-policy-reconciliation-2026-10-15.md#q22-verification-and-delivery),
[retention policy](../data_retention_policy.md), [matrix](data-inventory-retention-matrix.md),
[L03 inventory](l03-category-clock-writer-inventory.md),
[Q11 clocks/mail state machine](q11-signup-expiry-evidence-2026-10-18.md) and
[ordinary attempt erasure](q11-attempt-erasure-evidence-2026-10-18.md).
No proposed challenge/resend defaults, Q23 metadata category or Q26 copy limit
is adopted here. Takudzwa Hondova remains product reviewer; the exact historical
acceptance instant was not captured. Test timestamps do not replace it.

## Verified scope and clocks

| Category / approved reference | Original trusted event and deadline | Operation / preserved state |
|---|---|---|
| Challenge validity, existing settings/Q22 | Existing server issuance and configured `expires_at`; reject at/after expiry, on consumption, supersession or exhausted attempts | Preserve configured TTL/length/attempt maximum and account eligibility. Cleanup never makes a terminal challenge usable. |
| Challenge secrets, Q22 | Existing immediate terminal erasure; expiry is the original stored expiry, not the later cleanup observation | Existing writers still clear keyed HMAC immediately. The bounded consumer repairs eligible residual copies and records a content-free receipt. The 24h ceiling never authorizes waiting to erase secrets. |
| Challenge detail, Q22/Q25 | First use/supersession/exhaustion/cancellation event, or original expiry if earlier; detail deadline = event +24 elapsed hours | Clear email, attempt count, code-version detail and consumed marker; leave only the non-redeemable identity/clock shell needed for existing mail binding and recovery. `terminal_at` and NULL hash still deny use. No first-email-approval anchor is inferred from `consumed_at`. |
| Code-bearing mail, Q22 | Earlier of the original useful deadline or the actual terminal/cancellation event; existing terminal erasure remains immediate, with the approved one-hour ceiling | Erase body and next retry, minimize recipient to a non-deliverable placeholder, use fixed template name and remove free-form metadata. Seven-day metadata retention cannot preserve a body. |
| Ordinary delivery metadata, Q22 | The same original delivery/failure/cancellation/dispatch-expiry anchor +7 elapsed days | Delete the bound `mail_outbox` and `signup_mail_jobs` rows. An in-flight carrier's expired dispatch permission is not evidence of its external outcome. Preserve only the narrow unresolved-result verifier and recovery/cleanup receipts below. |
| Claim/result reconciliation, Q22 and this task's required receipt scope | Actual committed transport claim and later result; it does not extend the ordinary metadata deadline | For unresolved in-flight/uncertain jobs, retain random record reference, mail ID, hashed claim verifier, explicit unresolved state, observation/version. A valid late result clears its verifier once. No recipient, original subject, body, challenge HMAC, attempts or error text is copied. Malformed/unverifiable claims require review; no outcome is guessed. |
| Recovery obligations, Q26 | Original action/deadline plus verified live purge; latest actual recoverable copy expiry +7d and any longer protective need | Local minimal instructions remain **unpublished**, copy inventory **unverified**, `retain_until=NULL`. This is an interface, not independent storage/replay proof or an invented indefinite ordinary-metadata schedule. Receipt/shell retirement needs the actual copy inventory and purpose review. |

All new anchors are server-owned UTC. A retention anchor/deadline is set once
from its actual event. Initial known delivery expiry is an upper bound; a real
earlier terminal event can shorten it, never lengthen it. Reads, retries,
resends, delayed jobs, late acknowledgments and migration do not restart it.
The consumer records overdue work even when a late run succeeds. A technical
60s retry/check delay does not change any policy deadline.

If a source shows an earlier terminal event but its trusted retention anchor is
missing, the consumer does not replace that unknown clock with a later expiry.
It still erases known-expired secrets under the original validity boundary,
then leaves detail/metadata disposition explicitly unresolved for review.

Unknown/in-flight state is explicit. Payload expiry can occur while a carrier
still holds an in-memory copy. The consumer does not infer delivery, recall an
SMTP-accepted message, automatically resend uncertain work or mark an allegation
proven. An unknown future state loses its expired body/contact copy but retains
an explicit review obligation rather than a fabricated terminal result.

The Q11 72h/7d account clocks, first approval, completion, session versions,
rate limits, replacement challenges, ordinary attempt-erasure receipts, safety
evidence/holds, consent, blocks and lifetime pair budgets remain unchanged.
Scoped evidence copies stay under their own approved hold/retention clocks;
there is no new API to hold redeemable codes or an entire ordinary account.
Synthetic protected rows test preservation, not operational hold authority/MFA.
Other token purposes, unlinked legacy payloads, provider mailboxes, logs, PHP
session files and real storage/backups are outside this live SQL scope.

## Engineering review and implementation

[Migration 013](../../database/migrations/013_challenge_mail_retention.sql) adds
five retention/receipt/recovery tables and, through the
[controlled helper](../../php/verification_retention_migration.php), nullable
generation keys and immutable identity triggers on codes/mail. Existing rows
remain NULL/untracked. No production migration or backfill occurred. Partial
DDL retry validates exact existing triggers/routine instead of replacing them.

The actual issuance and queue transactions bind identities before returning.
[Verification](../../php/signup_verification.php),
[Q11 cancellation](../../php/signup_lifecycle.php) and
[mail completion](../../php/signup_mail_jobs.php) record original terminal
events in their existing transactions. Resend no longer repopulates consumed
detail on an already-terminal, erased generation. Existing immediate hash/body
erasure still works without enabling this worker or its synthetic adapter.

The [consumer](../../php/verification_retention.php) and
[CLI](../../bin/cleanup_verification.php) default-deny before connection unless
explicitly enabled. Additional destructive processing requires the full guarded
synthetic recovery adapter; **an operational provider is unavailable**, so an
enable flag cannot authorize operational erasure. No scheduler is installed.

One bounded run selects at most 50 records (default 25). Each record is a short
owned transaction with account-first locking, then the exact code, mail/job and
retention row. It rechecks original account generation/creation, source
generation, original creation/expiry, job/account/code binding and current
state. It accepts no saved plan, client timestamp or role assertion. Different
account versions caused by legitimate verification do not retarget a code;
the exact source identity and terminal eligibility are rechecked. Old IDs,
email reuse and replacement generations cannot select another person's data.

There is no external storage deletion in this consumer. SQL obligations precede
erasure in the same transaction; progress, minimal receipts and verification
commit with it. Failed work rolls back without acknowledging completion.
Duplicate workers serialize and find the same committed result. The status
command lets an interrupted client discover completion. Counts/alerts expose
fixed reason codes, protected record references and original deadlines only.
Batch exit success alone is not an erasure receipt: blocked counts remain open.

Deletion is limited to two ordinary mail tables; challenge detail uses a scoped
UPDATE. A schema-bound read-only definer health procedure checks expected
columns/identity triggers and any incoming FK or unreviewed UPDATE/DELETE
trigger before mutation. Unknown cascades block cleanup. Runtime grants do not
include schema DDL, user deletion or fixture-clock writes.

After ordinary metadata deletion, the carrier result path uses the pre-existing
retention identity and a current locking receipt read. This repairs an observed
REPEATABLE READ race in which the delivery transaction's old snapshot could
miss the newly committed result verifier while waiting on the account lock.
Late results cannot recreate mail, reset retention, verify an account or grant
contact access. An unavailable/wrong/used verifier is rejected.

## Synthetic acceptance evidence

The separate **QM01–QM32** matrix below is local engineering evidence only.
Commands, UTC/Africa-Harare execution times, exact runtimes/grants, case-level
assertions, worker/SQL identities, observed row waits and source hashes are in
the [machine-readable evidence](evidence/q22-2026-10-18.json).
The [suite](../../tests/integration_verification_retention.php),
[fixture](../../tests/support/verification_retention_fixture.php) and
[workers](../../tests/support/verification_retention_worker.php) use guarded
disposable databases, synthetic accounts and fake mail only.

Aggregate: **32 PASS / 0 PARTIAL / 0 NOT RUN** for these stated local variants;
**75 assertions and six observed overlaps per profile**. Operational/external
variants listed below remain NOT RUN. Profiles: Windows PHP **8.5.11 NTS /
mysqlnd 8.5.11, MySQL 9.6.0**; Linux PHP **8.4.26 NTS / mysqlnd 8.4.26,
MySQL 8.0.45**. Native PDO, InnoDB, UTC and the exact captured transaction/SQL
settings were used. These local versions are not Azure-version approval.

All **eleven commands per profile** pass: Q22, Q11 (74 assertions/seven overlaps),
attempt erasure (57 Windows/58 Linux assertions; existing Windows symlink
variant NOT RUN), signup/mail units, signup P0, injected-carrier SMTP outbox,
core, policy, reports (245 assertions/eight overlaps) and pairs (396/eleven).
Fresh/012-upgrade/repeat/partial migration checks pass in the Q22 suite.
PHP syntax passes for all thirteen changed PHP files. Documentation/link,
status/clock and tested-source hash checks are separate from functional tests.

After final review added the missing-anchor secret-repair variant, Q22 alone
was rerun on both profiles (75 assertions). The unchanged Q11/QE and eight
affected regression results were reused; their original execution times remain
in the evidence. Both owned test database services were identity-checked and
stopped after verification. No operational service was stopped.

| ID | Executed local variant | Result |
|---|---|---|
| QM01 | Disabled consumer and invalid batch refuse before connection | PASS |
| QM02 | Fresh, 012 upgrade, repeat and partial-DDL retry; old values/NULL generations preserved | PASS |
| QM03 | Actual writer-bound code/mail identities; SQL identity/expiry mutation rejected | PASS |
| QM04 | Actual verification before/at/after challenge expiry | PASS |
| QM05 | Successful use immediately clears HMAC; original detail deadline | PASS |
| QM06 | Detail deadline −1µs/exact/+1µs; retries/read/approval anchors preserved | PASS |
| QM07 | Late cleanup retains original expiry +24h | PASS |
| QM08 | Superseded generation cleanup preserves and can verify newer challenge | PASS |
| QM09 | Exhausted detail erased; old code and rate budgets never restored | PASS |
| QM10 | Useful payload deadline −1µs/exact/+1µs | PASS |
| QM11 | Accepted body erased immediately; seven-day metadata does not extend it | PASS |
| QM12 | Metadata deadline −1µs/exact/+1µs; ordinary rows deleted once | PASS |
| QM13 | Expired in-flight body erased without guessed terminal carrier outcome | PASS |
| QM14 | Ordinary metadata expires while only the unresolved-result verifier remains | PASS |
| QM15 | Wrong/valid/replayed late acknowledgment; no content or access resurrection | PASS |
| QM16 | Unknown future state: expired payload erased, explicit review remains | PASS |
| QM17 | Actual cleanup/claim overlap, no expired dispatch | PASS |
| QM18 | Actual cleanup/cancellation overlap, original deadline | PASS |
| QM19 | Actual metadata purge/dispatch-finish overlap, duplicate-free result | PASS |
| QM20 | Actual cleanup/known-nonacceptance retry overlap, original useful deadline | PASS |
| QM21 | Separate fake carrier outside SQL transaction overlaps purge, then late acceptance | PASS |
| QM22 | Duplicate cleanup workers overlap; exactly one detail receipt | PASS |
| QM23 | Crashes after intent/erasure/before commit; rollback and safe retries | PASS |
| QM24 | Crash after commit/before reply; status and retry find completion | PASS |
| QM25 | Missing DELETE grant rolls back; only intended restored grant permits retry | PASS |
| QM26 | Unknown incoming cascade rejected before deletion; fixture review/retry | PASS |
| QM27 | Stale account generation and legacy missing anchors fail closed | PASS |
| QM28 | Operational provider gate; read-only unknown-bound recovery; clean diagnostics | PASS |
| QM29 | Valid scoped evidence hold, safety/consent/budgets/account/recovery records preserved | PASS |
| QM30 | Resend cannot repopulate erased detail; reused source ID rejected | PASS |
| QM31 | Original overdue alert, bounded batch, restricted runtime grants | PASS |
| QM32 | Missing earlier challenge/mail terminal provenance stays unresolved; known-expired secrets still erased | PASS |

Each overlap claim has distinct worker PIDs/SQL connection IDs. Five transaction
race pairs show both workers simultaneously waiting in the engine; QM21 instead
shows a separate carrier waiting outside a transaction while cleanup commits.
These are concurrency/crash checks, not new genuine-deadlock evidence.

Older Q11 fixtures now cancel earlier jobs via the real cancellation service;
they no longer rewrite immutable mail expiry. The SMTP outbox fixture advances
only its own guarded SQL session clock, then restores it; it uses an injected
fake carrier. No real SMTP/provider request was sent. Existing A/LB/LC/QS/QE
evidence and their partial/external variants are not relabelled by regressions.
Candidate GitHub **CI is NOT RUN**. The new CI step has no run attribution.

## Remaining gates and next bounded task

- **Engineering/operational integration:** actual independently protected recovery
  provider, copy inventory/retirement, restore replay before reads/mail and
  witnessed restore remain OPEN / operational variants NOT RUN. No hard-coded
  35d/49d expiry is installed. Compute from the actual verified purge/copies +7d.
- **Legacy evidence:** genuine original terminal events, account/source generations
  and writer cutover remain UNVERIFIED. Unknown records are surfaced for review;
  missing rows/events do not grant cleanup authority.
- **Qualified/operational acceptance:** privacy/security/safety review of actual
  receipts, processing and copy bounds; real operators/backup/alerts/coverage,
  capacity and scheduler acceptance; actual production runtime/provider/inbox
  evidence remain OPEN. Synthetic guards and SQL alerts are not those sign-offs.
- **Independent live-copy gap:** physical PHP session copies and other unindexed
  legacy/provider stores are outside this receipt. QE21's Windows symlink
  variant and other earlier gates remain unchanged.

Next bounded repository task: inventory the account/generation-to-PHP-session
copy paths and prepare their safe cleanup/revocation contract. This addresses
the explicit remaining Q11 live-copy gap without deleting unrelated sessions or
choosing proposed session lifetimes. Existing Q11 erasure approvals suffice;
actual handler/storage configuration and independent recovery need evidence.
No production access, real user data/mail, scheduling, push, merge or deployment
was performed here.
