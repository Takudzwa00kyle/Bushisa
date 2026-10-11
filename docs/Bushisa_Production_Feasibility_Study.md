# Bushisa production feasibility study

Prepared **8 October 2026**, Africa/Harare.  
Repository: [Takudzwa00kyle/Bushisa](https://github.com/Takudzwa00kyle/Bushisa).  
Reviewed branch: `main`. Reviewed commit: **`ecf6217ceda74cc257d75483f253862b932dfe9d`**, “Matchmaking Alg”, committed 8 October 2026 at 17:30:26 +0200.

## 1. Decision

**The project is technically feasible, but this commit is not ready for production.** Continue development on the existing PHP/MySQL application. Do not open registration to real users until the release blockers below have been repaired and verified in staging.

Bushisa already has server-rendered pages, a responsive CSS layer, password hashing, CSRF protection, session helpers, reciprocal preferences, a versioned 20-question questionnaire, mutual-like logic, messaging permission records, confessions, notifications, moderation pages, and an account-deletion route. A framework rewrite is unnecessary to address the observed problems.

The immediate problem is that several essential flows fail or enforce incomplete rules. Local HTTP/database tests reproduced failed mutual matching, chat, blocking and account deletion; automatic verification without email proof; missing age onboarding; continued posting by a suspended session; incomplete confession reporting; and recommendation pagination/retrieval defects.

The planning scenario is approximately **5,000 students**, as stated in the repository’s matching requirements. That is an account-count assumption, not a measured concurrency level or evidence of market demand.

| Feasibility dimension | Assessment | Condition for a positive decision |
|---|---|---|
| Technical | Feasible after remediation | Repair application flows and establish one tested schema and deployment process. |
| Operational | Not yet ready | Assign moderation, support, release, backup and incident responsibilities. |
| Economic | Not established | Budget engineering, hosting, email delivery, media, backups, moderation and applicable compliance work. Revenue and demand evidence are absent. |
| Legal/privacy | Pending review | Resolve applicable Zimbabwe obligations and align actual processing with the privacy/retention notices. |
| Schedule | Achievable in phases | Prioritize a narrow, tested launch scope; allow time for membership verification and operational decisions. |

**Priority definitions:** P0 blocks any production release involving real users. P1 must be completed or explicitly removed from the initial public-launch scope. P2 can follow a successful controlled launch. These are delivery priorities, not formal security severity ratings.

## 2. Scope and evidence

The review used a fresh clone of the named repository, not an older feasibility document as the source of implementation facts. It inventoried the 75 tracked files and examined the 52 PHP files, five JavaScript files, both database schemas, configuration, CI, and the main browser/API flows. Earlier reports inside `docs/` describe older commits and contain observations that are now stale: questionnaire scoring, several APIs, notification tables and account-deletion routes have since been added.

Verification performed:

- PHP syntax validation: **52/52 files passed**, using PHP 8.3.6 from the Ubuntu package build available in the review environment.
- JavaScript syntax validation: **5/5 files passed**, using `node --check`.
- Focused execution of age checks, questionnaire validation, reciprocal gender eligibility, hard intent preferences, and scoring.
- Local HTTP/database probes with **native PDO prepares**, **MariaDB 10.11.14**, a disposable database, synthetic accounts, and manually created fixtures where a broken preceding flow prevented later testing.
- Source inspection of deployment, schema, session, media and retention behavior. Repository source files were not modified and nothing was pushed or deployed.

The probes are diagnostic evidence, not a passing regression suite. MariaDB was the available database runtime; repeat integration tests on the exact MySQL/MariaDB version selected for production. This review did not verify an existing hosting account, DNS, TLS termination, production secrets, SMTP delivery, real student membership, browser rendering/accessibility, peak-load capacity, or external integrations. It is not a penetration test or a legal compliance certification.

### Selected observed results

| Probe | Actual result | Consequence |
|---|---|---|
| Register with a generic four-letter student ID and a syntactically valid institutional-domain email | Account created; `is_verified=1`; no email proof | Membership/identity claim is unsupported. |
| Inspect newly registered profile and consents | DOB `NULL`; zero consent records | Age and consent helpers are not connected to onboarding. |
| Fresh user requests discovery | HTTP 400: adult DOB required | A normal new user cannot complete the matching journey. |
| Two eligible fixtures like each other | First like HTTP 200; reciprocal like HTTP 500; zero matches | Mutual matching is broken. |
| Matches API, messages API and chat page | HTTP 500 with native PDO parameter errors | Core communication fails. |
| Block an established match | HTTP 500; block persisted and match remained | Safety action is partially applied. |
| Suspend an already signed-in account, then publish a confession | HTTP 200; post created | Suspension does not invalidate the active session’s access. |
| Delete account after password confirmation | HTTP 500 | Deletion cannot be advertised as working on this tested stack. |
| Report an anonymous confession without its hidden author ID | HTTP 501 | The server does not resolve the target into a usable report. |
| Reference a flagged confession | Feed returned its excerpt | Moderated content can reappear through references. |
| Request the next discovery page using only its returned cursor | First-page IDs repeated | Cursor pagination is ineffective. |
| Put 500 ineligible rows before an eligible candidate | Zero results and `has_more=false` | The candidate limit can create a false empty pool. |
| Use a DB account with only SELECT/INSERT/UPDATE/DELETE | Request-time schema bootstrap failed with CREATE denied | Runtime currently requires schema-changing privileges. |
| Serve the repository root using the local PHP server | `/logs/app.log` and `/database/bushisa.sql` returned HTTP 200 | A safe production web root/server policy is required. |

The file-exposure probe demonstrates the repository-root deployment risk. It does not establish that a separately configured production server is currently exposing those paths. Syntax success likewise does not establish functional readiness.

## 3. What exists and what is unfinished

| Capability | Current implementation | Production gap |
|---|---|---|
| Login and registration | Forms, bcrypt, CSRF and login throttling | Real verification, recovery, approved student-number validation, age/consent onboarding and stronger abuse controls. |
| Discovery and Find Match | Shared ranking service, reciprocal age/gender rules, 20-question catalog, directional scores, confidence/coverage labels | Working onboarding, correct pagination, unbiased candidate retrieval, privacy-safe responses and evaluated outcomes. |
| Mutual matching | Like/pass records, canonical user ordering and unique pair constraint | Native query repairs, idempotency and simultaneous-like handling. |
| Messaging | Match membership checks, permission records, send/read/unsend API code and server forms | Query repairs, consistent permission transitions, bounded history and finished live UI integration. |
| Confessions | Posting, anonymous handles, flag filtering, reference and view-count API code | Abuse-resistant reporting, moderated-reference filtering and consistent UI/API behavior. |
| Profile media | One active profile-photo upload, extension/MIME checks and JPEG re-encoding | Persistent storage, cleanup, all-format processing, total quota and the two additional advertised slots. |
| Notifications | Tables, creation helpers, API and a rendered panel | Working preference controls and read state; push is an optional separate feature. |
| Moderation | Admin pages and suspension/report helpers | Canonical roles, provisioning, current authorization, usable cases/evidence and operational ownership. |
| Account deletion | Password confirmation and anonymisation code | Native SQL compatibility, complete data lifecycle and invalidation of every session. |
| Release/operations | Environment-variable DB support and Apache HTTPS rules | Real CI, runtime configuration, safe web root, migrations, health checks, monitoring, backups and rollback. |

## 4. P0: release blockers

### F01 — Verification and adult onboarding are incomplete

Evidence: [registration verification flag](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/auth.php#L226), [NULL DOB insertion](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/auth.php#L237), [registration form](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/register.php#L82), and [age requirement in matching](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/matching/service.php#L27).

Registration accepts any lowercase alphanumeric identifier of length 4–20, sets `is_verified=1`, and creates a profile with no DOB. Neither the registration form nor the active profile update path provides a functioning DOB step. Consent helpers exist but are not called by normal registration. Matching then refuses the new user, while other signed-in features remain accessible.

Add a complete onboarding state machine: registered → email pending → email confirmed → adult eligibility and required acknowledgments completed → discovery eligible. Store hashed, expiring, single-use verification tokens; add resend limits and email delivery/retry handling. Confirm the institutional student-number rules and membership policy, normalize letters to lowercase, and validate on the server. An email-domain suffix alone does not prove ownership or current enrolment.

Connect strict DOB validation to writes and eligibility. The standalone `verify_age()` correctly rejected a future date; `matching_age_from_profile()` interpreted `2080-01-01` as age 53 because it used an unsigned date difference. Reuse one strict validator, reject future/impossible dates, and keep full DOB private. Record policy versions and timestamps for required acknowledgments and any optional discovery/data-processing consent.

**Acceptance:** No unconfirmed account receives verified status; an eligible adult can complete onboarding through the real UI; underage/future-date submissions are rejected; missing required acknowledgments block eligibility; registration tests cover approved and rejected student IDs.

### F02 — Native database queries break essential flows

Evidence: [PDO configuration](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/config/db.php#L12), [mutual match lookup](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/swipe.php#L166), [chat query](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/chat.php#L39), [matches API](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/matches.php#L31), [messages API](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/messages.php#L45), and [blocking query](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/safety.php#L59).

Seven SQL string templates in six files reuse named parameter markers. With `PDO::ATTR_EMULATE_PREPARES=false`, executed paths produced `SQLSTATE[HY093]: Invalid parameter number`. The [PHP PDO manual](https://www.php.net/manual/en/pdo.prepare.php) requires distinct named markers under native prepares. This directly explains the tested reciprocal-like, chat, matches, messages and blocking failures. Unsend and permission-revocation templates contain the same pattern and require regression coverage even where an earlier failure prevented execution.

Use unique parameter names or positional markers for every occurrence. Keep native prepares enabled. On the tested MariaDB version, the anonymiser’s parameterized `SHOW COLUMNS ... LIKE` and `SHOW TABLES LIKE` probes also failed with SQLSTATE 42000/1064. Use parameterized `information_schema` queries, or another proven compatible approach, for those helpers and similar admin probes.

**Acceptance:** Both likes create exactly one match; members can request/approve/revoke chat, send/read/unsend messages and block; unrelated users receive authorization errors; account deletion succeeds; every route is tested on the selected production database with native prepares.

### F03 — The schema and upgrade process are not production-safe

Evidence: [destructive bootstrap](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/database/bushisa.sql#L4), [alternative user schema](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/sql/schema.sql#L6), and [runtime DDL](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/operational.php#L72).

`database/bushisa.sql` matches many active code paths but lacks a `users.role` column. Login therefore assigns the fallback `user` role and the moderator gates are inaccessible after a clean canonical install. `sql/schema.sql` includes roles but uses incompatible user/profile columns and lacks the preference structure required by active code. Neither file is a reliable, complete release contract.

The first schema drops core tables on import. `ensure_operational_schema()` creates tables and adds columns during requests, repeating schema checks per request. A restricted runtime DB user failed even after tables existed because CREATE privileges were still required. Several operational tables lack foreign keys, increasing orphan/deletion risks.

Adopt one canonical schema with additive, numbered migrations and a migration-history table. Provide an explicit migration for each supported legacy variant, including roles and account status. Run migrations once as a release task using a separate migration account; runtime should have only needed data privileges. Keep destructive reset/seed commands limited to disposable development/test databases. Guard the seed script against production: it can overwrite passwords and restore verified/unsuspended states for matching IDs.

**Acceptance:** A clean install and an upgrade of representative existing data both work; migrations preserve users/messages and can be rehearsed from a backup; an approved moderator can be securely provisioned; ordinary users cannot reach admin actions; no request executes CREATE/ALTER/DROP.

### F04 — Suspension, deletion and privilege changes do not revoke access

Evidence: [central login guard](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/session_manager.php#L138), [login-only suspension check](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/auth.php#L49), and [suspension helper](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/moderation.php#L14).

`require_login()` checks only the session’s user ID. It does not reload verification, suspension, deletion or current role. Suspension blocked a future login but an already signed-in account still posted successfully. Role checks also trust the session’s cached role.

Create a shared authorization guard that loads current account eligibility and privileges. Add a session-version/revocation mechanism, an absolute session lifetime and appropriate recent-authentication checks. Suspend/delete/reset-password events must invalidate all existing sessions. Return HTTP 401 JSON for unauthenticated APIs rather than redirecting to login HTML; the local unauthenticated API probe ended at HTTP 200 HTML after following the redirect.

**Acceptance:** Suspension and deletion stop actions immediately in every open session; role removal stops admin access; APIs have stable 401/403 behavior; expired sessions cannot mutate data.

### F05 — Safety and privacy controls need to work consistently

Evidence: [safety actions](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/safety.php#L42), [anonymous-report limitation](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/safety.php#L126), [flag action](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/confessions.php#L129), [reference joins](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/confessions.php#L58), and [candidate DOB response](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/matching/service.php#L71).

Blocking is not an atomic service operation in the active API: the block insert survived when match cleanup failed. Ordinary authenticated users can directly flag and hide any confession without a rate-limited moderation report. Anonymous confession reports fail if the caller does not supply an author ID that the public feed correctly keeps hidden. References can return excerpts of flagged content. Candidate responses expose full DOB although age is sufficient.

Route browser forms and APIs through the same safety service. Resolve reported owners from authorized server-side target records; do not trust arbitrary client author IDs. Add report/block/unmatch controls to discovery, chat and confessions, with reliable success/error states. Rate-limit reports and flags, retain a reviewed case trail, prevent suppressed content from leaking through references, and remove DOB from public DTOs. Define whether blocking closes or hides a match and preserve restricted report evidence as required by the retention policy; current core foreign keys delete messages when a match is deleted.

**Acceptance:** A user can report an anonymous post without learning its author; blocks immediately prevent further interaction; failures leave a consistent state; flagged content is absent from all public references; moderator cases and access are tested; public responses contain only intended fields.

### F06 — Account deletion and retention do not match the stated policy

Evidence: [deletion API](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/account.php#L48), [anonymisation implementation](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/anonymiser.php#L10), [soft-delete fields](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/anonymiser.php#L149), and [retention policy](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/docs/data_retention_policy.md).

The tested deletion request failed before successful anonymisation. Separately, source inspection shows that the intended soft-delete path leaves DOB, gender, faculty/year, questionnaire answers and several interaction/operational records intact. It does not invalidate other sessions, close existing matches, or implement the policy’s scheduled retention rules. Photo removal happens before the SQL transaction completes and could leave a missing file after a rollback. Clearing a password alone is not a complete deleted-account state.

After fixing SQL compatibility, implement explicit `deleted_at`/account status, all-session revocation, immediate discovery removal and defined match/history behavior. Inventory every table and media object; erase, anonymise or retain each with a documented purpose and expiry. Add reliable cleanup jobs, retry handling, deletion tombstones for restored backups, legal-hold handling where applicable, and an access/export/correction request process. Review indefinite-retention claims before treating them as a legal requirement.

**Acceptance:** Deletion completes through the real UI and API; all sessions lose access; retained fields match the reviewed notice; abandoned photos and operational rows are cleaned; a backup restore does not reactivate a deleted account. Post-success deletion effects remain unverified until the broken path is repaired.

### F07 — A safe, repeatable production release is missing

Evidence: [DB defaults/error output](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/config/db.php#L4), [Apache rules](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/.htaccess), [file logger](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/logger.php#L42), and [CI placeholder](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/.github/workflows/ci.yml#L7).

There is no documented release pipeline or meaningful CI check. The workflow only echoes a placeholder. There are no tracked automated tests or runtime/dependency manifests. DB defaults permit root with an empty password, and connection errors are printed regardless of `APP_DEBUG`. `.env.example` uses `bushisa` while the schema/default use `bushisa_db`; code reads `getenv()` and contains no dotenv loader, so copying that file alone does not configure the app. The existing `public/` directory is not a complete safe web root: most active routes/assets are at repository root.

Provide either a container image or a reproducible server provisioning/release script; Docker is an option, not a prerequisite. Define runtime/extensions, PHP-FPM and web-server configuration, secret injection, writable paths, persistent media/sessions, health/readiness probes, migrations, deployment verification and rollback. Exclude logs, SQL, docs, configuration, internal helpers and repository metadata from web access. Disable directory listing and PHP execution in media directories. Set HTTPS, secure cookies, proxy trust and environment validation explicitly; the Apache rewrite currently checks only backend HTTPS state and can misbehave behind TLS termination.

Add real CI that checks out code, lints, runs database-backed regression tests, validates migrations and checks dependencies/secrets where applicable. Require a staging release, backup/restore rehearsal and monitoring before the production gate.

**Acceptance:** One documented process deploys a clean environment; missing production secrets fail startup; runtime DB privileges are restricted; private paths return 403/404; HTTP/TLS/proxy behavior is verified; backups restore successfully; CI blocks a broken release.

## 5. P1: finish or reduce the public-launch scope

### F08 — Candidate retrieval, pagination and matching quality

Evidence: [500-row retrieval cap](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/matching/service.php#L216), [offset/cursor selection](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/matching/service.php#L38), and [API supplies default offset](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/discover.php#L23).

Retrieval selects the first 500 unswiped, unblocked rows in ascending user ID before PHP applies reciprocal compatibility. This creates early-account exposure bias and false empty results. The API always supplies an offset, defaulting to zero, so the service ignores a supplied cursor. Even repaired offsets can skip candidates when swipes remove earlier rows between page requests.

Apply reciprocal hard constraints during retrieval where feasible, page or rotate the eligible pool, and use a stable keyset/snapshot cursor. Add discoverability/pause and recent-activity fields, exposure logging and a modest variety policy. Validate the questionnaire’s matrices and interpretation; the current score is a heuristic fit indicator, not a calibrated romantic-success probability. Version checks and `needs_review` answers must be enforced during reads/scoring as well as writes.

**Acceptance:** Pagination returns distinct candidates despite intervening swipes; an eligible later account is reachable; passed/blocked/suspended profiles stay excluded; unknown/legacy answers do not silently become evidence; test with a representative 5,000-account dataset.

### F09 — Race conditions and messaging permission transitions

Evidence: [swipe transaction](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/swipe.php#L127) and [request upsert](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/api/message_requests.php#L89).

Existing-swipe checks occur before insertion, and reverse-like checks need concurrency testing: two simultaneous likes can each miss the other’s uncommitted row. A unique pair constraint prevents some duplicates but does not guarantee one match is created. Return idempotent outcomes and use a tested transaction/locking or pair-processing strategy.

The re-request test changed a revoked permission to pending but left `revoked_at` populated. Updates conditionally inspect a status that is also changed earlier in the same statement. Declined/revoked re-request policy and opposite-direction requests need a single authoritative state model. Server forms and APIs currently implement different request checks/rate limits; sending in the server form also relies on the textarea’s client-side length limit.

**Acceptance:** Concurrent and retried likes produce exactly one match; permission transitions are atomic and consistent; request retries do not misreport success; block/revoke races cannot authorize a send; all write paths enforce the same size/rate limits.

### F10 — Media storage and quota

Evidence: [upload handling](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/upload_validator.php#L57), [JPEG processing](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/upload_validator.php#L99), and [disabled image slots](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/profile.php#L183).

The 5 MB limit applies to an individual file. Replacements create new files without removing old ones; the displayed quota measures only the current profile image. `profile_images` is created but not integrated with the active upload path. JPEG EXIF stripping silently skips work without GD; PNG/WebP are not re-encoded. One uploaded photo is tracked under `uploads/`, while `.gitignore` excludes only `public/uploads/`.

Choose persistent object storage or a backed-up media volume, generate opaque filenames, decode/re-encode permitted formats, strip metadata, bound dimensions and processing resources, enforce actual per-account storage limits, and safely clean superseded/orphaned images. Add the three-slot model only if required for launch; a polished single-photo release is viable if its UI and policy are accurate. Remove/ignore actual upload content from source control after checking whether tracked files are intended assets.

### F11 — Complete the active UI/API contract

Evidence: [page script loading](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/php/frontend.php#L219), [chat page ending](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/chat.php#L316), [unused auth request target](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/js/auth.js#L438), and [legacy read route](https://github.com/Takudzwa00kyle/Bushisa/blob/ecf6217ceda74cc257d75483f253862b932dfe9d/js/chat.js#L8).

Active pages generally load only `js/app.js`. The larger auth/discovery/chat/confession scripts are not evidence that their dynamic features work. Simply enabling them is unsafe: `auth.js` posts to a helper file rather than an auth controller; `chat.js` expects an unimplemented `/api/messages.php/read` contract and uses a different message-length limit. Chat currently depends on form posts/page reloads rather than the unused polling implementation. Preference switches, extra photos and other controls remain disabled, and some UI text exposes implementation notes.

Choose the active rendering/interaction model, remove stale code or finish its contracts, centralize service logic, standardize JSON error/response shapes and add bounded history pagination. Finish report/block/unmatch/unsend and notification read/preference interactions where promised. Provide user-facing onboarding/error states for missing DOB or verification rather than a generic empty pool. Perform browser/mobile, keyboard and accessibility checks and slow-network tests.

### F12 — Recovery, throttling, monitoring and maintenance

Add secure password-reset/recovery and password change with token expiry and all-session revocation. Add registration/upload/report/request throttles and a bounded input/body policy. Review the login limit keyed only by IP: campus users may share an address, and a successful login currently clears that IP’s recorded failures. Use a balanced account/IP strategy and a trusted proxy-IP policy.

Add bounded message histories and indexes based on query plans; core messages lack an explicit `(match_id, id)` index while current queries can fetch an entire conversation. The matches API aggregates message history broadly. Rotate/centralize logs, prune expired rate-limit data, monitor errors/latency/database health/disk usage/email failures, and keep PHP/database security updates current. Define who receives alerts and owns restores and incidents.

## 6. Production design and deployment additions

A small monolithic deployment is a reasonable starting design for the stated account count. Capacity remains conditional on concurrency, query repair and load testing.

| Component | Initial requirement |
|---|---|
| Web/application | TLS ingress with explicit protected paths; PHP-FPM using an actively supported PHP branch; application instances deployed from a pinned commit. |
| Database | Supported MySQL/MariaDB version selected and tested; private access; runtime/migration users separated; migrations and appropriate indexes. |
| Sessions | Protected persistent session store; revocation/version checks. A single-instance file store can work initially; multiple instances need shared sessions or an explicitly designed routing arrangement. |
| Media | Persistent storage, access policy, image processing, quotas and cleanup; never rely on an ephemeral deployment directory. |
| Email | Configured delivery for verification/recovery, retry/outbox handling and delivery visibility. |
| Jobs | Controlled scheduler/worker for mail retries, cleanup, retention and backups. A database-backed outbox and cron may suffice initially. |
| Operations | Health/readiness checks, centralized errors, uptime/latency alerts, encrypted off-host backups, restore rehearsal and release rollback. |
| Testing/release | Real CI, staging with synthetic data, smoke tests and controlled rollout. |

Select PHP 8.4 or 8.5 after compatibility tests; both are listed as actively supported on the [official PHP support page](https://www.php.net/supported-versions.php) at this review date. The review’s PHP 8.3 runtime establishes diagnostic behavior, not the final production version. Required extensions should include `pdo_mysql`, `fileinfo`, `mbstring` and the chosen image-processing extension, with startup checks for anything the app depends on. Use UTC consistently for PHP/database storage and render local times deliberately.

Do not assume a static host or a JavaScript-only hosting service can run this PHP/MySQL repository. Kubernetes, microservices, Redis, WebSockets, a native app and machine-learning ranking are not initial prerequisites. Add them only when measured needs justify their cost.

Minimum release configuration must document database variables, application environment/debug handling, secure-cookie settings, application/base URL, mail configuration, session/media paths, upload/body limits, log destinations, database/PHP timeouts and proxy behavior. A configuration file is useful only if the deployment actually loads it.

## 7. Capacity and economic feasibility

Five thousand registered accounts should be treated separately from simultaneous active users. Start with representative data for 5,000 profiles, questionnaire answers, swipes, reports and realistic conversation histories; then test agreed peak workloads. Candidate scoring repeatedly loads up to 500 profiles and answer sets, while runtime schema inspection adds overhead. Chat’s dormant script would poll the thread every five seconds and matches every eight seconds: if 500 clients used both intervals continuously, that alone would be about **163 requests/second** (`500/5 + 500/8`), before other traffic. This is a planning calculation, not current measured traffic.

Suggested staging targets, to be agreed rather than treated as existing guarantees: 100 concurrent mixed users initially; assess 500 if the intended campus launch needs it; p95 normal API latency below one second; zero unauthorized data returned; no missed/duplicate matches under concurrency; bounded database connections and memory; and complete recovery from a stopped application instance. Measure query plans and optimize retrieval/indexes before adding infrastructure.

Economic feasibility is conditional. The repo does not provide a hosting budget, revenue model, support staffing, acquisition/retention evidence or verified supplier quotes. Obtain current quotes for compute/database, media/egress, transactional mail, backups, monitoring and applicable compliance costs. Include moderator and engineering time: those may exceed raw hosting costs. Model monthly cost as fixed infrastructure/support plus active-user email/media/traffic costs; test whether the campus pilot generates enough useful reciprocal connections to justify continued spending.

Prioritize a working, safe connection flow and clear limits. Browser push, extra photos, location-based discovery, advanced exposure learning and native apps can be P2 enhancements after evidence from the pilot.

## 8. Operational and regulatory readiness

Before launch, assign named owners for engineering/releases, account support, moderation and privacy requests. Define report triage, appeals, evidence access, escalation and incident response; set an achievable response target. Confirm the campus-membership policy, graduation/inactivity handling and authorization to imply institutional affiliation. Obtain actual pilot feedback about trust, empty pools and meaningful connections.

POTRAZ’s published [S.I. 155 of 2024](https://www.potraz.gov.zw/wp-content/uploads/2025/02/sI-155-of-2024-Cyber-and-Data-Protection-Normal_240913_1250178.pdf) establishes a data-controller licensing regime, exemptions, DPO provisions and obligations concerning data security and transfers. Review current applicability with qualified local advice or POTRAZ before launch, including the operator, hosting jurisdiction and licence category. The repository’s privacy pages and indefinite-consent-retention wording do not establish compliance. Confirm current amendments, notices, lawful processing, rights handling and processor agreements. This is a release dependency, not a determination that a particular licence or legal conclusion applies to Bushisa.

## 9. Delivery sequence and indicative effort

These are engineering planning estimates inferred from the code, not measured completion times or supplier commitments. Assumptions: one developer comfortable with PHP/PDO/database work, timely product decisions, a narrow initial scope, no framework rewrite, and synthetic staging data. External approvals, university integration, data cleanup and regulatory lead times are excluded.

| Phase | Deliverables | Indicative developer days |
|---|---|---:|
| 1 — Establish a working baseline | Canonical schema/migrations, roles, native query fixes, regression harness and CI | 6–9 |
| 2 — Complete identity and access | Verified-email/recovery, student-ID policy, DOB/consent onboarding, account status and all-session revocation | 7–11 |
| 3 — Finish safety and lifecycle | Atomic safety services, reporting UI, reference privacy, deletion/retention, durable media and quota cleanup | 8–13 |
| 4 — Stabilize product behavior | Cursor/retrieval fixes, concurrency handling, messaging/preference integration and browser/mobile QA | 5–9 |
| 5 — Prepare and rehearse release | Environment/server configuration, staging, monitoring, backups/restore, workload tests and controlled pilot | 4–7 |
| **Total** | **A restricted, production-ready initial scope** | **30–49** |

Allow approximately **6–10 working weeks for one developer**, with some specialist/QA review and time for decisions. Additional media slots, browser push, geographic matching or a learned recommender increase scope. Release by acceptance evidence, not the calendar estimate.

## 10. Production release checklist

- [ ] One canonical schema supports clean install and non-destructive upgrades; runtime contains no DDL.
- [ ] Native prepared queries pass matching, messaging, blocking, moderation and account-deletion tests on the selected production DB version.
- [ ] Verification requires email ownership proof; adult eligibility and approved student-number rules are enforced server-side.
- [ ] Required policy acknowledgments/consents are recorded with versions; paused/deleted/unverified/suspended accounts are excluded as appropriate.
- [ ] Every session is invalidated by suspension/deletion/password-reset/privilege changes; API authentication returns JSON 401/403.
- [ ] Concurrent/retried likes create one match; request/revoke/block races do not allow unauthorized chat.
- [ ] Anonymous reports resolve safely; moderated references cannot leak content; DOB/private identifiers do not appear in public responses.
- [ ] Deletion and scheduled retention match the reviewed notices, including media, questionnaire data, evidence and backups.
- [ ] Discovery pagination is stable and reaches eligible later accounts; a representative dataset and empty-pool cases are tested.
- [ ] Launch UI has no misleading disabled features or implementation notes; browser/mobile/accessibility tests pass for critical flows.
- [ ] Production secrets, PHP/extensions, HTTPS/proxy handling, protected web root, media/session storage and request limits are validated.
- [ ] CI, staging and smoke tests are working; release rollback and a database/media restore are rehearsed.
- [ ] Monitoring and alert ownership exist; agreed workload targets are met without authorization/data-integrity failures.
- [ ] Support/moderation/privacy owners, institutional policy and applicable regulatory obligations are resolved.

**Recommendation:** Preserve the existing stack, repair and test the P0 flows first, finish a deliberately small public-launch scope, and launch a controlled campus pilot only after the checklist is satisfied. Deploying the current repository unchanged would expose an incomplete identity/safety system and several broken core features.
