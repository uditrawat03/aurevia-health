# Version 1 Milestones

## Version 1 Goal

Version 1 establishes a production-capable **global healthcare platform foundation** and a safe first slice of patient, scheduling, encounter, privacy, and clinical-record functionality.

It is intentionally not a complete hospital information system.

## Version 1 User Outcome

An authorized healthcare user should be able to:

1. authenticate;
2. work within the correct organization/facility scope;
3. register a synthetic patient;
4. review duplicate candidates;
5. schedule an appointment;
6. start an encounter;
7. record basic clinical information;
8. maintain allergies and problems;
9. create and sign a basic clinical note;
10. complete the encounter;
11. inspect the patient timeline;
12. inspect audit evidence;
13. apply consent/privacy decisions through a policy boundary;
14. do this without requiring a country-specific national identifier.

# V1-M0 — Repository and Development Foundation

**Outcome:** clean clone can be bootstrapped consistently.

Scope:

- Laravel 13;
- Angular 22;
- PostgreSQL;
- Redis;
- Docker Compose;
- environment conventions;
- CI;
- health check;
- documentation;
- ADR process.

Acceptance:

- API starts;
- Angular starts;
- database connects;
- Redis connects;
- backend tests pass;
- frontend tests pass;
- CI runs on pull requests.

# V1-M1 — Organization and Country Profile Foundation

**Outcome:** healthcare organizations can be modeled without country-specific assumptions.

Scope:

- tenant/organization;
- health system;
- facility;
- department;
- settings;
- country-profile contract;
- profile resolver;
- configuration inheritance;
- profile version metadata.

Acceptance:

- organization may own multiple facilities;
- profile selection is server controlled;
- country changes do not require core organization schema forks;
- relevant configuration changes are audited.

# V1-M2 — Identity, Authentication and Authorization

**Outcome:** protected healthcare resources require valid identity and authorization.

Scope:

- Sanctum SPA authentication;
- users;
- organization memberships;
- roles/permissions;
- RBAC;
- ABAC contract;
- session logout/revocation;
- Angular authenticated shell.

Rules:

- no long-lived first-party tokens in browser storage;
- server authorization is mandatory;
- cross-tenant access is denied.

# V1-M3 — Audit Foundation

**Outcome:** sensitive operations create structured audit evidence.

Scope:

- audit schema;
- actor;
- organization;
- patient/resource;
- action;
- outcome;
- correlation ID;
- timestamp;
- restricted audit viewer.

Protected reads/writes and security-relevant denials must be testable.

# V1-M4 — Patient Identity / MPI Foundation

**Outcome:** patients can be registered and safely identified.

Scope:

- internal patient ID;
- MRNs/typed identifiers;
- demographics;
- names;
- contacts;
- addresses;
- relationships;
- duplicate candidate service;
- merge review model;
- patient search;
- patient context banner.

Rules:

- national identifiers are never primary keys;
- uncertain duplicates are never auto-merged.

# V1-M5 — Consent and Privacy Boundary

**Outcome:** privacy is enforced through explicit policy behavior.

Scope:

- consent aggregate;
- data category;
- purpose;
- recipient/class;
- effective period;
- revocation;
- privacy policy interface;
- break-glass model.

Acceptance:

- revoked consent affects relevant decisions;
- country profile can extend policy;
- break-glass requires reason and audit;
- frontend cannot bypass policy.

# V1-M6 — Scheduling

**Outcome:** patients can be booked against healthcare resources.

Scope:

- provider/resource calendar;
- appointment type;
- appointment;
- reschedule;
- cancellation;
- waitlist foundation;
- statuses;
- timezone-safe timestamps.

Acceptance includes conflict behavior, scope authorization, audit, and idempotent create semantics where retries matter.

# V1-M7 — Encounter Foundation

**Outcome:** registration/appointment can become a clinical encounter.

Scope:

- encounter;
- encounter type/status;
- participants;
- facility/department;
- start/end;
- patient-location foundation;
- outpatient flow;
- timeline events.

Example state:

```text
planned
  ↓
arrived
  ↓
in-progress
  ↓
completed
```

with appropriate cancellation/error paths.

# V1-M8 — Core Clinical Record

**Outcome:** clinicians can capture a safe first longitudinal record.

Scope:

- problem list;
- allergy foundation;
- vitals/observations;
- clinical note;
- draft/sign lifecycle;
- addendum/correction;
- provenance;
- patient timeline.

Safety:

- signed notes cannot be silently overwritten;
- corrections preserve original history;
- patient context is visible during clinical edits.

# V1-M9 — Terminology and Interoperability Contracts

**Outcome:** clinical data is not coupled to hard-coded local codes or FHIR persistence.

Scope:

- terminology concept abstraction;
- coding-system/version metadata;
- mapping contract;
- interoperability mapper contract;
- FHIR boundary skeleton;
- correlation model.

Acceptance:

- a domain concept can carry multiple codings;
- terminology versions are explicit;
- FHIR is not the primary internal clinical aggregate solely for exchange convenience.

# V1-M10 — Hardening and Release Candidate

Scope:

- security review;
- threat-model review;
- dependency review;
- performance baseline;
- accessibility baseline;
- backup/restore test;
- migration rehearsal;
- negative authorization tests;
- audit coverage review;
- documentation review;
- end-to-end synthetic workflow.

Required flow:

```text
Create organization/facility
        ↓
Create authorized staff user
        ↓
Register patient
        ↓
Schedule appointment
        ↓
Start encounter
        ↓
Record allergy/problem/vital
        ↓
Write clinical note
        ↓
Sign note
        ↓
Complete encounter
        ↓
Review patient timeline
        ↓
Review audit evidence
```

# Version 1 Non-Goals

Version 1 does not need to complete:

- full inpatient nursing;
- dispensing/e-prescribing networks;
- full LIS;
- PACS/DICOM production integration;
- surgery;
- country-specific claims;
- full prior authorization;
- patient portal;
- public-health reporting;
- production AI assistants;
- complete national exchange integrations;
- advanced hospital command center.

Extension points should be preserved without prematurely implementing these modules.

# Version Labels

Recommended progression:

```text
v1.0.0-alpha.1
v1.0.0-alpha.2
v1.0.0-beta.1
v1.0.0-rc.1
v1.0.0
```

Milestone identifiers are engineering checkpoints, not public semantic versions.
