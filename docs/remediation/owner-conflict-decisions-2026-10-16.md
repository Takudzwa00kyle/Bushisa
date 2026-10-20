# Owner decisions: restoration consent and temporary unset Reply-To

Documentation date: 2026-10-16, Africa/Harare. Reviewer: **Takudzwa Hondova**.
Baseline: `93314b2bdf874bb923aea9c63f891b279c980f0d`, existing
`codex/prompt03-owner-decisions-20261014` branch, initially clean.

Source: the owner's answers to the two explicit conflict questions:

1. `replace` — answering whether to keep own-undo restoration or replace it
   with fresh agreement from both participants.
2. `permit temporary unset until monitored inbox is verified.`

These are express product approvals of those two amendments only. They do not
adopt the remaining proposed values, appoint operators, approve a particular
support address or authorize application/configuration changes.

**Actual acceptance timestamp: CONFIRM**, in UTC and Africa/Harare. The message
metadata available here exposes no sent timestamp. First recording clock:
2026-10-15T22:38:15Z / 2026-10-16 00:38:15 Africa/Harare (UTC+02:00).
That is a recording timestamp, not an invented approval instant; earlier
acceptance dates/timestamps are not rewritten or backdated.

## Supersession record

| Entry | Earlier approved scope | Owner's new approved scope | Disposition |
|---|---|---|---|
| D04 Decision 27: match-restoration consent | Decision 20 allowed restoration without the other participant's renewed agreement. Decisions 21-22 controlled each person's own unmatch/undo. | Restoring an existing unmatched relationship requires fresh affirmative agreement from both participants; either may refuse. | The no-renewed-agreement clause of Decision 20 is **superseded**. Own-unmatch control and both own undos where both unmatched remain; no one undoes the other's unmatch. Q08's renewed-consent conflict is **resolved by explicit replacement**, not left pending. |
| D11 Reply-To amendment | Matching Reply-To was required, without the proposed temporary-unset exception. | Reply-To may remain temporarily unset until a real monitored receiving inbox is verified. | The temporary exception is **approved**; the Q45 fallback conflict is resolved. The monitored-inbox/address/owner verification gates remain. |

## D04 Decision 27 boundaries

- An immediate restoration attempt is allowed, but restoration cannot finish
  without both participants' current agreement. Preserve the separately selected
  maximum of one successful pair restoration per rolling 24 hours; an attempt
  or one person's consent is not a successful restoration.
- Only a participant may undo their own unmatch. If Alice alone unmatched,
  Alice's undo also needs Brian's fresh agreement; Brian's agreement is not
  permission to undo Alice's action. If both unmatched, both own undos and
  both participants' fresh agreement are required. The interface and exact
  consent-event representation remain L02 engineering work, not a new policy
  appointment, expiry period or invented workflow.
- Refusal or absent agreement leaves the pair unmatched. An old like, existing
  chat approval, passive read or elapsed time is not fresh restoration agreement.
- Restoration of the existing relationship remains an undo, **not** a genuinely
  recreated match, even with fresh agreement. It does not clear a decline
  restriction. Decision 5's decline-only exemption for genuinely new matches
  remains exactly bounded; budgets, probation, active blocks/account/consent
  restrictions and the second-ending cutoff never reset through restoration.
- Decision 13 still preserves an already-approved chat after unmatch. Fresh
  match-restoration agreement is not required merely to continue that chat,
  and refusing restoration does not itself end the chat. Separate chat-ending,
  account and safety restrictions remain applicable.
- Decision 19's still-pending request remains pending until its recipient
  approves while matched and before original expiry. Mutual restoration consent
  is not chat-request approval, does not revive expiry and does not reset any
  request or explicitly ended-chat restart clock.
- Ordinary/combined blocker-initiated unblock chat effects under Decisions
  10-12 and 25-26 remain unchanged; this amendment does not insert a new
  match-consent prerequisite into those separately approved chat-grant flows.

## D11 temporary-unset boundaries

- Approved From remains `DoNotReply@bushisa.me`, display name `Bushisa`, with
  selected transactional/reset reuse and the existing recorded ACS resources.
- Until a real receiving inbox is verified and monitored, an unset Reply-To
  is permitted. Do not substitute or advertise an unverified receiving address.
  After verification, the approved matching receiving address and monitored
  workflow can be configured through a separately authorized change.
- `support@bushisa.me`, its provider, named operator/backup and coverage remain
  proposals/unverified facts. No mailbox or role is created by this acceptance.
- Verify external receipt, monitoring and actual Reply-To/header behavior in
  later authorized acceptance. Existing transport should omit the header when
  unset; this task supplies no new runtime or live-delivery evidence.
- Remaining support/privacy contact, H06 delivery, staffing, privacy/security
  and release gates are not waived. Reusing the sender does not itself require
  new domain DNS; no DNS, mail, billing or production setting is changed here.

## Implementation and release status

Both policy conflicts are resolved to the scope above. **D04 remains pending
overall with partial product approvals; D11 remains partial.** D03's full scope,
all other recorded choices/proposals and qualified gates remain unchanged.

L02 must implement/currently verify fresh mutual restoration agreement, own-undo
authority, the rolling successful-restoration cap, unchanged decline/lifetime
history and independence from chat grants. L06/L07 must verify temporary-unset
header handling and later receiving-inbox/monitoring acceptance. L01a reporting
still requires neither restoring a match nor sending email.

This is documentation only. No application, migration, database, real mail,
production configuration, secret, DNS, merge, push or deployment action occurs.
Migration 005 is not declared production-applied and isolated restore stays
NOT RUN. Existing candidate CI retains its exact historical head/run attribution;
it is not evidence for either newly accepted amendment.

See [decisions.md](decisions.md), the [updated answer record](owner-policy-reconciliation-2026-10-15.md),
[L01a contract](l01a-report-route-target-contract.md),
[backlog](backlog.md#q01-q48-implementation-backlog) and
[configuration checklist](configuration-impact-checklist-2026-10-15.md).

## Documentation validation and changed files

PASS: `git diff --check`; 88 local links, including seven heading references,
across the 13 affected Markdown files; paired code fences; new-file whitespace;
and all 48 numbered answer sections present once. Only Q08 and Q45's answer
sections changed. The original Decision 20 wording is retained with an explicit
supersession note, the register's historical body and candidate CI section are
unchanged, and every inventory/retention table row is unchanged. The conditional
recovery calculation remains 7 + 35 + 7 = 49 days. Application/configuration/
migration/test files have no changes; no database or browser suites were run.

Changed files:

- `docs/remediation/owner-conflict-decisions-2026-10-16.md` (this acceptance record)
- `docs/remediation/decisions.md`
- `docs/remediation/owner-policy-reconciliation-2026-10-15.md`
- `docs/remediation/prompt03-owner-decision-packet-2026-10-14.md`
- `docs/remediation/policy-proposals-d04-d05.md`
- `docs/remediation/Bushisa_D04_D05_Policy_Review_and_Revised_Proposal.md`
- `docs/remediation/backlog.md`
- `docs/remediation/l01a-report-route-target-contract.md`
- `docs/remediation/configuration-impact-checklist-2026-10-15.md`
- `docs/remediation/data-inventory-retention-matrix.md`
- `docs/remediation/README.md`
- `docs/azure-smtp-deployment.md`
- `docs/Bushisa_P0_to_P2_Azure_Remediation_Playbook.md`
