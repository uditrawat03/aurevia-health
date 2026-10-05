# V1 Release Candidate Evidence

This file records the verification evidence for the Aurevia Health Version 1 release candidate. Replace `PENDING` only with evidence from the exact candidate commit or an explicitly identified predecessor whose result remains applicable. Use synthetic healthcare data only.

## Candidate identity

| Item | Evidence |
|---|---|
| Verified M10 implementation commit | `d3f59e18cf2308501cfc1c7b687cf2cb94a534df` — full local release gates rerun immediately before commit |
| Verified pre-documentation closeout commit | `9c425e022147dd576bcbec1dee728f669ca1f763` — GitHub Actions Quality run #18 completed successfully |
| Verified documentation consolidation commit | `0380cbd16f72421c692ca7db1519016dd8553504` — 25-file documentation review committed/pushed; GitHub Actions Quality run #19 completed successfully |
| Final tag candidate commit | **PENDING** — the evidence-closeout commit produced from this state must pass CI on its exact SHA before tagging |
| Candidate tag | `v1.0.0-rc.1` (only after every release blocker is cleared) |
| Verification date | 2026-10-05 |
| Verifier | Local developer verification |
| Environment | Windows 11 / Docker local acceptance plus CI |

## Automated gates

| Gate | Evidence |
|---|---|
| Lighthouse schema validation | **PASS** — schema valid |
| Backend test count/assertions | **PASS** — 84 tests, 530 assertions |
| V1 end-to-end acceptance workflow | **PASS** — `Tests\\Feature\\Release\\V1ReleaseCandidateWorkflowTest` |
| Composer audit | **PASS** — no security vulnerability advisories |
| Angular unit tests | **PASS** — 6 files, 9 tests |
| Angular production build | **PASS** — production bundle generated |
| Production npm dependency audit | **PASS** — 0 vulnerabilities |
| `testing.ERROR` log check | **PASS** — no entries |
| `git diff --check` | **PASS** |
| GitHub Actions on pre-documentation closeout commit | **PASS** — Quality run #18 on `9c425e022147dd576bcbec1dee728f669ca1f763` |
| GitHub Actions on documentation consolidation commit | **PASS** — Quality run #19 on `0380cbd16f72421c692ca7db1519016dd8553504` |
| GitHub Actions on final tag candidate commit | **PENDING** — required after this evidence-closeout change is committed |

Local automated-gate command:

```powershell
pwsh ./scripts/release-candidate.ps1
```

The local automated gates were rerun on the exact implementation content committed as `d3f59e18cf2308501cfc1c7b687cf2cb94a534df`. The CI/evidence closeout commit `9c425e022147dd576bcbec1dee728f669ca1f763` passed Quality run #18, and documentation consolidation commit `0380cbd16f72421c692ca7db1519016dd8553504` passed Quality run #19. After this evidence-only closeout change is committed, CI must pass once more on that exact commit before `v1.0.0-rc.1` is created.

## Security and tenant isolation

Record PASS/FAIL plus relevant test names or manual evidence:

| Check | Evidence |
|---|---|
| unauthenticated protected GraphQL access denied | **PASS** — `IdentityAuthorizationTest::test_organization_query_requires_authentication` |
| cross-tenant organization/patient access denied | **PASS** — `IdentityAuthorizationTest` and `PatientIdentityFoundationTest` |
| wrong-facility access denied | **PASS** — selected facility-scope and patient-scope tests |
| revoked membership takes effect | **PASS** — `IdentityAuthorizationTest::test_selected_facility_scope_is_enforced_and_revocation_takes_effect` |
| clinical write requires appropriate role | **PASS** — `ClinicalRecordFoundationTest::test_staff_cannot_modify_clinical_record` |
| consent denial affects patient workflows | **PASS** — consent/privacy, scheduling, and encounter negative-path tests |
| break-glass requires reason and expires | **PASS** — break-glass policy/GraphQL coverage verifies reason plus effective-window behavior |
| restricted audit viewer enforced | **PASS** — `AuditFoundationTest::test_audit_viewer_is_restricted_and_is_itself_audited` |
| CSRF/session boundary verified | **PASS** — login/logout web guard and GraphQL CSRF tests |
| production debug/cookie/CORS/proxy configuration reviewed | **PASS (source-level RC)** — production defaults are debug-off; a dedicated production template requires HTTPS plus secure/HttpOnly/encrypted sessions; trusted hosts/proxies are explicit; Aurevia first-party CRUD remains GraphQL-only. Deployment-specific host/proxy values remain an operator responsibility. |

## Clinical safety

| Check | Evidence |
|---|---|
| wrong-patient write rejected | **PASS** — `ClinicalRecordFoundationTest::test_wrong_patient_encounter_context_is_rejected_before_clinical_write` |
| signed-note immutability | **PASS** — clinical-record signed-note integrity coverage |
| append-only correction/addendum | **PASS** — signed-note correction reason and append-only coverage |
| invalid encounter transition rejected | **PASS** — encounter transition policy and GraphQL negative-path coverage |
| scheduling conflict rejected | **PASS** — scheduling resource-conflict test |
| terminology mapping never guesses absent translation | **PASS** — `ExistingCodingTerminologyMappingTest` |
| timeline preserves provenance | **PASS** — clinical-record provenance/timeline test plus complete V1 acceptance workflow |
| expected business-rule failures do not emit server-error logs | **PASS** — `ExpectedBusinessRuleViolationTest` plus zero `testing.ERROR` entries after full suite |

## Migration rehearsal

Run:

```powershell
pwsh ./scripts/migration-rehearsal.ps1
```

Result: **PASS — 13/13 Version 1 migrations applied successfully to an isolated clean database.**

M10 intentionally adds no database migration. This rehearsal proves the current Version 1 migration chain can construct a clean database from zero.

## Backup and restore rehearsal

Run:

```powershell
pwsh ./scripts/backup-restore-test.ps1
```

Result: **PASS**

Notes / restored migration count: **13 migrations restored into the isolated rehearsal database.**

This validates local PostgreSQL dump/restore mechanics for synthetic development data. It does not replace a production backup retention, encryption, off-site storage, recovery-time or recovery-point program.

## Performance baseline

Run:

```powershell
pwsh ./scripts/performance-baseline.ps1
```

Method: isolated temporary PostgreSQL database; synthetic organization/facility/patient/appointment/encounter/clinical fixture; 3 warm-up requests plus 25 measured authenticated GraphQL requests per operation inside the API container. This is an application-layer local Docker baseline and excludes external TLS/reverse-proxy/network/browser latency. Timing thresholds are not unit-test assertions.

Result: **PASS — local application-layer baseline recorded on 2026-10-05.**

| Operation | P50 | P95 | Target / note |
|---|---:|---:|---|
| representative authenticated GraphQL API | 17.95 ms | 21.55 ms | below initial P95 target < 300 ms |
| patient search | 19.93 ms | 23.66 ms | below initial P95 target < 1 s |
| clinical-record read | 29.26 ms | 33.88 ms | highest measured P95 in the final pre-commit run |
| scheduling-window read | 24.08 ms | 26.80 ms | local baseline |
| audit-view read | 17.16 ms | 18.53 ms | local baseline |
| main clinical shell usable time | NOT MEASURED | NOT MEASURED | browser/network/TLS/render timing requires staging/browser measurement; the API benchmark cannot measure this honestly |

The final pre-commit benchmark completed with `OK (1 test, 299 assertions)`. All five measured GraphQL operations stayed below 34 ms P95 in the local Docker environment and remained comfortably inside the initial engineering targets. These figures are not production SLAs and should not be extrapolated to browser-perceived latency.

The unmeasured clinical-shell timing is a documented staging/browser follow-up rather than a failed API measurement. Performance exceptions require an explicit reason and follow-up rather than deleting the measurement.

## Accessibility baseline

Automated/source-level status: **PASS** — reusable tabs expose `tablist`/`tab`, roving tabindex and Arrow/Home/End keyboard navigation; primary clinical editor controls have accessible names; shared styles provide visible `:focus-visible` treatment; Angular tests remain 9/9 after the hardening.

Manual verification status: **PASS — developer completed the documented keyboard/visual accessibility smoke check on 2026-10-05.**

- [x] keyboard-only navigation through primary authenticated workflow
- [x] visible focus on interactive controls
- [x] semantic structure/headings reviewed
- [x] form labels and validation associations reviewed
- [x] patient-context banner readability reviewed
- [x] status/error communication does not rely only on color
- [x] contrast reviewed on primary light theme surfaces
- [x] focus behavior after errors/dialog interactions reviewed where present
- [x] accessible names for primary links/buttons reviewed

Known accessibility limitations: **No release-blocking issue was identified in this manual smoke check.** This engineering check is not a formal WCAG certification or a substitute for broader assistive-technology and production accessibility review.

## Audit coverage review

Automated coverage status: **PASS** — Version 1 tests exercise audit evidence across organization/configuration, identity decisions, patients, consent/privacy, scheduling, encounters and clinical records. The complete V1 acceptance workflow also ends with restricted audit review.

Manual vocabulary/completeness review: **PASS (source-level V1 review)** — the stable `AuditAction` vocabulary covers organization/settings, memberships, patients/merge review, consent/privacy/break-glass, scheduling/waitlist, encounters, and clinical-record operations, while `AuditResourceType` has corresponding Version 1 resource categories. `OrganizationAuthorizationService` records both allowed and denied authorization decisions with correlation context, and operation-level services/tests add specific lifecycle actions. The V1 acceptance workflow explicitly verifies `REGISTER_PATIENT`, `BOOK_APPOINTMENT`, `SIGN_CLINICAL_NOTE`, and `COMPLETE_ENCOUNTER`.

This is a Version 1 application-boundary completeness review, not a SIEM certification or jurisdiction-specific regulatory audit assessment. M9 contract-preview operations are non-patient/read-only contract inspection and do not introduce patient exchange audit semantics.

## Known Version 1 limitations

These are intentional boundaries, not silent omissions:

- no full FHIR server or SMART on FHIR authorization;
- no patient-level interoperability import/export transport;
- no automatic SNOMED/LOINC/ICD translation;
- no production terminology-server integration;
- no full inpatient nursing, pharmacy, LIS, PACS, surgery or country-specific claims workflow;
- no patient portal;
- no production AI assistant;
- backup script validates local restore mechanics only, not a production disaster-recovery program;
- production deployment topology still requires deployment-specific trusted proxy/host values and secret injection;
- formal penetration testing, jurisdiction-specific compliance review and deployment threat modeling remain required before real healthcare use.

## Release decision

- [x] automated implementation/documentation gates pass through `0380cbd16f72421c692ca7db1519016dd8553504`
- [x] migration rehearsal passes
- [x] backup/restore rehearsal passes
- [x] security review has no unresolved source-level release blocker
- [x] clinical-safety review has no unresolved release blocker
- [x] performance baseline recorded and exceptions accepted
- [x] accessibility baseline recorded and limitations accepted
- [x] documentation reviewed and consolidated across README, architecture, roadmap, application and domain documentation
- [x] documentation consolidation commit is clean, pushed and CI-green
- [ ] CI green on the evidence-closeout commit that will receive the RC tag

Decision: **READY FOR FINAL EVIDENCE COMMIT — DO NOT TAG until GitHub Actions passes on that exact commit.**
