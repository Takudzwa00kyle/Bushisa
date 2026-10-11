# D04/D05 Policy Proposals

Updated: 2026-10-10, Africa/Harare.

Status: **pending proposal**. This file records proposed D04/D05 rules and the current repository mapping. It does not approve production policy, P0 clearance, live deployment, user-facing wording, or any retention number. Accepted values must be recorded in `docs/remediation/decisions.md` by the actual owner/reviewer before production use.

## 1. R01-R12 Implementation Map

| Rule | Current mapping | Working behavior | Reproduced gap or pending evidence |
|---|---|---|---|
| R01 eligible reciprocal likes and explicit chat approval | `api/swipe.php`, `php/matching/service.php`, `api/message_requests.php`, `api/messages.php`, `php/policy_safeguards.php`, `matches`, `message_requests` | Swipe path checks reciprocal matching inputs before creating a match. Messages now use `bushisa_authorize_match_action()` and require active match plus approved request. | True HTTP overlap for request approval still needs multi-worker evidence. |
| R02 request states and rematch/unblock | `message_requests.status`, `expires_at`, `closed_at`, `purge_due_at`; `api/message_requests.php`; `blocks`; `matches.match_status` | States include `pending`, `approved`, `declined`, `cancelled`, `expired`, `revoked`; unblock removes only the blocker row. | Cooldown and seven-day expiry are proposed development behavior only. Owner must approve budgets and repeat-contact wording. |
| R03 cascades must not remove protected evidence | Legacy FKs in `database/bushisa.sql`: `messages.match_id ON DELETE CASCADE`, `reports.reported_user_id ON DELETE CASCADE`; `php/block_handler.php`; `api/safety.php` | Block now closes match rows instead of deleting them; message rows survive. Scoped snapshots live in `moderation_evidence`, which has no FK cascade to users/messages. | Existing legacy FK remains dangerous if future code hard-deletes matches/users. Tests cover the new shared path, not every ad hoc SQL path. |
| R04 unsend evidence model | `api/messages.php`, `messages.unsent_at`, `moderation_body`, `ordinary_body_purged_at`, `moderation_evidence` | Unsend is idempotent, sender-only, and bounded to 15 minutes by server UTC time; ordinary body is purged instead of blanket shadow-retained. | Attachment projections are not implemented in this repo; no production wording approved. |
| R05 verifier and mail payload storage | `php/signup_verification.php`, `mail_outbox`, `student_email_approval_codes`, `bin/cleanup_policy.php` | Terminal code verification/expiry erases `code_hash` and code-bearing fake-mail body. Fake mail throws in `APP_ENV=production`; non-fake provider remains blocked until encrypted implementation exists. | Real Bushisa sender/inbox verification remains pending under D11. |
| R06 either-account relationship cleanup | `php/anonymiser.php`, `php/policy_safeguards.php`, `swipes`, `matches`, `message_requests`, `cleanup_outbox`, `protective_replay_events` | Account deletion closes either-account matches with `deleted_cleanup`, revokes requests, deletes ordinary swipes, and queues cleanup/replay. | Final production retention purpose/deadline remains D05 pending. |
| R07 confession deletion before author unlink | `api/confessions.php`, `confessions.deleted_at`, `purge_due_at`, `confession_references`, `php/anonymiser.php` | Account deletion now records owned confession deletion, erases ordinary public body, marks references unavailable, then existing author unlinking can occur. | Continued anonymous-publication alternative is not implemented or approved. |
| R08 personal-data inventory | `users`, `profiles`, `preferences`, `find_match_answers`, `profile_images`, `mail_outbox`, `audit_log`, `logs/`, future D12 fields | Deletion now clears credentials, DOB, preferences, questionnaire answers, profile-image metadata and owned public confession bodies through the shared deletion path. | Application logs, upload derivatives, caches, Blob versions and future location data need environment inventory before P0 clearance. |
| R09 consent/deletion proof minimization | `consent_records`, `protective_replay_events`, `cleanup_outbox` | Replay ledger is independent of ordinary relationship rows and records minimized protective events. | Consent retention period and ledger expiry are pending; no six-year or 36-month value is approved. |
| R10 UTC purge deadlines without reset | `purge_due_at`, `evidence_purge_due_at`, `closed_at`, `terminal_at`; `bin/cleanup_policy.php` | New rows record UTC terminal/closure/deadline fields and do not reset on read. Cleanup is idempotent for signup/outbox entries. | Full retention worker for messages/evidence/media is designed but not complete. |
| R11 case-scoped evidence access | `moderation_cases`, `moderation_case_assignments`, `moderation_evidence`, `evidence_access_audit`, `bushisa_case_evidence_for_viewer()` | Ordinary users and unassigned moderators cannot read evidence; assigned moderators or audited admin exceptions can. Access is audited without copying private bodies into audit rows. | No user-facing moderation UI exists yet for case assignment or evidence viewing. |
| R12 protective restore replay | `protective_replay_events`, `restore_replay_runs`, `tests/policy_safeguards_p0_flows.php` | Synthetic restore check fails closed when replay evidence is unavailable/incomplete. Ledger covers account deletion, match closure, confession deletion and case opening. | Actual Azure restore rehearsal and isolated restored DB/media replay are not run. |

## 2. Proposed D04 Rules

These rules remain proposed until D04 is accepted in the decision register.

- A like creates only a swipe. A match requires eligible reciprocal likes under the current matching service.
- A match does not authorize chat. Chat requires one current request and recipient approval.
- Message request states are `pending`, `approved`, `declined`, `cancelled`, `expired` and `revoked`.
- Proposed development timers are seven days for pending request expiry, 24 hours after cancel/expiry before retry, and 30 days after decline before a re-request. These values are not approved.
- Send, read, receipt and unsend routes must recheck current account eligibility, active match status, block state and chat approval.
- Block suppresses discovery/contact both ways, closes active matches, revokes requests and removes ordinary swipes. Unblock restores only the blocker-owned block row; it does not restore match or chat consent.
- Unmatch should use the same close/revoke path with reason `unmatched`. The user-facing unmatch route remains to be completed.
- Notifications must not include private message bodies or evidence payloads.

## 3. Proposed D05 Rules

These rules remain proposed until D05 is accepted in the decision register.

- Account deletion immediately revokes sessions by changing account/session state.
- Ordinary relationship cleanup is triggered when either account deletes, not when both accounts delete.
- Deleted-account swipes, preferences, questionnaire answers, DOB and direct identifiers are removed or overwritten in ordinary storage.
- Owned public confessions are resolved before author unlinking: ordinary public bodies are erased and references are marked unavailable. Scoped case evidence is separate.
- Expired or consumed verifier material must not retain raw or derived code-bearing secrets. Code-bearing fake mail is synthetic-development only and disabled in production.
- `purge_due_at` and related UTC deadlines are derived from the original terminal event and must not reset on read, retry or edit.
- Evidence is case-scoped and accessed only through assigned/audited paths. Audit records must not contain message bodies, passwords, raw DOB, approval codes or location payloads.
- Restore must replay an independent protective ledger for deleted accounts/content, unsend, blocks, revoked consent and expired data before any traffic or worker resumes. Missing or incomplete replay evidence blocks reopening.

## 4. Current Technical Safeguards

- `php/policy_safeguards.php` centralizes active chat authorization, match closure, moderation evidence snapshots, account-deletion cleanup/replay and terminal signup material erasure.
- `php/block_handler.php` and `api/safety.php` close matches instead of deleting them, preventing the legacy `messages.match_id ON DELETE CASCADE` from firing in the normal block path.
- `api/messages.php` uses the shared authorization guard and implements sender-only 15-minute UTC unsend without blanket original-body retention.
- `php/anonymiser.php` calls the shared deletion-preparation path before soft-deleting account fields.
- `bin/cleanup_policy.php` performs idempotent synthetic signup/outbox cleanup.
- `database/migrations/003_restore_replay_safeguards.sql` records restore replay rehearsal decisions separately from restored application data.

## 5. Current Limitations

- D04/D05 are still pending human decisions. Proposed timers and ceilings are not production policy.
- Real encrypted production mail, ACS sender verification and real inbox delivery are not implemented.
- Full media/blob/cache/log cleanup is inventoried but not complete.
- HTTP/browser tests exercise routes, but genuine multi-worker HTTP overlap is not yet proven. Windows single-worker PHP server output must not be treated as HTTP concurrency evidence.
- D12 production location use remains disabled pending approval; synthetic D12 fixtures remain labelled provisional.

## 6. Pending Owner Choices

### 6.1 Required Section 6.1 Choices

| Choice | Pending decision |
|---|---|
| Chat transitions | Approve request expiry, cooldowns, request budgets, revoke/rematch behavior, unblock behavior and account-pause behavior. |
| Unsent originals | Choose scoped-case-only retention or explicitly approve a blanket original-retention alternative with notice, encryption, access and purge capability. |
| Closed chat/report evidence | Approve purpose, access model, final-resolution definition, deadlines and deletion capability. |
| Deleted confessions | Approve deletion-on-account-removal default or separately approve continued anonymous-publication model. |
| Abandoned/underage signup | Approve exact expiry and minimal safety proof. |
| Consent proof | Specify fields, purpose and post-deletion period. No automatic six-year rule is approved. |
| Restore protections | Approve Azure/Blob/copy horizons, safety margin, owner and ledger continuity mechanism. |
| Operations and rights | Name case, cleanup, support, privacy and escalation owners. |

## 7. Verification Targets

Focused regressions now cover:

- case-scoped evidence access and audit;
- block close/revoke without message cascade;
- either-account deletion cleanup;
- deleted-confession erasure before author unlink;
- terminal verifier and fake-mail payload erasure;
- synthetic restore fail-closed behavior when replay evidence is missing.

Required but not yet complete:

- real multi-worker HTTP overlap with independent sessions/connections and application-side overlap evidence;
- Azure MySQL/version compatibility;
- real sender/inbox verification;
- complete media/blob/cache/log purge proof;
- isolated Azure restore rehearsal.

