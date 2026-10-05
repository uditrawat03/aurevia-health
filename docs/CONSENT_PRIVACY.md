# Consent and Privacy Boundary

## Purpose

The Version 1 consent/privacy boundary adds explicit patient privacy decisions on top of Aurevia Health's authentication, organization membership, facility scope, patient identity, and audit foundations.

The key rule is:

```text
Authentication
    ↓
Organization / role permission
    ↓
Facility scope
    ↓
Patient privacy decision
    ├── effective consent
    ├── temporary break-glass
    └── country-profile tightening
    ↓
Patient record access
```

Consent does not replace RBAC or facility authorization. A request must satisfy both authorization layers.

## Consent Aggregate

`patient_consents` stores a durable grant and any later revocation on the same record:

- patient and organization;
- optional facility scope;
- data category;
- purpose of use;
- recipient class;
- granting user;
- effective-from / effective-until;
- active or revoked status;
- revocation timestamp, actor, and reason.

Revocation does not delete the original grant. The grant actor, scope, effective period, and creation timestamp remain available as evidence.

Initial generic categories:

```text
DEMOGRAPHICS
CLINICAL
BILLING
RESEARCH
```

Initial purposes:

```text
TREATMENT
CARE_COORDINATION
OPERATIONS
BILLING
RESEARCH
```

Initial recipient classes:

```text
CARE_TEAM
ORGANIZATION_STAFF
EXTERNAL_PROVIDER
RESEARCH_TEAM
```

These are country-neutral policy attributes rather than regulatory labels.

## Effective Consent

A consent permits a privacy decision only when all of the following match:

- organization;
- patient;
- requested data category;
- requested purpose;
- requested recipient class;
- facility scope, where organization-wide consent uses a null facility;
- status is `ACTIVE`;
- effective-from is not in the future;
- effective-until is absent or still in the future.

A revoked or expired consent does not authorize access.

## Patient Search vs Patient Record

The patient directory remains a minimum patient-locator boundary so authorized healthcare staff can locate a subject and manage consent even when a record is not currently readable.

`PatientSearchResult.items` therefore exposes only:

- internal patient ID;
- organization/facility context;
- names;
- date of birth;
- sex at birth;
- typed identifiers.

Contacts, addresses, relationships, and future clinical fields are not queryable from the search projection. The full `patient(...)` query passes the privacy decision boundary.

This separation prevents a client from bypassing consent by requesting sensitive patient fields through search.

## Break-Glass

Break-glass is an emergency treatment override, not a generic privacy bypass.

Rules in V1-M5:

- caller must have `BREAK_GLASS_PATIENT_ACCESS`;
- normal organization/facility authorization still applies;
- only `TREATMENT` purpose is accepted;
- a specific reason of at least 12 characters is mandatory;
- each grant is scoped to actor, organization, patient, and facility;
- access expires after 15 minutes;
- the durable break-glass record stores the reason;
- structured audit evidence references the break-glass record.

A break-glass record does not mutate or reactivate revoked consent.

## Country Profile Extension

`CountryProfilePrivacyPolicyRegistry` receives the organization's server-pinned country-profile code and version after the core consent/break-glass decision.

Country-specific policy may preserve or tighten the core decision. `PrivacyAuthorizationService` explicitly rejects an implementation that attempts to turn a core denial into an allow.

The default registry adds no jurisdiction-specific rules. Future India, UK, US, or other profile implementations can add restrictions without embedding regulatory names or rules in the generic patient aggregate.

## Permissions

Initial privacy permissions:

| Role | View consent | Manage consent | Break-glass |
|---|---:|---:|---:|
| OWNER | yes | yes | yes |
| ADMIN | yes | yes | yes |
| CLINICIAN | yes | no | yes |
| STAFF | yes | yes | no |
| VIEWER | no | no | no |

Organization-wide consent grants require all-facility consent-management authority. Facility-scoped grants may be managed by an authorized role inside that facility.

## GraphQL Operations

Queries:

```text
patientConsents
patientPrivacyDecision
```

Mutations:

```text
grantPatientConsent
revokePatientConsent
activateBreakGlass
```

Patient reads continue through the existing `patient(...)` query; that resolver now executes both organization authorization and privacy authorization.

## Browser Testing

Local demo seeding creates two privacy scenarios:

- **Asha Mehta / DEMO-0001** — active demographics/treatment/care-team consent.
- **Rahil Khan / DEMO-0002** — the same synthetic consent is revoked.

Use the patient directory **Privacy** action to open the consent workspace without requiring the full patient record first.

For Rahil:

1. inspect the revoked consent and denied treatment decision;
2. grant a new consent and confirm the decision becomes allowed; or
3. activate break-glass with a specific emergency reason and confirm temporary access.

For newly registered patients, the registration workflow intentionally does not imply consent. Use **Manage consent before opening record**.

## Verification

With the Docker application profile running:

```powershell
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan db:seed --force

docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit

docker exec aurevia-health-web npm test -- --watch=false
docker exec aurevia-health-web npm run build

git diff --check
```

Regression coverage must continue to verify consent grant/revocation, denied record access, privacy-policy enforcement, and time-bounded break-glass behavior. The browser flow is a useful local demonstration, while the Laravel policy boundary remains authoritative.
