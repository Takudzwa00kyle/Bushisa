# Q11 synthetic session-copy physical consumer evidence

Date: 2026-10-18, UTC; local display Africa/Harare. Branch:
`codex/q11-synthetic-session-copy-consumer-20261018`, based on checkpoint
`5e7c5a5`. Reviewer: Takudzwa Hondova. The historical owner-acceptance time is
not recorded; these test times are execution timestamps, not approval times.

This checkpoint implements and exercises only the existing Q11
`session_copies_v1` obligation for positively covered new attempts in the
guarded synthetic store. The approved live-copy deadline remains the original
attempt/onboarding expiry +24 hours. No new retention period is introduced.
Existing production handler, general session, anonymous-session, Q14 deletion,
legacy mapping, Q40, privacy-review or operational decisions are unchanged.

## Implementation boundary

The consumer is CLI-only and refuses a disabled or non-synthetic destination
before opening the database. It validates the original Q11 receipt and
immutable registration/first-approval anchor, account generation, expiry,
lifecycle and session versions, positive synthetic writer coverage and the
current fixture-store identity. It does not infer old rows from a matching ID,
email, empty directory or missing manifest.

Processing uses bounded database claims and seal, capture, erase and verify
stages. The generation seal and account write fence prevent late managed writes
and session regeneration; pending one-use handoffs are fenced under the same
account state. Captured manifests bind account, generation, session binding,
epoch, namespace and immutable object generation. A per-copy intent is
committed before filesystem I/O. The adapter validates the locator, root,
regular-file identity, generation header, link count and held file lock; it
unlinks only that exact fixture object and then independently verifies the
directory entry is absent. The SQL receipt is written only after all known
eligible copies verify absent. A crash between unlink and SQL acknowledgment is
reconciled by checking that same immutable locator on retry. A changed object,
changed authority, unsafe path, missing anchor or incomplete writer coverage
blocks completion.

Filesystem removal and database state are separate failure domains. The local
receipt proves only scoped absence in this synthetic store; it does not assert
that an OS operation is transactionally reversible, that another store was
enumerated, or that a recovery copy cannot restore the object. The independent
recovery row remains unpublished, inventory unverified and `retain_until`
unset, even when its synthetic live-purge observation is present.

## Case-level results

The current SC matrix in the [contract](account-session-copy-contract.md#7-planned-acceptance-matrix)
is **15 PASS / 19 PARTIAL / 6 NOT RUN**. This run advanced SC16, SC25 and SC28
to PASS for the tested synthetic variants, and SC26 from NOT RUN to PARTIAL.
It leaves every other case status unchanged.

| Case | Executed synthetic result | Remaining boundary |
|---|---|---|
| SC16 | PASS: no obligation before Q11 expiry; no deletion before expiry +24h; exact boundary completes with original anchors; late run alerts without shifting deadline; first approval +7d is used for unfinished onboarding. | Only new synthetic attempts with proven anchors/coverage. |
| SC20 | PASS subcases: migration leaves pre-015 copy/binding uncertified and intact; no inferred purge. | Overall PARTIAL: actual production writer/store inventory and cutover remain unverified. |
| SC22 | PASS subcases: still-eligible onboarding waits; unrelated live account is untouched; late write is denied and its original copy is later removed. | Overall PARTIAL: full legacy generations, all instances and other event classes remain outside scope. |
| SC23 | PASS subcase: late regeneration after generation seal cannot create an unfenced alias. | Overall PARTIAL: every crash point in a two-copy regeneration reservation remains unproved. |
| SC24 | PASS subcase: Q11 expiry fences a pending one-use transition handoff without restoring session access. | Other independent handoff/revocation combinations remain in the binding matrix. |
| SC25 | PASS: two separate worker processes/connections overlap at an observed database row-lock wait; one stable receipt results and all targeted copies are absent. | One local synthetic engine/profile. |
| SC26 | PARTIAL: a fresh claimant fences an expired claim token and resumes the same obligation. | A process paused during physical I/O was not proven quiescent solely by lease expiry; the lease remains a scheduling claim, not a filesystem fence. |
| SC27 | PASS subcases: crash after durable purge intent leaves the object and original deadline; retry completes that same obligation. | Other migration/host crash and external-store failure modes remain untested. |
| SC28 | PASS: missing exact object is reconciled; crash after unlink but before SQL acknowledgment produces no false receipt; retry verifies absence; lost final response is discoverable with one receipt. | Applies to a validated object in the isolated fixture root. |
| SC29 | PASS subcases: locked object stays pending; unlink failure is code-free and retryable; repair allows bounded completion. | Overall PARTIAL: no production multi-instance/provider partial-availability proof. |
| SC30 | PASS subcases: traversal is rejected; Linux symlink escape is rejected without changing the target; hard-link refusal is exercised on both profiles. | Overall PARTIAL: Windows symlink/reparse creation was NOT RUN; UNC/absolute/null variants are covered by the binding regression, not the consumer test, and production roots are unknown. |
| SC31 | PASS subcase: changed physical object generation/identity is preserved and receipt is blocked. | Overall PARTIAL: external object-store conditional-delete semantics are not implemented or verified. |
| SC18 | PASS subcases: changed account authorization version blocks purge; missing historical anchor does not create a guessed deadline. | Overall PARTIAL: unresolved non-Q11/logout retention stays unresolved. |
| SC33 | PASS subcase: operator status output omits event/account/copy pseudonyms. | Overall PARTIAL: no production exports, logs or provider diagnostics were audited. |
| SC37 | NOT RUN: independent backup/export expiry horizon and replay not exercised. | Requires actual inventory and independent recovery evidence. |
| SC39 | PASS subcases: disabled consumer refuses before connection; existing 014 schema upgrades to 015 and a second migration run is idempotent. | Overall PARTIAL: deployment/runtime grants and actual Azure destination guards are unverified. |

Other NOT RUN cases remain SC13 (reset), SC17 (Q14 deletion), SC19 (scoped
evidence holds), SC35 (independent journal restore), SC36 (restored alias and
privilege state), and SC37 (backup/export horizon). Those are not implied by
these Q11 synthetic checks.

## Executed environments and commands

All fixtures used generated synthetic accounts, fake mail, guarded disposable
databases and per-run private storage. No real user export, production database,
production session store, Azure resource or real email was accessed.

| Profile | Actual command / test entrypoints | Result |
|---|---|---|
| Windows native: PHP 8.5.11, MySQL Community 9.6.0, UTC, test DB port 3374 | `powershell -NoProfile -ExecutionPolicy Bypass -File .tmp/sc-native.ps1 -Mode regressions`; ran `tests/integration_session_fence.php`, `tests/integration_q11_signup_expiry.php`, `tests/integration_verification_retention.php`, `tests/integration_attempt_erasure.php`, and `tests/integration_session_copy_cleanup.php`. | 104/104 binding assertions; 74/74 Q11; 75/75 Q22; 57/57 attempt-erasure; consumer 35 PASS and 1 NOT RUN (Windows symlink/reparse fixture). Aggregate exit 0. Execution: 2026-10-18 17:59:43Z–18:03:57Z (19:59:43–20:03:57 Africa/Harare). |
| Linux container: PHP 8.4.26, MySQL Community 8.0.45, UTC, task-owned MySQL container | `powershell -NoProfile -ExecutionPolicy Bypass -File .tmp/sc-run-linux.ps1 -Mode consumer`; then `.tmp/sc-linux-regressions.ps1`, which ran modes `sessions`, `q11`, `q22`, `erasure` sequentially. | Consumer 36/36; binding 105/105; Q11 74/74; Q22 75/75; attempt-erasure 58/58. Aggregate exit 0. Linux exercised symlink escape refusal. Consumer: 18:07:28Z–18:08:25Z (20:07:28–20:08:25 Harare); regression batch: 18:09:57Z–18:15:17Z (20:09:57–20:15:17 Harare). |

The SC25 overlap evidence records two distinct process IDs and SQL connection
IDs and observed InnoDB row waits against a separate blocking connection. The
duplicate consumer work resolved to one receipt. This establishes overlap for
that disposable-engine path only; it is not a hosted-run or production claim.
The SC26 test checks stale claim-token rejection after a second claim, but does
not treat lease expiry as proof that a stalled process has ceased physical I/O.

Migration verification included a live synthetic session on the 014 schema,
an additive 015 upgrade, preservation/no-certification of that older mapping,
and a repeated migration run that skipped the completed version. No historical
session binding or manifest was backfilled. The test's migration and fixture
roles are synthetic; no production privilege set was changed.

CI configuration adds the consumer integration step to
`.github/workflows/ci.yml`. Hosted CI is **NOT RUN**. The temporary `.tmp`
evidence artifacts are local ignored test output; this document is the curated
tracked record and does not reproduce cookies, session bodies, raw keys or
fixture secrets.

## Open gates and next task

The production handler and its root, access controls, replicas, volumes,
instance coverage, GC and backups remain unverified. Writer cutover and authentic
legacy provenance are not established, so old mappings remain fail-closed.
Production scheduling/activation is not enabled. The recovery instruction is
not independently durable/published, no complete backup/export expiry horizon
was observed, and no witnessed restore was performed. Qualified privacy,
security and safety review, accepted named operators, backup coverage, overdue
alerts and independent sign-offs remain open. Hosted CI is unobserved.

Next existing bounded backlog task: L06 password-reset challenge, outbox and
session revocation, using the recorded Q47 choices on disposable systems. The
actual monitored support inbox and email acceptance remain gates; Q40 numerical
session settings remain proposed. No broader Q11/Q14 purge or production
integration is implied.
