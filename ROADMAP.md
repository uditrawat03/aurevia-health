# Product Roadmap

## Purpose

This roadmap describes the intended product progression for Aurevia Health. It is capability-oriented rather than date-driven and distinguishes delivered Version 1 foundations from future hospital-platform scope.

Detailed release sequencing is defined in [version_1_milestones.md](version_1_milestones.md) and [version_2_milestones.md](version_2_milestones.md).

## Product Strategy

```text
Platform Trust
    ↓
Core Care Delivery
    ↓
Hospital Operations
    ↓
Country / Ecosystem Integration
    ↓
Intelligence and Optimization
```

The platform should not begin with AI, dashboards, or country-specific billing before identity, authorization, audit, patient identity, privacy, and encounter boundaries exist.

## Current Position

The Version 1 release-candidate scope delivers the trust-first outpatient/clinical foundation that precedes the broader roadmap. This does **not** mean every capability listed in Horizons 1–3 is complete.

| Horizon | Version 1 position |
|---|---|
| Horizon 0 | **Implemented** — repository, Laravel/Angular, PostgreSQL/Redis, Docker, CI, documentation and release gates |
| Horizon 1 | **Core subset implemented** — organization, country profile, session auth, memberships/roles/facility scope, audit, patient identity, consent/privacy and break-glass |
| Horizon 2 | **Outpatient subset implemented** — scheduling, waitlist foundation and controlled encounter lifecycle; inpatient bed/admission/transfer/discharge remain future |
| Horizon 3 | **Core record subset implemented** — problems, allergies, observations, signed notes, corrections/addenda, timeline and terminology boundary; orders/results/care-team/task systems remain future |
| Horizon 9 | **Contract foundation implemented** — versioned terminology and FHIR exchange mapping contracts; production transport/integration operations remain future |
| Horizons 4–8, 10–14 | **Future roadmap** |

The next roadmap work should extend these boundaries without weakening the Version 1 authorization, privacy, audit, provenance, signed-record and tenant-isolation invariants.

## Version 2 Delivery Focus

Version 2 is scoped as a care-delivery expansion across selected parts of Horizons 2–5: care teams/tasks, orders/results, inpatient ADT and bed flow, nursing, emergency, medication safety and diagnostic workflow foundations. It is not intended to complete the financial, patient-portal, country-integration, analytics or AI horizons. See [version_2_milestones.md](version_2_milestones.md) for the dependency-ordered plan.

# Horizon 0 — Engineering Foundation

Create the repository, Laravel 13 backend, Angular 22 frontend, PostgreSQL, Redis, Docker local infrastructure, CI, configuration conventions, observability baseline, documentation, ADR process, and synthetic test-data policy.

**Exit:** another engineer can clone, bootstrap, test, and run the system from documented steps.

# Horizon 1 — Trust, Identity and Organizational Core

Build organization/facility hierarchy, users, memberships, authentication, MFA-ready identity architecture, RBAC, ABAC foundation, session controls, audit, patient identity, duplicate review, country-profile framework, consent, privacy policy evaluation, and break-glass access.

**Exit:** major patient workflows cannot bypass authorization or audit.

# Horizon 2 — Scheduling, Encounters and Patient Flow

Build provider/resources, appointments, waitlists, registration, check-in, encounter lifecycle, outpatient/emergency/inpatient/observation types, facility/unit/room/bed models, admission, transfer, discharge, census, and patient timeline.

**Exit:** a synthetic patient can move through a complete encounter with audit and authorization.

# Horizon 3 — Core Clinical Record

Build problems, diagnoses, allergies, vitals, notes, signing, corrections/addenda, care team, clinical tasks, orders/results foundation, configurable forms, timeline, and terminology boundary.

**Exit:** clinical data has provenance, history, patient context, authorization, and audit.

# Horizon 4 — Diagnostics and Medication

### Laboratory

Catalog, orders, specimens, accessioning, processing, results, critical values, corrections.

### Imaging

Orders, scheduling, imaging workflow, report lifecycle, DICOM/DICOMweb integration boundary.

### Medication

History, reconciliation, prescribing abstraction, inpatient medication orders, eMAR, pharmacy workflow, medication-administration safety.

**Exit:** high-risk workflows have scenario tests and preserve clinical history.

# Horizon 5 — Inpatient, Emergency and Surgery

Build ED board/triage, inpatient nursing, flowsheets, intake/output, handover, surgery scheduling, perioperative checklist, procedure documentation, recovery, discharge planning, and care transition.

# Horizon 6 — Patient and Caregiver Experience

Build patient portal, proxy access, self-service appointments/forms, secure messages, record access/results, medication requests, consent/preferences, invoices/payment links, and record export.

# Horizon 7 — Financial Core

Build payer, plan, coverage, eligibility abstraction, authorization abstraction, charge, invoice, claim, payment, adjustment, refund, and financial work queues without assuming one national reimbursement system.

# Horizon 8 — Country Profiles

Implement jurisdiction-specific behavior as separate profiles.

Potential first profiles:

- United States;
- United Kingdom;
- India.

Each profile owns its privacy rules, terminology mappings, national identifiers/integrations, payer/reimbursement adapters, prescribing adapters, public-health/reporting behavior, and conformance tests.

# Horizon 9 — Interoperability Platform

Build message registry, correlation IDs, mapping engine, FHIR, HL7, document exchange, DICOM, EDI, partner APIs, retry/acknowledgement/dead-letter behavior, and an integration operations console.

**Exit:** accepted integration messages cannot silently disappear.

# Horizon 10 — Hospital Operations

Build command center, bed management, housekeeping state, internal transport, inventory, procurement, equipment, workforce schedule, credentialing, and operational task queues.

# Horizon 11 — HIM, Quality and Public Health

Build record completion, amendments, release of information, legal holds, disclosure accounting, exports, quality measurement, regulatory reporting adapters, and public-health integrations.

# Horizon 12 — Analytics Platform

Create CDC/event ingestion, warehouse/lakehouse, governed semantic models, privacy-aware dashboards, and operational/quality/financial analytics outside the production clinical database.

# Horizon 13 — AI Governance

Create AI gateway, model registry, prompt/version registry, provider adapters, privacy/data minimization, evaluation framework, audit, and human-review requirements.

No clinical AI feature should bypass this layer.

# Horizon 14 — Assistive AI

Potential features:

- evidence-linked chart summary;
- draft clinical documentation;
- handoff draft;
- discharge-summary draft;
- coding suggestions;
- documentation-gap detection;
- inbox summaries;
- operational forecasting;
- denial-risk assistance.

High-impact clinical decisions remain human-authorized.

# Long-Term Extensions

Potential specialties include cardiology, oncology, pediatrics, obstetrics, orthopedics, mental health, rehabilitation, dialysis, dentistry, ophthalmology, and home health.

Potential platform extensions include an approved integration marketplace, specialty SDK, country-profile SDK, embedded healthcare apps, developer portal, and operational digital twin.

# Roadmap Governance

A roadmap item enters implementation only when:

- domain ownership is clear;
- privacy/security impact is assessed;
- country-specific concerns are separated;
- data-model consequences are understood;
- interoperability implications are known;
- acceptance tests exist;
- migration/rollback implications are documented;
- required clinical/compliance review is identified.

The roadmap changes when evidence, regulation, or product learning requires it.
