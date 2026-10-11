# Bushisa Remediation Decision Register

Updated: 2026-10-10, Africa/Harare.

| ID | Status | Decision | Owner/date | Notes |
|---|---|---|---|---|
| D01 | Approved | Student email domain is `students.nust.ac.zw`. Student number format is `n` + three enrolment-year digits + five decimal digits + one lowercase letter, for example `n02531234f`. Alumni, graduated and inactive students are allowed. | Operator, 2026-10-09 | Implementation also requires the email local part to match the student number before sending an approval code. |
| D02 | Approved | Adult eligibility is `age >= 18`. Date of birth is collected after student-email approval. Underage accounts may be created but are permanently blocked from onboarding. | Operator, 2026-10-09 | Current implementation sets `account_status = underage_blocked` after an underage DOB attempt. |
| D03 | Pending | Launch product scope. | Pending | Needed before final P1 UI/API and optional-feature removal. |
| D04 | Pending | Messaging and safety policy. | Pending | Revised proposal prepared in `docs/remediation/policy-proposals-d04-d05.md`. Unresolved choices: request expiry/cooldowns/budgets, rematch/unblock/account-pause behavior, unsend evidence model, closed-chat evidence access and reviewed user-facing wording. Synthetic-development safeguards may be tested, but production D04 remains unapproved. |
| D05 | Pending | Data lifecycle and retention. | Pending | Revised proposal prepared in `docs/remediation/policy-proposals-d04-d05.md`. Unresolved choices: field-level retention, abandoned/underage signup expiry, confession deletion model, consent proof fields/deadline, restore ledger horizon, media/cache/log purge ownership and rights-request process. All durations remain proposed until owner/privacy review. |
| D06 | Pending | Azure ownership, subscription, region and budget. | Pending | Needed before Azure provisioning. |
| D07 | Pending | Availability, RPO and RTO. | Pending | Needed before production topology and recovery acceptance. |
| D08 | Pending | Runtime/networking versions and session strategy. | Pending | Needed before Azure-bound artifact lock. |
| D09 | Pending | Operations owners and escalation coverage. | Pending | Needed before launch. |
| D10 | Pending | Telemetry fields, retention and alert recipients. | Pending | Needed before production logging/monitoring. |
| D11 | Partial | Use a Bushisa-owned sender domain, approved From address and matching Reply-To. Use fake mail only for labelled local/synthetic development until Azure Communication Services is ready. | Operator, 2026-10-09 | Exact production domain/address and ACS resource remain pending. Fake mail is blocked in production code paths and code-bearing fake-mail payloads are erased on terminal verification/expiry. |
| D12 | Pending | Geographic matching catalogue, consent, retention, granularity and weight. | Pending | Required before P1-01G can be certified. |
