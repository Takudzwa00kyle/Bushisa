# Completed owner evidence: direct-release reconciliation

Recorded 21 October 2026, Africa/Harare, from preserved checkout
`ba800431a9bcecddb2645f9b0a267948c54dbe25` on
`codex/direct-production-release-prep-20261020`. **Deployment recommendation:
NO-GO.** This checkpoint accepts the returned evidence; it does not execute a
release or reopen settled policy. Direct production using existing resources
remains the owner's approach. Managed staging stays **DEFERRED / AZS01-AZS18
NOT RUN**, and SC26 production storage/topology stays **PARTIAL**.

## Sources accepted and limits

The nine files were found in the repository's parent directory. No missing-file
request, repeated production collection, SQL execution, worker execution or
application-regression rerun was needed. The original DOCX and supplement hashes
are unchanged. Capture 07 adds a local ZIP comparison and synthetic validation
of the next metadata reader, separately from application acceptance.
All source sizes/digests and per-file comparisons are in the
[sanitized reconciliation](evidence/completed-owner-evidence-reconciliation-2026-10-21.json).
Original files remain private and unmodified, outside Git.

| Source | Accepted observation / attribution |
|---|---|
| Original `MigrationHistoryWithActualSchemaData.docx` | Owner-collected production metadata; original 227 columns, 122 index entries, 13 FK rules, 29 tables, migrations 001-004, versions/settings/grants and sampled hashes. Reuse the [observed-schema rehearsal](owner-schema-release-rehearsal-2026-10-20.md); no SQL from the document was executed. |
| `01-schema-context.txt`, `02-schema-supplement.txt` | Owner MySQL metadata. Context: `bushi-database`, **2026-10-20 23:11:13.709855 UTC / 2026-10-21 01:11:13.709855 Africa/Harare**. Individual supplement-query times were not recorded. |
| `03-worker-location.txt` | Owner Kudu job metadata identifies triggered `bushisa-mail`, `run.sh`, and scheduler text `0 * * * * *`. The copied text has extra closing syntax and is not valid standalone JSON; fields are treated as recorded text, not repaired or executed. Recorded last run: **2026-10-20 23:43:00.0735028-23:43:01.6998039 UTC / 2026-10-21 01:43:00.0735028-01:43:01.6998039 Africa/Harare**, Success. This is not a capture timestamp, delivery or cleanup proof. No history/output URLs were fetched. |
| `04-installed-webjob-sha256.txt` | Owner installed-file capture at **2026-10-20 23:50:49 UTC / 2026-10-21 01:50:49 Africa/Harare**, actual directory `/home/site/wwwroot/App_Data/jobs/triggered/bushisa-mail`; both file hashes below. |
| `05-application-code-sha256.txt` | Owner file manifest at **2026-10-21 00:00:29-00:00:35 UTC / 02:00:29-02:00:35 Africa/Harare**, physical root `/home/site/wwwroot`. Complete BEGIN/END block, no recorded STOP; 93 digests and 54 MISSING paths. This is not an atomic deployment snapshot. |
| `06-artifact-provenance.txt` | Prior **local agent read-only inspection**, recorded **2026-10-21 00:26:32.5241896 UTC / 02:26:32.5241896 Africa/Harare**. This is not an independent Azure capture or an owner approval timestamp. Reuse its archive inspection; archives were not reopened or executed for this reconciliation. |
| `07-installed-settings-summary.txt` | Owner Application SSH read-only capture at **2026-10-21 11:35:51 UTC / 13:35:51 Africa/Harare**. Same installed digest as 04; one property, only the recorded minute schedule; no BOM, CRLF or LF. The capture was read locally; Azure collection was not repeated. |
| `08-deployed-code-names.txt` | Owner metadata capture at **2026-10-21 12:41:11 UTC / 14:41:11 Africa/Harare**: 190 visited, 10 excluded, 166 rows (154 code paths/12 top directories), no STOP. All 93 capture-05 paths are present; 61 additional names are locally classified. See the [inventory reconciliation](deployed-code-inventory-reconciliation-2026-10-21.md). |
| `09-code-gap-evidence.txt` | Owner Application SSH capture **2026-10-21 14:28:23–14:28:24 UTC / 16:28:23–16:28:24 Africa/Harare**: 65 new hashes all match, three missing reviewed audit helpers, 13 new names and deferred hostingstart; no STOP. See the [complete delta reconciliation](code-gap-reconciliation-2026-10-21.md). |

No file-save time, recording time or last job run is substituted for historical
owner acceptance. Previously accepted decisions remain accepted despite an
uncaptured exact acceptance timestamp.

## Physical schema: requested assumptions resolved

The [completed supplement comparison](owner-schema-supplement-comparison-2026-10-21.md)
confirms **all six previously assumed metadata groups** against the guarded
observed-schema reconstruction:

| Attribute | Supported result |
|---|---|
| FK child/referenced columns and ordinal positions | **13/13 MATCH**; baseline-derived mappings are now supported by owner observations. |
| Index direction, visibility and expression | **122/122 MATCH**: ascending `A`, visible `YES`, expression SQL NULL. |
| Ambiguous character defaults | **51/51 SQL-null metadata defaults MATCH**: 13 nullable columns default NULL; 38 NOT NULL columns have **no explicit default**, not `DEFAULT NULL`. |
| CHECK constraints | Captured empty set; none on the 29 original reconstructed tables. |
| Table options | **29/29 MATCH**: ROW_FORMAT Dynamic, empty CREATE_OPTIONS. |
| Partitioning/subpartitioning | Captured empty set; original tables unpartitioned. |

Together with the original column/type/default/charset/collation, index and FK
rule comparison, this resolves the **requested physical-metadata fidelity gap**.
No further SHOW CREATE TABLE or repeat schema audit is requested from these
results. Captures are observations at their respective times, not proof of no
later drift, globally atomic snapshot, complete artifact identity or Azure
execution under new restricted principals. No new migration, NULL-history or
restore test is claimed. Historical fixture/report PARTIAL labels retain their
original scope; **DR-P01 remains PARTIAL for actual Azure restricted grants,
definers, PHP PDO TLS and managed-service behavior**. The existing
`protective_replay_events` plain timestamps remain an observed pending-upgrade
fact; nothing was migrated on production.

## Application and installed worker comparison

The offline comparison used Git object bytes, without checking out/running old
code or loading captured dependencies. Comparator:
`0bf311fb6d6a298712fd4cd16d0722305516edd4`; proposed tested source:
`888a2c273711c1ac5f3d1dc3d26d649f1e636235`, with implementation
`3dbcb50bbb8e3401e2d7688143c4d406dbf0d426`. Local command:
`D:/cygwin64/bin/python3.9.exe .tmp/reconcile-owner-captures.py`.
This ignored helper reads bounded metadata and Git blobs, not production data.

| Coverage | Result and implication |
|---|---|
| 75 captured Git-tracked application paths | **75/75 raw-byte matches to `0bf311f`**. No newline normalization was needed. 45 also match `888a2c2`; the other 30 differ from that candidate. No unmatched tracked file was found within this allowlist. |
| 54 MISSING paths | None is a baseline-tracked target. 53 are candidate-tracked additions, including migrations 005-016 and new flows; the remaining path is `vendor/composer/autoload_files.php`. Missing files were not installed or treated as unexpected drift. |
| 18 captured dependency paths | 17 raw-byte local matches, including seven PHPMailer source files. Capture-08 local comparison now accounts for `vendor/composer/installed.php`: changing the two local generated root-reference fields to baseline `0bf311f` yields its exact captured digest. This is one constructed metadata match, not an 18th raw local match or whole deployment identity; no further production read is needed for that discrepancy. |
| Lockfile | Captured baseline `composer.lock` matches Git; it declares `phpmailer/phpmailer` **v7.1.1**, source reference `1bc1716a507a65e039d4ac9d9adebbbd0d346e15`. A lockfile/local dependency match is not proof of the complete installed dependency tree or authenticated build. |
| Installed `run.sh` | SHA-256 `8549c2b452b03a883dffbb0752ff33b35c6f86aa137f85255a8d42ce72fa6015` matches baseline bytes and the Git blob introduced by `634fa92d6092918c5b3e091736ab28ab9b0b4b49`. It differs from the candidate wrapper. |
| Installed `settings.job` | SHA-256 `0ae4c9493350ff029b823ec7b457ad61be2b8f515ab2116d78d2b9cb294ef501` matches no available history blob at `deploy/mail-webjob/settings.job`, including tested LF/CRLF variants. Capture 07 now establishes exactly `{"schedule":"0 * * * * *"}` as its parsed semantics. Locally serialized compact JSON (26 bytes) hashes to this same installed digest; the formatted ZIP remains byte-different. |

**Complete hosted source identity remains UNESTABLISHED.** The known-code
manifest supports a baseline comparison for those paths; it does not exclude
extra/custom code, additional dependencies or changes between captures.
Capture 08 supplies bounded names: 61 additions (Playwright, 59 expected package
paths, unidentified hostingstart.html). Its excluded/untraversed/extension gaps
are explicit in the [inventory record](deployed-code-inventory-reconciliation-2026-10-21.md). The application manifest
does not identify the separately installed worker or certify a previous bundle.
No newly fetched Git history, dependency download or production access is claimed.

### Archive findings retained exactly

`D:\Downloads\Bushisa-mail-webjob.zip` (324 bytes) has archive SHA-256
`3ef3ed29d7224bd75baac2dff173c7a7807f61a68b56a72b3da4bc2cb7a28c41`.
Its only entries are `run.sh` (105 bytes) and `settings.job` (34 bytes).

- ZIP `run.sh` exactly matches the installed hash above.
- ZIP `settings.job` hash is
  `0a85fa0a78826caa80c04aad6eb0b47c8fef48c802626986948327bb9438c0f6`;
  it matches baseline/candidate repository settings, **not installed settings**.
  The tested BOM/newline/trailing-newline alternatives also did not match.
- That byte mismatch **does not establish a different schedule**. Owner Kudu
  metadata and capture 07 establish the same minute schedule as the ZIP.
  The local compact serialization hashes to the installed digest, while ZIP
  formatting whitespace accounts for its different representation. The
  historical editing/installation process remains UNKNOWN.
- Deployment provenance for **this exact ZIP remains UNVERIFIED**. Matching
  script bytes/file dates cannot replace an upload/installation receipt.
- **No preserved application deployment archive was found** in the named ZIP
  and inspected `.tmp` locations. The 23 `.tmp` ZIPs are CI result/log archives;
  rehearsal subsets are fixtures. These are not complete rollback packages.
  Other locations/renamed files were not searched or asserted absent.

No replacement historical archive was manufactured, full site downloaded,
original modified or live worker changed.

### Concrete mail rollout consequence

The exact installed wrapper has `set -euo pipefail`, changes to
`/home/site/wwwroot`, calls `php bin/cleanup_policy.php`, then
`php bin/dispatch_mail.php`. In the candidate, broad
[cleanup_policy.php](../../bin/cleanup_policy.php) exits **78 before DB loading**
outside development. **Leaving the old wrapper installed with the new app
would therefore stop before dispatch.** This is a static consequence of the
matched wrapper and candidate guard, not an executed live-worker result.

A future separately approved release must quiesce/reconcile the old worker,
install the reviewed candidate [mail-only wrapper](../../deploy/mail-webjob/run.sh)
and configure accepted dedicated `MAIL_WORKER_DB_USER` /
`MAIL_WORKER_DB_PASSWORD` bindings. Its cleanup failure remains visible without
suppressing otherwise valid dispatch. Do not weaken the broad-cleanup guard,
change the live script now, preserve an old scheduled writer during migration
or enable fake/test controls in production. Pending old signup mail and unknown
Q11 anchors still need the documented continuation plan; do not relabel them
as new authorized envelopes.

## Remaining release gates and independent preparation

| Gate / role | What remains; useful preparation using existing approvals |
|---|---|
| Artifact / engineering + release operator | Installed settings semantics are established by 07; capture 08 classifies 61 added names and accounts for generated dependency metadata; collect only newly justified hashes and remaining boundary/extension names; establish full app/worker/package provenance or explicitly review a forward-repair route. Known source files match baseline, but a complete previous package is not available. No full-site export is requested. |
| Role separation / DB + security reviewers | Actual configured DB_USER has global administration and GRANT OPTION. Review separate runtime, mail worker and migrator grants/host scopes and secret delivery. Finish full-D03 allowed-write/negative-grant cases; retain trusted routine/trigger definers. Different setting names in one App Service do not prove secret isolation. No live grant/config change is authorized. |
| Legacy continuation/cutover / engineering + safety | Preserve known minimum pair counts and incomplete history; keep writer epoch inactive until authentic all-writer boundary evidence and a bounded enablement design exist. Cover web/API, lazy writers, moderation, sessions/reset, imports/recovery and old/new workers. Missing events/anchors are not zero counts or new clocks. Split active, trusted pending and unknown-anchor signup cases without exposing users. |
| Compatible fallback / engineering + owner | OS17 demonstrates that old code can accept a request without pair provenance on the forward schema. Restoring old PHP files cannot undo this. Keep **all** ingress/lazy writers/admin/support/session/signup/reset/old-and-new jobs/imports/direct writers stopped during any separately approved fallback. Reconcile in-flight SMTP. Only an exact-artifact compatibility demonstration or tested forward repair preserving current restrictions permits traffic to reopen; unrestricted baseline rollback stays **INCOMPATIBLE**, actual-artifact DR-P02 **NOT RUN**. |
| Recovery / operations + privacy/safety witnesses | Accept a fresh DB/media/config/worker/session recovery set, expiry/access and independent protective replay before reads/mail. No app archive, independent durability or witnessed recovery is proved here; DR-P03 stays **NOT RUN**. Calculate replay protection from actual recoverable-copy bounds plus seven days; observed seven-day DB backups do not approve a 35-day policy or hard-coded 49 days. |
| Owner/operator and qualified review | Accepted primary/backup, maintenance/abort window, monitoring/delivery/support coverage and privacy/security/safety review remain required. Reset stays default-off pending its activation/throttle acceptance; Q40 proposals remain pending. Full D03 journeys, geography/confessions/media/retention/session gates are preserved. No second paid environment is required for preparation. |

These are engineering/evidence and operational gates, not a new 48-question
policy review. Missing historical provenance cannot be approved into existence.

## Installed settings semantics: capture 07 accepted

Capture 07 has SHA-256
`8eaf1b74bccd942cce68072b757713fc5f2ba535eb5021b89cbd76a7d01708ab`,
253 bytes. Its owner capture instant is **2026-10-21 11:35:51 UTC / 13:35:51
Africa/Harare**, not an approval time. It reports the same installed digest
as 04, `property_count=1`, `only_recorded_schedule=1`, and BOM/CRLF/LF counts 0.
The installed object's semantics are **exactly `{"schedule":"0 * * * * *"}`**.

The agent reread only the 34-byte settings entry from the existing local ZIP,
with bounded ZIP checks, duplicate JSON-key rejection and before/after archive
identity checks; it did not extract or execute it. The parsed ZIP object is
identical. Local compact serialization is 26 bytes and its SHA-256 is
`0ae4c9493350ff029b823ec7b457ad61be2b8f515ab2116d78d2b9cb294ef501`,
matching 04/07. The ZIP's 34-byte representation still hashes to
`0a85fa0a78826caa80c04aad6eb0b47c8fef48c802626986948327bb9438c0f6`.
Thus **semantic equivalence is established; raw ZIP/installed equality is
false**. This supplies no installation/upload receipt or whole-package identity.
No original archive, worker or observation file changed.

Exact command/results and source digests are in the
[settings evidence](evidence/installed-settings-semantics-2026-10-21.json).
Local comparison command:
`D:/cygwin64/bin/python3.9.exe .tmp/accept-owner-settings-07.py`.
The earlier 07 collection is complete. **Do not repeat Azure collection.**

## Captures 08 and 09 accepted; bounded collection complete

The [capture-09 reconciliation](code-gap-reconciliation-2026-10-21.md)
accounts for all 68 reviewed targets and every new filename. All 65 new hashes
match six baseline Git files and 59 locked-package files. Three missing audit
helpers are not baseline runtime dependencies. Captures 05/09 now cover 158
distinct hashes: 81 baseline Git paths and 77 dependency paths, including the
one constructed installed.php metadata match. No further broad production
file collection or hashing of the eight new documentation/licence/version
names is requested. hostingstart stays unclassified; its concrete release
disposition and the bounds of full identity acceptance are explained there.

The deployed source settings copy matches the ZIP; installed App_Data settings
remain byte-different and semantically equivalent under 07. Exact ZIP deployment
provenance remains unverified, and no preserved application archive was found.
The historical collection procedures/reports remain attributed to their
original sources; owner execution of a metadata reader is not application or
staging acceptance. The [handover review packet](direct-release-handover-review-2026-10-21.md)
advances local worker-role, legacy cutover and recovery preparation instead of
requesting another audit. Existing worker incompatibility, unsafe baseline
rollback and actual recovery gates remain open.

## Validation and candidate attribution

The initial checkpoint performed offline source/hash/count reconciliation and
documentation checks only. It does not rerun passed acceptance suites or assign
new PASS results. The existing 29-case local observed-schema report, its
NULL-clock checks and deliberately unsafe rollback demonstration are reused.
Candidate `888a2c2` has six successful jobs in each of
[push 39506098991](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39506098991)
and [PR 39506110044](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39506110044),
test merge `2bdd01a214d0665ddd446a823d4dda61988006ca`; this is historical
candidate CI, not CI for subsequent documentation heads or production acceptance.
Implementation `3dbcb50` retains its separate
[record](evidence/owner-schema-hosted-ci-2026-10-21.json).
Read-only GitHub metadata reconfirms [PR 8](https://github.com/Takudzwa00kyle/Bushisa/pull/8)
is open, draft, unmerged, with source `888a2c2`; no PR state was changed.

**Historical validation at `6b2294c` (before capture 07):**
`D:/cygwin64/bin/python3.9.exe .tmp/validate-completed-owner-evidence.py`.
Result: **101 relative links**, including five links to new-document anchors,
resolve; all six source hashes are unchanged; **147 manifest paths** and the
summary/status counts reconcile. The extracted read-only command passed
`D:/php/php.exe -n -l .tmp/owner-settings-summary.php` on local **PHP 8.5.11 CLI**;
this was syntax validation, not command execution or Azure behavior evidence.
`git diff --check` passed. No functional suite or hosted CI was rerun for these
documentation-only changes; their prior results remain at the sources above.

Capture 07 follow-up validation: the bounded local ZIP JSON comparison and
compact-serialization digest match are recorded separately. The next names-only
reader passed PHP 8.5.11 syntax and ten isolated Windows fixture checks,
including a real owned symlink refusal, deterministic names, private exclusions,
wrong root, missing directories and actual depth/5,000-entry stops. These are
collection-tool preparation checks, not new application/AZS/SC acceptance.
See the [local preparation report](evidence/code-names-local-preparation-2026-10-21.json).
All seven source-file hashes are unchanged. No Azure or Linux execution of
this new names reader is claimed; existing application/CI evidence is reused.
Final documentation consistency check:
`D:/cygwin64/bin/python3.9.exe .tmp/finalize-owner-07-validation.py` resolved
**108 relative links** (five new-document anchor links), rechecked seven owner
source hashes and reused the ten fixture results only after confirming the
reader digest was unchanged. `git diff --check` passed. These are documentation
and collection-tool checks, not a change to existing acceptance matrices.

Capture 08 follow-up: the [local Git/package comparison](evidence/deployed-code-inventory-reconciliation-2026-10-21.json)
and [consolidated-reader preparation](evidence/code-gap-local-preparation-2026-10-21.json)
are separate from the historical 07 checks above. The new standalone collector
has 17 synthetic Windows PHP 8.5.11 checks PASS, PHP/Bash syntax and local
PowerShell rendering PASS; Azure execution NOT RUN. No application/AZS/SC matrix
or prior CI result is changed. All eight original source hashes are preserved.

Capture 09 follow-up: offline [per-path results](evidence/code-gap-reconciliation-2026-10-21.json)
reuse the unchanged local Git/package comparisons and accepted source captures.
The new [handover packet](direct-release-handover-review-2026-10-21.md) distinguishes
fixture grants from a final least-privilege contract: table-wide UPDATE on users
does not prove password/suspension denial. Its HR01–HR04 checks are NOT RUN.
No old acceptance or CI claim is extended to this documentation head.
