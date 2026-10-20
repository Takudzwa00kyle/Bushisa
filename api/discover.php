<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/report_intake.php';
require_once __DIR__ . '/../php/matching/service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$limit = clean_int($_GET['limit'] ?? null) ?? 10;
$offset = array_key_exists('offset', $_GET) ? (clean_int($_GET['offset'] ?? null) ?? 0) : null;
$cursor = isset($_GET['cursor']) && is_string($_GET['cursor']) ? $_GET['cursor'] : '';

$options = [
    'limit' => $limit,
    'cursor' => $cursor,
    'filters' => [
        'gender' => $_GET['gender'] ?? [],
        'faculty' => $_GET['faculty'] ?? [],
        'age_min' => $_GET['age_min'] ?? null,
        'age_max' => $_GET['age_max'] ?? null,
    ],
];
if ($offset !== null) {
    $options['offset'] = $offset;
}

$result = bushisa_report_transaction($pdo, function () use ($pdo, $currentUserId, $options): array {
    if (!bushisa_report_actor_ok($pdo, $currentUserId, (int) $_SESSION['session_version'])) {
        return ['success' => false, 'error' => 'Session unavailable', 'status' => 401];
    }
    $result = matching_get_ranked_candidates($pdo, $currentUserId, $options);
    if (($result['success'] ?? false) === true) {
        $cards = [];
        foreach ($result['data']['results'] as $card) {
            $id = (int) $card['user_id'];
            $lock = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            if (!bushisa_account_is_chat_eligible($pdo, $id) || operational_is_blocked($pdo, $currentUserId, $id)) {
                continue;
            }
            $card['report_context'] = bushisa_report_context_for_response($pdo, $currentUserId, 'user', $id);
            $cards[] = $card;
        }
        $result['data']['results'] = $result['data']['candidates'] = $cards;
    }
    return $result;
});

if (($result['success'] ?? false) !== true) {
    json_response(['error' => (string) ($result['error'] ?? 'Unable to load discovery profiles')], (int) ($result['status'] ?? 500));
}

json_response(['success' => true, 'data' => $result['data']]);
