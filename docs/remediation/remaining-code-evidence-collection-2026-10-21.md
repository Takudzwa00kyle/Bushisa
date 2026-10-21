# One consolidated code-gap collection

**Completed owner observation:** capture 09 is received and
[fully reconciled within its bounds](code-gap-reconciliation-2026-10-21.md).
Do not rerun this collection or its optional hostingstart read. The procedure
and original local fixture results below are historical preparation evidence.
Owner execution produced metadata, not application/staging acceptance; the
agent did not execute it against Azure. No new broad collection is requested.

Prepared after [capture 08 reconciliation](deployed-code-inventory-reconciliation-2026-10-21.md).
**Not executed against Azure by the agent.** Direct production remains the
chosen release approach, not permission to release now. No old collection,
database query, discovered worker/application-script execution, HTTP probe or
full-site export is needed.

The [reviewed specification](evidence/code-gap-collection-spec-2026-10-21.json)
contains **68 new hash targets** classified using local Git or the locked
PHPMailer distribution: Playwright (1), package files (59), deploy/sql source
paths (6), and the previously untraversed database bootstrap/seed tools (2).
It forbids rehashing the **93 capture-05 paths**. A names-only pass surfaces
unclassified filenames beyond capture 08's extension/directory limits; the
154 known filenames and 12 known top-level directories are suppressed.
Unknown paths are **not hashed automatically**. Private/dynamic/credential,
log/mail/dump/archive/key paths remain excluded with counts only.

`hostingstart.html` has **unverified contents and origin**. Default is no hash.
If the owner can privately confirm, using Kudu's **plain-text read-only viewer**
without visiting/rendering the page, that it contains only non-sensitive source
and no user records/credentials/tokens, the optional local declaration below may
be set true. Do not copy its body into evidence. If classification is unavailable
or it contains private material, leave false; the collector records the gap.
This is a confidentiality classification, not a production setting, a new policy
approval or proof that it is an Azure default/public page.

## Owner action, in execution order

**Local IntelliJ PowerShell, repository directory:** render the reviewed
command/specification into an ignored local file and put it on the clipboard.
This runs no discovered code and does not contact Azure. Only uncomment the
classification assignment after the private review described above.

```powershell
$GapDocument = Get-Content -LiteralPath docs/remediation/remaining-code-evidence-collection-2026-10-21.md -Raw
$GapSpecification = Get-Content -LiteralPath docs/remediation/evidence/code-gap-collection-spec-2026-10-21.json -Raw | ConvertFrom-Json
# Only after private classification as non-sensitive source:
# $GapSpecification.owner_classified_hostingstart_code_only = $true
$GapJson = $GapSpecification | ConvertTo-Json -Depth 12 -Compress
$GapMatch = [regex]::Match($GapDocument, '(?s)```bash\r?\n(php -n <<''PHP''.*?\r?\nPHP)\r?\n```')
if (-not $GapMatch.Success) { throw 'Reviewed collection template missing' }
$GapBlock = $GapMatch.Groups[1].Value.Replace('__OWNER_CODE_GAP_SPEC_JSON__', $GapJson).Replace("`r`n", "`n") + "`n"
$GapOutput = Join-Path (Get-Location) '.tmp/09-code-gap-collection.sh'
[IO.File]::WriteAllText($GapOutput, $GapBlock, (New-Object Text.UTF8Encoding($false)))
$GapBlock | Set-Clipboard
```

**Azure Portal > Bushisa > Application SSH, Bash:** paste the generated
clipboard block once. It invokes PHP's standalone reader with `-n`, not the app
or autoloader. It reads only classified file hashes and directory metadata.
No discovered code, SQL, seed tool, OAuth utility or worker is executed. Output contains
only hashes, safe names, counts, classification status and UTC times. A STOP
means save the error and stop; do not weaken guards, install tools or rerun
prior captures. The template below is not runnable until the local spec is
inserted; malformed/unrendered specs fail before filesystem traversal.

```bash
php -n <<'PHP'
<?php
function stop_gap(string $reason): never { echo 'STOP_', $reason, PHP_EOL; exit(1); }
try {
    $spec = json_decode(<<<'OWNER_CODE_GAP_SPEC'
__OWNER_CODE_GAP_SPEC_JSON__
OWNER_CODE_GAP_SPEC, true, 32, JSON_THROW_ON_ERROR);
} catch (Throwable $e) { stop_gap('INVALID_SPEC'); }
if (!is_array($spec) || ($spec['format'] ?? '') !== 'bushisa.owner-code-gap-read.v1'
    || ($spec['root'] ?? '') !== '/home/site/wwwroot'
    || !is_array($spec['hash_targets'] ?? null) || !is_array($spec['accepted_hash_paths'] ?? null)
    || !is_array($spec['already_named_paths'] ?? null)
    || count($spec['hash_targets'] ?? []) !== 68
    || count($spec['accepted_hash_paths'] ?? []) !== 93
    || count($spec['already_named_paths'] ?? []) !== 154
    || !is_bool($spec['owner_classified_hostingstart_code_only'] ?? null)) stop_gap('INVALID_SPEC');
function valid_gap_path(mixed $path): bool {
    return is_string($path) && strlen($path) <= 240
        && preg_match('~\A[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*\z~D', $path)
        && !in_array('..', explode('/', $path), true) && !in_array('.', explode('/', $path), true);
}
$accepted = array_fill_keys($spec['accepted_hash_paths'], true);
$seen = array_fill_keys($spec['already_named_paths'], true);
$targets = [];
foreach ($spec['hash_targets'] as $target) {
    $path = $target['path'] ?? null;
    if (!valid_gap_path($path) || isset($accepted[$path]) || isset($targets[$path])
        || !is_bool($target['seen_in_08'] ?? null)) stop_gap('INVALID_OR_REPEATED_TARGET');
    $targets[$path] = $target;
}
ksort($targets, SORT_STRING);
if (hash('sha256', implode("\n", array_keys($targets)) . "\n") !== '132460e1abdc6a2673952c38a05a250b514a5c462af6224994dd13c3cd99854b') stop_gap('UNREVIEWED_TARGET_SET');
$root = '/home/site/wwwroot';
if (realpath($root) !== $root) stop_gap('ROOT_MAPPING');
$start = gmdate('Y-m-d\TH:i:s\Z');
$native = static fn(string $p): string => $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $p);
$rows = [];
$privateName = '~(?:^\.|config\.local|credentials?|^auth\.json$|^(?:passwords?|secrets?|tokens?|codes?)(?:[._-]|$)|(?:^|[_-])(?:dump|backup|snapshot)(?:[_.-]|$)|\.(?:log|eml|msg|pem|key|pfx|p12|zip|tgz|gz|7z|rar|dump)$|^(?:uploads?|sessions?|logs?|caches?|secrets?|keys?|backups?|media|storage|tmp|tests|docs|node_modules|App_Data)$)~i';
if (@preg_match($privateName, 'pattern_check') === false) stop_gap('INVALID_EXCLUSION_PATTERN');
$hashFile = static function (string $path, bool $previouslySeen) use ($native, &$rows): void {
    $full = $native($path);
    if (is_link($full)) stop_gap('SYMLINK_TARGET');
    if (!file_exists($full)) {
        if ($previouslySeen) stop_gap('PREVIOUSLY_SEEN_TARGET_MISSING');
        $rows[] = 'MISSING_REVIEWED_TARGET ' . $path; return;
    }
    if (realpath($full) !== $full) stop_gap('TARGET_MAPPING');
    $before = @lstat($full);
    if (!$before || ($before['mode'] & 0170000) !== 0100000 || $before['size'] > 1048576) stop_gap('UNSAFE_OR_OVERSIZE_TARGET');
    $handle = @fopen($full, 'rb');
    if (!$handle) stop_gap('UNREADABLE_TARGET');
    $opened = fstat($handle);
    if (!$opened) stop_gap('STAT_FAILED');
    foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
        if ($before[$key] !== $opened[$key]) stop_gap('TARGET_CHANGED');
    }
    $hash = hash_init('sha256');
    $length = hash_update_stream($hash, $handle, 1048577);
    $closed = fstat($handle);
    fclose($handle);
    clearstatcache(true, $full);
    $after = @lstat($full);
    if (!$closed || !$after || $length !== $before['size'] || is_link($full)
        || realpath($full) !== $full) stop_gap('TARGET_CHANGED');
    foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
        if ($opened[$key] !== $closed[$key] || $closed[$key] !== $after[$key]) stop_gap('TARGET_CHANGED');
    }
    $rows[] = 'NEW_SHA256 ' . hash_final($hash) . ' ' . $path;
};
foreach ($targets as $path => $target) $hashFile($path, $target['seen_in_08']);
if ($spec['owner_classified_hostingstart_code_only']) {
    $hashFile('hostingstart.html', true);
    $rows[] = 'HOSTINGSTART_OWNER_CLASSIFICATION code_only_non_sensitive';
} else { $rows[] = 'DEFERRED_UNCLASSIFIED hostingstart.html'; }
$visited = 0; $excluded = 0;
$knownTop = array_fill_keys(['admin','api','bin','config','css','database','deploy','js','php','public','sql','vendor'], true);
$roots = ['admin','api','bin','config','css','js','php','public','vendor','deploy','sql','database'];
$walk = function (string $relative, int $depth) use (&$walk, $root, $native, $seen, $knownTop, $privateName, &$visited, &$excluded, &$rows): void {
    if ($depth > 8) stop_gap('DEPTH_LIMIT');
    $directory = $relative === '' ? $root : $native($relative);
    if (is_link($directory) || realpath($directory) !== $directory) stop_gap('DIRECTORY_MAPPING');
    $before = @lstat($directory);
    if (!$before || ($before['mode'] & 0170000) !== 0040000) stop_gap('NOT_DIRECTORY');
    try { $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS); }
    catch (Throwable $e) { stop_gap('DIRECTORY_UNREADABLE'); }
    foreach ($items as $item) {
        if (++$visited > 5000) stop_gap('ITEM_LIMIT');
        $name = $item->getFilename();
        $path = $relative === '' ? $name : $relative . '/' . $name;
        if (!in_array($name, ['.htaccess','.user.ini'], true) && preg_match($privateName, $name)) { ++$excluded; continue; }
        if (!valid_gap_path($path)) stop_gap('UNSAFE_NAME');
        $full = $native($path);
        if (is_link($full)) stop_gap('SYMLINK');
        $state = @lstat($full);
        if (!$state) stop_gap('ENTRY_CHANGED');
        $kind = $state['mode'] & 0170000;
        if ($kind === 0040000) {
            if ($relative === '') {
                if (!isset($knownTop[$name])) $rows[] = 'NEW_TOP_DIRECTORY ' . $name;
            } else { $walk($path, $depth + 1); }
        } elseif ($kind === 0100000) {
            if (!isset($seen[$path])) $rows[] = 'NEW_FILE_NAME ' . $path;
        } else { stop_gap('NONREGULAR_ENTRY'); }
    }
    clearstatcache(true, $directory);
    $after = @lstat($directory);
    foreach (['dev','ino','mode','mtime','ctime'] as $key) {
        if (!$after || $before[$key] !== $after[$key]) stop_gap('DIRECTORY_CHANGED');
    }
    if (realpath($directory) !== $directory || is_link($directory)) stop_gap('DIRECTORY_CHANGED');
};
$walk('', 0);
foreach ($roots as $path) {
    if (!file_exists($native($path))) { $rows[] = 'MISSING_ROOT ' . $path; continue; }
    $walk($path, 1);
}
sort($rows, SORT_STRING);
echo 'BEGIN_CODE_GAP_EVIDENCE', PHP_EOL;
echo 'capture_utc=', $start, PHP_EOL;
echo 'physical_root=', $root, PHP_EOL;
echo 'reviewed_hash_targets=68', PHP_EOL;
echo 'visited_entries=', $visited, PHP_EOL;
echo 'excluded_entries=', $excluded, PHP_EOL;
echo 'row_count=', count($rows), PHP_EOL;
foreach ($rows as $row) echo $row, PHP_EOL;
echo 'capture_end_utc=', gmdate('Y-m-d\TH:i:s\Z'), PHP_EOL;
echo 'END_CODE_GAP_EVIDENCE', PHP_EOL;
PHP
```

**Local IntelliJ PowerShell:** copy only the result block/STOP, then save:

```powershell
$OwnerEvidenceDir = Split-Path -Parent (Get-Location).Path
Get-Clipboard -Raw | Set-Content -LiteralPath (Join-Path $OwnerEvidenceDir '09-code-gap-evidence.txt') -Encoding UTF8
```

Expected: 60 previously named new targets still present, plus explicit
hashes/missing status for eight newly reviewed auxiliary paths, new names beyond
the former filter/boundaries, and either a privately authorized hostingstart
hash or a DEFERRED row. Missing prior targets/unsafe paths stop without a
partial result. The new exclusions/counts have a broader scope than 08 and must
not be compared as identical inventories. Old hashes/names are reused, not
re-emitted; target drift across observations stays explicit.

Unknown new paths will be classified locally before any further content read.
Private exclusions, unsupported external stores, non-atomic capture, installation
receipts, access/routing, exact previous bundle and recovery remain separate
limits. Successful collection cannot authorize rollout, resolve the old-worker
exit-78 incompatibility, enable reset or close staffing/privacy/security gates.

## Local validation and execution limits

The rendered command/spec has PHP/Bash syntax validation and **17 synthetic
checks PASS on Windows PHP 8.5.11 CLI**. The documented PowerShell renderer was
executed locally with only its clipboard-copy line replaced by a status line.
File fixtures were isolated under `.tmp`; the reader's filesystem root alone
was substituted for normal runs, with explicit fixture spec variants for
negative/optional classification cases. One additional negative fixture supplied
an invalid exclusion pattern to prove fail-closed handling. No application, discovered package code, SQL, server,
database or mail carrier was run. The reviewed target-set digest is enforced
before file access; a regex validation failure stops before hashing.

See the [exact fixture report](evidence/code-gap-local-preparation-2026-10-21.json).
The source/spec digests identify the tested command; changed code needs renewed
validation. No hosted CI or application suite is rerun for this documentation
preparation. **Azure/Linux execution of this collector is NOT RUN**; Bash
syntax on the local Cygwin toolchain is not Linux runtime or Azure acceptance.
