# Frontend Workspace Navigation

## Purpose

Aurevia Health uses explicit Angular routes for the authenticated Version 1 workspace. This document records the **current route status**; it should not describe completed features as future milestone placeholders.

Authenticated healthcare data still comes from the protected Laravel GraphQL boundary. Route availability and disabled controls are UX behavior, not authorization.

## Current Routes

| Route | Version 1 status |
|---|---|
| `/overview` | Functional authenticated overview |
| `/patients` | Functional protected patient search |
| `/patients/new` | Functional patient registration with duplicate-candidate feedback |
| `/patients/:patientId` | Functional protected patient detail and patient-context banner |
| `/patients/:patientId/clinical` | Functional clinical record: problems, allergies, observations, notes, signing, amendments and timeline |
| `/privacy` | Functional consent/privacy decision and break-glass workspace |
| `/audit` | Functional restricted audit viewer |
| `/scheduling` | Functional booking, reschedule/cancel and waitlist workspace |
| `/encounters` | Functional encounter creation and lifecycle workspace |
| `/work-queues` | Intentional placeholder; no Version 1 work-queue domain implemented |
| `/interoperability` | UI placeholder; M9 backend terminology/interoperability contract queries are implemented |
| `/administration` | Intentional placeholder for broader administration UX |

Protected dynamic routes render client-side because they depend on the stateful Laravel session and authorized GraphQL calls.

## Patient Context and Clinical Safety

The reusable patient-context service/banner is part of the clinical safety boundary. Clinical workflows must keep the selected patient visible and send organization, facility, patient and encounter context that Laravel independently revalidates.

UI state must never be treated as security evidence. A stale or manipulated client request is still subject to backend authorization, privacy and binding checks.

## Accessibility Baseline

Version 1 source/test hardening includes:

- visible `:focus-visible` treatment on primary interactive controls;
- accessible names on primary clinical editor controls;
- semantic tablist/tab roles;
- roving tab `tabindex`;
- `ArrowLeft`, `ArrowRight`, `Home` and `End` tab keyboard behavior.

The release candidate still requires a manual keyboard/visual accessibility smoke check; automated component tests do not replace that evidence.

## Local Browser Verification

Start the application profile, migrate and seed synthetic data:

```powershell
scripts\docker.cmd up
scripts\docker.cmd migrate
docker exec aurevia-health-api php artisan db:seed --force
```

Open `http://localhost:4200` and use only synthetic patients. A practical Version 1 smoke flow is:

1. sign in with the documented local-only demo account;
2. search and open a synthetic patient;
3. review/manage privacy state as permitted;
4. book an appointment;
5. create, arrive and start an encounter;
6. open the clinical record, record basic clinical information and sign a note;
7. complete the encounter;
8. review the timeline and restricted audit evidence.

For the release decision and remaining manual checks, use [`V1_RELEASE_CANDIDATE_EVIDENCE.md`](V1_RELEASE_CANDIDATE_EVIDENCE.md).
