# Prompt 02 route/session evidence checkpoint

Evidence through **12 October 2026, 13:00 Harare (UTC+02:00)**.

Status: Prompt 02 startup safety repair is implemented and locally unit-verified. Actual rendered browser execution, two-session route revocation evidence and genuine multi-worker HTTP overlap are still **BLOCKED/NOT RUN** in this shell because the authorized disposable DB environment is not loaded and no multi-worker PHP runtime is available here.

This is not P0 clearance, production approval, CI evidence or live deployment evidence.

## Starting artifact

| Item | Value |
|---|---|
| Working branch | `codex/prompt02-route-session-evidence-20261012` |
| Current branch base | `0bf311f` (`comphrehensive presentation`) |
| Imported Prompt 01 checkpoint | `c27ea640c382c32792989611cfe682cf2a414520` from `codex/prompt01-db-validation-20261012`, excluding the local untracked continuation document |
| Parent/test-time baseline recorded by handoff | `6ed126d31ce6d2a5e046cf0ace98744345a8e934` |
| Prompt 02 source state | Uncommitted branch diff; no final implementation commit yet |

## Implemented in this slice

The handoff's first repair was the guarded HTTP/browser startup. At the inherited checkpoint, `playwright.config.js` still defaulted to `APP_DEBUG=true`, `BUSHISA_ALLOW_RUNTIME_DDL=true`, `DB_NAME=bushisa_db`, `DB_USER=root` and `DB_PORT=3317`, then wrote those defaults into `process.env` before the browser test's reset guard could run.

Implemented changes:

- `playwright.config.js` now imports `tests/support/disposable-database.js` and validates the browser harness environment before defining or launching a web server.
- The browser harness requires:
  - `APP_ENV=development`
  - `APP_DEBUG=false`
  - `BUSHISA_ALLOW_RUNTIME_DDL=false`
  - `BUSHISA_TEST_DATABASE=true`
  - explicit `DB_HOST`, `DB_PORT`, `DB_NAME` and `DB_USER`
  - a disposable database name such as `bushisa_test_*`, `bushisa_ci_*`, `bushisa_tmp_*`, `bushisa_synthetic_*` or `bushisa_p0_*`
  - `MAIL_PROVIDER=fake`
- The default Playwright server must use a loopback HTTP URL. An externally managed test server is allowed only with `BUSHISA_EXTERNAL_TEST_SERVER=true` and `BUSHISA_ALLOW_EXTERNAL_TEST_SERVER=true`; in that mode the config does not launch the default `php -S` server.
- The validated DB target is passed consistently to the PHP web server environment and to browser helper subprocesses.
- The shared PHP and JS disposable DB guards were tightened to require explicit environment values and false debug/runtime-DDL settings, not implicit development/root/default fallbacks.
- Added `tests/unit_playwright_config_guard.js` to prove the config rejects missing opt-in, production mode, missing/unsafe DB names, debug mode, runtime DDL, real mail provider and unapproved external servers before startup.
- Added `tests/browser/prompt02-session-routes.spec.js` as a focused two-browser-context route/session test. It creates a synthetic active student through actual registration, email approval, login and onboarding pages, logs in twice, revokes the account in the disposable DB, and asserts page/API access is lost with no forbidden swipe writes.
- CI now installs Node dependencies and runs syntax checks for the Playwright config, test support JS, browser test and the new Playwright config guard. It also runs the new Node guard in the unit step.

## Local checks

| Command | Result |
|---|---|
| `php -l tests\support\disposable_database.php` | PASS |
| `php -l tests\unit_disposable_database_guard.php` | PASS |
| `node --check playwright.config.js` | PASS |
| `node --check tests\support\disposable-database.js` | PASS |
| `node --check tests\unit_playwright_config_guard.js` | PASS |
| `node --check tests\browser\p0-rendered-flows.spec.js` | PASS |
| `node --check tests\browser\prompt02-session-routes.spec.js` | PASS |
| `php tests\unit_disposable_database_guard.php` | PASS |
| `node tests\unit_playwright_config_guard.js` | PASS |
| `php tests\unit_signup_verification.php` | PASS |
| `php tests\unit_mail_configuration.php` | PASS |
| `py -3 tests\smtp_transport_test.py` | PASS |
| `php -m` extension check | PASS for `openssl`, `PDO`, `pdo_mysql` present |

The earlier combined local check command timed out without useful output, then the checks above passed when split into smaller commands. Treat the split command results as the usable evidence.

## Blocked / not run

| Area | Status | Boundary |
|---|---|---|
| Rendered browser reset/flows | BLOCKED/NOT RUN | This shell has no loaded disposable DB environment; `APP_ENV`, `APP_DEBUG`, `BUSHISA_ALLOW_RUNTIME_DDL`, `BUSHISA_TEST_DATABASE`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `MAIL_PROVIDER` and `DB_PASSWORD` were all unset. The guard should reject startup in this state. |
| Two independent browser sessions | NOT RUN | Requires the guarded disposable DB environment and focused browser/session tests beyond the existing rendered-flow spec. |
| Real route/session revocation matrix | NOT RUN | Pending tests for suspended/deleted/role-revoked/current-authorization states using actual pages/APIs and two sessions. |
| Genuine multi-worker HTTP overlap | BLOCKED | Native Windows `php -S` remains a single-worker development server. A supported isolated multi-worker runtime such as Nginx/PHP-FPM or equivalent container/Linux setup is required. |
| CI | NOT RUN | The handoff reports zero candidate workflow runs; no new CI run evidence was available in this local session. |
| Production-version matrix | NOT RUN | Azure MySQL server version and approved D08 matrix remain open. |
| R12 isolated restore/replay | NOT RUN | No Azure isolated restore or independent replay rehearsal was performed. |

## R01-R12 impact

This slice improves the safety of the Prompt 02 evidence harness but does not create new route-level R01-R12 evidence.

| Rule area | Current Prompt 02 result |
|---|---|
| R01-R02 matching consent/request transitions | Existing Prompt 01 DB/service suites remain inherited evidence. Actual route/session coverage is NOT RUN. |
| R03 block/delete evidence survival | Existing DB/service suites remain inherited evidence. Actual route overlap and rollback cases are NOT RUN. |
| R04 unsend boundaries | NOT RUN for actual route evidence. |
| R05 verifier/outbox cleanup | Unit/mail checks PASS locally; rendered/signup route code paths require guarded browser execution. |
| R06-R07 account deletion/confession erasure | Existing DB/service suites remain inherited evidence; actual route/session evidence is NOT RUN. |
| R08-R10 lifecycle/retention deadlines | Not advanced in this slice. |
| R11 case-scoped evidence | Existing DB/service suite remains inherited evidence; actual route/role access matrix is NOT RUN. |
| R12 protective restore replay | NOT RUN; explicit external restore/replay gap remains. |

## Next exact work

Continue Prompt 02 with the now-guarded browser harness:

1. Load the authorized disposable DB environment privately:
   - `APP_ENV=development`
   - `APP_DEBUG=false`
   - `BUSHISA_ALLOW_RUNTIME_DDL=false`
   - `BUSHISA_TEST_DATABASE=true`
   - `MAIL_PROVIDER=fake`
   - explicit `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and private `DB_PASSWORD`
2. Re-run `tests/bootstrap_database.php`, `php bin/migrate.php` twice, and the existing DB suites on the same selected disposable target.
3. Run the guarded Playwright rendered-flow suite, including `tests/browser/p0-rendered-flows.spec.js` and `tests/browser/prompt02-session-routes.spec.js`.
4. Extend focused Prompt 02 browser/API tests for the remaining current-authorization states and forged ownership/CSRF cases.
5. Prepare or run an isolated multi-worker HTTP server for overlap proof. If unavailable, keep overlap BLOCKED with the exact runtime prerequisite.
6. Update this evidence file with executed route/session/overlap cases, sanitized evidence paths, and R01-R12 mapping.
