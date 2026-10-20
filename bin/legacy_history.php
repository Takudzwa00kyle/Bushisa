<?php
declare(strict_types=1);

// No application bootstrap, environment loading, database driver or mail transport.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../php/legacy_history.php';

use Bushisa\LegacyHistory\InvalidInput;
use function Bushisa\LegacyHistory\{base_result, catalog_schema, encode, evaluate, read_artifact};

$at = gmdate('Y-m-d\TH:i:s\Z');
$safeAt = $at;
try {
    $mode = $argv[1] ?? '';
    if (!in_array($mode, ['validate', 'plan'], true)) { throw new InvalidInput('VALIDATE_OR_PLAN_ONLY'); }
    $options = [];
    foreach (array_slice($argv, 2) as $argument) {
        if (!preg_match('/\A--(bundle|snapshot|catalog|at)=(.+)\z/D', $argument, $m) || isset($options[$m[1]])) { throw new InvalidInput('CLI_OPTION'); }
        $options[$m[1]] = $m[2];
    }
    $at = $options['at'] ?? $at;
    $allowed = $mode === 'plan' ? ['bundle', 'snapshot', 'catalog', 'at'] : ['bundle', 'catalog', 'at'];
    if (!isset($options['bundle'], $options['catalog']) || ($mode === 'plan' && !isset($options['snapshot'])) || array_diff(array_keys($options), $allowed) !== []) { throw new InvalidInput('CLI_REQUIRED_OPTIONS'); }
    // Validate the processing clock before opening files, with no historical-time inference.
    base_result($at);
    $safeAt = $at;
    $bundle = read_artifact($options['bundle']);
    $catalog = catalog_schema(read_artifact($options['catalog'])['value']);
    $snapshot = $mode === 'plan' ? read_artifact($options['snapshot']) : null;
    foreach (['bundle' => $bundle, 'snapshot' => $snapshot] as $kind => $artifact) {
        if ($artifact !== null && !in_array($artifact['raw_sha256'], $catalog->raw_digests->{$kind}, true)) { throw new InvalidInput('RAW_CUSTODY_DIGEST_MISMATCH'); }
    }
    $result = evaluate($bundle['value'], $snapshot['value'] ?? null, $catalog, $at);
    $result['raw_custody_check'] = 'MATCHED_SYNTHETIC_CATALOG_ONLY';
} catch (InvalidInput $e) {
    $result = base_result($safeAt);
    $result['classification'] = 'INVALID'; $result['outcome'] = $e->outcome; $result['reasons'] = [$e->reason];
} catch (Throwable) {
    $result = base_result($safeAt);
    $result['classification'] = 'INVALID'; $result['outcome'] = 'REJECT_INPUT'; $result['reasons'] = ['INTERNAL_FAILURE'];
}
fwrite(STDOUT, encode($result) . "\n");
exit($result['outcome'] === 'PLAN_NON_ENABLING' ? 0 : 2);
