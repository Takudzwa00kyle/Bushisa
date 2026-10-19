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

Hosted candidate SHA, workflow URLs, job conclusions and draft-PR relationship
will be recorded after the authorized push. No hosted PASS is claimed yet.

## Remaining release evidence

Actual Android/iOS browsers, screen-reader/manual usability witnesses, inbox and
header/arrival acceptance, exact Azure runtime/engine/grants, staging migration
and worker compatibility, staff/monitored support, qualified security/privacy
review, and separate feature-enable approval remain open. Synthetic Chromium,
CI, and the deployed website do not establish those facts. Production scheduling,
independent recovery durability and witnessed restore remain separately gated.
