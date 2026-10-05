# Scheduling Foundation

## Purpose

The Aurevia Health scheduling boundary books an authorized patient against healthcare resources while preserving organization/facility scope, patient privacy, resource availability, retry safety, lifecycle history, timezone correctness, and auditable evidence.

The model remains country-neutral. Country profiles may later tighten operating hours, holidays, referral rules, or scheduling policy without changing the generic appointment or waitlist aggregates.

## Core model

```text
Organization
  ↓
Facility
  ├── AppointmentType
  ├── SchedulingResource (provider / room / equipment)
  │        ↓
Patient → Appointment ← resource assignments
  │             ↓
  │       AppointmentEvent
  │
  └────→ WaitlistEntry → AppointmentType
```

Appointments use internal ULIDs, organization/facility/patient scope, appointment type, one or more resources, explicit status, UTC start/end timestamps, the IANA timezone used for local interpretation, a client idempotency key, and a request fingerprint.

## Booking safety and idempotency

`bookAppointment` requires an authenticated `MANAGE_SCHEDULE` actor in the target facility, an eligible patient in that facility, a valid treatment privacy decision, an active appointment type, active scheduling resources, a future local start plus IANA timezone, and no resource or patient overlap.

The repository locks the patient and selected resource rows before conflict checks and insert. Identical retries using the same idempotency key and normalized input return the existing appointment. Reusing that key with different input is rejected. The explicit booking audit event is emitted only for the original create.

## Appointment lifecycle

A scheduled appointment can be rescheduled or cancelled. Both operations re-authorize organization/facility scope and patient privacy server-side.

Reschedule rules:

- only `SCHEDULED` appointments can move;
- at least one active target resource is required;
- the new start must be in the future;
- duration remains controlled by the appointment type;
- patient and resource conflicts are re-evaluated while excluding the appointment being moved;
- the original and replacement timestamps are persisted in an `appointment_events` `RESCHEDULED` record;
- a structured `RESCHEDULE_APPOINTMENT` audit event is emitted.

Cancellation rules:

- only `SCHEDULED` appointments can be cancelled;
- a specific cancellation reason is required;
- the appointment remains persisted with `CANCELLED` status, cancellation timestamp, actor, and reason;
- an `appointment_events` `CANCELLED` record preserves lifecycle evidence;
- cancelled appointments no longer block patient/resource conflict checks;
- a structured `CANCEL_APPOINTMENT` audit event is emitted.

New bookings also receive a durable `BOOKED` appointment event, so lifecycle evidence is not limited to audit metadata.

## Waitlist foundation

The waitlist represents demand without reserving a provider/resource slot. `joinWaitlist` stores patient/facility scope, appointment type, preferred UTC window, original IANA timezone, optional preference text, status, actor, and retry identity.

Initial statuses are:

```text
WAITING → OFFERED → BOOKED
   └──────────────→ CANCELLED
```

Version 1 implements `WAITING` creation and explicit cancellation; offer-to-book orchestration remains deferred. Waitlist creation is idempotent, privacy-protected, and audited. Cancelling a waiting entry preserves the row and records its cancellation reason.

## Timezone behavior

The API accepts local date/times plus an IANA timezone such as `Asia/Kolkata` or `Europe/London`. The application converts timestamps to UTC for persistence and retains the timezone for local rendering. Database comparisons bind date objects rather than raw ISO strings, keeping overlap behavior consistent across PostgreSQL and the SQLite test environment.

## Authorization

| Role | View schedule | Manage schedule | Configure scheduling |
|---|---:|---:|---:|
| OWNER | yes | yes | yes |
| ADMIN | yes | yes | yes |
| CLINICIAN | yes | no | no |
| STAFF | yes | yes | no |
| VIEWER | no | no | no |

Facility-scoped memberships remain restricted to explicitly selected facilities. Organization-wide schedule and waitlist queries require all-facility authority. Angular controls are convenience only; Laravel remains authoritative.

## GraphQL contract

Queries:

```text
schedulingResources
appointmentTypes
appointments
waitlistEntries
```

Mutations:

```text
bookAppointment
rescheduleAppointment
cancelAppointment
joinWaitlist
cancelWaitlist
```

## Browser verification

The Angular `/scheduling` workspace supports patient search, booking, appointment reschedule/cancellation, and waitlist creation/cancellation against the real GraphQL boundary. The calendar keeps cancelled appointments visible so operators can distinguish lifecycle history from open capacity.

For local testing, Asha Mehta has active treatment consent and can complete scheduling workflows. Rahil Khan has a revoked consent scenario and should be denied until privacy state changes or authorized break-glass access is active.

## Regression Coverage

M6 is complete only when schema validation, backend tests, dependency audit, frontend tests/build, and `git diff --check` are green. Tests cover booking idempotency, conflict behavior, privacy enforcement, facility authorization, reschedule conflict re-checking, cancellation slot release/history, and waitlist idempotency/cancellation.
