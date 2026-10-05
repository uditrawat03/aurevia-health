# Aurevia Health Documentation

This directory is the entry point for Aurevia Health technical and Version 1 domain documentation.

Documentation must distinguish **implemented behavior**, **release evidence**, and **future roadmap**. A roadmap item or architectural extension point is not an implemented product capability until code, tests and the owning domain document say so.

## Start Here

| Document | Purpose |
|---|---|
| [`../README.md`](../README.md) | Project overview, Version 1 capability snapshot, local setup and primary links |
| [`../ARCHITECTURE.md`](../ARCHITECTURE.md) | System boundaries, implemented V1 architecture and future extension points |
| [`../ROADMAP.md`](../ROADMAP.md) | Capability-oriented progression beyond the delivered V1 foundation |
| [`../version_1_milestones.md`](../version_1_milestones.md) | V1 outcomes, engineering checkpoints and current RC status |
| [`../MILESTONES_TESTING.md`](../MILESTONES_TESTING.md) | Test layers, safety/security gates and release evidence expectations |
| [`../CODING_GUIDELINES.md`](../CODING_GUIDELINES.md) | Mandatory code, GraphQL, healthcare-safety and documentation rules |
| [`../SECURITY.md`](../SECURITY.md) | Repository security policy and release blockers |

## Platform and Development

| Document | Scope |
|---|---|
| [`TECH_BASELINE.md`](TECH_BASELINE.md) | Historical framework/runtime baseline and upgrade policy |
| [`DOCKER_DEVELOPMENT.md`](DOCKER_DEVELOPMENT.md) | Local PostgreSQL/Redis/Laravel/Horizon/Angular topology and commands |
| [`GRAPHQL_BACKEND.md`](GRAPHQL_BACKEND.md) | First-party GraphQL boundary, schema composition, security and correlation |
| [`UI_FOUNDATION.md`](UI_FOUNDATION.md) | Aurevia visual/density primitives and accessibility conventions |
| [`FRONTEND_WORKSPACE_NAVIGATION.md`](FRONTEND_WORKSPACE_NAVIGATION.md) | Current Angular routes and functional/placeholder workspace status |
| [`ADR_TEMPLATE.md`](ADR_TEMPLATE.md) | Architecture decision record template |

## Version 1 Domain Boundaries

| Domain | Document | Implemented V1 responsibility |
|---|---|---|
| Organization / country profile | [`ORGANIZATION_COUNTRY_PROFILE.md`](ORGANIZATION_COUNTRY_PROFILE.md) | Tenant hierarchy, server-pinned country profile and inherited operational settings |
| Identity / authorization | [`IDENTITY_AUTHORIZATION.md`](IDENTITY_AUTHORIZATION.md) | Session auth, memberships, RBAC and facility scope |
| Audit | [`AUDIT_FOUNDATION.md`](AUDIT_FOUNDATION.md) | Structured allowed/denied evidence and restricted audit viewer |
| Patient identity / MPI | [`PATIENT_IDENTITY_MPI.md`](PATIENT_IDENTITY_MPI.md) | Internal patient identity, secure search, duplicate candidates and merge review |
| Consent / privacy | [`CONSENT_PRIVACY.md`](CONSENT_PRIVACY.md) | Consent lifecycle, privacy policy boundary and break-glass |
| Scheduling | [`SCHEDULING_FOUNDATION.md`](SCHEDULING_FOUNDATION.md) | Booking, conflicts, lifecycle, waitlist, timezone and retry safety |
| Encounter | [`ENCOUNTER_FOUNDATION.md`](ENCOUNTER_FOUNDATION.md) | Patient/facility-bound controlled encounter lifecycle |
| Clinical record | [`CLINICAL_RECORD_FOUNDATION.md`](CLINICAL_RECORD_FOUNDATION.md) | Problems, allergies, observations, signed notes, amendments, provenance and timeline |
| Terminology / interoperability | [`TERMINOLOGY_INTEROPERABILITY.md`](TERMINOLOGY_INTEROPERABILITY.md) | Versioned multi-coding concepts, mapping contract and FHIR R4 exchange mapper |

## Release Candidate

| Document | Purpose |
|---|---|
| [`RELEASE_HARDENING.md`](RELEASE_HARDENING.md) | Security, migration, restore, performance, accessibility and documentation gates |
| [`V1_RELEASE_CANDIDATE_EVIDENCE.md`](V1_RELEASE_CANDIDATE_EVIDENCE.md) | Candidate-specific verification evidence and remaining blockers |

The evidence document is authoritative for whether an RC may be tagged. A green earlier commit does not automatically satisfy CI for a later documentation/code commit.

## Documentation Rules

1. Update the owning domain document and root README when implementation changes externally meaningful behavior.
2. Describe what the current code does; move planned work to `ROADMAP.md` or an explicit **Deferred / Known limits** section.
3. Keep GraphQL, authorization, privacy, audit and clinical-safety invariants synchronized with tests.
4. Never describe a release candidate as regulatory compliance, production certification or permission to process real patient data.
5. Use synthetic examples only; do not paste real patient data, secrets, production logs or database exports.
6. Update release evidence only from observed commands/CI/manual checks. Never mark a manual gate complete by inference.
7. When a framework/runtime baseline changes, update `TECH_BASELINE.md` and add an ADR if architecture or runtime behavior changes materially.
