# Prompt 02 route/session evidence checkpoint

Evidence through **14 October 2026, 00:40 Harare (UTC+02:00)**.

Status: Prompt 02 startup safety repair is implemented. On 13 October 2026 the rendered browser suite, two-session revocation browser route check and Linux multi-worker HTTP reciprocal-swipe overlap check passed against guarded disposable MySQL targets. Native Windows overlap remains blocked by PHP worker limits, but the overlap proof was executed in a Linux Playwright container using `PHP_CLI_SERVER_WORKERS=4` and the local disposable MySQL listener.

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

## 2026-10-13 execution entry

This entry continues the same Prompt 02 checkpoint after reconciling the committed Prompt 01 artifact `c27ea640c382c32792989611cfe682cf2a414520` and the main README-only update on branch `codex/prompt02-completion-20261013`.

### Runtime and guarded target

| Item | Value |
|---|---|
| Local branch | `codex/prompt02-completion-20261013` |
| Starting HEAD for this slice | `f060901` (`Merge Prompt 01 validation artifact`) |
| PHP CLI | `PHP 8.5.11 (cli)` |
| Node/npm | Node `v20.19.6`, npm `10.8.2` |
| MySQL client/server | Local disposable MySQL `9.6.0` on `127.0.0.1:3318` |
| Disposable DB | `bushisa_test_prompt02_local` |
| DB principal | `root@localhost` on a freshly initialized local insecure disposable datadir under `.tmp/mysql-prompt02-local-20261013`; no production or Azure target used |
| Browser server | Windows `php -S 127.0.0.1:8097 -t .`, single worker, for serial rendered/session checks only |
| HTTP-overlap runtime | Docker Linux `mcr.microsoft.com/playwright:v1.64.0-noble`; PHP `8.3.6`; MySQL client `8.0.46`; `PHP_CLI_SERVER_WORKERS=4`; `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8097` |
| HTTP-overlap DB | `host.docker.internal:3318/bushisa_test_prompt02_docker_overlap`, `root@localhost`, guarded with `BUSHISA_ALLOW_REMOTE_TEST_DATABASE=true` because the Linux container reached the host-owned disposable MySQL listener |
| Mail | `MAIL_PROVIDER=fake` |
| Guard settings | `APP_ENV=development`, `APP_DEBUG=false`, `BUSHISA_ALLOW_RUNTIME_DDL=false`, `BUSHISA_TEST_DATABASE=true` |

### Implemented repairs after the 12 October entry

- Changed Playwright readiness from `/login.php` to `/css/style.css` so server startup does not query an empty DB before the guarded test reset/migration runs.
- Updated rendered browser assertions to match current registration copy.
- Kept confession timestamp coverage despite the disabled UI composer by posting through the implemented `/api/confessions.php` JSON route, then reloading and checking rendered UTC `<time>` output.
- Added `php/http_test_barrier.php`, enabled only for guarded development overlap runs with `BUSHISA_TEST_HTTP_OVERLAP=true`.
- Added reciprocal-match user-level locking in `api/swipe.php` with `GET_LOCK`/`RELEASE_LOCK` around unordered match pairs to serialize overlapping reciprocal likes.
- Added `tests/browser/prompt02-http-overlap.spec.js`, which uses two independent browser contexts, two logged-in sessions, real CSRF tokens and overlapping HTTP `POST /api/swipe.php` calls.
- Added a Linux CI job, `browser-route-session-overlap`, with MySQL 8.0, Chromium, guarded Playwright execution and `PHP_CLI_SERVER_WORKERS=4`.
- Tightened `playwright.config.js` so `BUSHISA_TEST_HTTP_OVERLAP=true` fails before server launch on native Windows unless an explicit external multi-worker test server is selected.

### Commands and results

| Command | Result | Notes |
|---|---|---|
| `php -l php/http_test_barrier.php; php -l api/swipe.php` | PASS | Syntax only for changed PHP. |
| `node --check playwright.config.js; node --check tests/unit_playwright_config_guard.js; node --check tests/browser/prompt02-http-overlap.spec.js; node --check tests/browser/p0-rendered-flows.spec.js; node --check tests/browser/prompt02-session-routes.spec.js` | PASS | Syntax only for changed JS/browser specs. |
| `node tests/unit_playwright_config_guard.js` | PASS | Proves fail-closed config for missing opt-in, production, unsafe DB, debug/runtime-DDL, real mail, unapproved external server and native Windows overlap. |
| `php tests/bootstrap_database.php; php bin/migrate.php; php bin/migrate.php; php tests/integration_core_p0_flows.php; php tests/overlap_db_p0_flows.php; php tests/policy_safeguards_p0_flows.php` | PASS | Same guarded disposable DB target. |
| `php tests/bootstrap_database.php; php bin/migrate.php; php tests/unit_signup_verification.php; php tests/unit_mail_configuration.php; php tests/unit_disposable_database_guard.php; php tests/integration_p0_flows.php; php tests/integration_smtp_outbox.php` | PASS | The same PowerShell line attempted `python tests/smtp_transport_test.py`, but Windows had no file association for `python`; the PHP commands before and after it passed. |
| `py -3 tests/smtp_transport_test.py` | PASS | Python SMTP fixture rerun with the available launcher. |
| `npx playwright test tests/browser/p0-rendered-flows.spec.js tests/browser/prompt02-session-routes.spec.js` | PASS | 4 tests passed: registration/code/DOB/logout rendering, pending-email/onboarding route restriction, confession persisted UTC timestamp after reload, and two independent sessions losing page/API access after account revocation. |
| `npx playwright test tests/browser/prompt02-http-overlap.spec.js` with `BUSHISA_TEST_HTTP_OVERLAP=true` on native Windows | BLOCKED as expected | Fails before fixture writes with: `Prompt 02 HTTP overlap checks require an isolated multi-worker Linux server, not native Windows php -S.` PHP also prints `forking is not supported on this platform`. |
| Docker Linux attempt using MySQL 8.0 plus `mcr.microsoft.com/playwright:v1.64.0-noble` | BLOCKED | First retry using a disposable MySQL container exceeded the tool timeout before producing test output; leftover disposable containers/network were removed. No PASS claimed from that run. |
| `docker run --rm -v <repo>:/work -w /work ... mcr.microsoft.com/playwright:v1.64.0-noble bash -lc "if ! command -v php >/dev/null; then apt-get update >/tmp/apt-update.log && DEBIAN_FRONTEND=noninteractive apt-get install -y php-cli php-mysql default-mysql-client >/tmp/apt-install.log; fi; php -v; mysql --version; npx playwright test tests/browser/prompt02-http-overlap.spec.js"` | PASS | Linux container reached `host.docker.internal:3318`, started five PHP CLI server processes with `PHP_CLI_SERVER_WORKERS=4`, and ran the overlap spec against `bushisa_test_prompt02_docker_overlap`. Output included `HTTP overlap barrier swipe-1791922917616-91410 participants=5019,5020`; one test passed in 1.1m. |

### Executed browser cases

| Case | Route/caller | Sessions | Result | Invariant |
|---|---|---:|---|---|
| Rendered signup/account journey | `/register.php`, `/verify_email.php`, `/login.php`, `/onboarding.php`, `/discover.php`, `/logout.php` | 1 | PASS | Synthetic user can register, receive fake approval mail, approve email, complete adult onboarding, reach discover and log out. |
| Pending account gates | `/discover.php`, `/confessions.php` redirects for pending states | 1 | PASS | Pending-email user is restricted to approval; approved but pending-DOB user is restricted to onboarding. |
| Confession timestamp rendering | `/api/confessions.php` create, `/confessions.php` rendered reload | 1 | PASS | Rendered `<time datetime>` uses stored UTC value and does not collapse an older DB timestamp to `Just now`. |
| Two-session revocation | `/discover.php`, `/api/discover.php` | 2 independent browser contexts | PASS | After DB revocation increments `session_version` and marks account suspended/inactive, both sessions lose access; JSON API returns `401`; no swipe writes exist for the revoked account. |
| HTTP reciprocal swipe overlap | `/api/swipe.php` | 2 independent browser contexts | PASS in Linux Docker; BLOCKED on native Windows | Barrier evidence proved two distinct PHP worker PIDs (`5019`, `5020`) reached `swipe_after_transaction_begin` before completion. Test asserted two reciprocal HTTP likes produced exactly two swipes, one active match and zero approved chat requests. |

### R01-R12 matrix after this entry

| Rule | Prompt 02 evidence status | Boundary |
|---|---|---|
| R01 | PARTIAL PASS | Actual route coverage now includes rendered signup/approval/onboarding and real HTTP reciprocal swipe overlap with two PHP workers. Explicit recipient chat approval through real callers remains inherited/prepared but not fully browser-executed here. |
| R02 | PARTIAL | Existing DB/service policy suites pass locally. Real route request/cancel/decline/approve/revoke/browser overlap matrix remains incomplete. |
| R03 | PARTIAL | Policy DB suite passes locally; genuine HTTP block/delete overlap remains not executed. |
| R04 | PARTIAL | DB/service and API syntax remain covered; sender-only unsend route browser evidence remains not executed. |
| R05 | PARTIAL PASS | Signup fake-mail browser route and unit/outbox regressions pass. Terminal verifier/payload erasure and delivery-failure handling remain broader Prompt 07/15 evidence. |
| R06 | PARTIAL | Existing DB/service cleanup regressions pass; actual account deletion route/browser overlap not executed. |
| R07 | PARTIAL | Existing DB/service confession erasure safeguards pass; rendered timestamp route passes; deletion/suppressed-reference browser route evidence remains incomplete. |
| R08 | NOT RUN | Environment-specific personal-data inventory and actual purge/access effects are still later D05/D06/D12 work. |
| R09 | PARTIAL | Existing DB/service evidence remains; independent replay horizon and all recoverable copies still not executed. |
| R10 | PARTIAL | UTC rendered timestamp evidence passes; full retention worker execution remains not run. |
| R11 | PARTIAL | Existing DB/service case-scoped evidence checks pass; route/role assignment/access browser evidence remains not executed. |
| R12 | NOT RUN | No isolated Azure restore or independent protective replay rehearsal was performed. |

### Remaining gates

Prompt 02 now has local Linux-container HTTP overlap execution evidence for reciprocal swipe/match creation, but it still is not P0/P1 clearance. Remaining gates are:

1. Push this feature branch or open/update a draft PR only when remote review/CI evidence is desired; do not merge to `main`.
2. Let the new `browser-route-session-overlap` GitHub Actions job run on `ubuntu-latest` as an independent CI confirmation of the local Linux-container result.
3. Execute the broader route matrix that remains outside this Prompt 02 slice: request cancel/decline/approve/revoke route coverage, block/delete/message races, role removal/moderation access, account deletion routes, full retention worker and isolated restore/replay.
4. Keep D04/D05 policy decisions, production runtime matrix, restricted runtime role, real sender/delivery, telemetry and password reset as separate gates.

## 2026-10-14 route/session/race completion entry

This entry continues branch `codex/prompt02-completion-20261013` from committed checkpoint `438397e4656d8506c9144134eb53c48110ffb7fc`. The tested artifact is the uncommitted Prompt 02 follow-up diff adding chat route/session/race regressions and focused repairs in `api/message_requests.php`, `api/messages.php`, `api/safety.php`, `api/swipe.php`, `php/policy_safeguards.php`, `php/http_test_barrier.php`, `.github/workflows/ci.yml`, `tests/browser/prompt02-http-overlap.spec.js`, `tests/browser/prompt02-chat-routes.spec.js` and `tests/browser/prompt02-chat-overlap.spec.js`. No Azure, production database, real SMTP or deployment target was used.

### Runtime and commands

| Item | Value |
|---|---|
| Branch | `codex/prompt02-completion-20261013` |
| Starting HEAD | `438397e4656d8506c9144134eb53c48110ffb7fc` |
| Local PHP | `PHP 8.5.11 (cli)` |
| Local Node/npm | Node `v20.19.6`, npm `10.8.2` |
| Local DB | MySQL `9.6.0`, `127.0.0.1:3318`, DB `bushisa_test_prompt02_local`, principal `root@localhost`, empty password, disposable local datadir |
| Docker overlap runtime | `mcr.microsoft.com/playwright:v1.64.0-noble`; PHP `8.3.6`; MySQL client `8.0.46`; Node `v24.21.0`; `PHP_CLI_SERVER_WORKERS=4` |
| Docker overlap DB | `host.docker.internal:3318/bushisa_test_prompt02_docker_overlap`, guarded with `BUSHISA_ALLOW_REMOTE_TEST_DATABASE=true` for the host-owned disposable listener |
| Guard settings | `APP_ENV=development`, `APP_DEBUG=false`, `BUSHISA_ALLOW_RUNTIME_DDL=false`, `BUSHISA_TEST_DATABASE=true`, `MAIL_PROVIDER=fake` |

| Command | Result | Notes |
|---|---|---|
| `php -l api/message_requests.php; php -l api/messages.php; php -l api/safety.php; php -l api/swipe.php; php -l php/policy_safeguards.php; php -l php/http_test_barrier.php` | PASS | Changed PHP syntax. |
| `node --check playwright.config.js; node --check tests/unit_playwright_config_guard.js; node --check tests/browser/prompt02-chat-routes.spec.js; node --check tests/browser/prompt02-chat-overlap.spec.js; node --check tests/browser/prompt02-http-overlap.spec.js; node --check tests/browser/prompt02-session-routes.spec.js; node --check tests/browser/p0-rendered-flows.spec.js` | PASS | Changed and related JS syntax. |
| `php tests/bootstrap_database.php; php bin/migrate.php; php bin/migrate.php; php tests/unit_disposable_database_guard.php; node tests/unit_playwright_config_guard.js; php tests/unit_signup_verification.php; php tests/unit_mail_configuration.php; py -3 tests/smtp_transport_test.py; php tests/integration_core_p0_flows.php; php tests/overlap_db_p0_flows.php; php tests/policy_safeguards_p0_flows.php; php tests/integration_p0_flows.php; php tests/integration_smtp_outbox.php` | PASS | Guarded local disposable DB and fake mail. |
| `npx playwright test tests/browser/p0-rendered-flows.spec.js tests/browser/prompt02-session-routes.spec.js tests/browser/prompt02-chat-routes.spec.js` | PASS | 7 browser/API route tests passed in 2.4m on Windows single-worker PHP server. |
| Docker `npx playwright test tests/browser/prompt02-http-overlap.spec.js --reporter=line` | PASS | One reciprocal swipe overlap test passed in 1.0m. Barrier `swipe-1791930733681-12572` saw worker PIDs `5021,5020`. Earlier rerun attempts exposed same-worker scheduling/barrier timeouts; test now retries only missing two-worker barrier evidence and still fails application 500s after a valid two-worker barrier. |
| Docker `npx playwright test tests/browser/prompt02-chat-overlap.spec.js --reporter=line` | PASS | Two chat overlap tests passed in 1.5m. Barriers `chat-request-1791930926037-74251` and `chat-block-1791930959820-62184` each saw worker PIDs `5021,5020`. |

### Focused repairs

- `api/message_requests.php` validates CSRF before POST authorization side effects, exposes a guarded overlap barrier, checks row counts for approve/decline/cancel/revoke races, and suppresses obsolete chat notifications on revoke.
- `api/messages.php` validates CSRF before POST authorization, keeps sender-only unsend parameters distinct for PDO, and reauthorizes with `FOR UPDATE` after the send/block race barrier before inserting messages.
- `api/safety.php` coordinates block races at the same guarded relationship-mutation barrier before closing matches.
- `api/swipe.php` catches lock-release failures, while the overlap spec now tolerates only infrastructure-level missing-barrier retries.
- `php/policy_safeguards.php` suppresses message-request notifications whenever match requests are revoked by block/delete cleanup.
- `php/http_test_barrier.php` remains fail-closed outside guarded development overlap runs and now handles concurrent directory creation without noisy warnings.

### Executed Prompt 02 case matrix

| Case ID | Route/caller | Actors/sessions | Result | Key invariant |
|---|---|---:|---|---|
| P02-CHAT-01 | `/api/message_requests.php`, `/api/messages.php`, `/api/safety.php` | 3 users, 3 independent browser contexts | PASS | Missing CSRF and forged outsider ownership denied with zero writes; pending request blocks send; recipient approve enables send/read; report preserves moderation evidence; non-sender unsend denied; sender unsend purges ordinary body but keeps scoped evidence; revoke denies later send and leaves one message. |
| P02-CHAT-02 | `/api/safety.php`, `/api/account.php`, `/api/messages.php`, `/api/discover.php` | 2 users, two sessions for deleting user | PASS | Missing-CSRF block denied; real block closes match, revokes chat grant, preserves message/evidence rows, suppresses obsolete chat notifications and denies later send; account deletion marks user deleted and a second stale session receives `401`; protective replay event recorded. |
| P02-CHAT-03 | `/admin/dashboard.php`, `/admin/reports.php` | 1 moderator, 2 independent browser contexts | PASS | After DB role removal, both existing moderator sessions are redirected to `/discover.php`; no report rows are created. |
| P02-RACE-SWIPE-01 | `/api/swipe.php` | 2 users, 2 contexts, two PHP workers | PASS | Overlapping reciprocal likes produce exactly two swipe rows, one active match and zero approved message requests. |
| P02-RACE-CHAT-01 | `/api/message_requests.php` | 2 users, 2 contexts, two PHP workers | PASS | Overlapping approve and revoke leave the grant revoked and leave zero obsolete chat deep-link notifications. |
| P02-RACE-CHAT-02 | `/api/messages.php` versus `/api/safety.php` | 2 users, 2 contexts, two PHP workers | PASS | Overlapping send and block leave the match blocked, chat grant revoked, at most one already-raced message, zero obsolete chat notifications and no later message writes. |

### R01-R12 matrix after this entry

| Rule | Prompt 02 result | Boundary |
|---|---|---|
| R01 | PASS for actual signup/onboarding and reciprocal swipe route overlap; PARTIAL overall | Real route signup/approval/onboarding and two-worker reciprocal match creation passed. Production launch scope and broader matching catalogue gates remain separate. |
| R02 | PARTIAL PASS | Actual request/approve/revoke route and approve-vs-revoke overlap passed. Cancel/decline expiry/cooldown/budget/rematch choices remain provisional D04 policy work. |
| R03 | PARTIAL PASS | Actual block route and send-vs-block overlap passed; message/evidence rows survive and obsolete notifications are suppressed. Full account deletion overlap, rollback injection and production retention gates remain later work. |
| R04 | PASS for current actual unsend route | Browser/API test proves non-sender denial, sender-only unsend, ordinary-body purge and retained moderation evidence. D04 final retention wording remains pending. |
| R05 | PARTIAL PASS | Signup fake-mail browser route, units, SMTP fixture and outbox regression passed. Real sender/ACS and broader terminal verifier delivery-failure matrix remain later tasks. |
| R06 | PARTIAL PASS | Actual account deletion route passed for deleted status, stale-session revocation, relationship cleanup and protective event. Full data inventory/media/cache/log purge remains D05/D12 work. |
| R07 | PARTIAL | Existing DB/service confession erasure and rendered timestamp evidence remain; this entry did not add confession deletion/suppressed-reference browser coverage. |
| R08 | NOT RUN | Environment-specific personal-data inventory and full purge/access effects remain later D05/D06/D12 work. |
| R09 | PARTIAL | Cleanup/replay rows are asserted for deletion and moderation cases, but independent replay horizon and recoverable-copy proof remain later work. |
| R10 | PARTIAL | UTC rendered timestamp evidence and purge due fields remain covered; full retention worker execution was not run. |
| R11 | PARTIAL PASS | Actual outsider message-report denial and report evidence capture passed; role-removal sessions lose admin pages. Assigned-moderator case evidence UI/API matrix remains later work. |
| R12 | NOT RUN | No isolated Azure restore or independent protective replay rehearsal was performed. |

### Remaining gates

CI has not yet run for this exact candidate commit because the branch was still local at the time of this entry. Production version matrix, restricted runtime role, isolated restore/replay, real sender, telemetry, password reset and final D04/D05 owner approvals remain separate gates. Native Windows remains unsuitable for HTTP overlap; the PASS overlap evidence is from the isolated Linux Docker runtime above.
