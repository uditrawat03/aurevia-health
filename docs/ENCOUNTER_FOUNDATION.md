# Encounter Foundation

V1-M7 establishes the country-neutral encounter boundary that connects patient identity and scheduling to later clinical documentation.

## Version 1 Scope

The encounter aggregate owns:

- internal ULID encounter identity;
- organization, facility, patient, optional department, and optional appointment linkage;
- country-neutral encounter type;
- controlled lifecycle status;
- arrival, start, completion, and cancellation timestamps;
- append-oriented encounter timeline events;
- clinical privacy authorization;
- facility-scoped role authorization;
- patient-aware audit evidence;
- a functional Angular encounter workspace.

Participants and longitudinal patient-location history remain deferred beyond the current Version 1 encounter foundation. The implemented scope focuses on safe patient/facility/appointment binding, explicit lifecycle transitions, clinical privacy authorization, durable timeline evidence, and auditability.

## Lifecycle

The initial state machine is deliberately narrow:

```text
PLANNED
  ├─> ARRIVED ─> IN_PROGRESS ─> COMPLETED
  └─> CANCELLED

ARRIVED ─> CANCELLED
```

`COMPLETED` and `CANCELLED` are terminal. A client cannot jump directly from `PLANNED` to `COMPLETED`, and an encounter that is already complete cannot be reopened through this foundation.

Every successful state change appends an `encounter_events` row. The encounter row stores the current state and milestone timestamps while the event stream preserves how that state was reached.

## Appointment linkage

`appointment_id` is optional so walk-in encounters are supported. When supplied, the appointment must:

- belong to the same organization;
- belong to the same facility;
- belong to the same patient;
- still be `SCHEDULED`;
- not already be linked to another encounter.

The database also enforces one encounter per appointment.

## Privacy and authorization

Encounter access requires both layers:

1. active organization membership with `VIEW_ENCOUNTERS` or `MANAGE_ENCOUNTERS` inside the requested facility scope;
2. a privacy decision for `CLINICAL / TREATMENT / CARE_TEAM` access.

The Angular page is never the security boundary. Direct GraphQL requests are subject to the same Laravel authorization and privacy checks.

For local demonstration, the development seeder creates active treatment consent for both `DEMOGRAPHICS` and `CLINICAL` categories for Asha Mehta. Rahil Khan keeps both categories revoked so denial behavior remains testable.

## GraphQL

Queries:

```graphql
encounters(input: PatientEncountersInput!): [Encounter!]!
```

Mutations:

```graphql
createEncounter(input: CreateEncounterInput!): Encounter!
transitionEncounter(input: TransitionEncounterInput!): Encounter!
```

Resolvers remain thin adapters. Workflow rules live in `EncounterService` and `EncounterTransitionPolicy`; persistence stays behind `EncounterRepo`.

## Browser verification

Open `http://localhost:4200/encounters` after login.

A useful local flow is:

1. search for `Asha`;
2. select an existing scheduled appointment, or leave the appointment blank for a walk-in;
3. create an `OUTPATIENT` encounter;
4. mark the patient arrived;
5. start the encounter;
6. complete it;
7. inspect the durable event timeline and Audit workspace.

Select Rahil to verify revoked clinical consent blocks encounter access.

## Verification

```powershell
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan db:seed --force
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test --filter=Encounter
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-web npm test -- --watch=false
docker exec aurevia-health-web npm run build
git diff --check
```
