<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/security_headers.php';
require_once __DIR__ . '/../php/session_manager.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/csrf.php';
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/function.php';
require_once __DIR__ . '/../php/operational.php';
require_once __DIR__ . '/../php/matching/questionnaire.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $answers = matching_load_answers($pdo, [$currentUserId]);
    $ownAnswers = $answers[$currentUserId] ?? [];
    json_response([
        'success' => true,
        'data' => [
            'catalog' => matching_questionnaire_public_catalog(),
            'answers' => $ownAnswers,
            'answered_count' => count($ownAnswers),
        ],
    ]);
}

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

$validation = matching_validate_answers_payload($payload);
if (!$validation['valid']) {
    json_response(['error' => 'Invalid questionnaire answers', 'details' => $validation['errors']], 422);
}

try {
    $pdo->beginTransaction();
    matching_replace_answers($pdo, $currentUserId, $validation['answers']);
    $pdo->commit();
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Unable to save questionnaire answers'], 500);
}

json_response([
    'success' => true,
    'data' => [
        'saved' => count($validation['answers']),
        'catalog_version' => BUSHISA_QUESTIONNAIRE_VERSION,
    ],
]);
