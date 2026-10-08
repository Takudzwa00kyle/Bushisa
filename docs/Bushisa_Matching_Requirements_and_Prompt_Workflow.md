# Bushisa matching system: requirements and implementation prompts

Prepared 6 October 2026 (Africa/Harare). Updated to incorporate the supplied reciprocal-matching strategy on the same date. Target: plain PHP, native PDO, MySQL and browser JavaScript; planning scale: approximately 5,000 students. This is an implementation specification, not a claim that the proposed algorithm has been built or validated.

## 1. Repository baseline and scope

Reviewed repository: https://github.com/Takudzwa00kyle/Bushisa

Reviewed default-branch HEAD: `c35f90d5a3a8955748b4e7c85de62f434138a7a3`, dated 5 October 2026. Review was performed from a shallow Git clone. Refresh the commit and inspect the deployed schema before implementing; local changes may differ.

The user-supplied additional reciprocal-matching strategy was incorporated directly in this revision, including directional scoring, balanced exposure, questionnaire breadth, meaningful outcomes and a simple-first daily-shortlist release. This new text was supplied in the conversation, independently of the inaccessible share URL.

The supplied shared discussion, https://chatgpt.com/share/6ac41d6b-216c-83ea-8692-0e299735d4e7, was not retrievable. No claims in this document are attributed to its unseen contents.

### Observed gaps

| Evidence in current code | Gap | Required correction |
|---|---|---|
| `api/find_match.php` accepts five keys: intent, interests, social_activity, communication_style, availability | Answers are free-form strings; no option catalog or version validation | Publish a versioned catalog of at most 20 multiple-choice questions; validate option IDs |
| The same API saves answers but its GET scoring starts at 40 and adds gender/faculty/year points | Stored answers do not contribute to the displayed “Questionnaire compatibility” | Score paired questionnaire answers; remove that misleading label until implemented |
| `discover.php` uses `ORDER BY RAND() LIMIT 10` | Discovery ignores the questionnaire and learned ranking | Route discovery through one shared matching service |
| `matches.php` shows established matches and disabled questionnaire controls | No complete questionnaire flow connected to recommendations | Implement save/resume/edit questionnaire and a scored candidate view |
| `js/discover.js` calls `api/discover.php`; that file is absent in the reviewed tree | Client and server contract gap | Implement the endpoint or explicitly align client routing, selectors and response shape |
| `api/find_match.php` treats preferred gender as score points, without reciprocal age/gender eligibility | An unsuitable candidate can still be ranked | Apply both users’ hard preferences before scoring |
| `database/bushisa.sql` and `sql/schema.sql` differ in users, profiles, swipes, matches and messages | Schema drift and silent fallbacks | Adopt one canonical schema and additive versioned migrations |
| `database/bushisa.sql` drops existing tables during import | Unsafe upgrade path | Never use this destructive bootstrap file as an upgrade migration |
| `config/db.php` sets `PDO::ATTR_EMULATE_PREPARES` to false; repeated placeholders remain in `api/find_match.php` and `api/swipe.php` | Native prepare failures remain possible | Unique placeholders and real MySQL integration tests; do not enable emulation as a workaround |
| `api/find_match.php` joins message requests directly | Multiple requests can duplicate candidates and give ambiguous action state | Aggregate or select one authoritative request state per pair |
| No geographic matching pipeline or impression/training store was found in the inspected matching paths | Nearby retrieval and continuous learning lack data | Add consented approximate location, exposure logging and versioned feature jobs |
| `api/swipe.php` checks for existing swipe/match before insertion | Simultaneous actions need stronger race handling | Canonical pair uniqueness, transaction-safe locking and idempotent outcomes |

The review is targeted, not a full security audit. Preserve authentication, student verification, adult eligibility, blocks, suspension, reporting and separate messaging consent. Recommendations suggest people; only explicit reciprocal likes create a match; a match alone never authorizes a DM.

## 2. Product objective and borrowed strategies

Optimize mutually wanted introductions, approved conversations and user-reported connection quality. Time spent, profile dwell and swipe volume are diagnostic signals, not success objectives. A questionnaire score is a similarity indicator, never a scientifically established probability of romantic success.

TikTok documents interaction feedback, different signal strengths, diversification and exploration [S1]. Meta describes retrieval followed by ranking stages [S2] and combining predictions about different outcomes [S3]. Reciprocal recommendation research highlights the need for mutual interest and the trade-off with exposure fairness [S4]. Adapt these public concepts; do not claim access to the platforms’ proprietary algorithms or exact weights.

| Strategy | Bushisa implementation | Start condition / fallback |
|---|---|---|
| Behavioural signals | Exposure-aware likes, passes, approved requests and reciprocal replies | Log immediately; use weak signals cautiously |
| Collaborative filtering | Sparse user-to-candidate interactions; similar users’ preferences and candidate neighbourhoods | Experimental after exposure data has enough coverage; otherwise questionnaire ranking |
| Embeddings | Versioned numeric questionnaire/profile vectors, then trained interaction vectors | Start with transparent fixed vectors; learned vectors only if evaluated |
| Graph prediction | Shared opt-in clubs/interests/activities and permitted connection overlap | No mandatory social graph; missing graph receives neutral contribution |
| Context and recent behaviour | Current preferences, coarse location freshness and decayed recent interactions | Preference changes invalidate cached recommendations immediately |
| Exploration | Small controlled allocation to eligible underexposed or uncertain candidates | Never bypass hard filters or repeated rejection |
| Several outcomes | Mutual interest, DM approval, reciprocal conversation, satisfaction | Start with heuristic features; label probabilities only after calibration |
| Nearby people | Coarse zone or consented approximate distance; two-sided radius constraints | Location denied: manual campus zone or non-geographic ranking |

A “people you might know” graph is a candidate source, not evidence of romantic compatibility. Shared faculty alone is weak evidence. Do not reveal someone’s private match, dating, messaging or block graph to explain a recommendation. Actual contact import is outside the initial scope.

### Explicit priorities from the supplied additional strategy

The core upgrade is **mutual compatibility plus balanced exposure**, with success measured by connections students value. The initial release must deliver structured preferences, paired questionnaire scoring, a small varied daily shortlist and exposure/outcome logging in the existing PHP/MySQL stack. Learned ranking is a later evidence-gated enhancement.

These requirements apply throughout the implementation, rather than forming a separate alternative algorithm:
- Require compatible declared relationship intentions before scoring when either user marks intent as a requirement. Otherwise use an explicit intent compatibility matrix as a soft feature; do not assume “exploring” excludes everyone.
- Let each student distinguish requirements from soft preferences. “Must want a serious relationship” excludes incompatible candidates; “prefer my faculty” normally influences score.
- Measure values, boundaries, lifestyle and practical opportunities to meet, alongside shared interests.
- Support similarity, complementary differences and flexible/no-preference choices selected by the student; do not assume matching personalities predict better relationships.
- Rank the entire shortlist for activity, balanced opportunity, variety, freshness and controlled exploration, rather than independently selecting the same popular profiles for every viewer.
- Learn from mutual likes, approved reciprocal conversations and optional worthwhile-connection feedback; reject session duration or message volume as proof of success.
- Student numbers establish university membership internally and must remain private in profiles, candidate responses, events, explanations, embeddings and model exports.

## 3. Non-negotiable eligibility policy

Run one eligibility function before retrieval scoring, again before returning a cached page, and before accepting a like or creating a match.

Both users must be verified, eligible adults, discoverable and not deleted, suspended or paused. Date of birth must be valid; absent DOB excludes a profile until completed. Map account status explicitly during migration rather than letting absent fields default to eligibility.

Exclude self, either-direction blocks, existing matches from discovery, existing likes/passes under the reviewed one-swipe-per-pair policy, and incompatible hard preferences. Keep established matches in their own view. Do not re-show a passed profile unless the product explicitly adds a user-controlled reset policy; do not silently relax this rule for exploration.

Gender and age preferences are reciprocal. Faculty, relationship intent and radius are hard only when explicitly marked as dealbreakers. Ordinary questionnaire similarity never overrides a dealbreaker. Do not change the existing gender enums silently; an inclusive identity/preference extension must have its own schema, UX and compatibility migration.

If a strict radius is configured and reliable location is absent, do not claim the pair is inside it. Return a clear location-required state or ask the user to remove that restriction. Empty pools explain which user-controlled filters can be broadened; never broaden them automatically.

## 4. Questionnaire contract: two users, at most 20 questions

Each person answers the same active catalog separately. Save their own answer and, where meaningful, acceptable partner answers and importance. Use single-choice unless explicitly marked multi-select. Include `skip`/`no_preference`; neither counts as a mismatch. Save partial progress; allow later edits. Never accept client-provided scores or weights.

The following 20-question starter catalog is proposed, not validated psychometrics. Option IDs are lowercase stable codes; labels can change without reinterpreting existing answers. Each row’s options also permits skip. Multi-select choices are sets, not arbitrary comma-separated text.

| # / key | Question | Proposed choices | Comparison |
|---|---|---|---|
| 1 intent | What connection are you looking for? | serious, casual, friendship_first,situational, exploring | Explicit compatibility matrix; optional dealbreaker |
| 2 pace | How quickly do you want a connection to develop? | slowly, gradually, quickly, flexible, one night | Ordinal / partner acceptance |
| 3 communication_style | How do you prefer staying in touch? | text, voice, calls, in_person, mixed, bed | Partner acceptance matrix |
| 4 contact_frequency | How often would you like contact? | occasional, few_weekly, daily, flexible | Ordinal / partner acceptance |
| 5 social_activity | What social setting suits you? | one_to_one, small_group, large_group, mixed | Matrix |
| 6 social_energy | How social is your usual week? | low, medium, high, variable | Ordinal |
| 7 interests | Which interests do you enjoy? (multi-select, max 5) | sport, music, reading, gaming, arts, tech, volunteering, outdoors/clubbing | Weighted Jaccard |
| 8 preferred_activity | What would you enjoy doing together? | coffee_walk, study, sport, creative_event, campus_event, smoke | Matrix |
| 9 availability | When are you usually free? (multi-select) | weekday_day, weekday_evening, weekend_day, weekend_evening | Set overlap |
| 10 planning | How do you plan social time? | advance, spontaneous, mixed | Matrix |
| 11 conflict_style | How do you prefer handling disagreement? | discuss_now, silent treatment, write_then_discuss, flexible | Acceptance matrix, no “best” answer |
| 12 support_style | What support do you appreciate? | listening, advice, practical_help,inside_joke, mixed | Acceptance matrix |
| 13 independence | How much personal time do you prefer? | substantial, balanced, mostly_together, flexible | Ordinal |
| 14 study_balance | How do you balance study and social time? | study_first, balanced, social_first, varies | Matrix |
| 15 smoking | Which smoking pattern describes you? | non_smoker, occasional, regular, prefer_not_to_say | Partner accepted options; optional explicit dealbreaker |
| 16 boundaries | How do you prefer discussing personal boundaries? | early_direct, gradually, ongoing_check_ins, flexible | Accepted options / explicit matrix, never consent inferred from an answer |
| 17 campus_activity | What campus activities appeal to you? (multi-select) | clubs, sport, cultural_events, volunteering, study_groups | Set overlap |
| 18 long_distance | How open are you to distance during breaks? | nearby_only, short_distance, long_distance_ok, unsure | Acceptance / optional dealbreaker |
| 19 meeting_setting | Where would you prefer a first meeting? | public_campus, public_off_campus, group_event, decide_together | Acceptance matrix |
| 20 preferred_faculty | How do you feel about faculty differences? | same_preferred, different_preferred, no_preference | Apply to real faculty values |

Keys preserve the existing five where feasible. Review `availability`, `interests` and other old free-form answers through a mapping migration; unmappable values become “needs review,” never guessed. Store gender/age/radius and other mandatory eligibility preferences outside the 20-question catalog. Lifestyle questions are optional and explicit: “prefer not to say” is missing evidence, never inferred as smoking or non-smoking. A required known non-smoking preference cannot be satisfied by unknown data; otherwise unknown is neutral.

### Compatibility dimensions and user-selected fit

| Dimension | Inputs in the 20-question catalog and structured profile | Matching rule |
|---|---|---|
| Relationship intentions | intent, pace, declared required partner intent | Reciprocal hard acceptance if required; otherwise explicit soft matrix |
| Values and expectations | communication, contact, conflict, support, independence, boundaries | Each user's acceptable partner choices and importance, without labeling answers morally better |
| Lifestyle | smoking, social energy, routines and study balance | Optional lifestyle dealbreakers or soft fit; no hidden inference |
| Interests | interests, preferred activities, campus activities | Overlap or explicitly desired complementary interests |
| Practical compatibility | campus zone, availability, meeting setting and distance during breaks | Meeting opportunity, with consented geography and explicit radius requirements |
| Personal preferences | preferred partner option IDs, faculty preference, desired similarities/differences | Directional fit chosen by the student, rather than enforced personality sameness |

For each supported question, store `fit_mode` as `similar`, `complementary`, `accepted_options` or `flexible`, alongside partner option IDs and importance. A question's published version declares which modes it supports. “Complementary” uses an explicit domain matrix selected by the student; never compute it universally as 1 minus similarity. Flexible means no contribution to that user's directional denominator, not automatic perfect compatibility. Compute coverage per direction using the informative eligible questions; expose the conservative minimum coverage for the pair and use it as C below. If a direction has no informative shared answers, that direction's score is null and pair Q is null. A non-answer and “no preference” carry no evidence of fit.

Publish the scoring matrices with product review before activation. Changing matrices, modes or weights increments the scoring version; old answer versions remain interpretable. The 20-question count excludes optional importance/accepted-option settings, which are preferences attached to the same question, not extra questionnaire questions.

### Deterministic scoring

For common valid answered questions J, define per-question directional compatibility c_j(u,v) in [0,1], weight w_j > 0 and user importance i_uj in {1,2,3}. Validate and version each matrix; categorical option IDs are never numeric ordinals. For real ordinal questions use an explicitly ordered list. For multi-select, Jaccard = intersection size / union size; empty sets are missing, not perfect compatibility.

Directional score D(u,v) = sum(w_j * i_uj * c_j(u,v)) / sum(w_j * i_uj), over informative shared questions for that direction, excluding flexible modes. Pair score Q = sqrt(D(u,v) * D(v,u)). Initially symmetric matrices produce symmetric scores unless partner acceptance/importance differs. Persist components and version for reproducibility.

Directional coverage C_u = sum of informative shared-answer base weights / sum of base weights for u’s informative active questions; pair coverage C = min(C_u, C_v). For a zero directional denominator, C_u=0. Initially require at least 10 shared answers and C >= 0.50 to show a questionnaire score; below that display “limited questionnaire information.” Allow otherwise eligible recommendations, with confidence reduced. These thresholds are adjustable launch defaults, not research findings.

Missing answers are excluded from the denominator, but high sparse-answer scores must not outrank comprehensive pairs solely because they skipped. Proposed confidence-adjusted feature Qc = C*Q + (1-C)*0.5. No common answers returns Q=null, C=0 and Qc=0.5. Rank uses Qc; UI can show round(100*Q) with coverage and “questionnaire fit,” never “chance of finding love.”

Acceptance examples: identical complete symmetric answers score 1; a 0.9/0.4 directional pair produces about 0.60; a 0.9/0.2 pair produces sqrt(0.18), about 0.424, below a 0.7/0.7 pair scoring 0.7; 100% agreement on one answered question does not become a high-confidence recommendation; hard incompatibility excludes regardless of Q. Questions and all weights sum only within the published version.

## 5. Matching architecture and ranking

Pipeline: authenticated request → hard eligibility → merge candidate sources → deduplicate → directional features → reciprocal scoring → diversity/exposure rerank → eligibility recheck → stable page → impression events.

Keep the PHP/MySQL runtime. Introduce proposed `php/matching/eligibility.php`, `questionnaire.php`, `candidate_repository.php`, `feature_builder.php`, `ranker.php`, and `explanations.php`, with dependencies defined once. These are proposed new paths, not existing files. An optional offline Python trainer may emit versioned numeric artifacts later; PHP requests must not depend on a live external LLM or training service.

For 5,000 users, start with indexed filtered retrieval and batched feature loads. An exact per-request vector scan of the eligible pool can be adequate after benchmarking. Approximately 12.5 million unordered pairs exist for 5,000 people: avoid recomputing all pairs on each interaction. No vector database, GPU or graph database is required initially.

Candidate union budget: up to 300 IDs per request (initial tuning target), including questionnaire leaders, behavioural neighbours, graph/community candidates, nearby profiles and eligible underexposed/new profiles. Deduplicate with source flags. Refill within the budget when filters remove candidates. Test recall against exhaustive ranking on synthetic 5,000-user fixtures; bounded retrieval should not discard strong candidates silently.

### Launch ranker without trained probabilities

All available features scaled [0,1]: Qc questionnaire, B behaviour affinity, E vector similarity, G graph/community affinity, L coarse proximity, A opt-in broad activity freshness. Proposed base weights: Qc 0.55, B 0.15, E 0.10, G 0.05, L 0.10, A 0.05. These are hypotheses to tune, not platform-derived values.

Compute confidence r_k for optional features. A missing feature has r_k=0; combine numerator sum(w_k*r_k*f_k) / denominator sum(w_k*r_k). Qc remains available with r_Q=1 even when Q is null. With no other signals, ranking falls back to questionnaire confidence and stable tie-breaking. Explain this fallback rather than fabricating learned confidence.

Add bounded exposure balancing and repeat penalties in a separately versioned reranker. Never rank by raw total likes, photo attractiveness, inferred wealth or sensitive traits. Declared dealbreakers and intentional likes take precedence over inferred affinity.

### Learned directional / outcome ranker

Learn p_like(u→v) and p_like(v→u) separately. A starting mutual-interest proxy is their product, or a direct calibrated joint model when data permits. Product independence is an approximation; check it rather than presenting the proxy as a measured joint probability.

Outcome utility can combine p_mutual, p_approved_given_mutual, p_reciprocal_conversation_given_approved and bounded explicit satisfaction. Example chain: expected conversation = p_mutual * p_approval_given_mutual * p_conversation_given_approval. Train each on its corresponding eligible opportunity population; zero future events are not negative labels before the attribution window closes.

Weights and exposure penalties must be versioned, nonnegative where appropriate and tuned in shadow evaluation. Questionnaire retains a configurable contribution; learned predictions do not erase onboarding preferences. Features from private conversations are limited to consented event counts/timing, not message content.

## 6. Behavioural learning and training contracts

Log a served candidate separately from a visible impression. Initial visible rule: >=50% card visibility for >=1 second while tab is active. Mark unverified client telemetry as such. Server issues impression tokens bound to viewer/candidate/request/position; validate token, CSRF, session and expiry. Server derives authenticated actor and records accepted likes/matches/approvals/replies after successful writes. Event IDs are unique and retried events are idempotent.

| Event | Meaning / use |
|---|---|
| Visible impression | Opportunity denominator and rank position; not attraction |
| Profile opened or bounded dwell | Weak interest feature; cap repeated counts and background time |
| Like / pass | Explicit positive / negative for the viewed pair; a pass does not classify a whole demographic |
| Mutual match | Reciprocal outcome; one canonical pair, no duplicate notifications |
| DM approved / declined / revoked | Consent outcome, with requester/recipient roles and timestamps |
| Reciprocal reply | Metadata from an approved conversation, not permission inferred from a match |
| Satisfaction feedback | Optional direct quality outcome; nonresponse is missing |
| Block / report | Block exclusion immediately; reports handled through moderation, not unquestioned attractiveness penalties |

Proposed recency feature decay: exp(-ln(2)*age_days/14). Separate long-term and recent aggregates; tune the 14-day half-life. Daily feature/training refresh initially, immediate preference/block/deletion cache invalidation. Do not retrain on every swipe. Repeated actions are capped; distinguish automated/burst activity and legitimate active users.

CF uses sparse directional interaction data, exposure-aware negatives and shrinkage for low-support similarities. Do not treat every unshown pair as a rejection. Start with weighted neighbour similarity; embeddings can follow from fixed answer vectors or implicit factors. Fixed-vector cosine similarities are not calibrated like probabilities. Do not run photo/body/face embeddings or infer sensitive traits.

Graph features use consented public memberships or explicit connections, with bounded common-neighbour/Jaccard/Adamic-Adar features. Exclude blocks and prohibited edges before computing features. No graph evidence means neutral fallback; graph membership is not required. Mutual private likes/messages cannot appear as a “people you know” explanation. Shared clubs count only if membership visibility permits it.

Training data must capture model/scoring version, feature timestamp, source mix, position and the sampling probability of stochastic exploration. Do not call deterministic top-ranked inclusion probability an exploration propensity. If propensity is unavailable, state that unbiased off-policy evaluation is unsupported. No exposure means unknown. Suppress private inbound-like leakage before reciprocal disclosure.

## 7. Geographic location

Add separate opt-ins for nearby processing and broad location display. Browser location requires explicit action; denial leaves manual campus-zone or ordinary matching available. Never infer precise location from IP. Ask for a manual area rather than automatically deriving it from an address.

Prefer approved campus-zone IDs or a coarse cell. If coordinates are obtained, quantize immediately and do not retain raw coordinates or movement history. Store coarse centre/cell, consent version, precision tier, source and refreshed_at. Proposed expiry: 7 days; exact-nearby features disabled after expiry. Do not repeatedly prompt for permission.

Calculate approximate Haversine distance using radius 6,371 km and radians; clamp intermediate values for numerical stability. For strict two-sided radius preferences, require sufficient accuracy or use conservative bounds: if estimated distance plus combined cell uncertainty exceeds the smaller radius, do not assert inside-radius eligibility. Campus-zone adjacency is a coarse alternative, not exact kilometres.

Default UI buckets: same broad area, nearby campus area, farther away; suppress buckets in sparse cells (proposed minimum 10 discoverable opted-in users). Never return coordinates, live GPS, dormitory, exact metres, map pins or exact last-seen time. Rate-limit repeated location/radius requests and suppress outputs that enable triangulation. Distance display can stay disabled while coarse proximity is used internally by consent.

Revocation deletes location rows and invalidates geographic features/caches immediately. Do not use location or protected attributes to infer relationship intent or orientation. A fresh explicit preference always overrides historic geography.

## 8. Exploration, fairness and pagination

Initial exploration allocation: one slot in each 10-result slate (10%), configurable between 0 and 15%. Draw from eligible candidates with low exposure or uncertain relevance; record distribution and realized sampling probability. Cold starts use questionnaire/profile vectors plus this allocation. No exploration candidate bypasses eligibility or an existing pass.

Balance per-candidate exposure normalized by the number of mutually eligible viewers, not global quotas that force incompatible matches. Monitor eligible new-user coverage, repeated exposure, top-decile exposure concentration and recipient request load. Limit excessive incoming recommendations through a bounded reranking penalty and explicit pause controls. Do not reduce someone’s eligibility merely because they are popular.

Provide stable pagination through an authenticated recommendation snapshot (proposed TTL 10 minutes) and opaque cursor. Bind snapshot to viewer, ranker version and preference/consent versions. Materialize ordered IDs or use a stable server snapshot; avoid random ordering on each page. Cached candidates must be rechecked for blocks/status. A stale cursor returns refresh-required; do not disclose the hidden exclusion reason of another user.

### Small, varied daily shortlist

Initial product default: 10 eligible introductions per student per local day (`Africa/Harare`), configurable after real feedback. The shortlist is a ranked recommendation set, not ten guaranteed mutual matches. Build it on first access using the current shared service; retain its ordered eligible IDs for the day. Daily refresh considers updated outcomes/features and does not repeatedly show dismissed profiles. Distinct days do not change the one-swipe/pass exclusion policy.

Separate this daily slate from the 10-minute pagination snapshot: the daily slate defines the day's recommendation set; snapshots are short-lived delivery views. Regenerating a snapshot must not create extra introductions or reset exposure counts. Count exposure only on validated visibility, not background prefetch. Preference/consent changes can legitimately rebuild the slate; blocks/deletion/suspension immediately remove candidates and allow safe replacement. Deduplicate across refreshes and record one distinct viewer-candidate-day opportunity plus raw idempotent impressions.

Select the slate jointly: prefer compatible and broadly active candidates, penalize excessive eligible-normalized exposure, encourage variety in selected interests/activities and permitted campus communities, and allocate the exploration slot. Variety is a soft reranking objective and must not introduce incompatible candidates. Honour intentional user preferences even if the resulting slate is less varied. Never reveal exact activity or punish students who do not stay continuously online.

Tests must show that all-eligible fixture users do not each receive the same top ten solely because of total like counts; underexposed suitable candidates receive opportunities; explicit passes and hard constraints survive daily refresh; and one or two available candidates yield an honest short slate rather than repeats or fabricated profiles.

## 9. Data model, services and migration requirements

Proposed entities; final SQL must follow the audited canonical user-ID types and foreign keys:

| Entity | Required fields / constraints |
|---|---|
| questionnaire_versions/questions/options | Version, key, position, type, option IDs, active flag, matrices/weights; <=20 active questions |
| questionnaire_responses | User, catalog version, question key, selected options, optional accepted-partner options, supported fit_mode, importance, updated_at; unique user/version/question |
| matching_preferences | Reciprocal hard preferences, optional dealbreakers/radius, discoverability, preference_version |
| matching_events | Unique event ID, actor/target, type, occurred_at/received_at, request/impression token, model/source/position, optional propensity; minimal metadata |
| matching_feature_snapshots | User/pair aggregates, feature version and data watermark, no unnecessary private content |
| user_locations | User primary key, coarse cell/zone, accuracy tier, consent/refreshed/expiry metadata |
| permitted_memberships | User/group, visibility, consent provenance; optional graph source |
| model_versions | Artifact checksum, feature/catalog versions, training cutoff, metrics, status and rollback predecessor |
| daily_recommendation_slates/items | Viewer, local date/timezone, ordered unique IDs, invalidation versions, replacement history; unique viewer/day and no repeated introductions within a slate |
| recommendation_snapshots/items | Opaque request ID, viewer, ordered candidates, expiry, preference/model versions, reason codes |
| experiment_assignments | Stable user assignment and experiment version; holdouts explicitly represented |

Reuse current swipes, matches, message_requests, notification preferences, consent records and answers where appropriate. Add constraints/indexes for directional pair lookups, canonical min/max match pairs, candidate status, event actor/time, target/time, unique event IDs and snapshot positions. Prevent duplicates from multiple message requests. Pending migrations cannot quietly mutate schemas on ordinary page requests.

Legacy schema conversion must report incompatible rows and a dry-run plan; never overwrite users or silently lose answers. Keep `ensure_operational_schema` compatibility temporarily, but move new DDL into a migration CLI. Back up before applying migrations; test both clean install and upgrade fixtures. Never reimport the DROP TABLE bootstrap to upgrade.

Proposed API responsibilities:
- `GET api/questionnaire.php`: catalog/version, allowed IDs and own saved progress.
- `POST api/questionnaire.php`: atomic validated answer update; authenticated, CSRF protected; 422 validation errors, 409 stale catalog. Legacy `api/find_match.php` POST can delegate during transition.
- `GET api/find_match.php`: ranked discovery using shared service, with limit 1–50 and snapshot cursor; keep a documented offset compatibility transition.
- `GET api/discover.php`: same service/contract for `js/discover.js`; never a separate ranking algorithm.
- `POST api/matching_events.php`: idempotent validated visibility/open/dwell telemetry; trust server domain events over client reports.
- `POST api/location.php`: explicit location consent/update/revoke, bounded precision and rate limiting.
- `api/swipe.php`: independently recheck eligibility, preserve canonical mutual-like match creation and DM-consent state machine.

Candidate response: user ID, permitted profile fields, questionnaire_fit (nullable), coverage, public reason codes, request/impression token, match/action state and next cursor. Do not return internal predicted probabilities, another user’s answers, coordinates or private graph edges. Return true public explanations such as shared selected interests; do not invent explanations from absent data.

## 10. Evaluation, reliability and privacy

Synthetic data is for correctness/load tests, not proof of predictive improvement. Start with at least 5,000 varied fixture users, sparse answers/interactions, conflicting preferences, empty pools, closed accounts, concurrent likes and denied/stale geography.

Test these invariants before launch:
1. Same inputs/catalog/model version give the same score and seeded slate; eligibility is bidirectional.
2. Known questionnaire matrices, unequal importance, missing sets, no shared answers and confidence adjustment produce expected values.
3. Self, minors/unknown age, unverified, inactive/deleted/suspended, paused, blocked and hard-incompatible pairs never appear, including cached pages and concurrent writes.
4. Existing matches and passes stay out of discovery; no duplicate candidates from joins, mixed retrieval or pagination.
5. Simultaneous mutual likes create exactly one canonical match; retries return consistent outcomes and notifications are deduplicated.
6. Every query prepares and executes with emulation disabled on real MySQL; metadata checks use INFORMATION_SCHEMA SELECTs where parameterization is needed.
7. Client-supplied actor/score/weight and forged telemetry tokens are rejected; answer writes are transactional and version validated.
8. Location denied/stale/revoked, graph absent, trainer unavailable and artifact corruption all fall back without widening hard filters.
9. Similar/complementary/flexible fit modes honour user choices; optional lifestyle data is not inferred; student numbers never enter public API payloads, telemetry or model exports. Daily shortlist refresh preserves passes and does not reset exposure.
10. Match does not grant messaging; pending/declined/revoked requests cannot deliver DMs.
11. Deletion removes responses, vectors, interaction associations, graph and location features plus recommendation caches. Job rebuilds exclude deleted IDs. Trainer exports/backups follow an explicit deletion policy.

Offline learned-model evaluation uses time-based splits, historical feature snapshots, closed outcome windows and pair-aware leakage checks. Report Recall@K/NDCG@K for observed outcomes with exposure bias caveats, calibration/Brier scores for declared probabilities, coverage, concentration and block/report rates. Separate cold-start and low-activity cohorts; never advertise arbitrary synthetic accuracy.

Initial attribution windows: like within 7 days of exposure; approval within 7 days of request; reciprocal conversation within 14 days of approval. Proposed conversation label: at least one reply from each participant after approval; measure longer conversations separately. These operational definitions need product validation and do not establish relationship success.

Primary online metric: approved reciprocal conversations per 100 unique eligible visible introductions, with complete attribution windows. Also track mutual-like rate, consent approval, optional satisfaction, recipient overload, blocks/reports and underexposed-user coverage. Do not count messages sent without approval. Stable randomized pilot cohorts; matching has cross-user interference, so document contamination and consider cluster/market experiments before claiming causal uplift. Shadow learned rankers first; rollout 5% → 25% → 100% only after measured checks, with immediate revert on any eligibility/consent leak.

Initial performance targets, to validate on stated hardware: p95 ranking request <=500 ms with warm features and 50 concurrent requests; bounded query count (target <=12), no candidate-by-candidate query loop; daily feature job <=15 minutes for 5,000 users. Record hardware, row counts, query plans and actual timings. Training is outside the request path; last known good model or questionnaire fallback handles failures.

Retention proposals for new matching data: raw behavioural events 90 days, aggregates 180 days, coarse location <=7 days, daily slates <=24 hours after their day ends, snapshots <=10 minutes. Shorten on withdrawal/deletion where applicable. Document anonymization limits and remove associated training rows/artifacts or rebuild models according to an explicit schedule. Extend `docs/data_retention_policy.md`; its existing legal assertions are not independently validated by this specification. Do not label this design legally compliant without separate review.

## 11. Prompt-engineering workflow

Run sequentially against a fresh checkout. Each prompt produces a focused patch with evidence; finish prerequisite gates before dependent work. User authorization covers implementation within the requested scope; ask only about unresolved product decisions that cannot be inferred. Default choices in this spec are usable starting hypotheses.

### Shared instruction for every implementation prompt

```text
Work in the existing Bushisa PHP/MySQL repository. Read AGENTS.md if present and relevant entry points before editing. Record HEAD and current schema. Preserve authentication, CSRF, verification, adult eligibility, blocks, account status and explicit messaging consent. Keep native PDO prepares disabled for emulation: assign unique placeholders and bind every value. Do not fabricate platform algorithms, test results, model accuracy or code paths. Use additive migrations; never reimport the destructive bootstrap. Keep new matching logic centralized and do not expose private answers, location or inbound likes. Implement the task, run available meaningful checks, and report changed files, actual results, limitations and rollback. If tooling is unavailable, give exact local checks and say they were not run. Do not publish/deploy merely because this prompt creates code.
```

### Prompt 1 — Audit and repair prerequisites

```text
Audit api/find_match.php, api/swipe.php, discover.php, matches.php, js/discover.js, php/operational.php, php/block_handler.php, php/anonymiser.php, config/db.php and both schema files. Trace ranking, answer persistence, account eligibility, consent and frontend endpoints. Fix native-PDO parameter reuse and incompatible SHOW placeholders using parameterized INFORMATION_SCHEMA SELECTs. Resolve missing api/discover.php/client selector integration without duplicating ranking. Identify request-state join duplication and deleted-account eligibility gaps. Produce an additive migration strategy for schema drift. Run real native-PDO query smoke tests, or document that MySQL was unavailable. Do not claim questionnaire scoring already works.
```

Gate: findings have file/line evidence, endpoint contract is coherent and affected queries execute natively.

### Prompt 2 — Catalog, response storage and questionnaire UX

```text
Implement the <=20-question catalog from section 4, stable option IDs, question types, compatibility matrices, versioned weights, importance, optional partner acceptance and supported similar/complementary/flexible fit modes. Cover intent, values/boundaries, lifestyle including optional smoking, interests and practical meeting compatibility without exceeding 20 questions. Student numbers stay private. Migrate the five existing answer keys through a reviewed mapping and flag unmappable values. Implement atomic validated save/resume/edit endpoints and an accessible responsive questionnaire in matches.php or a dedicated route linked from it. Reject unknown IDs, invalid sets and stale versions; no arbitrary free-form score values. Reuse existing CSRF/session helpers. Test full/partial completion, retries, edits and legacy migration.
```

Gate: two independent students can save valid versioned answers; 21 active questions cannot be published.

### Prompt 3 — Eligibility and deterministic pair scoring

```text
Implement one bidirectional eligibility service and the section 4 D/Q/C/Qc formulas as pure testable functions. Enforce hard gender/age/dealbreakers, verified/adult/active discoverability, both-direction blocks, existing matches and current pass policy. Use null and neutral confidence fallbacks exactly; add versioned component explanations. Test known matrices, asymmetric acceptance, chosen complementary fit, flexible directional denominators, sparse coverage, zero common answers and every exclusion. Include the 0.9/0.2 versus 0.7/0.7 reciprocal example. Distinguish hard relationship intent from ordinary soft faculty preference. Keep identity/preference enum expansion separate from the matching patch.
```

Gate: questionnaire changes alter scores predictably; high score cannot defeat eligibility.

### Prompt 4 — Shared ranking and stable pagination

```text
Connect api/find_match.php, proposed api/discover.php and discover.php to the same matching service. Replace random ranking with questionnaire-first ranking and stable ties. Batch-load features and prevent duplicate candidate rows from message-request joins. Implement the 10-person local-day shortlist with joint variety/activity/exposure reranking, authenticated snapshot cursors over that slate, stale-preference invalidation and eligibility rechecks. Page refresh must not increase the daily shortlist or reset exposure. Keep established matches separate and preserve action states. Align js/discover.js request fields, selectors, tokens and response handling. Test paging, empty pools, mixed sources and cache invalidation; benchmark versus exhaustive eligibility/scoring on 5,000 fixtures.
```

Gate: discovery and Find Match share ordering semantics; pages do not repeat profiles.

### Prompt 5 — Location and nearby retrieval

```text
Implement section 7 with separate processing/display consent, manual zone fallback, coarse cell storage, expiration and revocation. Compute approximate Haversine/zone proximity without storing raw GPS histories. Honour two-sided hard radius and uncertainty. Add sparse-area suppression and anti-triangulation rate limits. Never return coordinates or exact last-seen. Test permission denial, stale cells, missing locations, radius boundaries, uncertainty and revocation cache purge.
```

Gate: nearby matching works with consent, and ordinary matching works without location.

### Prompt 6 — Event instrumentation and aggregate jobs

```text
Implement visible-impression versus served logging, bounded active-tab dwell, server-issued bound tokens and idempotent event ingestion. Record authoritative successful swipe/match/approval/reply events on the server, with reliable retry/outbox handling where needed. Include versions, timestamps, position and actual exploration propensity. Add daily incremental feature jobs with watermarks, proposed decay and bounded counts. Test duplicate delivery, spoofed actor/tokens, unshown candidates, stale windows and failed writes. Do not treat unobserved pairs or immature outcomes as negatives.
```

Gate: a reproducible exposure-to-outcome dataset exists before learned ranking is enabled.

### Prompt 7 — CF, embeddings and graph prototypes

```text
Build optional offline candidate/feature prototypes: exposure-aware sparse neighbourhood CF, fixed questionnaire embeddings, then experimental learned factors, and permitted-community graph overlap. Benchmark support thresholds, shrinkage and retrieval recall. Keep private dating/message edges and photo-based sensitive inference out. Missing models/graph yield the section 5 fallback. Package artifacts with checksum, cutoff and schema/catalog versions; PHP loads numeric artifacts without live external AI. Use synthetic tests only for correctness and disclose lack of real predictive evidence.
```

Gate: each feature is independently switchable and evaluated against questionnaire-only retrieval.

### Prompt 8 — Reciprocal multi-outcome learning and exploration

```text
Train directional like models and conditional approval/conversation outcomes only if mature exposure data permits. Document sample sizes, calibration and joint-probability approximation. Implement the configurable utility chain, small logged stochastic exploration, opportunity-normalized exposure balancing and load controls. Keep hard filtering ahead of every rerank and recheck actions. Evaluate cold starts, rare eligible groups and popularity concentration. If data is insufficient, leave training disabled and ship the heuristic ranker with truthful labels.
```

Gate: model activation requires held-out evidence; no unsupported probability claims.

### Prompt 9 — Transactions, deletion and privacy integration

```text
Harden concurrent likes with canonical pair uniqueness and transactional/idempotent match creation. Preserve separate DM approval and notification preferences. Extend account deletion/anonymisation to responses, events, features, model exports, graph, locations and caches; add scheduled expiry jobs. Document retention changes and backup/model limitations without inventing legal compliance. Test simultaneous likes, duplicate requests, block-versus-like races, consent revocation and deleted-user resurfacing.
```

Gate: one match per pair; excluded users never resurface; revoked consent blocks DMs.

### Prompt 10 — Validation and staged rollout package

```text
Run the section 10 acceptance matrix, native PDO integration suite and a documented 5,000-user load test. Produce query plans, actual timings, privacy/invariant results, temporal-model metrics and a baseline comparison with limitations. Add flags for questionnaire ranker, learning, graph, embeddings and geography; implement last-good-artifact fallback. Provide a shadow/pilot rollout and rollback procedure, plus user-facing truthful fit explanations. Update README with migrations, required PHP extensions, jobs and test commands. No automatic production deployment.
```

Gate: a reviewer can reproduce results, enable one stage and roll it back without data destruction.

## 12. Completion checklist and decisions

- [ ] At most 20 versioned MCQs cover intentions, values/boundaries, lifestyle, interests, practical compatibility and personal preferences; paired scoring and labels are truthful.
- [ ] User-selected similarity/difference/acceptance modes work without assumed personality sameness.
- [ ] A small varied daily shortlist balances opportunity, activity and freshness, preserves exclusions and counts exposure correctly.
- [ ] Student numbers remain private throughout recommendation and learning pipelines.
- [ ] One ranking/eligibility service powers discovery; stable pagination and frontend contracts work.
- [ ] Both users’ hard constraints and live safety/consent state are enforced.
- [ ] Native PDO failures and schema drift have tested migration paths.
- [ ] Geography is opt-in, approximate and revocable; no-location fallback works.
- [ ] Exposure-aware data and feature jobs exist before CF/learned-model activation.
- [ ] Behaviour, CF, embeddings, graph, recent context, exploration and multi-outcome ranking each have an explicit implementation stage and fallback.
- [ ] Reciprocal matching, concurrency, fairness/exposure and deletion have acceptance evidence.
- [ ] Performance and quality claims use recorded measurements; flags and rollback are documented.

Product decisions to finalize during implementation: catalog wording/matrices, hard versus soft intent/faculty/distance, whether passes can ever be reset, permissible public graph memberships, and learned-stage activation criteria after observing real data. All proposed weights, thresholds, retention periods and latency targets remain versioned adjustable defaults. Do not invent unavailable interaction data to bypass these decisions.

## 13. Sources and attribution

[S1] TikTok, “How TikTok recommends videos #ForYou,” 18 June 2020. https://newsroom.tiktok.com/en-us/how-tiktok-recommends-videos-for-you — public explanation of signals, personalization and exploration; not a current source-code specification.

[S2] Engineering at Meta, “Journey to 1000 models: Scaling Instagram’s recommendation system,” 21 May 2025. https://engineering.fb.com/2025/05/21/production-engineering/journey-to-1000-models-scaling-instagrams-recommendation-system/ — retrieval/early/late ranking and model monitoring concepts.

[S3] Meta, “How AI Influences What You See on Facebook and Instagram,” 29 June 2023. https://about.fb.com/news/2023/06/how-ai-ranks-content-on-facebook-and-instagram/ — combining predictions and user feedback.

[S4] Yoji Tomita and Tomohiki Yokoyama, “Fair Reciprocal Recommendation in Matching Markets,” RecSys 2024. https://arxiv.org/abs/2409.00720 — reciprocal interest and opportunity fairness trade-offs; experimental results do not establish performance for Bushisa.

Repository evidence is identified in section 1 and grounded at the reviewed commit. All Bushisa-specific architecture, formulas, catalog, weights, defaults and prompts are proposed design choices derived from this review, not quotations or proprietary platform implementations. Instagram-style social suggestions and nearby retrieval are architectural adaptations; no exact Instagram people-suggestion or inDrive dispatch algorithm is asserted.
