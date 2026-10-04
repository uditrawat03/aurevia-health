# Frontend Workspace Navigation

## Purpose

Aurevia Health uses real Angular routes for authenticated workspace navigation. The shell no longer uses static hash anchors for domain areas.

This first V1-M5 frontend stage makes the foundations already implemented in V1-M3 and V1-M4 directly testable from the browser while the consent/privacy policy backend is added in the next M5 stage.

## Routes

| Route | State |
|---|---|
| `/overview` | Functional foundation overview |
| `/patients` | Functional protected patient search |
| `/patients/new` | Functional patient registration and duplicate-candidate result |
| `/patients/:patientId` | Functional protected patient read and patient-context banner |
| `/privacy` | M5 patient-context workspace; consent/policy controls added next |
| `/audit` | Functional restricted audit viewer |
| `/scheduling` | Routed placeholder for V1-M6 |
| `/encounters` | Routed placeholder for V1-M7 |
| `/work-queues` | Routed placeholder |
| `/interoperability` | Routed placeholder for V1-M9 |
| `/administration` | Routed placeholder |

Authenticated routes are rendered client-side because they depend on the stateful Laravel session and protected GraphQL calls. The Angular server route therefore uses client rendering rather than prerendering protected/dynamic patient routes.

## Local Browser Verification

Start the Docker application profile, migrate, and seed the local synthetic workspace:

```powershell
docker compose -f .\infrastructure\docker\compose.yml --profile app up -d
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan db:seed
```

Open `http://localhost:4200` and sign in with the development-only account documented in `README.md`.

### Test patient search

1. Open **Patients**.
2. Search `DEMO`.
3. Confirm the two seeded synthetic patients appear.
4. Open a patient.
5. Confirm the patient-context banner and protected patient details load.

### Test patient registration

1. Open **Patients → New patient**.
2. Use synthetic demographics only.
3. Select `Demo General Hospital`.
4. Enter a unique synthetic MRN.
5. Register.
6. Confirm the patient is returned.
7. Repeat similar demographics with a different MRN to inspect duplicate candidates; the UI must not auto-merge records.

### Test audit evidence

Open **Audit** using the local demo owner. Patient searches, reads, registrations, and authorization decisions should create or surface the structured evidence supported by the backend.

## Privacy Stage

`/privacy` is intentionally patient-context-aware before consent controls are enabled. The next V1-M5 patch adds:

- persisted consent records;
- data category, purpose, recipient class, and effective period;
- revocation;
- explicit privacy decisions;
- break-glass reason and audit evidence;
- server enforcement so Angular cannot bypass policy.

The privacy layer extends organization/facility authorization; it does not replace RBAC.
