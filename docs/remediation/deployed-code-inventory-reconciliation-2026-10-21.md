# Capture 08: deployed-code inventory reconciliation

**Subsequent capture 09 received:** the [delta reconciliation](code-gap-reconciliation-2026-10-21.md)
resolves the reviewed new hashes/boundaries and classifies every new filename.
Any collection directions below are historical; do not repeat them.

Continued from clean `a3d2cf0055e6dc8efecf85413ed5ecd306cdb9d1` on the existing
direct-release preparation branch. Source: owner Application SSH output
`08-deployed-code-names.txt`, SHA-256
`2c564889f6f8e7bc6fc7f4c7bfb2f1b75e2b36bbb53225e3bc54939fab14c0e4`.
Capture start/end **2026-10-21 12:41:11 UTC / 14:41:11 Africa/Harare**,
`/home/site/wwwroot`. Recording this observation does not invent an approval time.
No new Azure collection, discovered application/package/worker execution or HTTP exposure
test was performed by the agent. [Per-path evidence](evidence/deployed-code-inventory-reconciliation-2026-10-21.json)
records the local comparisons. Original owner files remain private/unmodified.

## Accepted inventory and classification

The complete, sorted BEGIN/END block has no STOP: **190 visited entries,
10 exclusions and 166 rows = 154 CODE_PATH + 12 TOP_DIRECTORY**, with no
MISSING_DIRECTORY rows. **All 93 already hashed paths from capture 05 appear.**
That is consistent path coverage at these separate observations, not a fresh
verification of their contents or an atomic deployment snapshot.

There are **61 additional file paths**, all classified locally before any new
hash request:

| Additional path group | Local evidence / classification | What remains unproved |
|---|---|---|
| `hostingstart.html` (1) | Unidentified root HTML document. No available application Git history or locked-package path identifies it. **No assumption that it is an Azure default page.** | Contents, sensitivity, origin and HTTP accessibility. Default: do not hash or fetch its body. Only private owner classification as code-only/non-sensitive permits the optional hash in the consolidated collection. |
| `playwright.config.js` (1) | Tracked test-harness configuration, present at baseline and candidate. Available path history: `bca0bcc`, `e022226`, `438397e`, `8b40e9e`. The initial root-file hash selector omitted root JS. | Hosted bytes/version; test execution or public accessibility. Do not execute it: the known tooling can start test servers and uses guarded test dependencies. |
| PHPMailer `composer.json` (1) | Expected package manifest in the locally cached distribution for locked **v7.1.1 / `1bc1716a507a65e039d4ac9d9adebbbd0d346e15`**. | Hosted content hash and complete dependency/build identity. Its optional/dev requirements are not proof those dependencies are installed. |
| PHPMailer `get_oauth_token.php` (1) | Expected packaged OAuth setup/token utility. Static inspection of the local package finds session/autoload and token-acquisition code. **Not executed.** This is not Bushisa's signup/reset dispatcher. | Hosted bytes, any configuration or route access. Presence does not prove OAuth is enabled, the script works, or it is publicly accessible. Never visit/execute it to identify it. |
| PHPMailer language files (57) | Every inventoried locale path exists in the locked-reference cached package and matches the corresponding local vendor bytes. Expected translation PHP files, separate from the seven core source files already hashed. | All 57 hosted content hashes. Filenames alone do not establish deployed contents or a fully matching package. |

The cached Composer archive is 157,309 bytes, SHA-256
`b40f8f2ceb2bfbcb0fe2010e8961537fbbd81316c620e61c3c4db3b0aa13da08`,
75 ZIP members, root `PHPMailer-PHPMailer-1bc1716/`. All 59 additional package
paths exist and match the explicit local vendor files. The seven cached core
source hashes also match the already accepted capture-05 values. The lock's dist checksum
is empty; this is a **local cached-package/reference consistency comparison**,
not an independently authenticated upstream download or hosted-package proof.
No extraction, Composer install, autoloader or discovered script execution was
used. Cache/originals were unchanged.
This dependency-only cache ZIP is not a preserved Bushisa application deployment
archive, rollback bundle or Azure installation receipt.

## Composer metadata difference resolved locally

No more production read of `vendor/composer/installed.php` is needed for the
previous digest discrepancy. The local generated file embeds root reference
`6ed126d31ce6d2a5e046cf0ace98744345a8e934`. A bounded in-memory candidate changes
its two root-reference occurrences to baseline
`0bf311fb6d6a298712fd4cd16d0722305516edd4`, retaining `dev-main`, root `dev=true`
and LF/final newline. Its SHA-256 exactly matches capture 05:
`43bff47902412561900e0817be4b91641e1293acae1e42a771ab908a7e87e0a9`.

This accounts for the bytes through generated root metadata. It is **17 raw
local dependency matches plus one constructed generated-metadata digest
match**, not 18 unchanged local files. No supplied PHP was evaluated, no
historical archive manufactured and no production content fetched. The embedded
Composer root reference does not identify every deployed app file; `dev=true`
does not establish APP_ENV or operational permissions. Historical installation
steps and complete deployment provenance remain unverified.

## Untraversed directories and collector limits

| Boundary | Local classification and remaining evidence |
|---|---|
| `deploy` | Listed top-level only. Git history identifies `deploy/mail-webjob/run.sh`, `settings.job`, two metadata SQL helpers and the local PowerShell command generator. These are source/deployment tooling, not evidence of what is installed in App_Data. Hash only these reviewed paths if present; list new names without executing or hashing unknown files. |
| `sql` | Listed top-level only. Git has `.gitkeep` and `sql/schema.sql`, a legacy bootstrap/schema template. Local baseline has CREATE/USE DDL, not an observed production dump. Do not execute it or infer hosted contents. Empty `.gitkeep` needs no hash. Unknown SQL/dump paths remain unclassified. |
| `database` | Only `database/migrations` was traversed. Git also has destructive `database/bushisa.sql` bootstrap and `database/seed.php` tooling; classify as source tools, never execute. The delta capture covers this remaining directory boundary too. Existing four migration file hashes/ledger evidence are reused. |
| Ten exclusions | Only an aggregate count is recorded. Their names/content cannot be reconstructed from the count. Dot/private/runtime paths, config.local/credentials/auth exclusions remain outside code coverage; do not inspect them to complete a manifest. |
| Extension filter | The initial reader selected common PHP/JS/CSS/HTML/SVG/SQL/shell types, named manifests, vendor JSON and `.htaccess`/`.user.ini`. It omitted `.job`, `.ps1`, YAML, text/docs, most INI/JSON outside vendor and other formats. The next reader lists only **new**, non-private names without an extension filter; unknown names are not automatically hashed. |
| Outside web root / runtime | External mounts, deployed build receipts, active NGINX/PHP overrides, effective authorization and HTTP routing are not established. Existing `.htaccess`/filenames are not proof of NGINX protection. No public accessibility claim is made. |

`190 - 166` is **not** a count of unknown files: rows include directory
summaries, visited entries include directory traversal, and exclusions/filtering
have different scopes. No globally complete inventory or deployment SHA follows.

## One consolidated remaining collection

The [consolidated delta procedure](remaining-code-evidence-collection-2026-10-21.md)
prepares **68 newly justified hashes**: Playwright + 59 package paths + eight
known auxiliary Git source paths. It suppresses all 93 accepted hash targets
and all 154 already inventoried filenames. Extension-independent names cover
the reviewed source roots plus deploy/sql/database; unknown paths receive
names-only classification, never automatic content reads.

`hostingstart.html` is separately default-denied until the owner privately
classifies it as code-only/non-sensitive; its body is never requested. This is
one bounded follow-up for the remaining justified gaps, not another full audit.
Previously accepted captures, installed worker/settings hashes and schema
queries are not repeated. Any new unknown/private path remains an explicit
coverage limit; do not broaden the reader to make a completeness claim.

## Release gates preserved

Capture 07 still establishes exact minute-schedule semantic equivalence while
ZIP/installed bytes differ. Exact ZIP deployment linkage and a preserved actual
application rollback package remain absent. The **old installed wrapper would
stop before dispatch** on the candidate broad-cleanup guard (exit 78); no
schedule/name comparison fixes that incompatibility. Coordinated reviewed
mail-only replacement and dedicated worker credentials remain prerequisites.

Runtime/worker/migrator role and secret/definer separation, full-D03 restricted
grant checks, authentic legacy pair/signup continuation and all-writer cutover,
compatible fallback/forward repair, fresh recovery coverage, independent
protective replay and witnessed restore remain open. No invented history,
anchors, reset activation, policy adoption or feature removal is authorized.
AZS01-AZS18 remain **DEFERRED / NOT RUN**, SC26 production topology **PARTIAL**,
actual-artifact DR-P02 and recovery DR-P03 **NOT RUN**. Recommendation: **NO-GO**.
PR 8 stays draft; old application/CI evidence retains its exact attribution.

## Local preparation validation

Local commands (ignored helpers, not production tools):
`D:/cygwin64/bin/python3.9.exe .tmp/compare-owner-08-local.py`,
`.tmp/inspect-locked-package-08.py`, and `.tmp/test-owner-09-preparation.py`.
The first two parse owner data/Git/cache bytes only; they execute no discovered
PHP or SQL. The third validates the prepared standalone reader with synthetic
files, not the application. Exact results are in the
[preparation report](evidence/code-gap-local-preparation-2026-10-21.json).

PowerShell rendering (clipboard untouched), PHP syntax and Bash syntax passed.
**17 fixture checks PASS on native Windows PHP 8.5.11 CLI** cover the 68 exact
new hashes, omission of accepted hashes/names, unknown-extension/boundary names
without hashes, private exclusions/no bodies, optional synthetic HTML
classification, retries/determinism, malformed specs, unreviewed/repeated targets,
missing old/new paths, size/depth/5,000-entry bounds and a real owned symlink.
The initial fixture caught an invalid character range in the exclusion regex;
it was corrected and pre-hash regex validation added, with the repaired run
and its invalid-pattern refusal passing. No failed result was relabelled as
application acceptance. Linux execution/Azure execution of 09 remain **NOT RUN**.
Existing A/LB/LC/QS/QE/QM/SC/AZS and candidate-CI results are unchanged.

Final documentation command:
`D:/cygwin64/bin/python3.9.exe .tmp/validate-owner-08-checkpoint.py`.
**123 relative links**, five checked anchor links and all eight owner-source
hashes pass; source/spec/rendered-command digests match the executed fixture
record. `git diff --check` passes. Read-only GitHub metadata reconfirms PR 8
draft/open/unmerged at source `888a2c2`, test merge `2bdd01a`; no CI or PR state
was changed. This is a local documentation/preparation checkpoint, not hosted
CI for its later head or production acceptance.
