# Aurevia Health — Version 2 Milestones

## Purpose

Version 2 expands the trusted Version 1 outpatient/clinical foundation into broader hospital care-delivery workflows. It is intentionally narrower than the complete product roadmap: Version 2 focuses on the clinical and operational dependencies required for orders/results, inpatient care, emergency care, medication safety and diagnostics.

Version 2 must preserve the invariants proven in `v1.0.0-rc.1`:

- organization and facility isolation is enforced server-side;
- authentication, membership, permission and facility scope remain authoritative in Laravel;
- patient privacy and consent supplement rather than replace authorization;
- break-glass remains reasoned, time-bounded and audited;
- clinical writes bind patient, encounter and facility context server-side;
- signed/finalized clinical records are never silently overwritten;
- provenance and lifecycle history are preserved;
- duplicate-patient candidates are never auto-merged;
- terminology mappings never guess absent translations;
- protected actions produce stable audit evidence;
- expected business-rule rejection remains distinct from unexpected server failure;
- GraphQL remains the first-party application boundary unless an explicit protocol/infrastructure exception is documented.

Version 2 is not permission to weaken these rules for implementation speed.

## Version 2 Theme

```text
V1 Trust + Outpatient Clinical Foundation
                ↓
Care Team + Clinical Tasks
                ↓
Orders + Results
                ↓
ADT + Bed / Location Flow
                ↓
Nursing + Emergency
                ↓
Medication + Diagnostics
                ↓
V2 Hardening / Release Candidate
```

## Scope Guardrails

Version 2 is a **care-delivery expansion**, not a full hospital suite.

Planned Version 2 scope:

- care teams and clinical task ownership;
- clinical orders and result lifecycle;
- admission/transfer/discharge and physical location/bed foundation;
- inpatient nursing foundation;
- emergency/triage foundation;
- medication history/reconciliation/order/administration safety foundation;
- laboratory workflow foundation and imaging report boundary;
- operational work queues directly required by those workflows;
- interoperability/audit extensions required by the new clinical domains;
- release hardening for the expanded workflow.

Explicitly deferred unless separately promoted through roadmap governance:

- country-specific billing/claims implementation;
- patient portal and caregiver proxy product;
- full PACS/archive implementation;
- external e-prescribing network certification;
- complete pharmacy inventory/dispensing;
- surgery/perioperative suite;
- public-health reporting;
- production national exchange integrations;
- AI clinical assistance;
- broad analytics/warehouse platform;
- hospital command center beyond the minimum patient-flow visibility required by Version 2.

## Cross-Milestone Definition of Done

Every Version 2 milestone must include, where applicable:

1. GraphQL schema/contracts with stable typed inputs and outputs;
2. server-side organization/facility/patient/encounter authorization;
3. privacy/consent evaluation for protected patient workflows;
4. allowed and denied audit evidence;
5. lifecycle/state-transition validation;
6. provenance and immutable-history behavior for finalized clinical data;
7. idempotency for retry-prone writes and integration boundaries;
8. positive and negative tests;
9. migration and rollback/forward-fix considerations;
10. README plus owning domain documentation updates;
11. synthetic acceptance data only;
12. no unresolved high-severity dependency vulnerability.

A milestone is not complete when only the happy-path UI works.

# V2-M0 — Version 2 Baseline and V1 Regression Contract

**Outcome:** Version 2 development starts without silently regressing the tagged V1 foundation.

## Scope

- record `v1.0.0-rc.1` as the compatibility baseline;
- convert the V1 acceptance workflow into a standing regression gate for V2;
- define Version 2 domain ownership and module boundaries;
- define shared lifecycle/idempotency conventions for new clinical workflows;
- confirm database migration conventions for long-lived V2 branches;
- document event/notification boundaries needed by orders, results and patient flow;
- establish test fixtures for inpatient and emergency synthetic scenarios.

## Acceptance

- V1 release workflow remains green unchanged or with explicitly reviewed compatible extensions;
- new V2 migrations build successfully from an empty database and upgrade from the V1 schema;
- no V2 module bypasses existing authorization/privacy/audit services;
- synthetic inpatient and emergency fixtures contain no real patient data;
- README, architecture and documentation index identify V2 as planned/in-progress rather than delivered.

# V2-M1 — Care Team and Clinical Task Foundation

**Outcome:** responsibility for patient care can be represented explicitly and audited.

## Scope

- encounter/patient care-team membership;
- participant role and effective period;
- assigned clinician/service ownership;
- clinical task aggregate;
- task priority, due time, owner and status;
- reassignment/cancellation/completion history;
- task comments/notes that do not replace clinical documentation;
- patient/encounter task work queue;
- audit actions/resources for care-team and task lifecycle.

## Safety

- care-team membership is never inferred solely from frontend state;
- task assignment does not grant broader patient authorization by itself;
- cross-facility assignment is rejected unless policy explicitly allows it;
- completion/reassignment history is preserved.

## Acceptance

- authorized users can assign, reassign, complete and cancel tasks;
- unauthorized or wrong-facility actors are denied and audited;
- patient timeline can reference meaningful task lifecycle events without leaking task internals into generic audit payloads.

# V2-M2 — Clinical Orders Foundation

**Outcome:** clinicians can place governed clinical orders without coupling the domain to one laboratory, imaging or medication implementation.

## Scope

- order aggregate and order item;
- order category/type;
- ordering clinician;
- patient/encounter/facility binding;
- priority;
- requested timing;
- indication/reason;
- lifecycle: draft/placed/accepted/in-progress/completed/cancelled as appropriate;
- cancellation/discontinuation reason;
- terminology-coded order concepts;
- idempotent order placement;
- audit and timeline events.

## Safety

- an order is not a result;
- placed/finalized order intent is not silently rewritten;
- cancellation/discontinuation preserves original intent/history;
- terminology mapping never invents a code;
- order placement requires valid patient and encounter context where clinically required.

## Acceptance

- generic orders can be placed without requiring a lab, imaging or pharmacy implementation;
- duplicate retry does not create duplicate orders;
- invalid patient/encounter/facility combinations are rejected;
- authorization, privacy and audit negative paths are covered.

# V2-M3 — Results, Reports and Clinical Acknowledgement

**Outcome:** results can be received, corrected, reviewed and acknowledged with provenance.

## Scope

- result/report aggregate linked to an order where applicable;
- result status lifecycle;
- observation/result components;
- source/provenance;
- preliminary/final/corrected states;
- abnormal/critical indicator foundation;
- correction/amendment history;
- clinician review/acknowledgement;
- result timeline events;
- correlation identifiers for external-result boundaries.

## Safety

- final results are not silently overwritten;
- corrections preserve prior result versions;
- critical-value flags cannot be cleared by presentation-only behavior;
- acknowledgement records who acknowledged and when;
- external correlation IDs do not contain patient-sensitive payloads.

## Acceptance

- final and corrected results retain complete history;
- result review does not mutate source clinical values;
- wrong-patient/order linkage is rejected;
- abnormal/critical test scenarios are covered.

# V2-M4 — Admission, Transfer, Discharge and Bed/Location Foundation

**Outcome:** Aurevia Health can represent inpatient patient flow beyond the V1 outpatient encounter.

## Scope

- inpatient/observation encounter types;
- admission;
- transfer;
- discharge;
- facility unit/ward;
- room/bed/location hierarchy;
- bed state and assignment history;
- encounter location history;
- admission source and discharge disposition foundation;
- census/read-model foundation;
- scheduling-to-admission handoff where relevant.

## Safety

- one active bed cannot be assigned inconsistently;
- transfers preserve previous location history;
- discharge does not erase encounter history;
- location assignment is facility-scoped;
- terminal encounter states cannot be silently reopened.

## Acceptance

- a synthetic patient can be admitted, assigned, transferred and discharged;
- occupancy conflicts are rejected;
- every transition is authorized and audited;
- patient timeline reflects admission, transfer and discharge history.

# V2-M5 — Inpatient Nursing Foundation

**Outcome:** nursing workflows can capture structured bedside care without using free-text notes as a substitute for governed clinical state.

## Scope

- nursing assessment foundation;
- configurable flowsheet definition and entries;
- intake/output;
- nursing observations;
- safety checks;
- handover summary foundation;
- nursing task integration;
- shift-aware work queue;
- escalation flag foundation;
- provenance and correction behavior.

## Safety

- charted nursing values retain author/time/source;
- finalized entries use correction/amendment rather than destructive replacement where required;
- patient identity/context remains visible during bedside charting;
- configurable forms cannot bypass typed validation for safety-critical values.

## Acceptance

- a synthetic inpatient shift can record observations, intake/output and handover;
- wrong-patient/wrong-encounter writes are rejected;
- corrections preserve history;
- nursing actions participate in audit/timeline evidence.

# V2-M6 — Emergency and Triage Foundation

**Outcome:** emergency patients can be registered, triaged and progressed through an ED-specific workflow.

## Scope

- emergency encounter type;
- arrival mode/source;
- triage assessment;
- acuity/priority abstraction;
- chief complaint;
- emergency location/zone;
- queue status;
- reassessment;
- clinician assignment;
- disposition to discharge/admission/transfer;
- emergency work-board read model.

## Safety

- acuity changes preserve history;
- queue ordering is not an authorization mechanism;
- emergency/break-glass access remains explicit and audited;
- disposition cannot bypass admission/discharge lifecycle rules.

## Acceptance

- a synthetic walk-in ED patient can move from arrival through triage to disposition;
- reassessment history is retained;
- unauthorized access remains denied even when the patient is visible on an operational board;
- break-glass scenarios are explicitly tested.

# V2-M7 — Medication Safety Foundation

**Outcome:** medication history, reconciliation, ordering and administration can be represented with explicit safety boundaries.

## Scope

- medication concept/reference boundary;
- medication history;
- medication reconciliation;
- medication order;
- dose/route/frequency/timing structure;
- start/stop/discontinue lifecycle;
- administration record foundation;
- omission/refusal/not-given reason;
- allergy cross-check hook;
- interaction/decision-support adapter contract;
- medication audit/timeline events.

## Safety

- medication identity is terminology-coded and version-aware;
- allergy information is surfaced at ordering/administration boundaries;
- administration cannot silently modify the source medication order;
- discontinued orders remain historically visible;
- no claim of production e-prescribing or interaction-checking certification.

## Acceptance

- synthetic medication reconciliation and administration scenarios pass;
- contraindication/allergy hook can block or warn through a governed policy result;
- wrong-patient/order administration is rejected;
- medication lifecycle is fully auditable.

# V2-M8 — Diagnostic Workflow Foundation

**Outcome:** laboratory workflow is operationally useful and imaging can exchange/report through a governed boundary without attempting a full LIS/PACS product.

## Laboratory scope

- test/catalog reference;
- lab order specialization;
- specimen;
- collection;
- accession identifier;
- specimen status;
- processing status;
- result linkage;
- critical result workflow foundation;
- correction history.

## Imaging scope

- imaging order specialization;
- modality/procedure metadata;
- scheduling/reference identifiers;
- report lifecycle boundary;
- DICOM/DICOMweb adapter contract only;
- no image archive implementation requirement.

## Safety

- specimen identity is distinct from patient identity;
- accession/specimen/order linkage is validated server-side;
- corrected results preserve prior versions;
- imaging integration does not make DICOM objects the internal clinical aggregate.

## Acceptance

- synthetic lab order → specimen → final/corrected result flow passes;
- wrong-patient specimen linkage is rejected;
- imaging report contract can be exercised without requiring a PACS;
- integration correlation and failure states are observable.

# V2-M9 — Care-Delivery Work Queues and Reliability

**Outcome:** new Version 2 workflows are operable without relying on hidden database state or silent integration failure.

## Scope

- role/facility-scoped clinical work queues;
- pending orders/results/tasks;
- overdue/escalated items;
- inpatient census/patient-flow views;
- ED queue views;
- acknowledgement/retry state for external boundaries introduced in V2;
- dead-letter/failure visibility where asynchronous processing exists;
- correlation-driven troubleshooting;
- bounded pagination/filtering;
- operational audit for privileged queue actions.

## Safety

- work queues do not grant access beyond the underlying domain authorization;
- hidden/failed asynchronous work is observable;
- retry behavior is idempotent;
- patient identifiers are minimized in logs/integration diagnostics.

## Acceptance

- accepted asynchronous work cannot silently disappear;
- retry does not duplicate clinical state;
- queue filtering cannot cross organization/facility boundaries;
- failure/recovery scenarios have automated coverage.

# V2-M10 — Version 2 Hardening and Release Candidate

**Outcome:** the expanded care-delivery slice is validated end-to-end and is ready for a Version 2 release candidate tag.

## Required synthetic flow

```text
Create organization/facility
        ↓
Register patient
        ↓
Create care team / task
        ↓
Place clinical order
        ↓
Receive and acknowledge result
        ↓
Admit patient
        ↓
Assign bed / transfer location
        ↓
Record nursing assessment / flowsheet
        ↓
Reconcile and administer medication
        ↓
Exercise ED or inpatient disposition path
        ↓
Complete diagnostic workflow
        ↓
Discharge / complete encounter
        ↓
Review timeline, work queues and audit evidence
```

## Hardening gates

- full V1 regression remains green;
- full V2 synthetic acceptance workflow passes;
- cross-tenant and wrong-facility negative tests pass for every new domain;
- wrong-patient/wrong-encounter clinical-write tests pass;
- finalized order/result/nursing/medication history cannot be silently overwritten;
- medication and critical-result safety scenarios pass;
- admission/transfer/bed conflict scenarios pass;
- integration retry/idempotency scenarios pass;
- migration-from-V1 rehearsal passes;
- clean-database migration rehearsal passes;
- backup/restore rehearsal passes;
- dependency audits pass;
- performance baseline covers new high-traffic queues and patient-flow reads;
- accessibility baseline covers inpatient, ED, order/result and medication workflows;
- audit vocabulary/completeness review covers every V2 protected action;
- documentation is consolidated and accurate;
- CI is green on the exact candidate commit.

## Candidate label

The first Version 2 release candidate should be tagged only after the exact candidate SHA passes all required gates.

Recommended label:

```text
v2.0.0-rc.1
```

## Version 2 Non-Goals

Version 2 does not need to complete:

- country-specific claims adjudication;
- full revenue-cycle management;
- patient/caregiver portal;
- surgery/perioperative suite;
- complete pharmacy inventory/dispensing;
- production PACS/image archive;
- national e-prescribing certification;
- complete national exchange integrations;
- public-health reporting;
- enterprise analytics warehouse;
- production AI assistants.

These remain roadmap items unless explicitly promoted through the governance process.

## Milestone Commit Discipline

Each completed Version 2 milestone should:

1. update its owning implementation/domain documentation;
2. update `README.md` when externally meaningful behavior changes;
3. update `ROADMAP.md` when scope or sequencing changes;
4. run milestone-specific tests plus the standing V1 regression gate;
5. commit only after validation is green;
6. use a clear Conventional Commit-style message;
7. produce an apply-ready patch when work is handed off outside the repository.

Example progression:

```text
feat(care-team): establish V2 care team and clinical task foundation
feat(orders): add governed clinical order lifecycle
feat(results): add result review and correction lifecycle
feat(inpatient): add admission transfer discharge and bed flow
feat(nursing): add inpatient nursing foundation
feat(emergency): add triage and emergency workflow foundation
feat(medication): add medication safety foundation
feat(diagnostics): add diagnostic workflow foundation
feat(operations): add care-delivery work queues and reliability
chore(release): harden version 2 release candidate
```
