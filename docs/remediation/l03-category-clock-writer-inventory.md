# L03: repository category, clock and writer inventory

Current consumer continuation: [ordinary attempt erasure](q11-attempt-erasure-evidence-2026-10-18.md)
implements the generation-bound Q11 scope from `455f1fc`: ordinary fields and
child rows, linked mail contact copies and staged fixture objects, with durable
claims/cursors/receipts and minimal recovery intents. Users/safety/consent/pair
references are retained; unknown schema/cascades/legacy generations block work.
Separate QE01–QE28: **27 PASS / 1 PARTIAL / 0 wholly NOT RUN**; the Windows
symlink variant is unexecuted. Operational recovery/storage providers remain
unavailable/fail-closed. Next independent slice: Q22 residual detail/metadata
clocks. The underlying baseline inventory and historical A/LB/LC/QS evidence
retain their original attribution and are not relabelled.

Earlier Q11 continuation: [signup-expiry implementation/evidence](q11-signup-expiry-evidence-2026-10-18.md)
implements new pending-email/onboarding anchors and atomic challenge/mail
cancellation from `be91e06`. For baseline C01/C02/C05–C07, W01–W03 and G01/G04/G05,
original deadlines now persist, first approval is one-time, expiry rechecks the
locked account and delivery uses explicit claims/preflight/receipts. Separate
QS01–QS24: **24 PASS / 0 PARTIAL / 0 NOT RUN** for the stated local variants.
Ordinary attempt-data erasure remains a pending consumer; legacy anchors,
code-free metadata cleanup, actual scheduling and independent recovery/review
remain open. No baseline inventory or historic A/LB/LC evidence is relabelled.

Earlier continuation: [L03a implementation and case evidence](l03a-text-evidence-lifecycle-evidence-2026-10-17.md)
implements the bounded new-intake text/hold core from `b521b67`, with LC01–LC18
now **13 PASS / 5 PARTIAL / 0 wholly NOT RUN**. Its eleven additive tables,
trusted-provider fail-closed boundary, scoped holds, bounded purge and synthetic
recovery interfaces are recorded there. The inventory, initial NOT RUN plan and
documentation validation below retain their **b521b67 baseline** attribution;
they are not current implementation/test totals. A/LB evidence is unchanged.

Repository checkpoint: `631e0d82ba7f2ce63cd18f28e80820be88a4e272`.
Branch: `codex/l03-category-clock-inventory-20261017`. The starting tree/index
were clean; no applicable AGENTS.md was found in the checkout or ancestors.
This is a static engineering inventory, not an executed cleanup, deployed-schema
inspection, privacy approval or independent recovery proof. No database, user
files, log contents, provider account or mail transport was accessed.

Authority is the current [decision register](decisions.md), including Decisions
27–33, the [Q01–Q48 reconciliation](owner-policy-reconciliation-2026-10-15.md),
[retention matrix](data-inventory-retention-matrix.md) and
[retention policy](../data_retention_policy.md). Their approved/proposed distinction
controls; code defaults and older standalone proposals do not approve durations.
This document updates repository mappings, not policy or historical evidence.
Takudzwa Hondova remains the product reviewer. The exact historical acceptance
instant was not captured; this document's date/check times do not replace it.

**A** means an approved rule, still subject to its recorded qualified review and
release gates. **P** means proposed, not adopted. **G** means missing evidence or
review. **Implemented** below means code exists, not deployed or comprehensively
tested. **Planned** means no corresponding feature store/writer was identified.
All operational role names below are responsibilities, with actual people
**UNASSIGNED**. No whole D04/D05/D12 approval follows from this inventory.

## 1. Shared clock, hold and recovery rules

These rules apply to every category below; H0/R0 references avoid duplicating
them in each row.

| Reference | Required behavior / actual boundary |
|---|---|
| T0 — approved clock rule | Q25: server UTC, elapsed hours/days; one year is 365 days. Only non-identifying aggregates use 13 calendar months. Persist new expiry instants and reject operations at or after them. Reads, retries, edits, exports, migrations, restores and routine reviews never renew an old deadline. A new independent event has its own identity. Preserve existing request/unsend clocks and approved boundary semantics. |
| H0 — ordinary data | A hold protects specified evidence, not an ordinary account, chat, profile, notification or whole case indefinitely. Access denial and ordinary erasure continue; only an already-authorized, scoped evidence copy follows C20/C21. Do not capture erased originals or copy another case's evidence during cleanup. |
| H1 — evidence holds | Q20 A: assigned-reviewer urgent hold at most 24h; joint safety-lead/privacy authority for ordinary holds at most 30d per authorization, review at least every 14d. Renew only by fresh necessity/authority. Verified legal instructions are a qualified-review path, not an invented exception. Keep original ordinary expiry. Release/expiry after that deadline requires purge within 24h; otherwise preserve the original deadline. |
| R0 — all recoverable personal/protective state | Q26/Q38 A: independently preserve current deletion/block/consent and expiry instructions outside the restore set; apply them before reads or dispatch. Retain until the latest actual recoverable-copy expiry plus 7d, and longer for a still-live restriction. Unknown copies/continuity block restore/release. Access denial, live erasure and backup expiry are separate stages. |
| R1 — conditional arithmetic | Q14's 7d live-erasure allowance plus **proposed** 35d DB copies plus the approved 7d recovery margin gives a conservative **49d from deletion**, not 42d and not a universal deletion timer. Compute from verified purge/copies and active protections. Media-copy 7d and troubleshooting-export 24h settings remain P; copied/restored material gains no fresh source lifetime. |
| E0 — evidence provenance | [L01b](l01b-implementation-evidence-2026-10-16.md), [L02a](l02a-implementation-evidence-2026-10-16.md), [L02c](l02c-offline-history-planner.md), [L02d](l02d-synthetic-import-evidence-2026-10-17.md) and [L02e](l02e-mixed-races-compatibility-evidence-2026-10-17.md) remain historical, individually attributed synthetic executions. No test is rerun or relabelled here. |

One stale cross-document statement needs no new owner answer: the older matrix's
Q01 renewal label is P, but **Decision 29 expressly approves** a fresh seven-day
period for a new qualifying report after expiry. Active probation is never
extended/reset and retries create no new period. The register prevails. Similarly,
Decision 27's fresh mutual restoration and the temporary unset Reply-To approval
remain settled. None changes retention limits or appoints an inbox operator.

## 2. Schema and store coverage

The schema is assembled from [base SQL](../../database/bushisa.sql), the
[operational schema helper](../../php/operational.php), migrations
[001](../../database/migrations/001_operational_contract.sql),
[002](../../database/migrations/002_policy_safeguards.sql),
[003](../../database/migrations/003_restore_replay_safeguards.sql),
[004](../../database/migrations/004_smtp_outbox.sql),
[005](../../database/migrations/005_protective_replay_event_precision.sql),
[006](../../database/migrations/006_report_intake.sql),
[007](../../database/migrations/007_pair_history.sql),
[008](../../database/migrations/008_synthetic_legacy_import.sql),
[009](../../database/migrations/009_pair_trigger_health.sql), and the
[report](../../php/report_migration.php)/[pair](../../php/pair_migration.php)
migration helpers. `body/content`, `author_id/user_id`, `sent_at/created_at`,
match-member and swipe-target aliases must be resolved through the existing
operational helpers. Base SQL alone is not the effective schema.
The alternate [sql/schema.sql](../../sql/schema.sql) declares incompatible legacy
names plus `users.verification_token/token_expires_at` and
`profiles.photo_1_url/photo_2_url/campus_zone/updated_at`. README and the guarded
bootstrap select `database/bushisa.sql`; the alternate file is not an upgrade or
new policy authority. Whether those legacy extra fields/copies exist in a deployed
schema is UNVERIFIED. No current writer/cleanup for its token/extra photo columns
was identified; include them in later authorized schema/object capture rather
than assuming they are absent or that all alternate fields are supported.

No deployed schema or production application of these migrations was verified.
In particular, 005/007/008/009 remain UNVERIFIED. Migration 009 is a fixed
read-only trigger-health procedure, not a retention writer or legacy activation.

The two tables below are one inventory keyed by C01–C32. Writer IDs resolve in
section 3. All personal-data rows inherit T0/H0/R0 unless expressly narrowed.

### Stores, purposes and stored clock anchors

| ID / category / policy purpose | Actual repository stores and clock fields | Writers, current coverage and evidence/gap |
|---|---|---|
| C01 Signup/onboarding — D01/D02, Q11 | `users` identity/email/password, verification/status/session version and `created_at`; initial `profiles`/`preferences`. No first-email-approval, account-expiry or onboarding-expiry column identified. Challenge `consumed_at` also means supersession and is not proof of first approval. | W01/W02/W06/W15/W22. State gating exists; no account-attempt expiry worker. G01. Auth/cleanup engineering. |
| C02 Rejected underage attempt — D02/Q12 | `users.account_status=underage_blocked`; initial profile/preferences may already exist. The rejected DOB path does **not** store the submitted raw DOB. No rejection timestamp, scoped rejection record or HMAC anti-replay table identified. | W01/W15. Permanent nonactivation of the attempt is implemented; 24h cleanup/finite anti-replay design is incomplete. G01/G14. Eligibility/privacy. |
| C03 Active profiles/preferences/answers — Q13 | `profiles` display/bio/gender/DOB/faculty/year/photo/handle; `preferences` partner gender/age/faculty; `find_match_answers` catalog/key/value/JSON/importance/fit/accepted-options/review flag and `updated_at`. `profile_images` path/MIME/size/slot/created/updated metadata. | W01/W04/W05/W15. Current-value SQL replacement exists, no answer archive identified. Upload objects are not fully indexed; candidate allowlist/purpose review remains G. G02. Product/privacy/matching engineering. |
| C04 Account inactivity — Q13 | No durable intentional foreground activity, warning-sent or inactivity-expiry field identified. Session `last_activity` is a different, polling-renewed value. | W06 is not a suitable activity writer. No inactivity worker/warning scheduler. G01/G03. Support/cleanup. |
| C05 Account deletion — Q14 | `users.deleted_at`, active/verified/suspended/status/session version; anonymized identity/password/profile; residual gender/creation/status fields and relationship rows. Queue/replay fields in C28. | W15/W18. Immediate logical denial and partial SQL erasure exist; account-wide seven-day physical completion is absent. G02/G04/G12. Operations/engineering/privacy. |
| C06 Verification challenges — Q22 | `student_email_approval_codes`: account/email, keyed `code_hash`, `code_version`, `created_at`, `expires_at`, attempts, consumed/terminal/purged times. Alternate SQL also declares `users.verification_token/token_expires_at`, without a current writer/consumer. | W02/W18. Issuance/supersession/consume/exhaustion and terminal secret erasure exist. Rows/address/attempt detail are not physically purged by the job; actual legacy token copies UNVERIFIED. G05. Auth/security. |
| C07 Mail secrets and code-free delivery — Q22/Q45/Q47 | `mail_outbox`: recipient/subject, encrypted SMTP body (plaintext fake body only in guarded development), provider/status, code link, creation/expiry/sent/payload-erased/next-attempt times, attempts and metadata. No separate immutable useful-delivery deadline or generic terminal-outcome timestamp. SMTP provider copies/inbox processing are external and UNVERIFIED. | W02/W03/W18/W24. Signup-only dispatcher erases terminal body and rechecks challenge; code-free row cleanup absent. G05/G06. Mail/security/privacy. |
| C08 Password reset — Q47 | **Planned** purpose-specific token/generation/consumption store and outbox integration; no reset challenge/route/session-revocation flow identified. Existing signup code storage is not reset-token implementation. | Later L06; no reset scheduler/writer to certify. G05/G14. Auth/security. |
| C09 Credentials/sessions — Q14/Q40/Q47 | `users.password_hash`, role/status/session version; PHP session contents/cookie and handler-managed storage. No DB session store registered in `session_manager.php`. Actual save handler/path/GC, platform copies and MFA issuance are UNVERIFIED. | W01/W06/W15; W14 suspension changes only its flag. Session-version checks exist; per-account file erasure/absolute clock and intentional-activity distinction are incomplete. G03/G04. Security/operations, later L06/L07. |
| C10 Discovery/likes — Q06/Q09/Q13 | `swipes` actor/target/direction/`created_at`; `matches`; pair `unmatched_at`. No individual recommendation-impression store, discovery-pause writer or persistent recommendation-cache registry identified. Legacy `campus_zone` references are not a geographic catalogue. | W07/W15/W20. Unmatch deletes both active likes; discovery excludes recent pair unmatch. Impression/cache/pause stores are **planned** where absent. G07. Matching engineering. |
| C11 Minimal relationship/safety ledger — D04/Q23 | `pair_policy_history` original members, match/history state/genesis, version/generation, ending count/actor/time, suppression, own-unmatch/restore times and current request key; `pair_request_counts` directional count/verification/retry boundary; `pair_decline_restrictions`; `pair_ledger_epoch`, `pair_account_genesis`; `blocks`; `report_block_links`; `report_probation_periods` actor/trigger/starts/expiry. | W07/W08/W12/W13/W15/W19/W21/W22. L02 preserves three-request directional lifetime budgets, fixed probation, decline/cutoff, creator-only removal and current clearance; legacy history stays incomplete. No approved blanket post-deletion ledger erasure schedule is implemented. G08/G12. Safety/chat/privacy. |
| C12 Ordinary request rows/events/global windows — D04/Q24 | Mutable `message_requests` requested/expiry/responded/revoked/closed/purge/generation; `pair_request_events` immutable event key, members/generation/source/requested/original expiry/status/terminal/purge/provenance; `message_request_sends.created_at` independent of row reuse. | W08/W12/W19/W20/W22. Lazy expiry and terminal cleanup exist; minimal C11 survives. Unknown legacy terminal anchors are not fabricated. G08. Chat engineering. |
| C13 Pair operation receipts — L02 contract/Q23 | `pair_policy_operations` actor/key/intent hashes, match/resulting version, creation/expiry. Current implementation sets creation +30d. | W07/W08/W20. Retry identity/version checks and physical expiry exist; the numerical receipt-purpose mapping is an implementation choice requiring category review, not a new owner-approved general metadata duration. G08. Chat/privacy. |
| C14 Active chat text — Q16/D04 | `messages` match/sender/body/sent/read, report revision/segment, unsent and ordinary-body-purged fields; nullable `evidence_purge_due_at`. `moderation_body` is a legacy extra copy, not an approved unsend archive. | W09/W15/W20/W22. Text-only real readers/writers exist. No per-message 365d or human-message inactivity expiry is implemented/approved. G09. Chat/privacy. |
| C15 Explicitly closed chat text — Q17 | `pair_chat_segments` opening/grant/ending/ender/`read_until`; each message's immutable `evidence_purge_due_at` and segment key. The field name is misleading: here it selects **ordinary** closed text, not a case-evidence hold. | W08/W09/W20. L02a tests old/new text isolation and seven-day reads; full media/account cleanup remains outside that evidence. G09/G12. Chat/cleanup. |
| C16 Unsent text/projections — D04 Decisions 15–17/Q10 | Same `messages` fields, body/moderation body, sent/unsent/purged timestamps; potential attachment/preview/notification stores are separate. | W09 clears ordinary text inside accepted unsend and hides entry. No content captured merely for unsend. Attachment/preview stores are **planned**; notification/object completion unproved. G09/G10. Chat/media. |
| C17 Notifications/preferences — Q10/Q14/Q23 | `notifications` recipient/category/title/body/deep-link/dedupe/payload/created/read; `notification_preferences` enabled/category/updated. | W08/W11/W15. Dedup upsert rewrites `created_at`/read state. Relationship suppression deletes some chat-related notices, but no message-specific unsend consumer or general expiry/account purge. G03/G10. Product/cleanup. |
| C18 Confessions/references/views — Q15/Q13 | `confessions` author/body/created/flag/deleted/purge/revision; `confession_references` parent/source/created/deleted metadata; `confession_views` post/viewer hash/`viewed_at`. Hash is predictable SHA-256 of `user:<id>`, not anonymous. No reply store or confession-media store identified (**planned**). | W10/W14/W15. Account deletion blanks bodies and hides references before author detach; no physical category worker. Active-author delete route absent; admin delete is distinct. View retention/purpose mapping needs review. G02/G10. Content/privacy. |
| C19 Report intake, contexts and receipts — L01/Q18 | `report_incidents` anchor/key/receipt/case/created/expiry; `report_submissions` own explanation/reason/actor/information hash/received/expiry/purged; `report_operations` key/intent/action/version/reference/times; `report_case_links`. Encrypted reporter-bound response contexts contain selectors/version, not text; no server exposure archive. Account contexts currently get issue +30d; some active sources have no expiry. | W12/W13/W19. New-intake expiry/redaction/dedup implemented locally. Proof-purpose horizon and metadata categorization remain review gaps; no universal 30d approval. G08/G11. Safety/privacy/security. |
| C20 Evidence, decisions and bans — Q18/Decisions 32–33 | `moderation_cases` status/reason/created/final resolution/evidence due/intake version/resolution class; `moderation_evidence` source/subject/text/hash/captured/due/purged/version; `moderation_case_assignments` actor/assignment/revocation times. Legacy `reports` reason/evidence path/status/created is separate. No notice/appeal/finding ledger or adopted 7d/30d/permanent ban-enforcement record/workflow identified. | W12/W13/W14/W19/W22. New evidence captures authorized text only; legacy creation +24-month helpers still exist without an identified active intake caller. No application writer certifies final resolution; tests set it synthetically. G11/G14. Safety/privacy. |
| C21 Holds — Q20 | `report_evidence_holds`: exact case/evidence IDs, opaque authorization ref, starts/expiry/released times. No authority approval record, reviewer independence, urgent/joint type, necessity/review-due or durable release/purge obligation fields. | W19 reads it; only fixture insertions identified. **Planned** application authorization/release workflow. Current exclusion is text-item scoped, not a staffed lawful-hold system. G11. Safety lead/privacy jointly. |
| C22 Audits — Q19/Q23 | `report_action_audit` actor/case/action/outcome/occurred/expiry; legacy `evidence_access_audit` viewer/item/case/free-text reason/access time; `audit_log` account/action/raw IP/timestamp. | W06/W12/W13/W14/W15/W17/W19. New audit expiry uses event +365d; old evidence audit/ordinary audit lack purge. Q19 evidence accesses are A; broader privileged-audit durations remain P under Q23. G06/G11. Security/privacy. |
| C23 Consent proof/withdrawal — Q21/Q30 | `consent_records` account/type/granted/revoked only; no purpose notice version/method/event ID, processing-end, expiry or independent withdrawal-version record. | W16 helper exists; no application grant/revoke caller identified beyond W15 deletion revocation. Protected processing and geography enforcement are not supplied by this helper. G04/G14. Privacy/consent engineering. |
| C24 Media/files/derivatives — Q10/Q12–Q15/Q24 | `profiles.profile_photo_path`, `profile_images.path`, legacy `reports.evidence_path`, local `uploads/<user>/photo_*`; paths in `cleanup_outbox`. Alternate SQL's `photo_1_url/photo_2_url` also require capture if present. No complete private-object/thumbnail/preview/CDN index or media-authorization controller identified. Actual Azure/object/version/cache copies are UNVERIFIED. | W04/W15; no W18 `media_delete` consumer. Old replaced files may lose their only profile pointer; unlink occurs outside rollback protection. G02/G04/G10. Media/operations. |
| C25 Coarse geography — Q28–Q34 | **Planned** current coarse area ID/catalogue version/confirmation/expiry/consent; catalogue/cohort/cursor stores and local conversion. `campus_zone` is declared in alternate SQL, without an identified active location writer; this is not proof of approved geographic stores. No exact coordinates invented here. | Later L04; absent feature writers/scheduler are planned, real catalogue/local reviewer and provider flows UNVERIFIED. G14. Location/privacy. |
| C26 Operational/security logs and counters — Q23/Q43 | `logs/app.log`, `audit_log`, `rate_limits` action/identifier/attempt time; PHP/web-server/platform/provider diagnostics and exports (external instances UNVERIFIED). Login rate key can be raw IP and is logged. No browser analytics persistence or deployment telemetry configuration identified as accepted. | W06/W17. Login clears its attempts on success; no age-based rate/log worker. No rotation/archival expiry in `logger.php`. G06. Operations/security/privacy; L05/L07. |
| C27 Offline/import evidence and receipts — L02b–L02e/Q24–Q26 | Local bundle/snapshot/catalog/plan/permit files; `synthetic_import_destination`, `synthetic_import_pairs` snapshot/binding/guard/counters; `synthetic_import_facts`, `synthetic_import_equivalences`, `synthetic_import_keys`, `synthetic_import_receipts` with original `expires_at`; private event JSON and result JSON, no ordinary chat body. | W21/W22/W23. Offline planner reads files only; executor is synthetic, guarded, non-enabling. Expired reads/imports refuse; physical purge/frontier compaction absent. G08/G12. Engineering/privacy. |
| C28 Cleanup and recovery instructions — Q24/Q26/Q38 | `cleanup_outbox` type/subject/payload/status/run-after/attempt/error/created/completed; co-located `protective_replay_events` event/subject/actor/payload/occurred/replayed; `restore_replay_runs` label/availability/completeness/reopen flags/time/evidence JSON. No independent store, protective version stream, verified-purge/retain-until/copy manifest. | W15/W18/W22; W07's native pair branch returns before legacy `match_closed` event capture. New block/unblock, consent and L01 intake are not a comprehensive replay stream. Restore table writes observed only in tests. G04/G12/G13. Backup/operations/security/privacy. |
| C29 Backups, exports, restored copies — Q19/Q26/Q27 | **External UNVERIFIED:** DB/PITR/redundancy, media versions, provider retention, manual/export files, isolated restored servers and key copies. No runtime user-data/evidence export store/controller or copy-expiry inventory identified. | Export/copy feature stores **planned** where absent; infrastructure cannot be inferred from YAML/SQL. G12/G14. Backup/privacy/operations. |
| C30 Support/privacy/recovery tickets — Q23/Q27/Decision 31 | **Planned** case/reference/contact/identity-proof/closure/exception/deadline/attachment stores; no monitored support/privacy inbox established by repository source. | Human compromise/disputed-authorship and post-deletion rights routes remain gates, not `support@bushisa.me` operational proof. G14. Support/privacy. |
| C31 Aggregates — Q13/Q23/Q43 | **Planned** sufficiently suppressed daily counts without recoverable account/pair keys, period/creation and expiry. View hashes and user-level telemetry are not these aggregates. | No aggregate writer/purge scheduler identified. G14. Analytics/privacy. |
| C32 Engineering/schema/test artifacts — release consistency and evidence | `schema_migrations.version/applied_at` records schema application; repository questionnaire catalogue/code/configuration definitions are versioned engineering artifacts. Test harness `.tmp` bundles/snapshots/sessions/HTTP logs and temporary barrier/control JSON under the OS temp directory are separate from committed selected sanitized evidence JSON/Markdown. No real user exports inspected. | W22/W23/W25. Schema application time is not an account/content clock; no personal-content retention is licensed by an engineering artifact. Some barrier participants are pruned lazily by matching key/age; no general test-artifact expiry worker. Engineering controls fixture/key disposal, outside production retention claims. |

### Category access, erasure, holds and recovery

`+24h`, `+7d`, etc. mean elapsed additions to the specified event, never a job's
next run or a fresh interval after a retry. Earliest applicable denial/ordinary
expiry wins. H1 applies only to specifically authorized evidence copies.

| ID | Approved rule versus proposal; access cutoff and live deletion | Hold/recovery behavior and implementation gap |
|---|---|---|
| C01 | Q11 A: pending-email cutoff creation +72h; unfinished onboarding cutoff first successful email approval +7d; live cleanup by cutoff +24h. Resends/visits do not renew. | H0/R0. Account expiry is not the challenge expiry. Missing first-approval anchor cannot be backfilled from guesses. |
| C02 | D02/Q12 A: rejected attempt permanently non-activatable; entered content/photos/answers/raw DOB erased within rejection +24h. Q12 finite HMAC record **365d P/G**, later fresh adult eligibility unresolved. | H0/R0. Do not erase the only eligibility protection and interpret absence as approval; no indefinite child-profile retention either. Next rejected-attempt design needs its recorded review. |
| C03 | Q13 A purpose/current service use; replace values within 24h, Q14 deletion earlier. Candidate field allowlist P/G; no invented universal active-profile duration. | H0/R0. SQL overwrite/replacement handles current values; abandoned/replaced objects and retained backups are distinct. |
| C04 | Q13 A: delete at intentional foreground activity +365d via C05. Warning at 11 months has **CONFIRM exact offset**, not an invented 335d clock. | H0/R0; polling cannot reset it. No durable activity/expiry or scheduled deletion implementation. |
| C05 | Q14 A: immediately deny deleting account/profile/discovery/new interactions/affected chat/media, revoke sessions/challenges and stop nonessential mail. Ordinary live data <=deletion +7d; media <=+24h. | H0; only separate evidence/minimal consent/enforcement/recovery exceptions. R0/R1. Current logical denial is not completed erasure. Repeated deletion must not reset `deleted_at` or completion deadline. |
| C06 | Q22 challenge/resend settings remain P (code defaults 600s/five failures are not owner approvals). Single-use/current-generation checks exist in code. A lifecycle controls retain keyed-HMAC/external-key protection and existing immediate terminal verifier erasure; residual verifier/attempt detail <=use/expiry +24h. | H0/R0; never retain a redeemable verifier for a case. Restore must not revive used/superseded challenges. No residual detail/address purge. |
| C07 | Q22 A: useful dispatch deadline `min(queued_at+5m, challenge_expiry-60s)`; terminal secret body erased immediately, <=terminal +1h ceiling; code-free metadata <=terminal outcome +7d. Provider acceptance is not inbox arrival. | H0/R0. Current SMTP expiry equals challenge expiry, not useful deadline; retries don't change it but can run too late. Fake `sent` rows hold plaintext until challenge terminalization/expiry, not terminal +1h by construction. No metadata deletion or provider-copy proof. |
| C08 | Q47 A: >=256-bit reset token, hashed challenge, issuance +15m; supersession/atomic use/password success invalidate it; hash erased <=use/expiry +24h. Same bounded mail deadline/terminal-body ceiling as C07. Resend 60s/3-hourly/10-daily limits remain P. | H0/R0. Planned, including reset success session revocation; no inherited signup/ban/onboarding bypass. |
| C09 | Immediate account/session-version denial A. Q40 DB store, 30m/12h student and 15m/4h privileged clocks and cookie redesign remain P. Current code refreshes its 1,800s idle cookie/activity on protected requests, including polling. | H0/R0. Session revocation is not proof of session-file erasure. Handler/GC/backup facts require authorized environment capture, not this task. |
| C10 | Q06/Q13 A: clear active pair likes on unmatch; auto-discovery exclusion +7d; unmatched non-safety like history <=original event +90d; individual recommendation impressions +30d. Q09 pause stops recommendations and cached responses <=5m. | H0/R0; minimal safety state C11 separate. No ordinary like-history archive needs creating merely to retain it 90d; absent impressions/cache/pause stores remain planned. |
| C11 | Approved budgets/endings/declines/blocks and fixed 168h probation are independently enforced. Decision 29 new qualifying post-expiry activation is separate; active deadline never extends. Q23 ledger/probation retention remains P (probation active +30d proposed); code removes inactive periods at linked case final +365d. Retain required minimal active protections, not messages indefinitely. | R0 must precede removal of obsolete live safety rows. Do not equate missing history with zero counts/permission, or purge uncleared `report_block_links` to permit removal. Actual post-deletion field scope/purpose review remains G; no metadata proposal is adopted by the existing code. |
| C12 | D04 A: request creation +7d; cancel/expiry restart boundary +12h, original expiry anchoring; sender 3/12h (probation 3/24h), recipient 10/12h, pair direction lifetime 3. Q24 A: terminal expired/cancelled/declined detail <=terminal +30d. Code also assigns revoked detail +30d. | H0/R0. Event/count/decline/cutoff separation must survive physical deletion. `message_request_sends` currently purges at age 24h, independent of counts. Q23 extra retention buffer is P, not grounds to change approved enforcement. |
| C13 | Code uses operation creation +30d and rejects at expiry. No owner-approved standalone universal receipt-retention value inferred from this. | H0/R0. Receipt expiry must not reset versions/budgets or reapply an old transition; preserve L02 retry semantics while purpose/category review is completed. |
| C14 | Q16 linked sent +365d / last human message +90d schedule is **P**. A safeguards: reads/retries cannot renew old messages and history expiry alone does not revoke approved grant. Q14/Q17/unsend earlier applicable rules already apply. | H0/R0; no new active-chat timer may be silently installed by L03a. No whole-chat hold. |
| C15 | Q17 A: explicit approved-chat ending +7d authorized read-only, then deny and physically purge <=+24h (maximum 192h from ending); blocking/deletion can deny sooner. Unmatch alone starts no clock. | H0/R0. Current text can be deleted at +7d, inside the ceiling. New-segment messages remain separate; old cleanup must not select solely by reused match ID. |
| C16 | D04 A: 15m from server send time, preserve existing exact edge; accepted unsend erases ordinary original text immediately and entry disappears. Q10 A: immediate media/link denial, tied in-app notice <=60s, originals/derivatives <=24h. | Only already-preserved existing-report evidence may survive separately under H1; R0. Device/download/screenshot copies cannot be recalled. Source has no functioning attachment/preview pipeline to certify. |
| C17 | Q10/Q14 event-specific notification denial/erasure applies; no general notification age adopted. Preference purpose follows active account/deletion. | H0/R0. Upsert `created_at=NOW()` is unsuitable as an immutable retention origin. Reads set `read_at`, not evidence of new consent or deadline. |
| C18 | Q15 A: deleting author's public content/attribution/projections removed immediately; ordinary live content <=+7d, media <=+24h; active authors may delete own posts. Independent replies may stay only without erased/identifying reproduction. No anonymous publication by default. | H0/H1/R0. Private pre-preserved evidence is separate. `confession_views` is personal exposure metadata, not automatically anonymous; its classification/duration needs purpose mapping (do not assume every view is a Q13 recommendation impression). |
| C19 | L01/Q18 A: submitted content/fingerprints expire no later than applicable case content, final resolution +180d or unrelated/accidental +30d. Own receipts never expose private source data. Content-free metadata requires its own permissible category; code retains some references to final +365d. | H1 only on specified evidence, not automatically every submission/receipt. R0. Null final-resolution deadlines are unresolved case state, not approved indefinite retention. G11. |
| C20 | Q18 A: evidence content final +180d, unrelated/accidental <=final +30d; content-free structured decisions final +365d. Final is later of effective notice +allowed appeal window or timely appeal disposition; genuine reopening needs reason/new resolution. Decision 32 ban defaults A, not automatic findings. | H1/R0. No creation +24-month substitute, no legacy `reviewed` shortcut. Annual privacy-reviewed minimum permanent-ban enforcement record may outlive evidence; erase <=30d after lawful end/overturn, no indefinite evidence content. Actual finalization/appeal/ban workflows absent. |
| C21 | H1 A durations/authority/release rule. Ordinary expiry remains unchanged; future holds cannot resurrect erased evidence. | R0 includes held-then-deleted protection. Current row predicate checks dates/released state only; authority and overdue release obligation are not established. |
| C22 | Q19 evidence access audit event +365d A; evidence export +24h A. Q23 general privileged/security/audit schedules remain P unless separately adopted. | H1 only for a justified specific audit item; R0. New `report_action_audit` is partially purged; legacy evidence audit and case content-free data remain unbounded in code. |
| C23 | Q21 A/G: necessary proof while relevant processing/account continues, then applicable terminal event +365d subject to per-purpose privacy review; minimal current withdrawal state while needed. No full IP/coords/text/answers in receipt. | H0/R0. Current `revoked_at` alone is not versioned recovery protection. Consent proof is not indefinite and cannot override later withdrawal; deleting `users` may cascade away receipts prematurely. |
| C24 | Immediate deny for unsent/deleted/replaced/withdrawn media as applicable; approved live erasure <=event +24h. Actual media links, bytes, derivatives and failed removals need completion evidence. | H1 only specific evidence objects; R0/R1. Profile pointer deletion or queued path is not absence of all files/cache/version copies. Proposed backup 7d is not a live-purge extension. |
| C25 | Q30–32 A: withdraw immediately from ranking/disclosure/cursors; explicit confirmation +7d freshness, live area/cache purge <=expiry/withdrawal/replacement +24h; no movement history; minimal consent proof has no area/coords. 2 successful changes/rolling 24h A; withdrawal unrestricted. | H0/R0; planned features cannot be marked implemented. Source combination, zone widths and ranking bands retain P status. |
| C26 | Q23 P: ordinary logs 30d/no archive, justified raw IP <=7d/minimized security <=30d, rate events longest window +24h. Q23/Q43 A: redact before transmission, no bodies/credentials/student IDs/location/answers in ordinary logs. | H0/R0. No accepted timer invented for absent fields. Existing IP-bearing log/rate rows and exception text lack purge/redaction; provider minima/copies UNVERIFIED. |
| C27 | Contracted absolute source/proof/permit/snapshot expiry, fact expiry bounded by original event retention; retry/new alias inherits old receipt expiry. These are synthetic v1 evidence boundaries, not new production metadata approvals. | H0/R0. Never delete facts/receipts and reset stored counters as if import never occurred. Loss of counted frontier/registry stays fail-closed; staged sensitive snapshots need purpose/expiry review before any real-data design. |
| C28 | Q24 A: at least hourly cleanup, alert at missed deadline/escalate after 24h unresolved, content-free counts/category/deadline/error/completion watermark. Q26 R0 bound, no hard-coded 49d purge. No standalone numeric queue/replay-audit retention is adopted. | H0/R0. Co-located events/queue are not independent durability; queue `done` is not erasure proof. Repeated enqueue can overwrite payload, keep `done` and omit a new obligation. |
| C29 | Q26 copy settings DB 35d/media 7d/troubleshooting export 24h are P/G. Approved Q19/Q27 evidence/user exports are created +24h for access and stored file. Isolated rehearsal copy <=creation +72h or earlier source expiry A. | R0/R1; actual copy manifests/lifetimes and isolated restored-server expiry UNVERIFIED. No exports or restore executed. |
| C30 | Q27 A identity/redaction/deletion/export route and 2d acknowledgment/30d aim, qualified exceptions; these service targets require real ownership/capacity. Q23 support closure +90d and rights proof +365d remain P. | H0/R0. Separate record types, closure/attachment clocks and proportionate verification are planned. Temporary unset Reply-To does not solve missing monitored contact. |
| C31 | Q13 A: sufficiently non-identifying suppressed aggregates +13 calendar months, with pinned UTC calendar arithmetic still an implementation requirement. No recoverable user/pair key or raw sensitive cohorts. | Not automatically personal-data erasure evidence; establish non-identification first. Copies inherit original expiry. Planned. |
| C32 | No numerical policy for content-free schema-version receipts/public definitions or general test artifacts is adopted here. Schema receipt trigger is actual migration application; test artifacts retain their own creation/capture times. Real rehearsal copies use C29. Guarded fixture output is not production provenance or physical restore proof. | Release/restore must reconcile schema/code versions before reads/dispatch. No ordinary user data belongs in routine fixtures; sanitized committed evidence is distinct from temp sessions, keys, database files and HTTP logs. Not every test DB is a rehearsal. Actual artifact locations/expiry require operator inventory. |

## 3. Writers, reads with side effects, cleanup and scheduler coverage

This is the identified repository writer set, not an attestation that every
deployed/retired/direct-SQL writer is known. Existing L02b all-writer provenance
gates remain open. No writer below was executed for this inventory.

| ID | Concrete entry point and function(s) | Mutation / clock / cleanup responsibility |
|---|---|---|
| W01 | [signup auth](../../php/auth.php) `register_user`, [signup flow](../../php/signup_verification.php) `bushisa_verify_signup_code_locked`, `bushisa_complete_adult_onboarding`; [register](../../register.php), [verify](../../verify_email.php), [onboarding](../../onboarding.php); `public/register.php` only redirects | Insert identity/profile/preferences, approve email and bump version; activate adult or block rejected attempt. Approval stores no first-approved time; underage branch does not persist rejected DOB. Auth/eligibility owner. |
| W02 | Same signup module: `bushisa_issue_signup_code`, `bushisa_terminalize_signup_code`, `bushisa_cleanup_expired_signup_codes`; [policy helper](../../php/policy_safeguards.php) `bushisa_erase_terminal_signup_material`; `verify_email.php` resend | Lock account/challenge; supersede verifiers/mail, queue SMTP/fake bodies; consume/use/exhaust/expiry erases hashes. Terminal cleanup is not residual-row cleanup. Auth/mail. |
| W03 | [mail delivery](../../php/mail_delivery.php) `bushisa_queue_smtp_signup_email`, `bushisa_finish_mail`, `bushisa_dispatch_mail`; [CLI dispatcher](../../bin/dispatch_mail.php); `bushisa_queue_fake_signup_email` in signup module | Store encrypted redeemable body, advance attempt/next-attempt, erase terminal body; named dispatcher lock plus account/challenge/outbox locks. External transport under DB transaction cannot guarantee exactly-once provider delivery after uncertain commit; restore reconciliation remains required. Mail/operations. |
| W04 | [profile form](../../profile.php), [profile SQL](../../php/db.php) `update_profile`/`update_preferences`, [uploads](../../php/upload_validator.php) `save_upload`/`strip_exif` | Upload file before profile transaction; replace pointer/current values. JPEG EXIF removal is conditional on image support; other media/private-object handling isn't established. No old-photo enqueue and no `profile_images` insert in this path. Media/profile engineering. |
| W05 | [questionnaire API](../../api/questionnaire.php) and [find-match API](../../api/find_match.php); [questionnaire](../../php/matching/questionnaire.php) `matching_replace_answers`/`matching_save_answers` | Transactional delete/replace current answers, upsert `updated_at=NOW()`. A replacement timestamp is not account intentional-activity proof or an extension of retained old answers. Matching engineering. |
| W06 | [login auth](../../php/auth.php) `attempt_login`, [sessions](../../php/session_manager.php) `start_secure_session`, `refresh_session_expiry`, `require_login`, `destroy_session` | Session/cookie/CSRF/auth content, role/session-version reloads; every protected request can refresh activity. Login clears login attempts and writes audits/file logs. No durable foreground/inactivity writer. Auth/security. |
| W07 | [swipe API](../../api/swipe.php), [pair API](../../api/pairs.php); [pair history](../../php/pair_history.php) `bushisa_pair_ensure`, `bushisa_pair_restore`, `bushisa_pair_match`, `bushisa_pair_close_relationship`; [blocks](../../php/block_handler.php) `block_user`/`unblock_user` | Create/update original pair, likes, fresh mutual generation/decline clearance, own restoration and caps, suppression/blocks. Pair GET/reader ensure can materialize minimal ledger/projections. Never equate this with authentic legacy completeness. Chat/safety. |
| W08 | [request API](../../api/message_requests.php), pair helper `bushisa_pair_request`, `bushisa_pair_observe_request`, `bushisa_pair_request_terminal`, `bushisa_pair_open_chat`, `bushisa_pair_operation` | Send/count/global-window event, lazy pending expiry, approve/decline/cancel/end, per-segment deadlines and retry receipts. GET can observe a legacy request; projection expiry is not physical cleanup. No new timestamps invented for unknown terminal history. Chat engineering. |
| W09 | [message API](../../api/messages.php), [chat page](../../chat.php), [pair messages](../../php/pair_messages.php) `bushisa_pair_messages`, `bushisa_pair_match_list`, `bushisa_pair_chat_profile` | Send with UTC/segment; read sets `is_read`; unsend clears original/moderation bodies immediately. Lists/previews use per-message deadline/current authority. No read renews that deadline; no attachment worker. Chat engineering. |
| W10 | [confession API](../../api/confessions.php), [confession page](../../confessions.php) | Two posting paths; API additionally inserts references and authorized returned-post view hash. GET view count is a write. Flagged/deleted source projections are filtered; author self-delete is not supplied by these POST paths. Content engineering. |
| W11 | [notification API](../../api/notifications.php); `operational_write_notification` in [operational helper](../../php/operational.php), called by requests/confessions | Upsert notifications/preferences, mark read; dedup rewrites `created_at`. `bushisa_suppress_chat_notifications_for_match` deletes relationship notices. No generic expiry/unsent-object notification association. Product/cleanup. |
| W12 | [safety API](../../api/safety.php), confession report adapter and guarded legacy `submit_report` rejection; [intake](../../php/report_intake.php) `bushisa_report_intake`, `bushisa_report_capture`, `bushisa_report_activate`, `bushisa_report_attach_block` | Proof-authorized allegation/contexts, dedup receipts/evidence/submissions, fixed probation and optional personal block linkage under shared transaction. Receipt/activation is not a finding/ban/final resolution. No ordinary content access restored. Safety engineering. |
| W13 | Same intake module `bushisa_report_own_status`, `bushisa_report_review_evidence`, `bushisa_report_block_review`, `bushisa_report_clear_block`, `bushisa_report_audit`; legacy `bushisa_case_evidence_for_viewer` | Reads can insert audits; new assigned/MFA/versioned review differs from legacy admin-with-reason exception. Clearance leaves personal block. No hold/finalization/assignment creation workflow identified. Safety/security. |
| W14 | [admin reports](../../admin/reports.php), [admin moderation](../../admin/moderation.php), [moderation](../../php/moderation.php), [report handler](../../php/report_handler.php) | `update_report_status`, `suspend_user`/`unsuspend_user`, `flag_confession`/`unflag_confession`/`delete_confession` and audit/log. Neither `reviewed` nor `dismissed` writes effective notice/appeal/final resolution. Suspend/unsuspend toggle a flag, not Decision 32's timed ban/appeal machinery. Safety engineering. |
| W15 | [account API](../../api/account.php), [delete page](../../account_delete.php); [anonymizer](../../php/anonymiser.php) `anonymise_user`, `anonymiser_soft_delete_user`, `anonymiser_detach_confessions`, `anonymiser_delete_file`; policy helpers `bushisa_prepare_account_deletion`/`bushisa_soft_delete_owned_confessions`/`bushisa_enqueue_cleanup`/`bushisa_record_protective_event` | SQL anonymization/session version, consent revocation, like/preferences/answers removal, confession blank/hide/detach, enqueue and co-located replay capture. Profile file unlink precedes SQL commit; errors are ignored. `deleted_at=NOW()` can redraft the anchor on direct repeated calls. `hard_delete_user` exists but no application caller identified; base FKs cascade consent/messages/reports and must not become an unreviewed worker. Cleanup/engineering. |
| W16 | [consent](../../php/consent_manager.php) `grant_consent`/`revoke_consent`/`has_active_consent` | Insert grant or revoke active rows using NOW; no general runtime caller found beyond W15's direct revoke. No independent protective event or full Q21 receipt fields. Consent/privacy. |
| W17 | [logger](../../php/logger.php) `log_action`/`log_to_file`; [rate limits](../../php/rate_limit.php) `record_attempt`/`clear_attempts` | SQL action/IP and append-only `logs/app.log`; generic rate rows. No content-free fixed-field enforcement or file rotation. Caller exception messages and raw rate keys need redaction; body-safe L01/L02 logging doesn't prove all legacy logging safe. Operations/security. |
| W18 | [cleanup CLI](../../bin/cleanup_policy.php) | Sequential signup cleanup, report transaction, optional pair transaction, expired code-bearing mail nulling, then marks only `signup_code_cleanup`/`synthetic_mail_cleanup` queue types done. Does not consume `media_delete`, `account_field_cleanup`, `confession_cleanup`, `message_request_cleanup` or `closed_conversation_cleanup`. No bounded claim/retry lease, category deadline/watermark or alert workflow. Operations/cleanup. |
| W19 | Intake `bushisa_report_cleanup` | Derive/minimize incident/evidence due from final resolution; erase explanations/hashes, held text exclusion; null intent hashes; expire new action audits and 24h send windows; delete some content-free references at final +365d. Does not purge legacy cases/evidence-access audit or final case/assignment rows. Safety/cleanup. |
| W20 | Pair `bushisa_pair_cleanup` | Expire pending requests using original expiry, maintain cooldown floor, delete due terminal requests/events/operation receipts/closed ordinary text/empty segments. Deliberately never clears lifetime counts/declines/cutoffs/blocks. DB-wide scans are not bounded per claimed job; imported fact/receipt frontier is untouched. Chat/cleanup. |
| W21 | [offline CLI](../../bin/legacy_history.php)/[validator](../../php/legacy_history.php); [synthetic executor CLI](../../bin/synthetic_legacy_import.php)/[module](../../php/synthetic_legacy_import.php) `execute`/`status`/`receipt_result` | Offline inputs read only; synthetic executor guards before connection, transactional fact/equivalence/receipt/key inserts and monotone count/version updates. New alias retains original expiry. Status only reads within locking transaction; neither real-data application nor physical purge adapter exists. Engineering. |
| W22 | [migration runner](../../bin/migrate.php), operational/report/pair helpers and SQL 001–009 | Schema/backfill/precision/trigger writers, including lazy native genesis trigger and report revision triggers. Controlled upgrades preserve known instants and leave writer epoch inactive; missing history stays incomplete. Runtime ensure calls must remain DDL-disabled. Migration privileges/effective deployed schema are UNVERIFIED here. Release engineering. |
| W23 | [guarded seed](../../database/seed.php), [test bootstrap](../../tests/bootstrap_database.php), [synthetic fixture helpers](../../tests/support/synthetic_import_fixture.php), `tests/integration_l01b_reports.php`, `tests/integration_l02a_pairs.php`, `tests/integration_l02d_imports.php`, `tests/integration_l02e_races.php` and browser fixture SQL | Disposable-only setup/reset, synthetic approval/final-resolution/hold/epoch values, files/session stores and fake mail. These are **test writers**, never evidence of real notice, approval, history or cutover. They are not run in this task. Engineering. |
| W24 | [WebJob shell](../../deploy/mail-webjob/run.sh)/[schedule](../../deploy/mail-webjob/settings.job), [deployment instructions](../azure-smtp-deployment.md), [.github CI](../../.github/workflows/ci.yml)/[release workflow](../../.github/workflows/main_bushisa.yml) | Template six-field schedule `0 * * * * *` means every minute as documented; runs cleanup **before** dispatch under `set -e`. Cleanup failure prevents dispatch in that invocation. Installation, platform support, actual cadence, overlap behavior, alert recipients/coverage and retention settings are **UNVERIFIED**, not established by source YAML. No scheduler for other missing categories identified. Operations/L07. |
| W25 | [guarded HTTP barrier](../../php/http_test_barrier.php), test-only [import controls](../../tests/support/l02e_import_control.php) and harness file writers | Explicit test gates; atomic temporary JSON publication, `.tmp` evidence/HTTP logs, temp ready/release/error records. Stale same-key barrier JSON pruning is not comprehensive fixture or production cleanup. Engineering only. |

## 4. Clock and erasure findings to carry into implementation

These are source findings, not newly executed acceptance results. Their IDs
connect the category inventory to actionable work without treating every gap as
an owner policy question.

| Gap | Actual behavior and implication | Required engineering response / dependency |
|---|---|---|
| G01 Missing original account anchors | Creation is stored, but no first-approved/rejected/foreground/expiry times or account-expiry worker. Latest challenge consumption can mean resend/supersession. | Add explicit one-time server events/deadlines before expiry workers; classify unknown legacy anchors without fabricating times. Pending approval/underage/inactivity cleanup needs different triggers; precise warning offset and anti-replay policy remain distinct decisions. |
| G02 Incomplete ordinary deletion/object index | Anonymizer clears many fields immediately but leaves residual account/profile/relationship metadata; physical account job is absent. Replaced photo can be orphaned; image index rows are removed after enqueue before bytes are proved gone. Confession body is blanked but purge date uses proposed +30d helper. | Inventory exact per-field purpose/exceptions; preserve object identifiers until verified removal and recovery obligation. Replace obsolete 30d account intent with approved 7d deadline in a later bounded slice, not this task. |
| G03 Renewed/non-semantic clock fields | Session activity/cookie renew on polling; notification dedup renews creation; no intentional activity writer. File log timestamp uses PHP `date()` without repository-wide UTC initialization. | Distinguish last intentional activity, delivery/read/update time and immutable retention origin. A current-value update may be legitimate without extending the old copy's expiry. Never infer account activity from these fields. |
| G04 Access denial is incomplete physical/secret erasure | Account eligibility/version filters protect ordinary routes, but active verification hashes/pending mail aren't immediately cancelled by `bushisa_prepare_account_deletion`; SMTP dispatch later rejects deleted status. File unlink is before SQL commit and ignores failure; token/session files/media links aren't centrally indexed. | Immediate transactional secret cancellation plus separate verified object/session cleanup. Do not claim revocation is physical erasure or that a rolled-back SQL transaction restored an unlinked file. Preserve pre-existing scoped evidence separately. |
| G05 Useful mail/challenge boundary mismatch | Outbox expiry equals challenge expiry rather than min(queue +5m, expiry −60s). Retry schedule changes, expiry does not. Verifier expiry uses PHP UTC `< now`, while dispatcher SQL uses `> UTC_TIMESTAMP()`; exact at/after boundary needs alignment. Defaults/resend rate settings are not approvals. | Later L06 changes need original immutable useful deadline, server-time boundary tests and atomic supersession. Residual challenge rows/attempt detail and code-free mail metadata need their already approved cleanup clocks; do not introduce reset implementation here. |
| G06 Missing log/audit/mail detail cleanup | Old audit/access/rate/delivery addresses/metadata and file logs have no complete age cleanup. `log_action` and login denial can record raw IP; some legacy error logs include exception messages. | Field/purpose mapping and fixed-field redaction first; Q19/Q22 approved bounds can proceed, Q23 proposed general values cannot be silently installed. Capture actual provider minima/retention separately. |
| G07 Planned recommendation/pause stores | No durable impressions/current pause/cache invalidation workflow found. Old likes are actively deleted on unmatch; this does not itself require a new 90d archive. | L02/L04 follow-up functional work where missing, not an invented cleanup timer/table. No geographic collection or actual cache-latency proof here. |
| G08 Metadata purpose/continuity split | Receipt/proof implementation TTLs are not independent owner retention approvals. Counts/declines/cutoff/block links are needed after ordinary events disappear. Synthetic imports refuse missing receipt/fact frontier; there is no compaction/purge worker. | Review minimal field/category purposes, then design bounded safe frontier compaction and recovery. Deleting expired registry rows alone could recount imported history or lose clearance/cutoff evidence. Keep incomplete histories fail-closed; no real import/legacy activation. |
| G09 Partial text coverage, not full chat cleanup | Native explicit end assigns per-message +7d; readers deny at expiry and text worker can delete then. Unmatch doesn't assign that deadline; restart uses new segments. Active-chat 365d/90d remains P. Unsent rows retain empty row metadata and separate existing evidence only. | Preserve these tested text invariants. Add account/media/projection cleanup with immutable event/segment selection; don't apply a match-wide old queue intent to newly restarted text. No invented unsent-row retention or active-chat expiry. |
| G10 Missing category workers/projected copies | Confession/reference rows/views, notifications, replaced media and queued account/media tasks lack full consumers. No attachment/report-image private validated upload path exists. | Implement bounded per-category consumers with object completion, source/derivative indexes and earlier denial. Missing objects/indexes require tracked outcomes; never mark every queue item done merely because its deadline passed. |
| G11 Final resolution/holds incomplete | New case/evidence due is NULL until finalization, but no real notice/appeal/finalization writer exists. Legacy helpers use creation +24 months and old evidence reader ignores due time. New cleanup's LEAST can only shorten existing due dates; it cannot validate an appeal or represent a genuinely reopened resolution. Freezing an initial appealable finding as final would delete prematurely. Matching active hold rows protect evidence text only; submission explanation is erased independently. New review reader refuses ordinary-expired case data even if bytes are held. Old case/assignment/audit skeletons lack purge. | Establish trusted/versioned resolution and immutable category expiry, explicit item-scoped authority/release/overdue obligations and qualified held-evidence access. An old status or arbitrary timestamp is not final resolution. Missing review/staffing is a gate, not permission for infinite content. L03a below. |
| G12 Recovery/cascades/later-session risk | Existing replay is in the same DB and lacks independent versions/copy bounds; native pair branch skips legacy event recording. `hard_delete_user` would cascade messages/consent/legacy reports without scoped evidence/recovery preparation. Old cleanup intents identify a mutable subject, not an immutable event/session. | Independent replay interface and per-event completion/versioning before broad hard deletion. Preserve new segments, active protections, current sessions/versions and import floors. Co-located outbox/restore flags/005 precision alone cannot authorize reopened traffic. |
| G13 Worker execution/observability | CLI is sequential but not one atomic global unit; category failure can leave earlier committed progress. Report/pair scans lack bounded claiming/watermarks. Queue enqueue can replace payload/clear error while a previously done row remains done. Scheduler runs cleanup before mail, so one failure can delay useful dispatch. | Per-category bounded atomic units, stable obligation IDs, safe repeat/resume, lease/claim policy, deadline/error/count watermarks and overdue alerts. Do not reset deadlines/retry after unknown commit blindly. Verify actual cadence/rights in a later authorized environment task. |
| G14 External, policy and feature dependencies | Missing accepted field/purpose mapping, underage anti-replay duration/later-adult rule, active-chat schedule, Q23 metadata/copy values, exact warning offset; planned rights/location/reset/support/aggregate stores; actual staff/keys/providers/copies unknown. | Keep P/CONFIRM/VERIFY/REVIEW GATE labels. Engineering inventory or approved text lifecycle work need no reapproval of Decisions 1–33. Real appointments/qualified legal/security/safety findings cannot be invented. |

The DB bootstrap [sets the SQL session to UTC](../../config/db.php). Thus SQL
NOW()/CURRENT_TIMESTAMP through that connection is UTC, despite mixed spellings.
New L01/L02 helpers use UTC_TIMESTAMP(6) and explicit UTC DateTime parsing;
generic file logging and some legacy date logic rely on the PHP process zone.
Actual deployment clock synchronization, session handler/GC, storage paths,
database constraints/privileges and provider expiry remain UNVERIFIED. Static
inspection establishes predicates, not exact timing under a running scheduler.

## 5. Next bounded implementation: L03a text evidence expiry and scoped holds

Prioritize **new-intake (`intake_contract_version=1`) text evidence lifecycle**:
trusted final-resolution input, immutable content expiry, scoped hold/release,
bounded text purge and content-free recovery obligations. This uses approved
Q18–Q20/Q24–Q26 and Decision 33 appeal requirements without depending on proposed
active-chat/log/backup durations or broad account/object deletion. It addresses
G11/G13 and the text part of A36; it does not complete A36 or LB14/LB25–LB27/LB32.

Concrete engineering boundary for the later prompt:

1. Add forward-only lifecycle records, preserving existing text, timestamps and
   safety references. Require a verified effective notice and the allowed appeal
   window (14 days), plus disposition of any timely appeal. Derive final resolution
   as the later boundary, never status/creation/import time. Missing/contradictory
   notice/appeal anchors return a review prerequisite, deny unauthorized reads and
   do not invent a deletion deadline. Legacy cases/uncertified anchors are excluded
   with explicit reasons and due-review escalation, not silently certified or given
   an indefinite retention approval. Complex appeal extension/real reopening needs
   its documented authorized event; no arbitrary edit/reset of old expiry.
2. Persist immutable evidence/submission-content expiry as final +180d, or final
   +30d for wholly unrelated/accidental outcomes. Keep structured decision +365d
   and Q19 access-audit +365d as distinct categories. Do not infer Q23 approval for
   every receipt/hash/skeleton. No broader metadata deletion or import frontier
   compaction in this first slice; flag their purpose/recovery dependency.
3. Replace opaque hold trust with item/version-bound authority records. Support
   assigned-reviewer urgent <=24h and joint safety-lead/privacy <=30d, next review
   <=14d, explicit justified renewals/release, current authorization/MFA/session
   checks and content-free audit. Fixture capabilities are not appointments.
   A hold on one evidence item does not protect intake explanations, source chat,
   other evidence or expired receipts. If an explanation needs preservation, it
   needs its own authorized evidence item before erasure, not a case-wide hold.
4. Release/expiry records a stable item obligation: original due remains; overdue
   purge is due no later than release/hold-expiry +24h. Renew/read/retry never
   changes the old due time. Purge in bounded transactions under the current shared
   report serialization/row ordering, rechecking holds/versions inside the unit.
   At/after ordinary expiry ordinary readers remain denied; any held-evidence
   reviewer access needs an explicitly scoped qualified-review purpose, not a
   general case/ordinary-chat resurrection. No attachments or media URLs.
5. Atomically clear only authorized due text/hash copies and record item/category/
   deadline/count/outcome/completion plus a minimal **purge/restriction replay
   obligation**, without body or secret. Same obligation ID/retry has one effect.
   Keep session versions, grants, blocks/clearance, probation and lifetime budgets
   untouched. Use real independent-journal acknowledgment semantics: a local outbox
   is merely pending publication, never proof of external durability. A missing
   independent source/high-water mark blocks restored reads/dispatch/release;
   a synthetic adapter may test this interface only with explicit non-production
   attribution. The real independent store/copy inventory is still G.
6. Use guarded disposable fixtures/fake mail only in that later implementation;
   no live changes. Rehearse fresh/upgrade/repeat migrations if changed. Actual
   concurrency needs distinct workers/connections and observed overlap; injected
   errors are not real engine-deadlock evidence. A real restore remains a separate
   authorized/witnessed operation. Do not broaden `cleanup_policy.php` to delete
   all account/media/import categories opportunistically.

After that bounded slice, account-attempt anchors/secret cancellation, object
indexes/physical deletion and independent journal/copy reconciliation are separate
L03 dependencies. L04 geography, L05 telemetry, L06 reset/mail and L07 actual
configuration/staffing remain their own backlog tasks, not invented L02 phases.

### New L03a acceptance plan — every case NOT RUN

The timestamps and identities below are **synthetic design examples**, not
records or appointments. Fixture F is a contract-v1 case with two text evidence
items E1/E2, an ordinary source message, an intake explanation and an unrelated
case U. Effective notice `2026-01-01T00:00:00Z` / 02:00 Africa/Harare, no appeal,
gives final resolution `2026-01-15T00:00:00Z`. Use a server-controlled test clock;
compute durations, do not assume the example is real historical evidence.
Fixture P has a timely appeal whose final disposition is later; fixture R is a
synthetic stale restore plus a separately simulated independent journal. Positive
source coverage/authenticity and actual witnessed restore are not supplied.

| ID | Synthetic operation | Expected observation | Status |
|---|---|---|---|
| LC01 | Missing notice, missing appeal boundary, legacy `reviewed` or contradictory disposition supplied | Explicit review prerequisite; no invented final timestamp/expiry and no destructive write; ordinary authorization remains enforced. | NOT RUN |
| LC02 | F before, exactly at and after final +180d; U at final +30d | Store original expiry once; at/after deny ordinary evidence/receipt content even without worker. Due cleanup erases only selected content, not structured decisions/grants. | NOT RUN |
| LC03 | P timely appeal received before allowed deadline, pending then finally disposed; compare late ordinary repeat appeal | Initial finding cannot start final-retention clock during timely appeal. Valid final uses later boundary; repeat appeal/read does not silently renew old clocks. Unsupported reopening stays explicit review gate. | NOT RUN |
| LC04 | Assigned reviewer urgent hold on E1; unauthorized actor, wrong case/item/version and missing MFA/removed role | Only valid exact item hold <=24h accepted; unauthorized variants fail atomically. E2/explanation/source remain independent. | NOT RUN |
| LC05 | Joint ordinary authorization, incomplete approval, >30d duration, >14d review interval and automatic renewal attempt | Exact approved authority/limits; no single-role permanent hold, no automatic renewal or rewritten original expiry. | NOT RUN |
| LC06 | Release E1 before ordinary expiry; release/expire another hold after ordinary expiry | First retains original due; second stable purge obligation due within 24h of release/expiry. Early worker/read/retry never re-dates either. | NOT RUN |
| LC07 | Same release/purge retried after lost response or concurrent identical submissions | One authority transition/obligation/effect; existing safe receipt or explicit current-state conflict, no duplicate deletion/audit effect or deadline renewal. | NOT RUN |
| LC08 | Valid hold spans E1 ordinary expiry while E2 and explanation expire | Preserve only already-authorized E1 evidence; deny ordinary case/source resurrection. Qualified scoped hold access has current assignment/authority and content-free audit. | NOT RUN |
| LC09 | Hold versus purge overlap in both orders using separate processes/SQL connections | Engine-observed overlap and recheck: authorized hold-first preserves E1; purge-first leaves no content to hold/resurrect. No torn capture, guessed authorization or new source lookup. | NOT RUN |
| LC10 | Assignment/session revocation or account deletion overlaps held review/purge | Current authority wins; unrelated private data undisclosed. Existing scoped evidence may persist, but account sessions/chat grants are never restored by hold/cleanup. | NOT RUN |
| LC11 | Failure before/after text mutation, obligation insert and commit; restart after unknown result | Contracted unit rolls back or durable receipt identifies committed outcome; bounded retry/revalidation, no half-purged evidence or missing completion obligation. | NOT RUN |
| LC12 | Reads, exports, equivalent report retries/addenda, migrations and routine hold reviews | Original content expiry unchanged; any new independent event remains separate. Explanation/fingerprint erasure cannot be bypassed by receipt or a second case's hold. | NOT RUN |
| LC13 | Two concurrent cleanup batches, partially failed batch and repeat run | Bounded claims/transactions, one effect per obligation, safe progress/resume, counts/category/deadline/error/watermark only. A failed unit is not marked done; overdue obligation alerts at deadline. | NOT RUN |
| LC14 | Cleanup with new restarted chat segment, active probation, report-linked block and maximal lifetime counts | Exact new-session text/grants/versions, fixed probation, current clearance and count/cutoff floors unchanged. Import private facts/receipts unchanged; no A/LB case completion inferred. | NOT RUN |
| LC15 | R restores an older held/plain evidence copy; independent journal available versus missing/incomplete | Before reads/dispatch replay later purge/release/current restriction/expiry, no resurrection. Missing continuity fails closed. Synthetic interface check is not a witnessed actual restore. | NOT RUN |
| LC16 | Recovery bound with D+7d purge / proposed 35d DB copy / 7d margin; later purge/copy or unknown copy | Conditional floor D+49d, later facts extend as necessary, unknown copies block release. No copied backup or hold review restarts source expiry; no universal 49d deletion. | NOT RUN |
| LC17 | Fresh/008-or-current-schema upgrade, repeat migration and existing unknown resolution rows | Preserve existing content, immutable clocks and safety references; exclude unknown anchors with explicit reasons, no invented appointments or production-applied claim. | NOT RUN |
| LC18 | Untrusted/production destination, unsafe fixture flags, real mail selector, unavailable required grants | Guard refusal before connection/mutation where applicable; atomic privilege failure, fake mail only, no automatic grant broadening. Tests must be run only when separately authorized for implementation. | NOT RUN |

## 6. Missing decisions, engineering work and external evidence

| Class | Exact remaining requirement / impact |
|---|---|
| Existing policy questions, not reopened approvals | Q12 365d underage HMAC proposal and later fresh adult application; Q13 candidate field allowlist/purpose review and exact inactivity-warning offset; Q16 linked active-chat schedule; Q23 metadata schedule; Q26 copy-retention settings. Existing privacy review is also required for consent terminal purposes and long-lived ban suppression. These must not be smuggled into a generic purge worker. No new owner decision is required to build the approved L03a text/hold core. |
| Engineering that can proceed on existing approvals | Trusted resolution/appeal anchors, exact scoped authority and release obligations, immutable text expiry and bounded idempotent worker, explicit independent-journal interface/fail-closed checks; then account/secret/object inventories and their approved clocks. A missing schema field or scheduler is engineering work, not CONFIRM from the owner. |
| VERIFY operational facts | Deployed schemas/constraints/migrations, UTC/clock synchronization, runtimes/least-privilege roles/definer, session handler/GC, actual WebJob installation/cadence/failure isolation, every object/cache/provider/backup/export location and expiry, independent journal durability/acknowledgment/copy exclusion, purge watermarks and restore high-water marks. No production or external store inspected. |
| CONFIRM people/contacts and REVIEW GATE | Actual operations/cleanup/backup/safety/privacy/security primaries/backups and coverage; qualified reviewers, independent appeal reviewer, lawful hold/purpose/export acceptance and real monitored privacy/support contact. Takudzwa is product reviewer, not an inferred DPO, sole independent appeal reviewer or on-call appointee. Staffing/service targets remain conditional. |
| Separate execution/release gates | L03a cases LC01–LC18 all NOT RUN; broad account/media/hold/import compaction and full recovery tests remain later work. Actual isolated restore NOT RUN; independent review and production migration application UNVERIFIED. No database/browser suite run for this documentation inventory. |

Current historical matrices are preserved: **A01–A45 = 24 PASS / 19 PARTIAL /
2 NOT RUN**; **LB01–LB33 = 21 PASS / 12 PARTIAL / 0 wholly NOT RUN**, with their
listed unexecuted variants. LC01–LC18 is a separate proposed acceptance plan,
**0 PASS / 0 PARTIAL / 18 NOT RUN**. The 38-link historical check, L02e's 95-link
check and this checkpoint's documentation checks are not functional test runs.
Candidate CI remains exactly head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`,
push `38546846225`, PR `38546851035`; no new CI run or test attribution is claimed.

## 7. Documentation validation

Documentation validation PASS, observed **2026-10-17T07:13:31.139Z** /
**17/10/2026, 09:13:31 Africa/Harare** (UTC+02:00). This is a check time,
never owner acceptance.

- 114 local links and six heading targets resolve across the two changed Markdown
  files; no trailing whitespace or broken table/case references.
- 32 category IDs occur in both inventory tables; 25 writer and 14 gap definitions
  are unique/consecutive. All 52 declared schema table names map to categories;
  72 named functions resolve in repository source. These are static checks, not
  deployed-schema or writer-cutover attestation.
- LC01–LC18 all remain NOT RUN; unchanged A01–A45 totals 24/19/2 and LB01–LB33
  totals 21/12/0 are checked against their existing evidence/contract.
- The 14-day synthetic notice example, 168h fixed probation, 192h closed-history
  ceiling and conditional 7+35+7=49d arithmetic are consistent. Approved versus
  proposed underage/active-chat/metadata/copy/signup settings remain separated.
- Git diff checks pass. Application/migration files, decisions, retention policy,
  historical evidence/JSON and CI claims match the 631e0d8 baseline. Only this
  inventory and necessary backlog links belong to the checkpoint.

No functional suite was run; no database connection, provider capture, real data
inspection, scheduler execution, independent review or recovery rehearsal occurred.
No application code, migrations, production settings, database or mail are changed;
no push, merge, deployment or actual restore is authorized or performed.
