<?php

declare(strict_types=1);

require_once __DIR__ . '/questionnaire.php';

/**
 * @param array<string, array<string, mixed>> $viewerAnswers
 * @param array<string, array<string, mixed>> $candidateAnswers
 * @return array<string, mixed>
 */
function matching_score_pair(array $viewerAnswers, array $candidateAnswers): array
{
    $catalog = matching_questionnaire_catalog();
    $viewerDirection = matching_directional_score($viewerAnswers, $candidateAnswers, $catalog);
    $candidateDirection = matching_directional_score($candidateAnswers, $viewerAnswers, $catalog);

    $dViewer = $viewerDirection['score'];
    $dCandidate = $candidateDirection['score'];
    $coverage = min($viewerDirection['coverage'], $candidateDirection['coverage']);
    $commonCount = count(array_intersect(array_keys($viewerAnswers), array_keys($candidateAnswers)));

    $q = null;
    if ($dViewer !== null && $dCandidate !== null) {
        $q = sqrt($dViewer * $dCandidate);
    }

    $qc = $q === null ? 0.5 : ($coverage * $q) + ((1.0 - $coverage) * 0.5);
    $displayScore = ($q !== null && $commonCount >= 10 && $coverage >= 0.50)
        ? (int) round($q * 100)
        : null;

    return [
        'questionnaire_fit' => $displayScore,
        'score' => $displayScore,
        'coverage' => round($coverage, 3),
        'rank_value' => $qc,
        'common_answer_count' => $commonCount,
        'score_label' => $displayScore === null ? 'Limited questionnaire information' : 'Questionnaire fit',
        'scoring_version' => BUSHISA_QUESTIONNAIRE_VERSION,
        'components' => [
            'viewer_direction' => $dViewer,
            'candidate_direction' => $dCandidate,
            'confidence_adjusted' => round($qc, 3),
        ],
    ];
}

/**
 * @param array<string, array<string, mixed>> $sourceAnswers
 * @param array<string, array<string, mixed>> $targetAnswers
 * @param array<string, array<string, mixed>> $catalog
 * @return array{score: float|null, coverage: float}
 */
function matching_directional_score(array $sourceAnswers, array $targetAnswers, array $catalog): array
{
    $weightedScore = 0.0;
    $weightedDenominator = 0.0;
    $coveredBaseWeight = 0.0;
    $sourceBaseWeight = 0.0;

    foreach ($sourceAnswers as $key => $sourceAnswer) {
        if (!isset($catalog[$key])) {
            continue;
        }

        $question = $catalog[$key];
        $baseWeight = (float) ($question['weight'] ?? 1.0);
        if (($sourceAnswer['fit_mode'] ?? 'similar') !== 'flexible') {
            $sourceBaseWeight += $baseWeight;
        }

        if (!isset($targetAnswers[$key])) {
            continue;
        }

        $compatibility = matching_question_compatibility($key, $sourceAnswer, $targetAnswers[$key], $question);
        if ($compatibility === null) {
            continue;
        }

        $importance = max(1, min(3, (int) ($sourceAnswer['importance'] ?? 2)));
        $coveredBaseWeight += $baseWeight;
        $weightedScore += $baseWeight * $importance * $compatibility;
        $weightedDenominator += $baseWeight * $importance;
    }

    return [
        'score' => $weightedDenominator > 0.0 ? $weightedScore / $weightedDenominator : null,
        'coverage' => $sourceBaseWeight > 0.0 ? min(1.0, $coveredBaseWeight / $sourceBaseWeight) : 0.0,
    ];
}

/**
 * @param array<string, mixed> $sourceAnswer
 * @param array<string, mixed> $targetAnswer
 * @param array<string, mixed> $question
 */
function matching_question_compatibility(string $key, array $sourceAnswer, array $targetAnswer, array $question): ?float
{
    $sourceSelected = array_values(array_filter($sourceAnswer['selected'] ?? [], 'is_string'));
    $targetSelected = array_values(array_filter($targetAnswer['selected'] ?? [], 'is_string'));
    if ($sourceSelected === [] || $targetSelected === []) {
        return null;
    }

    $fitMode = (string) ($sourceAnswer['fit_mode'] ?? 'similar');
    if ($fitMode === 'flexible') {
        return null;
    }

    $acceptedOptions = array_values(array_filter($sourceAnswer['accepted_options'] ?? [], 'is_string'));
    if ($fitMode === 'accepted_options' || $acceptedOptions !== []) {
        return matching_set_overlap($acceptedOptions, $targetSelected) > 0.0 ? 1.0 : 0.0;
    }

    if ($fitMode === 'complementary') {
        return matching_complementary_score($key, $sourceSelected, $targetSelected);
    }

    if (($question['type'] ?? 'single') === 'multi') {
        return matching_jaccard($sourceSelected, $targetSelected);
    }

    $sourceValue = $sourceSelected[0] ?? '';
    $targetValue = $targetSelected[0] ?? '';
    if ($sourceValue === $targetValue) {
        return 1.0;
    }

    $ordinal = isset($question['ordinal']) && is_array($question['ordinal']) ? array_values($question['ordinal']) : [];
    if ($ordinal !== []) {
        $sourceIndex = array_search($sourceValue, $ordinal, true);
        $targetIndex = array_search($targetValue, $ordinal, true);
        if ($sourceIndex !== false && $targetIndex !== false && count($ordinal) > 1) {
            return max(0.0, 1.0 - (abs((int) $sourceIndex - (int) $targetIndex) / (count($ordinal) - 1)));
        }
    }

    if (in_array($sourceValue, ['flexible', 'mixed', 'varies', 'variable', 'decide_together', 'unsure'], true)) {
        return 0.7;
    }

    return 0.0;
}

/**
 * @param array<int, string> $sourceSelected
 * @param array<int, string> $targetSelected
 */
function matching_complementary_score(string $key, array $sourceSelected, array $targetSelected): float
{
    $pairs = [
        'social_energy' => [
            'low' => ['medium'],
            'medium' => ['low', 'high', 'variable'],
            'high' => ['medium'],
            'variable' => ['medium'],
        ],
        'social_activity' => [
            'one_to_one' => ['small_group'],
            'small_group' => ['one_to_one', 'large_group', 'mixed'],
            'large_group' => ['small_group', 'mixed'],
            'mixed' => ['one_to_one', 'small_group', 'large_group'],
        ],
        'planning' => [
            'advance' => ['mixed'],
            'spontaneous' => ['mixed'],
            'mixed' => ['advance', 'spontaneous'],
        ],
    ];

    foreach ($sourceSelected as $source) {
        foreach ($targetSelected as $target) {
            if ($source === $target) {
                return 0.8;
            }
            if (isset($pairs[$key][$source]) && in_array($target, $pairs[$key][$source], true)) {
                return 1.0;
            }
        }
    }

    return 0.0;
}

/**
 * @param array<int, string> $left
 * @param array<int, string> $right
 */
function matching_jaccard(array $left, array $right): float
{
    $left = array_values(array_unique($left));
    $right = array_values(array_unique($right));
    $union = array_values(array_unique(array_merge($left, $right)));
    if ($union === []) {
        return 0.0;
    }

    return count(array_intersect($left, $right)) / count($union);
}

/**
 * @param array<int, string> $left
 * @param array<int, string> $right
 */
function matching_set_overlap(array $left, array $right): float
{
    $left = array_values(array_unique($left));
    $right = array_values(array_unique($right));
    if ($left === [] || $right === []) {
        return 0.0;
    }

    return count(array_intersect($left, $right)) > 0 ? 1.0 : 0.0;
}

/**
 * @param array<string, array<string, mixed>> $sourceAnswers
 * @param array<string, array<string, mixed>> $targetAnswers
 */
function matching_has_hard_answer_incompatibility(array $sourceAnswers, array $targetAnswers): bool
{
    foreach (['intent', 'smoking', 'long_distance', 'meeting_setting'] as $key) {
        if (!isset($sourceAnswers[$key], $targetAnswers[$key])) {
            continue;
        }

        $source = $sourceAnswers[$key];
        $accepted = array_values(array_filter($source['accepted_options'] ?? [], 'is_string'));
        if ((int) ($source['importance'] ?? 2) < 3 || $accepted === []) {
            continue;
        }

        $targetSelected = array_values(array_filter($targetAnswers[$key]['selected'] ?? [], 'is_string'));
        if (matching_set_overlap($accepted, $targetSelected) === 0.0) {
            return true;
        }
    }

    return false;
}
