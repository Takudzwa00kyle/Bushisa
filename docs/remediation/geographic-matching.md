# Geographic Matching Status

Updated: 2026-10-09, Africa/Harare.

Status: pending D12.

The current audited code has no verified active geographic ranking signal in the authoritative matching service. `php/matching/service.php` ranks by questionnaire score plus faculty/year tie breakers and applies reciprocal eligibility, swipes, matches and blocks. No approved coarse-area catalogue, location consent flow, retention policy or proximity weight has been provided yet.

P1-01G remains a launch blocker until D12 is approved and implemented:

- Approved campus/area catalogue and representative points.
- Manual-area and optional device-location consent wording.
- Location freshness/expiry/deletion policy.
- Proximity weight and distance scale.
- Privacy-safe public response and telemetry rules.
- Stable pagination behavior with location-aware ordering.
