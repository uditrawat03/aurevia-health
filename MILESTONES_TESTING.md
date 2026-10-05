# Milestone Testing and Release Gates

## Purpose

This document defines the minimum verification evidence required before a milestone is considered complete.

Healthcare software should not use "it works on my machine" as a completion criterion. Each milestone should establish evidence for functionality, authorization, privacy, failure behavior, and regression safety appropriate to its scope.

## Current Version 1 Release-Candidate Evidence

The Version 1 hardening work currently has the following verified engineering evidence:

- Lighthouse schema validation: **PASS**;
- backend suite: **84 tests / 530 assertions**;
- complete synthetic V1 GraphQL workflow: **PASS**;
- Composer audit: **no security advisories**;
- Angular unit suite: **6 files / 9 tests**;
- Angular production build: **PASS**;
- production npm audit: **0 vulnerabilities**;
- clean-database migration rehearsal: **13/13 migrations**;
- local backup/restore rehearsal: **PASS**;
- local application-layer performance baseline: highest measured P95 **33.88 ms** across the five recorded GraphQL reads;
- GitHub Actions Quality workflow: **PASS** on closeout commit `9c425e0`.

The final tag candidate must repeat CI after any documentation or code commit that changes the SHA. Manual accessibility verification remains a separate release-evidence item; automated/source-level accessibility hardening does not replace it.

## 1. Testing Layers

| Layer | Purpose |
|---|---|
| Unit | Domain behavior and pure logic |
| Feature/API | Laravel HTTP/use-case behavior |
| Integration | Database, Redis, queues, storage, adapters |
| Contract | API/event/interoperability contracts |
| Frontend unit | Angular components/services/state |
| End-to-end | User workflow across web and API |
| Security | Authentication, authorization, tenant isolation |
| Accessibility | Keyboard, semantics, focus and automated checks |
| Performance | Latency/concurrency/workload behavior |
| Resilience | Retry, external failure and recovery |
| Migration | Safe schema evolution |
| Clinical safety | Wrong-patient, correction and high-risk scenarios |

## 2. Mandatory Rules for Every Milestone

A milestone cannot close until:

- implementation is reviewed;
- relevant backend tests pass;
- relevant frontend tests pass;
- new protected resources have authorization tests;
- sensitive operations have audit tests;
- cross-tenant access is tested where applicable;
- migrations are exercised from the prior milestone state;
- documentation is updated;
- only synthetic healthcare data is used;
- CI passes;
- known limitations are documented.

## 3. Backend Baseline

```bash
cd apps/api
php artisan test
```

Recommended later additions include Laravel Pint, PHPStan/Larastan, and mutation testing for selected safety-critical logic. Tool adoption must include CI ownership and failure policy.

## 4. Frontend Baseline

Angular 22 uses Vitest by default for new projects.

```bash
cd apps/web
npm test -- --watch=false
npm run build
```

Later add browser/component tests, Playwright end-to-end coverage, and automated accessibility tooling.

## 5. Test Data Policy

Use synthetic data only.

Never commit real patient data, medical-record numbers, exported clinical notes, screenshots containing patient data, production insurance identifiers, production database dumps, access tokens, or secrets.

Synthetic fixtures should still include realistic complexity such as multiple facilities, near-duplicate patients, dependents/guardians, unusual names, multiple identifiers, timezone boundaries, cancelled encounters, and corrected records.

## 6. Authorization Test Matrix

For each protected resource, test:

```text
unauthenticated
authenticated without permission
correct role / correct organization
correct role / wrong organization
allowed facility
wrong facility
revoked membership
```

When relevant:

```text
care-team relationship exists
care-team relationship absent
consent permits
consent denies
emergency access active
emergency access expired
```

Do not test only happy paths.

## 7. Audit Test Matrix

For each protected operation identify expected actor, patient/resource, action, result, correlation ID, timestamp, and purpose/context where required.

Test `ALLOWED` and security-relevant `DENIED` outcomes.

Audit failure behavior must be explicit for safety-critical operations.

## 8. Tenant Isolation

Maintain at least two organizations in tests and prove organization A cannot retrieve, search, update, attach resources to, or inspect audit data belonging to organization B.

An identifier is never authorization.

## 9. Database Migration Gate

Before release candidate:

1. start from prior tagged schema;
2. seed representative synthetic data;
3. run migrations;
4. run smoke tests;
5. inspect constraints/indexes;
6. test rollback where it is intentionally supported;
7. document irreversible migrations.

Avoid long production table locks without a deployment plan.

## 10. Queue and Async Tests

Test transaction-commit timing, retry behavior, idempotency, duplicate delivery, timeout, poison messages, and failed-job visibility.

For external integrations:

```text
accepted message != successfully delivered message
```

Persist enough state to reconcile delivery.

## 11. GraphQL and Protocol Contract Gate

The first-party application contract is GraphQL at `/graphql`. It requires typed inputs/payloads, request validation, stable errors with correlation IDs, server-side authorization, pagination behavior where relevant, idempotency semantics for retryable writes, and additive schema evolution/deprecation for breaking changes.

Protocol-specific exceptions such as health/readiness, Sanctum bootstrap, webhooks and future FHIR/HL7/DICOM/partner adapters must remain explicit and must not become parallel first-party CRUD APIs.

## 12. Frontend Safety Gate

Clinical UI changes should verify:

- patient context remains visible where required;
- stale state is handled;
- destructive operations require appropriate confirmation;
- disabled UI is not the only security control;
- errors do not expose sensitive payloads;
- retry/loading states do not duplicate writes;
- keyboard interaction works;
- focus behavior works after dialogs/errors.

## 13. Accessibility Gate

Initial direction is WCAG 2.1 AA.

At minimum verify semantic structure, labels, keyboard navigation, visible focus, accessible dialogs, associated errors, contrast, and screen-reader names.

Automated tooling does not replace manual keyboard/screen-reader review for critical flows.

## 14. Performance Gate

Use realistic datasets.

| Area | Initial target |
|---|---|
| Typical API | P95 < 300 ms |
| Search | P95 < 1 s once introduced |
| Critical async event | < 5 s under expected load |
| Main clinical shell | usable in roughly 2 s on expected enterprise network |

Temporary exceptions require a documented reason and remediation.

## 15. Security Gate

Before each release candidate:

- dependency vulnerabilities reviewed;
- secret scan clean;
- production debug disabled;
- security headers reviewed;
- CORS/stateful-domain config reviewed;
- production cookie attributes secure;
- negative authorization tests pass;
- tenant-isolation tests pass;
- uploads reviewed where relevant;
- abuse-sensitive endpoints rate limited;
- admin endpoints explicitly protected.

Formal threat modeling and penetration testing are required before real production healthcare use.

## 16. Clinical Record Integrity Gate

When documentation exists, test draft edit, signing, signed-note immutability, addendum, correction, attribution, timestamps, patient/encounter association, and unauthorized edit denial.

Never rely on ordinary CRUD update semantics for finalized clinical notes.

## 17. Wrong-Patient Scenario

Before Version 1 release candidate:

1. open Patient A;
2. navigate to another workflow;
3. switch to Patient B;
4. verify visible context updates;
5. attempt a stale Patient A write;
6. reject inconsistent/stale context where applicable;
7. record relevant audit evidence.

## 18. Consent Scenario

Test active consent, revocation, policy reevaluation, emergency override, required reason, expiry, and audit.

Country-specific consent rules belong in country conformance suites.

## 19. Milestone Evidence

Record for each completed milestone:

```text
Milestone:
Commit/tag:
Date:
Owner:
Backend tests:
Frontend tests:
Security tests:
Migration test:
Manual verification:
Known limitations:
Documentation changed:
```

## 20. Version 1 Gates

### V1-M0

Bootstrap, API, Angular, PostgreSQL, Redis, backend tests, frontend tests, CI.

### V1-M1

Organization hierarchy, country-profile resolution, configuration precedence, isolation.

### V1-M2

Authentication, logout/revocation, authorization happy/negative paths, tenant isolation.

### V1-M3

Protected read/write audit, relevant denial audit, correlation IDs.

### V1-M4

Registration, duplicate candidate behavior, secure search, wrong-tenant denial.

### V1-M5

Consent, revocation, policy extension, break-glass audit.

### V1-M6

Scheduling conflicts, reschedule/cancel, timezone handling, retry/idempotency.

### V1-M7

Encounter state transitions, invalid transition rejection, patient/org binding, audit.

### V1-M8

Signed-note integrity, correction/addendum, provenance, timeline, wrong-patient scenario.

### V1-M9

Terminology versioning, mapping extension, interoperability independence.

### V1-M10

Full V1 end-to-end flow, backup/restore, migration rehearsal, security review, performance and accessibility baselines, release documentation.

## 21. Release Blocking Failures

Block release for:

- cross-tenant patient access;
- required protected access that is unaudited;
- silent loss of accepted integration data;
- silent overwrite of signed clinical documentation;
- committed secrets;
- real patient data in tests;
- known authorization bypass;
- migration that corrupts patient/clinical data;
- failed restoration of required backups;
- unresolved high-severity security vulnerability without an accepted risk decision.

## 22. Test Philosophy

The goal is evidence that unsafe states are rejected, unauthorized access fails, privacy policy is applied, important actions are traceable, retries do not duplicate harmful effects, history is preserved, upgrades protect records, and failure is visible/recoverable.
