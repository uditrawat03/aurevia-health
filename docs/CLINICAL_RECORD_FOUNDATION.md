# Clinical Record Foundation

## Scope

V1-M8 establishes Aurevia Health's first country-neutral longitudinal clinical record boundary. It is intentionally smaller than a complete EHR, but it preserves the invariants needed before terminology, orders/results, diagnostics, medication, inpatient, or interoperability work expands the clinical domain.

Implemented capabilities:

- problem list entries with lifecycle status and optional terminology metadata;
- allergy entries with reaction, severity, clinical status, and verification status;
- numeric or text clinical observations with effective time and units;
- encounter-bound clinical notes with `DRAFT` and `SIGNED` lifecycle;
- append-only signed-note addenda and corrections;
- patient timeline aggregation across encounter and clinical events;
- patient, organization, facility, encounter, actor, and timestamp provenance;
- explicit clinical-record permissions and audit vocabulary;
- clinical privacy authorization through the existing consent/break-glass policy boundary;
- Angular clinical workspace with persistent patient-context banner.

## Safety invariants

### Patient and encounter binding

Every clinical write carries:

```text
organizationId
facilityId
patientId
encounterId
```

The application service reloads the patient and encounter and rejects the operation unless all four scopes agree. An identifier supplied by the browser is never treated as authorization.

Routine problem, allergy, observation, draft-note, and signing writes require an `IN_PROGRESS` encounter. Signed-note addenda and corrections may be appended after the encounter has moved on, but they still require the original patient/encounter binding.

### Signed notes are immutable

Clinical notes have two primary states:

```text
DRAFT
  ↓ sign
SIGNED
```

Draft content can be edited. Signing stores the signer and signed timestamp and freezes the original note body. An ordinary update against a signed note is rejected.

Post-sign changes are separate append-only records:

```text
SIGNED NOTE
   ├── ADDENDUM
   └── CORRECTION
```

A correction requires a reason. Neither operation overwrites the signed body.

### Terminology boundary

Core tables do not hard-code one country's code set. Problems, allergies, and observations can carry `codeSystem` plus `code` while retaining a human-readable display. V1-M9 will add the formal terminology/mapping contracts.

### Privacy and authorization

Clinical record reads require `VIEW_CLINICAL_RECORD`.

Clinical writes require `MANAGE_CLINICAL_RECORD`.

Initial role policy:

| Role | View | Manage |
|---|---:|---:|
| OWNER | Yes | Yes |
| ADMIN | Yes | Yes |
| CLINICIAN | Yes | Yes |
| STAFF | Yes | No |
| VIEWER | No | No |

The organization/facility authorization decision runs before the clinical service. The clinical service then applies patient privacy authorization for `CLINICAL / TREATMENT / CARE_TEAM`.

### Audit evidence

Authorization decisions are audited using the clinical-record resource boundary. Successful clinical mutations emit patient-aware audit events for:

- problem recording/status changes;
- allergy recording;
- observation recording;
- note creation and draft update;
- note signing;
- signed-note addendum;
- signed-note correction.

The audit event contains correlation ID through the existing request boundary.

## Persistence

V1-M8 adds:

```text
clinical_problems
patient_allergies
clinical_observations
clinical_notes
clinical_note_amendments
```

Clinical notes use restricted deletion relationships so finalized documentation cannot disappear through encounter/patient cascading behavior.

The patient timeline is a read model assembled from encounter events plus clinical provenance. It is not a second source of truth.

## GraphQL boundary

Application reads and writes remain GraphQL-first.

Main query:

```graphql
clinicalRecord(input: PatientClinicalRecordInput!): ClinicalRecord!
```

Mutations:

```text
recordProblem
updateProblemStatus
recordAllergy
recordObservation
createClinicalNote
updateClinicalNoteDraft
signClinicalNote
addClinicalNoteAmendment
```

The client must send patient and encounter context for clinical writes; the server revalidates that context.

## Angular workflow

Patient detail links to:

```text
/patients/:patientId/clinical
```

The page:

1. reloads the patient through the protected patient boundary;
2. sets the global patient context/banner;
3. loads encounters and the longitudinal clinical record;
4. enables clinical write controls only when an `IN_PROGRESS` encounter exists;
5. sends both patient and encounter IDs for every clinical write;
6. refreshes the record and timeline after successful mutations.

The disabled state is usability only. Laravel remains authoritative.

## Verification

Required local verification:

```powershell
cd apps/api
php artisan migrate
php artisan lighthouse:validate-schema
php artisan test

cd ../web
npm test -- --watch=false
npm run build
```

V1-M8 tests cover:

- problem, allergy, and observation persistence;
- note draft creation and editing;
- note signing;
- rejection of ordinary edits after signing;
- append-only correction behavior and required correction reason;
- patient timeline visibility;
- patient/encounter mismatch rejection before write;
- patient-aware audit evidence.

## Known limits

This milestone does not yet implement:

- formal terminology registry/mapping/version resolution (V1-M9);
- structured composite observations such as a first-class blood-pressure panel;
- clinical task/order/result workflows;
- medication or diagnostic workflows;
- note co-signatures;
- specialty note templates;
- full care-team relationship ABAC;
- FHIR persistence.

Those extensions must preserve the V1-M8 patient binding, provenance, signed-note immutability, privacy, authorization, and audit rules.
