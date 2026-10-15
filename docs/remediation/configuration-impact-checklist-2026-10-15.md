# Azure and Namecheap configuration impact: read-only checklist

Document date: 2026-10-15. Linked [owner selections and proposed values](owner-policy-reconciliation-2026-10-15.md),
[inventory/retention matrix](data-inventory-retention-matrix.md) and
[implementation backlog](backlog.md#q01-q48-implementation-backlog).

This checkpoint changes documentation only. It contains no instruction to apply
a setting, provision resources, upgrade a subscription, modify DNS/secrets,
send mail, query the live database, merge or deploy. Later operator captures
need their own authorized task; do not run old playbook deployment examples as
part of this checklist. **CONFIRM** is missing owner information; **VERIFY** is
missing technical evidence; **REVIEW GATE** is a qualified acceptance.

## Evidence already recorded, with provenance

These are historical records, not current portal/DNS/database inspections.
The 11 October [continuation pack](../Bushisa-P0-P2-Continuation-Prompts-2026-10-11.md)
T01-T15 and [SMTP deployment document](../azure-smtp-deployment.md) supply the
resource leads. The latter's original not-deployed statement is historical;
T02/T10-T14 record later source/operator progress, without certifying every gate.

| Item | Recorded lead | Current status / capture still needed |
|---|---|---|
| App and subscription | App `Bushisa`; resource group `takudzwaportfolio`; Basic Linux, South Africa North; Azure for Students shown | Operator portal evidence. VERIFY current plan/instance count/region/offer and expiry; CONFIRM owner/funding. Bushisa is already hosted, not a new deployment. |
| Domain | `bushisa.me`, HTTPS/bindings reported configured | Operator evidence. VERIFY authoritative nameservers/registrar/DNS host and redacted website/TLS metadata; preserve working resolution. |
| Database | `bushi-server.mysql.database.azure.com`, DB `bushi-database`; TLS cipher `TLS_AES_256_GCM_SHA384` reported | Historical terminal evidence, not a present connection. VERIFY current server version/network/private DNS/TLS/roles through later approved capture. Do not query it now. |
| PHP | CLI PHP 8.5.6 reported on 11 October | Historical operator SSH output. VERIFY current runtime/patch/OS/extensions, exact candidate artifact and staging parity. |
| Sender/resources | Email resource `bushisa-email`; sending resource `bushisa-communication`; domain `bushisa.me`; `DoNotReply@bushisa.me`; ACS data location United States recorded | Portal/operator evidence; exact From/display-name reuse selected by Q45. VERIFY connection/domain/sender/region/processing/quotas and redacted header evidence; do not invent new IDs or call this fresh verification. |
| Transport | `smtp.azurecomm.net`, 587, STARTTLS; configuration/auth succeeded | Historical terminal/operator evidence. VERIFY current safe adapter/configuration metadata, secret references and rotation ownership without retrieving values. |
| Scheduler | `bushisa-mail`, `0 * * * * *`, cleanup then dispatch; Always On/storage/Kudu settings reported | T12 operator evidence. VERIFY actual scheduled/restart execution and useful-deadline capacity; one email does not prove service targets. |
| Delivery/onboarding | Authorized inbox/code entry/adult onboarding/logout/login reported successful | T13 screenshot/operator confirmation and T14 user report. Preserve attribution; broader headers/failure/resend/revocation checks remain open. |
| Migrations | T08 reports 001-004 at that historical environment; Prompt 02 rehearsed 005 on disposable DBs | **No production-applied claim for 005.** Read-only future inventory must distinguish operator evidence from disposable rehearsal. |
| Candidate CI | Head `e30dcf844abaf499bcc657172c2c9aa1f4b4986d`; push `38546846225`, PR `38546851035` | Previously independently retrieved candidate jobs, exactly as in owner packet. Not a test of new policy, current production or a restore. |

## Read-only capture checklist for a later authorized operator task

| Area / questions | Capture without mutation or secrets | Acceptance / remaining gate |
|---|---|---|
| Ownership/funding Q35/Q42 | Actual tenant/subscription/offer and accountable person's acceptance; remaining credit/expiry; payment source/reserve/currency/taxes; cost-alert recipients | CONFIRM real owner, approved total cap/reserve. Takudzwa's suggested appointment is not accepted by inference. Keep account/contact details in a private operations register. |
| Service geography Q36 | Current app/DB/media/workspace/ACS locations; redundancy/backup copies; control-plane/support/email/DNS/CDN/SDK flows and relevant processor terms | Qualified privacy/security review of actual Zimbabwe/foreign flows; proposed South Africa North is not a universal residency guarantee. |
| Runtime/artifact Q39 | Redacted current production/staging reports, exact PHP/MySQL major/minor/patch, OS/build/container digest/package lock, declared extensions | Named engineering/security reviewer plus Takudzwa accept exact matrix; no guessed supported patch or DB upgrade. Required later tests include UTC/collation/SQL modes/TIMESTAMP(6)/locks/auth/migrations. |
| Sessions/ingress Q40 | Effective handler/storage scope/persistence/locking; instance count; cookie/idle/absolute settings; trusted proxy/HTTPS path; evidence of foreground vs polling activity | Source currently calls PHP sessions, uses Strict cookies and 1,800-second refresh logic. Proposed DB sessions/role-specific timeouts/Lax are unimplemented/unapproved settings; verify deployment rather than equating `/home` with shared storage. |
| Network/access Q41 | Resource/network metadata; current VNet/private endpoint/private DNS/egress/firewall/TLS policy; runtime vs release identities and role descriptions; secret-reference names/resolution status only | VERIFY private reachability and restrictive runtime permissions later. No broad firewall exception, credential values, connection strings, keys or all-settings dumps in evidence. Security approver UNASSIGNED. |
| Deployment/staging Q37 | Current deployment route, artifact retention, tier/slots capability, isolated app/DB/media/config/mail and migration execution path | Staging must be isolated. Microsoft documents slots for Standard/Premium/Isolated; Basic needs a separately priced staging option unless changed later under approval. [Slot documentation](https://learn.microsoft.com/en-us/azure/app-service/deploy-staging-slots). |
| Backups/copies Q26/Q38 | Actual DB/PITR/redundancy/long-term retention; stopped/restored servers/dumps; Blob versions/soft deletion; manual exports; each creation/expiry and independent replay source/key custody | Proposed DB 35d/media 7d/export 24h are not current settings or physical-erasure proof. Verify service-specific retention, including stopped servers and independently retained archives; compute latest copy expiry plus 7d margin. [MySQL backup documentation](https://learn.microsoft.com/en-us/azure/mysql/flexible-server/concepts-backup-restore). |
| Recovery Q38 | Existing runbooks, backup-owner acceptance, proposed restore witnesses, queue/protective-ledger continuity and last measured rehearsal evidence if any | Current isolated restore **NOT RUN**. Do not substitute synthetic fail-closed tests or deployment success. RPO/RTO values remain engineering proposals; actual restore is separate authorization. |
| Diagnostics Q23/Q43/Q44 | Actual workspace/table/trace/metric/export destinations, region, minimum retention/billing tier, collection allowlist/sampling/caps/redaction/consent state | VERIFY enforceability of proposed 30d logs and US$5 allowance; document longer provider minima before decision. A daily cap can overshoot and excess ingestion can be billed. [Daily-cap limits](https://learn.microsoft.com/en-us/azure/azure-monitor/logs/daily-cap). |
| Dispatch Q22/Q46/Q47 | Current job schedule/retries/batch limit and actual configured/provider quota, stable secret-reference names, last redacted run metadata | Compare cadence with min(queue +5m, auth expiry -60s). Signup-specific dispatcher must not be claimed reset-ready. Approved future test recipients/named mail operator remain CONFIRM; no mail sent now. |
| Cleanup Q10-Q27 | Current job/category/object-index coverage, overdue counters/watermarks, alert route and accepted executor/repair/privacy sampler | VERIFY hourly execution, immediate denial independent of job, per-category physical ceilings and overdue-at-deadline escalation. Existing cleanup marks only limited signup/synthetic categories; queue completion is not full erasure. |
| Staffing Q05/Q27/Q42/Q48 | Accepted primary/backup, monitored contact, coverage, independent appeal reviewer and qualified review roles; alert receipt/capacity evidence | No public SLA promise until staffing/alerts/capacity are real. Routine 08:00-20:00 Harare and urgent on-call are proposals. |

Only allowlisted nonsecret fields may be captured. If an output might contain
secrets or private students' data, redact at source rather than copying it into
a public evidence artifact. A documentation checklist is not authorization for
the later live capture, configuration changes or testing.

## Cost inventory and funding worksheet

**Not yet costed to a reliable total:** actual SKUs, quantities, service usage,
offer discounts/credit, approved currency/billing terms and total cap are missing.
Do not fill unknowns with invented US$50/US$100 budgets or imply a payment
commitment. This worksheet makes every cost category and formula reviewable;
the costed total stays **VERIFY/CONFIRM** until real inputs/prices are captured.
Use the [official Azure calculator](https://azure.microsoft.com/en-us/pricing/calculator/)
with exact region/SKU/usage and retain its dated estimate separately from the
owner's later funding acceptance. Domain/inbound-mail costs need the actual
provider quote, renewal terms and taxes.

| Cost category | Required quantity / billing input | Unit rate and monthly cost | Owner decision |
|---|---|---|---|
| Production app | Actual Linux plan/SKU/instances/hours | VERIFY regional effective rate x instance-hours | Existing hosted service; no upgrade approval |
| Isolated staging | Separate plan/app or verified supported slot capacity; operating hours | VERIFY incremental compute/capacity/storage | Isolation required; topology/cost pending |
| MySQL | Actual major/tier/vCores/HA/hours/storage/IO | VERIFY compute + storage + IO/HA | No local-version substitution or unapproved upgrade |
| DB backups/recovery | Used GiB, redundancy, retained copies/PITR/LTR and rehearsal hours | VERIFY included allowance + excess storage + temporary recovery resources | 35d proposal, RPO/RTO pending |
| Private networking | Needed integration/endpoints/DNS/zones/traffic | VERIFY per-resource/hour/query/data rates | Cost proposal before any mutation |
| Media | Live/version GiB, operations, derivatives, recovery copies | VERIFY storage + operations + version/soft-delete overhead | Private access and 7d backup proposal |
| Egress | Actual outward GB and service/region path | VERIFY effective transfer rate x chargeable usage | Do not assume free student egress |
| Authentication mail | Consented volume/size/retries/provider quota | VERIFY message/data rates, overage and queued-work compute | Same sender selected; no real sending now |
| Jobs/secrets/registry | Existing scheduler vs additional job resources, execution/storage/access operations | VERIFY actual selected infrastructure rates | Do not buy a second scheduler by assumption |
| Telemetry | Workspace ingestion, retention/table minimums, sampling/export | VERIFY volume x rates + retained/exported data | **US$5/month is proposed**, inside overall budget |
| Monitoring/notifications | Synthetic checks, alert rules, recipients/channels/volume | VERIFY billable checks/rules/notifications | Thresholds/response staffing pending |
| Domain and inbound support | Current domain renewal, receiving provider/seats/retention | VERIFY actual renewal/provider price / monthly equivalent | `support@` is a proposal, not an existing mailbox |
| Continuity reserve/tax/FX | Approved reserve, billing currency, applicable tax/conversion | CONFIRM; separately itemize recurring versus contingency cost | No approved payment source/cap/reserve yet |
| Total | Sum priced recurring lines; show reserve/tax separately | **VERIFY: no defensible numeric total available** | CONFIRM explicit owner acceptance before spending |

Selected cost guardrails are actual-cost 50%/80%/100% and forecast-100% alerts,
once a real owner-approved budget exists. Budget notifications do not stop
resource consumption. At cap escalate and stop optional new paid provisioning;
do not delete data or turn off safety/recovery automatically. [Microsoft budget
behavior](https://learn.microsoft.com/en-us/azure/cost-management-billing/costs/tutorial-acm-create-budgets).

## Sender, inbound mailbox and Namecheap

1. **Preserve sender identity.** Q45/Q47 select `DoNotReply@bushisa.me`, display
   name `Bushisa`, using the existing recorded ACS resources. Future authorized
   header/inbox checks must verify the actual domain connection and allowed
   MailFrom. A change of email purpose to password reset does not itself add
   a new sender-domain DNS requirement. This is an inference from reusing the
   same verified domain/sender, not a fresh DNS export or delivery test.
   [ACS verified-domain/sender contract](https://learn.microsoft.com/en-us/azure/communication-services/quickstarts/email/add-custom-verified-domains).
2. **Receiving support is separate.** `support@bushisa.me` is proposed; actual
   provider/account/owner/backup, external receipt, monitoring and a privacy
   route are CONFIRM/VERIFY. Outbound ACS registration proves no receiving
   mailbox. Resolve the earlier D11 matching-Reply-To versus proposed unset
   fallback in the reconciliation; do not publish an unverified inbox.
3. **Capture authoritative DNS first.** Registration at Namecheap alone does
   not establish the active zone. Capture current nameservers and approved
   receiving-provider instructions. Namecheap's Mail Settings depend on the
   actual receiving service and nameserver setup. [Namecheap MX guidance](https://www.namecheap.com/support/knowledgebase/article.aspx/322/2237/how-can-i-set-up-mx-records-required-for-mail-service/),
   [authoritative host-record guidance](https://www.namecheap.com/support/knowledgebase/article.aspx/434/2237/how-do-i-set-up-host-records-for-a-domain/).
4. **No guessed records.** This task supplies no MX targets/priorities,
   verification tokens, SPF includes, DKIM selectors/keys or DMARC destination.
   A later receiving-provider choice may require that provider's exact records
   after review of existing website/mail settings. Do not point inbound MX to
   the outbound ACS SMTP endpoint, duplicate SPF policies or replace working
   selectors by guesswork. No domain, nameserver or DNS change is authorized.
5. **Later acceptance evidence.** With accepted people and consenting recipients,
   verify sender/authentication headers, safe From/Reply-To, useful inbox arrival,
   bounce/failure handling, entered signup code and single-use reset link.
   Keep live codes/tokens/addresses out of screenshots and logs. Do not claim
   dispatch deadlines guarantee inbox arrival or provider-message recall.

Public vendor documentation above was inspected for constraints during this
documentation task; it does not verify Bushisa's actual settings or constitute
privacy, security, funding or operational acceptance.
