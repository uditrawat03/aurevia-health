# V1 Release Hardening

## Purpose

V1-M10 hardens the existing Aurevia Health Version 1 foundation and produces release-candidate evidence. It does not add a new healthcare domain. The release candidate remains suitable only for synthetic/development validation until deployment, regulatory, security, privacy, and operational reviews appropriate to the intended jurisdiction are completed.

## Automated release gate

With the Docker application profile running:

```powershell
pwsh ./scripts/release-candidate.ps1
```

or:

```bash
./scripts/release-candidate.sh
```

The gate validates the Lighthouse schema, backend tests, Composer advisories, Angular tests, the production Angular build, production npm dependency advisories, Laravel test-log hygiene, and `git diff --check`.

## V1 end-to-end acceptance flow

`V1ReleaseCandidateWorkflowTest` proves one synthetic workflow across the public GraphQL application boundary:

```text
Create organization and facility
        ↓
Assign scheduler and clinician with facility-scoped roles
        ↓
Register synthetic patient
        ↓
Grant treatment consent
        ↓
Book appointment
        ↓
Create / arrive / start encounter
        ↓
Record problem, allergy and observation
        ↓
Create and sign clinical note
        ↓
Complete encounter
        ↓
Review longitudinal timeline
        ↓
Review audit evidence
```

Scheduling configuration is test fixture data because Version 1 intentionally does not expose generic first-party CRUD for appointment types/resources.

## Separation of duties

The RC workflow deliberately keeps the existing role model:

- `STAFF` performs registration, consent management, and scheduling;
- `CLINICIAN` manages encounters and clinical records;
- `OWNER` performs membership administration and restricted audit review.

M10 must not broaden permissions merely to simplify an acceptance test.

## Migration rehearsal

M10 introduces no database migration. The migration gate therefore verifies that the complete current migration chain can create a clean database:

```powershell
pwsh ./scripts/migration-rehearsal.ps1
```

The script creates a temporary PostgreSQL database, runs every committed migration through the API container, checks migration status, and removes the temporary database.

## Backup and restore rehearsal

Use synthetic/local data only:

```powershell
pwsh ./scripts/backup-restore-test.ps1
```

The rehearsal creates a PostgreSQL custom-format dump inside the local database container, restores it into a temporary database, verifies migration history and core organization/patient/audit tables are queryable, then deletes the temporary database and dump.

This script is a local release gate. Production backup design still requires managed storage, encryption, retention, access control, monitoring, restore objectives, and operational runbooks.

## Production configuration baseline

`apps/api/.env.example` and `infrastructure/docker/compose.yml` are development-only inputs and intentionally keep local values such as `APP_DEBUG=true`, localhost domains and the `change-me-local-only` PostgreSQL fallback. They must not be promoted to production.

Start production configuration from `apps/api/.env.production.example`. The template keeps debug disabled, uses HTTPS, requires secure/HttpOnly session cookies, encrypts server-side session payloads, disables Lighthouse introspection, and assumes a same-origin frontend/API deployment so CORS does not need to be opened by default. Replace every example hostname and provide secrets through the deployment secret store.

Trusted hosts and proxies are explicit opt-in settings. `TRUSTED_HOSTS` contains Laravel host regular expressions. `TRUSTED_PROXIES` contains only the IPs/CIDRs of load balancers or reverse proxies that are allowed to supply forwarded headers. Leave `TRUSTED_PROXIES` empty when Laravel receives TLS directly; never use `*` merely to make proxy detection work. If a future deployment separates frontend and API origins, add a narrow CORS allowlist for exactly those origins rather than enabling wildcard credentialed CORS.

The Horizon dashboard/API routes are framework operational routes, not first-party Aurevia domain CRUD. The existing non-local Horizon gate remains deny-by-default until an explicit production operator access policy is configured.

## Security hardening checklist

Before an RC tag, verify:

- production `APP_DEBUG=false`;
- production secrets are external to source control;
- TLS termination and trusted-proxy behavior are reviewed for the deployment topology;
- session cookies use secure production attributes;
- CORS and Sanctum stateful domains contain only intended origins;
- authorization remains server-side for every protected GraphQL operation;
- negative authorization, facility-scope, revoked-membership and cross-tenant tests pass;
- privacy consent and break-glass negative paths pass;
- protected operations have expected audit evidence;
- application/error logs contain no patient payloads or secrets;
- GraphQL query depth, complexity and pagination limits remain configured;
- no ordinary domain REST CRUD bypass has been introduced;
- dependency audits have no unresolved release-blocking advisories.

## Clinical-safety regression checklist

Verify the automated suite continues to cover:

- wrong-patient encounter/clinical writes are rejected;
- signed notes cannot be overwritten;
- corrections/addenda preserve original signed content;
- encounter transitions reject invalid state jumps;
- scheduling conflicts reject overlapping bookings;
- terminology mapping does not guess absent clinical translations;
- patient context remains visible on clinical UI surfaces;
- security-relevant expected business-rule rejections do not pollute server-error logs.

## Performance baseline

The targets in `ARCHITECTURE.md` are engineering targets, not contractual SLAs. Before tagging an RC, record measurements in `V1_RELEASE_CANDIDATE_EVIDENCE.md` from a representative local/staging environment with synthetic data.

Run the repeatable local application-layer baseline against an isolated PostgreSQL database:

```powershell
pwsh ./scripts/performance-baseline.ps1
```

or:

```bash
./scripts/performance-baseline.sh
```

The benchmark warms each operation, records 25 measured authenticated GraphQL requests, and prints `AUREVIA_PERF` P50/P95 lines for organization read, patient search, clinical-record read, scheduling-window read, and audit-view read. The temporary PostgreSQL database is removed after the run. The measurement includes Laravel, GraphQL, authorization, privacy/audit behavior, and PostgreSQL inside the local Docker stack; it does not include external TLS/reverse-proxy/network/browser latency. No wall-clock threshold is asserted in the normal test suite.

At minimum measure:

- representative authenticated GraphQL read/write P50/P95;
- patient search P50/P95 with a representative synthetic dataset;
- clinical-record read P50/P95;
- scheduling-window read P50/P95;
- audit-view read P50/P95;
- first usable Angular clinical shell time under the expected enterprise network profile.

Do not turn unstable wall-clock assertions into unit tests. Keep reproducible load-test commands/results with the release evidence instead.

## Accessibility baseline

Perform a manual WCAG 2.1 AA-oriented pass on the authenticated shell, patient search/detail, scheduling, encounter and clinical record flows. Record evidence for:

- keyboard-only navigation;
- visible focus;
- semantic headings/regions;
- form labels and associated validation errors;
- accessible names for buttons/links;
- patient-context banner readability;
- dialogs/error recovery and focus return where applicable;
- contrast for text, controls and status indicators;
- no color-only communication of critical state.

Automated component tests remain useful but do not replace keyboard/screen-reader review for clinical workflows.

## Documentation coherence gate

Before tagging an RC:

- README must describe the complete implemented Version 1 slice, not stop at an earlier milestone;
- `ARCHITECTURE.md` must distinguish implemented modules from target/future boundaries;
- `ROADMAP.md` must not imply a full horizon is complete when Version 1 implements only a subset;
- domain documents must describe current behavior and deferred scope rather than obsolete "next milestone" language;
- `apps/api/README.md` and `apps/web/README.md` must document Aurevia, not framework-generator boilerplate;
- release evidence must identify the exact candidate SHA and must not mark manual checks complete without evidence;
- every link/command changed by implementation work must be reviewed as part of the same milestone.

## Release blockers

Do not tag the RC when any of the following is known:

- cross-tenant or wrong-facility patient access;
- authorization bypass;
- unaudited required protected operation;
- silent overwrite/loss of signed clinical documentation;
- silent terminology translation;
- failed migration or restore rehearsal;
- committed secret or real patient data;
- unresolved high/critical production dependency vulnerability without an accepted risk decision;
- failing backend/frontend/schema/build gates;
- unexpected `testing.ERROR` output from the automated suite.
