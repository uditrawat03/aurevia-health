# Aurevia Health Web

`apps/web` is the Angular 22 SSR frontend for Aurevia Health. It is a stateful first-party client of the Laravel GraphQL boundary and is not an independent security boundary.

## Frontend Model

- standalone Angular architecture;
- strict TypeScript and zoneless change detection;
- server-side rendering enabled;
- Vitest unit tests;
- Tailwind-based Aurevia design foundation;
- centralized GraphQL transport;
- Sanctum CSRF/session bootstrap for first-party authentication;
- persistent patient context on clinical workflows;
- keyboard/focus/accessibility behavior treated as release criteria.

Feature components must not call ordinary Laravel REST CRUD routes. Protected decisions remain authoritative on the backend even when the UI disables or hides an action.

## Local Development

From the repository root, the preferred path is:

```powershell
scripts\docker.cmd up
```

Open `http://localhost:4200`.

For a host-run frontend:

```powershell
cd apps/web
npm ci
npm start
```

The development server proxies `/graphql` and `/sanctum` to Laravel.

## Verification

```powershell
cd apps/web
npm test -- --watch=false
npm run build
npm audit --omit=dev --audit-level=high
```

The current Version 1 unit baseline is 6 test files / 9 tests. Manual keyboard/visual accessibility verification remains part of RC evidence.

## Current Workspaces

Functional Version 1 routes include overview, patient search/registration/detail, privacy, audit, scheduling, encounters and patient clinical record. Work queues, administration and the interoperability screen remain UI placeholders even though M9 backend interoperability contract queries are available.

See [`../../docs/FRONTEND_WORKSPACE_NAVIGATION.md`](../../docs/FRONTEND_WORKSPACE_NAVIGATION.md) for the route-level status and [`../../docs/UI_FOUNDATION.md`](../../docs/UI_FOUNDATION.md) for design/accessibility conventions.
