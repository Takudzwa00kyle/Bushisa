<?php

declare(strict_types=1);

require_once __DIR__ . '/php/security_headers.php';
require_once __DIR__ . '/php/session_manager.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php/csrf.php';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/function.php';
require_once __DIR__ . '/php/frontend.php';
require_once __DIR__ . '/php/matching/service.php';

require_login();

$currentUserId = (int) $_SESSION['user_id'];
$preferences = get_preferences($pdo, $currentUserId);
$candidates = [];
$establishedMatches = [];
$csrfToken = generate_csrf_token();

$matchColumnsStatement = $pdo->query('SHOW COLUMNS FROM matches');
$matchColumns = $matchColumnsStatement !== false ? $matchColumnsStatement->fetchAll(PDO::FETCH_COLUMN) : [];
$matchUserAColumn = in_array('user_a_id', $matchColumns, true) ? 'user_a_id' : 'user_one_id';
$matchUserBColumn = in_array('user_b_id', $matchColumns, true) ? 'user_b_id' : 'user_two_id';
$matchTimeColumn = in_array('matched_at', $matchColumns, true) ? 'matched_at' : 'created_at';

$matchesSql = sprintf(
    'SELECT
        m.id AS match_id,
        CASE WHEN m.%1$s = :select_user THEN m.%2$s ELSE m.%1$s END AS other_user_id,
        p.display_name,
        p.profile_photo_path,
        p.faculty,
        m.%3$s AS matched_at
     FROM matches m
     INNER JOIN profiles p
        ON p.user_id = CASE WHEN m.%1$s = :join_user THEN m.%2$s ELSE m.%1$s END
     WHERE m.%1$s = :where_user_a OR m.%2$s = :where_user_b
     ORDER BY m.%3$s DESC, m.id DESC
     LIMIT 12',
    $matchUserAColumn,
    $matchUserBColumn,
    $matchTimeColumn
);
$matchesStatement = $pdo->prepare($matchesSql);
$matchesStatement->execute([
    ':select_user' => $currentUserId,
    ':join_user' => $currentUserId,
    ':where_user_a' => $currentUserId,
    ':where_user_b' => $currentUserId,
]);
$establishedMatches = $matchesStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

$candidateResult = matching_get_ranked_candidates($pdo, $currentUserId, ['limit' => 10]);
if (($candidateResult['success'] ?? false) === true) {
    $candidates = $candidateResult['data']['results'] ?? [];
}

render_page_shell_start('home', 'Bushisa', $csrfToken, $currentUserId, true);
?>
<?php render_service_under_development('Discovery, matching and chat', 'Service under development. Matching actions, chat entry and notifications are visible for testing only. This message will be revoked once these services are up to date on the next push.'); ?>

<section class="matches-strip" aria-labelledby="matches-title">
    <h2 class="sr-only" id="matches-title">Established matches</h2>
    <?php if ($establishedMatches === []): ?>
        <div class="empty-state">
            <h2>No established matches yet</h2>
            <p>Find compatible students, send likes, and mutual likes will appear here. A match still is not chat permission.</p>
            <a class="button-secondary" href="matches.php">Go to Find Match</a>
        </div>
    <?php else: ?>
        <ul class="matches-strip__list" aria-label="Established matches">
            <?php foreach ($establishedMatches as $match): ?>
                <li>
                    <a class="match-avatar-link" href="chat.php?match_id=<?= e($match['match_id'] ?? '') ?>" aria-label="Open development-status chat with <?= e($match['display_name'] ?? 'match') ?>">
                        <?= render_avatar($match['profile_photo_path'] ?? null, (string) ($match['display_name'] ?? 'Match')) ?>
                        <span><?= e($match['display_name'] ?? 'Match') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section aria-labelledby="home-heading">
    <h2 class="hero-statement" id="home-heading">A little spark. A real connection.</h2>

    <?php if ($preferences === null): ?>
        <div class="empty-state">
            <h2>Set your discovery preferences</h2>
            <p>Bushisa needs basic preferences before it can show eligible nonmatches.</p>
            <a class="button" href="profile.php#preferences">Set preferences</a>
        </div>
    <?php elseif ($candidates === []): ?>
        <div class="empty-state">
            <h2>No discovery profiles right now</h2>
            <p>There are no eligible nonmatches for your current preferences. You can broaden your preferences or check back later.</p>
            <a class="button-secondary" href="profile.php#preferences">Edit preferences</a>
        </div>
    <?php else: ?>
        <div class="profile-grid" data-static-discovery>
            <?php foreach ($candidates as $candidate): ?>
                <?php
                $name = (string) ($candidate['display_name'] ?? 'Student');
                $age = isset($candidate['age']) ? (int) $candidate['age'] : null;
                $meta = array_filter([
                    $age !== null ? $age . ' years old' : null,
                    $candidate['faculty'] ?? null,
                    $candidate['year_of_study'] ?? null,
                ]);
                ?>
                <article class="profile-card" data-candidate-id="<?= e($candidate['user_id'] ?? '') ?>">
                    <div class="profile-card__image">
                        <?= render_avatar($candidate['profile_photo_path'] ?? null, $name) ?>
                    </div>
                    <div class="profile-card__body">
                        <h3 class="profile-card__name"><?= e($name) ?></h3>
                        <?php if ($meta !== []): ?>
                            <p class="profile-card__meta"><?= e(implode(' · ', $meta)) ?></p>
                        <?php endif; ?>
                        <p class="profile-card__bio"><?= e($candidate['bio'] !== '' ? $candidate['bio'] : 'No bio added yet.') ?></p>
                        <p class="field-help"><?= e($candidate['score_label'] ?? 'Limited questionnaire information') ?><?= isset($candidate['questionnaire_fit']) && $candidate['questionnaire_fit'] !== null ? ': ' . e($candidate['questionnaire_fit']) . '%' : '' ?></p>
                        <div class="card-actions">
                            <button class="discover-card__action discover-card__action--like" type="button" disabled title="Service under development">Like</button>
                            <button class="discover-card__action" type="button" disabled title="Service under development">Pass</button>
                            <a class="button-secondary" href="community_guidelines.php">Report</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
render_page_shell_end();
