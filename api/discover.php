<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/matching/service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$limit = clean_int($_GET['limit'] ?? null) ?? 10;
$offset = clean_int($_GET['offset'] ?? null) ?? 0;
$cursor = isset($_GET['cursor']) && is_string($_GET['cursor']) ? $_GET['cursor'] : '';

$result = matching_get_ranked_candidates($pdo, $currentUserId, [
    'limit' => $limit,
    'offset' => $offset,
    'cursor' => $cursor,
    'filters' => [
        'gender' => $_GET['gender'] ?? [],
        'faculty' => $_GET['faculty'] ?? [],
        'age_min' => $_GET['age_min'] ?? null,
        'age_max' => $_GET['age_max'] ?? null,
    ],
]);

if (($result['success'] ?? false) !== true) {
    json_response(['error' => (string) ($result['error'] ?? 'Unable to load discovery profiles')], (int) ($result['status'] ?? 500));
}

json_response(['success' => true, 'data' => $result['data']]);
