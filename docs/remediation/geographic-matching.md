# Geographic Matching Status

Updated: 2026-10-15, Africa/Harare.

Status: **partial product selections; D12 pending overall**.

Reviewer: Takudzwa Hondova. Exact actual acceptance UTC/Harare date/time:
CONFIRM. Current authority is [decisions.md](decisions.md) and its
[Q28-Q34 acceptance supplement](owner-policy-reconciliation-2026-10-15.md#d12-geographic-matching).
These choices are not implemented, tested, deployed or accepted under H13 by
this documentation checkpoint. Real catalogue names/points are not supplied.

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

## Current owner selections and proposals

| Topic | Owner-approved portion | Proposed / evidence or review still needed |
|---|---|---|
| Q28 source | No backend/mail/log/telemetry raw device coordinates or continuous tracking; only confirmed coarse IDs/version/time/consent in any selected flow | Manual selection plus optional offline/local one-time device conversion is proposed. Review conversion before activation; use reviewed manual geography first if precise third-party exposure cannot be avoided. |
| Q29 catalogue | Versioned reviewed NUST/Bulawayo structure, stable IDs/labels/bounds/public representative centroid/provenance/history; broad elsewhere/outside/unknown fallback; no rooms/houses/small blocks; no paid provider initially | Urban >=approximately 5km and sparse >=10km widths are proposed. Actual local names/bounds/points CONFIRM/VERIFY with Takudzwa or accepted local reviewer. No coordinates invented. |
| Q30 consent | Optional separate opt-in; ordinary matching/requests/chat work on refusal; withdrawal immediately stops ranking/disclosure, invalidates cursors/caches and purges live state <=24h; matches/chats continue | Exact wording, mechanism, privacy review and real device grant/refuse/withdraw checks remain open. Browser permission alone is insufficient consent proof. |
| Q31 precision | Current area ID/version/confirmation/expiry/consent only; no user coordinates/exact distance/pins/residential area/trail. Safe broad label requires >=10 eligible opted-in users; merge sparse area or suppress both label and bonus. Two successful changes/rolling 24h; withdrawal never capped | Cohort implementation, fair disclosure grouping, search/probing controls and inference tests/H13 review missing. A count of 10 is not proof of anonymity. |
| Q32 lifetime | Explicit confirmation +7d freshness, immediate exclusion at expiry and live area/cache erasure <=24h; no movement history or passive renewal; Q21 minimal proof contains no area/coordinates | Immutable deadlines, replacement/expiry/withdrawal workers and independent replay need evidence. D05 copy limits remain proposed. |
| Q33 ranking | Soft bonus <=5 points on normalized 0-100 comparison after eligibility/safety; no mandatory radius; stronger distant candidates can outrank nearby. Unknown/stale/withdrawn/sparse-suppressed/unusable broad areas get zero without exclusion. Keep compatibility distinct | Candidate centroid bands 0-5km +5, >5-15 +3, >15-30 +1, >30 +0 remain proposed. Verify normalization, reciprocal filters, examples/fairness/probing without redesigning unrelated strategies. |
| Q34 pagination | Location change refreshes the next response and invalidates old snapshot/cursor with results-updated feedback; stable ordering inside a snapshot; live safety/consent rechecks before response/action | Tests for two tabs, changes/expiry/withdrawal during paging, duplicates/missing cards and cache invalidation remain open. |

The older approximately 1km cells, 24h device freshness and 0.10/10km soft
formula in the playbook were development proposals. They are not authority over
the selections above; neither are they proof of an approved catalogue.

## Data, implementation and release gates

Use [data-inventory-retention-matrix.md](data-inventory-retention-matrix.md) for
category-specific cutoff/purge/proof/recovery. Location withdrawal never changes
approved chat, matching eligibility from independent rules or personal blocks.
HMAC consent references remain potentially personal data; do not call them anonymous.

B04 in the [bounded backlog](backlog.md#q01-q48-implementation-backlog) covers
catalogue/consent/current-state/ranking/cursor work after unresolved source and
candidate choices are adopted. Product reviewer is Takudzwa; actual privacy,
engineering/local-area reviewers and Android/iOS witnesses are UNASSIGNED.
H03/H13, query/load/mobile/probing evidence, final notices and actual named
acceptances remain required. Documentation authorizes no location collection,
paid map SDK, implementation, migration or release and removes no D03 feature.
