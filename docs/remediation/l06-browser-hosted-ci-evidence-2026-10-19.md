# L06 rendered acceptance and hosted candidate CI

Continued from `77510f9` on `codex/l06-password-reset-20261019`.
This checkpoint authorizes synthetic testing, feature push, a matching draft PR
and CI only. Password-reset issuance stays off by default. Explicit test-only
throttles do not approve the pending Q47 production values; Q40 and external
review/operational gates remain open.

## Local executed evidence

The [browser result](evidence/l06-browser-local-2026-10-19.json) records
**L06-B01–B06: 6 PASS** on Windows, Node 20.19.6, Playwright 1.64.0,
Chromium 156.0.8078.4, PHP 8.5.11 and disposable MySQL 8.0.45.
Command: `node node_modules/@playwright/test/cli.js test --config=playwright.l06.config.js`.
The [corrected HTTP result](evidence/l06-password-reset-local.json) is
**25 PASS**, including fresh/upgrade/repeat migrations and two distinct
processes/connections observed waiting concurrently with one consume winner.

| Case | Executed behavior |
|---|---|
| L06-B01 | Rendered known/unknown/pending-account requests return the same outcome; pending signup authorization and signup-purpose rows remain separate. |
| L06-B02 | Initial and same-document fragment autofill; visible URL clears; no-store/no-referrer; GET leaves password, generation and challenge state unchanged; no proof in local/session storage, cookie, IndexedDB names, cache names, console or resource URLs; only same-origin resources observed. |
| L06-B03 | No-JavaScript labelled manual form and keyboard navigation; CSRF, invalid and expired proof reject without changing account state. |
| L06-B04 | Explicit reset succeeds, with proof only in intended POST; no auto-login; two browser contexts have API access before reset, then API 401/page denial; regular new-password login; used-proof rejection; separate password-change notice. |
| L06-B05 | No-JavaScript repeated GET/HEAD scanner visits are inert; bare form/manual key and keyboard submit complete recovery and normal sign-in. |
| L06-B06 | All generated proofs absent from owned HTTP/PHP diagnostic files and synthetic audit/code-free mail records. |

Browser traces, screenshots and video are disabled for these cases because they
would capture DOM/POST proof. Fixture proof is piped in memory, not placed in CLI
arguments or artifacts. The result reporter records safe titles/status/version
metadata and redacts opaque proof from failure text. No real mail or production
session store is used. Test session files are isolated under `.tmp`.

The first rendered run found a real fragment defect: following a reset link when
the same page was already open did not rerun the initial script. The fix listens
for `hashchange` as well as initial load. Subsequent browser acceptance passed.

Historical evidence at `77510f9` is preserved in
[`l06-password-reset-local-77510f9.json`](evidence/l06-password-reset-local-77510f9.json).
Its AUTH-02 assertion used GET on a POST-only account endpoint and accepted 405;
it also did not establish pre-reset access after cookie regeneration. That run
is **not sufficient API-revocation evidence**. The corrected AUTH-00/AUTH-02
and B04 establish real pre-access followed by denial through the supported
notifications GET route. Earlier matrix claims outside L06 are unchanged.

## Candidate and workflow boundary

Before push, authenticated repository access and `origin/main` at
`0bf311fb6d6a298712fd4cd16d0722305516edd4` were inspected. Both local and remote
workflow inventories contain CI and `main_bushisa.yml`. Azure deployment triggers
only on a `main` push or explicit deployment-workflow dispatch; neither is used.
Feature pushes and draft PRs run CI. No deployment workflow is dispatched.
Existing draft PRs 3, 4 and 5 are unrelated and remain unchanged.

CI adds a dedicated `l06-recovery-acceptance` job with fresh disposable MySQL 8,
PHP 8.4, Node 20, fake mail and explicit synthetic limits. It executes the HTTP
suite plus all rendered cases; nonzero test exit fails the job. Existing lint,
signup/outbox/Q11/Q22/erasure/session/import and browser regressions remain in
their existing jobs. Only safe machine-readable L06 results are uploaded.

Draft [PR 6](https://github.com/Takudzwa00kyle/Bushisa/pull/6) uses this task's
feature branch against main. Initial candidate
`ed5d6450c41a6467d1e18b760eabf4719a60a763` had a failed
[push run 39216700556](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39216700556)
and failed [PR run 39216784253](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39216784253).
The PR's synthetic merge commit was `a0f93351c522a8e4c50b1e47a99914109ddfcd02`
over base `0bf311fb6d6a298712fd4cd16d0722305516edd4`.
All four jobs failed in those initial runs; the dedicated rendered L06 step
itself passed. These runs are not green candidate evidence.

| Hosted failure | Focused correction |
|---|---|
| L06 race fixture shared the preceding request's IP/cooldown on the faster Linux runner | Give the race its own synthetic subject through the existing fixture helper; preserve production throttles. |
| Minimal `php -n` removed `ctype`, causing the pure guard test to fail before its assertions | Load only that dependency while retaining disabled PDO and no database driver. |
| Old import upgrade test compared `SELECT *` before/after later additive migrations | Compare every original field and require the new legacy generation column to remain NULL. Linux disposable rerun: 100/100 PASS. |
| Old rendered fixtures expected superseded signup wording, omitted synthetic writer genesis, and used the former report payload | Align with current Q11 messaging and L01/L02 contracts; explicitly activate only the guarded synthetic cohort and supply current report idempotency/receipt semantics. |

Subsequent candidate results are recorded below when completed. Hosted failures
are retained rather than reclassified as passing local evidence.

Candidate `8b40e9e72c80cc54488c443a90ee81bf8c6aad70` passed both
`l06-recovery-acceptance` (25 HTTP checks plus six browser cases) and
`lint-and-unit` in the [push run 39218434123](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39218434123)
and [PR run 39218438791](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39218438791).
Its PR merge SHA was `d5c8cf3670d6eadd1966f7c7a7e8a14983e96bd0`.
The overall runs remained failed due to older pair/browser and mixed-import
fixtures. Follow-up aligns those tests with current pair CAS/idempotency,
reporter-bound read proofs, and uniform inaccessible-target 404 responses.
Conflicting transitions must reject stale intent; a later explicit refresh is
tested separately. A new guarded fixture establishes only positively new
synthetic pairs and refuses an existing/legacy relationship. No legacy history
is certified or production epoch activated.

The mixed-import HTTP fixture now includes only the current intake's three
INSERT stores and the precise L06 account-cancellation UPDATE columns/state
DELETE. Importer grants remain unchanged. This is a disposable test identity
alignment to concrete SQL statements, not a production grant change or broader
authority. Its missing-privilege/atomicity tests remain enabled.

The affected Linux mixed-writer regression subsequently passed **182/182
assertions, 17 race schedules and four genuine engine retry trials** at
`.tmp/l02e-20261019-075934-4d6032/execution.json`. This execution count is separate
from the unchanged LB acceptance matrix and its external provenance/cutover
gates. Current hosted L06 artifacts explicitly include source SHA, checkout SHA,
event and run ID, distinguishing feature-push code from the PR merge checkout.

The existing rendered deletion regression also exposed a lost protective-event
hook when `bushisa_close_match_between` began delegating to the pair ledger.
The bounded repair records content-free `match_closed` events with an explicit
`pair-restriction-v1` payload in the same transaction, only for changed
status/reason/actor. It preserves approved chat grants under independent block
and account checks, never calls unmatch an explicit chat end, and does not reset
retention clocks. Same-key retries remain covered by the pair operation receipt.
This restores a local record only; external publication/replay, durability and
witnessed restore are still unverified.

Candidate `a4ed86ff70d292cdf913aeb5d29368c2beb4c121` completed with three successful
jobs (`lint-and-unit`, `l06-recovery-acceptance`, `smtp-outbox-integration`) and
the remaining browser fixture failures in both the
[push run 39220214119](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39220214119)
and [PR run 39220220967](https://github.com/Takudzwa00kyle/Bushisa/actions/runs/39220220967).
PR merge SHA: `48a51e097b47f535f063d5f5d123c44e21fd4eb8`.
The remaining assertions used the legacy report table and expected ordinary
blocking to revoke an approved request row. They now check canonical intake
rows, preserved approved state plus independent suppression/denied sending,
and the restored protective events. The seven non-overlap local browser cases
passed across focused reruns (four signup/session, one report/chat, two
deletion/role cases); the whole hosted browser job is still pending the repair.

## Remaining release evidence

Actual Android/iOS browsers, screen-reader/manual usability witnesses, inbox and
header/arrival acceptance, exact Azure runtime/engine/grants, staging migration
and worker compatibility, staff/monitored support, qualified security/privacy
review, and separate feature-enable approval remain open. Synthetic Chromium,
CI, and the deployed website do not establish those facts. Production scheduling,
independent recovery durability and witnessed restore remain separately gated.
