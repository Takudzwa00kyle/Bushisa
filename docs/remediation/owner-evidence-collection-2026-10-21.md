# Owner-run collection, existing resources

**Progress update: all 01-08 files received and reconciled.** Schema details
[match the reconstruction](owner-schema-supplement-comparison-2026-10-21.md);
actual worker location/hashes, application allowlist and local archive findings
are accepted in the [completed evidence record](completed-owner-evidence-reconciliation-2026-10-21.md).
Do not repeat steps 1-4 below. They preserve the original collection procedure
for reference, not a new request. The next smallest action is the bounded
[consolidated code-gap collection](remaining-code-evidence-collection-2026-10-21.md#owner-action-in-execution-order),
saved as 09 beside the existing parent-directory evidence files. Capture 07
establishes schedule equivalence and 08 adds bounded names/classification; do not
repeat them or retrieve installed.php again. Hostingstart remains default-deferred.
No SQL, worker run or artifact download is requested.

Prepared from clean `888a2c273711c1ac5f3d1dc3d26d649f1e636235` on
`codex/direct-production-release-prep-20261020`. Commands below are **prepared,
not executed against Azure by the agent**. PR 8 stays draft. No code, grants,
settings, migration, worker, cleanup or delivery is activated. Direct production
remains the selected approach; managed staging stays DEFERRED/18 cases NOT RUN.
SC26 production topology stays PARTIAL. Prior CI attribution is unchanged.

## 1. First collection: verify the already connected schema

**Completed by the owner; retained for reference.** This ran in the MySQL/MariaDB prompt of the existing certificate-verifying
connection that produced the DOCX. This is not a Bash or IntelliJ command:

```sql
SELECT DATABASE() AS selected_schema,
       UTC_TIMESTAMP(6) AS captured_utc,
       CONVERT_TZ(UTC_TIMESTAMP(6), '+00:00', '+02:00') AS africa_harare;
```

Expected selected schema: **bushi-database**. Stop on a different schema or a
connection whose TLS verification is unknown. Do not reconnect using weaker
TLS or print credentials. If the connection has ended, reuse the owner's
existing trusted connection procedure; do not copy passwords into commands.
Save just this result as `01-schema-context.txt`, excluding login/prompts with
account identifiers. This is a new observation time, not an approval timestamp.

**Local IntelliJ terminal — PowerShell**, in this repository: prepare private
local evidence storage, outside Git. These commands write only on your PC:

```powershell
$EvidenceDir = Join-Path $env:USERPROFILE 'Documents\Bushisa-release-evidence\2026-10-21'
New-Item -ItemType Directory -Path $EvidenceDir -Force | Out-Null
# After copying only the result block above:
Get-Clipboard -Raw | Set-Content -LiteralPath (Join-Path $EvidenceDir '01-schema-context.txt') -Encoding UTF8
```

Keep this directory private. After each later block, copy its result only and
use the same save command with the indicated filename. Do not copy terminal
history, environment dumps, raw application logs or connection details.

## 2. Minimal physical-schema supplement

Use the existing [supplemental-schema-metadata.sql](../../deploy/release/supplemental-schema-metadata.sql)
unchanged. **IntelliJ terminal — PowerShell:**

```powershell
Get-Content -LiteralPath deploy/release/supplemental-schema-metadata.sql -Raw | Set-Clipboard
```

**MySQL prompt:** paste that SQL after step 1 selects the expected schema.
It uses only information_schema SELECTs inside START TRANSACTION READ ONLY /
ROLLBACK. Do not paste it into Application SSH Bash; do not run migrations.
Do not substitute SQL from the Word transcript. Save results, including empty
sets and errors, as `02-schema-supplement.txt` in the local evidence directory.

| Only missing information requested | Expected output and reconciliation |
|---|---|
| FK child/referenced columns and ordinal mapping | 13 existing constraints' mapping rows, including positions. Existing names/parent/update/delete rules are already captured. Replaces the baseline-derived mapping assumption. |
| Index direction, visibility and expression | Attributes for the existing 122 index entries. Ordinary ASC/visible/no-expression is the current reconstruction assumption, not an observation. |
| SQL NULL versus literal text `NULL` defaults | Boolean `default_is_sql_null` for ambiguous character defaults. 1 means SQL NULL; 0 means the literal string. Other defaults are already captured. |
| CHECK clauses and enforcement | Empty only if none exist and the authorized capture has sufficient visibility. If present, their definitions must be reproduced. |
| ROW_FORMAT / CREATE_OPTIONS | One row per captured table (29 absent drift). Identifies options not established by InnoDB/collation alone. |
| Partitioning/subpartitioning presence and expressions | Empty only if unpartitioned. Any returned partition requires targeted SHOW CREATE TABLE for that table's boundaries/options. |

No new column/default/index/FK-rule inventory, migration ledger, version, grants,
TLS or user-content export is needed. Existing 227/122/13 comparisons remain
valid for their captured attributes. An error or unexpected count stays UNKNOWN.
Only for a conflicting/partitioned/otherwise unresolved **named table**, ask
engineering to select the exact `SHOW CREATE TABLE table_name;` follow-up; do
not dump all tables or data. Auto-increment counters, cardinalities and row
counts are not needed for this synthetic schema reconstruction.

## 3. Locate the installed worker before hashing

**Kudu browser, read-only GET:** Azure Portal > Bushisa > Advanced Tools > Go.
Keep the actual authenticated SCM origin; do not invent a hostname or obtain
publishing credentials. In that browser origin open
`/api/triggeredwebjobs/bushisa-mail` (GET for this job only).
The [Kudu WebJobs API](https://github.com/projectkudu/kudu/wiki/WebJobs-API)
documents this GET and its job metadata. Do not use POST/PUT/DELETE or Run.

Find **bushisa-mail**, type triggered. Save only name, type and the script-path
field (`run_command` / `runCommand`, depending on response) as
`03-worker-location.txt`. Record UTC observation time and which SCM surface
returned it. If the endpoint is unavailable or the response does not identify
this job, stop and record UNKNOWN. Do not copy settings, history/log bodies, command arguments or
URLs containing credentials/tokens. If the command has arguments, retain only
the path after private review; never paste the full command into a shell.

**Kudu Debug Console — Bash**, not Application SSH: use the file browser to
locate the reported job. The documented conventional location below is a
**candidate to check**, not an asserted observation:

```bash
date -u '+capture_utc=%Y-%m-%dT%H:%M:%SZ'
if test -d /home/site/wwwroot/App_Data/jobs/triggered/bushisa-mail; then
  find -P /home/site/wwwroot/App_Data/jobs/triggered/bushisa-mail \
    -maxdepth 1 -type f -printf '%f\n'
else
  printf 'STANDARD_JOB_DIRECTORY_NOT_FOUND\n'
fi
```

This lists filenames only inside the dedicated job directory. If the API gives
another location, inspect that exact job directory in Kudu instead. If it gives
only `run.sh` and the candidate location is absent, stop: the actual location
remains UNKNOWN. Do not search all of /home, inspect process environments,
read job output/history or substitute `wwwroot/deploy/mail-webjob/run.sh`.
If Kudu has no Bash console, use its file browser to record the resolved path
and report that limitation; do not install tools or trigger the job.

**Kudu Bash, only after API/file-browser correlation establishes the path:**

```bash
# Replace this literal with the verified absolute installed job directory.
job_dir='/REPLACE_WITH_VERIFIED_INSTALLED_JOB_DIRECTORY'
case "$job_dir" in /REPLACE_*|'') printf 'STOP_UNVERIFIED_JOB_PATH\n'; exit 1;; esac
test -d "$job_dir" && test ! -L "$job_dir" || exit 1
printf 'BEGIN_INSTALLED_WEBJOB_IDENTITY\n'
date -u '+capture_utc=%Y-%m-%dT%H:%M:%SZ'
cd -- "$job_dir"
printf 'physical_job_directory=%s\n' "$(pwd -P)"
for p in run.sh settings.job; do
  if test ! -e "$p"; then printf 'MISSING %s\n' "$p"; continue; fi
  test -f "$p" && test ! -L "$p" || exit 1
  before=$(stat -c '%d:%i:%s:%y:%z' -- "$p")
  result=$(sha256sum -- "$p")
  after=$(stat -c '%d:%i:%s:%y:%z' -- "$p")
  test "$before" = "$after" || { printf 'STOP_CHANGED %s\n' "$p"; exit 1; }
  printf '%s\n' "$result"
done
date -u '+capture_end_utc=%Y-%m-%dT%H:%M:%SZ'
printf 'END_INSTALLED_WEBJOB_IDENTITY\n'
```

Save as `04-installed-webjob-sha256.txt`. If the actual executable has a different
name, stop and return the location metadata; do not guess. Extra package files
must be classified privately as code/settings/secrets before any further
hashing. Do not print script/settings bodies. Hash matches can establish the
known script's content and its literal working directory/CLI targets from Git.
An unmatched hash leaves that behavior unresolved until a private, sanitized
code review; never execute a script merely to identify it. Two file hashes alone
do not certify a larger package or the application files it invokes.

## 4. Collect the application code manifest, separately

**IntelliJ terminal — PowerShell:** generate a command block from the union of
known baseline/candidate source paths, plus explicit dependency-code paths:

```powershell
$block = & ./deploy/release/prepare-owner-code-identity.ps1
[IO.File]::WriteAllText((Join-Path $EvidenceDir '05-app-collection-command.sh'), $block, (New-Object Text.UTF8Encoding($false)))
$block | Set-Clipboard
```

This local helper reads Git metadata and emits Bash; it does not connect to
Azure, inspect local secrets or run the generated commands. **Azure Application
SSH — Bash:** paste the generated block directly. No script is uploaded or
written on the service. It only reads regular reviewed code files beneath
/home/site/wwwroot, with symlink/changed-file refusal, hashes and timestamps.
It never loads the application/autoloader, calls a route, connects to MySQL or
dispatches mail. Save output as `05-application-code-sha256.txt` locally.

`MISSING` is evidence, not an instruction to install a file: candidate-only
paths should be absent on the earlier release. The corrected sign-in probe is
login.php. Do not repeat the old standalone nine-file probe. This broader
manifest is needed to check all known code, not infer a source SHA from samples.
If any STOP appears or the BEGIN/END block is truncated, retain the error and
leave the capture incomplete. Do not suppress an error or loosen a guard.

The allowlist deliberately excludes .env, local configs, keys, uploaded media,
session storage, logs, mail/proof data, Git metadata, test data and arbitrary
unknown files. Separately use Kudu's file browser to identify **unexpected code
names only** in the deployed source directories, excluding all private/dynamic
directories. Do not hash unknown files until classified. The helper lists known
PHPMailer/Composer paths; unexpected dependency packages or customized autoload
targets remain a separate code-manifest gap, not an assumed lockfile match.

If an existing privately preserved **actual deployed** application or WebJob
archive is available, **IntelliJ PowerShell** may hash that local file:

```powershell
Get-FileHash -LiteralPath 'C:\REPLACE_WITH_EXISTING_PRIVATE_ARTIFACT.zip' -Algorithm SHA256
```

Record its acquisition/deployment linkage and digest in
`06-artifact-provenance.txt`; do not share the archive or assume any arbitrary
old ZIP is the deployed package. Otherwise record NO_PRESERVED_ARTIFACT. Do not
create a full-site ZIP/download: it could include credentials/user data.
Record the active deployment ID from Deployment Center only if checking it
changed during capture; do not collect secret-bearing configuration. A UUID is
not a source SHA. Live file hashes are observations, not a globally atomic
snapshot, immutable previous bundle, rollback approval or independent recovery.

## 5. Why the old rollback is unsafe

The reproduced old API accepted a new message request but did not write the
new durable pair ledger. After returning to the newer app, the current request
and the safety/lifetime history can disagree. Restart and budget decisions can
then lack required evidence. The safer new app refuses incomplete history;
it must not treat an unrecorded old action as permission. Copying the old PHP
files back does not undo the upgraded schema or preserve new protective actions.

This instruction applies **during a separately approved maintenance/rollback**,
not to the current read-only collection: keep all traffic that can write stopped,
including logged-in UI/API, lazy-expiry GET routes, admin/moderation/support,
signup/verification/onboarding/reset/session changes, old and new WebJobs,
dispatch/cleanup, migrations/direct SQL and any recovery/import worker. Read-only
pages can still run lazy writers; maintenance must fence server-side requests,
not merely hide buttons. Reconcile already in-flight SMTP; no DB action recalls
a message already accepted by the provider. Do not stop/change the live service
as part of these collection steps.

Before traffic reopens, require the exact previous app/worker or reviewed forward
repair to pass real-route synthetic tests on the forward schema, including all
pair writers, budgets/restarts, block clearance, current revocations and session
authorization. Also require observed all-writer cutover, reconciled legacy
eligibility, consistent code/worker identities, reviewed grants/settings, and
the fresh backup/protective-replay/recovery acceptance in the
[release package](direct-production-release-preparation-2026-10-20.md).
OS17 remains INCOMPATIBLE for the known baseline; actual previous artifact
compatibility and witnessed recovery remain NOT RUN. Keep maintenance rather
than reopening an unproved fallback.

## 6. Independent preparation ready for the collected evidence

| Workstream | Concrete local preparation / next review requirement |
|---|---|
| Role separation | Existing restricted migration and worker grant evidence is retained. Review web DB_USER/DB_PASSWORD separately from CLI MAIL_WORKER_DB_USER/MAIL_WORKER_DB_PASSWORD; keep DB_HOST/DB_NAME/DB_SSL_CA fixed and TLS verified. Complete full-D03 write/negative-grant cases before switching the runtime. No global administration, GRANT OPTION or runtime DDL as a workaround. |
| Definers and secrets | Controlled migrator creates fixed health routines/triggers; retain their legitimate definer identity/rights through credential restriction. Do not drop it blindly. Record secret delivery/location/access as references only; shared App Service names are not proof of credential isolation. Actual reviewers, host scopes and secret placement remain unaccepted/unverified. |
| Legacy pair cutover | Use the [L02b writer inventory/boundary contract](l02b-legacy-history-import-contract.md#3-writer-inventory-at-6001497). Add current L06 reset/session-version writers and separated mail/Q11/retention paths to the observed package map. The historical inventory alone is not proof every deployed writer is covered. Preserve inactive epoch and incomplete history; no production activation/import command is prepared. |
| Signup continuation | Separate active legacy accounts, pending-email with trusted creation, approved-email with trusted first approval, and unknown-anchor attempts. Known anchors retain their original Q11 deadlines. Unknown anchors stay explicit; fresh resends/visits do not create replacement clocks. Untracked old mail cannot be relabelled as a new authorized envelope. No user-content export or automatic backfill is needed for this collection. |
| Recovery acceptance | Prepare a witness sheet for DB/media/config/previous code/worker/protective-record sources, copy expiry, verified restore/replay, session revocation and stale-mail reconciliation. Record protection/access references, no keys or data. Use actual backup/media bounds plus seven-day recovery margin; do not blindly apply 49 days to the observed seven-day backup policy. No backup/restore/export is executed here. |

The six result files/limitations have been returned through the owner's private
evidence route, with no credentials, user rows or bodies. No new 48-question
review or reapproval of settled decisions is needed. Role acceptance, authentic
legacy provenance/continuation, effective storage, qualified reviewers, delivery,
coverage and recovery remain distinct gates. Candidate 888a2c2's passing CI is
reused; these documentation/collection preparations do not certify production.

Local preparation checks: the helper emits one LF-normalized, UTF-8-without-BOM
Bash block with **147 reviewed target paths**; private/dynamic path exclusion
checks and `bash -n` passed in the owned local Linux container. The generated
block was **not executed against Azure or a database**. Documentation-link and
`git diff --check` validation are documentation checks, not production acceptance.
