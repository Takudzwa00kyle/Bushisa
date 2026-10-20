# Bushisa retention policy: owner selections and open review

Updated: 2026-10-15. Reviewer: Takudzwa Hondova.

**D05 is pending overall.** This is an internal policy record, not a reviewed
public notice or a claim of implemented/deployed erasure. Actual acceptance
UTC/Harare date/time is CONFIRM; the supplied timezone is Africa/Harare. The
[decision register](remediation/decisions.md) and
[Q01-Q48 acceptance supplement](remediation/owner-policy-reconciliation-2026-10-15.md)
define exactly which choices were adopted and which remain proposed/conflicted.
Qualified privacy/security/safety review and named operational acceptance stay open.

## Current data contract

The authoritative compact schedule is the
[data inventory and retention matrix](remediation/data-inventory-retention-matrix.md).
It maps actual schema categories to purpose/allowed fields, access cutoff,
live deletion deadline, immutable trigger, evidence exception, backup/recovery
bound and accountable role. A role is not a named appointment; actual names
remain UNASSIGNED. No live database or external store was accessed in this task.

Directly selected product rules, still requiring implementation and review:

- Expire pending-email signup at creation +72 hours; unfinished onboarding at
  first email approval +7 days. Deny at expiry and erase live data within 24h.
- Keep a rejected underage attempt permanently non-activatable and erase its
  entered profile/photos/answers/raw DOB within 24h. The proposed 365-day
  HMAC anti-replay record and later fresh adult application need review.
- Keep only disclosed service data; replaced values erase within 24h.
  Individual impressions expire in 30d; unmatched non-safety like history in
  90d; non-identifying suppressed aggregates in 13 calendar months. Inactivity
  deletion starts at one year/365 elapsed days after intentional foreground
  activity, not polling; the exact 11-month warning offset remains CONFIRM.
- Either account's deletion immediately denies affected ordinary account/chat/
  media access and revokes sessions/challenges; ordinary live erasure is within
  7d and media within 24h. No public grace period or both-users-delete rule.
- Remove deleted authors' public confessions, attribution, generated excerpts
  and identifying reproductions immediately; content purge within 7d/media
  24h. No default continued anonymous publication.
- Explicitly ended approved chat has at most 7d authorized read-only history,
  then access denial and live purge within 24h. Unmatching alone preserves
  approved chat and starts no closed-history clock. Restart cannot renew the
  old segment or recover erased content.
- Unsend keeps its earlier approved window, original-text deletion and fully
  disappearing entry. Immediately deny app/media access and erase ordinary
  text in the accepted operation; in-app notification removal <=60s, live
  attachment/preview purge <=24h. Only an already-preserved existing-report
  copy falls within the approved original-text exception.
- Evidence content expires 180d after final resolution, or 30d for wholly
  unrelated/clearly accidental cases; content-free decisions expire in 365d.
  Final resolution includes the appeal window/timely appeal. Assigned-case
  access, 365d content-free access audit and 24h approved exports are required.
- Holds are item-scoped: urgent reviewer hold <=24h, joint ordinary authority
  <=30d with review <=14d; overdue evidence purges <=24h after release/expiry.
  They never retain an entire ordinary account/chat or reset ordinary clocks.
- Minimal consent receipts are selected for the necessary processing/account
  period plus 365d after the applicable terminal event, subject to per-purpose
  privacy review. No indefinite or presumed six-year legal requirement.
- Cleanup runs at least hourly with immediate denial independent of jobs;
  terminal expired/cancelled/declined ordinary request rows purge <=30d after
  terminal event, retaining only required minimal safety/lifetime state.

## Values still proposed

Q16's linked active-message maximum of one year/365d plus 90d human-message
inactivity remains proposed. Q23's metadata retention table remains proposed
except values separately adopted in other answers. Q26's routine DB copies
35d, media copies 7d and troubleshooting exports 24h remain proposed settings,
not current configuration. Q12 HMAC retention, candidate account allowlist and
long-lived ban suppression require the stated privacy reviews.

## Clocks and recoverable copies

Separate access denial, live erasure and protected backup expiry. New expiry
timestamps use server UTC/elapsed durations and are immutable; reads, edits,
exports, jobs, retry, migration/replay and routine hold review do not renew them.
Preserve earlier request/unsend clocks exactly. HMAC identifiers remain
potentially personal data with protected keys, access, retention and review.

The independent recovery instruction survives until the latest actual
recoverable-copy expiry +7d and any longer needed active restriction. With
proposed 7d live erasure and 35d DB-copy lifetime, the conservative example is
**7 +35 +7 =49d from deletion**. Compute from verified purge/copies; never
delete universally at day 49. Unknown copies block restore/release. A backup
copy does not gain a fresh lifetime through copying or restoration.

Actual provider expiry, stopped/restored servers, media versions, caches and
exports need inventory. Replay current deletion/block/consent state and original
expiry before restored reads/mail. Actual isolated restore is NOT RUN and
migration 005 is not certified production-applied.

## Acceptance and public wording

The former 30d account, both-users-delete messages, anonymous-post default,
24-month case, indefinite-consent/legal-requirement and assumed 30d-backup
statements are superseded as internal policy guidance. Their existence did not
establish qualified legal approval or verified technical capability.

See the [bounded backlog](remediation/backlog.md#q01-q48-implementation-backlog)
and [read-only configuration checklist](remediation/configuration-impact-checklist-2026-10-15.md).
Public deadlines/notices require named owners, overdue alerts, demonstrated
capacity and qualified review of actual processing/rights/foreign flows. No
legal sign-off, public-notice publication or operational change is made here.
