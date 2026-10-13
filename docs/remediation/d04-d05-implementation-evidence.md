# D04/D05 Implementation Evidence

Updated: 2026-10-12, Africa/Harare.

Status: technical implementation evidence for pending D04/D05 proposals. This is not P0 clearance and not production approval.

## Runtime Versions

- PHP CLI: `PHP 8.5.11 (cli)` from `php -v`.
- MySQL client: `MySQL Community Server 9.6.0` client from `mysql --version`.
- MySQL server: **NOT RUN/BLOCKED**; local MySQL processes were present, but guarded disposable bootstrap to `bushisa_test_local` on port 3306 rejected `root@localhost` without password. No alternate authorized test DB credentials were available.
- Node/npm: Node `v20.19.6`, npm `10.8.2`.

## Migration Ordering

1. `001_operational_contract.sql` through `php bin/migrate.php` idempotent schema path.
2. `002_policy_safeguards.sql` through the same idempotent schema path.
3. `003_restore_replay_safeguards.sql` as additive restore replay run table.

## P0 Task Evidence Matrix

| Task | Evidence path | Current result |
|---|---|---|
| R01/R02 request consent and transitions | `api/message_requests.php`, `php/policy_safeguards.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. Multi-worker HTTP overlap still required. |
| R03 evidence-safe block/delete | `php/block_handler.php`, `api/safety.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. Legacy cascades still exist but normal close path avoids them. |
| R04 unsend | `api/messages.php` | Static PHP syntax PASS. Focused route/browser regression **NOT RUN/BLOCKED** by DB auth. |
| R05 verifier/outbox cleanup | `php/signup_verification.php`, `bin/cleanup_policy.php`, `tests/integration_p0_flows.php`, `tests/policy_safeguards_p0_flows.php` | Static PHP syntax PASS; `tests/unit_signup_verification.php` PASS. DB regressions **NOT RUN/BLOCKED**. Real sender/inbox pending. |
| R06 either-account cleanup | `php/anonymiser.php`, `php/policy_safeguards.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. |
| R07 confession erasure before unlink | `php/policy_safeguards.php`, `api/confessions.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. |
| R08 data inventory | `docs/remediation/policy-proposals-d04-d05.md` section 1 and 5 | Documented. Environment inventory still pending. |
| R09/R10 UTC deadlines and cleanup | `cleanup_outbox`, `purge_due_at` fields, `bin/cleanup_policy.php` | DB regression **NOT RUN/BLOCKED**. Full retention worker incomplete. |
| R11 case-scoped evidence access | `moderation_cases`, `moderation_case_assignments`, `moderation_evidence`, `evidence_access_audit`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED**. UI pending. |
| R12 protective restore replay | `protective_replay_events`, `restore_replay_runs`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED**. Azure isolated restore not run. |
| D12 production location disabled | `docs/remediation/geographic-matching.md`, `docs/remediation/decisions.md` | Pending D12 approval; production use disabled. |

## Commands Run This Turn

| Command | Result |
|---|---|
| `php -l` on changed PHP files | PASS |
| `php tests/unit_signup_verification.php` | PASS |
| `php tests/unit_disposable_database_guard.php` | PASS |
| `node --check tests/support/disposable-database.js` | PASS |
| `node --check tests/browser/p0-rendered-flows.spec.js` | PASS |
| `php bin/migrate.php` | NOT RUN/BLOCKED: database connection rejected for default local root credentials |
| `php tests/integration_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `php tests/integration_core_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `php tests/policy_safeguards_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `php tests/overlap_db_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `mysql --protocol=TCP --host=127.0.0.1 --port=3317 --user=root --execute="SELECT VERSION();"` | NOT RUN/BLOCKED: no MySQL listener on port 3317 |
| `php tests/bootstrap_database.php` with `APP_ENV=development`, `BUSHISA_TEST_DATABASE=true`, `DB_NAME=bushisa_test_local`, local root/no password | BLOCKED: MySQL rejected `root@localhost` with no password before any test fixture writes |
| `php tests/unit_mail_configuration.php` | BLOCKED locally: this PHP CLI has `pdo_mysql` and `PDO` but no `openssl` extension, so existing mail encryption tests cannot run in this runtime |
| `py -3 tests/smtp_transport_test.py` | FAIL/BLOCKED locally: SMTP fixture timed out waiting for its thread to finish; not caused by the DB guard change |

## 2026-10-12 Prompt 01 Harness Changes

- Added `tests/support/disposable_database.php` and `tests/support/disposable-database.js` as shared fail-closed guards for fixture writers and destructive browser resets.
- Guard requires non-production `APP_ENV`, `BUSHISA_TEST_DATABASE=true`, an explicit `DB_NAME`/`AZURE_MYSQL_DBNAME`, a narrowly named disposable database such as `bushisa_test_ci`, and a second opt-in before using a remote host.
- Added `tests/bootstrap_database.php` so schema import rewrites `database/bushisa.sql` to the selected disposable database and rejects unrelated `CREATE/USE/DROP DATABASE` targets.
- Moved guard checks before `config/db.php` in `tests/integration_p0_flows.php`, `tests/integration_core_p0_flows.php`, `tests/policy_safeguards_p0_flows.php`, `tests/overlap_db_p0_flows.php`, `tests/integration_smtp_outbox.php`, `tests/workers/db_race_worker.php` and `database/seed.php`.
- Updated `tests/browser/p0-rendered-flows.spec.js` to require the same opt-ins, drop only the selected disposable database, rewrite the bootstrap schema target, redact MySQL passwords from command diagnostics and avoid printing code-bearing mail bodies.
- Updated CI to use `bushisa_test_ci`, run the guarded bootstrap, include `tests/unit_disposable_database_guard.php`, and add `integration_core_p0_flows.php` plus `overlap_db_p0_flows.php` to the DB-backed regression job.

Prompt 01 status: safe runner/guard implementation is reviewable and unit-verified. Full DB-backed migration/integration execution remains **NOT RUN/BLOCKED** until an authorized disposable MySQL credential is supplied or CI executes the new guarded job.

## Prompt 02 startup guard - 2026-10-12

The browser harness startup guard was tightened so `playwright.config.js` no longer supplies unsafe fallback DB/debug/runtime-DDL settings before the reset guard runs. The config now requires explicit disposable DB settings, `APP_DEBUG=false`, `BUSHISA_ALLOW_RUNTIME_DDL=false`, `BUSHISA_TEST_DATABASE=true` and `MAIL_PROVIDER=fake` before launching the PHP test server. External test servers require an explicit opt-in and do not launch the default server. `tests/browser/prompt02-session-routes.spec.js` prepares a two-context session revocation route check for the next guarded browser run.

Local checks for this slice passed: changed PHP/JS syntax, `php tests/unit_disposable_database_guard.php`, `node tests/unit_playwright_config_guard.js`, `php tests/unit_signup_verification.php`, `php tests/unit_mail_configuration.php` and `py -3 tests/smtp_transport_test.py`.

[Prompt 02 evidence checkpoint](prompt02-route-session-evidence-2026-10-12.md) records the exact boundaries as of 12 October. At that point, rendered browser execution, two-session route revocation and genuine multi-worker HTTP overlap remained **NOT RUN/BLOCKED** until the authorized disposable DB environment and suitable multi-worker runtime were available.

## Prompt 02 route/session/overlap execution - 2026-10-13

The guarded Prompt 02 browser work advanced on branch `codex/prompt02-completion-20261013`. Local Windows execution against `127.0.0.1:3318/bushisa_test_prompt02_local` passed the rendered signup/onboarding/pending-gate/timestamp suite and the two-independent-session revocation route/API check. A Linux Playwright container then executed `tests/browser/prompt02-http-overlap.spec.js` with `PHP_CLI_SERVER_WORKERS=4`, PHP `8.3.6`, MySQL client `8.0.46`, fake mail and the host-owned disposable MySQL listener via `host.docker.internal:3318/bushisa_test_prompt02_docker_overlap`.

The reciprocal swipe overlap test passed with two distinct PHP worker PIDs at the guarded `swipe_after_transaction_begin` barrier and asserted exactly two swipe rows, one active match and zero approved message-request rows. This establishes the focused R01/F02 HTTP overlap case for reciprocal swipes; broader request/revoke/block/delete/message races, role-removal routes, full retention worker execution, production-version acceptance and R12 isolated restore remain open.

[Prompt 02 route/session evidence](prompt02-route-session-evidence-2026-10-12.md) contains the exact commands, runtimes, barrier output and R01-R12 matrix.

## Explicit Non-Claims

- No P0 clearance.
- No live deployment.
- No approved D04/D05 production policy.
- No verified real sender/inbox delivery.
- No Azure restore rehearsal.
- No claim that a Windows single-worker development server proves HTTP concurrency.


## Prompt 01 local DB execution - 2026-10-12

Selected signup, core, policy, SMTP-outbox and DB-overlap suites passed
on the disposable database. These results supersede the earlier local
execution blockers.

[Full execution checkpoint](prompt01-local-db-execution-2026-10-12.md).

Tested baseline: 6ed126d, with uncommitted changes now preserved on
codex/prompt01-db-validation-20261012.

Browser/HTTP/session, migration-role, production-version and CI evidence
remain incomplete. D04/D05 approval and P0/P1 gates remain open.
