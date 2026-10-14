# Geographic Matching Status

Updated: 2026-10-14, Africa/Harare.

Status: pending D12.

D03 now includes geographic matching in the full feature scope for Bushisa's next
production-ready release. This document still does not approve D12 or authorize
production location collection/use.

Current implementation:

- `php/matching/service.php` is the authoritative discovery matcher.
- Candidate retrieval excludes the viewer, already swiped users, existing
  matches and either-way blocks. It requires verified/active adult profiles.
- Pair eligibility applies reciprocal gender/age preferences and hard
  questionnaire incompatibility checks.
- Ranking uses questionnaire compatibility from `php/matching/scoring.php`,
  then adds a small faculty/year tie breaker before sorting by score and
  `user_id`.
- The matcher does not join a location table, calculate distance, read browser
  geolocation, use `profiles.campus_zone`, or apply a proximity weight.
- The base schema has a legacy `profiles.campus_zone` text column, but there is
  no approved campus/area catalogue, consent flow, retention policy, freshness
  model or public disclosure contract attached to it.

P1-01G remains a blocker for the next production-ready release until D12 is
approved and implemented:

- Approved campus/area catalogue and representative points.
- Manual-area and optional device-location consent wording.
- Location freshness/expiry/deletion policy.
- Proximity weight and distance scale.
- Privacy-safe public response and telemetry rules.
- Stable pagination behavior with location-aware ordering.

Remaining D12 choices:

- Location source: manual area, optional device geolocation converted to coarse
  location, both, or neither.
- Consent: opt-in wording, fallback for denial, withdrawal behavior and whether
  existing matches/chats are affected.
- Granularity: campus/area labels, representative points, approximate cell size
  and whether exact coordinates are ever stored.
- Retention: freshness window, expiry deadline, consent proof fields, backup
  handling and deletion/withdrawal deadlines aligned with D05.
- Weighting: soft bonus cap, distance scale, handling for unknown/stale
  locations, hard-radius limits if any and the rule for far stronger reciprocal
  matches.
