<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../operational.php';
require_once __DIR__ . '/questionnaire.php';
require_once __DIR__ . '/scoring.php';

/**
 * @param array<string, mixed> $options
 * @return array<string, mixed>
 */
function matching_get_ranked_candidates(PDO $pdo, int $viewerId, array $options = []): array
{
    ensure_operational_schema($pdo);

    $viewerProfile = get_profile($pdo, $viewerId);
    $viewerPreferences = get_preferences($pdo, $viewerId);
    if ($viewerProfile === null || $viewerPreferences === null) {
        return [
            'success' => false,
            'error' => 'Complete your profile and discovery preferences before matching.',
            'status' => 400,
        ];
    }

    $viewerAge = matching_age_from_profile($viewerProfile);
    if ($viewerAge === null || $viewerAge < 18) {
        return [
            'success' => false,
            'error' => 'A valid adult date of birth is required before matching.',
            'status' => 400,
        ];
    }

    $limit = max(1, min(50, (int) ($options['limit'] ?? 20)));
    $offset = max(0, (int) ($options['offset'] ?? matching_offset_from_cursor((string) ($options['cursor'] ?? ''))));
    $filters = is_array($options['filters'] ?? null) ? $options['filters'] : [];

    $rows = matching_fetch_candidate_rows($pdo, $viewerId, $filters);
    $candidateIds = array_map(static fn (array $row): int => (int) $row['user_id'], $rows);
    $answersByUser = matching_load_answers($pdo, array_merge([$viewerId], $candidateIds));
    $viewerAnswers = $answersByUser[$viewerId] ?? [];

    $results = [];
    foreach ($rows as $row) {
        $candidateId = (int) $row['user_id'];
        $candidateProfile = matching_profile_from_row($row);
        $candidatePreferences = matching_preferences_from_row($row);
        $candidateAnswers = $answersByUser[$candidateId] ?? [];

        if (!matching_pair_is_eligible($viewerProfile, $viewerPreferences, $candidateProfile, $candidatePreferences, $viewerAnswers, $candidateAnswers)) {
            continue;
        }

        $score = matching_score_pair($viewerAnswers, $candidateAnswers);
        $rankValue = (float) $score['rank_value'];
        $rankValue += matching_profile_tiebreaker($viewerProfile, $viewerPreferences, $candidateProfile) / 100.0;

        $reasonCodes = matching_reason_codes($viewerProfile, $candidateProfile, $viewerAnswers, $candidateAnswers);
        $results[] = [
            'user_id' => $candidateId,
            'id' => $candidateId,
            'display_name' => (string) ($row['display_name'] ?? 'Student'),
            'bio' => (string) ($row['bio'] ?? ''),
            'gender' => (string) ($row['gender'] ?? ''),
            'faculty' => (string) ($row['faculty'] ?? ''),
            'year_of_study' => (string) ($row['year_of_study'] ?? ''),
            'profile_photo_path' => (string) ($row['profile_photo_path'] ?? ''),
            'age' => isset($row['age']) ? (int) $row['age'] : null,
            'questionnaire_fit' => $score['questionnaire_fit'],
            'coverage' => $score['coverage'],
            'score' => $score['score'],
            'score_label' => $score['score_label'],
            'common_answer_count' => $score['common_answer_count'],
            'reason_codes' => $reasonCodes,
            'action' => 'like',
            '_rank_value' => $rankValue,
        ];
    }

    usort($results, static function (array $left, array $right): int {
        $rank = ((float) $right['_rank_value'] <=> (float) $left['_rank_value']);
        return $rank !== 0 ? $rank : ((int) $left['user_id'] <=> (int) $right['user_id']);
    });

    $total = count($results);
    $paged = array_slice($results, $offset, $limit);
    foreach ($paged as &$item) {
        unset($item['_rank_value']);
    }
    unset($item);

    $nextOffset = $offset + count($paged);

    return [
        'success' => true,
        'data' => [
            'results' => $paged,
            'candidates' => $paged,
            'has_more' => $nextOffset < $total,
            'next_offset' => $nextOffset,
            'next_cursor' => $nextOffset < $total ? matching_cursor_from_offset($nextOffset) : null,
            'scoring_version' => BUSHISA_QUESTIONNAIRE_VERSION,
        ],
    ];
}

/**
 * @param array<string, mixed> $filters
 * @return array<int, array<string, mixed>>
 */
function matching_fetch_candidate_rows(PDO $pdo, int $viewerId, array $filters): array
{
    $matchColumns = operational_match_columns($pdo);
    $swipeColumns = operational_swipe_columns($pdo);
    $hasDob = operational_column_exists($pdo, 'profiles', 'date_of_birth');
    if (!$hasDob) {
        return [];
    }

    $profilePhotoSelect = operational_column_exists($pdo, 'profiles', 'profile_photo_path')
        ? 'p.profile_photo_path AS profile_photo_path'
        : (operational_column_exists($pdo, 'profiles', 'photo_1_url') ? 'p.photo_1_url AS profile_photo_path' : 'NULL AS profile_photo_path');
    $facultySelect = operational_column_exists($pdo, 'profiles', 'faculty') ? 'p.faculty AS faculty' : 'NULL AS faculty';
    $yearSelect = operational_column_exists($pdo, 'profiles', 'year_of_study') ? 'p.year_of_study AS year_of_study' : 'NULL AS year_of_study';
    $verifiedSql = operational_column_exists($pdo, 'users', 'is_verified') ? 'AND COALESCE(u.is_verified, 0) = 1' : '';
    $activeSql = operational_column_exists($pdo, 'users', 'is_suspended')
        ? 'AND COALESCE(u.is_suspended, 0) = 0'
        : (operational_column_exists($pdo, 'users', 'is_active') ? 'AND COALESCE(u.is_active, 1) = 1' : '');

    $params = [
        ':current_profile_user' => $viewerId,
        ':swipe_viewer' => $viewerId,
        ':match_viewer_a' => $viewerId,
        ':match_viewer_b' => $viewerId,
        ':blocker_viewer' => $viewerId,
        ':blocked_viewer' => $viewerId,
    ];

    $filterSql = '';
    $genderFilters = normalise_filter_values($filters['gender'] ?? []);
    if ($genderFilters !== []) {
        $placeholders = [];
        foreach ($genderFilters as $index => $gender) {
            $placeholder = ':filter_gender_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = strtolower($gender);
        }
        $filterSql .= ' AND LOWER(p.gender) IN (' . implode(', ', $placeholders) . ')';
    }

    $facultyFilters = normalise_filter_values($filters['faculty'] ?? []);
    if ($facultyFilters !== [] && operational_column_exists($pdo, 'profiles', 'faculty')) {
        $placeholders = [];
        foreach ($facultyFilters as $index => $faculty) {
            $placeholder = ':filter_faculty_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $faculty;
        }
        $filterSql .= ' AND p.faculty IN (' . implode(', ', $placeholders) . ')';
    }

    $ageMin = isset($filters['age_min']) ? (int) $filters['age_min'] : null;
    if ($ageMin !== null && $ageMin >= 18) {
        $filterSql .= ' AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) >= :filter_age_min';
        $params[':filter_age_min'] = $ageMin;
    }
    $ageMax = isset($filters['age_max']) ? (int) $filters['age_max'] : null;
    if ($ageMax !== null && $ageMax >= 18) {
        $filterSql .= ' AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) <= :filter_age_max';
        $params[':filter_age_max'] = $ageMax;
    }

    $sql = sprintf(
        'SELECT
            p.user_id,
            p.display_name,
            p.bio,
            p.gender,
            p.date_of_birth,
            TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age,
            %1$s,
            %2$s,
            %3$s,
            pref.preferred_gender,
            pref.age_range_min,
            pref.age_range_max,
            pref.preferred_faculty
         FROM profiles p
         INNER JOIN users u ON u.id = p.user_id
         INNER JOIN preferences pref ON pref.user_id = p.user_id
         WHERE p.user_id <> :current_profile_user
           %4$s
           %5$s
           AND p.date_of_birth IS NOT NULL
           AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) >= 18
           AND NOT EXISTS (
               SELECT 1 FROM swipes s
               WHERE s.swiper_id = :swipe_viewer
                 AND s.%6$s = p.user_id
           )
           AND NOT EXISTS (
               SELECT 1 FROM matches m
               WHERE (m.%7$s = :match_viewer_a AND m.%8$s = p.user_id)
                  OR (m.%8$s = :match_viewer_b AND m.%7$s = p.user_id)
           )
           AND NOT EXISTS (
               SELECT 1 FROM blocks b
               WHERE (b.blocker_id = :blocker_viewer AND b.blocked_id = p.user_id)
                  OR (b.blocker_id = p.user_id AND b.blocked_id = :blocked_viewer)
           )
           %9$s
         ORDER BY p.user_id ASC
         LIMIT 500',
        $facultySelect,
        $yearSelect,
        $profilePhotoSelect,
        $verifiedSql,
        $activeSql,
        $swipeColumns['target'],
        $matchColumns['user_a'],
        $matchColumns['user_b'],
        $filterSql
    );

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param mixed $value
 * @return array<int, string>
 */
function normalise_filter_values(mixed $value): array
{
    $items = is_array($value) ? $value : (is_string($value) ? [$value] : []);
    $normalised = [];
    foreach ($items as $item) {
        if (!is_string($item)) {
            continue;
        }
        $clean = trim(str_replace("\0", '', $item));
        if ($clean !== '') {
            $normalised[] = $clean;
        }
    }

    return array_values(array_unique($normalised));
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function matching_profile_from_row(array $row): array
{
    return [
        'user_id' => (int) ($row['user_id'] ?? 0),
        'gender' => (string) ($row['gender'] ?? ''),
        'date_of_birth' => (string) ($row['date_of_birth'] ?? ''),
        'faculty' => (string) ($row['faculty'] ?? ''),
        'year_of_study' => (string) ($row['year_of_study'] ?? ''),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function matching_preferences_from_row(array $row): array
{
    return [
        'preferred_gender' => (string) ($row['preferred_gender'] ?? ''),
        'age_range_min' => isset($row['age_range_min']) ? (int) $row['age_range_min'] : 18,
        'age_range_max' => isset($row['age_range_max']) ? (int) $row['age_range_max'] : 30,
        'preferred_faculty' => (string) ($row['preferred_faculty'] ?? 'Any'),
    ];
}

/**
 * @param array<string, mixed> $viewerProfile
 * @param array<string, mixed> $viewerPreferences
 * @param array<string, mixed> $candidateProfile
 * @param array<string, mixed> $candidatePreferences
 * @param array<string, array<string, mixed>> $viewerAnswers
 * @param array<string, array<string, mixed>> $candidateAnswers
 */
function matching_pair_is_eligible(
    array $viewerProfile,
    array $viewerPreferences,
    array $candidateProfile,
    array $candidatePreferences,
    array $viewerAnswers,
    array $candidateAnswers
): bool {
    $viewerAge = matching_age_from_profile($viewerProfile);
    $candidateAge = matching_age_from_profile($candidateProfile);
    if ($viewerAge === null || $candidateAge === null || $viewerAge < 18 || $candidateAge < 18) {
        return false;
    }

    if (!matching_preferences_accept($viewerPreferences, $candidateProfile, $candidateAge)) {
        return false;
    }

    if (!matching_preferences_accept($candidatePreferences, $viewerProfile, $viewerAge)) {
        return false;
    }

    if (matching_has_hard_answer_incompatibility($viewerAnswers, $candidateAnswers)) {
        return false;
    }

    return !matching_has_hard_answer_incompatibility($candidateAnswers, $viewerAnswers);
}

/**
 * @param array<string, mixed> $preferences
 * @param array<string, mixed> $targetProfile
 */
function matching_preferences_accept(array $preferences, array $targetProfile, int $targetAge): bool
{
    $preferredGender = strtolower((string) ($preferences['preferred_gender'] ?? ''));
    $targetGender = strtolower((string) ($targetProfile['gender'] ?? ''));
    if ($preferredGender !== '' && $targetGender !== '' && $preferredGender !== $targetGender) {
        return false;
    }

    $ageMin = max(18, (int) ($preferences['age_range_min'] ?? 18));
    $ageMax = (int) ($preferences['age_range_max'] ?? 30);
    if ($ageMax < $ageMin) {
        $ageMax = $ageMin;
    }

    return $targetAge >= $ageMin && $targetAge <= $ageMax;
}

/**
 * @param array<string, mixed> $profile
 */
function matching_age_from_profile(array $profile): ?int
{
    if (isset($profile['age']) && is_numeric($profile['age'])) {
        return (int) $profile['age'];
    }

    $dob = (string) ($profile['date_of_birth'] ?? '');
    if ($dob === '') {
        return null;
    }

    try {
        $birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
        $errors = DateTimeImmutable::getLastErrors();
        if ($birthDate === false
            || ($errors !== false && ((int) $errors['warning_count'] > 0 || (int) $errors['error_count'] > 0))
            || $birthDate->format('Y-m-d') !== $dob
        ) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        if ($birthDate > $today) {
            return null;
        }

        return (int) $birthDate->diff($today)->y;
    } catch (Throwable) {
        return null;
    }
}

/**
 * @param array<string, mixed> $viewerProfile
 * @param array<string, mixed> $viewerPreferences
 * @param array<string, mixed> $candidateProfile
 */
function matching_profile_tiebreaker(array $viewerProfile, array $viewerPreferences, array $candidateProfile): float
{
    $score = 0.0;
    if (($viewerPreferences['preferred_faculty'] ?? 'Any') !== 'Any'
        && ($viewerPreferences['preferred_faculty'] ?? '') === ($candidateProfile['faculty'] ?? '')
    ) {
        $score += 6.0;
    }
    if (($viewerProfile['faculty'] ?? '') !== '' && ($viewerProfile['faculty'] ?? '') === ($candidateProfile['faculty'] ?? '')) {
        $score += 3.0;
    }
    if (($viewerProfile['year_of_study'] ?? '') !== '' && ($viewerProfile['year_of_study'] ?? '') === ($candidateProfile['year_of_study'] ?? '')) {
        $score += 1.0;
    }

    return $score;
}

/**
 * @param array<string, mixed> $viewerProfile
 * @param array<string, mixed> $candidateProfile
 * @param array<string, array<string, mixed>> $viewerAnswers
 * @param array<string, array<string, mixed>> $candidateAnswers
 * @return array<int, string>
 */
function matching_reason_codes(array $viewerProfile, array $candidateProfile, array $viewerAnswers, array $candidateAnswers): array
{
    $reasons = [];
    foreach (['interests', 'availability', 'campus_activity'] as $key) {
        if (!isset($viewerAnswers[$key], $candidateAnswers[$key])) {
            continue;
        }
        $overlap = array_intersect(
            array_values(array_filter($viewerAnswers[$key]['selected'] ?? [], 'is_string')),
            array_values(array_filter($candidateAnswers[$key]['selected'] ?? [], 'is_string'))
        );
        if ($overlap !== []) {
            $reasons[] = 'shared_' . $key;
        }
    }

    if (($viewerProfile['faculty'] ?? '') !== '' && ($viewerProfile['faculty'] ?? '') === ($candidateProfile['faculty'] ?? '')) {
        $reasons[] = 'same_faculty';
    }

    return array_values(array_unique(array_slice($reasons, 0, 4)));
}

function matching_cursor_from_offset(int $offset): string
{
    return rtrim(strtr(base64_encode(json_encode(['offset' => $offset], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
}

function matching_offset_from_cursor(string $cursor): int
{
    if ($cursor === '') {
        return 0;
    }

    $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
    if ($decoded === false) {
        return 0;
    }

    $payload = json_decode($decoded, true);
    return is_array($payload) && isset($payload['offset']) ? max(0, (int) $payload['offset']) : 0;
}
