# Legacy continuation and writer cutover: local preparation

Prepared from `faa5b7a`, accepted observed schema 001–004 and owner captures
01–09. This is a non-enabling engineering worksheet for the controlled direct
release. No production snapshot, backfill, import or epoch activation occurred.
Reviewer/operator names remain PENDING. Local preparation continues independently.
The [L02b contract](l02b-legacy-history-import-contract.md) is authoritative for
source provenance, completeness/conflict handling and B/W/H/C boundaries.

## Cohort handling and prohibited shortcuts

| Cohort | Current candidate behavior / local evidence | Required continuation before full release |
|---|---|---|
| Verified active legacy account | Upgrade preserves eligibility/status/password/session version; no fabricated signup generation. Earlier OS07/OS18–19 remain historical evidence. | Complete actual runtime-grant and protected-route coverage; session/storage acceptance remains separate. No forced re-verification or invented old creation event. |
| Legacy pending-email/onboarding attempt with missing anchors | RP-L01 retains the recorded creation value, records anchor uncertainty and leaves generation NULL. `bushisa_issue_signup_code` requires `expiry.state=live`, so it does not silently promote this cohort to a new tracked attempt. | A bounded continuation design must establish acceptable provenance for each required anchor or a separately reviewed explicit replacement/recovery path. Keep uncertain expiry unresolved; do not use migration/intake time, erase automatically, extend an old clock or inherit verification in a replacement. |
| Old queued signup envelope without tracked job/generation | Historical OS12 discarded before carrier; the candidate does not manufacture Q11 references. New strict-role tests cover genuinely tracked queue cancellation and fake acceptance separately. | Explicitly account for old/in-flight work and tell affected users safely what recovery route exists. Do not declare working SMTP sufficient or resend old codes. Support coverage is still unassigned. |
| Existing incomplete pair | Migration 007 records `legacy_incomplete`; missing requests/events are not zeros or restored permission. Historical OS09/OS16 remain fail-closed evidence. | Authentic evidence/import authority and enabling contract remain required before any legacy completeness change. Synthetic L02 imports do not supply that authority. |
| Existing approved chat after unmatch | Historical OS10 and approved Decision 13 preserve chat independently of match restoration. | Never use a cutover/unmatch event to end approved chat, or clear blocks/bans/ending cutoffs/lifetime budgets. |
| New accounts/pair after a future cutover | RP-L02 confirms migration leaves epoch inactive and account genesis empty. Without observed writer coverage, new requests needing complete history can remain blocked too. | Verify every actual writer and positive boundary/genesis evidence before genuine post-cutover certification. Schema installation alone cannot establish W. |

No policy decision here permits reducing D03's full scope to avoid these gaps.
No owner reapproval is needed for settled budgets, clocks, consent, restart or
unblock rules. Authentic source evidence is different from a new policy choice.

## Writer sheet to complete before a real boundary

These rows retain the L02b IDs. Local code inspection identifies paths and
required controls; **all actual stop/drain/revision/boundary fields are UNVERIFIED**.

| ID | Writer group / local entry point evidence | Boundary prerequisite |
|---|---|---|
| W01 | Match list/restoration; `api/matches.php` invokes `bushisa_pair_match_list`, restoration uses the pair helper. | Account for read/lazy mutations, both participants' current consent and restoration operation versions. |
| W02 | Swipe/matching; `api/swipe.php` invokes contact check, pair ensure/operation/match helpers. | No retired matcher or direct match writer may bypass ledger/consent state. |
| W03 | Request/chat/restart/lazy expiry; `api/message_requests.php` invokes `bushisa_pair_request`. | Drain committed/in-flight requests and capture lifetime/decline/end counters without resetting them. |
| W04 | Reports, blocks and versioned clearance. | Preserve once-only report effects, independent restrictions and creator-only actual unblock; reviewer clearance is not an unblock event. |
| W05 | Messages, unsend, preview/questionnaire-derived relationships. | Account for server-authorized writes and original expiry obligations; no erased-text recreation. |
| W06 | Account deletion, consent/replay. | Preserve latest acknowledged denial/recovery state independently of the restored snapshot. |
| W07 | Admin/moderator/direct support paths. | Inventory accepted identities and actual tools; app ingress closure alone does not stop them. |
| W08 | Registration, verification, onboarding, authentication and genesis. Include L06 reset/session-version writes. | No account may acquire fabricated generation/anchors during migration; drain verification/reset consumption and invalidate stale authority. |
| W09 | Profiles/preferences/consent/session helpers. | Capture auth versions and session/storage writer coverage; a Redis extension or file path is not coverage proof. |
| W10 | Installed mail job plus Q11/Q22/erasure/SC/evidence consumers. | Locate actual scheduled entry points and stop/drain them. Only accepted bounded mail schedule may later resume; other consumers remain gated. |
| W11 | Migrations/runtime DDL helpers. | Exact pending versions 005–016, controlled migration principal/definers and no runtime DDL. No marker deletion or forced old-script replay. |
| W12 | Restore, direct SQL, imports, support/test tools and retired workers. | Restrict access and attest no hidden writer. Synthetic tooling is not a production enabling adapter. |

For **each** row fill: actual artifact/installed path and revision/digest;
primary/backup; stop mechanism; last acknowledged action/commit; in-flight
disposition; timestamp plus ordering/watermark evidence; first reliable event;
reviewer; whether it can write outside the app; unresolved gap. Do not substitute
a declaration or timestamp for causal/transaction evidence.

## Executable local boundary protocol; real execution still gated

1. Select quiesced capture or a demonstrably complete snapshot/catch-up method
   under L02b. The accepted metadata does not prove a continuous change stream;
   do not assume `log_bin=1` grants usable/authentic CDC or complete history.
2. Rehearse an ingress/write fence that leaves the migration execution channel
   usable. Stopping the entire app may also remove Application SSH; a running
   app is not quiescent merely because a maintenance page is shown. No existing
   global maintenance setting or ingress exclusion is assumed here.
3. Stop/drain every writer row, including the actual installed old WebJob.
   Account for transactions begun before B but committed after it and SMTP
   acceptance whose client result was lost. Record B with consistent all-table
   scope, schema and custody. Do not execute queued mail to discover its contents.
4. Preserve actual app/worker/configuration/media/protective state privately;
   verify recovery coverage before upgrading. Candidate plus old wrapper is an
   explicitly reproduced incompatibility, not an allowed intermediate serving state.
5. Upgrade exact reviewed versions and deploy/verify every writer while closed.
   Record the first proven reliable-history boundary W and complete suffix H;
   verify current locked destination/version C before any separately authorized
   reconciliation. Unknown history remains incomplete. No production activation
   or history-enabling tool is implemented by this worksheet.
6. Verify restrictions, session versions, approved-chat survival, all D03
   journeys and bounded worker behavior under accepted principals. Only an
   independently supportable boundary and replay/compatibility evidence permits
   reopening. Named operators/reviewers accept their actual responsibilities;
   their pending names are not replaced by synthetic test identities.

## Next bounded engineering work

Extend the runtime grant contract through actual signup/mail, discovery,
confession, report/moderation and relationship routes, separating fixture setup
from execution. The current RP runtime profile deliberately lacks signup mail
writes and is **not deployable as the complete application role**. Add exact
allow/deny cases rather than inheritance from the worker or schema-wide grants.

For legacy continuation, prepare a non-enabling evidence/transition contract for
pending attempts and genuine post-cutover genesis, with examples for missing,
contradictory and trusted anchors and current restrictions. An enabling adapter
needs its own bounded reviewed implementation and authentic boundary evidence;
neither a reviewer appointment nor owner approval can invent that evidence.
Continue the [existing-resource recovery preparation](existing-resource-recovery-options-2026-10-22.md)
in parallel. Actual cutover and recovery acceptance remain NOT RUN.
