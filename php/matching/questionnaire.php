<?php

declare(strict_types=1);

const BUSHISA_QUESTIONNAIRE_VERSION = 'matching_v1_2026_10_06';

/**
 * @return array<string, array<string, mixed>>
 */
function matching_questionnaire_catalog(): array
{
    return [
        'intent' => question_single('What connection are you looking for?', ['serious', 'casual', 'friendship_first', 'situational', 'exploring'], 2.0, ['similar', 'accepted_options', 'flexible']),
        'pace' => question_single('How quickly do you want a connection to develop?', ['slowly', 'gradually', 'quickly', 'flexible', 'one_night'], 1.0, ['similar', 'accepted_options', 'flexible'], ['slowly', 'gradually', 'quickly']),
        'communication_style' => question_single('How do you prefer staying in touch?', ['text', 'voice', 'calls', 'in_person', 'mixed', 'brief_checkins'], 1.0, ['similar', 'accepted_options', 'flexible']),
        'contact_frequency' => question_single('How often would you like contact?', ['occasional', 'few_weekly', 'daily', 'flexible'], 1.0, ['similar', 'accepted_options', 'flexible'], ['occasional', 'few_weekly', 'daily']),
        'social_activity' => question_single('What social setting suits you?', ['one_to_one', 'small_group', 'large_group', 'mixed'], 1.0, ['similar', 'complementary', 'accepted_options', 'flexible']),
        'social_energy' => question_single('How social is your usual week?', ['low', 'medium', 'high', 'variable'], 1.0, ['similar', 'complementary', 'flexible'], ['low', 'medium', 'high']),
        'interests' => question_multi('Which interests do you enjoy?', ['sport', 'music', 'reading', 'gaming', 'arts', 'tech', 'volunteering', 'outdoors_clubbing'], 1.2, 5),
        'preferred_activity' => question_single('What would you enjoy doing together?', ['coffee_walk', 'study', 'sport', 'creative_event', 'campus_event', 'social_outing'], 1.0, ['similar', 'accepted_options', 'flexible']),
        'availability' => question_multi('When are you usually free?', ['weekday_day', 'weekday_evening', 'weekend_day', 'weekend_evening'], 1.3, 4),
        'planning' => question_single('How do you plan social time?', ['advance', 'spontaneous', 'mixed'], 0.8, ['similar', 'complementary', 'accepted_options', 'flexible']),
        'conflict_style' => question_single('How do you prefer handling disagreement?', ['discuss_now', 'space_then_talk', 'write_then_discuss', 'flexible'], 1.2, ['similar', 'accepted_options', 'flexible']),
        'support_style' => question_single('What support do you appreciate?', ['listening', 'advice', 'practical_help', 'humour', 'mixed'], 1.0, ['similar', 'accepted_options', 'flexible']),
        'independence' => question_single('How much personal time do you prefer?', ['substantial', 'balanced', 'mostly_together', 'flexible'], 1.0, ['similar', 'accepted_options', 'flexible'], ['substantial', 'balanced', 'mostly_together']),
        'study_balance' => question_single('How do you balance study and social time?', ['study_first', 'balanced', 'social_first', 'varies'], 0.8, ['similar', 'accepted_options', 'flexible']),
        'smoking' => question_single('Which smoking pattern describes you?', ['non_smoker', 'occasional', 'regular', 'prefer_not_to_say'], 1.0, ['similar', 'accepted_options', 'flexible']),
        'boundaries' => question_single('How do you prefer discussing personal boundaries?', ['early_direct', 'gradually', 'ongoing_check_ins', 'flexible'], 1.2, ['similar', 'accepted_options', 'flexible']),
        'campus_activity' => question_multi('What campus activities appeal to you?', ['clubs', 'sport', 'cultural_events', 'volunteering', 'study_groups'], 1.0, 5),
        'long_distance' => question_single('How open are you to distance during breaks?', ['nearby_only', 'short_distance', 'long_distance_ok', 'unsure'], 0.8, ['similar', 'accepted_options', 'flexible']),
        'meeting_setting' => question_single('Where would you prefer a first meeting?', ['public_campus', 'public_off_campus', 'group_event', 'decide_together'], 1.1, ['similar', 'accepted_options', 'flexible']),
        'preferred_faculty' => question_single('How do you feel about faculty differences?', ['same_preferred', 'different_preferred', 'no_preference'], 0.7, ['similar', 'flexible']),
    ];
}

/**
 * @param array<int, string> $options
 * @param array<int, string> $fitModes
 * @param array<int, string>|null $ordinal
 * @return array<string, mixed>
 */
function question_single(string $label, array $options, float $weight, array $fitModes, ?array $ordinal = null): array
{
    return [
        'label' => $label,
        'type' => 'single',
        'options' => $options,
        'weight' => $weight,
        'supported_fit_modes' => $fitModes,
        'ordinal' => $ordinal,
    ];
}

/**
 * @param array<int, string> $options
 * @return array<string, mixed>
 */
function question_multi(string $label, array $options, float $weight, int $maxSelected): array
{
    return [
        'label' => $label,
        'type' => 'multi',
        'options' => $options,
        'weight' => $weight,
        'supported_fit_modes' => ['similar', 'accepted_options', 'flexible'],
        'max_selected' => $maxSelected,
        'ordinal' => null,
    ];
}

/**
 * @return array<string, mixed>
 */
function matching_questionnaire_public_catalog(): array
{
    $questions = [];
    foreach (matching_questionnaire_catalog() as $key => $question) {
        $questions[] = [
            'key' => $key,
            'label' => $question['label'],
            'type' => $question['type'],
            'options' => array_map(
                static fn (string $id): array => ['id' => $id, 'label' => matching_option_label($id)],
                $question['options']
            ),
            'supported_fit_modes' => $question['supported_fit_modes'],
            'max_selected' => $question['max_selected'] ?? 1,
        ];
    }

    return [
        'version' => BUSHISA_QUESTIONNAIRE_VERSION,
        'question_count' => count($questions),
        'questions' => $questions,
    ];
}

function matching_option_label(string $optionId): string
{
    $label = str_replace('_', ' ', $optionId);
    return ucwords($label);
}

/**
 * @param array<string, mixed> $payload
 * @return array{valid: bool, errors: array<string, string>, answers: array<string, array<string, mixed>>}
 */
function matching_validate_answers_payload(array $payload): array
{
    $catalog = matching_questionnaire_catalog();
    $version = isset($payload['catalog_version']) ? (string) $payload['catalog_version'] : '';
    if ($version !== BUSHISA_QUESTIONNAIRE_VERSION) {
        return [
            'valid' => false,
            'errors' => ['catalog_version' => 'Refresh the questionnaire before saving.'],
            'answers' => [],
        ];
    }

    $rawAnswers = isset($payload['answers']) && is_array($payload['answers']) ? $payload['answers'] : [];
    $answers = [];
    $errors = [];

    foreach ($rawAnswers as $key => $rawAnswer) {
        if (!is_string($key) || !isset($catalog[$key])) {
            $errors[(string) $key] = 'Unknown question.';
            continue;
        }

        if (!is_array($rawAnswer)) {
            $errors[$key] = 'Answer must be an object.';
            continue;
        }

        $question = $catalog[$key];
        $selected = normalise_selected_options($rawAnswer['selected'] ?? null);
        $accepted = normalise_selected_options($rawAnswer['accepted_options'] ?? []);
        $fitMode = isset($rawAnswer['fit_mode']) ? (string) $rawAnswer['fit_mode'] : 'similar';
        $importance = isset($rawAnswer['importance']) ? (int) $rawAnswer['importance'] : 2;

        if (!in_array($fitMode, $question['supported_fit_modes'], true)) {
            $errors[$key] = 'Unsupported fit mode.';
            continue;
        }

        if ($importance < 1 || $importance > 3) {
            $errors[$key] = 'Importance must be 1, 2 or 3.';
            continue;
        }

        $allowedOptions = array_flip($question['options']);
        foreach (array_merge($selected, $accepted) as $optionId) {
            if (!isset($allowedOptions[$optionId])) {
                $errors[$key] = 'Unknown option.';
                continue 2;
            }
        }

        if (($question['type'] ?? 'single') === 'single' && count($selected) > 1) {
            $errors[$key] = 'Choose one option.';
            continue;
        }

        $maxSelected = (int) ($question['max_selected'] ?? 1);
        if (($question['type'] ?? 'single') === 'multi' && count($selected) > $maxSelected) {
            $errors[$key] = 'Choose no more than ' . $maxSelected . ' options.';
            continue;
        }

        if ($selected === []) {
            continue;
        }

        $answers[$key] = [
            'selected' => $selected,
            'importance' => $importance,
            'fit_mode' => $fitMode,
            'accepted_options' => $accepted,
        ];
    }

    return [
        'valid' => $errors === [],
        'errors' => $errors,
        'answers' => $answers,
    ];
}

/**
 * @param mixed $value
 * @return array<int, string>
 */
function normalise_selected_options(mixed $value): array
{
    if (is_string($value)) {
        $items = [$value];
    } elseif (is_array($value)) {
        $items = $value;
    } else {
        $items = [];
    }

    $normalised = [];
    foreach ($items as $item) {
        if (!is_string($item)) {
            continue;
        }
        $clean = strtolower(trim(str_replace("\0", '', $item)));
        if ($clean !== '' && $clean !== 'skip' && $clean !== 'no_preference') {
            $normalised[$clean] = $clean;
        }
    }

    return array_values($normalised);
}

/**
 * @param array<string, array<string, mixed>> $answers
 */
function matching_save_answers(PDO $pdo, int $userId, array $answers): void
{
    $statement = $pdo->prepare(
        'INSERT INTO find_match_answers
            (user_id, question_key, catalog_version, answer_value, answer_json, importance, fit_mode, accepted_options_json, needs_review)
         VALUES
            (:user_id, :question_key, :catalog_version, :answer_value, :answer_json, :importance, :fit_mode, :accepted_options_json, 0)
         ON DUPLICATE KEY UPDATE
            catalog_version = VALUES(catalog_version),
            answer_value = VALUES(answer_value),
            answer_json = VALUES(answer_json),
            importance = VALUES(importance),
            fit_mode = VALUES(fit_mode),
            accepted_options_json = VALUES(accepted_options_json),
            needs_review = 0,
            updated_at = NOW()'
    );

    foreach ($answers as $key => $answer) {
        $selected = array_values(array_filter($answer['selected'] ?? [], 'is_string'));
        $accepted = array_values(array_filter($answer['accepted_options'] ?? [], 'is_string'));
        $statement->execute([
            ':user_id' => $userId,
            ':question_key' => $key,
            ':catalog_version' => BUSHISA_QUESTIONNAIRE_VERSION,
            ':answer_value' => implode(',', $selected),
            ':answer_json' => json_encode($selected, JSON_UNESCAPED_SLASHES),
            ':importance' => (int) ($answer['importance'] ?? 2),
            ':fit_mode' => (string) ($answer['fit_mode'] ?? 'similar'),
            ':accepted_options_json' => $accepted === [] ? null : json_encode($accepted, JSON_UNESCAPED_SLASHES),
        ]);
    }
}

/**
 * @param array<string, array<string, mixed>> $answers
 */
function matching_replace_answers(PDO $pdo, int $userId, array $answers): void
{
    $delete = $pdo->prepare('DELETE FROM find_match_answers WHERE user_id = :user_id');
    $delete->execute([':user_id' => $userId]);
    matching_save_answers($pdo, $userId, $answers);
}

/**
 * @param array<int, int> $userIds
 * @return array<int, array<string, array<string, mixed>>>
 */
function matching_load_answers(PDO $pdo, array $userIds): array
{
    $userIds = array_values(array_unique(array_filter($userIds, static fn (int $id): bool => $id > 0)));
    if ($userIds === []) {
        return [];
    }

    $params = [];
    $placeholders = [];
    foreach ($userIds as $index => $userId) {
        $placeholder = ':user_' . $index;
        $placeholders[] = $placeholder;
        $params[$placeholder] = $userId;
    }

    $statement = $pdo->prepare(
        'SELECT user_id, question_key, catalog_version, answer_value, answer_json, importance, fit_mode, accepted_options_json, needs_review
         FROM find_match_answers
         WHERE user_id IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($params);

    $catalog = matching_questionnaire_catalog();
    $answers = [];
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $userId = (int) $row['user_id'];
        $key = (string) $row['question_key'];
        if (!isset($catalog[$key])) {
            continue;
        }

        $selected = decode_answer_options($row['answer_json'] ?? null);
        if ($selected === []) {
            $selected = legacy_answer_to_options($key, (string) ($row['answer_value'] ?? ''));
        }
        if ($selected === []) {
            continue;
        }

        $accepted = decode_answer_options($row['accepted_options_json'] ?? null);
        $fitMode = (string) ($row['fit_mode'] ?? 'similar');
        if (!in_array($fitMode, $catalog[$key]['supported_fit_modes'], true)) {
            $fitMode = 'similar';
        }

        $answers[$userId][$key] = [
            'selected' => $selected,
            'importance' => max(1, min(3, (int) ($row['importance'] ?? 2))),
            'fit_mode' => $fitMode,
            'accepted_options' => $accepted,
            'needs_review' => (int) ($row['needs_review'] ?? 0) === 1,
        ];
    }

    return $answers;
}

/**
 * @param mixed $json
 * @return array<int, string>
 */
function decode_answer_options(mixed $json): array
{
    if (!is_string($json) || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    return normalise_selected_options($decoded);
}

/**
 * @return array<int, string>
 */
function legacy_answer_to_options(string $key, string $value): array
{
    $value = strtolower(trim($value));
    if ($value === '') {
        return [];
    }

    $catalog = matching_questionnaire_catalog();
    if (!isset($catalog[$key])) {
        return [];
    }

    $normalised = str_replace([' ', '/', '-'], '_', $value);
    $allowed = array_flip($catalog[$key]['options']);
    if (isset($allowed[$normalised])) {
        return [$normalised];
    }

    $parts = preg_split('/\s*,\s*/', $normalised) ?: [];
    $mapped = [];
    foreach ($parts as $part) {
        if (isset($allowed[$part])) {
            $mapped[] = $part;
        }
    }

    return array_values(array_unique($mapped));
}
