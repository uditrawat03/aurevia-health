# Aurevia Health

Aurevia Health is a country-neutral health and hospital management platform foundation built with **Laravel 13**, **Angular 22**, **PostgreSQL 18**, and **Redis 8**. Version 1 establishes a secure GraphQL-first slice spanning organization/facility scope, identity and authorization, audit, patient identity, consent/privacy, scheduling, encounters, core clinical documentation, terminology/interoperability contracts, and release-candidate hardening.

The architecture keeps global healthcare concepts in the core while isolating jurisdiction-specific regulation, reimbursement, terminology profiles, national identifiers, and external infrastructure behind country-profile and integration boundaries.

> **Release posture:** Version 1 is an engineering release-candidate foundation validated with synthetic data. It is not a claim of regulatory compliance, production certification, or suitability for real patient care without deployment-specific security, privacy, clinical-safety, legal, operational, and jurisdictional review.

## Official Product Identity

**Product:** Aurevia Health  
**Repository:** `aurevia-health`  
**Backend application:** `aurevia-health-api`  
**Frontend application:** `aurevia-health-web`

Aurevia Health is the official name used for product, architecture, roadmap, milestone, and implementation documentation.

## Version 1 Status

Version 1 RC1 is tagged as [`v1.0.0-rc.1`](docs/releases/v1.0.0-rc.1.md) at commit `5d7ef2a3903dcc4980c45630f55869cab34f70cc`. The tag-candidate commit passed GitHub Actions **Quality** run #20, and the manual accessibility smoke check passed on 2026-10-05. Version 1 remains an engineering release candidate, not a regulatory-compliance or production-certification claim.

| Area | Current Version 1 capability |
|---|---|
| Organization | Country-neutral organization, health-system, facility and department hierarchy with server-pinned country profiles and inherited operational settings |
| Identity | Sanctum first-party session authentication, organization memberships, role permissions, facility scope and server-side authorization |
| Audit | Structured allowed/denied authorization evidence, stable V1 action/resource vocabulary, patient context and restricted audit viewing |
| Patient identity | Internal ULID patient identity, typed identifiers, secure search, duplicate candidates and non-destructive merge review |
| Privacy | Consent lifecycle, privacy decisions and time-bounded break-glass treatment access |
| Scheduling | Resource/appointment types, booking, conflict checks, reschedule, cancellation, waitlist foundation, timezone handling and idempotency |
| Encounters | Facility/patient-bound encounter creation and controlled lifecycle from planned through completion/cancellation |
| Clinical record | Problems, allergies, observations, draft/sign note lifecycle, append-only addenda/corrections, provenance and timeline |
| Terminology / interoperability | Multi-coding terminology concepts, explicit versions, governed mapping contract, FHIR R4 exchange mapper and correlation-aware contract preview |
| Release hardening | Full synthetic V1 workflow, dependency audits, migration/restore rehearsals, production configuration baseline, performance evidence and accessibility source/test hardening |

See [docs/README.md](docs/README.md) for the maintained documentation map and ownership rules.

## Version 2 Planning

Version 2 is planned as a **care-delivery expansion** built on the V1 trust foundation. The sequence adds care teams/tasks, clinical orders/results, inpatient admission/transfer/discharge and bed flow, nursing, emergency, medication-safety and diagnostic workflow foundations before a new hardening/release-candidate gate.

The Version 2 plan deliberately does not pull the entire long-term roadmap into one release. Financial workflows, patient portal, country-specific claims, production national integrations, broad analytics and AI remain outside V2 unless separately promoted through roadmap governance.

See [version_2_milestones.md](version_2_milestones.md) for the planned sequence and [ROADMAP.md](ROADMAP.md) for the longer-term capability horizons.

## Technology Baseline

| Area | Baseline |
|---|---|
| Backend | Laravel 13.x |
| PHP | 8.5 recommended; Laravel 13 supports 8.3–8.5 |
| Frontend | Angular 22.x |
| Node.js | 24 LTS |
| TypeScript | Angular 22 compatible 6.0.x |
| Frontend testing | Vitest |
| Angular model | Standalone, strict, zoneless |
| Database | PostgreSQL 18 |
| Cache / queues | Redis 8 |
| API authentication | Laravel Sanctum, first-party SPA session/cookie model |
| GraphQL server | Nuwave Lighthouse 6.x |
| Local orchestration | Docker Compose |
| API style | GraphQL-first application API at `/graphql`, with protocol/interoperability HTTP adapters kept separate |
| Architecture | Modular monolith first, event-driven boundaries |
| CI | GitHub Actions baseline |

The framework versions above were selected against the supported releases available at the repository baseline date. Patch releases should be allowed through normal dependency management and tested in CI.

## Product Direction

The core product owns global healthcare concepts:

- organizations and facilities;
- users, identities, roles, and policies;
- patients and patient identifiers;
- encounters and admissions;
- scheduling;
- clinical documentation;
- orders and results;
- medication workflows;
- laboratory and imaging workflows;
- nursing, emergency, surgery, and discharge;
- billing primitives;
- consent and privacy decisions;
- audit;
- interoperability abstractions;
- workflow automation;
- analytics;
- AI governance.

Country profiles own jurisdiction-specific concerns:

- regulatory policy;
- national healthcare identifiers;
- clinical terminology profiles;
- reimbursement and payer rules;
- prescribing networks;
- government integrations;
- public-health reporting;
- country-specific interoperability profiles;
- legal retention and disclosure policies.

The governing rule is:

> **Healthcare concepts belong to the core. Jurisdiction-specific regulation, reimbursement, terminology, and national infrastructure belong to country profiles.**

## Repository Layout

```text
aurevia-health/
├── apps/
│   ├── api/                     # Laravel 13 application, GraphQL schemas, domain/application code and tests
│   └── web/                     # Angular 22 SSR application, workspaces, shared UI and tests
├── docs/                        # Platform, domain and release documentation
├── infrastructure/docker/       # Local PostgreSQL/Redis/Laravel/Horizon/Angular Compose topology
├── scripts/                     # Bootstrap, Docker helpers and release-candidate rehearsal commands
├── .github/workflows/quality.yml
├── ARCHITECTURE.md
├── CODING_GUIDELINES.md
├── MILESTONES_TESTING.md
├── ROADMAP.md
├── SECURITY.md
├── version_1_milestones.md
└── README.md
```

## Prerequisites

Recommended developer environment:

- Git
- PHP 8.5
- Composer 2
- Node.js 24 LTS
- npm
- Docker Desktop / Docker Engine with Compose
- PowerShell 7+ on Windows

Validate the tools:

```powershell
php --version
composer --version
node --version
npm --version
docker --version
docker compose version
```

## Framework Bootstrap

A normal clone of this repository already contains `apps/api` and `apps/web`; **do not rerun the framework bootstrap over an existing checkout**. The bootstrap commands below are retained for recreating the framework skeleton in an intentionally empty starter workspace.

### Windows / PowerShell

From an empty starter repository root:

```powershell
pwsh ./scripts/bootstrap.ps1
```

### macOS / Linux

```bash
bash ./scripts/bootstrap.sh
```

The bootstrap script:

1. creates a fresh Laravel 13 application in `apps/api`;
2. installs Laravel Sanctum and Nuwave Lighthouse for the first-party GraphQL boundary;
3. installs Laravel Horizon for Redis queue operations;
4. creates the initial healthcare domain folders;
5. creates an Angular 22 application in `apps/web`;
6. enables routing, strict mode, standalone APIs, zoneless operation, SCSS, and Vitest;
7. leaves both applications ready for project-specific implementation.

The bootstrap scripts intentionally use official framework generators instead of committing an imitation of generated framework source.

### Docker-first bootstrap

If you prefer not to install PHP, Composer, Node.js, and npm on the host, or Windows PowerShell execution policy blocks `bootstrap.ps1`, use Docker instead:

```cmd
scripts\bootstrap-docker.cmd
```

This generates the same Laravel 13 and Angular 22 applications through containerized PHP/Composer and Node tooling. After it completes:

```cmd
scripts\docker.cmd up
scripts\docker.cmd migrate
```

## Docker Development

Aurevia Health supports two local modes: infrastructure-only Docker, or a full Docker application profile.

Start PostgreSQL and Redis only (safe before framework bootstrap):

```cmd
scripts\docker.cmd infra
```

After `apps/api` and `apps/web` exist, start Laravel, Horizon, Angular, PostgreSQL, and Redis:

```cmd
scripts\docker.cmd up
```

Development endpoints:

| Service | Address |
|---|---|
| Angular | `http://localhost:4200` |
| Laravel | `http://localhost:8000` |
| Laravel health | `http://localhost:8000/up` |
| GraphQL | `http://localhost:8000/graphql` |
| Angular GraphQL proxy | `http://localhost:4200/graphql` |
| PostgreSQL | `127.0.0.1:5432` |
| Redis | `127.0.0.1:6379` |

Useful commands:

```cmd
scripts\docker.cmd status
scripts\docker.cmd migrate
scripts\docker.cmd test
scripts\docker.cmd e2e
scripts\docker.cmd logs
scripts\docker.cmd down
```

### Browser acceptance testing

`scripts\docker.cmd e2e` runs a separate, disposable browser-acceptance stack with **Python 3.14.7**, **pytest 9.1.1**, **Playwright 1.63.0**, and Chromium. The stack has its own PostgreSQL/Redis data, seeds synthetic demo data, drives Angular through a real browser, captures screenshots/traces on failure, and removes the isolated volumes after the run.

No host Python installation is required. See [docs/BROWSER_E2E_TESTING.md](docs/BROWSER_E2E_TESTING.md) for test-writing rules, architecture, artifacts and debugging guidance.

The Windows `.cmd` helper does not depend on PowerShell script execution policy. See [docs/DOCKER_DEVELOPMENT.md](docs/DOCKER_DEVELOPMENT.md) for details.

## GraphQL Backend Foundation

Aurevia Health uses **Nuwave Lighthouse 6.x** for the Laravel GraphQL server. The first-party application endpoint is `/graphql`; ordinary domain CRUD must not add parallel `/api/v1/...` routes.

After applying GraphQL dependency changes with the Docker application stack running:

```cmd
scripts\graphql.cmd
```

The command updates `apps/api/composer.lock`, clears Laravel caches, and validates the GraphQL schema. Commit the resulting lockfile with the milestone.

Verify the backend boundary:

```powershell
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit
```

The initial query is intentionally non-clinical:

```graphql
query {
  systemInfo {
    name
    version
    graphqlEndpoint
  }
}
```

See [docs/GRAPHQL_BACKEND.md](docs/GRAPHQL_BACKEND.md) for resolver, security, correlation-ID, and schema conventions.

## Organization and Country Profile Foundation

V1-M1 adds the country-neutral organization hierarchy (`Organization → Health System / Facility → Department`), internal ULID identifiers, server-controlled country-profile selection with pinned profile versions, typed operational setting inheritance, and correlation-linked configuration-change evidence.

Configuration resolves through global defaults, country profile, organization, facility, and department. The initial country profiles are India, United Kingdom, United States, plus a `CORE` fallback for other country codes; adding a country profile does not require a fork of the generic organization schema.

The organization boundary remains GraphQL-first. V1-M1 introduced typed hierarchy/settings operations, and the completed V1-M2 authorization layer now protects them with authenticated memberships, role permissions and facility scope. Organization IDs remain scope identifiers rather than authorization evidence.

See [docs/ORGANIZATION_COUNTRY_PROFILE.md](docs/ORGANIZATION_COUNTRY_PROFILE.md) for the hierarchy, profile resolution, configuration precedence, GraphQL contract, audit evidence, authorization behavior, isolation guarantees, and current limits.

## Run Applications on the Host

If you prefer host processes, start only infrastructure with `scripts\docker.cmd infra`, then run:

```powershell
cd apps/api
php artisan migrate
php artisan serve
```

and in another terminal:

```powershell
cd apps/web
npm start
```

## Identity, Authentication and Authorization Foundation

V1-M2 protects the first-party GraphQL boundary with Laravel Sanctum's stateful `web` session, explicit CSRF bootstrap, organization memberships, role permissions, facility scope, and an ABAC-style policy contract. The Angular shell does not store bearer tokens in browser storage; it restores the authenticated user through `me`, signs in through the GraphQL `login` mutation after `/sanctum/csrf-cookie`, and invalidates the current server session through `logout`.

Organization creation now requires an authenticated user and atomically assigns that user an active `OWNER` membership with all-facility scope. Existing organization reads and mutations require active organization membership plus the permission and facility scope appropriate to the operation. Cross-organization access, unselected facility access, and revoked memberships are denied server-side.

Initial roles are `OWNER`, `ADMIN`, `CLINICIAN`, `STAFF`, and `VIEWER`. Role permissions are evaluated together with organization/facility attributes rather than relying on Angular route state. All-facility organization owners and administrators can assign or revoke memberships through typed GraphQL mutations.

Version 1 still does not implement MFA, enterprise OIDC/SAML, password recovery, or remote-session inventory. Patient privacy, consent and break-glass decisions now extend the authorization boundary for patient/clinical workflows; richer care-team and purpose-specific ABAC can extend it further without changing the rule that Laravel remains authoritative.

See [docs/IDENTITY_AUTHORIZATION.md](docs/IDENTITY_AUTHORIZATION.md) for the session flow, role/permission matrix, facility scoping, GraphQL operations, test expectations, and current limitations.

## Audit Foundation

V1-M3 adds structured, append-oriented `audit_events` for the protected organization boundary. Authorization decisions persist actor, organization/facility scope, generic resource context, action, `ALLOWED`/`DENIED` outcome, correlation ID, and occurrence time. The schema also reserves nullable patient context for V1-M4 without embedding patient data in generic audit payloads.

Denied GraphQL mutation decisions are deferred in request context and flushed after Lighthouse completes or rolls back its transactional mutation. This preserves security evidence for rejected writes without disabling transaction safety. Allowed authorization evidence participates in the request transaction, and organization creation records its authenticated onboarding event explicitly.

The `auditEvents` GraphQL viewer is restricted to organization-wide `OWNER` and `ADMIN` memberships through the new `VIEW_AUDIT` permission. The viewer is itself audited and returns only the selected organization's newest evidence, with a bounded maximum of 100 rows per request.

See [docs/AUDIT_FOUNDATION.md](docs/AUDIT_FOUNDATION.md) for the audit schema, denial durability model, restricted viewer, patient-context extension point, and verification requirements.

## Patient Identity / MPI Foundation

V1-M4 introduces a country-neutral patient identity aggregate with internal ULID patient IDs, typed MRN/national/insurance/other identifiers, demographics, contacts, addresses, relationships, secure patient search, explainable duplicate candidates, and a non-destructive merge-review model. National and external identifiers never become primary keys, and duplicate candidates are never auto-merged.

Patient reads and search are organization/facility authorized and patient-aware audit evidence now carries patient context. `OWNER`/`ADMIN` can request organization-wide merge review; `CLINICIAN`/`STAFF` can view and register patients inside permitted facility scope; `VIEWER` receives no patient access in this foundation.

For local development, seed the synthetic workspace after migration:

```powershell
docker exec aurevia-health-api php artisan db:seed
```

Then sign in with:

```text
Email:    owner@aurevia.local
Password: AureviaLocal123!
```

These credentials are intentionally local-development-only. The demo seeder is disabled outside Laravel's `local` environment and also creates one demo facility plus two synthetic patients.

See [docs/PATIENT_IDENTITY_MPI.md](docs/PATIENT_IDENTITY_MPI.md) for patient identity modeling, duplicate matching, merge-review safety, authorization, auditing, the patient-context banner foundation, and seed-data details.

## Consent and Privacy

V1-M5 adds persisted consent grants/revocations, explicit privacy decisions and time-bounded break-glass treatment access. Consent is evaluated in addition to organization, role and facility authorization; it never replaces those controls. Country-profile privacy policies may preserve or tighten the core decision but cannot convert a core denial into an allow.

See [docs/CONSENT_PRIVACY.md](docs/CONSENT_PRIVACY.md).

## Scheduling

V1-M6 implements facility-scoped scheduling with appointment types, resources, patient/resource conflict detection, timezone-safe booking, retry-safe idempotency, reschedule/cancellation history and waitlist creation/cancellation. Scheduling operations re-evaluate patient privacy and server-side scope.

See [docs/SCHEDULING_FOUNDATION.md](docs/SCHEDULING_FOUNDATION.md).

## Encounter Foundation

V1-M7 connects scheduling and patient identity to a controlled clinical encounter lifecycle. Encounter creation validates organization, facility, patient and optional appointment binding; state transitions are explicit and terminal states cannot be silently reopened.

See [docs/ENCOUNTER_FOUNDATION.md](docs/ENCOUNTER_FOUNDATION.md).

## Core Clinical Record

V1-M8 provides the first longitudinal clinical record slice: problems, allergies, observations, clinical notes, draft/sign lifecycle, append-only signed-note addenda/corrections, provenance and patient timeline. Clinical writes bind organization, facility, patient and encounter server-side, and ordinary edits of signed notes are rejected.

See [docs/CLINICAL_RECORD_FOUNDATION.md](docs/CLINICAL_RECORD_FOUNDATION.md).

## Terminology and Interoperability Contracts

V1-M9 adds a country-neutral terminology concept that can carry multiple explicitly versioned codings, a replaceable terminology-mapping contract, a correlation-aware interoperability envelope, and an anti-corruption mapper for a pinned FHIR R4 `4.0.1` boundary. FHIR remains an exchange representation rather than an Eloquent/database model.

The initial terminology registry advertises SNOMED CT, LOINC, ICD-10 International, and an Aurevia/local extension URI while still accepting other absolute URI/URN code systems. The default mapping behavior never guesses clinical translations: it may select a target-system coding already present on a concept, otherwise it rejects the mapping until a governed adapter is configured.

M9 exposes non-patient GraphQL contract queries for terminology-system discovery, supported interoperability profiles, and concept-to-FHIR preview. Patient-level import/export transport, SMART on FHIR, terminology-server calls, HL7 v2/DICOM transport, and automatic code translation remain outside this milestone.

See [docs/TERMINOLOGY_INTEROPERABILITY.md](docs/TERMINOLOGY_INTEROPERABILITY.md) for the terminology model, mapping rules, FHIR boundary, correlation behavior, GraphQL preview contract, verification steps, and deferred production-integration work.

## Version 1 Release Candidate Hardening

V1-M10 adds release-candidate verification around the completed Version 1 foundation rather than introducing another healthcare domain. A full synthetic acceptance test now crosses organization/facility setup, facility-scoped staff roles, patient registration, consent, scheduling, encounter lifecycle, clinical documentation, signing, timeline review, and restricted audit review through the GraphQL application boundary.

The RC gate also adds Composer and production npm dependency audits to CI, repeatable Docker scripts for the complete local quality gate, a clean-database migration rehearsal, and a PostgreSQL backup/restore rehearsal that restores only into a temporary local database.

Run the Windows acceptance gates with:

```powershell
pwsh ./scripts/release-candidate.ps1
pwsh ./scripts/migration-rehearsal.ps1
pwsh ./scripts/backup-restore-test.ps1
```

See [docs/RELEASE_HARDENING.md](docs/RELEASE_HARDENING.md) for the security, clinical-safety, migration, restore, performance, accessibility, and production-configuration gates. Record candidate-specific evidence in [docs/V1_RELEASE_CANDIDATE_EVIDENCE.md](docs/V1_RELEASE_CANDIDATE_EVIDENCE.md) before creating an RC tag.

## Development Principles

### 1. Core before country specifics

Do not place `HIPAA`, `NHS`, `ABHA`, country payer codes, or national identifiers directly inside generic `Patient`, `Encounter`, `Order`, or `Invoice` domain objects.

### 2. Modular monolith before microservices

Version 1 starts with one Laravel deployable split into explicit domains. Services may be extracted later only when scale, independent lifecycle, regulatory isolation, or operational ownership justifies it.

### 3. FHIR is an interoperability model, not the database schema

FHIR mappings belong in the interoperability layer. Internal domain models should preserve workflow state, provenance, audit metadata, permissions, and application behavior without being constrained to an external exchange representation.

### 4. Privacy and audit are platform capabilities

Patient access, consent, emergency access, exports, and disclosure decisions must not be bolted on after clinical modules are complete.

### 5. Clinical safety beats convenience

No silent mutations of signed clinical records. No ambiguous patient context for high-risk actions. No dropped integration messages. No autonomous high-impact AI decisions.

### 6. Configuration over product forks

Country and organization differences should be implemented through contracts, policy engines, adapters, configuration, terminology mappings, and workflow definitions.

## Branching

Recommended baseline:

```text
main
feature/<short-description>
fix/<short-description>
chore/<short-description>
```

Keep `main` releasable.

For larger teams, protect `main` with:

- required pull requests;
- required CI checks;
- required reviews;
- signed commits if organizational policy requires them;
- secret scanning;
- dependency review.

## Commit Convention

Use Conventional Commit-style messages:

```text
feat(patient): add patient identity aggregate
fix(auth): reject expired organization membership
test(consent): cover revoked consent decisions
docs(architecture): add country-profile ADR
chore(ci): add backend static analysis
```

## Documentation

Start with [docs/README.md](docs/README.md), the maintained documentation index. Key references include:

- [CODING_GUIDELINES.md](CODING_GUIDELINES.md) — mandatory coding, clinical-safety, GraphQL-boundary and documentation rules.
- [ARCHITECTURE.md](ARCHITECTURE.md) — system boundaries, modules, security, data and integration architecture.
- [ROADMAP.md](ROADMAP.md) — product progression beyond version 1.
- [version_1_milestones.md](version_1_milestones.md) — implementation sequence for the first production-capable foundation.
- [version_2_milestones.md](version_2_milestones.md) — planned care-delivery expansion milestones for Version 2.
- [MILESTONES_TESTING.md](MILESTONES_TESTING.md) — milestone gates, testing rules, and release evidence.
- [docs/releases/v1.0.0-rc.1.md](docs/releases/v1.0.0-rc.1.md) — release notes for the tagged Version 1 RC1 baseline.
- [docs/ADR_TEMPLATE.md](docs/ADR_TEMPLATE.md) — architecture decision record template.
- [docs/TECH_BASELINE.md](docs/TECH_BASELINE.md) — framework/runtime baseline and upgrade policy.
- [docs/DOCKER_DEVELOPMENT.md](docs/DOCKER_DEVELOPMENT.md) — local Docker architecture and commands.
- [docs/GRAPHQL_BACKEND.md](docs/GRAPHQL_BACKEND.md) — GraphQL server, resolver, security, and testing conventions.
- [docs/IDENTITY_AUTHORIZATION.md](docs/IDENTITY_AUTHORIZATION.md) — session authentication, memberships, role/facility authorization, and ABAC extension points.
- [docs/AUDIT_FOUNDATION.md](docs/AUDIT_FOUNDATION.md) — structured audit evidence, denial durability, and restricted audit viewing.
- [docs/PATIENT_IDENTITY_MPI.md](docs/PATIENT_IDENTITY_MPI.md) — patient identity, MPI duplicate review, secure search, patient audit context, and local demo seeding.
- [docs/CONSENT_PRIVACY.md](docs/CONSENT_PRIVACY.md) — consent lifecycle, privacy decisions, break-glass and country-policy extension.
- [docs/SCHEDULING_FOUNDATION.md](docs/SCHEDULING_FOUNDATION.md) — booking, conflict, lifecycle, waitlist, timezone and idempotency behavior.
- [docs/ENCOUNTER_FOUNDATION.md](docs/ENCOUNTER_FOUNDATION.md) — encounter binding, lifecycle, privacy and audit behavior.
- [docs/CLINICAL_RECORD_FOUNDATION.md](docs/CLINICAL_RECORD_FOUNDATION.md) — longitudinal clinical record, provenance, signed-note integrity and timeline.
- [docs/TERMINOLOGY_INTEROPERABILITY.md](docs/TERMINOLOGY_INTEROPERABILITY.md) — versioned terminology concepts, mapping contracts, FHIR boundary, and correlation behavior.
- [docs/FRONTEND_WORKSPACE_NAVIGATION.md](docs/FRONTEND_WORKSPACE_NAVIGATION.md) — current Angular routes and which workspaces are functional versus intentionally deferred.
- [docs/RELEASE_HARDENING.md](docs/RELEASE_HARDENING.md) — V1 release-candidate security, migration, restore, performance, accessibility, and production-readiness gates.
- [docs/V1_RELEASE_CANDIDATE_EVIDENCE.md](docs/V1_RELEASE_CANDIDATE_EVIDENCE.md) — evidence template for the exact release-candidate commit.
- [SECURITY.md](SECURITY.md) — repository security expectations and release blockers.

## Initial Definition of Ready

A feature is ready for implementation when it has:

- clear user or operational outcome;
- domain ownership;
- security and privacy impact identified;
- country-specific behavior separated from generic behavior;
- API and event implications considered;
- acceptance criteria;
- test plan;
- migration implications;
- audit requirements;
- rollback or failure behavior for risky changes.

## Initial Definition of Done

A change is complete when:

- implementation is reviewed;
- tests pass;
- authorization has been tested positively and negatively;
- sensitive-data handling has been reviewed;
- migrations are backward-safe or have a documented rollout;
- API contracts are documented;
- relevant audit events exist;
- relevant documentation is updated;
- no secret or sensitive patient fixture is committed;
- CI passes;
- milestone evidence is recorded when the work closes a milestone.

## Security Notice

Never commit:

- production credentials;
- API keys;
- private certificates;
- patient data;
- real medical records;
- unredacted support exports;
- database dumps containing health information.

Development and test data must be synthetic.

## License

Choose the final license before making the repository public. Healthcare product source may require a commercial/private licensing strategy rather than an open-source license.
