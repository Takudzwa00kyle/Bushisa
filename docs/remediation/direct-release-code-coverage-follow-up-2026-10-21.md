# Direct-release follow-up: deployed code coverage

Prepared from `6b2294c36a0d415d0d52ab10ff1c7881346e459c` after accepting
[capture 07](evidence/installed-settings-semantics-2026-10-21.json). Installed
and ZIP settings are semantically equivalent: exactly
`{"schedule":"0 * * * * *"}`. Their raw hashes differ. Local compact JSON is
26 bytes and hashes to the installed digest; the 34-byte ZIP entry uses
formatting whitespace. This resolves the schedule/representation comparison,
not who installed/edited it or whether this exact ZIP was deployed.

## Next unresolved requirement and local preparation

**Establish coverage of the hosted code, rather than infer a whole deployment
from the 75 known source matches.** Capture 05 already supplies the reviewed
file hashes. Do not repeat them, query the database or request a full site ZIP.
No actual previous application archive was found in the inspected locations.

| Component | Established | Remaining, and how the next capture helps |
|---|---|---|
| Known application source | 75 raw baseline matches; 53 missing candidate additions | Extra/custom code outside the 147-target allowlist has not been inventoried. Compare names against the existing Git trees before choosing any additional hash targets. |
| Dependencies | Baseline lock and 17 of 18 captured vendor files match the explicit local installation | Unknown extra packages/autoload targets; `vendor/composer/installed.php` hash differs. Names alone cannot explain that file's bytes. Review only its relevant metadata privately if still necessary; do not `require` it or run Composer on production to identify it. |
| Installed worker | Actual directory and both hashes known; script identified; settings schedule established | Exact ZIP upload/installation linkage remains UNVERIFIED. This web-code inventory does not traverse App_Data or certify the full worker package. |
| Previous bundle / recovery | Preserved two-file worker ZIP, historical Git comparator and reports | No complete attested previous app bundle. A names list does not create one, make OS17 rollback safe or close DR-P02/DR-P03. |

Local preparation consists of the bounded names-only reader below, plus its
synthetic checks. It does not add an application route, public endpoint,
deployment action or runtime configuration. It does not load any discovered
PHP/JavaScript/JSON/SQL, execute an autoloader or connect to a database.

## Smallest owner action: one names-only capture

**Azure Portal > Bushisa > Application SSH, Bash.** Paste this block once.
It lists reviewed kinds of code filenames under the known source directories,
plus safe top-level directory names to identify areas needing classification.
It reads directory metadata only: no file bodies, hashes, environment,
logs, sessions, uploads, credentials, user rows or mail/proof data.

Unknown top-level directories are **listed, not traversed**. Private/dynamic
directory names and private configuration names are excluded. Symlinks and
non-regular objects stop the reader; no new target is silently followed.
Depth/item limits and excluded areas remain explicit coverage limits, not
proof of a complete artifact. The result is not an atomic snapshot; preserve
capture time and any STOP instead of suppressing it or installing tools.

```bash
php -n <<'PHP'
<?php
function stop_names(string $reason): never {
    echo 'STOP_', $reason, PHP_EOL;
    exit(1);
}
$root = '/home/site/wwwroot';
if (realpath($root) !== $root) stop_names('ROOT_MAPPING');
$start = gmdate('Y-m-d\TH:i:s\Z');
$roots = ['admin', 'api', 'bin', 'config', 'css', 'js', 'php', 'public', 'vendor', 'database/migrations'];
$rows = [];
$visited = 0;
$excluded = 0;
$privateName = '~(?:^\.|^(?:uploads?|sessions?|logs?|caches?|secrets?|keys?|backups?|media|storage|tmp|tests|docs|node_modules|App_Data)$|config\.local|credentials?|^auth\.json$)~i';
$scan = function (string $relative, int $depth) use (&$scan, $root, $roots, &$rows, &$visited, &$excluded, $privateName): void {
    if ($depth > 8) stop_names('DEPTH_LIMIT');
    $directory = $relative === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_link($directory) || realpath($directory) !== $directory) stop_names('UNSAFE_DIRECTORY');
    $before = @lstat($directory);
    if (!$before || ($before['mode'] & 0170000) !== 0040000) stop_names('NOT_DIRECTORY');
    try { $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS); }
    catch (Throwable $e) { stop_names('DIRECTORY_UNREADABLE'); }
    foreach ($items as $item) {
        if (++$visited > 5000) stop_names('ITEM_LIMIT');
        $name = $item->getFilename();
        $path = $relative === '' ? $name : $relative . '/' . $name;
        $special = in_array($name, ['.htaccess', '.user.ini'], true);
        if (!$special && preg_match($privateName, $name)) { ++$excluded; continue; }
        if (!preg_match('~\A[A-Za-z0-9_.-]+\z~D', $name)) stop_names('UNSAFE_NAME');
        $full = $directory . DIRECTORY_SEPARATOR . $name;
        if (is_link($full)) stop_names('SYMLINK');
        $state = @lstat($full);
        if (!$state) stop_names('ENTRY_CHANGED');
        $kind = $state['mode'] & 0170000;
        if ($kind === 0040000) {
            if ($relative === '') {
                $rows['TOP_DIRECTORY ' . $name] = true;
            } else {
                $scan($path, $depth + 1);
            }
        } elseif ($kind === 0100000) {
            if ($special || preg_match('~\.(?:php[0-9]?|inc|phtml|phar|js|mjs|cjs|css|html?|svg|sql|sh)\z~iD', $name)
                || in_array($name, ['composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'web.config'], true)
                || ($relative !== '' && str_starts_with($relative . '/', 'vendor/') && str_ends_with($name, '.json'))) {
                $rows['CODE_PATH ' . $path] = true;
            }
        } else { stop_names('NONREGULAR_ENTRY'); }
    }
    clearstatcache(true, $directory);
    $after = @lstat($directory);
    foreach (['dev', 'ino', 'mode', 'mtime', 'ctime'] as $key) {
        if (!$after || $before[$key] !== $after[$key]) stop_names('DIRECTORY_CHANGED');
    }
    if (realpath($directory) !== $directory || is_link($directory)) stop_names('DIRECTORY_CHANGED');
};
$scan('', 0);
foreach ($roots as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_link($path)) stop_names('SYMLINK');
    if (!file_exists($path)) { $rows['MISSING_DIRECTORY ' . $relative] = true; continue; }
    $scan($relative, 1);
}
$names = array_keys($rows);
sort($names, SORT_STRING);
echo 'BEGIN_DEPLOYED_CODE_NAMES', PHP_EOL;
echo 'capture_utc=', $start, PHP_EOL;
echo 'physical_root=', $root, PHP_EOL;
echo 'visited_entries=', $visited, PHP_EOL;
echo 'excluded_entries=', $excluded, PHP_EOL;
echo 'row_count=', count($names), PHP_EOL;
foreach ($names as $name) echo $name, PHP_EOL;
echo 'capture_end_utc=', gmdate('Y-m-d\TH:i:s\Z'), PHP_EOL;
echo 'END_DEPLOYED_CODE_NAMES', PHP_EOL;
PHP
```

The root's `database` name may appear, but only `database/migrations` is
traversed. App_Data and runtime/private directories are excluded even when
the name is known. Any unexpected top-level name/code extension requires
private classification, not an automatic recursive follow-up. Configuration
and unknown executable formats outside this reader's scope remain unverified.
The existing scoped hash manifest is reused after comparison; new hashes, if
needed, will be selected only for newly classified code paths.

Save only the result as **`08-deployed-code-names.txt`** beside 01-07.
**Local IntelliJ PowerShell, with this repository as current directory:**

```powershell
$OwnerEvidenceDir = Split-Path -Parent (Get-Location).Path
Get-Clipboard -Raw | Set-Content -LiteralPath (Join-Path $OwnerEvidenceDir '08-deployed-code-names.txt') -Encoding UTF8
```

Expected: a complete BEGIN/END block of names/counts, or a STOP requiring
targeted review. **Production execution NOT RUN.** No second settings capture,
whole-file hashing, database connection, worker run, Composer invocation or
application archive export is requested.

## Local validation of the prepared reader

`D:/cygwin64/bin/python3.9.exe .tmp/validate-owner-07-preparation.py` extracted
the command above and syntax-checked it with `D:/php/php.exe -n -l`.
Native **Windows PHP 8.5.11 CLI** executed it against unique, owned synthetic
directories under `.tmp`; only the root literal was substituted. No repository
application code, real session storage or Azure path was executed/read.

**10 local preparation checks PASS:** expected/extra code names; no bodies or
private paths; unknown top-level directories listed without traversal; stable
sorted repeat results; explicit missing directory; wrong root refusal; unsafe
filename refusal; depth bound; the actual 5,000-entry bound; and refusal of a
real owned filesystem symlink. Error cases emitted a STOP without a partial
BEGIN/END result. Exact cases, source digest and profile are in the
[preparation report](evidence/code-names-local-preparation-2026-10-21.json).

Linux/Azure execution remains **NOT RUN**. These checks validate a proposed
read-only metadata command, not application behavior, production filesystem
fencing, independent durability or an AZS/SC case. Existing application suites
and hosted CI were not rerun or relabelled. Owner source hashes 01-07 remain
unchanged, and the worker ZIP was read-only/unchanged.

## Separate gates after this capture

The known installed old wrapper still calls production-gated broad cleanup
before dispatch and would stop at exit 78 on the candidate. Schedule equivalence
does not fix that rollout incompatibility. Keep the reviewed coordinated
mail-only worker replacement, dedicated worker identity and old-mail
continuation prerequisites in the [release package](direct-production-release-preparation-2026-10-20.md).

Actual runtime/worker/migrator grants and secret/definer handling remain a
qualified DB/security review and future separately approved change. Full-D03
grant-path negatives are executable engineering work, not an owner policy
question. Authentic legacy history/all-writer cutover and trusted signup anchors
remain evidence requirements: do not fabricate them, reset clocks or enable
ledger completeness. Test compatible forward repair or the exact previous
artifact before reopening any fallback writers. Fresh DB/media/config/session
recovery, independent protective replay and witnessed recovery remain separate
open acceptance gates. Managed staging stays deferred; reset stays off.
