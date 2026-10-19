# Q11 synthetic session-copy physical consumer evidence

Date: 2026-10-18, UTC; local display Africa/Harare. SC26 repair branch:
`codex/sc26-physical-io-fence-20261018`, based on roadmap audit
`774547cf`. Reviewer: Takudzwa Hondova. The historical owner-acceptance time is
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
is **15 PASS / 19 PARTIAL / 6 NOT RUN**. This continuation changes only SC26's
local synthetic physical-I/O subcase to PASS. SC26 remains PARTIAL overall
because the local process fence does not establish a supported production or
shared-store fence. Every other case status is unchanged.

| Case | Executed synthetic result | Remaining boundary |
|---|---|---|
| SC16 | PASS: no obligation before Q11 expiry; no deletion before expiry +24h; exact boundary completes with original anchors; late run alerts without shifting deadline; first approval +7d is used for unfinished onboarding. | Only new synthetic attempts with proven anchors/coverage. |
| SC20 | PASS subcases: migration leaves pre-015 copy/binding uncertified and intact; no inferred purge. | Overall PARTIAL: actual production writer/store inventory and cutover remain unverified. |
| SC22 | PASS subcases: still-eligible onboarding waits; unrelated live account is untouched; late write is denied and its original copy is later removed. | Overall PARTIAL: full legacy generations, all instances and other event classes remain outside scope. |
| SC23 | PASS subcase: late regeneration after generation seal cannot create an unfenced alias. | Overall PARTIAL: every crash point in a two-copy regeneration reservation remains unproved. |
| SC24 | PASS subcase: Q11 expiry fences a pending one-use transition handoff without restoring session access. | Other independent handoff/revocation combinations remain in the binding matrix. |
| SC25 | PASS: two separate worker processes/connections overlap at an observed database row-lock wait; one stable receipt results and all targeted copies are absent. | One local synthetic engine/profile. |
| SC26 | **Local synthetic subcase PASS.** Worker A paused after the object probe and preliminary ownership check while holding a non-expiring OS process fence. After the synthetic lease expired and A's SQL connection was killed, distinct worker B could not take over: it returned `busy`, and claim hash/version, file, delete intent and receipt state stayed unchanged. A resumed, failed its current-claim check, and returned incomplete without unlinking or publishing a receipt. Once A exited, B safely retried the same generation and completed with one receipt. | **Overall PARTIAL.** This proves the guarded local filesystem variant only. Production handler/store, shared or remote lock semantics, lock persistence/recovery and full instance coverage remain unverified; no lease-only takeover is enabled for the supported synthetic consumer. |
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

The table above records the earlier `5e7c5a5` checkpoint and is retained as
historical evidence. The focused SC26 repair run below supersedes its consumer
counts/timings; later regression totals are listed exactly here.

### SC26 repair run and affected regressions

| Profile | Command | Result |
|---|---|---|
| Windows native: PHP 8.5.11, MySQL Community 9.6.0, UTC, disposable test DB port 3374 | Focused consumer: `powershell -NoProfile -ExecutionPolicy Bypass -File .tmp/sc-native.ps1 -Mode consumer`; affected regressions are separately recorded below. | Focused consumer 36 PASS / 1 NOT RUN (Windows symlink/reparse fixture unavailable), exit 0. Run 2026-10-18 21:46:02Z–21:46:34Z (23:46:02–23:46:34 Africa/Harare). Local `evidence.json` is under ignored `.tmp/sc-run-a438a85dd5f0a7c5a14e9b66f7a131c5/`. |
| Linux container: PHP 8.4.26, MySQL Community 8.0.45, UTC, task-owned disposable MySQL container | Focused consumer: `powershell -NoProfile -ExecutionPolicy Bypass -File .tmp/sc-run-linux.ps1 -Mode consumer`; affected regressions are separately recorded below. | Focused consumer 37/37 PASS, exit 0; symlink escape refusal exercised. Run 2026-10-18 21:46:42Z–21:48:19Z (23:46:42–23:48:19 Africa/Harare). Local `evidence.json` is under ignored `.tmp/sc-run-d8ebc7352dd6d01908b2b8a589df6b09/`. |

The SC25 overlap evidence records two distinct process IDs and SQL connection
IDs and observed InnoDB row waits against a separate blocking connection. The
duplicate consumer work resolved to one receipt. This establishes overlap for
that disposable-engine path only; it is not a hosted-run or production claim.
For the latest focused SC26 runs, Linux recorded A PID 36 / SQL connection 886
and B PID 37 / connection 887; Windows recorded A PID 20140 / connection 59
and B PID 35592 / connection 60. In each run the order was:
A reached `before_unlink`; the synthetic DB clock expired its claim; the test
killed A's SQL connection so the row lock no longer serialized B; B returned
`busy` without changing claim version 1, unlinking, or creating a receipt. At
pause, after B and after A resumed, the manifest remained `published` with the
same object generation, the file remained present, the purge intent remained
`deleting`, and receipt count remained zero. A resumed and returned
`incomplete` without unlinking or recording completion; after A exited, B
retried and completed with the manifest `erased`, file absent and exactly one
receipt. This is direct
local physical-I/O ownership evidence, not merely stale-token rejection. The
non-expiring OS lock makes a stalled holder fail closed by keeping contenders
busy; it has no forced-break or remote-store guarantee. A permanently wedged
owner can hold the obligation pending until the process is stopped/repaired.

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
instance coverage, GC and backups remain unverified. The OS lock is validated
only for the guarded local synthetic store; semantics on Azure/shared or remote
storage, crash/power-loss recovery and multi-host ownership are not established.
Writer cutover and authentic
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
