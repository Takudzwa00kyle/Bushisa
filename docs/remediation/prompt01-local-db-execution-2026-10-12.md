# Prompt 01 local database execution checkpoint

Evidence received through **12 October 2026, 04:39 Harare (UTC+02:00)**.

Status: selected local bootstrap, mail and DB regression checks passed. This
checkpoint records operator terminal evidence; P0/P1 release acceptance remains
open. No production action is established by these local results.

## Tested working copy

- Repository: `Takudzwa00kyle/Bushisa`.
- Baseline HEAD: `6ed126d31ce6d2a5e046cf0ace98744345a8e934`.
- Test-time branch/status at 04:14: `main...origin/main`, with the modifications
  listed below.
- At 04:39 the operator created `codex/prompt01-db-validation-20261012` and
  supplied status showing the same modified/untracked file list on that branch.
  A candidate commit has not yet been supplied.
- Tests ran against the modified working tree. The baseline SHA identifies the
  committed starting point; a commit containing the tested changes is still to
  be created. Preserve the changes and record that candidate commit before the
  next implementation task changes its source.
- Full changed-file contents/diff were not supplied for independent review.

```text
 M .github/workflows/ci.yml
 M database/seed.php
 M docs/remediation/backlog.md
 M docs/remediation/d04-d05-implementation-evidence.md
 M tests/browser/p0-rendered-flows.spec.js
 M tests/integration_core_p0_flows.php
 M tests/integration_p0_flows.php
 M tests/integration_smtp_outbox.php
 M tests/overlap_db_p0_flows.php
 M tests/policy_safeguards_p0_flows.php
 M tests/smtp_transport_test.py
 M tests/unit_mail_configuration.php
 M tests/workers/db_race_worker.php
?? docs/Bushisa-P0-P2-Continuation-Prompts-2026-10-11.md
?? tests/bootstrap_database.php
?? tests/fixtures/
?? tests/support/
?? tests/unit_disposable_database_guard.php
```

## Local environment and isolation

| Item | Observed value / evidence |
|---|---|
| Runtime | Windows Git Bash; PHP CLI 8.5.11; Python 3.13.5. |
| PHP configuration | `D:\php\php.ini`; OpenSSL enabled; Composer installed PHPMailer v7.1.1 from the lock file and generated the autoloader. |
| MySQL server | `SELECT VERSION()` returned 9.6.0; TCP `127.0.0.1:3306`. |
| Disposable database | `bushisa_test_local_20261012`. |
| Dedicated principal | `bushisa_test_20261012@localhost`; USAGE globally and ALL on the exact escaped-underscore test schema only. `partial_revokes=0` was shown. |
| Restored-shell preflight | Selected database and authenticated account matched the two entries above. |
| Test environment | `APP_ENV=development`, `APP_DEBUG=false`, `BUSHISA_TEST_DATABASE=true`, explicit local DB target/principal, `MAIL_PROVIDER=fake`, runtime DDL disabled. |
| Credentials | Test password entered through a hidden prompt and exported temporarily; no password or production key is recorded here. |

The dedicated account is a test-schema setup/fixture principal. Its schema-level
ALL grant does not demonstrate a restricted production runtime role. Azure PHP
was earlier observed as 8.5.6; the Azure MySQL server version remains unrecorded.
This local version combination does not establish the approved production matrix.

## Executed results

All results below were supplied by the operator. The report times identify when
the outputs were received, rather than measured test execution durations.

| Command / scenario | Outcome | Supplied evidence / report time |
|---|---|---|
| `php tests/bootstrap_database.php` | PASS | `Disposable database bootstrapped`, earlier 12 October setup. |
| `php bin/migrate.php` | PASS | Applied 001–004; `Migrations complete`. |
| Second `php bin/migrate.php` | PASS | Skipped 001–004; `Migrations complete`. |
| `php tests/unit_mail_configuration.php` | PASS | `unit_mail_configuration PASS`, 03:07 Harare. |
| `python tests/smtp_transport_test.py` | PASS | `smtp_transport_test PASS`, 03:26 Harare. |
| `php tests/integration_p0_flows.php` | PASS | `integration_p0_flows PASS`, 03:53 Harare. |
| `php tests/integration_core_p0_flows.php` | PASS | `integration_core_p0_flows PASS`, 03:56 Harare. |
| `php tests/policy_safeguards_p0_flows.php` | PASS | `policy_safeguards_p0_flows PASS`, 03:58 Harare. |
| `php tests/integration_smtp_outbox.php` | PASS | `integration_smtp_outbox PASS`, 04:00 Harare. |
| `php tests/overlap_db_p0_flows.php` | PASS | `overlap_db_p0_flows PASS`, 04:09 Harare. |

The core suite includes direct synthetic DB fixtures and service calls. The
overlap suite starts separate PHP DB workers for match creation and duplicate
swipes. These results do not demonstrate multi-worker HTTP route overlap or
browser/session behavior.

## Resolved local blockers and test fixes

1. Local MySQL access was established with the dedicated disposable principal
   rather than the earlier unsuccessful blank-password root attempt.
2. PHP OpenSSL was enabled. Missing bundled DLLs beside `php.exe` were identified
   during Composer setup; matching PHP ZIP restoration was instructed. Composer
   installation and subsequent PHP mail tests succeeded. Post-restoration
   OpenSSL/cURL versions were not separately reported.
3. The mail settings test cleared `DB_SSL_CA`, then relied on the Linux system
   bundle path. The supplied portability patch selects a public synthetic CA
   fixture at `tests/fixtures/mail_configuration_ca.pem` inside the settings-only
   test. That fixture must not be used for a real database connection.
4. SMTP diagnostics showed PHP exit 0 with `rejected` while the local server was
   still waiting in its first `accept()`. The fixture listens on IPv4, so its PHP
   client now uses `127.0.0.1` and its generated certificate SAN includes
   `DNS:localhost,IP:127.0.0.1`. The real PHP fixture then passed all three
   existing trusted/no-STARTTLS/untrusted-CA scenarios. Windows DNS ordering was
   not independently captured; TLS verification remains enabled.

The assistant separately checked patch applicability/whitespace, public CA
parsing, Python syntax and the SMTP fixture using a Python TLS client. Actual PHP
suite PASS evidence above comes from the operator's Windows execution.

## Remaining evidence and next work

- Capture the candidate commit and sanitized changed-file evidence; retain this
  working-tree checkpoint until a commit containing the tested source exists.
- Link this checkpoint from the existing backlog and D04/D05 evidence without
  deleting their historical failures or turning proposed policy into approval.
- Rendered browser reset/flows, real route/session revocation and genuine
  multi-worker HTTP overlap remain NOT RUN; continue Prompt 02 using the guarded
  disposable environment and supported server setup.
- Legacy/interrupt migration scenarios, restricted runtime-role checks, the
  approved production-version matrix, CI execution and isolated restore remain
  incomplete. Earlier CI billing blockage has not been rechecked here.
- D04/D05 owner approval and the P0/P1 gates remain open.
- Application Insights integration/collection stays deferred until D10 review.
- Password reset using `DoNotReply@bushisa.me` remains requested work; this
  checkpoint does not implement or deploy it.

Preserve the current disposable DB setup. Every later reset must intentionally
use the guarded disposable interface. Live Azure `bushi-database` remains
outside these regression/reset operations.
