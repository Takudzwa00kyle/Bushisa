# Bushisa: Azure SMTP deployment

## Current documentation boundary: 2026-10-16

The [11 October continuation checkpoint](Bushisa-P0-P2-Continuation-Prompts-2026-10-11.md)
T02/T10-T14 records later source/operator evidence of deployment, SMTP and an
authorized inbox/code-entry journey. This document's original not-pushed/not-deployed
paragraph describes its original patch baseline, not the latest operator report.
It is not fresh verification of current Azure settings or all delivery gates.

Q45/Q47 now select the recorded `DoNotReply@bushisa.me` sender/display `Bushisa`
for transactional mail/reset. Reset is not implemented/accepted merely by that
selection. The owner now permits temporary unset Reply-To until a real
monitored receiving inbox is verified; see the
[amendment record](remediation/owner-conflict-decisions-2026-10-16.md).
`support@bushisa.me`, its owner and actual receipt/monitoring proof remain
proposed/unverified. See the
[acceptance supplement](remediation/owner-policy-reconciliation-2026-10-15.md),
[retention matrix](remediation/data-inventory-retention-matrix.md) and
[read-only Azure/Namecheap checklist](remediation/configuration-impact-checklist-2026-10-15.md).
No additional sender-domain DNS is required solely by reusing the same verified
sender for reset; any real repair/receiving-provider records need actual evidence.
Do not run the historical mutation/deployment examples below for this task.

## L06 local implementation handoff 2026-10-19

The current `codex/l06-password-reset-20261019` branch adds the bounded reset
flow and migration `016_password_reset.sql`. Its local evidence is in
[`l06-password-reset-local.json`](remediation/evidence/l06-password-reset-local.json).
The default remains `PASSWORD_RESET_ENABLED=false`. Enabling issuance also
requires explicit `PASSWORD_RESET_RESEND_COOLDOWN_SECONDS`,
`PASSWORD_RESET_MAX_REQUESTS_PER_HOUR`, and
`PASSWORD_RESET_MAX_REQUESTS_PER_DAY` values: Q47's proposed numeric values
remain pending and are not supplied as defaults. Synthetic HTTP origin
exceptions require both synthetic guards; a non-synthetic origin is fixed to
the trusted HTTPS `https://bushisa.me` origin.

Compatible rollout ordering for a later separately authorized environment is:
review/apply additive migration 016, deploy code with reset issuance disabled,
verify the compatible dispatcher and cleanup worker, resolve throttle values
and qualified review/readiness gates, then consider a separate explicit
feature-enable change. This repository task did not alter Azure settings,
secrets, DNS, mail scheduling, or production data. Reusing the already-approved
sender does not itself require new sender-domain DNS records. Actual sender
verification, inbound monitored support, headers and arrival remain unverified.

The [rendered acceptance and hosted-CI evidence](remediation/l06-browser-hosted-ci-evidence-2026-10-19.md)
separately records Windows/Linux synthetic results and exact GitHub source/merge
checkouts. CI uses fake mail, disposable databases and explicit synthetic
throttles; its success does not enable recovery or establish Azure compatibility.

The original application only captured fake development email. This change adds
the SMTP provider, an encrypted transactional outbox, a CLI dispatcher and Azure
MySQL environment-variable mapping with verified TLS. DNS and a connected Azure
email domain do not install this code into the running app.

## 1. Deploy the code and dependencies

Apply the supplied patch to the repository checkout, review it, commit it and
push it through the existing GitHub deployment workflow. For example, from the
repository root:

```bash
git apply --check /path/to/Bushisa-Azure-SMTP.patch
git apply /path/to/Bushisa-Azure-SMTP.patch
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

The release must contain `vendor/autoload.php`. Run Composer in the build job
before packaging the app, or use App Service's supported Composer/Oryx build.
For App Service source/ZIP builds that need Oryx, configure
`SCM_DO_BUILD_DURING_DEPLOYMENT=true`. Do not turn off certificate verification.

This repository's CI tests the change; it does not deploy the app by itself.
Continue using the deployment workflow selected in Azure Deployment Center.

## 2. Azure app settings

Open **App Services → Bushisa → Settings → Environment variables → App settings**.
Add the following names exactly, then Apply the main settings page. Azure
injects app settings into PHP's environment; uploading `.env.example` does not
configure these values.

| Name | Production value |
|---|---|
| APP_ENV | `production` |
| APP_DEBUG | `false` |
| APP_BASE_URL | `https://bushisa.me` once its HTTPS binding works |
| MAIL_PROVIDER | `smtp`, after this code, dependencies and migration are deployed |
| SMTP_HOST | `smtp.azurecomm.net` |
| SMTP_PORT | `587` |
| SMTP_ENCRYPTION | `tls` (STARTTLS) |
| SMTP_USERNAME | Copy the exact username in **bushisa-communication → SMTP Usernames**; it must be Ready to use |
| SMTP_PASSWORD | The associated Entra application's client secret **Value** |
| SMTP_TIMEOUT_SECONDS | `15` |
| MAIL_FROM_ADDRESS | Copy the exact registered MailFrom address under **bushisa-email → bushisa.me → MailFrom addresses** |
| MAIL_FROM_NAME | `Bushisa` |
| MAIL_REPLY_TO | An existing receiving address, preferably on `bushisa.me`; leave blank if none is configured yet |
| MAIL_OUTBOX_KEY | Base64 encoding of 32 random bytes, separate from the HMAC key |
| SIGNUP_CODE_HMAC_KEY | A separate random secret with at least 32 characters |
| SIGNUP_CODE_LENGTH | `6` |
| SIGNUP_CODE_TTL_SECONDS | `600` |
| SIGNUP_CODE_MAX_ATTEMPTS | `5` |
| STUDENT_EMAIL_DOMAIN_ALLOWLIST | `students.nust.ac.zw` |
| BUSHISA_ALLOW_RUNTIME_DDL | `false` |
| DB_SSL_CA | `/etc/ssl/certs/ca-certificates.crt`, or an absolute path to a trusted CA bundle with Azure MySQL's current roots |

The SMTP username is an authentication identity, independent of the From email
address. Do not substitute the app registration's display name, Secret ID, ACS
connection string or an Azure resource key. An arbitrary `noreply@bushisa.me`
address is not authorized merely because you own the domain.

Keep SMTP/database passwords and both keys in Azure Key Vault and use App Service
Key Vault references. Configure identity access and check that references resolve.
The actual values are never included in the patch or committed to Git.

Generate the two independent application keys using PHP on your own machine:

```bash
php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Use the first output for MAIL_OUTBOX_KEY and the second for SIGNUP_CODE_HMAC_KEY.
Keep these values stable across instances and restarts. A key change affects
outstanding mail/challenges; drain or explicitly invalidate them before rotation.
Mark secrets as deployment-slot settings if using slots with different resources.

## 3. Database settings and migration

After this patch, the existing `AZURE_MYSQL_*` settings from the portal are
supported. Explicit `DB_*` settings override them:

| Existing Azure setting | Equivalent explicit setting |
|---|---|
| AZURE_MYSQL_HOST | DB_HOST |
| AZURE_MYSQL_PORT | DB_PORT |
| AZURE_MYSQL_DBNAME | DB_NAME |
| AZURE_MYSQL_USERNAME | DB_USER |
| AZURE_MYSQL_PASSWORD | DB_PASSWORD |

Use your current server/database names. There is no need to duplicate both sets.
Remove or correct conflicting local-development `DB_*` values if present.
`AZURE_MYSQL_FLAG=MYSQL_CLIENT_SSL` is not a PDO TLS configuration.

Production requires a non-root account, a password and verified TLS. Confirm
that the selected CA bundle includes **DigiCert Global Root G2** and **Microsoft
RSA Root Certificate Authority 2017**, following the current Azure MySQL guide.
The database host must be its Azure DNS name. Leave the server's
`require_secure_transport` enabled, and grant the app the necessary network
access through the existing firewall/VNet configuration.

Take a database backup, then use a migration identity with DDL permission to run:

```bash
php bin/migrate.php
```

Migration `004_smtp_outbox` adds the challenge reference, retry count and next
attempt time, and permits erasing terminal code verifiers. The command handles both fresh bootstrap and existing databases
and can be rerun. Restore the runtime identity afterwards; keep runtime DDL false.
Do not import `database/bushisa.sql` into a live database: it drops tables and is
used only to bootstrap the isolated CI database.

## 4. Schedule the sender

The app queues mail transactionally; the dispatcher sends it. Configuring SMTP
without running the dispatcher leaves messages pending.

For the existing Linux PHP App Service:

1. Enable **Always On** on an eligible App Service plan.
2. Add app setting `WEBSITE_SKIP_RUNNING_KUDUAGENT=false`.
3. Use the supplied `Bushisa-mail-webjob.zip`, or ZIP
   `deploy/mail-webjob/run.sh` and `settings.job` together with both files at the
   ZIP root.
4. Open **Bushisa → WebJobs → Add**. Select **Triggered → Scheduled**, upload
   the ZIP and use the six-field schedule `0 * * * * *` (every minute).
5. Check the job logs and manually run it once after migration/settings are ready.

The script expects the deployed application, PHP and Composer dependencies at
`/home/site/wwwroot`. Confirm that path in your actual build. If using a packaged
runtime with a different path, update the script before uploading.

The same CLI commands can run in another scheduler with the correct app
environment/database access if WebJobs or Always On is unavailable:

```bash
php bin/cleanup_policy.php
php bin/dispatch_mail.php
```

These commands reject HTTP execution. No public URL triggers email delivery.
The dispatcher defaults to ten candidates per run and uses a database mutex to
prevent overlapping workers. Azure's initial custom-domain quotas are
30 emails/minute and 100/hour per subscription; ten candidates per run is not a
promise of 600 accepted emails/hour. Confirm your actual quota and monitor demand.

## 5. Check one authorized student mailbox

After deployment, use your own approved test student account:

1. Register with its institutional address and request one code.
2. Check WebJob counters and inbox/spam. Inspect the received From address and
   SPF/DKIM/DMARC authentication results.
3. Enter the code once; the account should move to pending onboarding.
4. Requesting a newer code must cancel the old queued generation. Expired codes
   and provider outages must leave accounts unapproved.

The worker's `sent` status means **SMTP accepted**, not inbox delivery. Inspect
ACS delivery/bounce diagnostics separately. Failed rows contain only safe reason
categories, never the raw SMTP response, credentials or approval code.

Transient errors retry up to five attempts with backoff, bounded by the original
expiry. Accepted, expired, superseded, consumed, exhausted and permanently failed
payloads are erased. SMTP acceptance never verifies a user.

Dispatch keeps account/challenge locks during SMTP operations to coordinate with
resend and verification; socket timeouts limit stalls. SMTP and the database are
separate systems: a crash after acceptance and before database commit can cause a
duplicate email. Message-ID stays stable, but provider deduplication is not
guaranteed. Resend cooldown/shared issuance budgets and the repository's other
P0/P2 release checks remain separate work.

## Local validation

Prepared against repository commit `bca0bcc9e4f16d5d19137646c2bfd853429f286e`.
The patch has not been pushed to GitHub or deployed to Azure.

Checked locally with PHP 8.3.6 and MySQL 8.0.36:

- PHP syntax and signup/configuration unit tests.
- Actual PHPMailer SMTP exchange with a local TLS fixture, including rejection
  of unavailable TLS and untrusted certificates.
- Fresh migrations, rerun, and an upgrade preserving existing fake-mail data.
- Existing signup and policy-safeguard database regressions.
- Encrypted outbox, acceptance/erasure, retry/expiry, resend cancellation,
  consumed/replayed codes, concurrent worker exclusion, tampering and failed
  resend rollback.

The database checks also exposed and corrected two existing compatibility
problems: a non-null verifier column prevented terminal erasure, and comparing a
TIMESTAMP to an empty string caused strict MySQL to reject account cleanup.
GitHub CI is configured for PHP 8.4 and MySQL 8.0.

Actual Azure database TLS/network access, Key Vault references, SMTP credentials
and student-inbox delivery still require a staging check with the operator's
resources and authorized test mailbox.

## References

- [ACS SMTP authentication](https://learn.microsoft.com/en-us/azure/communication-services/quickstarts/email/send-email-smtp/smtp-authentication)
- [Azure MySQL verified TLS](https://learn.microsoft.com/en-us/azure/mysql/security/security-tls-how-to-connect)
- [PHP on App Service](https://learn.microsoft.com/en-us/azure/app-service/configure-language-php)
- [Scheduled WebJobs](https://learn.microsoft.com/en-us/azure/app-service/tutorial-webjobs)
- [ACS email limits](https://learn.microsoft.com/en-us/azure/communication-services/concepts/service-limits)
- [ACS retirement and changes](https://learn.microsoft.com/en-us/azure/communication-services/acs-retirement-and-breaking-changes-guide)

This integration continues the ACS setup already selected for Bushisa. It does
not change the pending release/policy decisions or certify the application for
production launch. Confirm the provider retirement guidance before committing to
long-term infrastructure.
