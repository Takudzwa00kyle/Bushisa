# Capture 09: bounded code-gap reconciliation

Owner observations accepted on 21 October 2026 from preserved checkout
`de7547a19bb27072c2334551dabd16c6881699b2`, on
`codex/direct-production-release-prep-20261020`. This is an offline comparison,
not an independent Azure capture, application test or release approval.
The original files 01–09 remain private and unmodified in the repository parent.
See the [earlier reconciliation](completed-owner-evidence-reconciliation-2026-10-21.md)
and [per-path result](evidence/code-gap-reconciliation-2026-10-21.json).

## Accepted result and comparison

`09-code-gap-evidence.txt`: SHA-256
`b7805f13fec935c0f5fa40d7875670b0b1128e33d92a807c1b547953c55ad770`,
9,590 bytes. Owner Application SSH capture **2026-10-21 14:28:23–14:28:24 UTC /
16:28:23–16:28:24 Africa/Harare**, physical root `/home/site/wwwroot`.
There is no STOP. The capture records 198 visited entries, 12 excluded entries
and 82 rows: **65 NEW_SHA256 + 3 MISSING_REVIEWED_TARGET + 13 NEW_FILE_NAME +
1 DEFERRED_UNCLASSIFIED**. The 65 hashes and three missing targets account for
the complete reviewed 68-target specification; none repeats a capture-05 hash.

| Group | Offline result | Limit |
|---|---|---|
| Six Git-owned targets | All raw bytes match baseline `0bf311fb6d6a298712fd4cd16d0722305516edd4`: `playwright.config.js`, `database/bushisa.sql`, `database/seed.php`, `deploy/mail-webjob/run.sh`, `deploy/mail-webjob/settings.job`, `sql/schema.sql`. | Only `database/bushisa.sql`, `settings.job` and `sql/schema.sql` also match tested candidate `888a2c2`. No SQL, seed, wrapper or Playwright configuration was executed. |
| 59 PHPMailer targets | All raw hashes match the already inspected cached locked package: `composer.json`, `get_oauth_token.php` and 57 language files. | No discovered script was executed. Matching source says nothing about public reachability or OAuth configuration. |
| Three missing reviewed targets | `deploy/release/prepare-owner-code-identity.ps1`, `deploy/release/read-only-metadata.sql`, `deploy/release/supplemental-schema-metadata.sql` are absent. None exists in the baseline. | These are later local audit helpers, not missing runtime dependencies. The latter two exist in `888a2c2`; the first was added after it. |

The dependency comparator is PHPMailer **v7.1.1**, locked reference
`1bc1716a507a65e039d4ac9d9adebbbd0d346e15`; cached ZIP SHA-256
`b40f8f2ceb2bfbcb0fe2010e8961537fbbd81316c620e61c3c4db3b0aa13da08`.
This reuses the [capture-08 local package inspection](deployed-code-inventory-reconciliation-2026-10-21.md).
The lockfile has no nonempty dist checksum: cache/reference consistency is not
an independently authenticated build/download or proof of a complete deployment.

Captures 05 and 09 together contain **158 distinct hashed paths**: 81 Git-owned
paths matching the baseline and 77 dependency paths. Dependency accounting is
17 existing raw local matches, 59 cached-package raw matches, and one
`vendor/composer/installed.php` constructed metadata match. The latter changes
the two local generated root-reference fields to `0bf311f`; it is not an 18th
raw local-vendor match. No new read of installed.php is needed.

## All additional names and collection boundaries

The 13 new filename rows are completely accounted for:

| Names | Classification / evidence |
|---|---|
| `database/bushisa.sql`, `database/seed.php`, `deploy/mail-webjob/run.sh`, `deploy/mail-webjob/settings.job`, `sql/schema.sql` | Five of the six Git hash matches above; boundaries previously not traversed are now covered for these files. |
| `README.md` | Baseline Git documentation name; contents not captured in 09. |
| `vendor/composer/LICENSE` | Expected local Composer distribution licence name; contents not captured in 09. |
| `vendor/phpmailer/phpmailer/COMMITMENT`, `LICENSE`, `README.md`, `SECURITY.md`, `SMTPUTF8.md`, `VERSION` (all under that package directory) | Six names present in the inspected locked package; hosted contents remain unverified. |

The last eight are documentation/licence/version names, not new unexplained
executable paths. There is no concrete compatibility requirement for collecting
their bodies/hashes now. Their filenames do not prove contents, accessibility
or full artifact identity. No further broad file collection is requested.

Capture 09 uses the [reviewed specification and reader](remaining-code-evidence-collection-2026-10-21.md).
It removes the former extension filter **within its bounded traversed roots**;
it does not scan every filesystem/store. Dot/private/runtime, credential/local
configuration, logs, mail, dump/archive and key paths remain excluded, as do
`tests`, `docs`, `node_modules`, `App_Data` and media/upload/session/storage/cache
directories. The dot-name rule expressly exempts the already reviewed
`.htaccess` and `.user.ini` names. Twelve
excluded entries are counted without names or contents. Unknown top-level
directories are names only; outside-web-root files, external media/session
stores and installation records remain outside the collection. Traversal,
depth/entry limits and unsafe-path rejection remain in force. Captures are not
atomic or simultaneous. Do not turn differences between the 08/09 visit or
exclusion counts into a count of unidentified files or a drift finding.

## Source settings, installed settings and archives

| Object | SHA-256 / established relationship |
|---|---|
| Deployed **source copy** `deploy/mail-webjob/settings.job` (09) | `0a85fa0a78826caa80c04aad6eb0b47c8fef48c802626986948327bb9438c0f6`; matches the preserved ZIP member and Git source. |
| **Installed** `App_Data/jobs/triggered/bushisa-mail/settings.job` (04/07) | `0ae4c9493350ff029b823ec7b457ad61be2b8f515ab2116d78d2b9cb294ef501`; different raw bytes, exactly `{"schedule":"0 * * * * *"}` as established by 07. |
| Both schedules | Semantically equivalent every-minute schedule. The byte mismatch does not establish a different schedule. |
| Installed/source/ZIP old `run.sh` | All match `8549c2b452b03a883dffbb0752ff33b35c6f86aa137f85255a8d42ce72fa6015`; candidate wrapper differs. |

Capture 06's exact ZIP deployment provenance remains **UNVERIFIED**. There was
no preserved application deployment archive in the inspected locations. Neither
matching members, a source copy nor a synthetic Git archive manufactures a
complete historical deployment/rollback package. No live worker was changed.

## hostingstart.html: concrete disposition, not another broad read

`hostingstart.html` remains **DEFERRED_UNCLASSIFIED**. Its name does not prove
an Azure default, safe contents or public/default-document accessibility.
Private classification is **not necessary to complete the current bounded hash
comparison, worker/role analysis or local cutover/recovery preparation**.

It becomes necessary if the release relies on retaining/serving that file or
claims complete previous-artifact acceptance including it. A proposed alternative
is a reviewed candidate artifact and owned-path manifest that excludes the
unreviewed ancillary file, with operator evidence that deployment/routing cannot
serve leftovers. That requires private preservation of the actual pre-change
files for recovery and verified NGINX/default-document/private-path behavior;
it is not permission to delete anything now. Neither alternative is executed
or accepted here. Full hosted artifact identity and recovery remain open;
another hostingstart hash alone would not close them.

## Release consequence and next step

The requested known-code/dependency comparison is complete within its bounds.
The requested physical-schema details were already reconciled from 01/02;
no repeated schema capture or application tests are needed for this update.
The [handover review packet](direct-release-handover-review-2026-10-21.md)
now advances worker compatibility, least-privilege checks, legacy cutover and
recovery preparation. It identifies a table-wide worker grant limitation in
the existing fixture instead of treating role-name separation as sufficient.

**NO-GO for deployment** remains: old-worker/candidate-cleanup incompatibility,
actual role/secret/TLS acceptance, legacy pair/signup continuation and all-writer
cutover, compatible forward repair/fallback, actual recovery and qualified
operational/review gates. Full D03 scope remains approved. Reset stays off;
stage/fake/test controls stay absent in production. All 18 managed-stage cases
remain DEFERRED/NOT RUN; SC26 production storage/topology remains PARTIAL.
No A/LB/LC/QS/QE/QM/SC acceptance result changes.

Local reconciliation command:
`D:/cygwin64/bin/python3.9.exe .tmp/reconcile-owner09.py` (ignored helper,
bounded metadata/Git byte comparisons only). No application/worker, database,
archive contents or discovered PHP/SQL was executed. Existing historical local
and hosted CI attribution remains unchanged; this documentation head has no
new functional/hosted PASS claim. PR 8 remains draft; no PR state change or push
is performed by this checkpoint.

Documentation validation command:
`D:/cygwin64/bin/python3.9.exe .tmp/validate-owner09-docs.py`.
The [result record](evidence/code-gap-reconciliation-2026-10-21.json) contains
relative-link/anchor counts, nine unchanged source hashes, exact target/name
accounting, unchanged collector-template/spec digests and `git diff --check`.
Static source checks confirm the cited fixture grants and five-column Q11
update; these are documentation checks, not new functional execution. Historical
17 collector-fixture PASS results are reused under their original attribution.
