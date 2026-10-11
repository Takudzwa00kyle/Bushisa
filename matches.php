<?php

declare(strict_types=1);

require_once __DIR__ . '/php/security_headers.php';
require_once __DIR__ . '/php/session_manager.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php/csrf.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/function.php';
require_once __DIR__ . '/php/operational.php';
require_once __DIR__ . '/php/frontend.php';
require_once __DIR__ . '/php/matching/service.php';

require_login();
ensure_operational_schema($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$matches = [];
$recommendations = [];
$formError = null;
$formSuccess = null;
$csrfToken = generate_csrf_token();
$catalog = matching_questionnaire_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($submittedToken === '' || !validate_csrf_token($submittedToken)) {
        $formError = 'Invalid form token. Please refresh and try again.';
    } else {
        $answers = [];
        foreach ($catalog as $key => $question) {
            $answerKey = 'answer_' . $key;
            $acceptedKey = 'accepted_' . $key;
            $answers[$key] = [
                'selected' => $_POST[$answerKey] ?? [],
                'importance' => $_POST['importance_' . $key] ?? 2,
                'fit_mode' => $_POST['fit_mode_' . $key] ?? 'similar',
                'accepted_options' => $_POST[$acceptedKey] ?? [],
            ];
        }

        $validation = matching_validate_answers_payload([
            'catalog_version' => BUSHISA_QUESTIONNAIRE_VERSION,
            'answers' => $answers,
        ]);

        if (!$validation['valid']) {
            $formError = 'Some questionnaire answers need correction.';
        } else {
            $pdo->beginTransaction();
            try {
                matching_replace_answers($pdo, $currentUserId, $validation['answers']);
                $pdo->commit();
                $formSuccess = 'Questionnaire saved.';
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $formError = 'Unable to save questionnaire answers.';
            }
        }
    }
}

$answersByUser = matching_load_answers($pdo, [$currentUserId]);
$savedAnswers = $answersByUser[$currentUserId] ?? [];
$recommendationResult = matching_get_ranked_candidates($pdo, $currentUserId, ['limit' => 12]);
if (($recommendationResult['success'] ?? false) === true) {
    $recommendations = $recommendationResult['data']['results'] ?? [];
}

$matchColumnsStatement = $pdo->query('SHOW COLUMNS FROM matches');
$matchColumns = $matchColumnsStatement !== false ? $matchColumnsStatement->fetchAll(PDO::FETCH_COLUMN) : [];
$matchUserAColumn = in_array('user_a_id', $matchColumns, true) ? 'user_a_id' : 'user_one_id';
$matchUserBColumn = in_array('user_b_id', $matchColumns, true) ? 'user_b_id' : 'user_two_id';
$matchTimeColumn = in_array('matched_at', $matchColumns, true) ? 'matched_at' : 'created_at';

$messageColumnsStatement = $pdo->query('SHOW COLUMNS FROM messages');
$messageColumns = $messageColumnsStatement !== false ? $messageColumnsStatement->fetchAll(PDO::FETCH_COLUMN) : [];
$messageBodyColumn = in_array('body', $messageColumns, true) ? 'body' : 'message_text';
$messageTimeColumn = in_array('sent_at', $messageColumns, true) ? 'sent_at' : 'created_at';

$sql = sprintf(
    'SELECT
        m.id AS match_id,
        CASE WHEN m.%1$s = :select_user THEN m.%2$s ELSE m.%1$s END AS other_user_id,
        p.display_name,
        p.profile_photo_path,
        p.faculty,
        lm.body AS last_message,
        lm.sent_at AS last_message_time,
        m.%3$s AS matched_at
    FROM matches m
    INNER JOIN profiles p
        ON p.user_id = CASE WHEN m.%1$s = :join_user THEN m.%2$s ELSE m.%1$s END
    LEFT JOIN (
        SELECT msg.match_id, msg.%4$s AS body, msg.%5$s AS sent_at
        FROM messages msg
        INNER JOIN (
            SELECT match_id, MAX(id) AS last_message_id
            FROM messages
            GROUP BY match_id
        ) latest ON latest.last_message_id = msg.id
    ) lm ON lm.match_id = m.id
    WHERE m.%1$s = :where_user_a OR m.%2$s = :where_user_b
    ORDER BY COALESCE(lm.sent_at, m.%3$s) DESC, m.id DESC',
    $matchUserAColumn,
    $matchUserBColumn,
    $matchTimeColumn,
    $messageBodyColumn,
    $messageTimeColumn
);

$statement = $pdo->prepare($sql);
$statement->execute([
    ':select_user' => $currentUserId,
    ':join_user' => $currentUserId,
    ':where_user_a' => $currentUserId,
    ':where_user_b' => $currentUserId,
]);
$matches = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

render_page_shell_start('find', 'Find Match', $csrfToken, $currentUserId, false, 'Questionnaire fit and consent-aware match actions');
?>
<?php render_service_under_development('Find Match', 'Service under development. Questionnaire, recommendation, matching and conversation controls are visible for testing only. This message will be revoked once this service is up to date on the next push.'); ?>

<section class="find-flow" aria-labelledby="find-title">
    <article class="find-card">
        <div class="find-progress" aria-label="Questionnaire progress">
            <?php $progress = count($savedAnswers) / max(1, count($catalog)); ?>
            <span class="<?= $progress > 0 ? 'is-active' : '' ?>"></span>
            <span class="<?= $progress >= 0.25 ? 'is-active' : '' ?>"></span>
            <span class="<?= $progress >= 0.50 ? 'is-active' : '' ?>"></span>
            <span class="<?= $progress >= 0.75 ? 'is-active' : '' ?>"></span>
        </div>
        <h2 id="find-title">Search with intention</h2>
        <p>Answer the same versioned questionnaire as other students. Scores appear only when enough shared answers exist; otherwise Bushisa labels the result as limited information.</p>
        <?php render_flash($formError, $formSuccess); ?>
        <form method="post" class="questionnaire-form">
            <?= csrf_input_field() ?>
            <input type="hidden" name="catalog_version" value="<?= e(BUSHISA_QUESTIONNAIRE_VERSION) ?>">
            <div class="answer-grid">
                <?php foreach ($catalog as $key => $question): ?>
                    <?php
                    $saved = $savedAnswers[$key] ?? [];
                    $selected = array_values(array_filter($saved['selected'] ?? [], 'is_string'));
                    $accepted = array_values(array_filter($saved['accepted_options'] ?? [], 'is_string'));
                    $isMulti = ($question['type'] ?? 'single') === 'multi';
                    ?>
                    <fieldset class="answer-option">
                        <legend><?= e($question['label'] ?? $key) ?></legend>
                        <div class="answer-option__choices">
                            <?php foreach (($question['options'] ?? []) as $optionId): ?>
                                <label>
                                    <input
                                        type="<?= $isMulti ? 'checkbox' : 'radio' ?>"
                                        name="answer_<?= e($key) ?><?= $isMulti ? '[]' : '' ?>"
                                        value="<?= e($optionId) ?>"
                                        <?= in_array($optionId, $selected, true) ? 'checked' : '' ?>
                                    >
                                    <span><?= e(matching_option_label((string) $optionId)) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="questionnaire-controls">
                            <label>
                                Fit
                                <select name="fit_mode_<?= e($key) ?>">
                                    <?php foreach (($question['supported_fit_modes'] ?? ['similar']) as $mode): ?>
                                        <option value="<?= e($mode) ?>" <?= (($saved['fit_mode'] ?? 'similar') === $mode) ? 'selected' : '' ?>><?= e(matching_option_label((string) $mode)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Importance
                                <select name="importance_<?= e($key) ?>">
                                    <?php foreach ([1 => 'Low', 2 => 'Normal', 3 => 'Requirement'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= ((int) ($saved['importance'] ?? 2) === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <details>
                            <summary>Accepted partner answers</summary>
                            <div class="answer-option__choices">
                                <?php foreach (($question['options'] ?? []) as $optionId): ?>
                                    <label>
                                        <input
                                            type="checkbox"
                                            name="accepted_<?= e($key) ?>[]"
                                            value="<?= e($optionId) ?>"
                                            <?= in_array($optionId, $accepted, true) ? 'checked' : '' ?>
                                        >
                                        <span><?= e(matching_option_label((string) $optionId)) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <button class="button" type="submit" disabled title="Service under development">Save questionnaire</button>
        </form>
    </article>

    <section aria-labelledby="recommended-title">
        <h2 class="section-title" id="recommended-title">Recommended students</h2>
        <?php if ($recommendations === []): ?>
            <div class="empty-state">
                <h2>No recommendations right now</h2>
                <p>Complete your profile, date of birth and preferences, or broaden your filters later.</p>
            </div>
        <?php else: ?>
            <div class="profile-grid">
                <?php foreach ($recommendations as $candidate): ?>
                    <article class="profile-card" data-candidate-id="<?= e($candidate['user_id'] ?? '') ?>">
                        <div class="profile-card__image">
                            <?= render_avatar($candidate['profile_photo_path'] ?? null, (string) ($candidate['display_name'] ?? 'Student')) ?>
                        </div>
                        <div class="profile-card__body">
                            <h3 class="profile-card__name"><?= e($candidate['display_name'] ?? 'Student') ?></h3>
                            <p class="profile-card__meta"><?= e(implode(' - ', array_filter([isset($candidate['age']) && $candidate['age'] !== null ? $candidate['age'] . ' years old' : null, $candidate['faculty'] ?? null, $candidate['year_of_study'] ?? null]))) ?></p>
                            <p class="profile-card__bio"><?= e(($candidate['bio'] ?? '') !== '' ? $candidate['bio'] : 'No bio added yet.') ?></p>
                            <p class="field-help"><?= e($candidate['score_label'] ?? 'Limited questionnaire information') ?><?= isset($candidate['questionnaire_fit']) && $candidate['questionnaire_fit'] !== null ? ': ' . e($candidate['questionnaire_fit']) . '%' : '' ?></p>
                            <div class="card-actions">
                                <button class="discover-card__action discover-card__action--like" type="button" disabled title="Service under development">Like</button>
                                <button class="discover-card__action" type="button" disabled title="Service under development">Pass</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section aria-labelledby="match-results-title">
        <h2 class="section-title" id="match-results-title">Established matches</h2>
        <?php if ($matches === []): ?>
            <div class="empty-state">
                <h2>No established matches yet</h2>
                <p>Mutual likes from Home will appear here. A match is separate from approved DM permission.</p>
                <a class="button-secondary" href="discover.php">Return Home</a>
            </div>
        <?php else: ?>
            <div class="match-list-page">
                <?php foreach ($matches as $match): ?>
                    <article class="match-row">
                        <div class="profile-summary">
                            <?= render_avatar($match['profile_photo_path'] ?? null, (string) ($match['display_name'] ?? 'Match')) ?>
                            <div>
                                <h3><?= e($match['display_name'] ?? 'Match') ?></h3>
                                <p class="muted"><?= e($match['faculty'] ?? 'NUST student') ?></p>
                                <?php if (!empty($match['last_message'])): ?>
                                    <p class="muted"><?= e($match['last_message']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a class="button-secondary" href="chat.php?match_id=<?= e($match['match_id'] ?? '') ?>">View development-status conversation</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
<?php
render_page_shell_end();
