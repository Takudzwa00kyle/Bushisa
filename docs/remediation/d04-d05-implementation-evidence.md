# D04/D05 Implementation Evidence

Updated: 2026-10-10, Africa/Harare.

Status: technical implementation evidence for pending D04/D05 proposals. This is not P0 clearance and not production approval.

## Runtime Versions

- PHP CLI: `PHP 8.5.11 (cli)` from `php -v`.
- MySQL client: `MySQL Community Server 9.6.0` client from `mysql --version`.
- MySQL server: **NOT RUN/BLOCKED**; local application connection rejected `root@localhost` without password on port 3306 and no alternate test DB was reachable on port 3317.
- Node/npm: Node `v20.19.6`, npm `10.8.2`.

## Migration Ordering

1. `001_operational_contract.sql` through `php bin/migrate.php` idempotent schema path.
2. `002_policy_safeguards.sql` through the same idempotent schema path.
3. `003_restore_replay_safeguards.sql` as additive restore replay run table.

## P0 Task Evidence Matrix

| Task | Evidence path | Current result |
|---|---|---|
| R01/R02 request consent and transitions | `api/message_requests.php`, `php/policy_safeguards.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. Multi-worker HTTP overlap still required. |
| R03 evidence-safe block/delete | `php/block_handler.php`, `api/safety.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. Legacy cascades still exist but normal close path avoids them. |
| R04 unsend | `api/messages.php` | Static PHP syntax PASS. Focused route/browser regression **NOT RUN/BLOCKED** by DB auth. |
| R05 verifier/outbox cleanup | `php/signup_verification.php`, `bin/cleanup_policy.php`, `tests/integration_p0_flows.php`, `tests/policy_safeguards_p0_flows.php` | Static PHP syntax PASS; `tests/unit_signup_verification.php` PASS. DB regressions **NOT RUN/BLOCKED**. Real sender/inbox pending. |
| R06 either-account cleanup | `php/anonymiser.php`, `php/policy_safeguards.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. |
| R07 confession erasure before unlink | `php/policy_safeguards.php`, `api/confessions.php`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED** by local DB auth. Static PHP syntax PASS. |
| R08 data inventory | `docs/remediation/policy-proposals-d04-d05.md` section 1 and 5 | Documented. Environment inventory still pending. |
| R09/R10 UTC deadlines and cleanup | `cleanup_outbox`, `purge_due_at` fields, `bin/cleanup_policy.php` | DB regression **NOT RUN/BLOCKED**. Full retention worker incomplete. |
| R11 case-scoped evidence access | `moderation_cases`, `moderation_case_assignments`, `moderation_evidence`, `evidence_access_audit`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED**. UI pending. |
| R12 protective restore replay | `protective_replay_events`, `restore_replay_runs`, `tests/policy_safeguards_p0_flows.php` | DB regression **NOT RUN/BLOCKED**. Azure isolated restore not run. |
| D12 production location disabled | `docs/remediation/geographic-matching.md`, `docs/remediation/decisions.md` | Pending D12 approval; production use disabled. |

## Commands Run This Turn

| Command | Result |
|---|---|
| `php -l` on all PHP files | PASS |
| `php tests/unit_signup_verification.php` | PASS |
| `php bin/migrate.php` | NOT RUN/BLOCKED: database connection rejected for default local root credentials |
| `php tests/integration_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `php tests/integration_core_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `php tests/policy_safeguards_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `php tests/overlap_db_p0_flows.php` | NOT RUN/BLOCKED: database connection rejected |
| `mysql --protocol=TCP --host=127.0.0.1 --port=3317 --user=root --execute="SELECT VERSION();"` | NOT RUN/BLOCKED: no MySQL listener on port 3317 |

## Explicit Non-Claims

- No P0 clearance.
- No live deployment.
- No approved D04/D05 production policy.
- No verified real sender/inbox delivery.
- No Azure restore rehearsal.
- No claim that a Windows single-worker development server proves HTTP concurrency.
