# New-attempt session binding and write-fence foundation

Continuation of `077b8c78b0b556c887b241b9666ce24efc1eebfe` on
`codex/session-binding-write-fence-20261018`. This is local implementation and
synthetic engineering evidence, not independent privacy/security review,
operational acceptance or production activation. Owner approvals and their
uncaptured historical acceptance timestamps are unchanged.

## Implemented boundary and engineering review

| Contract concern | Implementation / reviewed behavior |
|---|---|
| Trusted account generation | [Managed handler](../../php/managed_session.php) `authority()` requires the current account, exact `signup_attempt_key`, original registration in `signup_attempt_identities`, current authorization version and usable status. Pending stages also require the original, consistent Q11 clock and reject at/after expiry. No legacy mapping/backfill or zero-version assumption. |
| Password proof versus replacement | [Login](../../php/auth.php) retains the exact server-read identity for which the password succeeded. `Handler::admit()` rechecks its generation, original creation and verified password hash under the account lock. A reload by email cannot transfer proof to G2, even with the same numeric ID, email, version, creation second and password hash. |
| Minimal registry | [Migration 014](../../database/migrations/014_session_binding_fence.sql) adds bindings, immutable copy references/write intents, regeneration lineage, one-use handoffs and component obligations. No payload, raw SID, cookie, email, CSRF or MFA assertion in these records; no FK/cascade into users or safety history. No pre-existing rows are mapped. |
| Payload minimization | Only CSRF, existing activity marker and authoritative account ID/generation/version/role are supported. Email/profile/content and cached MFA/capability assertions are rejected. Pending identity is authentication for the pending flow, not email/adult/onboarding permission. Existing route-stage guards still apply. Role is reloaded. Operational MFA/assignment issuers remain unavailable. |
| Read/write fence | Account -> binding -> manifest -> bounded local object operation. Read checks authority before payload exposure. Every explicit, PHP lazy-timestamp and controlled implicit-shutdown write rechecks generation, epoch, revision, current state and deadline. The handler connection uses UTC and READ COMMITTED; the second deadline read cannot reuse a transaction snapshot from before I/O. Stale CAS never merges old identity/capabilities. |
| Reservation and non-atomic storage | The write intent commits before I/O. A fresh random non-bearer object reference identifies one immutable, AES-256-GCM sealed copy, with a purpose-derived payload key. Publication revalidates authority after I/O. SQL failure/crash can leave a manifested unpublished object; it cannot make that object readable. A later consumer must reconcile it. SQL rollback is not claimed to erase files. |
| Regeneration and handoff | `Handler::rotate()` records both refs/lineage and retires the old alias before the new private seed is used. Fresh login clears the prior principal and CSRF/capability state. Verification/onboarding issue a pending handoff only within their actual successful locked transaction; the private process context consumes it once against the exact binding revision and current account version. Logout/new revocation beats a delayed response. Crashes can require fresh authentication; they never revive the old alias. |
| Response/failure handling | [Session manager](../../php/session_manager.php) uses the guarded handler at bootstrap, read, regeneration, close and shutdown. Buffered fixture HTTP responses suppress private output, successful redirects and renewal cookies on failed publication. Store/registry errors have fixed diagnostics and no native fallback. Early-flushed/streaming/FastCGI response integration is not supported or operationally accepted by this fixture. |
| Current logout | Retires only the presented binding durably, including a binding whose old payload became unreadable during a handoff. Clearing the cookie does not claim physical payload deletion. Other sessions are not logged out. Logout-all/reset/ban-writer and operational privileged-revocation integration remain separate work. |
| Q11 obligations | [Expiry hook](../../php/signup_lifecycle.php) records `session_copies_v1` using the exact original Q11 receipt, generation, expiry and expiry+24h deadline, in the expiry transaction. Retries neither duplicate nor renew it. It remains `pending_consumer`; QE's `erase_attempt_v1` / `verified_live_scope_v1` and Q22 receipts are not relabelled as session erasure. |
| Recovery | Component records remain `unpublished`, copy inventory `unverified`, retain-until NULL. Restored encrypted fixture bytes are denied by current retirement state. Restoring an older DB/index requires the independent current protection journal and startup replay, which this foundation does not supply. No universal 43/49-day TTL or proposed backup setting is adopted. |

`BUSHISA_SESSION_SYNTHETIC=true` is the only installation entry. Before a
connection, it requires all existing closed Q11 development/disposable/fake-mail
guards, loopback and an explicit `bushisa_test_q11_*` database, plus the owned
`.tmp/sf-storage-<nonce>` root, matching namespace marker and protected fixture
key. It refuses already active unmanaged sessions, missing/ambiguous roots or
keys and unsafe locators. Rejection of an already active handler aborts its
pending PHP write. The adapter supports **only this private immutable
local fixture namespace**, shared by cooperating synthetic workers. No existing
or real native session files are read, swept or adopted. One exact newly created
private native fixture verifies pending-write discard at the unsupported-handler
boundary; it does not establish native-handler support or production coverage.
There is no production apply mode,
remote adapter, general GC consumer or operational handler selection.

The production/native handler path remains outside this integration. No actual
production handler, store/instance coverage, filesystem permissions, key custody,
writer cutover, legacy provenance or independent durability was inferred from
these fixtures. HMACs and account/copy references remain protected personal
data, not anonymous identifiers.

## Execution and attribution

The reproducible suite is
[`tests/integration_session_fence.php`](../../tests/integration_session_fence.php),
using the separate guarded [CLI fixture](../../tests/support/session_fence_worker.php)
and [HTTP probe](../../tests/support/session_fence_probe.php). Fixtures contain
only synthetic accounts, private synthetic client capsules and fake mail.
Capsules/keys/session objects stay in ignored isolated storage; the committed
evidence contains fixed checks, counts and worker/connection identities only.
[`tests/unit_session_fence_guard.php`](../../tests/unit_session_fence_guard.php)
also tests pre-connection rejection with PDO disabled.

Final commands, exact versions, UTC execution instants, migration rehearsals,
grants, per-case assertions and observed SQL waits are recorded in the linked
JSON artifacts below. These execution times are not owner approval times.

| Local profile | Final session suite | Migrations / overlap | Artifact |
|---|---|---|---|
| Windows, PHP 8.5.11 NTS / mysqlnd 8.5.11; owned MySQL 9.6.0 | **104/104 assertions PASS**, 11 overlaps | Fresh 014 + repeat; existing 013 upgrade + repeat; no legacy bindings fabricated | [Native exact results](evidence/session-fence-native-2026-10-18.json) |
| Linux, PHP 8.4.26 / mysqlnd 8.4.26; cached MySQL 8.0.45 | **105/105 assertions PASS**, 11 overlaps | Same migration variants; additional Linux symlink case | [Linux exact results](evidence/session-fence-linux-2026-10-18.json) |

Both profiles passed the **13-case pre-connection guard unit** with PDO disabled.
All **11 affected regression commands per profile passed**: Q11, Q22, attempt
erasure, signup/mail units, signup, fake SMTP/outbox, core authorization, policy,
report and pair suites. Q11 (74 assertions), Q22 (75), erasure (57 Windows / 58
Linux), reports (245 / eight overlaps) and pairs (396 / 11 overlaps) retain their
exact run/path attribution in the [regression record](evidence/session-fence-regressions-2026-10-18.json).
After the password-generation repair, the directly affected Q11, signup and
core suites passed again on both profiles. Other completed regression evidence
was reused; it is not represented as a second execution. No browser suite was
run, and these local runs do not identify current Azure PHP/MySQL versions.

The committed entry points are:

```text
php tests/integration_session_fence.php
php -n -d disable_classes=PDO tests/unit_session_fence_guard.php
```

Use an explicitly authorized disposable loopback database and the existing
development/test/runtime-DDL-disabled guards, Q11 synthetic/fake-queue flags,
fake mail provider/transport, synthetic attempt-erasure flags and guarded HTTP
overlap flag. Fixture/migration setup uses the disposable fixture administrator;
actual route/session and erasure workers use the recorded restricted test
account. Its combined fixture grants support erasure race execution and are not
a proposed production role. The suite creates its own isolated namespace/key.
The recorded `.tmp` wrappers select the two owned runtime profiles; they contain
only local fixture setup and are not operational activation instructions.

Source hashes in the JSON records identify the frozen local implementation
(normalized LF). They do not authenticate legacy/production history, substitute
for independent review or claim a hosted CI run.


Every overlap record identifies distinct processes/connections and observed
engine row-lock waits. The password-proof/replacement race additionally records
the replacing parent writer and the HTTP connection waiting on its transaction.
The extra observer only rendezvous-controls that test; it is not counted as the
replacing writer. Handoff tests run the actual `verify_email.php`, `onboarding.php`
and `logout.php` routes. Synthetic failures are not called engine deadlocks or
independent recovery proof.

## SC01–SC40 disposition

The [existing 40-case matrix](account-session-copy-contract.md#7-planned-acceptance-matrix)
is updated individually. A PASS is confined to the supported synthetic boundary;
the whole matrix and external release gates are not complete.

| Cases | Executed scope and remaining variant |
|---|---|
| SC01–SC03, SC05–SC09 | Anonymous entered-code verification without login; new-attempt password admission and pending-route restrictions; missing/contradictory generation/version; all 22 current protected route callers after revocation; exact ±1µs Q11 stage boundaries; resends/reads preserve anchors; completed onboarding survives stale pending eligibility; exact one-use verification/adult handoffs and rejected invented versions. |
| SC04 **PARTIAL** | Numeric ID/email/version reuse and actual password-proof replacement race are denied; old-generation logout preserves G2. Future session-copy deletion against a replacement generation is NOT RUN. |
| SC10–SC12 **PARTIAL** | Current logout, versioned synthetic restriction/lift, current role withdrawal and rejected cached MFA tested. No logout-all/reset endpoint, reviewed ban-writer integration, independent assignment/MFA issuer or staffing claim. The legacy suspension writer still needs durable revocation alignment. |
| SC14 | Regeneration replaces ID/CSRF, retires old alias and preserves the trusted principal. Fresh password login switches principal without old identity/capability state. |
| SC15 **PARTIAL** | Managed polling leaves Q11 anchors unchanged; activity refresh is excluded by code review, with no proposed timeout adopted. Intentional-activity and proposed numerical/privileged production settings remain pending. |
| SC18 / SC20 **PARTIAL** | Original Q11 component deadline and once-only obligation, missing generation/root/key and stale manifests fail closed. Broader Q14/missing-event/coverage reconciliation and unknown physical deadlines are not implemented. |
| SC21 | Lazy update, explicit close and implicit shutdown share the fence. HTTP writes lose to expiry, version advancement and current logout; competing implicit writes reject the stale revision. No private success response follows failed publication. |
| SC22–SC23 **PARTIAL** | Real ordinary-erasure/HTTP-write overlap preserves the pending session component; regeneration wins over a stale explicit writer, and its new copy remains usable. Future physical cleanup/lease and all provisional-regeneration-crash variants remain NOT RUN. |
| SC24 | Both actual verification and onboarding response handoffs lose to concurrent current logout and newer account authorization, with distinct workers and simultaneous SQL waits. |
| SC27 **PARTIAL** | Process exits after committed write intent and after object creation leave a reserved manifest and the prior committed copy readable on retry. Physical-purge intent/recovery variants remain NOT RUN. |
| SC29–SC33 **PARTIAL** | Local store failure, unsafe path forms, hardlinks, stale manifest epoch, unmanaged active handler, forbidden payload/capability fields and content-free receipts/diagnostics tested. Linux symlink variant is recorded separately; Windows reparse/symlink equivalence, physical conditional deletion, partial real instances/remote adapters and old-payload erasure remain NOT RUN. |
| SC34 | Restored synthetic encrypted bytes cannot bypass the current retirement registry. This does not restore an old DB or prove independent-journal continuity. |
| SC38–SC40 **PARTIAL** | Missing keys/namespace/root, unsafe DB before connection, missing manifest INSERT privilege and a second cooperating HTTP instance tested. No real key rotation, operational role acceptance, disabled physical consumer or complete volume/instance inventory claim. |
| SC13, SC16–SC17, SC19, SC25–SC26, SC28, SC35–SC37 **NOT RUN** | Future reset, physical cleanup/holds/leases/conditional deletion and independent restore/recovery/copy-bound variants. Existing A/LB/LC/QS/QE/QM statuses and unexecuted variants are unchanged. |

## Remaining gates and next bounded task

**Engineering:** implement the Q11-only `session_copies_v1` physical consumer for
this supported synthetic namespace: bounded claims, original generation/event/
copy/epoch checks, sealing of unpublished intents and aliases, conditional exact
object absence verification, resumable failures, stable component receipts and
minimal recovery-interface ordering. Include duplicate workers, crashes on both
sides of deletion/acknowledgment, hold exclusions and newer-generation isolation.
Do not enable legacy sweeps or operational scheduling. Existing Q11 erasure
approval is sufficient for that bounded work; it needs no new owner decision.

**Policy:** P01 production handler/topology and proposed Q40 numerical settings;
P02 physical retention after ordinary logout/regeneration/idle/nondeletion
revocation, anonymous sessions and standalone mapping/receipt retirement. These
remain unresolved, with no borrowed deadline. They do not reopen Q11/Q14.

**Operational / qualified review:** actual handler/root/permissions and every
instance/volume, legacy provenance and writer cutover/reauthentication, protected
key management, real session/ban/reset/privileged integrations, independent
protection publication/replay/no-loss proof, copy inventory and witnessed isolated
restore. Named security/privacy reviewers and operators/backup/alerts remain
unassigned/unverified. No staffing, MFA, availability or release sign-off is
created by this engineering review.

CI configuration adds the guarded suite and pre-connection unit guard, but
**hosted candidate CI is NOT RUN**. Local results do not replace exact earlier
commit/run attribution. No production or real session-store access, real mail,
scheduling, push, merge or deployment occurred.

Documentation/static validation: `git diff --check`, syntax for the 12 changed
PHP files, **123 local links / six heading targets**, all 40 SC statuses,
recorded source hashes, code-free evidence and regression-result consistency
pass. These static checks are separate from the functional executions above
and do not constitute hosted CI or qualified review.
