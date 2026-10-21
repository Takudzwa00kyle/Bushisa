# Azure compatibility audit and isolated staging acceptance plan

**Current release-route amendment:** the owner has selected a controlled
direct-production update using existing resources. Managed staging is deferred
for resource constraints; this document's second-environment proposals are
historical options, not preparation prerequisites. All 18 AZS cases remain
NOT RUN. The [direct-release package](direct-production-release-preparation-2026-10-20.md)
supplies the current plan and narrower newly authorized read-only production
schema/grant capture. This does not turn local staging-driver tests into Azure
acceptance or authorize any production mutation.

Updated 2026-10-20, Africa/Harare. Status: **PARTIAL compatibility audit;
owner-observed configuration recorded; staging GATED / NOT RUN**.
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
| Last returned main deployment-workflow run | [38230207825](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/38230207825), push at `0bf311f`, SUCCESS, created 12 October | GitHub workflow metadata only. Current hosted artifact and unreconciled deployment paths remain UNKNOWN; scoped owner configuration observations are recorded below. |

The committed [L06 implementation artifact summary](evidence/l06-hosted-ci-2026-10-19.json)
deliberately remains attributed to earlier implementation candidate `e7501fe`
and its `82f692d` PR merge. The final `7326716` attribution above does not
relabel those earlier runs. Existing A/LB/LC/QS/QE/QM/SC matrices are unchanged.

At checkpoint `0187c43`, independent Azure capture could not execute: `az`
and `Az.Accounts` were unavailable, and
the browser/node-repl runtime failed before inventory with
`failed to write kernel assets: The system cannot find the path specified.
(os error 3)`, including a browser reset/retry and a post-interruption recheck
(time recorded separately in the metadata JSON). No Azure authenticated session
or resource state was observed. No auth-cache, secret, publishing profile,
connection string, application-settings export, user record or session file was
read. That [historical capture record](evidence/azure-compatibility-metadata-2026-10-19.json)
is preserved unchanged. The following owner-supplied observations now replace
the corresponding unknowns, without becoming an independent agent capture.

### 1.1 Owner-provided observations, 19-20 October 2026

Source: **owner-provided Azure Portal screenshots and application-container
CLI output collected on 19-20 October 2026, Africa/Harare**, transcribed in the
owner's continuation message. The original screenshots/output were not
independently inspected in this update. The [scoped observation ledger](evidence/azure-owner-observations-2026-10-20.json)
preserves every supplied value, including the exact setting names and time
limitations. Exact collection instants were not supplied; no drafting time is
used as capture or approval time. These observations are not policy adoption,
qualified review, staging acceptance or evidence that `7326716` is deployed.

| Owner-observed area | Recorded fact | Remaining interpretation / gate |
|---|---|---|
| App/plan | `Bushisa`, `takudzwaportfolio`, Azure for Students, South Africa North, Linux/Code; plan `ASP-takudzwaportfolio-a5b0`, Basic B1; one instance/app, zero slots, zone redundancy disabled; domain `bushisa.me`. | No slot is available on this observed B1 plan. Propose a separately funded isolated staging app/compute; no upgrade/provisioning is authorized. Credit, expiry, budget, reserve and accepted operators remain unknown. |
| App controls | PHP stack 8.5, platform channel Standard/default, startup blank; HTTPS Only, Always On, SSH and WebJobs runtime enabled. App/SCM minimum TLS 1.2; affinity disabled; SCM Basic Auth enabled; FTP Basic Auth disabled, FTPS only. HTTP 1.1, HTTP 2.0 proxy Off; cipher displayed `TLS_RSA_WITH_AES_128_CBC_SHA` (default); client certificate mode Ignore; health check not configured. | These are observed settings, not security/performance acceptance. Review publishing-auth dependencies, cipher/ingress and health/alert coverage without changing settings. Standard/default channel is not a Standard-tier plan. The PHP/NGINX advisory details remain unreviewed. |
| Network | App integration `BushisaVnet/BushisaAppSubnet`; zero App Service private endpoint connections. MySQL private access through `BushisaVnet/BushisaDbSubnet`, DNS zone `privatelink.mysql.database.azure.com`. | Reported private DB configuration does not establish effective app routing, TLS validation or private staging ingress. Zero **app** private endpoints does not contradict the separately reported private DB access. |
| Deployment | GitHub `Takudzwa00kyle/Bushisa`, main; build provider App Service Build Service. Active ID `25ae3df2-bf75-4dd4-a9ed-625a2b759c24`; log says OneDeploy, output `/home/site/wwwroot`, success on 12 October around 11:30:27 AM in portal. | UUID is not a Git SHA; portal log timezone/UTC offset not supplied. No source/worker artifact or migration attribution to this ID is established. Repository workflow and portal provider observations must be reconciled, not silently treated as one proven release path. |
| CLI PHP | PHP 8.5.11 NTS, Zend OPcache 8.5.11. Supplied module subset includes PDO/pdo_mysql/mysqli/mysqlnd, openssl/sodium, mbstring/fileinfo/gd/exif/intl/curl/zip/redis/pcntl. Main loaded configuration file none; additional directory `/usr/local/etc/php/conf.d`; parsed extension files, `opcache-recommended.ini`, and `/usr/local/etc/php/conf.d/php.ini`. | CLI-only. No main file does not mean no INI configuration. FPM/web overrides, application-installed session handler and complete extension requirements remain VERIFY. Redis extension is not a Redis service or shared session store. |
| MySQL service | Flexible Server, MySQL 8.0, South Africa North/zone 2, Burstable `Standard_B1ms`, one vCore/2 GiB RAM, 20 GiB/360 provisioned IOPS, autogrow on; no HA/replicas; system-managed maintenance. | Exact patch, effective connection auth/isolation/collation, grants and deployed schema remain UNKNOWN. Tier/IOPS are not capacity or availability evidence; no engine upgrade is inferred. |
| MySQL parameters (owner transcription) | `require_secure_transport=ON`; `tls_version=TLSv1.2,TLSv1.3`; `time_zone=+00:00`; `sql_mode=NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO`; `character_set_server=UTF8MB4`; `audit_log_enabled=OFF`; isolation NOT SHOWN, collation truncated. | Preserve the exact observed list, without adding defaults. Server configuration is not effective app-session evidence. DB audit flag is distinct from application safety/audit tables and does not establish their coverage. |
| Worker | Triggered `bushisa-mail`, Completed; six-field `0 * * * * *`; existing log displays 2026-10-19 23:42:00-23:42:02, timezone unlabelled; BashScriptHost ran `run.sh`, Success. Cleanup counters `expired_codes_terminalized=0`, `code_bearing_payloads_erased=0`, `cleanup_rows_completed=0`; mail counters sent/retried/discarded/failed all zero. | One successful **no-work** run. No delivery, erasure, useful-deadline enforcement, complete coverage or sustained cadence proven. Its time is not silently converted to UTC/Harare. Worker SHA remains UNKNOWN. |

Observed non-secret app values, transcribed exactly (not read by the agent):

```text
APP_ENV=production
APP_DEBUG=false
BUSHISA_ALLOW_RUNTIME_DDL=false
AZURE_MYSQL_FLAG=MYSQLI_CLIENT_SSL
DB_HOST=bushi-server.mysql.database.azure.com
DB_NAME=bushi-database
MAIL_PROVIDER=smtp
WEBSITES_ENABLE_APP_SERVICE_STORAGE=true
WEBSITE_SKIP_RUNNING_KUDUAGENT=false
```

DB, SMTP, outbox and signup-verification setting-name categories were present;
their complete individual name inventory was not supplied. No password,
encryption/HMAC key or telemetry connection-string value was collected.
`APP_BASE_URL`, `PASSWORD_RESET_ENABLED` and production throttle values were
not supplied and remain UNKNOWN; candidate reset issuance is off **by default**,
which is not evidence of the deployed activation state.

Static code comparison: the candidate's PDO configuration reads neither
`AZURE_MYSQL_FLAG` nor `MYSQLI_CLIENT_SSL`. Its production/hostname rules require
verified TLS through `DB_SSL_CA`/the CA bundle and PDO options; the observed
flag and server TLS settings do not prove deployed certificate/hostname checks.
The exact observed telemetry name is **`APPLICATIONINSIGHTS_CONNECTIONS_STRING`**
(plural CONNECTIONS). No tracked application/config/JS/workflow read of this
name, the singular form, or an Application Insights SDK reference was found
in the scoped code search. Microsoft's documented conventional name is
[`APPLICATIONINSIGHTS_CONNECTION_STRING`](https://learn.microsoft.com/en-us/azure/azure-monitor/app/application-insights-faq).
This is a naming/usage review item, not permission to rename, retrieve its
value or activate collection. Platform agents, actual destinations and ingestion
remain unverified; **D10 stays GATED** pending purpose/notice/redaction/consent,
configuration/retention and reviewer acceptance.

The observed cleanup output also does not identify the candidate: current
`bin/cleanup_policy.php` emits `signup_expiry`, `password_reset_cleanup`,
report and pair counters, and does **not** emit `cleanup_rows_completed`.
The owner's transcription may be a subset or a different artifact/wrapper;
do not guess a SHA from the output shape. Capture redacted artifact provenance
for application and worker separately before compatibility execution.

| Owner backup observation | Supplied UTC instant | Derived Africa/Harare display |
|---|---|---|
| Latest completed automated backup | 2026-10-19 02:42:36.457 UTC | 2026-10-19 04:42:36.457 |
| That backup retained until | 2026-10-26 02:42:36.457 UTC | 2026-10-26 04:42:36.457 |
| Earliest restore point displayed | 2026-10-12 22:17:24.999 UTC | 2026-10-13 00:17:24.999 |

Configured retention is **seven days**, with seven completed automated backups
visible in the supplied observation. Redundancy, other recoverable copies and
independent recovery are UNKNOWN; restore rehearsal was not performed.
Seven days is an observed setting, not adoption/rejection of Q26's proposed
35-day ceiling or proof of RPO/RTO. For a protective action that this particular
backup could undo, its listed expiry plus the approved seven-day recovery
margin is **2026-11-02 02:42:36.457 UTC / 04:42:36.457 Africa/Harare** (derived
arithmetic only). Later copies or still-live restrictions can require longer;
no account-specific or universal expiry is established without the full inventory.

## 2. Repository requirements versus actual Azure

Owner observations above now support the corresponding configuration facts.
All remaining UNKNOWN/VERIFY/GATED entries retain their precise scope;
repository behavior is established by source inspection, not Azure execution.

| ID / area | Concrete repository requirement and behavior | Owner-observed configuration / remaining evidence and consequence |
|---|---|---|
| AC01 PHP | [composer.json](../../composer.json) permits PHP `^8.2`; declares PDO, pdo_mysql, OpenSSL and PHPMailer. Calls also need `session`, `ctype`, `mbstring`, cURL and `fileinfo`. [upload_validator.php](../../php/upload_validator.php) conditionally uses GD/JPEG re-encoding; absence silently skips JPEG stripping, so extension presence alone does not certify media privacy. CI uses PHP 8.4; deployment workflow builds with 8.5. | Owner Portal stack 8.5/Standard-default channel and container CLI PHP 8.5.11 NTS/OPcache 8.5.11 are observed. Required PDO/pdo_mysql/openssl/mbstring/fileinfo/curl and gd appear in the supplied module subset; ctype/session were not explicitly listed in that subset, not proved absent. CLI INI scan/parsing is recorded above. Web/FPM overrides, complete effective extensions and the accepted exact Q39 matrix remain VERIFY. Linux CI 8.4.26 is not an Azure PHP 8.5 acceptance run. |
| AC02 MySQL/schema | PDO native prepares, InnoDB transactions/locks, UTC session, microsecond timestamps, JSON and triggers/routines through [migration 016](../../database/migrations/016_password_reset.sql). [bin/migrate.php](../../bin/migrate.php) owns additive migration markers and `--through=NNN`; MySQL DDL is not an atomic whole-release transaction. | Owner reports Flexible Server `bushi-server.mysql.database.azure.com`, DB `bushi-database`, MySQL 8.0, Burstable Standard_B1ms (1 vCore/2 GiB), 20 GiB/360 IOPS with autogrow; system-managed maintenance, no HA/replicas. Server parameter transcription is recorded above. Exact patch, full collation, effective auth/isolation and applied migrations remain UNKNOWN. Neither 005 nor 016 is asserted production-applied; local 9.6 is not the Azure target. |
| AC03 privileges/TLS | [database_settings.php](../../config/database_settings.php) resolves `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`/`DB_PASS`, then Azure aliases; production rejects missing config/root/empty password. `DB_SSL_CA` or the system CA is required for production/Azure hosts. [config/db.php](../../config/db.php) enables certificate verification, requires a nonempty TLS cipher, sets `+00:00`. Runtime needs exact DML and fixed health-procedure EXECUTE; migration identity needs reviewed DDL/TRIGGER/routine privileges/definer visibility. | Owner reports production/debug-off/runtime-DDL-off app values; app VNet BushisaAppSubnet, DB private BushisaDbSubnet/DNS, secure transport ON, TLSv1.2/TLSv1.3 and server UTC. These align with parts of the intended configuration, not effective client certificate/hostname validation or private reachability proof. `AZURE_MYSQL_FLAG=MYSQLI_CLIENT_SSL` is not consumed by this PDO code. Actual CA/options, SQL roles/grants/definers and connection isolation remain UNKNOWN; no live DB connection was made. |
| AC04 deploy | [main_bushisa.yml](../../.github/workflows/main_bushisa.yml) deploys app `Bushisa`, slot `Production`, on **main push or workflow_dispatch**; CI is a separate push/PR workflow. Deployment has no required `needs: CI` gate and no migration step. Its PHP 8.5 build is not an effective Azure runtime setting. | Owner Portal source is GitHub Takudzwa00kyle/Bushisa/main with App Service Build Service; OneDeploy UUID `25ae3df2-bf75-4dd4-a9ed-625a2b759c24` reports success on 12 October. UUID is not a source SHA. Reconcile portal provider/OneDeploy and repository workflow paths. Hosted app/worker source/artifact, deployment approvals and schema versions remain UNKNOWN; do not infer `7326716` deployment or dispatch production to create staging. |
| AC05 web root/startup | PHP entry points are at repository root; WebJob assumes `/home/site/wwwroot`. [.htaccess](../../.htaccess) contains Apache rewrites/HTTPS rules, not an NGINX protection configuration. Repository includes database SQL, tests, config, logs and `.tmp` paths requiring denial or exclusion from a web artifact. | Owner reports Linux/Code, blank startup and OneDeploy output `/home/site/wwwroot`; a PHP/NGINX advisory is visible but detailed guidance unreviewed. HTTPS Only is on, inbound app/SCM TLS minimum 1.2. Effective NGINX root/denials, package contents, FPM and trusted proxy behavior remain UNKNOWN. Deployment output path is not a verified web-root configuration; `.htaccess` is not sufficient. No live sensitive-path probes were made. |
| AC06 sessions | [session_manager.php](../../php/session_manager.php) uses native configured PHP sessions unless the guarded synthetic handler is requested. Native cookies are HttpOnly/Strict with 1,800-second lifetime/activity refresh, including protected-request polling; `SESSION_COOKIE_SECURE` can override HTTPS detection. `require_login` reloads account status/session version. Q40's DB store, role-specific idle/absolute values and Lax redesign remain proposed. | Owner reports one instance, session affinity off and Redis CLI extension present. Neither establishes a Redis service, shared session handler or safe persistence. Web-effective/installed handler, save path, cookie/proxy overrides, locking/GC and restart behavior remain UNKNOWN. Current one-instance observation does not adopt proposed Q40 configuration or prove physical session-copy erasure. |
| AC07 SC26/storage fence | [managed_session.php](../../php/managed_session.php), [session_copy_store.php](../../php/session_copy_store.php) and [consumer](../../php/session_copy_cleanup.php) support only isolated `.tmp/sf-storage-*` synthetic roots, exact generations/namespaces and a non-expiring OS process fence. Stalled A keeps B `busy`; after A resumes/exits, retry can complete. | Local physical-I/O subcase remains PASS; SC26 overall remains PARTIAL. Owner's one-instance and persistent-storage flag observations do not prove the synthetic fence on actual Azure mounts, across restarts/hosts or future scale. Production handler/root/object identity, lock semantics, failure recovery and copy coverage remain UNKNOWN. No lease-only takeover or forced lock break is approved/proved. |
| AC08 media/logs | [upload_validator.php](../../php/upload_validator.php) writes `uploads/<user>/photo_*`; complete private media/derivative/version mapping is absent. [logger.php](../../php/logger.php) writes `logs/app.log`. Q11 fixture deletion is not a production media adapter. | Owner reports `WEBSITES_ENABLE_APP_SERVICE_STORAGE=true` and CLI gd/exif, but no actual session/media mount/root, ACL, object/version/cache inventory or physical-erasure evidence. `APPLICATIONINSIGHTS_CONNECTIONS_STRING` is a reported name only, not a code-consumed setting or instrumentation proof. Private media, effective metadata stripping, logs/destinations/retention and D10 acceptance remain unverified/gated. |
| AC09 scheduling | [run.sh](../../deploy/mail-webjob/run.sh) uses `set -euo pipefail`, `cd /home/site/wwwroot`, cleanup then dispatch; [settings.job](../../deploy/mail-webjob/settings.job) says `0 * * * * *`. A cleanup failure stops subsequent dispatch. [cleanup_policy.php](../../bin/cleanup_policy.php) handles bounded signup/report/pair/reset paths; separate Q22/attempt/session consumers are not all invoked by that script. | Owner reports Always On/WebJobs runtime enabled, `WEBSITE_SKIP_RUNNING_KUDUAGENT=false`, and Triggered `bushisa-mail` with every-minute schedule. One existing BashScriptHost `run.sh` run completed successfully with all reported counters zero; its displayed timezone is unknown. This proves no useful-work delivery/erasure or sustained cadence. Counter shape differs from the candidate; exact worker artifact, coverage, quotas/capacity, overdue alerts and failure routing remain UNKNOWN. |
| AC10 recovery/holds | L03a authority and independent journal integration remain unavailable outside guarded fixtures; Q11 attempt erasure and Q22 consumers require recovery adapters; synthetic session consumer cannot be enabled on an arbitrary remote store. Minimal independent protection must survive outside the restore set and be replayed before reads/dispatch. | Owner observes configured DB backup retention 7d, seven completed backups and the exact restore/expiry instants above. Redundancy, all other copies, independent journal/key custody/acknowledged continuity, effective holds and witnessed restore remain UNKNOWN / NOT RUN. No restore rehearsal occurred. The 35d DB limit remains a proposal; compute recovery from actual latest recoverable expiry plus 7d and protective needs, never a universal 49d. |
| AC11 mail/reset | `MAIL_PROVIDER`, `MAIL_TRANSPORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `MAIL_REPLY_TO`, `MAIL_OUTBOX_KEY`; SMTP host/port/encryption/username/password/timeout; signup HMAC/code settings. Reset additionally uses `APP_BASE_URL`, `PASSWORD_RESET_ENABLED` and the three throttle variables below. Proof validity is fixed at 900s; useful queue ceiling 300s and expiry minus 60s. | Owner observes `APP_ENV=production`, `MAIL_PROVIDER=smtp` and DB/SMTP/outbox/signup setting-name categories; secret values were not collected. Current resource/sender/secret-reference resolution, quotas, delivery/inbox/support monitoring remain unverified beyond historical ACS/sender evidence. `APP_BASE_URL`, `PASSWORD_RESET_ENABLED` and production throttle values were not supplied: deployed reset activation is UNKNOWN, while the candidate defaults off. Both managed-staging blockers remain. |
| AC12 tier/funding | Isolation, separate configuration/data/mail/storage and deployment controls are required. No staging resource or billable monitor is created by source. | Owner observes Bushisa/takudzwaportfolio, Azure for Students, South Africa North/Linux, plan ASP-takudzwaportfolio-a5b0, Basic B1, one instance/app, zero slots and zone redundancy off. B1 does not support a staging slot; propose separately funded isolated staging app/compute instead, without upgrading/provisioning here. Funding/credit/expiry/reserve, stage destination and accepted operators remain CONFIRM; no capacity/SLO approval follows from the SKU. |

Microsoft documents [PHP/NGINX configuration and persisted `/home`](https://learn.microsoft.com/en-us/azure/app-service/configure-language-php),
[slot tier requirements](https://learn.microsoft.com/en-us/azure/app-service/deploy-staging-slots)
and [WebJobs platform limits](https://learn.microsoft.com/en-us/azure/app-service/overview-webjobs).
These platform descriptions are not Bushisa resource observations; persisted
storage says nothing about this handler's cross-host lock/conditional-delete safety.
Microsoft's [slot requirements](https://learn.microsoft.com/en-us/azure/developer/azure-developer-cli/app-service-slots)
exclude Basic; the owner-observed B1/zero-slot state therefore changes the
staging proposal to separate funded isolation, without authorizing an upgrade.

## 3. Safe owner metadata collection, without secret retrieval

The owner-provided facts in section 1.1 need not be recollected to complete
this update. Use these instructions only for remaining unknowns or a necessary
later refresh, keeping new sources/times distinct. They are **unexecuted read-only instructions** for an already-authenticated
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
$auditApp = 'Bushisa' # owner-observed production app; never a staging target
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

Non-secret flags still needed: `APP_BASE_URL`, `PASSWORD_RESET_ENABLED`, the
three reset throttle values, `SESSION_COOKIE_SECURE`, `WEBSITE_RUN_FROM_PACKAGE`,
`SCM_DO_BUILD_DURING_DEPLOYMENT`, `WEBJOBS_STOPPED` and scheduler/consumer flags.
The owner-supplied production/debug/runtime-DDL/storage/Kudu values above do
not need recollection for this update.
The operator supplies allowlisted booleans/statuses at source, redacting any
URL that embeds credentials. **Do not call appsettings/connection-string list
even with `--query`**: client-side projection still fetches the secret-bearing
endpoint. Likewise no publishing-profile, Key Vault secret-get or storage-key
operation. Record key/reference **presence and resolution status only**.

## 4. Audited staging blockers and guarded implementation

The table records ordinary production/loopback behavior audited at `7326716`.
The [guarded implementation follow-up](guarded-staging-implementation-2026-10-20.md)
supplies the separate stage-origin profile, private CLI fake carrier and ingress
driver, preserving these production protections. Actual managed execution still
needs the accepted destination/identity profile; local implementation evidence
does not establish Azure acceptance.

| Baseline blocker | Preserved production/loopback protection | Limitation without guarded profile | Guarded follow-up / required acceptance |
|---|---|---|---|
| STG-I01 trusted origin | [password_reset.php](../../php/password_reset.php) `bushisa_reset_trusted_origin()` uses configured `APP_BASE_URL`, rejects user/pass/query/fragment, requires HTTPS and host exactly `bushisa.me` with absent/443 port outside synthetic mode. It never builds links from Host/redirect input. Synthetic relaxation needs `BUSHISA_PASSWORD_RESET_SYNTHETIC` and **all** `bushisa_q11_synthetic_enabled()` guards, including loopback DB host, `bushisa_test_q11_*`, development/debug-off, runtime-DDL-off, fake provider/transport and explicit synthetic flag. | An Azure staging hostname or `staging.bushisa.me` is rejected. Remote managed MySQL cannot satisfy that loopback cohort. Request UX stays generic and writes no reset issuance. Setting the staging URL to the production origin, tunnelling a live database through localhost, or setting flags to impersonate a fixture is not an acceptable workaround. | A separately reviewed staging-only origin/destination profile, exact HTTPS hostname and explicit dedicated synthetic resource/account-generation proof. Preserve the default production `bushisa.me` rule, no wildcard hosts or request-selected origin. Reject missing/production/wrong resource, unknown host, userinfo/query/fragment, bad port/scheme and spoofed Host/redirect; prove valid staging links remain on the approved staging origin, GET/HEAD remain inert and existing 15m/supersession/consume rules unchanged. The guarded follow-up implements this profile; managed acceptance remains NOT RUN. |
| STG-I02 fake carrier | [bin/dispatch_mail.php](../../bin/dispatch_mail.php) rejects `MAIL_PROVIDER != smtp` **before DB load** and constructs an SMTP mailer. [signup_verification.php](../../php/signup_verification.php) rejects fake mail in production. Existing service [mail_delivery.php](../../php/mail_delivery.php) `bushisa_dispatch_mail(..., $transport)` supports injected test callbacks; [reset jobs](../../php/password_reset_mail_jobs.php) refuse sendable fake jobs without an explicit carrier. | Running the production CLI with fake settings fails; fake mode alone does not deliver/reset a job. Supplying real SMTP would violate this acceptance plan and add real-mail dependencies. Existing guarded fixture callbacks are local-only, not a managed-staging CLI/worker adapter. | Separate CLI-only synthetic carrier entry point/profile with pre-connection immutable destination checks, synthetic-recipient/account allowlist and scoped in-memory receipt capture. Reuse service state machines, encryption, claim/version, deadlines and cleanup. Keep production CLI SMTP-only and production fake-provider denial. Test default denial, wrong target/recipient/purpose, no SMTP/network access, retries/cancellation/in-flight and late-result handling, password-change notices, one consume winner and secret-free output. A missing carrier must fail, never fall back to SMTP. The separate guarded entry point now exists in the follow-up; managed execution remains NOT RUN. |

These requirements govern the bounded implementation using the destination
contract below, with initial tests on owned disposable systems.
Acceptance of the actual manifest/security profile is required before managed
execution; no production throttle decision is needed for explicitly labelled
synthetic limits. Adapting these two surfaces alone
does not provide managed-session cleanup, independent recovery or production
readiness. Do not weaken local-harness guards to retarget destructive suites.

### 4.1 Final bounded engineering brief: STG-I01 / STG-I02 / managed driver

**Implemented in the bounded follow-up; managed execution remains GATED / NOT
RUN.** The numbered brief remains the engineering acceptance contract. See the
linked implementation for concrete guards, commands and exact local/CI evidence;
it does not provision or authorize a real staging destination.

1. **One stage destination contract, default-deny.** Define a protected,
   versioned manifest binding exact synthetic app/resource, HTTPS origin,
   dedicated server/database identity, credential roles, recipient/account
   generations, storage/session namespace and fake-carrier mode. Validate
   before connection/fixture mutation. Actual stage values remain UNASSIGNED.
   Reject production `Bushisa`/`bushi-server`/`bushi-database`, unknown or
   mismatched targets, missing isolation evidence/CA and an unapproved origin;
   a test-looking name or loopback tunnel is insufficient. HTTP headers,
   submitted IDs, roles or timestamps cannot establish that authority.
2. **Stage-only trusted origin admission.** Add the minimum explicit guarded
   staging profile for a single exact approved HTTPS origin. Keep ordinary
   production admission at `bushisa.me` and existing rejection of userinfo,
   query/fragment, bad scheme/port and Host/redirect input. No wildcard
   `azurewebsites.net`, arbitrary configured-host acceptance, production-domain
   links from staging or relaxation of the existing local-only guards.
3. **Separate CLI fake-carrier path.** Provide a CLI-only synthetic entry point
   using the existing injected-transport services/state machine. It must use
   the same validated manifest, stage-only credentials and synthetic recipient
   allowlist; never create an SMTP mailer, read production mail credentials or
   fall back to the ordinary SMTP CLI. Keep fake mail forbidden in production
   even if a caller supplies a staging flag. Keep signup/reset/notice purpose
   separation, encrypted envelopes, immutable deadlines and in-flight receipts.
4. **A guarded managed-stage ingress/fixture driver.** Separate non-destructive
   browser/HTTP execution from explicitly authorized bootstrap/migration work.
   Use exact private HTTPS target, separate fixture/runtime/worker identities
   and synthetic fresh generations; never silently reset a DB during a browser
   run or retarget loopback fixtures by changing flags. Capture reset proof
   only in memory through the fake carrier and intended form POST, disabling
   recovery traces/screenshots/video/DOM dumps. No cookies, proof, passwords,
   bodies or keys in logs/result artifacts. Session-handler/recovery adapters
   remain outside this slice, not bypassed by the driver.
5. **Required guarded tests.** First use owned disposable databases, synthetic
   accounts/storage and fake mail only: missing/conflicting manifest and
   production-mode/target denial before connection; trusted-origin injection
   negatives; fake carrier zero SMTP/network calls and no fallback; unchanged
   signup eligibility/purpose separation; CSRF, scanner-safe GET/HEAD,
   expiry/supersession/replay and cross-account/wrong-purpose proof rejection;
   cancellation/late-result/retry behavior; two authenticated sessions losing
   page/API access and normal new-password sign-in. Prove one concurrent consume
   winner with distinct processes/connections and actual overlap, not timing
   assumptions. Run affected L06/mail/Q11/Q22/authorization regressions on the
   established profiles, record exact candidate/environment and keep missing
   variants NOT RUN. A later managed-runtime run must separately record the
   real ingress, TLS, restricted grants, patch and web-effective session state.
6. **Completion and exclusions.** Deliver the guarded manifest/origin/carrier/
   driver code, focused local commit, negative tests and safe evidence, with
   production defaults/protections unchanged. No new session/Q40 design,
   telemetry/D10 activation, cleanup/recovery provider, production throttle,
   legacy certification, purchase, upgrade, deployment or real mail. Retain
   unresolved release/qualified-review gates. Local implementation test PASS
   must not turn AZS01-AZS18 into managed-stage PASS; those require separate
   executed evidence and execution authorization.

Existing Q47/eligibility/session-revocation approvals govern this engineering
work; no settled product decision needs reapproval. Explicit synthetic limits
do not adopt production throttle values. Before managed execution, the owner
and accepted security/operations reviewers must bind/fund the actual isolated
destination, credentials, host, recipients and namespace. The observed B1 plan
does not supply a slot or such a manifest.

## 5. Isolated staging procedure (not executed)

### 5.1 Preflight and destination manifest

Before any later execution/provisioning authorization, engineering and the
accepted operator record the following privately. **Staging target** values are **UNASSIGNED**;
the production observations in section 1.1 are not a staging destination.
placeholder names are not resources, accounts, namespaces or appointments.

| Target | Required concrete binding and isolation |
|---|---|
| Deployment | Exact candidate SHA/artifact digest; separate private staging app/resource ID, hostname, region, plan/tier and approved funding. Never app `Bushisa`/slot `Production`, main push, production workflow dispatch or slot swap. Owner reports Basic B1/zero slots: propose a separately funded isolated app/compute, not a slot on this plan or an automatic upgrade. Price/approve the actual destination before provisioning. |
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

This lane remains **GATED** by remaining section 3 capture, the accepted target
manifest and verified guarded-origin/carrier/driver configuration. STG-I01/I02
have a separate implementation; local fixture evidence is not this lane's
execution. It needs separate execution authorization; this document
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
| AZS02 | CLI and FPM runtime/extensions/artifact match accepted Q39 matrix | Owner CLI 8.5.11 supplied; FPM/complete matrix and actual stage execution still GATED; engineering/security + Takudzwa acceptance |
| AZS03 | Managed TLS/auth/UTC/microsecond/collation/lock behavior and restrictive runtime grants | GATED: stage DB/identities; DB/security reviewer |
| AZS04 | Fresh 001-016 and repeat run; runtime cannot perform migration DDL | GATED: dedicated bootstrap/migration target; engineering |
| AZS05 | Representative 015-to-016 upgrade preserves original rows/anchors/generations; repeat safe after reviewed partial failure | GATED: reviewed synthetic fixture/projection; engineering |
| AZS06 | Private ingress routes/assets work; hidden/config/SQL/test/log/CLI paths denied | GATED: stage artifact/root/NGINX configuration; security |
| AZS07 | Signup/email approval/adult/onboarding/replacement clocks stay immutable and restricted | GATED: fake carrier/ingress driver; engineering |
| AZS08 | Reset origin exact stage allowlist/default production denial and no Host/redirect override | GATED: implemented profile needs actual accepted managed target/security execution; security + engineering |
| AZS09 | Fake carrier cannot reach SMTP; purpose/envelope/deadline/cancellation/late-result behavior correct | GATED: implemented carrier needs actual guarded managed execution; engineering/email operations |
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
| Owner decision Q40 | Proposed shared DB store/one instance, student 30m idle/12h absolute, privileged 15m/4h with MFA, Lax cookie redesign and no remember-me until separate design | Owner's observed one-instance/affinity-off settings are not policy adoption. Effective handler/web settings remain unknown. Approve exact design rather than treating native Strict/refresh behavior, Redis extension or synthetic handler as adopted Q40. Q47 logical reset revocation remains required. |
| Owner/operator CONFIRM Q35/Q37/Q42 | Production B1 plan/tier is owner-observed; actual isolated staging destination, funded inventory/cap/reserve/credit expiry, accepted primary/backup/release/DB/storage/cleanup/email/support owners and alert recipients/coverage remain missing | No new purchase, slot, upgrade, role appointment or public coverage promise. Decision33 targets remain conditional on demonstrated coverage. |
| VERIFY Q39/Q41 | Owner CLI 8.5.11 and MySQL 8.0/configured TLS/private-network facts recorded; exact MySQL patch, SQL grants/isolation/definers, application/worker SHA, effective CA/hostname verification, FPM/web-root/session/mount and sustained cadence still missing | Needed for accepted stage/release compatibility. Review SCM Basic Auth, cipher/NGINX advisory and absent health check/alerts with accepted security/operations owners; no setting change is authorized. |
| VERIFY Q45/Q46 | Existing selected `DoNotReply@bushisa.me` sender/domain/ACS linkage, correct From/Reply-To, SPF/DKIM/DMARC/TLS/useful arrival/bounces, consenting test recipients and mail operator | Real-delivery acceptance is a separate authorized task, not Lane H/M fake mail. Temporary unset Reply-To is approved; `support@bushisa.me` must exist and be monitored before advertisement. Reusing the sender for reset does not itself require new DNS records. |
| REVIEW GATE Q36/Q48 | Named qualified privacy/security/safety and independent appeal reviewer; actual Zimbabwe/foreign flows, private dating/profile data and lawful retention/rights handling | No inferred legal/DPO appointment or sign-off. Geographic scope, confessions, moderation and all D03 features remain release requirements. |
| Existing policy proposals Q12/Q13/Q16/Q23/Q26/Q38 | Anti-replay/field-purpose review, active-chat timing, broader metadata/copy values, recovery objectives and actual inventory | Do not choose missing durations or hard-code a universal 49d. These do not reopen approved report/pair/probation/signup/reset clocks or block independent approved engineering work. |
| Engineering (existing approvals suffice) | STG-I01/I02 and managed-stage driver; supported session/storage/recovery adapters, lifecycle coverage, private media and ingress protections | Prepare/review bounded code changes separately; no weakening guards, enabling legacy pairs, scheduling production cleanup or adopting Q40 by inference. |

**Bounded implementation follow-up:** the guarded staging origin, private CLI
fake carrier and managed-stage driver are recorded in the linked implementation
evidence. Local disposable tests do not close AZS01-AZS18.
**Next owner action before managed execution:**
accept named engineering/security/operations primary and backup, choose/fund
separate isolated staging resources (B1 has no slot), and bind the destination
manifest; provide redacted hosted application/worker source/artifact provenance
and the unread NGINX advisory details. Use section 3 only for remaining facts,
not to repeat the supplied observations; no secrets or user/database records
are requested. Real mail, provider fencing,
independent recovery and qualified go/no-go remain distinct gates. No settled
decision is reopened; the engineering brief supplies an implemented guarded
path, not an executed or accepted managed-stage flow.

At the owner-observation documentation checkpoint, executed checks were
**108 local link targets**, 12 compatibility
categories, exact baseline CI/merge/two four-job successes, all 18 new cases
NOT RUN, migration inventory 001-016 and documentation-only changes;
`git diff --check` passed. The owner observation ledger was checked for exact
values, provenance/unknown boundaries, UTC/Harare conversion, recovery-margin
arithmetic and zero-work counters. Scoped setting-use search and the new
engineering-brief anchor passed; decisions and historical CI/SC26 evidence
were unchanged. Proposed-versus-approved boundaries and command scope were
reviewed against the source. These are documentation checks, not functional
acceptance. No database, browser acceptance, staging, recovery or application
tests were rerun for that documentation-only checkpoint. The new implementation's
checks and candidate attribution are separate in its linked evidence.
