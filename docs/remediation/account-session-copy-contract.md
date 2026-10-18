# Account/generation-to-PHP-session copy contract

Repository checkpoint: `9053428ad9622feddb7fe5b4d955cfe947a1f313`.
Branch: `codex/account-session-copy-contract-20261018`. Inspection date:
2026-10-18, Africa/Harare. Starting HEAD matched the requested checkpoint and
the index/tree were clean. No AGENTS.md was found in the checkout or ancestors.

**Current continuation from `077b8c7`:** the new-attempt binding/write-fence
foundation is implemented behind a guarded synthetic handler. See the
[focused engineering evidence](session-binding-write-fence-evidence-2026-10-18.md)
for supported boundaries, exact checks and remaining gates. The current matrix
is **12 PASS / 18 PARTIAL / 10 NOT RUN**; no physical cleanup, production handler
activation, independent review or restore acceptance is claimed.

The original documentation checkpoint was design-complete and ready for
engineering review. At that checkpoint no PHP, database/session-store access or
functional execution occurred and all SC cases were NOT RUN. Sections 1–5
preserve that original source inventory/design; the continuation below and
linked evidence distinguish the new synthetic implementation. No real session
files, user data, production configuration or provider account was accessed.

Authority: [current decisions](decisions.md), especially
[Q11/Q14/Q20/Q25/Q26/Q38/Q40/Q47](owner-policy-reconciliation-2026-10-15.md),
[retention policy](../data_retention_policy.md),
[retention matrix](data-inventory-retention-matrix.md) and
[L03 C09/W06/G03/G04/G12 inventory](l03-category-clock-writer-inventory.md).
The [Q11 clock evidence](q11-signup-expiry-evidence-2026-10-18.md),
[attempt-erasure scope](q11-attempt-erasure-evidence-2026-10-18.md) and
[Q22 cleanup evidence](q22-terminal-mail-cleanup-evidence-2026-10-18.md)
explicitly exclude physical PHP session copies. This contract addresses that
gap without changing their receipts or results. Reviewer: Takudzwa Hondova;
qualified reviewers/operators remain unassigned. Exact historical acceptance
UTC/Harare timestamps were not captured; this inspection date is not approval.

## 1. Actual handler, fields and writer inventory

At the original contract checkpoint, the application called PHP's configured
session handler. No tracked application
`session_set_save_handler`, `session_module_name`, `session_save_path`, save
handler/path INI setting, DB session table or explicit session GC scheduler was
found. Therefore **the deployed handler is UNVERIFIED**, not certified as files
or shared DB storage. Its root, serializer, auto-start/lazy-write behavior,
locking, GC settings, permissions, replicas, worker/instance coverage, slots,
container persistence, backups and exports are also UNVERIFIED. Do not infer
shared storage from `/home` or a mail WebJob's working directory.

The only explicit save-path overrides found are synthetic test harnesses, for
example [Q11](../../tests/integration_q11_signup_expiry.php),
[attempt erasure](../../tests/integration_attempt_erasure.php),
[L01b](../../tests/integration_l01b_reports.php),
[L02a](../../tests/integration_l02a_pairs.php),
[L02d](../../tests/integration_l02d_imports.php),
[L02e](../../tests/integration_l02e_races.php) and
[L03a](../../tests/integration_l03a_lifecycle.php). They create isolated fixture
sessions and pass a save path to their test servers. Reading that source does
not inspect its generated files or establish production behavior. The
[test Dockerfile](../../tests/support/l02e.Dockerfile),
[CI workflow](../../.github/workflows/ci.yml) and [.htaccess](../../.htaccess)
do not prove an effective production handler or instance topology.

| Path / function | Creation, reads, writes, close or authorization behavior | Consequence / gap |
|---|---|---|
| [Session bootstrap](../../php/session_manager.php): module-level call, `start_secure_session()` | Including the module starts/resumes a session, sets strict mode and cookie parameters, reads `last_activity`, then refreshes it. It can create an anonymous session before database/account authorization. | There is no generation or copy registration. Refresh occurs even on public/denied/polling requests; bootstrap is not proof of login. |
| Same file: `bushisa_session_cookie_params()`, `bushisa_cookie_secure()`, `refresh_session_expiry()` | Code uses 1,800s cookie lifetime, path `/`, empty domain, HttpOnly, SameSite=Strict. Secure uses an environment override or HTTPS/server-port detection. Writes PHP `time()` activity and refreshes the cookie. | These are source settings, not verified ingress behavior or adopted Q40 defaults. No explicit absolute or privileged timeout. Existing idle check uses `>` rather than a new persisted at/after expiry boundary. |
| Same file: `regenerate_session()`, `destroy_session()`, `bushisa_auth_fail()` | Regeneration calls `session_regenerate_id(true)` and refresh; destruction clears the current array, expires the current cookie and calls `session_destroy()`. Auth failure destroys then redirects/returns an error. | No result/copy receipt, old/new lineage index, durable per-session revocation, all-device enumeration or physical-erasure proof. Return values from native regeneration/destruction are not checked. |
| [Login](../../login.php), [authentication](../../php/auth.php): `attempt_login()` | Password/current account and pending-stage eligibility checks, then regeneration; writes four authenticated fields listed below. No account lock spans credential snapshot through session commit. | Auth can race account/version changes; later guards can deny it, but a stale sensitive copy may already be written. Regeneration preserves the surrounding array unless fields are explicitly replaced; no full secret-field clearing contract exists. |
| [Register](../../register.php), `register_user()` in auth, [verification](../../verify_email.php): `bushisa_verification_user()` | Registration commits account/attempt/challenge and renders a verification link; it does **not** authenticate the browser. Public forms generate CSRF state. Verification may resolve an account through the authenticated ID or supplied email; supplied email is not itself stored as session ownership. | Do not bind an anonymous browser to an account using email, URL, IP or successful registration alone. Anonymous entered-code verification must remain available under existing decisions. |
| Verification route; [signup service](../../php/signup_verification.php): `bushisa_verify_signup_code()` | Captures an authenticated old version if present, calls `session_write_close()` before the account transaction. Successful verification increments DB version and sets first approval once. Anonymous success asks for ordinary login. | Snapshot variables survive close; they are not a live session lock/authority. No verification-code body is intentionally persisted in the session. |
| [Onboarding](../../onboarding.php), `bushisa_complete_adult_onboarding()` | Requires login, closes before DB work; successful completion increments version; rejection also increments version and underage rejection attempts destruction of the current session. | DB DOB is not intentionally copied into session. A failed transition must not rebind an old session or erase a completed generation. |
| Session manager: `bushisa_resume_transition_session()` | Reopens and updates only if current session ID context still has the same account/old version and the DB account is usable at the expected new version. | Useful existing guard, not an immutable generation/lineage or final-write fence. It does not call regeneration. Resume/start can itself refresh/write state before validation; abandoned/denied writes need coverage. |
| `require_login()`, `bushisa_current_account()`, `bushisa_account_can_use_session()`, `bushisa_gate_pending_account()` | Reload account/status/version, run pending-stage Q11 expiry, deny restricted accounts/version mismatch, refresh cached role/email, restrict pending routes and refresh activity. Missing version is coerced to zero. | No account-generation comparison or per-session revocation row. `require_login()` is an entry check, not proof that every later private read/mutation rechecks after concurrent revocation. |
| [CSRF](../../php/csrf.php): `generate_csrf_token()`, `validate_csrf_token()` | Generate/reuse server session secret; validate submitted token against it. | CSRF proves neither account identity nor MFA. Tokens must not enter mapping/cleanup receipts. |
| [Logout](../../logout.php); [deletion page](../../account_delete.php), [deletion API](../../api/account.php) | Logout destroys the current session only. Both deletion handlers reauthenticate and call `anonymise_user()`, then destroy current session. Page UI is disabled but backend handlers exist. | Logout is not logout-all. No logout-all/reset route or global session-copy consumer was found. UI-disabled controls do not disable server paths. |
| [Anonymiser](../../php/anonymiser.php): `anonymise_user()`, `anonymiser_soft_delete_user()`; [Q11](../../php/signup_lifecycle.php): `bushisa_signup_expire_locked()` | Deletion changes account usability and increments session version. Q11 uses account-first locking, immutable expiry +24h obligation, and one version increment. | Other cookies become unusable when authoritative guards recheck; physical session copies remain. Anonymiser has no session index/replay adapter and is not certified full account erasure. |
| [Moderation](../../php/moderation.php): `suspend_user()`, `unsuspend_user()` | Toggle `is_suspended` without a session-version increment; no role-change writer found in tracked application code. | An untouched old session can potentially work again after unsuspension. Required durable ban/privilege revocation remains an implementation gap; reviewed lifting cannot revive revoked session credentials. |
| [Helpers](../../php/function.php): `get_current_user_id()`, `is_admin()`, `is_moderator()`; [frontend](../../php/frontend.php): `render_notification_panel()` | Read cached identity/role; notification helper queries by cached ID. | They are not independent current-account authorization. Callers must retain entry and final resource checks. Notification data is rendered rather than intentionally stored in session. |
| [Report reviewer](../../php/report_intake.php): `bushisa_report_require_reviewer()`, `bushisa_report_review_evidence()`; [safety API](../../api/safety.php) | Check current actor/version/role/case assignment and server-loaded MFA-bound fields. Safety API snapshots auth and closes before its service transaction. | No production MFA issuer for those fields found; synthetic assertions are not accepted staffing/MFA integration. Role alone does not authorize private evidence. |
| [Evidence API](../../api/evidence_lifecycle.php), [lifecycle](../../php/evidence_lifecycle.php): `Context::fromServerSession()`, `verify_proof()` | Separate context/CSRF authorization path; closes before DB use. Operational authority integration fails closed; guarded synthetic proof checks current role/version/assignment/capabilities. | This route does not call `require_login()`; include its context and proof revocation in any handler integration. Never accept body/header role, MFA or timestamp assertions as authority. |

**Complete tracked `require_login()` caller groups at this checkpoint:**

- Pages: [discover](../../discover.php), [matches](../../matches.php),
  [chat](../../chat.php), [confessions](../../confessions.php),
  [profile](../../profile.php), onboarding and account deletion linked above.
- Admin: [dashboard](../../admin/dashboard.php),
  [moderation](../../admin/moderation.php), [reports](../../admin/reports.php).
- APIs: [discover](../../api/discover.php), [find match](../../api/find_match.php),
  [matches](../../api/matches.php), [swipe](../../api/swipe.php),
  [pairs](../../api/pairs.php), [requests](../../api/message_requests.php),
  [messages](../../api/messages.php), [confessions](../../api/confessions.php),
  [questionnaire](../../api/questionnaire.php),
  [notifications](../../api/notifications.php), account and safety linked above.

Explicit early-close sites are verification/onboarding and the APIs for matches,
swipe, pairs, requests, messages, confession reporting, safety and evidence
lifecycle. Pair/report services recheck captured versions through
`bushisa_report_actor_ok()` and their transactional authorization paths; this is
not a general proof for other routes. [Index](../../index.php), login and
registration merely redirect on an existing `user_id`, without `require_login()`.
Any route left with an active session can reach implicit request-shutdown write;
the inventory must not stop at explicit assignments/`session_write_close()`.

### Current field mapping

| Server session field | Producer / consumer | Identity/security meaning and minimal future treatment |
|---|---|---|
| `user_id` | `attempt_login()`; all authenticated routes/helpers | Numeric account reference, not generation proof. Bind to authoritative immutable account generation before supported authenticated storage. |
| `session_version` | Login, transition resume; account/route/service checks | Account authorization epoch, not account generation or physical copy version. Do not default missing proof to an authorized zero in the new contract. |
| `role` | Login and `require_login()` refresh; admin/frontend helpers | Cached display/authorization hint only; reload current privileges at protected action. Clear old privileged claims on identity/scope change. |
| `email` | Login and `require_login()` refresh; verification fallback | Unnecessary persisted contact copy for authentication. Derive from current authorized account when needed; future supported payload allowlist excludes this field. Existing copies still require deletion, not just future minimization. |
| `last_activity` | Bootstrap/refresh on reads and protected calls | Currently PHP wall-clock/polling-renewed; not intentional foreground proof or account-inactivity anchor. |
| `csrf_token` | CSRF module, forms/APIs | Raw security secret inside the protected payload only; never log/index/export. |
| `mfa_verified`, `mfa_user_id`, `mfa_session_version` | Reviewer consumers; test-only producers found | No operational issuer located. A client boolean or stale cached value cannot establish/recreate MFA. |
| `evidence_authority_assertion` | Synthetic L03a context/proof fixture | Secret capability material, not an operational provider. Remove from retired payloads; never copy into cleanup receipts. |
| Account generation, session reference, binding epoch, copy version | **Not present in current session writers** | New supported mapping/handler interfaces are required; migration 012 does not automatically bind existing sessions. |

## 2. Lifecycle and deadline contract

**Immediate revocation** denies later authorized reads/actions and rejects stale
session writes. **Live erasure** requires verified removal of every in-scope
stored payload/copy; cookie expiry, status/version changes and native destroy
calls are not that proof. **Backup expiry** and independent replay are separate.
Already delivered responses/device copies cannot be recalled by deleting a
server session. No new whole-account/chat deletion workflow is authorized here.

| Event / policy | Required logical behavior | Physical copy deadline / unresolved scope |
|---|---|---|
| Pending-email expiry, Q11 | At original creation +72h, deny the attempt and retire its sessions across versions/instances. Resend/read/retry does not renew. | Its ordinary live session payload copies fall within original expiry +24h; late discovery does not shift that deadline. |
| Unfinished onboarding expiry, Q11 | At first successful email approval +7 elapsed days, same denial. Completed onboarding must survive a stale pending-stage job. | Original onboarding expiry +24h for eligible attempt copies. Never substitute challenge consumption or intake time for first approval. |
| Verified account deletion, Q14/Q27 | Immediately revoke all sessions/credentials for that account generation and deny restored/new attempts to use it. | Ordinary live session payloads within deletion +7d; earlier applicable Q11 obligation wins. This does not extend media's separate 24h deadline. |
| Logout, Q40/current scope | Revoke the current session binding durably before acknowledging logout; attempt current-handler destruction and cookie removal. Other current sessions remain unless logout-all is explicitly invoked. | Native synchronous destroy success must be verified; a general residual-copy deadline after logout is **not selected**. Do not borrow Q22's one-hour/seven-day clocks. |
| Logout-all, successful password reset, ban, Q40/Q47/Decision 32 | Atomic account authorization-version advance and revocation of affected bindings. A reset request alone does not revoke anything. Lifting a reviewed ban never revalidates old credentials. | General physical-retention periods after these nondeletion events are **unresolved**; enforce any independent earlier Q11/Q14 obligation. Reset/logout-all endpoints are not implemented. |
| Privilege/assignment/MFA revocation | Deny the revoked capability against current authoritative state; fence old privileged claims, including delayed responses/writes. Fresh reviewed authority is needed for a later grant. | No blanket ordinary-session/audit retention period is approved by Q40/Q23. Preserve separately required case audit, not entire session payloads. Exact session-wide versus capability-only scope must be specified by that authority integration; no automatic privilege restoration. |
| Legitimate verification/onboarding version transition | Only the same current principal/session may perform the authorized old-to-new epoch handoff. Other old bindings remain revoked; no guessed next version. | Preserve the valid new binding/copies. Retirement of old copies has no new general timer here; existing deletion/expiry obligations remain original. |
| Regeneration/login rotation/idle expiry | Retire old binding aliases; an old request cannot recreate them. Cookie/timeout behavior is distinct from stored-copy purge. | General old-copy/anonymous/idle-expired payload retention is **unresolved**. Current code's 1,800s is not an adopted physical erasure period. |
| Replacement signup or reused email/numeric ID | A real new registration has a different immutable generation and repeats eligibility. An old job/session cannot target or authenticate it. | Old obligations retain their original deadlines and exact generation/copy scope. Never select by email or adopt the replacement's verification/session version. |
| Optional location withdrawal, blocks, unmatch, probation | Recheck the relevant consent/resource restriction independently of session allowance. Unmatch alone preserves approved chat; probation is not a session ban. | Do not mass-delete or reauthorize sessions/chat merely because a pair or optional-location state changes. Preserve Decisions 1–33 and their ledgers. |
| Underage rejection, D02/Q12 | Permanently deny that rejected attempt; revoke its sessions. Do not infer permission for a later fresh adult application. | Apply approved 24h erasure to any copied rejected profile/DOB/answers; do not invent a general whole-session retention rule or 365d child-tracking record. Such sensitive fields are excluded from new session payloads. |

Q25 governs every new deadline: server UTC, elapsed hours/days, persist the
original expiry and reject operations at/after it. Reads, job claims, retries,
exports, restoration and hold reviews never renew it. Any legitimate later
event has its own identity and scope. Q40 permits only intentional foreground
activity to refresh idle time; current polling refresh is a gap. Its numerical
idle/absolute values, shared DB store, one-instance plan and Lax cookies remain
**proposed**. This contract does not adopt them or relax Strict.

## 3. Minimum protected mapping and admission

The following are required design fields/interfaces, not an approved
production store choice. The bounded synthetic implementation now uses
[migration 014](../../database/migrations/014_session_binding_fence.sql) and its
linked evidence; the broader cleanup/recovery interfaces remain gated. No
historical migration or legacy mapping is rewritten.

| Planned record | Minimum required fields / authority | Secret/access restrictions |
|---|---|---|
| Account binding | Server-read account ID, immutable `users.signup_attempt_key`, matching `signup_attempt_identities.attempt_key/registered_at`, original creation, current authorization version/status | Keys are recorded only by [actual new registration](../../php/signup_attempt_erasure.php) `bind_creation()` via Q11, with [migration 012](../../database/migrations/012_signup_attempt_erasure.sql)/[immutable guard](../../php/attempt_erasure_migration.php). No email, IP, session claim or missing row supplies generation proof. These references remain personal data. |
| Session binding | Random internal `session_ref`; server-keyed HMAC of incoming session ID with store namespace/key version; immutable account generation; binding epoch/auth version; scope class; state/revoked event; current copy revision | No raw session ID/cookie, CSRF value, email, password, challenge, MFA assertion or serialized payload in index/receipts. HMAC/key custody, rotation and least privilege require review; this is not anonymization. |
| Copy manifest | Binding epoch; immutable copy ID; approved handler/store/namespace version; exact instance/replica coverage; opaque locator; immutable object/row generation; state/write-intent ID and observed result | Provider locator is not authorization. If a native handler forces a bearer ID into its locator, encrypt that locator separately for the adapter only; never use a plaintext ID or its filename in a job/log/export. Prefer a non-bearer, server-generated immutable object namespace for a new adapter. |
| Regeneration/transition lineage | Old and new internal refs/epochs and exact copy refs, server transition event, expected old/new versions, one-time completion state | New copies cannot inherit stale privileged claims. A handoff is authorized by the actual locked account transition plus still-current binding, never a client-supplied version/role. An anonymous CSRF session is not promoted using an email lookup. |
| Revocation/purge obligation | Stable event/operation ID; exact generation/session/epoch scope; revoked authorization floor or closed-generation flag; original effective time/deadline and policy reference; explicit unknown deadline reason | Revocations are monotonic. Account generation, authorization version, binding epoch and physical copy generation are distinct. No global decrement/reset to repair an old job. |
| Work/receipt/recovery | Bounded claim and progress cursor, protected copy refs, action/version/deadline, verified absence result, fixed error code, publication/copy-inventory/retain-until status | Worker claim secrets hash-only; no serialized session/content digest/credential in diagnostics. Completion receipts are scope-specific. Ordinary receipt retirement not already covered by Q26 needs purpose/retention review; no general Q23 period is adopted. |

An authenticated supported binding requires current authoritative account state,
matching immutable generation and exact authorization version, nonrevoked
session/epoch, the applicable Q11 deadline and current resource/privileged
permission. Check before exposing private data and again at protected mutation
commit/write publication; a PHP mutex or an old `$_SESSION` snapshot is not
authority. Once the account-generation/index authority is unavailable, deny
authenticated admission/write rather than fall back to arbitrary cookie data.
Anonymous entered-code verification stays an independently validated flow; it
must not restore a stale authenticated binding or expose account private data.

Missing/contradictory legacy generation or mapping means **unmapped**, not zero
copies and not permission to delete or rebind. Do not infer ownership by parsing
all serialized sessions, searching emails or enumerating a shared directory.
Introducing the new admission rule requires a separately reviewed writer
cutover/legacy reauthentication plan; this documentation changes no current
sessions and authorizes no mass logout/backfill. Missing evidence is not a new
request to reapprove settled Q11/Q14/Q40 constraints.

New schema must preserve account tombstones, evidence/consent/recovery and
pair-history records. Review every FK/trigger effect; deleting a session-copy
row must not cascade into an account or its protective history. No parent
account DELETE or historical receipt rewrite is part of this interface.

## 4. Safe write, cleanup and retry protocol

### Writers, regeneration and the commit fence

Every supported writer must cooperate: bootstrap refresh/CSRF, authenticated
login binding, transition resume, regeneration, explicit close, automatic
shutdown and any privileged assertion issuer. Persistence on an apparently
read-only request or PHP lazy-write/timestamp refresh is included. A helper
called only on login cannot establish complete coverage.

1. Reserve a bounded payload-write intent and exact immutable target copy in a
   committed mapping transaction **before** any storage write. Authenticate the
   account/generation/epoch and use an allowlist of necessary payload fields.
   New supported authenticated payloads omit the persisted email and other
   profile/preferences/message data. No arbitrary session-array export.
   Treat externally auto-started/already-active sessions explicitly: current
   `start_secure_session()` returns before its bootstrap checks in that case.
   The new guard cannot assume that seeing an active PHP session means its
   handler, cookie configuration or binding has been validated.
2. Publication uses one documented lock order: current account, binding/epoch,
   operation/copy rows, then the supported adapter's bounded object fence.
   Recheck current authority and deadline immediately before activation.
   Use an immutable object version and compare-and-set the expected binding
   revision. Competing writes may retry after revalidation or fail safely;
   never blindly merge stale identity/role/MFA fields.
3. For a controlled local adapter, all object creation/publication and purge
   participate in that same fence; no legacy file lock is held while waiting
   for the database. Existing native file-session locking must not be combined
   with account-first cleanup in the opposite order. A timeout/unknown writer
   is unresolved, not evidence that its write stopped.
4. A store write and database commit are not atomic. A crash after storage write
   leaves a previously manifested, unpublished copy to reconcile. Only a
   current committed mapping can expose it. Failed admission/publication sends
   no authenticated success/renewal cookie and cannot recreate a retired entry.
   Never roll back revocation to make a store error disappear.
5. Regeneration reserves old/new refs and target generations before retiring
   the old binding; account/version checks and one-time handoff prevent replay.
   Track both old and provisional new copies after a crash. A verified
   onboarding response can advance only its own permitted binding; logout,
   account expiry or a newer security revocation wins over a late response.

Remote adapters need equivalent conditional-write/fencing/quiescence evidence
before support; do not hold an unbounded network operation under an account
lock or claim that a SQL rollback undid external I/O. For private reads/actions,
define the authorization linearization point under the same current-account
ordering. Work committed before revocation cannot be recalled, but later commits
and session writes must not use its earlier authorization snapshot.

### Bounded erasure state machine

`pending -> claimed -> fenced -> deleting -> verified -> complete`, with
`retry`/`blocked` outcomes and exact per-copy progress. Implement bounded batches,
bounded payload/manifest sizes and technical lease/backoff limits; numerical
worker tuning is engineering configuration, not a new retention deadline.

1. The revoking transaction records denial and a stable session-copy obligation
   with original event/deadline. Q11 expiry must use its original receipt and
   actual attempt generation; never create a new expiry from job discovery.
   Per-session logout fences only that session; account deletion/expiry closes
   the affected generation. Logout-all targets already-issued authorization
   epochs, preserving legitimately admitted later sessions where allowed.
2. Claim atomically using an unpredictable hashed claim secret and expected
   job/version. Recheck exact account generation, source event, current
   eligibility, session lineage and copy generation before every destructive
   step. Stale Q11 jobs cannot erase completed onboarding; newer credentials
   or another account sharing an email are not targets. A completed binding
   transition is not automatically subject to its old pending-stage cleanup.
3. Persist the exact manifest and recovery intent before erasing locators or
   payloads. Seal the retired binding's write epoch; include pending/provisional
   writes and regeneration aliases. Verify writer quiescence or a provider
   fence that actually rejects every old writer. Lease expiry alone is not
   proof that a paused process cannot later create another copy.
4. Delete only exact manifested versions through the approved adapter; persist
   intent before external deletion and verify absence before acknowledging it.
   If an identifier now names a different generation, preserve it and record
   a conflict. A missing object can be reconciled against that exact manifest;
   a missing store/root/instance is unavailable, not successful erasure.
5. Recheck progress/claim ownership after I/O. A crash after unlink but before
   acknowledgment retries through absence reconciliation, never deleting a
   replacement. Duplicate workers find one stable result. Partial instance or
   storage failures keep the obligation open with the original deadline.
6. Completion requires all scoped copies/aliases/intents verified absent,
   old writers fenced, and declared store/instance coverage complete. Record
   a separate component receipt such as `session_copies_v1`; do not relabel
   `erase_attempt_v1`, Q22 SQL receipts or unknown legacy copies as complete.
   Live-scope verification and independent recovery publication are distinct
   statuses; neither silently certifies the other.

Only the configured adapter may resolve a locator. Native-files support needs
observed handler/serializer/root layout and cooperative writer fencing. Reject
client paths, traversal, absolute/UNC/drive overrides, nulls, symlinks/reparse
points, hardlinks, changed identities and namespace/root mismatch. Avoid
stat-then-unlink races: require a private immutable object namespace with no
untrusted replacement and a verified conditional-delete/lock primitive; if the
platform cannot provide it, the adapter is unsupported and fails closed.
No recursive delete, glob of native session filenames, broad GC invocation,
directory ownership guess or serialization scan may substitute for mapping.
Planned DB/Redis/custom/object handlers need their own guarded adapters; Q40's
DB-store proposal and a server path do not establish current support.

A valid hold covers separately authorized, minimized evidence, not the ordinary
session blob or bearer/CSRF/MFA secrets. Preserve the actual evidence item and
its clocks independently. If a direct session-store evidence reference or
unknown exclusion is discovered, block the disputed scope for qualified review;
do not copy the whole session to a case or claim it was erased. Access remains
revoked, original obligations remain and overdue work must be visible.

Diagnostics contain fixed category/error codes, protected operation/copy refs,
counts, original deadline and completion watermark. No bearer/CSRF/MFA value,
email, raw path/session filename, IP or serialized data belongs in logs, alerts,
receipts or ordinary exports. Operations executes, engineering repairs and
privacy samples; actual people/backup/monitored alerts are UNASSIGNED. Under
Q24 alert on a missed known deadline and escalate after 24h unresolved. Unknown
deadlines/mappings are separate review obligations, not evidence of success.

## 5. Recovery, coverage and retirement

The independent recovery interface must preserve protected generation/ref,
revocation action/version, effective time, original purge deadline, verified
purge and computed retain-until bound. It must retain monotonic account-version
floors, closed generations, per-session/lineage revocations and relevant current
consent/safety restrictions, with no raw session ID or deleted payload.

Before **any** restored session is read/exposed or committed, restore account
and binding/index state, replay the independently current protections, apply
original expiry and establish writer/store coverage. Restored files alone,
restored old DB/index versions and a stale local "complete" flag cannot lower
the revocation floor. Unknown journal continuity, keys, namespace, copies or
instance coverage keep reads/writes fail-closed. No fallback to native handler
data on replay failure. Account/session recovery cannot clear pair budgets,
blocks/reviewer clearance, bans, withdrawn consent or reinstate child attempts.

Retain minimal protection until the latest **actual** recoverable-copy expiry
+7d, and longer for a still-live restriction. Conditional examples only:
Q11's 1d live allowance + proposed 35d copies +7d margin = **43d from expiry**;
Q14's 7+35+7 = **49d from deletion**. A later actual purge/surviving copy requires
a later computed bound; copying/restore never renews the source's lifetime.
Neither is a hard-coded session TTL, an adopted backup setting or permission to
forget untracked copies. General session/mapping retirement remains the policy
gap below. Protect locator/HMAC keys until all required reconciliation/recovery
is possible; destroying a key or losing an index is not proof of payload purge.

Independent durability/no loss of acknowledged protection, real provider
behavior, replay and a witnessed isolated restore remain **OPEN / NOT RUN**.
Local Q11/Q22 obligations and a future fixture provider do not satisfy them.
Supported production topology requires evidence for every current/retired
instance, staging/slot, volume and recoverable export; absence of a central
index or a stopped instance is not evidence that no session copies survive.

## 6. Decisions, implementation gaps and evidence gates

| Class / ID | Exact unresolved requirement | Effect / accountable role |
|---|---|---|
| Policy P01 — Q40 | Select production handler/topology and idle/absolute/privileged numerical settings; DB/one-instance/30m–12h/15m–4h/Lax remain proposals. | Needed before corresponding operational configuration/timeout changes. Keep current settings recorded; not required to build a guarded new-attempt mapping prototype. Product owner after security/operations review. |
| Policy P02 — Q40/Q23 | Select physical retention for ordinary logout, idle expiry, login/regeneration and nondeletion reset/ban/privilege retirement; identify anonymous-session retention and any standalone mapping/receipt lifetime outside necessary recovery. | No approved generic duration was found. Do not adopt 24h/7d/30d/1,800s by analogy. Q11/Q14 deadlines for their own copies already apply. Product/privacy review. |
| Engineering E01 | New-attempt binding, minimal manifests, guarded synthetic save handler, late-write fence, handoff and retirement lineage implemented; physical consumer, operational handler and legacy mapping remain absent/gated. | [Current evidence](session-binding-write-fence-evidence-2026-10-18.md); next Q11-only synthetic physical consumer needs existing approval, not Q11/Q14 reapproval. Engineering/security. |
| Engineering E02 | Current bootstrap/polling refresh, absent absolute/role-specific clocks, absent logout-all/reset, role/MFA integration and suspension without durable version revocation. | Separate bounded alignment using existing approved constraints; proposed numerical choices stay pending. Do not declare legacy moderation a reviewed ban/appeal system. |
| Evidence V01 — VERIFY | Actual PHP handler/INI/serializer/GC, private roots/permissions and conditional-delete capabilities, instance/slot/mount coverage, clock/ingress behavior and provider backups/exports. | Authorized sanitized configuration capture later; never inspect real payloads to fill this table. Operations/security. |
| Evidence V02 — VERIFY | Genuine generation/mapping provenance and all writer versions/cutover; disposition of existing unmapped sessions. | No backfill or claim of complete coverage from absence. Prepare a reviewed cutover/reauthentication plan separately; no live session transition authorized here. Engineering/operations. |
| Evidence V03 — VERIFY / REVIEW GATE | Independent current recovery publication/continuity, copy bounds, access/startup gates and witnessed restore. | Existing LC/QS/QE/QM external gates stay open; no fixture substitutes. Engineering/operations/privacy/safety witnesses. |
| Named review — REVIEW GATE | Real privacy/security reviewers, operator/backup/alert coverage and qualified purpose/secret/access review; operational MFA/privilege issuer if supported. | Remain UNASSIGNED/unverified; Takudzwa is product reviewer, not invented staff, independent reviewer or DPO. No public deadline/capability claim. |

No conflict with an existing explicit approval was found. P01/P02 are the
focused missing policy choices; V01–V03 and named sign-offs are evidence/review
requirements, not another 48-question approval sequence. This contract records
engineering requirements and does not change any decision's approval status.

## 7. Planned acceptance matrix

Use synthetic principals A/B, real new-registration generations G1/G2 and
internal session refs S1/S2; these symbols are not cookies or real identifiers.
Never seed proof by client roles/timestamps. Future execution requires guarded
disposable DBs, fake mail and isolated fixture storage, with fixtures separated
from restricted runtime/cleanup identities. Use original server-owned Q11/Q14
events. Where a general policy is unresolved, test rejection of the unsupported
operation rather than invent a production retention value.

Statuses below reflect the [new synthetic execution](session-binding-write-fence-evidence-2026-10-18.md),
with each unexecuted variant still open: **12 PASS / 18 PARTIAL / 10 NOT RUN**.
Existing browser/QS/QE/QM results are not relabelled as session erasure. For concurrency,
record distinct HTTP/CLI PIDs, handler instances and SQL connections plus actual
engine/provider overlap; two tabs or simulated exceptions alone do not prove it.

| ID | Concrete fixture/action | Required observation | Status |
|---|---|---|---|
| SC01 | Anonymous registration/entered-code page versus authenticated pending A | Anonymous CSRF session never acquires A's identity by email; legitimate entered-code flow works without manufactured login. | PASS |
| SC02 | Login to new A/G1, then protected page/API and CSRF write | Current authoritative generation/version mapped before publication; payload excludes email/profile data; exact copies indexed. | PASS |
| SC03 | Missing generation, missing auth version and contradictory original creation | Supported authenticated binding fails closed; no zero-version assumption, backfill or legacy purge. | PASS |
| SC04 | Reuse numeric account ID/email with new G2 | G1 session cannot authenticate G2 or cause its deletion, even with matching numeric versions. | PARTIAL |
| SC05 | Two A sessions; authoritative version/account revocation; all protected route groups | Both lose later access/actions; no stale snapshot or helper-only bypass. | PASS |
| SC06 | Pending-email creation +72h at −1µs/exact/+1µs; poll/resend | Deny at expiry; original event/deadline remains; no session-write renewal. | PASS |
| SC07 | First approval +7d at −1µs/exact/+1µs; repeated reads/verification | Original onboarding clock; stale versions cannot continue. | PASS |
| SC08 | Complete onboarding before expiry, then run selected stale pending job | New completed binding/copies survive; stale eligibility rejected. | PASS |
| SC09 | Same-session successful verification/onboarding handoff, plus unrelated old sessions | Only legitimate exact old-to-new handoff succeeds; others cannot guess/promote versions. | PASS |
| SC10 | Current-session logout while another A session exists; separate logout-all event | Logout fences only its binding; logout-all fences targeted old epochs, with no borrowed physical TTL. | PARTIAL |
| SC11 | Ban/suspend A, lift reviewed restriction, replay old cookies/write | Old bindings remain revoked; lifting does not resurrect them or bypass other restrictions. | PARTIAL |
| SC12 | Revoke reviewer role/assignment/MFA while review request waits | Current capability check denies; cached booleans/proof cannot restore privilege. | PARTIAL |
| SC13 | Future Q47 reset request versus successful reset with two A sessions | Request changes no auth; success revokes all relevant sessions and requires ordinary login; Q47 integration dependency. | NOT RUN |
| SC14 | Login A after prior different principal/privileged session; regenerate | Old identity/MFA/capability/CSRF material cannot leak into new binding; old alias denied. | PASS |
| SC15 | Polling/read versus intentional activity; unsupported timeout configuration | Polling cannot renew approved idle semantics; no proposed numerical lifetime silently adopted. | PARTIAL |
| SC16 | Expired A/G1 session-copy obligation at expiry +24h boundaries | Eligible owned copies gone by original deadline; late retries alert without extension. | NOT RUN |
| SC17 | Verified Q14 deletion with copies on two declared stores, seven-day boundaries | Access denied immediately; ordinary copies purged by original +7d; other categories unchanged. | NOT RUN |
| SC18 | Missing/contradictory event timestamp and unresolved logout retention | Known denial remains; unresolved deadline/cleanup obligation explicit, no invented time or false completion. | PARTIAL |
| SC19 | Scoped held evidence plus A session containing obsolete sensitive fields | Authorized evidence survives independently; no whole-session hold or raw secret copied to case. | NOT RUN |
| SC20 | Missing mapping/root/retired instance, then supposedly empty result | Unknown coverage is blocked, not zero copies or successful erasure. | PARTIAL |
| SC21 | Account expiry/logout versus late close, timestamp/lazy-write and implicit shutdown | Revoked writer cannot publish, recreate or renew a stored session/cookie. | PASS |
| SC22 | Old logout-all/expiry job versus legitimate fresh S2/G2 login | Exact session/epoch/generation scope preserves authorized later binding and unrelated B. | PARTIAL |
| SC23 | Regeneration overlaps cleanup; crash between old/new reservations | Both provisional copies remain tracked; no old alias resurrection or deletion of valid new binding. | PARTIAL |
| SC24 | Actual verification/onboarding response versus concurrent logout and newer revocation | Only still-current handoff can publish; late response cannot recreate session or invent next version. | PASS |
| SC25 | Two cleanup workers claim/process same event with observed overlap | One stable component receipt; no duplicate effect or lost deadline. | NOT RUN |
| SC26 | Expired technical lease and stalled old writer/cleanup claimant | Old token/CAS rejected; lease expiry alone cannot assert writer quiescence. | NOT RUN |
| SC27 | Crash after committed write/purge intent before storage action | Resumption finds original manifest and deadline; no false completion or untracked new copy. | PARTIAL |
| SC28 | Crash after object deletion before SQL acknowledgment; client loses final reply | Verify exact absence on retry; stable status/receipt; no deletion of replacement object. | NOT RUN |
| SC29 | One instance succeeds, second returns partial failure/unavailable | Per-copy progress retained; original deadline/overdue alert; no global success. | PARTIAL |
| SC30 | Traversal/absolute/UNC/null locator, symlink/reparse/hardlink and root mismatch | Refuse unsafe target without touching B/outside storage; actual platform variants recorded separately. | PARTIAL |
| SC31 | Replace locator's object version/identity between selection and delete | Conditional operation preserves replacement; conflict remains explicit. | PARTIAL |
| SC32 | Unmanaged native handler/noncooperating writer or unsupported remote-store fence | Refuse cleanup/publication support; no inverse-lock deadlock, fallback or speculative GC. | PARTIAL |
| SC33 | Secret-bearing old payload plus diagnostics/receipt/export capture | Scoped payload removed; no SID/cookie/CSRF/email/MFA/body/raw locator in retained diagnostics or receipts. | PARTIAL |
| SC34 | Restore old session copies against current DB/revocation registry | Old authentication stays denied before payload exposure or session rewrite. | PASS |
| SC35 | Restore old DB/index and files; independent journal newer, missing or truncated | Replay latest monotonic protection first; unknown continuity keeps reads/writes/mail gated. | NOT RUN |
| SC36 | Restore old regeneration alias/privileged proof after valid new grant | No automatic lineage/capability restoration; current account/resource restrictions still dominate. | NOT RUN |
| SC37 | Later purge/export/copy expiry and active protection outlive example day 43/49 | Computed latest-copy +7d/longer restriction wins; no universal timer or read-based renewal. | NOT RUN |
| SC38 | Missing/rotated HMAC or locator key, ambiguous namespace | Resolve through protected versioned adapter or fail closed; no raw ID logging, blind deletion or lost-key erasure claim. | PARTIAL |
| SC39 | Disabled consumer, unsafe fixture destination, missing runtime/adapter grants | Refuse before unsafe connection/action; atomic rollback and code-free error; no schema/user-delete privileges added. | PARTIAL |
| SC40 | Two app instances, restart/GC and an unindexed older volume | Complete writer/copy coverage required; GC/restart alone cannot prove revocation or physical cleanup. | PARTIAL |

Historical results remain A **24/19/2**, LB **21/12/0**, LC **13/5/0**, QS
**24/0/0**, QE **27/1/0** and QM **32/0/0** (PASS/PARTIAL/wholly NOT RUN).
Their original run/source attribution and unexecuted variants remain intact,
including QE21's Windows symlink variant. No functional tests or new CI run
were performed for the original documentation checkpoint; the current SC execution is linked above.

## 8. First bounded implementation slice

**Continuation:** this foundation is implemented for the guarded synthetic
handler only; [evidence and next slice](session-binding-write-fence-evidence-2026-10-18.md)
record its limits. The original bounded requirements below remain the scope
boundary, not a claim of physical cleanup or operational readiness.

**New-attempt session binding and write-fence foundation**, limited to known
post-012 account generations and a guarded synthetic managed-handler adapter:

1. Add the minimal binding/copy/write-intent schema and default-deny adapter
   boundary; preserve all legacy rows and unknown mappings. No operational
   handler switch, legacy sweep or Q40 store/timeout/cookie adoption.
2. Integrate authenticated new-attempt login, current-generation/version reads,
   explicit/implicit writes and authorized transition/regeneration handoff.
   Minimize new payloads and reject late writes using current binding state.
3. Record per-session retirement and attach the exact Q11 session-copy
   obligation to its original expiry receipt/deadline, without relabelling QE's
   existing completion. A supported controlled fixture is not a real recovery
   provider; operational publication/cleanup stays gated.
4. Execute the applicable SC01–SC12/SC14/SC21–SC24/SC27/SC33/SC39 foundation
   variants, plus required migration rehearsals and affected Q11/Q22/auth
   regressions. Keep the physical consumer, unsupported-handler/restore/other
   policy-dependent variants NOT RUN until their own implementation/evidence.

This foundation used settled Q11/Q14/current-version protections;
P01/P02 need resolution before their corresponding broader operational scope.
A later bounded physical consumer depends on a proven manifest, writer cutover
and safe adapter; it is not implemented or enabled by this contract. The original documentation checkpoint changed no live configuration, account
permission, session payload, scheduler, database or mail. The continuation
executes only the separately authorized guarded synthetic implementation/tests;
no production access, scheduling, push, merge or deployment is authorized.

## 9. Historical documentation validation at 077b8c7

`git diff --check` and local source/reference checks pass: **102 local links,
four heading targets, 22 current `require_login()` callers, 30 referenced
function declarations**, SC01–SC40 uniqueness/status and retention-clock
consistency. Policy, historical evidence, application/test code, migrations and
runtime/CI configuration remain unchanged from `9053428`. These are static
documentation checks only. New functional acceptance and CI are **NOT RUN**;
no database/session-store connection or session payload inspection is needed
or performed for them.


Current continuation validation and exact local execution artifacts are recorded
in the [focused evidence](session-binding-write-fence-evidence-2026-10-18.md).
The preceding 102-link/heading/declaration check remains historical documentation
validation; it is not functional testing or a new CI run.
