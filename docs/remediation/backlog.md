# Bushisa P0-P2 Remediation Backlog

Updated: 2026-10-14, Africa/Harare.

| Finding | Current state | Evidence / files |
|---|---|---|
| B00/B01 | Current checkout matches audited commit `ecf6217ceda74cc257d75483f253862b932dfe9d`; findings treated as live unless repaired. | `git rev-parse HEAD`; this backlog. |
| F01 | Partial. Registration now creates pending accounts, validates approved NUST student email/student number, queues fake approval-code mail and routes to email approval plus DOB onboarding. Unit and DB integration tests pass locally. Real ACS sender remains pending. `mail_outbox.body` is fake-only plaintext capture; non-fake providers throw until an encrypted provider/outbox is implemented. | `php/auth.php`, `php/signup_verification.php`, `verify_email.php`, `onboarding.php`, `mail_outbox`, `tests/integration_p0_flows.php`. |
| F02 | Partial. Known duplicate named parameters in active match/chat/message/safety paths were repaired for native PDO. Local DB integration covers reciprocal candidate retrieval, mutual-match uniqueness, chat approval/message writes and block cleanup through shared helpers/SQL. Synthetic overlapping DB-process tests now cover duplicate match creation and duplicate same-direction swipe insertion. Prompt 02 adds reciprocal-match locking and a real HTTP overlap spec; Linux Docker execution with `PHP_CLI_SERVER_WORKERS=4` proved overlapping reciprocal swipes create one active match without duplicate approval rows. Broader message/block/delete overlap remains pending. | `api/swipe.php`, `php/http_test_barrier.php`, `api/matches.php`, `api/messages.php`, `api/message_requests.php`, `api/safety.php`, `chat.php`, `php/block_handler.php`, `tests/integration_core_p0_flows.php`, `tests/overlap_db_p0_flows.php`, `tests/browser/prompt02-http-overlap.spec.js`. |
| F03 | Partial. Operational migration and runner added. Direct SQL migration passes once against the current base schema; `bin/migrate.php` is idempotent for partially upgraded schemas. Runtime DDL can be disabled in production with `APP_ENV=production`; full legacy upgrade rehearsals pending. | `database/migrations/001_operational_contract.sql`, `bin/migrate.php`, `php/operational.php`. |
| F04 | Partial. `require_login()` reloads current account, role and session version, and gates pending email/onboarding states. Rendered browser tests now cover pending-email and pending-onboarding restrictions. Prompt 02 browser evidence now proves two independent sessions lose page/API access after account revocation; broader role/removal/deletion matrices remain pending. | `php/session_manager.php`, `php/auth.php`, `tests/browser/p0-rendered-flows.spec.js`, `tests/browser/prompt02-session-routes.spec.js`. |
| F05 | Partial. Confession reports can use nullable `reported_user_id` after migration and resolve confession owners server-side; flagged referenced confession bodies are hidden; block writes/match cleanup are transactional. Atomic safety-service policy still pending D04/D05. | `api/confessions.php`, `api/safety.php`, migration. |
| F06 | Partial. Native-compatible anonymiser metadata probes and session-version/delete status fields added. Local DB integration verifies anonymisation completes and marks the user deleted/inactive. Full lifecycle inventory and backup tombstone replay remain pending D05. | `php/anonymiser.php`, `tests/integration_core_p0_flows.php`. |
| F07 | Partial. Production DB config fails safely for root/empty password and real CI lint/unit workflow replaces placeholder. Web-root/server/Azure artifacts remain pending. | `config/db.php`, `.github/workflows/ci.yml`. |
| F08 | Partial. Discover API no longer overrides cursor with default offset. Stable keyset/snapshot pagination and representative 5,000-account tests pending. | `api/discover.php`. |
| U01 | Partial. API create responses now return DB-stored UTC confession time; rendered page emits UTC ISO in `datetime`. Synthetic browser test verifies an older persisted confession timestamp does not render as current after reload. Fake-clock boundary tests and H09 human comparison remain pending. | `api/confessions.php`, `confessions.php`, `php/function.php`, `tests/browser/p0-rendered-flows.spec.js`. |
| U02 | Pending. Geographic matching requires D12 before implementation can be certified. | Pending. |

## 2026-10-12 Continuation Notes

| Finding | Current state | Evidence / files |
|---|---|---|
| B01 | Improved. Fixture-writing PHP suites, the DB race worker, synthetic seed script and browser reset now require a shared disposable-database guard before connecting or dropping/importing schema. Local unit and syntax checks pass; full DB-backed execution is still blocked by missing authorized local MySQL credentials. | `tests/support/disposable_database.php`, `tests/support/disposable-database.js`, `tests/bootstrap_database.php`, `tests/unit_disposable_database_guard.php`, `tests/browser/p0-rendered-flows.spec.js`, `docs/remediation/d04-d05-implementation-evidence.md`. |
| F07 | Improved. CI now uses `bushisa_test_ci`, bootstraps through the guarded runner instead of an inline unguarded PDO import, and includes the previously omitted core and DB-overlap regressions. Browser CI and production-version execution remain pending. | `.github/workflows/ci.yml`. |
| Prompt 02 | Started. Browser harness startup now fails closed unless explicit disposable DB, fake mail, non-debug and runtime-DDL-disabled settings are present before server launch. A two-context session revocation browser test is prepared but NOT RUN here. Actual rendered browser, broader route matrix and multi-worker HTTP-overlap evidence remains NOT RUN/BLOCKED. | `playwright.config.js`, `tests/unit_playwright_config_guard.js`, `tests/browser/prompt02-session-routes.spec.js`, `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`. |

## 2026-10-13 Prompt 02 execution

| Finding | Current state | Evidence / files |
|---|---|---|
| Prompt 02 | Improved. Local disposable MySQL `9.6.0` on `127.0.0.1:3318` executed the rendered browser suite and two-session revocation route check successfully. Guard/unit/PHP DB/mail regressions passed. Genuine HTTP reciprocal-swipe overlap passed in a Linux Playwright container using `PHP_CLI_SERVER_WORKERS=4` and the same host-owned disposable MySQL listener through `host.docker.internal`. Native Windows remains unsuitable for overlap; CI confirmation is still useful but no longer the first execution blocker. | `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`, `.github/workflows/ci.yml`, `playwright.config.js`, `tests/browser/p0-rendered-flows.spec.js`, `tests/browser/prompt02-session-routes.spec.js`, `tests/browser/prompt02-http-overlap.spec.js`, `api/swipe.php`, `php/http_test_barrier.php`. |
| Prompt 02 route/session/race completion | Improved. Actual browser/API route tests now cover request/approve/send/read/unsend/report/revoke, forged ownership, missing CSRF, block cleanup, account deletion stale-session denial and moderator role removal. Linux Docker multi-worker HTTP tests now cover reciprocal swipe overlap, approve-vs-revoke and send-vs-block with two distinct PHP worker PIDs at guarded barriers. Candidate CI remains pending until branch push/PR run. | `docs/remediation/prompt02-route-session-evidence-2026-10-12.md`, `tests/browser/prompt02-chat-routes.spec.js`, `tests/browser/prompt02-chat-overlap.spec.js`, `tests/browser/prompt02-http-overlap.spec.js`, `api/message_requests.php`, `api/messages.php`, `api/safety.php`, `php/policy_safeguards.php`, `php/http_test_barrier.php`. |


## Prompt 01 local DB execution - 2026-10-12

Selected signup, core, policy, SMTP-outbox and DB-overlap suites passed
on the disposable database. These results supersede the earlier local
execution blockers.

[Full execution checkpoint](prompt01-local-db-execution-2026-10-12.md).

Tested baseline: 6ed126d, with uncommitted changes now preserved on
codex/prompt01-db-validation-20261012.

Browser/HTTP/session, migration-role, production-version and CI evidence
remain incomplete. D04/D05 approval and P0/P1 gates remain open.
