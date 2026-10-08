<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/sanitize.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/matching/service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $payload = json_decode($rawBody !== false ? $rawBody : '', true);
    $payload = is_array($payload) ? $payload : $_POST;

    $submittedToken = '';
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
    } elseif (isset($payload['csrf_token']) && is_string($payload['csrf_token'])) {
        $submittedToken = $payload['csrf_token'];
    }

    if ($submittedToken === '' || !validate_csrf_token($submittedToken)) {
        json_response(['error' => 'Invalid CSRF token'], 403);
    }

    $payload['catalog_version'] = isset($payload['catalog_version']) && is_string($payload['catalog_version'])
        ? $payload['catalog_version']
        : BUSHISA_QUESTIONNAIRE_VERSION;
    $payload['answers'] = matching_legacy_answers_payload($payload['answers'] ?? $payload);

    $validation = matching_validate_answers_payload($payload);
    if (!$validation['valid']) {
        json_response(['error' => 'Invalid questionnaire answers', 'details' => $validation['errors']], 422);
    }

    $pdo->beginTransaction();
    try {
        matching_replace_answers($pdo, $currentUserId, $validation['answers']);
        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['error' => 'Unable to save questionnaire answers'], 500);
    }
    json_response(['success' => true, 'data' => ['saved' => count($validation['answers'])]]);
}

$limit = clean_int($_GET['limit'] ?? null) ?? 20;
$offset = clean_int($_GET['offset'] ?? null) ?? 0;
$cursor = isset($_GET['cursor']) && is_string($_GET['cursor']) ? $_GET['cursor'] : '';

$result = matching_get_ranked_candidates($pdo, $currentUserId, [
    'limit' => $limit,
    'offset' => $offset,
    'cursor' => $cursor,
    'filters' => matching_request_filters($_GET),
]);

if (($result['success'] ?? false) !== true) {
    json_response(['error' => (string) ($result['error'] ?? 'Unable to find matches')], (int) ($result['status'] ?? 500));
}

json_response(['success' => true, 'data' => $result['data']]);

/**
 * @param mixed $answers
 * @return array<string, array<string, mixed>>
 */
function matching_legacy_answers_payload(mixed $answers): array
{
    if (!is_array($answers)) {
        return [];
    }

    $normalised = [];
    foreach ($answers as $key => $value) {
        if (!is_string($key)) {
            continue;
        }

        if (is_array($value) && isset($value['selected'])) {
            $normalised[$key] = $value;
            continue;
        }

        if (is_string($value) && trim($value) !== '') {
            $normalised[$key] = [
                'selected' => $value,
                'importance' => 2,
                'fit_mode' => 'similar',
                'accepted_options' => [],
            ];
        }
    }

    return $normalised;
}

/**
 * @param array<string, mixed> $query
 * @return array<string, mixed>
 */
function matching_request_filters(array $query): array
{
    return [
        'gender' => $query['gender'] ?? [],
        'faculty' => $query['faculty'] ?? [],
        'age_min' => $query['age_min'] ?? null,
        'age_max' => $query['age_max'] ?? null,
    ];
}
