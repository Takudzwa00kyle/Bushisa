# Data inventory and retention matrix

Document date: 2026-10-15. Product reviewer: Takudzwa Hondova.
Acceptance provenance/time and the single conflict/unknown table are in the
[Q01-Q48 reconciliation](owner-policy-reconciliation-2026-10-15.md).

**A** = directly selected product rule; **P** = proposed value, not adopted;
**G** = qualified review/real-world evidence still required. A does not mean
implemented, tested, deployed or fully accepted under D05. Named accountable
operators remain **UNASSIGNED**; role labels below describe responsibilities,
not appointments. No live store was inspected or purged.

This matrix supersedes conflicting legacy retention suggestions, including
`docs/data_retention_policy.md`'s former indefinite consent, both-users-delete
chat and default anonymous-confession rules. It does not rewrite earlier
approvals or certify a legal retention requirement.

## Source-to-schema inventory

| Category | Current repository mapping | Allowed purpose / field review and gap |
|---|---|---|
| Identity and eligibility | `users.id`, `nust_student_id`, `email`, `is_verified`; operational account state, approval times and session version; `profiles.date_of_birth` | Service/eligibility only; normalized student identifiers, necessary DOB and HMACs are private/potentially personal. D01 alumni/inactive eligibility and D02 rejected-attempt block persist. No document/health/location-history collection is approved. |
| Profile/preferences | `profiles.display_name`, `bio`, `gender`, `faculty`, `year_of_study`, `profile_photo_path`, `anon_handle`; `preferences` age/gender/faculty fields; `profile_images` added by operational schema | Q13 candidate allowlist needs field-by-field purpose/consent review. Existing `anon_handle` and `preferred_faculty` need explicit mapping/justification; candidate omission is not permission to silently break approved features. Photo objects/derivatives need a real object index. |
| Compatibility | `find_match_answers`, questionnaire definitions and `php/matching/scoring.php` | Current disclosed answers only. Answers/preferences can be sensitive; no inferred traits, old-answer archive, identifiable training or new profiling approval. |
| Relationship and restrictions | `swipes`, `matches`, `message_requests`, `blocks`, `rate_limits`, `protective_replay_events` | Separate active likes/match, approved chat, own unmatch/undo, directional decline and lifetime pair state. Current schema/routes do not establish the whole approved model; never delete enforcement state merely to purge ordinary request rows. |
| Chat and projections | `messages.body`, `sent_at`, `unsent_at`, ordinary-body purge fields; notifications; upload/cache paths | Separate ordinary text/media/preview from an already-preserved existing-report copy. Current placeholder and active-match guard require alignment. New/old conversation segments and physical media indexes are missing evidence. |
| Confessions | `confessions`, `confession_references`, deleted/purge fields, author linkage | Erase public bodies, generated previews/excerpts and identifying reproductions before losing the ability to find them; private case snapshots are separate. |
| Safety | `reports`, `moderation_cases`, `moderation_case_assignments`, `moderation_evidence`, `evidence_access_audit` | Minimized case-scoped intake/evidence, human findings, independent appeal, hold/access/expiry. Existing helpers/tables are not staffed UI, accepted sanctions, complete audit or final-resolution proof. |
| Signup and mail | `student_email_approval_codes`, `mail_outbox`; `php/signup_verification.php`, `php/mail_delivery.php` | Keyed code verifier; encrypted redeemable queued payload is a separate secret-bearing copy. Dispatcher is signup-specific. No reset schema/flow or new resend-budget evidence is certified. |
| Consent | `consent_records` currently has user/type/granted/revoked times | Add purpose/version/method/event/withdrawal proof mapping; existing helper alone does not supply complete Q21 receipts, approved notice or fresh location consent. |
| Sessions/security/logs | `users.password_hash`, session version; `php/session_manager.php`; `audit_log.ip_address`, `rate_limits`, app/provider logs | Current PHP session calls use Strict cookies and refresh activity on protected requests; deployed handler and all destinations VERIFY. Q40's DB store/timeouts/Lax are proposals. Raw IP and free-text logs need inventory/redaction. |
| Geography | Legacy `profiles.campus_zone` is not an active approved location catalogue; current matcher has no geographic join | Future current coarse area/version/confirmation/expiry/consent only. No actual catalogue, coordinates, cohort counts, device conversion or ranking certification is supplied. |
| Recovery/external copies | `cleanup_outbox`, `protective_replay_events`, `restore_replay_runs`; Azure DB/media/telemetry, exports, caches, stopped/restored servers | An application queue row is not object-erasure proof. Independent continuity, every recoverable copy and provider-controlled expiry must be captured before release/restore. |

## Retention and access

The backup column uses **R**: the independently computed recovery bound below,
with replay before restored access. It never means the live deadline can be
postponed until backup expiry. Holds cover authorized evidence only; ordinary
account/chat data does not become evidence by being held indiscriminately.

| Category / source / status | Allowed fields and purpose | Live access cutoff | Live deletion deadline | Original trigger | Evidence exception | Backup / recovery bound | Accountable role (name pending) |
|---|---|---|---|---|---|---|---|
| Pending-email signup Q11 A/G | Pending identity/credentials needed for bounded signup | Creation +72h | Expiry +24h | Account creation; resends never renew | Only separately authorized case material | R; cancel stale auth mail | Engineering + cleanup |
| Approved-email unfinished onboarding Q11 A/G | Minimal unfinished account/onboarding state | First email approval +7d | Expiry +24h | First successful approval, not last visit | Scoped evidence separately | R | Engineering + cleanup |
| Rejected underage attempt Q12 A/G | Rejection outcome, not an activatable account | Immediately at rejection; permanently non-activatable attempt | Raw DOB/profile/photos/answers within 24h | Rejection | Existing scoped report evidence only | R; no restored activation | Eligibility + privacy |
| Underage anti-replay record Q12 P/G | Rejection ID; necessary student/email HMACs; reason; UTC times; version | Eligibility/safety only | Proposed rejection +365d | Rejection | No full account or child-history archive | R; key custody and later-adult policy REVIEW GATE | Eligibility + privacy |
| Active profile/answers Q13 A purpose; P field allowlist; G | Disclosed service fields only, private identifiers/DOB; current answers | Eligibility/consent/deletion rules immediately | While purpose/account valid; replaced values within 24h | Replacement or Q14 deletion | Relevant case copy separate | R; no restored replaced history | Product + privacy + engineering |
| Individual recommendation impressions Q13 A/G | Minimal service exposure record; no unrelated profiling | Authorized service only | Event +30d | Impression event | Scoped case record only if justified | R | Matching engineering + privacy |
| Unmatched non-safety like history Q13 A/G | Minimal ordinary history; active pair likes cleared on unmatch | Active-like use ends on unmatch | Event +90d maximum; earlier Q14 erasure wins | Original like/history event, never unmatch/read reset | Lifetime-policy ledger separate | R | Matching engineering |
| Account inactivity Q13 A/G | Last intentional authenticated foreground activity | Q14 denial when inactivity deletion starts | Q14 deadlines at last activity +365d | Intentional activity, not polling/webhooks/admin/jobs | Scoped evidence separate | R | Support + cleanup; warning offset CONFIRM |
| Deleted ordinary account/relationships Q14 A/G | No ordinary continued use; limited completion metadata | Immediately at verified deletion | Within 7d; media 24h | Verified deletion event | Separately authorized evidence/consent/enforcement only | R; conditional conservative 49d example below | Operations + cleanup + engineering |
| Profile/replaced/deleted media Q13/Q14/Q24 A/G | Originals/derivatives/thumbnail index for service/erasure | Immediately on withdrawal/replacement/deletion as applicable | Within 24h | Replacement/deletion event | Authorized specified evidence only | Proposed media copies 7d; R across all copies | Media/cleanup operations |
| Public confession and copied projections Q15 A/G | No default continued anonymous publication | Immediately on author deletion/deletion request | Content within 7d; media 24h | Content/account deletion; earlier deadline wins | Private pre-preserved case copy | R; include references/previews/quoted content | Content + cleanup + privacy |
| Active ordinary message history Q16 P/G | Ordinary participant conversation | Existing authorization and earlier denial rules | Proposed min(sent +365d, last human message +90d) | Each message sent / last human-sent message | Existing scoped evidence only | R; no restore of expired text | Chat engineering + privacy |
| Explicitly closed old history Q17 A/G | Read-only for original authorized participants | Earlier independent denial/expiry, otherwise end +7d | End +7d +24h, i.e. at most 8d from ending | Explicit ending/revocation, never unmatch | Scoped case copies separate | R; restart cannot revive old segment | Chat + cleanup |
| Unsent message text Q10 + D04 15-17 A/G | None in ordinary views; no placeholder | During accepted unsend operation | Ordinary original text erased in that operation | Accepted unsend within approved window | Only text already preserved for existing report; no new capture on unsend | R; no immediate-backup-erasure claim | Chat engineering |
| Unsent attachments/previews/notifications Q10 A/G | No ordinary access; minimal cleanup references | Immediate app/media denial; in-app notice removed within 60s | Live originals/derivatives within 24h | Accepted unsend | Already-authorized scoped report copy/hold only | R; residual bearer URL revocation VERIFY | Media/chat + cleanup |
| Report evidence Q18 A/G | Reported item/relevant attachments/IDs/times/safety context; necessary +/-10 messages | Assigned case access only; no fresh content access on report | Final resolution +180d | Final resolution after appeal window/timely appeal | Scoped Q20 hold only | R; final clock also applied during replay | Safety + privacy |
| Unrelated/accidental case evidence Q18 A/G | Minimum needed to resolve the allegation | Restricted case access | Within 30d after final resolution | Final resolution, not initial appealable finding | Justified minimal separate audit/abuse record, not whole evidence | R | Safety + privacy |
| Structured case decisions Q18 A/G | Reason/sanction/notice/appeal dates/reviewer IDs, no evidence content | Authorized case/appeal use only | Final resolution +365d | Final resolution | Q20 specified lawful hold only | R | Safety lead + privacy |
| Permanent-ban enforcement Q18 conditional G | Minimal case/sanction/HMAC/start/review; no indefinite evidence text | Assigned enforcement purpose only | Within 30d after overturn/lawful enforcement end; annual necessity review while active | Effective enforcement/end, with explicit privacy approval | Not an unlimited identity blacklist | Keep needed live restriction and R; never blindly delete at day 49 | Safety + privacy approver |
| Evidence access audit Q19 A/G | Actor/case/item/action/time/purpose/outcome, content-free | Scoped audit authorization | Event +365d | Each access/assignment/export/change/delete event | A specific verified hold only | R | Security/privacy |
| Evidence export Q19 A/G | Scoped approved encrypted export, named recipient | At approval expiry, max 24h | Same 24h bound | Export creation; no renewal on download | New exceptional disclosure needs separate approval, not silent extension | R; no ordinary moderator bulk copy | Privacy/security approver |
| Evidence holds Q20 A/G | Case/items/authority/reason/start/end/review; no duplicate evidence archive | Existing case access only | Urgent hold <=24h; ordinary authorization <=30d with review <=14d; after release overdue items purge <=24h | Hold authorization; original ordinary expiry retained | Renew only by fresh justified authority; verified legal instructions separate | R includes held-then-deleted protection | Safety lead + privacy jointly |
| Consent receipts Q21 A subject to G | Purpose/version/choice/time/method/event and protected subject ref | Authorized proof only; withdrawal stops processing immediately | Applicable account deletion/processing end +365d after per-purpose review | Declared processing/account terminal event | Narrow qualified exception only | R; active withdrawal state persists as necessary | Privacy + consent engineering |
| Signup challenge Q22 P settings; A/G lifecycle | Keyed HMAC/current generation/purpose/expiry/attempts | Use/expiry/supersession/exhaustion immediately | Preserve current immediate verifier erasure; any remaining attempt details <=24h after use/expiry | Original issue/use/expiry, not resend/read | No evidence exception retaining redeemable code | Minimize backup exposure; expire on restore | Auth engineering + security |
| Secret-bearing auth mail Q22/Q47 A/G | Encrypted queued redeemable payload, dispatcher only | Useful dispatch deadline; routine exports forbidden | Immediate terminal erasure, operational ceiling 1h after terminal state | Delivery/failure/expiry/supersession | No retained secret archive | Inventory encrypted backups/keys; never send stale restored jobs | Mail operations + security |
| Code-free mail metadata Q22 A/G | Message ID/template/outcome/count/UTC times; address only while needed | Authorized diagnostics | Terminal outcome +7d | Terminal delivery outcome | Justified separate minimal security/case record only | R; provider retention separately VERIFY | Mail + privacy |
| Reset challenge Q47 A/G | Hash-only >=256-bit token challenge/purpose/generation/times | Issue +15m, or earlier use/supersession | Hash cleanup within 24h of use/expiry | Token issuance; never queue retry | No reusable-token evidence copy | Encrypted mail is a separate secret-bearing copy; R | Auth + security |
| Application/server/telemetry logs Q23 P/G | Fixed route/template/status/error/duration/environment/release/sanitized correlation | Restricted operations | Proposed event +30d; no archive extension | Event | Case content lives separately | Provider minima/copies VERIFY, R | Operations + privacy |
| Security abuse/auth logs Q23 P/G | Security event/protected actor/time/outcome; IP only if justified | Restricted security | Proposed raw IP <=7d, minimized event <=30d | Event | Specific necessary case record separate | R | Security + privacy |
| Privileged/moderator audits Q23 P; Q19 A for evidence access | Actor/resource/action/reason/time/outcome | Scoped audit only | Proposed event +365d; Q19 evidence access already selected | Event | Scoped verified hold | R | Security/privacy |
| Sliding-rate events Q23 P/G | Subject/pair/event time/type, no bodies | Enforcement only | Proposed longest applicable window end +24h | Original request/event window, never retry extension | Minimal separate safety ledger | R; not ordinary content history | Auth/chat engineering |
| Fixed probation state Q23 P retention; D04 fixed duration A | Trigger ref/start/end/status | Fixed 168h probation; independent restrictions still checked | Proposed probation end +30d | Immutable activation, no repeat-report extension | Separate justified case record | R; post-expiry renewal is Q01 P | Safety + auth engineering |
| Lifetime pair-policy ledger Q23 P retention; D04 limits A | Pair IDs/count/necessary decline/end/cutoff state only | Enforcement only | Proposed original pair lifetime, then bounded recovery after account deletion | Original pair existence / deletion; rematch does not reset counters | Not permission to retain messages | R; active protective restrictions need independent continuity | Safety + chat engineering |
| Support / rights proof Q23 P/G | Minimal category/contact/correspondence or verified request/decision/times | Staffed authorized workflow | Proposed support closure +90d; rights closure +365d; attachments earlier | Closure; reads do not renew | Scoped lawful exception, not whole user export | R | Support + privacy |
| User-data export Q27 A/G | Redacted scoped encrypted file | Export creation +24h | Stored file also expires at +24h | Creation, not last download | No raw DB dump | R; track authorized recipient/copy bounds | Privacy/support |
| Current coarse location Q30-32 A/G | Area ID/version/confirmation/expiry/consent only | Immediate withdrawal or confirmation +7d | Within 24h of withdrawal/expiry; old value within 24h of replacement | Explicit confirmation/withdrawal, never passive read | No movement-history evidence exception by default | R; consent proof contains no area/coordinates | Location engineering + privacy |
| Daily non-identifying aggregates Q13 A/G; Q23 same proposal | Suppressed counts with no recoverable user/pair key | No individual access | Aggregation event +13 calendar months | Original aggregate period/event | Must establish non-identification, not simply HMAC IDs | Copies retain same expiry | Analytics + privacy |
| Routine DB copies Q26 P/G | Restricted encrypted recovery material | Isolation; no ordinary browsing | Proposed maximum 35d, no extra archive | Original backup creation; copies inherit earlier source expiry | Not an evidence store | Latest actual copy expiry determines R | Backup operations |
| Private media versions Q26 P/G | Recovery-only objects/versions | Denied ordinary access when original revoked | Proposed maximum 7d if recovery feasible | Original copy creation; earlier source expiry wins | Separately preserved evidence has own record | R over DB/media/exports, not media alone | Backup/media operations |
| Troubleshooting exports Q26 P/G | Approved encrypted minimum, controlled store | Proposed <=24h | Same <=24h | Creation; no personal downloads or copy renewal | Separate exceptional approval required | Register every copy; R | Engineering + privacy |
| Isolated rehearsal copies Q26 A/G | Synthetic isolated recovery state | Never production users/mail/dependencies | Within 72h of creation or earlier source deadline | Rehearsal creation, not restart | No indefinite rehearsal archive | Include copies in R; actual restore NOT RUN | Engineering + backup operations |
| Independent recovery instruction Q26 A rule; P copy assumptions; G | Protected refs/action/version/effective time/verified purge/retain-until; no content/coords/secrets | Dedicated replay/enforcement use | After latest recoverable-copy expiry +7d AND no still-needed protective state | Original protective event plus verified copy/purge bounds | Keep necessary active restriction, not entire account | Outside restore set; fail closed if unavailable/incomplete | Backup + security + privacy |

## Clock and boundary contract for later implementation

1. Record immutable server UTC instants. New operations at `t >= expires_at`
   fail. New rolling-window enforcement counts events in `(t - window, t]`
   under the same atomic allowance/insert transaction, including concurrent
   same-time events; events at the lower edge have aged out. This is an
   implementation contract to verify, not permission to alter approved amounts.
2. Preserve existing D04 clocks: pending expiry is creation +7 days;
   cancelled retry starts at cancellation, expired retry at stored `expires_at`,
   both +12 hours; ended-chat restart is end/revocation +12 hours; probation is
   activation +168 hours with no extension/early restoration. Verify the exact
   previously selected unsend boundary; do not infer a changed 15-minute edge.
3. Seven days means 168 elapsed hours from the event, not seven midnight
   resets. One year is 365 elapsed days. The 13-month aggregate rule alone uses
   calendar months. Pin a UTC end-of-month/leap-day arithmetic convention in
   the later implementation evidence; no other category gains calendar resets.
4. Expiry/access denial is independent of whether an hourly cleanup job ran.
   Jobs must meet the explicit physical ceiling, not add another hourly grace.
   A text erasure promised inside an accepted unsend operation cannot wait
   24 hours merely because media has a separate deadline.
5. Retain original ordinary expiry during a scoped hold. Review/release never
   re-dates it; if overdue, purge within 24 hours after release. Reopening on
   material evidence records a new justified case event and affected scope,
   never recovers already-erased content or quietly extends unrelated records.
6. Apply the earliest applicable ordinary deadline and current access denial.
   Q16's active-chat schedule remains proposed; Q17's accepted old-segment clock
   cannot be renewed by restart or delete messages belonging to a new segment.

## Recovery horizon: conditional 49-day example

Let `D` be deletion, `P` verified live purge, `C_i` the last expiry of each
recoverable copy, and `M = 7 days` the selected safety margin. The instruction
must survive at least `max(C_i) + M`, and longer if an active restriction still
needs continuity. Persist the bound and proof; never recompute it from reads.

Under the **proposed**, not verified, maximum DB-copy settings:

| Event | Conservative latest time from D | Meaning |
|---|---|---|
| Access denied | D | No claim that all copies are physically erased. |
| Live ordinary data purged | D +7d | Q14 product deadline; actual verified purge must be recorded. |
| Last content-bearing backup expires | D +7d +35d = D +42d | Assumes a content-bearing backup could exist just before the latest purge and a 35-day maximum copy lifetime. |
| Recovery instruction earliest conservative purge eligibility | D +7d +35d +7d = D +49d | Conditional floor for this worst-case example, not a universal timer or automatic deletion permission. |

If purge is late, a restore creates another recoverable server, an export/copy
survives longer or a restriction is still active, compute the later bound and
record the incident. A copied backup does not acquire a new 35-day life. Unknown
copy inventories block restore/release. Media's proposed 7-day copies, export
24-hour bounds and rehearsal 72-hour bounds cannot shorten a longer DB bound.

Replay the latest independent deletion/block/consent state and original expiry
before app reads or mail dispatch. Apply content deletion, stale queue cleanup,
media access invalidation and current safety constraints, not just login flags.
Migration 005 and synthetic replay tests do not prove this actual isolated
restore; it remains **NOT RUN**.

## Evidence and acceptance still required

- An environment-specific field/object/cache/log/export/backup inventory, real
  provider retention and privileged-access/key inventory, without secret values.
- Named operations executor, engineering repair owner, privacy sampler and
  safety/backup reviewers, overdue-at-deadline alerts and 24-hour escalation.
- Bounded worker/hold/clock/media/restore tests later, using guarded disposable
  systems; erasure watermarks and sampled object absence, not just queued work.
- Actual isolated restore witness evidence, replay continuity and qualified
  H03/H08/H13 review before D05/D12/recovery release acceptance.

No DB/browser suites were run for this documentation change. Historical
candidate CI retains the exact attribution in the owner packet.
