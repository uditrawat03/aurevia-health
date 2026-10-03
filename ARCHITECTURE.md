# Architecture

## 1. Purpose

This document defines the initial technical architecture for the Aurevia Health.

The system is intended to support multi-facility healthcare organizations while keeping the clinical core independent from any one country's healthcare regulations, payer model, terminology, national identifiers, or public infrastructure.

The first implementation may target one country deeply, but country-specific implementation must not become the platform architecture.

## 2. Architecture Goals

The platform should:

1. preserve patient safety and record integrity;
2. enforce authorization server-side;
3. support strong auditability and provenance;
4. support multiple healthcare organizations and facilities;
5. keep country-specific rules isolated;
6. support interoperable healthcare exchange without making FHIR the internal database model;
7. allow gradual extraction of services without beginning with distributed-system complexity;
8. provide reliable asynchronous processing;
9. support regional deployment and data-residency requirements;
10. create a governed base for future AI features.

## 3. Baseline Technology

### Backend

- Laravel 13.x
- PHP 8.5 recommended
- Laravel Sanctum for first-party SPA authentication
- Laravel queues
- Laravel Horizon with Redis
- PostgreSQL
- Redis

### Frontend

- Angular 22.x
- standalone APIs
- strict TypeScript
- zoneless change detection
- Angular Router
- typed GraphQL client/transport boundary for first-party application data
- Angular HttpClient only inside that transport boundary or for approved protocol exceptions
- signals where appropriate
- Vitest for unit tests
- SCSS

### Infrastructure

- Docker Compose for PostgreSQL and Redis, with an optional `app` profile for Laravel, Horizon, and Angular
- named container dependency volumes for Laravel `vendor` and Angular `node_modules`
- GitHub Actions for baseline CI
- object storage introduced before document-heavy clinical modules
- search engine introduced only when authorization-safe indexing requirements are defined
- analytics warehouse introduced separately from the production transactional database

### Local Docker Topology

Local development separates persistent infrastructure from application containers. PostgreSQL and Redis may start independently; the optional `app` profile adds Laravel, Horizon, and Angular. Source code is bind-mounted while Linux dependency trees remain in named volumes.

```text
Browser :4200 -> Angular -> /graphql proxy -> Laravel :8000
                                      |-> PostgreSQL :5432
                                      |-> Redis :6379 / Horizon
```

These development images are not production images.

## 4. System Context

```text
Patients / Caregivers
        |
Clinical / Administrative Users
        |
        v
+-------------------------------+
|          Angular Web          |
+---------------+---------------+
                |
                | HTTPS / GraphQL
                v
+-------------------------------+
|         Laravel API           |
|  Application + Domain Layer   |
+----------+----------+---------+
           |          |
           |          +--------------------+
           |                               |
           v                               v
+------------------+             +-------------------+
|   PostgreSQL     |             |      Redis        |
| transactional DB |             | cache / queues    |
+------------------+             +-------------------+
           |
           +-------------------------------------------+
                                                       |
                                                       v
                                            +----------------------+
                                            | Integration Boundary |
                                            | FHIR / HL7 / DICOM    |
                                            | EDI / REST / Events   |
                                            +----------------------+
                                                       |
                                                       v
                                            External health systems
```

## 5. Architectural Style

Version 1 uses a **modular monolith with explicit domain boundaries and event-driven integration inside the application**.

This is deliberate. Healthcare systems have complex transactional workflows. Starting with many services would introduce distributed transactions, cross-service authorization complexity, more network failure modes, duplicated reference data, harder development environments, and premature operational cost.

Service extraction is allowed later when measurable needs justify it. Potential extraction candidates include interoperability, document processing, notifications, analytics ingestion, AI gateway, and high-volume terminology services.

## 6. Repository Application Boundaries

```text
apps/
├── api/   Laravel
└── web/   Angular
```

Do not create separate country applications such as `api-us`, `api-uk`, or `api-india`. Use one global core plus country-profile implementations.

## 7. Laravel Domain Structure

```text
apps/api/app/
├── Domains/
│   ├── Organization/
│   ├── Identity/
│   ├── Patient/
│   ├── Consent/
│   ├── Scheduling/
│   ├── Encounter/
│   ├── Clinical/
│   ├── Orders/
│   ├── Medication/
│   ├── Laboratory/
│   ├── Imaging/
│   ├── Surgery/
│   ├── Pharmacy/
│   ├── Billing/
│   ├── Coverage/
│   ├── Authorization/
│   ├── HIM/
│   ├── Inventory/
│   ├── Workforce/
│   ├── Terminology/
│   ├── Interoperability/
│   ├── Workflow/
│   ├── Notification/
│   ├── Audit/
│   ├── Analytics/
│   └── AI/
├── CountryProfiles/
├── Application/
└── Infrastructure/
```

A domain owns its entities, value objects, policies, services, commands, queries, events, and repository contracts. Framework-specific infrastructure should not become the domain model.

## 8. Country Profile Architecture

Country-specific functionality is implemented through contracts.

```php
interface CountryHealthcareProfile
{
    public function privacyProfile(): PrivacyProfile;
    public function terminologyProfile(): TerminologyProfile;
    public function billingProfile(): BillingProfile;
    public function prescribingProfile(): PrescribingProfile;
    public function interoperabilityProfile(): InteroperabilityProfile;
    public function reportingProfile(): ReportingProfile;
}
```

Example:

```text
CountryProfiles/
├── Core/
├── US/
├── UK/
└── IN/
```

A country profile may provide validators, policy rules, terminology adapters, identifier formats, billing adapters, prescribing adapters, government API integrations, regulatory reports, and interoperability mappings.

## 9. Configuration Hierarchy

```text
Global defaults
      ↓
Country profile
      ↓
Organization
      ↓
Facility
      ↓
Department
```

Configuration must distinguish overridable operational preferences from regulatory/security controls that lower scopes cannot weaken. Safety- and compliance-relevant configuration changes should be auditable.

## 10. Identity and Authentication

Support progression toward local development identities, enterprise OIDC, SAML where needed, MFA, WebAuthn/passkeys, emergency account controls, and session revocation.

The first-party Angular application should use Laravel Sanctum's stateful session/cookie approach. Do not store long-lived first-party access tokens in browser local storage.

External integrations use separate machine identities, scoped credentials, rotation, and audit.

## 11. Authorization Model

Use layered authorization:

```text
RBAC
 +
ABAC
 +
organization/facility scope
 +
patient relationship
 +
purpose
 +
consent
 +
context
```

An authorization decision may consider actor, role, organization, facility, department, patient, encounter, care-team relationship, resource type, data sensitivity, action, purpose, consent, emergency override, and jurisdiction.

Angular route guards are UX controls only. Laravel is authoritative.

## 12. Patient Identity

Use internal UUID/ULID keys. Never use national IDs, insurance IDs, NHS Number, ABHA Number, or organization MRNs as primary database keys.

External identifiers should include system/issuer, value, use, status, validity period, and provenance.

Patient merge/unmerge must be explicit and auditable.

## 13. Clinical Record Integrity

Clinical records require stronger semantics than ordinary CRUD.

Signed/finalized documentation uses correction or addendum workflows rather than silent overwrite. Preserve distinct timestamps such as event time, observed time, recorded time, signed time, and corrected time where applicable.

## 14. Audit Architecture

Separate application logs, security logs, clinical/privacy audit, and integration logs.

Protected actions should create structured events containing actor, organization, patient/resource context, action, purpose/context where relevant, outcome, correlation ID, and timestamp.

Audit storage should become append-oriented and tamper-evident as the system matures.

## 15. Consent and Privacy

Consent is not a boolean. Model subject, data category, purpose, recipient/class, jurisdiction, effective period, restrictions, revocation, and provenance.

Privacy decisions should be evaluated through policy services so country profiles can alter behavior without rewriting clinical modules.

## 16. API Architecture

Use **GraphQL as the default first-party application API boundary**. The canonical product endpoint is `/graphql`.

Guidelines:

- Angular feature code must not call ordinary domain REST endpoints directly;
- reads use GraphQL queries and state changes use GraphQL mutations;
- subscriptions are introduced only when a workflow genuinely requires server-pushed updates;
- schema inputs and payloads are explicitly typed; generic JSON fields require a documented exception;
- resolvers are thin boundary adapters and contain no business rules, Eloquent chains, or persistence queries;
- server-side authorization applies to every protected query, mutation, object, and sensitive field;
- query planning/data loading must prevent N+1 database access;
- GraphQL errors are structured, preserve correlation IDs, and do not leak sensitive internals;
- schema evolution is additive by default; breaking fields are deprecated before removal;
- retried mutations with external side effects require idempotency design;
- frontend operations are centralized and typed rather than assembled as ad hoc HTTP calls;
- request correlation IDs;
- audit semantics for protected operations.

GraphQL does not replace protocol-specific HTTP boundaries. Health/readiness endpoints, Sanctum CSRF/session bootstrap, OAuth/OIDC/SAML callbacks, inbound webhooks, signed object-storage handoffs, metrics where required, and FHIR/HL7/DICOM/EDI/partner REST adapters remain separate explicit boundaries. These exceptions must not become a backdoor for ordinary application CRUD.

## 17. Domain Events

Important state changes emit domain events, for example:

```text
PatientRegistered
PatientMerged
AppointmentScheduled
EncounterStarted
PatientAdmitted
PatientTransferred
OrderPlaced
ResultPublished
MedicationAdministered
ConsentChanged
PatientDischarged
InvoiceIssued
ClaimSubmitted
```

Use after-commit dispatch for side effects that must only occur after successful transactions.

## 18. Queue Architecture

Redis is the initial asynchronous backend.

Potential queues:

```text
critical
default
integrations
notifications
exports
analytics
```

Rules:

- separate critical work from slow bulk work;
- minimize sensitive data in job payloads;
- define retry behavior;
- make failed jobs visible;
- design idempotency for retried external effects.

Laravel Horizon provides Redis queue visibility.

## 19. Interoperability Architecture

FHIR is an exchange representation, not the internal persistence model.

```text
Internal domain
      ↓
mapping / anti-corruption layer
      ↓
country or partner profile
      ↓
FHIR / HL7 / DICOM / EDI / REST
```

Message lifecycle:

```text
Received
Validated
Queued
Processing
Completed
Acknowledged

or

Failed
Retrying
Dead Letter
Manual Review
```

Every inbound/outbound message should carry a correlation ID.

## 20. Terminology

Create a terminology boundary before large clinical modules.

A concept may map to SNOMED CT, LOINC, ICD variants, medication vocabularies, national billing codes, and local organization codes. Mappings need versions and effective periods.

## 21. Frontend Architecture

```text
apps/web/src/app/
├── core/
├── shared/
├── design-system/
└── features/
    ├── shell/
    ├── patient/
    ├── registration/
    ├── scheduling/
    ├── encounter/
    ├── emergency/
    ├── clinical/
    ├── nursing/
    ├── pharmacy/
    ├── laboratory/
    ├── imaging/
    ├── billing/
    ├── command-center/
    └── administration/
```

Use lazy-loaded features, typed GraphQL operation boundaries, local state by default, Angular signals where appropriate, and reusable healthcare UI primitives. `HttpClient` belongs inside the GraphQL transport/client layer or an approved protocol exception, not directly inside feature components for domain CRUD.

## 22. Patient Context UX

Clinical screens should maintain visible patient context such as name, preferred name, DOB, relevant patient identifier, encounter, location, and critical allergies/warnings.

High-risk actions should add wrong-patient prevention checks.

## 23. Data Storage

**PostgreSQL:** transactional source of truth.  
**Redis:** cache, locks, queues, rate-limit state.  
**Object storage:** documents, exports, reports, attachments before document-heavy production workflows.  
**Search:** introduced only after authorization-safe indexing is designed.  
**Analytics:** separated from the production clinical database.

## 24. Data Classification

Minimum classes:

- public;
- internal;
- confidential;
- sensitive healthcare data;
- secrets.

Sensitive healthcare data must not casually appear in debug logs, error-reporting payloads, analytics events, source-control fixtures, or URL query strings.

## 25. Encryption

Use TLS in transit. Encrypt production databases, object storage, backups, search/analytics stores containing sensitive data, and use managed key management in production.

## 26. Observability

Use correlation IDs across API, queues, and integrations.

Track HTTP latency/errors, database latency, queue depth/wait, integration failures, authentication failures, authorization denials, application errors, and health status while minimizing sensitive data.

## 27. AI Boundary

No clinical feature calls arbitrary AI providers directly.

```text
Application
   ↓
AI Gateway
   ├── authorization
   ├── privacy policy
   ├── data minimization
   ├── model registry
   ├── prompt/version registry
   ├── retrieval
   ├── output validation
   └── audit
        ↓
Model provider
```

Clinical AI remains assistive unless separately reviewed.

## 28. Deployment Evolution

Start with one regional Laravel/Angular/PostgreSQL/Redis deployment. Add object storage, WAF, managed KMS/secrets, centralized logs, and managed infrastructure as production needs mature.

For regional deployments, keep sensitive data in the required region by default and do not introduce cross-border replication casually.

## 29. Architecture Decision Records

Initial ADRs:

```text
ADR-001 Modular Monolith
ADR-002 Country Profile Architecture
ADR-003 Patient Identifier Strategy
ADR-004 Authorization Model
ADR-005 Consent Architecture
ADR-006 FHIR Is Not Internal Domain Model
ADR-007 Event and Queue Semantics
ADR-008 Audit Architecture
ADR-009 Tenant Isolation
ADR-010 AI Gateway
```

Use `docs/ADR_TEMPLATE.md`.

## 30. Architectural Fitness Rules

Reject changes that:

- place country-specific fields directly in generic domain objects without justification;
- authorize only in Angular;
- expose sensitive data in logs;
- silently mutate signed clinical records;
- bypass required audit;
- use exchange standards as internal persistence merely for convenience;
- couple domains through uncontrolled table access;
- add ordinary product REST endpoints or direct Angular `/api/...` calls instead of the GraphQL application boundary;
- introduce critical synchronous external calls without timeout/failure design;
- call AI providers outside the AI boundary;
- put real patient data in tests.

## 31. Initial Quality Attributes

| Attribute | Initial target |
|---|---|
| Typical API | P95 < 300 ms |
| Search | P95 < 1 s when introduced |
| Critical async event | < 5 s under expected load |
| Clinical availability | >= 99.95% once formal SLA exists |
| Audit coverage | 100% of identified protected operations |
| Integration reliability | no silent loss of accepted messages |
| Accessibility | design toward WCAG 2.1 AA |

These are engineering targets, not contractual SLAs until formally adopted.
