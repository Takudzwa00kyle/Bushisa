# Azure compatibility audit and isolated staging acceptance plan

Status: **PARTIAL audit; Azure configuration UNKNOWN; staging GATED / NOT RUN**.
This is documentation on `codex/azure-compatibility-staging-plan-20261019`,
based on `73267163564e2de6dc1c9de5a1354c6fd9ecdf9f`. No applicable AGENTS.md was
found in the checkout or its ancestors. Existing work and historical evidence
are preserved. This task authorizes read-only non-secret metadata inspection
and a local documentation commit, not provisioning, deployment, live database
connections, migration, cleanup, mail, or feature activation.

The [decision register](decisions.md), [owner-policy reconciliation](owner-policy-reconciliation-2026-10-15.md),
[backlog](backlog.md), [roadmap](p0-p2-status-2026-10-18.md),
[SC26 evidence](session-copy-consumer-evidence-2026-10-18.md) and
[L06 evidence](l06-browser-hosted-ci-evidence-2026-10-19.md) govern this plan.
D03's full scope remains: student verification, adult onboarding, discovery,
reciprocal/geographic matching, approved chat, confessions, safety/moderation
and account management. Compatibility acceptance cannot remove these features.
Exact historical owner acceptance times remain unavailable; capture/test times
below are not approvals. CONFIRM means owner information, VERIFY technical
evidence, and REVIEW GATE qualified acceptance.

## 1. Evidence recovered without rerunning acceptance

The [metadata capture](evidence/azure-compatibility-metadata-2026-10-19.json)
was observed at **2026-10-19 10:29:38 UTC / 12:29:38 Africa/Harare** through
authenticated, read-only GitHub API requests. No test or deployment was invoked.

| Observation | Exact attribution | Meaning / limitation |
|---|---|---|
| Tested source | `73267163564e2de6dc1c9de5a1354c6fd9ecdf9f` | Preserved baseline; this audit adds documentation only. |
| Feature-push CI | [39224866889](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39224866889), source/checkout `7326716` | All four jobs SUCCESS. |
| PR CI | [39224874180](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39224874180), source `7326716`, merge checkout `3dd131875aeea548bb4adc202d907febbb12258b` | All four jobs SUCCESS; base `0bf311fb6d6a298712fd4cd16d0722305516edd4`. |
| Draft PR | [6](https://github.com/Takudzwa00kyle/Bushisa/pull/6), L06 source branch, open/unmerged | A synthetic merge checkout is not a merge or deployed release. |
| Existing test evidence | 25 L06 HTTP cases, six L06 rendered cases, 10 existing rendered regressions; lifecycle/import/session suites in their separate job | Confirmed CI evidence, not Azure execution. Hosted profile reported PHP 8.4.26, MySQL 8.0.46, Node 20.20.2, Playwright 1.64.0, Chromium 156.0.8078.4. |
| Last returned main deployment-workflow run | [38230207825](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38230207825), push at `0bf311f`, SUCCESS, created 12 October | GitHub workflow metadata only. Current Azure artifact/configuration and other deployment routes remain UNKNOWN. |

The committed [L06 implementation artifact summary](evidence/l06-hosted-ci-2026-10-19.json)
deliberately remains attributed to earlier implementation candidate `e7501fe`
and its `82f692d` PR merge. The final `7326716` attribution above does not
relabel those earlier runs. Existing A/LB/LC/QS/QE/QM/SC matrices are unchanged.

Azure capture could not execute: `az` and `Az.Accounts` were unavailable, and
the browser/node-repl runtime failed before inventory with
`failed to write kernel assets: The system cannot find the path specified.
(os error 3)`, including a browser reset/retry and a post-interruption recheck
(time recorded separately in the metadata JSON). No Azure authenticated session
or resource state was observed. No auth-cache, secret, publishing profile,
connection string, application-settings export, user record or session file was
read. Resource leads below are historical operator evidence, not observations.

## 2. Repository requirements versus actual Azure

Every Azure-state entry is **UNKNOWN** until the bounded capture in section 3.
Repository behavior is established by source inspection, not execution here.

| ID / area | Concrete repository requirement and behavior | Azure lead / missing evidence and consequence |
|---|---|---|
| AC01 PHP | [composer.json](../../composer.json) permits PHP `^8.2`; declares PDO, pdo_mysql, OpenSSL and PHPMailer. Calls also need `session`, `ctype`, `mbstring`, cURL and `fileinfo`. [upload_validator.php](../../php/upload_validator.php) conditionally uses GD/JPEG re-encoding; absence silently skips JPEG stripping, so extension presence alone does not certify media privacy. CI uses PHP 8.4; deployment workflow builds with 8.5. | PHP 8.5.6 was operator-reported on 11 October. Actual CLI/FPM patch, OS, extension/build parity and custom INI UNKNOWN. Capture then accept an exact Q39 matrix; no guessed production patch or automatic upgrade. |
| AC02 MySQL/schema | PDO native prepares, InnoDB transactions/locks, UTC session, microsecond timestamps, JSON and triggers/routines through [migration 016](../../database/migrations/016_password_reset.sql). [bin/migrate.php](../../bin/migrate.php) owns additive migration markers and `--through=NNN`; MySQL DDL is not an atomic whole-release transaction. | Historical lead `bushi-server.mysql.database.azure.com`, database `bushi-database`. Current service type/major/patch, collation, SQL modes, auth and applied versions UNKNOWN. Neither 005 nor 016 is asserted production-applied. Local MySQL 9.6 is not the selected Azure target. |
| AC03 privileges/TLS | [database_settings.php](../../config/database_settings.php) resolves `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`/`DB_PASS`, then Azure aliases; production rejects missing config/root/empty password. `DB_SSL_CA` or the system CA is required for production/Azure hosts. [config/db.php](../../config/db.php) enables certificate verification, requires a nonempty TLS cipher, sets `+00:00`. Runtime needs exact DML and fixed health-procedure EXECUTE; migration identity needs reviewed DDL/TRIGGER/routine privileges/definer visibility. | Historical cipher `TLS_AES_256_GCM_SHA384` is not a present test. Actual private network, firewall, CA/hostname validation, runtime/migration/test identities and SQL grants UNKNOWN. Azure IAM is not proof of SQL privileges. Do not enable trust/superuser flags or broaden runtime grants to pass migration tests. |
| AC04 deploy | [main_bushisa.yml](../../.github/workflows/main_bushisa.yml) deploys app `Bushisa`, slot `Production`, on **main push or workflow_dispatch**; CI is a separate push/PR workflow. Deployment has no required `needs: CI` gate and no migration step. Its PHP 8.5 build is not an effective Azure runtime setting. | Main is `0bf311f`; tested L06 branch is unmerged. Actual Deployment Center source, current artifact SHA, additional manual routes, environment approvals and branch protection UNKNOWN. Do not dispatch the production workflow to create staging. |
| AC05 web root/startup | PHP entry points are at repository root; WebJob assumes `/home/site/wwwroot`. [.htaccess](../../.htaccess) contains Apache rewrites/HTTPS rules, not an NGINX protection configuration. Repository includes database SQL, tests, config, logs and `.tmp` paths requiring denial or exclusion from a web artifact. | Current startup/NGINX root, package contents, hidden/static-path protections and HTTPS/proxy handling UNKNOWN. A Linux NGINX deployment cannot be certified from `.htaccess`. No live sensitive-path requests are made in this audit. |
| AC06 sessions | [session_manager.php](../../php/session_manager.php) uses native configured PHP sessions unless the guarded synthetic handler is requested. Native cookies are HttpOnly/Strict with 1,800-second lifetime/activity refresh, including protected-request polling; `SESSION_COOKIE_SECURE` can override HTTPS detection. `require_login` reloads account status/session version. Q40's DB store, role-specific idle/absolute values and Lax redesign remain proposed. | Handler, save path, FPM settings, locking/GC, restart persistence, instances, proxy and cookie behavior UNKNOWN. `/home` persistence is not evidence of this session mapping or its fencing. Logical session-version revocation is not physical deletion of session copies. |
| AC07 SC26/storage fence | [managed_session.php](../../php/managed_session.php), [session_copy_store.php](../../php/session_copy_store.php) and [consumer](../../php/session_copy_cleanup.php) support only isolated `.tmp/sf-storage-*` synthetic roots, exact generations/namespaces and a non-expiring OS process fence. Stalled A keeps B `busy`; after A resumes/exits, retry can complete. | Local physical-I/O subcase PASS; SC26 overall remains PARTIAL. Azure Files/Blob/shared-host lock semantics, cross-instance topology, crash recovery and supported production handler UNKNOWN. No lease-only takeover or forced lock break is approved/proved. |
| AC08 media/logs | [upload_validator.php](../../php/upload_validator.php) writes `uploads/<user>/photo_*`; complete private media/derivative/version mapping is absent. [logger.php](../../php/logger.php) writes `logs/app.log`. Q11 fixture deletion is not a production media adapter. | Actual mount/root, durability, private access, versions/backups, object index, CDN/caches, diagnostics destinations and retention UNKNOWN. See L03/L04/L05/P1-03; no general media-erasure completion claim. |
| AC09 scheduling | [run.sh](../../deploy/mail-webjob/run.sh) uses `set -euo pipefail`, `cd /home/site/wwwroot`, cleanup then dispatch; [settings.job](../../deploy/mail-webjob/settings.job) says `0 * * * * *`. A cleanup failure stops subsequent dispatch. [cleanup_policy.php](../../bin/cleanup_policy.php) handles bounded signup/report/pair/reset paths; separate Q22/attempt/session consumers are not all invoked by that script. | Historical `bushisa-mail`/Always On/storage/Kudu reports only. Current WebJobs support, installed script/version, execution cadence, missed schedules, quotas/latency and alerts UNKNOWN. A schedule file is not scheduler evidence or complete cleanup coverage. |
| AC10 recovery/holds | L03a authority and independent journal integration remain unavailable outside guarded fixtures; Q11 attempt erasure and Q22 consumers require recovery adapters; synthetic session consumer cannot be enabled on an arbitrary remote store. Minimal independent protection must survive outside the restore set and be replayed before reads/dispatch. | Actual independent journal, key custody, durable acknowledged watermark, copy inventory/expiry and witnessed restore UNKNOWN / NOT RUN. Flags or SQL receipts do not establish durability. Scoped holds preserve specified evidence only and never renew ordinary clocks. |
| AC11 mail/reset | `MAIL_PROVIDER`, `MAIL_TRANSPORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `MAIL_REPLY_TO`, `MAIL_OUTBOX_KEY`; SMTP host/port/encryption/username/password/timeout; signup HMAC/code settings. Reset additionally uses `APP_BASE_URL`, `PASSWORD_RESET_ENABLED` and the three throttle variables below. Proof validity is fixed at 900s; useful queue ceiling 300s and expiry minus 60s. | Historical ACS `bushisa-email` / `bushisa-communication`, sender `DoNotReply@bushisa.me`, United States ACS data-location lead. Actual linked resources, sender, secret-reference resolution, quotas, processing, inbox/headers and monitored contact UNKNOWN. No secret values or real mail collected. |
| AC12 tier/funding | Isolation, separate configuration/data/mail/storage and deployment controls are required. No staging resource or billable monitor is created by source. | Historical Basic Linux / South Africa North / Azure for Students, group `takudzwaportfolio`. Actual tier/instance count/credit/budget/region UNKNOWN. **No slot selected.** If Basic is confirmed, price a separate staging app; slots require a supported tier and still do not prove data isolation. |

Microsoft documents [PHP/NGINX configuration and persisted `/home`](https://learn.microsoft.com/en-us/azure/app-service/configure-language-php),
[slot tier requirements](https://learn.microsoft.com/en-us/azure/app-service/deploy-staging-slots)
and [WebJobs platform limits](https://learn.microsoft.com/en-us/azure/app-service/overview-webjobs).
These platform descriptions are not Bushisa resource observations; persisted
storage says nothing about this handler's cross-host lock/conditional-delete safety.

## 3. Safe owner metadata collection, without secret retrieval

These are **unexecuted read-only instructions** for an already-authenticated
operator. Use the correct subscription explicitly; fill resource identifiers
from the portal, not guesses. Keep full tenant/resource/principal IDs in the
private operations register. Capture UTC time and Africa/Harare display, actor,
resource, command/portal blade, result and any redaction. Null/denied results
remain UNKNOWN. Do not install tools, create Cloud Shell storage, authenticate
on someone else's behalf or request new access as an implied part of this audit.

Portal sequence: open **App Services > Bushisa > Overview / Properties**, record
OS, region, plan and current source/artifact information; follow the actual plan
to its tier/instance count. In **Configuration / General settings / Path mappings**,
record stack/Always On/TLS/root and mount type/target only. Do not reveal storage
keys, startup-embedded credentials or all environment variables. In **Deployment
Center**, record repository/branch and deployment ID/time; do not Sync/Deploy.
In **WebJobs**, record job name/type/schedule/status and code-free latest-run
time/result; do not Run or download logs. Open the actual MySQL resource's
**Overview, Networking, Server parameters, Backups** for metadata only. Do not
open database query tools. For storage use **Configuration / Networking / Data
protection**, not containers/files. For monitoring record destinations/retention,
not telemetry records. For ACS record region/data location, linked domain and
sender verification status; never Keys/connection strings or Send/Test actions.

Equivalent CLI metadata examples (PowerShell, operator fills these values):

```powershell
$auditSubscription = '<verified subscription ID>'
$auditGroup = '<verified app resource group>'
$auditApp = 'Bushisa' # historical lead; verify in Overview first
$auditDbGroup = '<verified MySQL resource group>'
$auditDbServer = '<verified flexible-server name>'
az webapp show --subscription $auditSubscription -g $auditGroup -n $auditApp --query '{name:name,kind:kind,state:state,location:location,serverFarmId:serverFarmId,httpsOnly:httpsOnly}' -o json --only-show-errors
az webapp config show --subscription $auditSubscription -g $auditGroup -n $auditApp --query '{linuxFxVersion:linuxFxVersion,phpVersion:phpVersion,alwaysOn:alwaysOn,numberOfWorkers:numberOfWorkers,minTlsVersion:minTlsVersion,ftpsState:ftpsState}' -o json --only-show-errors
az appservice plan show --subscription $auditSubscription -g '<verified plan group>' -n '<verified plan name>' --query '{sku:sku.name,tier:sku.tier,capacity:sku.capacity,location:location,reserved:reserved}' -o json --only-show-errors
az webapp deployment source show --subscription $auditSubscription -g $auditGroup -n $auditApp --query '{branch:branch,isManualIntegration:isManualIntegration}' -o json --only-show-errors
az mysql flexible-server show --subscription $auditSubscription -g $auditDbGroup -n $auditDbServer --query '{version:version,location:location,sku:sku,network:network,backup:backup,storage:storage,highAvailability:highAvailability}' -o json --only-show-errors
az mysql flexible-server parameter show --subscription $auditSubscription -g $auditDbGroup --server-name $auditDbServer --name require_secure_transport --query '{name:name,value:value,source:source}' -o json --only-show-errors
az mysql flexible-server parameter show --subscription $auditSubscription -g $auditDbGroup --server-name $auditDbServer --name tls_version --query '{name:name,value:value,source:source}' -o json --only-show-errors
```

References: [web app metadata](https://learn.microsoft.com/en-us/cli/azure/webapp),
[web configuration](https://learn.microsoft.com/en-us/cli/azure/webapp/config),
[deployment-source metadata](https://learn.microsoft.com/en-us/cli/azure/webapp/deployment/source)
and [MySQL metadata](https://learn.microsoft.com/en-us/cli/azure/mysql/flexible-server).
Run the parameter-show form separately for `time_zone`, `sql_mode`,
`lower_case_table_names`, `innodb_lock_wait_timeout`,
`log_bin_trust_function_creators` where exposed; do not change any value.
The control-plane server version may be only a major line; exact engine patch,
connection cipher/authentication and SQL grants remain unverified without a
separately authorized technical capture. No production SQL command is part of
this plan's collection phase.

For a verified storage account, metadata-only `az storage account show` may
project `location`, `sku.name`, `minimumTlsVersion`, `allowBlobPublicAccess`,
`publicNetworkAccess`; `az storage account blob-service-properties show` may
project versioning and delete/container retention policies. For a known resource,
`az monitor diagnostic-settings list --resource <verified-resource-id>` is
control-plane configuration, not a log query; retain only destination IDs,
category names and retention settings. Follow the actual workspace to capture
its configured table minima/retention. [Storage CLI](https://learn.microsoft.com/en-us/cli/azure/storage/account),
[Blob service properties](https://learn.microsoft.com/en-us/cli/azure/storage/account/blob-service-properties)
and [diagnostic metadata](https://learn.microsoft.com/en-us/cli/azure/monitor/diagnostic-settings).
Do not list blobs, read logs, obtain account keys or inspect webapp storage-mount
credential responses.

```powershell
az storage account show --subscription $auditSubscription -g '<verified storage group>' -n '<verified storage account>' --query '{name:name,location:location,sku:sku.name,minimumTlsVersion:minimumTlsVersion,allowBlobPublicAccess:allowBlobPublicAccess,publicNetworkAccess:publicNetworkAccess}' -o json --only-show-errors
az storage account blob-service-properties show --subscription $auditSubscription -g '<verified storage group>' --account-name '<verified storage account>' --query '{isVersioningEnabled:isVersioningEnabled,deleteRetentionPolicy:deleteRetentionPolicy,containerDeleteRetentionPolicy:containerDeleteRetentionPolicy,restorePolicy:restorePolicy}' -o json --only-show-errors
az monitor diagnostic-settings show --subscription $auditSubscription --resource '<verified app/DB/storage resource ID>' --name '<verified diagnostic setting name from portal>' --query '{name:name,workspaceId:workspaceId,storageAccountId:storageAccountId,logs:logs,metrics:metrics}' -o json --only-show-errors
az monitor log-analytics workspace show --subscription $auditSubscription -g '<verified workspace group>' -n '<verified workspace name>' --query '{location:location,retentionInDays:retentionInDays,sku:sku.name}' -o json --only-show-errors
```

An operator with an already-authorized runtime console can run the following
standalone metadata-only PHP command; it loads no application/DB bootstrap:

```bash
php -r 'echo json_encode(["php"=>PHP_VERSION,"sapi"=>PHP_SAPI,"os"=>PHP_OS_FAMILY,"ini_file"=>php_ini_loaded_file(),"extensions"=>array_values(array_filter(["PDO","pdo_mysql","openssl","curl","mbstring","fileinfo","ctype","session","gd"],"extension_loaded")),"session_handler"=>ini_get("session.save_handler"),"cookie_secure"=>ini_get("session.cookie_secure"),"cookie_httponly"=>ini_get("session.cookie_httponly"),"cookie_samesite"=>ini_get("session.cookie_samesite")],JSON_PRETTY_PRINT),PHP_EOL;'
```

CLI output does not prove the web/FPM SAPI configuration. Record effective
web-handler parity through an approved bounded diagnostic design, not a public
`phpinfo()` page. For `files`, privately confirm the session root/mount/ACL and
GC metadata without reading files; for other handlers never print
`session.save_path` blindly because a DSN can contain credentials. No `env`,
`printenv`, `php -i`, configuration-file dumps or secret-bearing command lines.

Non-secret flags still needed: `APP_ENV`, `APP_DEBUG`,
`BUSHISA_ALLOW_RUNTIME_DDL`, `APP_BASE_URL`, `PASSWORD_RESET_ENABLED`, the three
reset throttle names, `SESSION_COOKIE_SECURE`, `WEBSITES_ENABLE_APP_SERVICE_STORAGE`,
`WEBSITE_RUN_FROM_PACKAGE`, `SCM_DO_BUILD_DURING_DEPLOYMENT`,
`WEBSITE_SKIP_RUNNING_KUDUAGENT`, `WEBJOBS_STOPPED` and scheduler/consumer flags.
The operator supplies allowlisted booleans/statuses at source, redacting any
URL that embeds credentials. **Do not call appsettings/connection-string list
even with `--query`**: client-side projection still fetches the secret-bearing
endpoint. Likewise no publishing-profile, Key Vault secret-get or storage-key
operation. Record key/reference **presence and resolution status only**.

## 4. Two staging blockers and proposed implementation only

| Blocker | Current behavior and protection | Why managed staging cannot pass now | Smallest proposed adaptation / required tests |
|---|---|---|---|
| STG-I01 trusted origin | [password_reset.php](../../php/password_reset.php) `bushisa_reset_trusted_origin()` uses configured `APP_BASE_URL`, rejects user/pass/query/fragment, requires HTTPS and host exactly `bushisa.me` with absent/443 port outside synthetic mode. It never builds links from Host/redirect input. Synthetic relaxation needs `BUSHISA_PASSWORD_RESET_SYNTHETIC` and **all** `bushisa_q11_synthetic_enabled()` guards, including loopback DB host, `bushisa_test_q11_*`, development/debug-off, runtime-DDL-off, fake provider/transport and explicit synthetic flag. | An Azure staging hostname or `staging.bushisa.me` is rejected. Remote managed MySQL cannot satisfy that loopback cohort. Request UX stays generic and writes no reset issuance. Setting the staging URL to the production origin, tunnelling a live database through localhost, or setting flags to impersonate a fixture is not an acceptable workaround. | A separately reviewed staging-only origin/destination profile, exact HTTPS hostname and explicit dedicated synthetic resource/account-generation proof. Preserve the default production `bushisa.me` rule, no wildcard hosts or request-selected origin. Reject missing/production/wrong resource, unknown host, userinfo/query/fragment, bad port/scheme and spoofed Host/redirect; prove valid staging links remain on the approved staging origin, GET/HEAD remain inert and existing 15m/supersession/consume rules unchanged. No implementation in this audit. |
| STG-I02 fake carrier | [bin/dispatch_mail.php](../../bin/dispatch_mail.php) rejects `MAIL_PROVIDER != smtp` **before DB load** and constructs an SMTP mailer. [signup_verification.php](../../php/signup_verification.php) rejects fake mail in production. Existing service [mail_delivery.php](../../php/mail_delivery.php) `bushisa_dispatch_mail(..., $transport)` supports injected test callbacks; [reset jobs](../../php/password_reset_mail_jobs.php) refuse sendable fake jobs without an explicit carrier. | Running the production CLI with fake settings fails; fake mode alone does not deliver/reset a job. Supplying real SMTP would violate this acceptance plan and add real-mail dependencies. Existing guarded fixture callbacks are local-only, not a managed-staging CLI/worker adapter. | Separate CLI-only synthetic carrier entry point/profile with pre-connection immutable destination checks, synthetic-recipient/account allowlist and scoped in-memory receipt capture. Reuse service state machines, encryption, claim/version, deadlines and cleanup. Keep production CLI SMTP-only and production fake-provider denial. Test default denial, wrong target/recipient/purpose, no SMTP/network access, retries/cancellation/in-flight and late-result handling, password-change notices, one consume winner and secret-free output. A missing carrier must fail, never fall back to SMTP. Entry point does not yet exist. |

Implement these together as one bounded proposed engineering task after the
destination contract/security review; no production throttle decision is needed
to use explicitly labelled synthetic limits. Adapting these two surfaces alone
does not provide managed-session cleanup, independent recovery or production
readiness. Do not weaken local-harness guards to retarget destructive suites.

## 5. Isolated staging procedure (not executed)

### 5.1 Preflight and destination manifest

Before any later execution/provisioning authorization, engineering and the
accepted operator record the following privately. Values are **UNASSIGNED**;
placeholder names are not resources, accounts, namespaces or appointments.

| Target | Required concrete binding and isolation |
|---|---|
| Deployment | Exact candidate SHA/artifact digest; separate private staging app/resource ID, hostname, region, plan/tier and approved funding. Never app `Bushisa`/slot `Production`, main push, production workflow dispatch or slot swap. Inspect the real tier first. A separate app is the fallback proposal if Basic is confirmed; price/approve it before provisioning. |
| Database | Dedicated synthetic-only server/resource ID, endpoint, TLS CA and separate disposable fresh/upgrade schema names. No production snapshot, restore, replica, tunnel or credentials. Record resolved resource/engine identity and deny network reachability to production dependencies; a test-looking DB name alone is insufficient. |
| Identities | Separate stage fixture/bootstrap identity, controlled migration identity, restricted app runtime identity and worker identity; private credentials/reference names owned by security. Runtime cannot create/drop schema or change unrelated DBs; never use the production role/admin or grant broad access to make tests pass. |
| Mail | `MAIL_PROVIDER=fake`, `MAIL_TRANSPORT=fake`; no SMTP credentials/mailer or outbound SMTP permission. `APP_ENV=development`, `APP_DEBUG=false`, `BUSHISA_ALLOW_RUNTIME_DDL=false` are lab conditions, not proof of production-mode behavior. Reset stays disabled in the app until STG-I01/I02 and stage authorization pass. |
| Proof/keys | Fresh stage-only `MAIL_OUTBOX_KEY`, `SIGNUP_CODE_HMAC_KEY`, report context keys and any synthetic-session key, created/held privately. Never reuse production keys or record values, proofs, cookies or message bodies in artifacts. |
| Sessions/storage | Explicit stage handler/root/mount, instance coverage and private media/log destinations. Separate per-run synthetic `.tmp` roots and `ss_<random32hex>` namespace for the existing local handler only, matching its marker/root rules. No production session mount, inferred legacy mapping, shared upload folder or blind directory deletion. |
| Workers | Exact stage resource/database/root/namespace/entry point in every job manifest; startup gates and schedules disabled initially (`WEBJOBS_STOPPED` where supported, no uploaded auto-start job). Only manual bounded workers after preflight; no production scheduler targets. |
| Recovery/observability | Separate stage journal/control-plane/log namespaces outside any restored set; test alert recipient/coverage accepted, diagnostics allowlist and safe result retention. Existing synthetic journal is an interface fixture, not this durable provider. |

Fail preflight on missing/ambiguous target identity, production reference,
unreviewed tier/spend, unknown egress, shared storage/key, unsupported adapter,
incorrect artifact or absent accepted operator. No stage can be called isolated
merely because a slot or database has a staging label.

### 5.2 Lane H: existing loopback synthetic harness

This is an isolated runner procedure, potentially hosted on separately approved
Azure test compute. It still tests loopback PHP/MySQL and synthetic filesystem
adapters. It does **not** test App Service ingress, managed MySQL/TLS, production
session storage or real mail. Provisioning/running it is outside this audit.
Use a fresh environment with only the runner's disposable credentials supplied
privately, not inherited production settings. No proxy/tunnel to production.

```bash
export APP_ENV=development APP_DEBUG=false BUSHISA_TEST_DATABASE=true
export BUSHISA_ALLOW_RUNTIME_DDL=false DB_HOST=127.0.0.1 DB_PORT=3306
export DB_USER='<dedicated disposable fixture user>'
export MAIL_PROVIDER=fake MAIL_TRANSPORT=fake
export BUSHISA_Q11_SYNTHETIC=true BUSHISA_Q11_QUEUE_FAKE=true
export BUSHISA_PASSWORD_RESET_SYNTHETIC=true PASSWORD_RESET_ENABLED=true
export PASSWORD_RESET_RESEND_COOLDOWN_SECONDS=1
export PASSWORD_RESET_MAX_REQUESTS_PER_HOUR=100 PASSWORD_RESET_MAX_REQUESTS_PER_DAY=200
export DB_NAME=bushisa_test_q11_stage_l06_http L06_CI_REPORT=true
php tests/integration_l06_password_reset.php
export DB_NAME=bushisa_test_q11_stage_l06_browser
node node_modules/@playwright/test/cli.js test --config=playwright.l06.config.js
```

Prerequisites: verified candidate checkout, Composer/npm lockfile installs,
compatible PHP/extensions, MySQL client, Node/Playwright/Chromium, local owned
MySQL and private `.tmp`/session storage. `DB_PASSWORD` is injected privately;
never put it in shell history or command arguments. The HTTP harness deliberately
uses its own `1/30/60` synthetic throttles; rendered CI profile uses `1/100/200`.
Neither approves Q47 production limits. Expected results: existing 25 HTTP and
six browser cases PASS, separate safe JSON with exact source/checkout versions,
fresh 015-to-016 upgrade/repeat, two distinct consumer processes/connections
with one winner, two genuine pre-authenticated sessions denied after reset.
Any failure stops that lane; do not change expected status to get a green run.

Under each suite's exact flags/database prefix from [CI](../../.github/workflows/ci.yml)
and its tracked evidence, use separate disposable targets for:
`tests/integration_q11_signup_expiry.php`, `tests/integration_attempt_erasure.php`,
`tests/integration_verification_retention.php`, `tests/integration_session_fence.php`
and `tests/integration_session_copy_cleanup.php`. Consumer flags are explicitly
`BUSHISA_ATTEMPT_ERASURE_ENABLED`/`BUSHISA_ATTEMPT_ERASURE_SYNTHETIC`,
`BUSHISA_Q22_CLEANUP_ENABLED`/`BUSHISA_Q22_SYNTHETIC`,
`BUSHISA_SESSION_SYNTHETIC`/`BUSHISA_SESSION_CLEANUP_ENABLED`, as applicable;
fixture entrypoints establish their own private roots/keys. Do not run bare
consumer CLIs against managed staging to substitute for missing adapters.
For scoped evidence interfaces, `tests/integration_l03a_lifecycle.php` needs its
separate loopback `bushisa_test_l03a_*` profile and journal/authority fixtures.
Existing HTTP overlap specs require distinct Linux workers, recorded barrier
arrivals/connections and the controlled scheduling helper. An unavailable
variant remains NOT RUN; a synthetic hold/MFA fixture is not a staffed authority.

### 5.3 Lane M: actual isolated App Service + managed MySQL

This lane remains **GATED** by section 3 capture, the target manifest and
STG-I01/I02 for reset. It needs separate execution authorization; this document
contains no deploy/provision command. The staging artifact must be pinned and
checked independently of the main-only production workflow. Keep automatic
workers and reset issuance off during install/upgrade. Synthetic data only.

1. **Runtime and network:** capture CLI and web/FPM versions/extensions, startup
   root, CA identity and dedicated endpoints. From the stage runtime identity
   only, verify TLS/hostname, engine patch/auth, UTC/microseconds/collation/SQL
   modes, locks/transactions, health-procedure visibility and negative grants.
   Stage-only metadata SQL may select `VERSION()`, `CURRENT_USER()`,
   `DATABASE()`, `CONNECTION_ID()`, `@@session.time_zone`, `@@sql_mode` and
   `SHOW SESSION STATUS LIKE 'Ssl_cipher'`; never run it on production here.
   Redact account names in public artifacts. No CA bypass or network-rule fix
   is part of acceptance; stop and propose a reviewed change if incompatible.
2. **Fresh schema:** with the dedicated bootstrap/migration identity and an
   approved disposable schema on the isolated managed server, use the guarded
   bootstrap and migrator below. The remote-test opt-in is for the shared
   bootstrap guard only; it does not relax any L06/Q11/session local-only guard.
   No `mysql < database/bushisa.sql` shortcut: the helper rewrites/validates the
   base schema's database target. It can create/drop a database, so must never
   inherit the app or production credential environment.

   ```bash
   # Only inside the validated Lane M bootstrap environment, credentials private.
   # These manifest variables are filled/verified by the accepted stage operator.
   test "${stageDestinationApproved:?validated stage manifest required}" = yes || exit 1
   export APP_ENV=development APP_DEBUG=false BUSHISA_ALLOW_RUNTIME_DDL=false
   export BUSHISA_TEST_DATABASE=true MAIL_PROVIDER=fake MAIL_TRANSPORT=fake
   export PASSWORD_RESET_ENABLED=false
   export DB_HOST="${stageDbHost:?stage-only managed endpoint required}"
   export DB_PORT="${stageDbPort:?verified stage port required}"
   export DB_USER="${stageBootstrapUser:?stage-only bootstrap identity required}"
   export DB_SSL_CA="${stageCaPath:?verified readable CA path required}"
   test -n "${DB_PASSWORD:?private stage-only credential must already be injected}"
   export BUSHISA_ALLOW_REMOTE_TEST_DATABASE=true
   export DB_NAME=bushisa_test_stage_fresh
   php tests/bootstrap_database.php
   php bin/migrate.php --through=016
   php bin/migrate.php --through=016
   ```

   Expected: all 001-016 markers, exact schema/health routines and no legacy
   certification; second run skips completed migrations. Recheck using the
   restricted runtime identity, not the fixture identity. Migration errors,
   unknown existing routines/definers, unexpected cascades or partial DDL stop
   progression; inspect the precise state before a safe retry. Do not assume
   rolling back PHP reverses schema changes or enable global trust to succeed.
3. **Representative upgrade:** use a **different** stage-only disposable schema,
   set `DB_NAME=bushisa_test_stage_upgrade`, bootstrap it, run
   `php bin/migrate.php --through=015`, then create synthetic
   pending/finished accounts through real stage routes as available. Record
   original creation/first-approval, generation and authorization versions and
   safe row-count/digest projections. Stage-only fixture preparation for any
   additional legacy/closed/held state must be reviewed and labelled synthetic;
   never invent authentic historical events or missing production anchors.
   Run `php bin/migrate.php --through=016` twice and compare the original
   projections, constraints, nullable legacy mappings and new empty reset stores.
   Creating representative grant/hold fixtures is gated by the supported
   adapter/authority, not permission to bypass it. No production dump is used.
4. **Ingress/static paths:** from the private stage test runner, make bounded
   requests to `/register.php`, `/verify_email.php`, `/login.php`,
   `/onboarding.php`, `/discover.php`, `/forgot_password.php`,
   `/reset_password.php` and required assets. With fake transport, never use the
   SMTP-only CLI. Test `/.env`, `/.git/config`, `/logs/`, `/.tmp/`,
   `/database/bushisa.sql`, `/tests/`, `/config/` and CLI-only `/bin/` endpoints
   for 403/404 without retaining response bodies. If static data is exposed,
   stop. Do not probe these paths on production or publish `phpinfo()`.
   Check effective Secure/HttpOnly/current Strict cookies and the actual trusted
   HTTPS ingress behavior; do not silently substitute proposed Q40 settings.
5. **Identity/reset:** after STG-I01/I02, stage-only issuance authorization and
   synthetic recipient setup, run real ingress forms/APIs. Cover entered signup
   code, first approval once, adult/onboarding restrictions, known/unknown/
   ineligible reset neutrality, fragment clearing, manual/no-JS flow, no-store/
   referrer protections, preview-safe GET/HEAD, CSRF, invalid/expired/used/
   superseded/wrong-purpose/cross-account proof, delivery failures and no
   automatic login. Two authentic browser sessions must have API 200 before
   reset; afterward `/api/notifications.php` must return 401 and protected pages
   redirect to sign-in, while ordinary new-password login succeeds. Preserve
   account bans/deletion, approved match/chat rules and missing-history denial.
   Existing local tests hardcode loopback URLs and fixture guards: a separate
   guarded ingress/managed-DB driver is required; do not retarget them by flags.
   Disable recovery traces/screenshots/video/DOM or POST capture and redact
   before diagnostic transmission, not after storing a token-bearing artifact.
6. **Races and lifecycles:** use owned stage workers/connections and scoped
   synthetic barriers after adapter review. Prove actual overlap for one-use
   consume, expiry versus verify/onboarding, resend versus cancellation/dispatch,
   claimed/in-flight/late results, and cleanup versus newer generations/holds.
   Denial must occur immediately at the approved boundary, independently of
   physical cleanup. Cleanup cannot reset anchors, erase valid holds, damage
   newer sessions or call unknown delivery state terminal. Check bounded claims,
   crashes, idempotent retries, receipts and overdue signals without bodies.
   Missing operational recovery/storage/authority adapters must refuse and keep
   obligations pending; that refusal is not successful erasure acceptance.
7. **Storage/fencing/recovery:** inventory each actual stage mount/object version,
   handler, instance and copy. For a proposed supported provider, repeat SC26's
   physical-I/O-boundary stall with two real workers after preliminary checks,
   lease expiry and SQL-connection loss. Record object, manifest and receipt
   state before/after stalled A resumes. Without a demonstrated provider fence,
   allow no lease-only reassignment/forced break; retain a pending obligation
   and actionable busy diagnostic. Local `flock`/unlink evidence is insufficient.
   Independent protection must be outside the restored set, replay the latest
   deletion/block/consent version and expiry before reads/mail, and preserve
   acknowledged actions. Interface fixtures do not satisfy this. An actual
   isolated synthetic restore needs separate authorization and named witnesses;
   it must not serve users, send mail or touch production. Q26 rehearsal copies
   expire within 72h or an earlier source deadline. Copy limits (35d DB/7d media)
   remain proposals; compute recovery retention from actual latest copy expiry
   plus 7d and live protective needs. Conditional `7+35+7=49d` is not universal.
8. **Closeout:** record exact SHA/image/package/engine, identities by role, target
   manifest, per-case command/result, PIDs/connections, immutable timestamps,
   safe counters and reviewer/witness IDs. Verify stage workers stopped, issuance
   disabled and synthetic storage disposal under its approved run plan. Never
   infer a production go/no-go, migration application, restore success or whole
   P0 exit from a stage subset. Code rollback uses a compatible approved artifact;
   destructive schema reversal is not assumed.

## 6. Planned acceptance matrix (separate from prior matrices)

All **AZS01-AZS18 are NOT RUN** in this task. GATED means a prerequisite, not a
passing refusal test or an execution result. Attach separate Lane H/M evidence.

| Case | Target and required result | Current prerequisite / role |
|---|---|---|
| AZS01 | Immutable stage destination/credential/network/storage manifest proves isolation before any write | GATED: actual resources/funding/accepted operator; engineering + security |
| AZS02 | CLI and FPM runtime/extensions/artifact match accepted Q39 matrix | UNKNOWN capture; engineering/security + Takudzwa acceptance |
| AZS03 | Managed TLS/auth/UTC/microsecond/collation/lock behavior and restrictive runtime grants | GATED: stage DB/identities; DB/security reviewer |
| AZS04 | Fresh 001-016 and repeat run; runtime cannot perform migration DDL | GATED: dedicated bootstrap/migration target; engineering |
| AZS05 | Representative 015-to-016 upgrade preserves original rows/anchors/generations; repeat safe after reviewed partial failure | GATED: reviewed synthetic fixture/projection; engineering |
| AZS06 | Private ingress routes/assets work; hidden/config/SQL/test/log/CLI paths denied | GATED: stage artifact/root/NGINX configuration; security |
| AZS07 | Signup/email approval/adult/onboarding/replacement clocks stay immutable and restricted | GATED: fake carrier/ingress driver; engineering |
| AZS08 | Reset origin exact stage allowlist/default production denial and no Host/redirect override | GATED: proposed STG-I01; security + engineering |
| AZS09 | Fake carrier cannot reach SMTP; purpose/envelope/deadline/cancellation/late-result behavior correct | GATED: proposed STG-I02; engineering/email operations |
| AZS10 | Six rendered L06 behaviors through actual ingress, no proof in URLs/storage/diagnostics/telemetry | GATED: STG-I01/I02 + guarded driver; engineering/manual witnesses |
| AZS11 | 25 HTTP behavior families on supported managed-stage driver, including negative authorization | GATED: driver/fixtures; engineering |
| AZS12 | Two real existing sessions lose page/API access; new login works, reset does not grant other eligibility | GATED: stage origin/carrier/session topology; security |
| AZS13 | Distinct concurrent consumers have one winner; real expiry/resend/dispatch races preserve deadlines/state | GATED: controlled stage worker/barrier design; engineering |
| AZS14 | Covered cleanup/hold/retry/receipt boundaries preserve new/protected data; unavailable providers leave pending obligations | GATED: supported providers and trusted authority; cleanup/privacy/safety |
| AZS15 | Physical-I/O fencing at actual store across supported instance/failure topology; no false erasure receipt | GATED: production-handler design/provider proof; engineering/storage owner; SC26 stays PARTIAL |
| AZS16 | Independent journal/copy horizon/startup replay and separately authorized witnessed isolated restore | GATED: durable provider/inventory/witnesses; operations/privacy/safety |
| AZS17 | Manual/device/accessibility, error states and full approved geography/confession/media/moderation/account journeys | GATED: existing P1 implementation/review/witnesses; product/qualified reviewers |
| AZS18 | Code/schema-compatible stage rollback, scheduler/alert receipt, stopped workers and evidence review | GATED: accepted operators/coverage and separate execution authorization; release/operations |

## 7. Decisions, external checks and next bounded work

| Class | Exact outstanding item | Stage/release effect and responsible role |
|---|---|---|
| Owner decision Q47 | `PASSWORD_RESET_RESEND_COOLDOWN_SECONDS`, `PASSWORD_RESET_MAX_REQUESTS_PER_HOUR`, `PASSWORD_RESET_MAX_REQUESTS_PER_DAY`: suggested 60s/3 per rolling hour/10 per rolling day remain proposed | Explicit test limits can support a labelled synthetic stage; real-user enablement waits for adopted values and reviewed campus-aware controls. Takudzwa product decision; engineering/security evidence. |
| Owner decision Q40 | Proposed shared DB store/one instance, student 30m idle/12h absolute, privileged 15m/4h with MFA, Lax cookie redesign and no remember-me until separate design | Capture existing configuration first; approve exact design rather than treating native Strict/refresh behavior or the synthetic handler as adopted Q40. Logical reset revocation still required under existing Q47 approval. |
| Owner/operator CONFIRM Q35/Q37/Q42 | Actual plan/tier/staging destination, funded inventory/cap/reserve, accepted primary/backup/release/DB/storage/cleanup/email/support owners and alert recipients/coverage | No new resource purchase, slot assumption, role appointment or public coverage promise here. Decision33 targets remain conditional on real coverage. |
| VERIFY Q39/Q41 | Current runtime/service versions, TLS/network/SQL grants/definers, exact artifact and effective web-root/session/mount/cadence metadata | Needed to choose valid stage profiles and release compatibility; management-plane values cannot substitute for future stage connection tests. |
| VERIFY Q45/Q46 | Existing selected `DoNotReply@bushisa.me` sender/domain/ACS linkage, correct From/Reply-To, SPF/DKIM/DMARC/TLS/useful arrival/bounces, consenting test recipients and mail operator | Real-delivery acceptance is a separate authorized task, not Lane H/M fake mail. Temporary unset Reply-To is approved; `support@bushisa.me` must exist and be monitored before advertisement. Reusing the sender for reset does not itself require new DNS records. |
| REVIEW GATE Q36/Q48 | Named qualified privacy/security/safety and independent appeal reviewer; actual Zimbabwe/foreign flows, private dating/profile data and lawful retention/rights handling | No inferred legal/DPO appointment or sign-off. Geographic scope, confessions, moderation and all D03 features remain release requirements. |
| Existing policy proposals Q12/Q13/Q16/Q23/Q26/Q38 | Anti-replay/field-purpose review, active-chat timing, broader metadata/copy values, recovery objectives and actual inventory | Do not choose missing durations or hard-code a universal 49d. These do not reopen approved report/pair/probation/signup/reset clocks or block independent approved engineering work. |
| Engineering (existing approvals suffice) | STG-I01/I02 and managed-stage driver; supported session/storage/recovery adapters, lifecycle coverage, private media and ingress protections | Prepare/review bounded code changes separately; no weakening guards, enabling legacy pairs, scheduling production cleanup or adopting Q40 by inference. |

**Next owner action:** use section 3 to supply the allowlisted current Azure
metadata and identify an accepted engineering/security/operations primary and
backup; choose and fund an actually isolated staging destination after its tier
is known. No secrets or database records are requested. Meanwhile the next
bounded repository implementation can prepare the **staging-only origin,
fake-carrier and destination/ingress test contract** for review, followed by
implementation/tests under separate authorization. Real mail, provider fencing,
independent recovery and qualified go/no-go remain distinct gates. No settled
decision needs reapproval to prepare that engineering contract.

Executed documentation checks: **104 local link targets**, 12 compatibility
categories, exact baseline CI/merge/two four-job successes, all 18 new cases
NOT RUN, migration inventory 001-016 and documentation-only changes;
`git diff --check` passed. Proposed-versus-approved boundaries and command
scope were reviewed against the source. These are documentation checks, not
functional acceptance. No database, browser acceptance, staging, recovery or
application tests were rerun for this documentation change.
