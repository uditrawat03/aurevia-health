# Patient Identity / MPI Foundation

## Purpose

V1-M4 establishes Aurevia Health's country-neutral patient identity foundation. Patients receive internal ULID primary keys while MRNs, national identifiers, insurance identifiers, and other external identifiers remain typed identifiers. No external or national identifier becomes a database primary key.

## Patient Record

The initial patient aggregate contains:

- organization ownership and registration facility context;
- given, middle, family, and preferred names;
- date of birth and sex at birth;
- multiple typed identifiers;
- phone/email contacts;
- addresses;
- relationship / guardian / emergency-contact records.

All Version 1 fixtures and seed records are synthetic.

## Master Patient Index Candidate Detection

Registration performs duplicate-candidate detection before the new record is persisted. Candidate signals are deliberately explainable:

| Signal | Confidence |
|---|---:|
| exact normalized identifier | 100 |
| exact given name + family name + date of birth | 90 |
| family name + date of birth | 70 |

A candidate is evidence for human review, not permission to merge records. Registration never auto-merges patients.

The database intentionally does not make an external identifier globally unique across patient rows. Real MPI cleanup must be capable of representing conflicting or duplicated identifiers long enough to review them safely.

## Merge Review

`requestPatientMergeReview` creates a `PENDING` review between two patients in the same organization. V1-M4 does **not** execute a merge, approve a review, or delete either patient. Merge execution is intentionally deferred until provenance, downstream references, and correction policy are mature enough to make the operation safe.

Merge-review requests require organization-wide `REVIEW_PATIENT_MERGES` authorization and are currently limited to `OWNER` and `ADMIN`.

## Authorization

Patient access is server enforced.

- `OWNER` / `ADMIN`: patient view, registration, and merge-review request.
- `CLINICIAN` / `STAFF`: patient view and registration within their facility scope.
- `VIEWER`: no patient permission in V1-M4.
- facility-scoped users must search within an allowed facility.
- organization-wide search requires all-facility authority.
- patient reads evaluate the patient's registration-facility scope.
- cross-organization search/read is denied.

Version 1 encounter workflows and the consent/break-glass privacy boundary now extend this policy without replacing organization/role/facility authorization. Richer care-team relationship and purpose-specific ABAC remain future extensions.

## GraphQL Boundary

Queries:

```graphql
patient(organizationId: ID!, id: ID!): Patient!
patients(input: PatientSearchInput!): PatientSearchResult!
```

Mutations:

```graphql
registerPatient(input: RegisterPatientInput!): RegisterPatientPayload!
requestPatientMergeReview(input: RequestPatientMergeReviewInput!): PatientMergeReview!
```

`registerPatient` returns both the newly registered patient and duplicate candidates. Candidate presence never changes the patient into another patient automatically.

## Audit

Patient authorization decisions use `PATIENT` resource context. Specific patient reads carry the patient ID into the audit event. Successful registration additionally writes `REGISTER_PATIENT` evidence with the created patient ID. Merge-review requests record the target patient plus the review resource ID.

Security-relevant denied access continues to use the durable denial flush introduced in V1-M3.

## Angular Patient Context

`PatientContextService` and `PatientContextBannerComponent` provide a reusable wrong-patient-safety foundation. The implemented scheduling, encounter and clinical workspaces reuse patient context; the clinical record keeps the selected patient visible while Laravel independently revalidates patient/encounter binding on writes.

## Local Development Seeder

For local development only:

```powershell
docker exec aurevia-health-api php artisan db:seed
```

The local seeder is idempotent and creates a synthetic owner workspace, one facility, and two synthetic patients.

Login:

```text
Email:    owner@aurevia.local
Password: AureviaLocal123!
```

These are intentionally public **local-development credentials**. `DatabaseSeeder` does not run this demo seed outside the Laravel `local` environment. Never reuse the password or seed identity in deployed environments.

## Verification

```powershell
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan db:seed
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit

docker exec aurevia-health-web npm test -- --watch=false
docker exec aurevia-health-web npm run build

git diff --check
```

The M4 gate includes registration, duplicate-candidate behavior, secure search, wrong-tenant denial, facility-scope denial, patient audit evidence, merge-review non-destructive behavior, frontend patient-context tests, and synthetic-only seed data.
