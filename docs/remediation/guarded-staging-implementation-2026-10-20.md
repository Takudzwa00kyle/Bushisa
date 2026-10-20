# Guarded staging origin, private fake carrier and ingress driver

Continues from audit `7cc97bc` on
`codex/guarded-staging-origin-carrier-20261020`. The audit checkout was clean;
no applicable AGENTS.md was found in the checkout or ancestors. This implements
the [final brief](azure-compatibility-staging-plan-2026-10-19.md#41-final-bounded-engineering-brief-stg-i01--stg-i02--managed-driver),
using [Q47 and existing decisions](owner-policy-reconciliation-2026-10-15.md#q47-password-reset).
No Q40, production throttle, funding or reviewer appointment is adopted.

## Implemented boundary

| Surface | Behavior / source |
|---|---|
| Admission | [staging_guard.php](../../php/staging_guard.php) requires the whole pinned version-1 manifest and explicit role/intent, before connection. `APP_ENV=staging` alone is refused. Production/debug/runtime-DDL/test flags, fake provider/transport, trusted origin, resource/database identity, private storage and five distinct SQL roles are checked. A partially configured stage profile fails closed instead of falling back. |
| Actual DB identity | Before fixture writes or app mutations, verify selected schema, server UUID and `CURRENT_USER()` against the manifest; then the independently prepared run/binding/resource/namespace marker. Check actual grants: no global/foreign/inherited grants, runtime DDL or marker writes, and runner SELECT only. Managed connections retain PDO CA/hostname verification and require a nonempty cipher. A file pin or a test-looking name alone proves no resource identity. |
| Reset origin | [password_reset.php](../../php/password_reset.php) admits only the manifest's exact derived stage HTTPS origin under the complete guard set. Ordinary `https://bushisa.me` admission and the independent existing loopback cohort remain unchanged. Host/Forwarded/redirect input never supplies origin authority. Missing guards cannot promote an arbitrary APP_BASE_URL. |
| Target isolation | New stage reset requests/consumption require the server's immutable account-generation/recipient binding in the fixture registry. A pending account remains pending and cannot recover adult/verified permissions. Unlisted recipients return the generic request outcome without creating rate/challenge rows. Current account state and generation are still checked by L06. |
| Signup | [staging_mail.php](../../php/staging_mail.php) queues stage signup with the existing `v1` encrypted signup envelope, actual approval-code reference and signup useful deadline. Reset/notice envelopes remain `r1` and purpose separated; no invented approval-code reference, plaintext legacy fake-mail fallback or signup-guard relaxation. |
| CLI fake carrier | [dispatch_synthetic_stage_mail.php](../../bin/dispatch_synthetic_stage_mail.php) is CLI-only and requires the worker role, dispatch intent, bounded explicit job list and private runner pipe before connecting. It validates all selected targets before claims, rechecks bindings at carrier I/O and reuses existing claim/receipt/cleanup state machines. No SMTP mailer, SMTP credential load or SMTP fallback. The ordinary SMTP CLI is unchanged. |
| Private proof channel | STDOUT contains counts only. An explicitly inherited descriptor 3 plus a per-child random nonce carries proof to the owned runner in memory, after the transport result transaction commits. No public inbox/proof endpoint. Terminal retries retrieve no old proof; an interrupted private result requires a new ordinary request, subject to original throttles and clocks. A transport acceptance is synthetic, not inbox delivery. |
| Sessions | [session_manager.php](../../php/session_manager.php) validates the stage profile before session start and restricts this profile to native `files` under its private run namespace, Secure cookies and existing native authorization/version checks. This neither adopts Q40 nor supplies a production session-copy cleanup/fencing adapter. |
| Preparation | [prepare_synthetic_stage.php](../../bin/prepare_synthetic_stage.php) is a separate explicit CLI action on an empty migrated synthetic schema. It checks actual identity before DDL/writes, installs two fixture-only identity tables, creates new anchored accounts and bindings, and approves only the explicitly synthetic active fixtures. The transaction contains account creation/approval; a retry verifies existing bindings without resetting passwords/clocks. No production migration is added. |
| Driver | [managed_stage_driver.js](../../bin/managed_stage_driver.js) separates read-only preflight from explicit local/managed execution; it never bootstraps, drops or resets a DB. [stage_control.php](../../bin/stage_control.php) uses the SELECT-only runner role for schema, roster, snapshot and job checks through private IPC. Browser traffic is same-origin only; no traces/screenshots/video/DOM dumps or exception bodies are published. Two independently logged-in contexts must lose page/API access after reset. |

Managed subscription binding additionally compares the privately captured
`WEBSITE_OWNER_NAME` exactly with `resources.platform_owner` and requires its
subscription ID to match the app resource. No delimiter format is guessed.
Microsoft documents these [read-only platform identity variables](https://learn.microsoft.com/en-us/azure/app-service/reference-app-settings);
this is a repository guard, not an independent Azure capture.

The marker tables `bushisa_stage_identity` and `bushisa_stage_accounts` are
synthetic fixture infrastructure, not authentic legacy provenance, a consent
receipt, or production permission grants. Runtime/worker identities may SELECT
these tables but cannot write them. Existing migrations remain **001-016**;
production schema/application/worker provenance remains UNKNOWN.

## Configuration and execution order

Leave **all** `BUSHISA_STAGE_*` variables unset in ordinary development and
production. The incomplete [manifest template](../../config/staging-manifest.example.json)
has no destination, funding, operator, expiry or credential values; it cannot
enable execution. A filled manifest belongs in protected configuration outside
the managed web root, mode 0600 on POSIX, never Git. Its private root is named
`stage-<run_id>`, mode 0700, contains a matching `.bushisa-stage` marker and a
private `sessions` directory. No session files are read by preflight.

| Required configuration | Binding |
|---|---|
| `BUSHISA_STAGE_ENABLED=true`, `BUSHISA_STAGE_MANIFEST`, `BUSHISA_STAGE_MANIFEST_SHA256` | Bounded JSON (16 KiB/depth 12), supported version and independent protected SHA-256 pin. Changed/truncated/unsupported manifests refuse execution. A pin authenticates comparison with deployment configuration, not the truth of an approval claim. |
| `BUSHISA_STAGE_RUN_ID`, `BUSHISA_STAGE_ARTIFACT_SHA` | Exact run and candidate tags; actual deployed artifact/source evidence must still establish the tags' provenance. Manifest expiry is execution authorization, not extension of account/challenge/retention clocks. |
| `BUSHISA_STAGE_APP_RESOURCE_ID`, `BUSHISA_STAGE_DB_RESOURCE_ID` | Exact manifest resources. Managed mode supports separate `bushisa-stage-*` App Service/Flexible Server resources with the app's default HTTPS hostname, not slots/custom-host wildcards. Validate platform `WEBSITE_SITE_NAME`, `WEBSITE_HOSTNAME`, `WEBSITE_RESOURCE_GROUP` and absence of another slot. Known production names/host/schema are refused. |
| Explicit `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Exact target/role. Schema must be `bushisa_test_stage_*`. Managed mode also requires the existing `BUSHISA_ALLOW_REMOTE_TEST_DATABASE=true`, explicit `DB_SSL_CA` and its manifest hash. SQL UUID/principal and marker must match before mutation. No Azure DB aliases, DB_PASS or SMTP settings may leak into this environment. |
| `BUSHISA_STAGE_ROLE`, `BUSHISA_STAGE_INTENT` | `migration/migrate`, `fixture/prepare`, `runtime/serve`, `worker/dispatch`, `runner/preflight` or `runner/execute`. Five distinct `bst_*` identities, no root/admin. Use only reviewed scoped migration privileges; runtime/worker DML on app tables, SELECT-only marker access and necessary fixed-routine EXECUTE; runner SELECT only. |
| `BUSHISA_STAGE_STORAGE_NAMESPACE`, `BUSHISA_STAGE_STORAGE_ROOT`, `BUSHISA_STAGE_SESSION_HANDLER=files` | Exact private namespace/root/native-handler binding, no symlink/traversal or unsupported handler fallback. Observed production persistent-storage/Redis-extension settings prove none of this. |
| `APP_ENV=development`, `APP_DEBUG=false`, `BUSHISA_ALLOW_RUNTIME_DDL=false`, `BUSHISA_TEST_DATABASE=true`, `SESSION_COOKIE_SECURE=true` | Mandatory independent guards, not production configuration approval. Existing Q11/reset/session synthetic flags cannot be combined with this profile. |
| `MAIL_PROVIDER=fake`, `MAIL_TRANSPORT=fake`, `MAIL_FROM_ADDRESS=synthetic@example.invalid`, `STUDENT_EMAIL_DOMAIN_ALLOWLIST=students.example.invalid` | No real recipients or SMTP. Manifest allowlists exact synthetic student addresses; a pending recipient remains available for signup-purpose checks. Fresh private outbox/HMAC keys are supplied independently, not copied from production. |
| `PASSWORD_RESET_ENABLED`, three existing reset throttle settings | Off by default. Read-only preflight reports the flag; execution needs explicit stage-only enablement and explicit synthetic values. These values adopt no proposed production throttle. |
| `BUSHISA_STAGE_MANAGED_AUTHORIZATION` | Exact manifest pin plus nonempty accepted funding/destination/security/egress/storage/key-provenance references and distinct primary/backup. UNKNOWN/UNASSIGNED placeholders fail. References require real operator/reviewer evidence; synthetic strings are not sign-offs. No actual managed values have been supplied here. |

After destination/funding/coverage acceptance, the later authorized operator must:

1. Establish resource/network/CA/source identity and separate credentials;
   prevent all production dependency access and ordinary worker scheduling.
2. Bootstrap only the separately authorized disposable empty schema using the
   existing guarded helper. With the **migration** role/intent and complete
   profile, run `php bin/migrate.php --through=016` twice. Migration mode checks
   actual DB UUID/principal before SQL, before the fixture marker exists. A
   representative upgrade is a different empty synthetic schema, not a restore
   of real data. Stop on partial DDL, unknown grants or unexpected existing rows.
3. With the **fixture** role/prepare intent, privately supply
   `BUSHISA_STAGE_FIXTURE_PASSWORD` and run
   `php bin/prepare_synthetic_stage.php --prepare-new-synthetic-fixtures`.
   This action is separate from browser execution. Existing unknown/nonempty
   schemas cannot be promoted into verified synthetic targets.
4. Start only the reviewed private staging app with runtime/serve configuration.
   Leave reset issuance off until guards/identity/worker prerequisites pass.
   Worker/runner credentials are not installed in public forms or endpoints.
5. On that same bound stage application container, use runner/preflight and
   `node bin/managed_stage_driver.js --preflight`. It connects read-only and
   opens no browser or mail connection. A missing identity, marker, migration,
   funded destination or accepted coverage is BLOCKED, not a skipped PASS.
6. Only under separate managed execution authorization, set runner/execute,
   stage-only reset enablement and private `BUSHISA_STAGE_WORKER_DB_PASSWORD`
   and fixture password, then run
   `node bin/managed_stage_driver.js --execute-managed-stage`.
   The child worker's private-pipe nonce is generated in memory by the runner;
   do not invoke it with proof in shell arguments. No artifact records proof.

The local lane instead uses fictional `local-fixture://` resources, a loopback
DB and the exact reserved `stage-<run>.example.test` HTTPS origin. Its owned
loopback TLS proxy uses a single test certificate SPKI pin; managed mode never
uses that relaxation. Local mode is refused inside an App Service environment.
`--execute-local-fixture` cannot be relabelled managed execution. The driver's
local diagnostic check examines bounded owned test logs/content-free DB fields;
actual managed log/telemetry destinations remain a separate unexecuted check.

## Verification record and separate case matrix

The new SG matrix is separate from A/LB/LC/QS/QE/QM/SC/L06 and AZS01-AZS18.
Development checks against the modified audit worktree are not tests of clean
`7cc97bc`. Exact completed runs/candidate evidence are recorded below after
execution. Unexecuted managed variants remain NOT RUN.

| Cases | Requirement | Local result |
|---|---|---|
| SG-U01-U51 | Complete local profile; missing/production/staging-only flags, credential/role/resource/pin mismatch; unsafe origin; expiry/UUID format; each missing managed acceptance field; unsupported/oversized manifest; Host/Forwarded independence; preserved production origin rule | **51 PASS**, Windows PHP 8.5.11; Linux minimal-PHP/no-MySQL-driver variant recorded separately |
| SG-I01-I05b | Fresh install/repeated 001-016 migrations under separate migration identity; anchored synthetic preparation, non-resetting repeat and positive read-only connection-counter witness | **PASS**, Linux PHP 8.5.11 / MySQL 8.0.45 |
| SG-I06 variants / I07 | Seven unsafe entry profiles refuse before any new stage-role MySQL connection | **PASS**, seven variants; account-specific connection counts exclude unrelated health checks |
| SG-I08-I14 | Unlisted-recipient zero writes; purpose-separated encrypted issuance; private carrier with SMTP socket functions disabled; hash-only challenge; terminal retry/no proof; duplicate/conflicting selection zero effects | **PASS** |
| SG-I15a / I15 | Existing immutable attempt key cannot be rewritten; stale fixture mapping cannot authorize consumption | **PASS**; no immutable-key safeguard was relaxed |
| SG-I16 | Two distinct processes/connections engine-observed waiting on account lock; exactly one successful consume | **PASS** |
| SG-I19-I20 | Actual wrong server UUID prevents preparation writes; unexpected runtime DDL privilege fails closed | **PASS** |
| SG-I21-I25 | Original backoff respected, invalid member prevents the whole batch, production carrier refusal before connection, lost private result safe retry, distinct concurrent carrier connections/one delivery | **PASS** |
| SG-I17-I18 / SG-D01-D10 | Real rendered HTTPS fixture, neutral outcomes, inert GET/HEAD, CSRF, fragment removal, storage/console/URL checks, reset, two-session API/page revocation, used/manual form, ordinary new-password login, separate signup/notice purposes and owned diagnostics | **10 driver PASS**, within **33 final integration assertions PASS** |
| Managed variants / AZS01-AZS18 | Actual isolated Azure ingress/engine/grants/storage/recovery/device/operational execution | **NOT RUN / GATED** |

Local commands are `php tests/unit_staging_guard.php` (also minimal `php -n -d
extension=ctype tests/unit_staging_guard.php --expect-no-mysql`), and
`STAGE_RUN_BROWSER=true php tests/integration_staging.php`
under the existing explicit disposable guard with a loopback
`bushisa_test_stage_*` schema. The integration owns fresh synthetic credentials,
fixtures, namespace and processes; it never retargets the old loopback L06
harness. [The optional local runner image](../../tests/docker/staging.Dockerfile)
pins PHP 8.5.11 and uses MySQL 8.0 separately. Exact MySQL patch/OS/Node/browser
versions are captured from execution, not inferred from tags.

Fresh restricted-role trigger creation initially failed with real MySQL **1419**
while `log_bin_trust_function_creators=0`. No SUPER/global grant was added.
The owned loopback integration requires explicit
`BUSHISA_STAGE_TEST_ENGINE_SETUP=true` to prepare that disposable engine's trust
setting for the fixture, then restores and verifies the original value. This
fixture-only setting is not in the app/carrier/driver and is not an Azure change
or a decision that a managed server should adopt it. Actual managed parameter,
definer/role acceptance remains a prerequisite; failed/partial DDL must stop.

The [local machine record](evidence/guarded-staging-local-2026-10-20.json)
records clean implementation candidate **`a0289c1d78824dfb67fcf07b75b1a4edc87d88b6`**,
2026-10-20 **06:31:31-06:33:46 UTC** (08:31:31-08:33:46 Africa/Harare):
**33 assertions and 10 rendered driver cases PASS**. Consume PIDs 1209/1210,
connections 2766/2767 were observed waiting together with one winner; carrier
PIDs 1217/1218, connections 2773/2774 likewise produced one private delivery.
The disposable binary-log trust setting was restored and verified at its original
value 0. Pure guard **51 PASS** also ran on Windows PHP 8.5.11 and minimal Linux
PHP 8.5.11/8.4.26 with the MySQL driver absent. PDO's class can remain present;
the test asserts driver absence rather than claiming a class-removal sandbox.

A modified worktree tag does **not** attribute implementation tests to clean
audit commit `7cc97bc`.
Development run 2026-10-20 05:52:57-05:53:47 UTC (07:52:57-07:53:47
Africa/Harare) passed 32 integration assertions and 10 driver cases on Linux,
PHP 8.5.11, MySQL 8.0.45, Node 20.20.2 and Chromium 156.0.8078.4.
The clean-candidate and hosted runs supply independent current-candidate
evidence. The development run remains attributed to its modified audit worktree.

The separate [regression record](evidence/guarded-staging-regressions-2026-10-20.json)
preserves case results and unavailable variants without private payloads.
Affected Linux regression commands executed on that working tree:
`php tests/integration_l06_password_reset.php` (25 checks),
`node node_modules/@playwright/test/cli.js test --config=playwright.l06.config.js`
(6 rendered cases), `php tests/integration_q11_signup_expiry.php` (74 assertions),
`php tests/integration_verification_retention.php` (75), and
`php tests/integration_attempt_erasure.php` (58): **PASS**, plus signup/mail/
disposable-guard units. Windows PHP 8.5.11/Node 20.19.6 L06 HTTP 25 and browser
6 also passed. An initial Windows lifecycle invocation incorrectly disabled
the required synthetic HTTP barrier and timed out after 51 passing assertions;
only those lifecycle suites were rerun with the required flag: Q11 **74/74**,
Q22 **75/75**, ordinary attempt erasure **57/57** assertions passed. Linux erasure
ran 58 assertions; the unavailable Windows symlink variant remains NOT RUN as its
machine record states. New descriptor-3 carrier/managed-driver execution is
demonstrated on Linux; a Windows carrier/driver variant is **NOT RUN**. None of
these records changes historical acceptance totals.

CI adds a separate PHP 8.5/MySQL 8.0 staging-implementation job and preserves
the four existing jobs. It runs the real driver on an owned loopback HTTPS
fixture only. Failures fail the job; only sanitized JSON is uploaded. Hosted
results must name their own source SHA, PR merge checkout and run URLs; the
historical `7326716` CI is unchanged and does not certify this work.

The [hosted record](evidence/guarded-staging-hosted-ci-2026-10-20.json) verifies
**all five jobs successful in both runs** for implementation source `a0289c1`:
[feature push 39381076947](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39381076947)
and [PR 39381243193](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39381243193).
[Draft PR 7](https://github.com/Takudzwa00kyle/Bushisa/pull/7) has test merge
`f0f4bbfd9ea4c4908df58dea17c9e9b91e0c2020`, parents main `0bf311f` and
implementation `a0289c1`; its tree matches the source tree. Both staging ZIP
digests, source/checkout fields, 33 assertions, 10 driver cases and actual
consume/carrier overlap were checked. Observed hosted stage runtime is PHP
**8.5.11 / MySQL 8.0.46**; local MySQL was **8.0.45**, and Azure's patch is still
UNKNOWN. The other four jobs retain their declared PHP 8.4 line; their success
does not identify an Azure web runtime.

The following metadata/evidence checkpoint changes documentation only. Its own
exact-head CI is linked from PR 7 and the task completion record; it must not
silently relabel the implementation runs above or imply managed acceptance.

Pre-push workflow review found only `ci.yml` and `main_bushisa.yml` locally and
on remote main `0bf311fb6d6a298712fd4cd16d0722305516edd4`. Remote workflow blobs
matched that inspected tree. Azure deployment triggers only a main push or
explicit deployment-workflow dispatch; CI uses push/pull_request, with no Azure
login, deployment dependency, production feature activation or real carrier.
The dedicated branch/draft PR are the only authorized publish targets.

## Gates preserved

All **18 managed-staging AZS cases remain NOT RUN**. SC26 production
storage/topology remains **PARTIAL**. Native filesystem/session evidence is
not shared-Azure fencing or independent recovery proof. No adapter, production
purge scheduler, witnessed restore, real delivery, monitored mailbox, funded
destination or named coverage is invented. Q47 production throttles, Q40,
D10, qualified privacy/security/safety review and the full D03 feature/release
scope remain open as recorded. The next owner action is accepted funding,
destination/source identity and primary/backup/reviewer evidence needed to fill
the protected manifest, not a production reset enablement request.
