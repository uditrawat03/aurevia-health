# Browser End-to-End Testing

## Purpose

Aurevia Health uses a Dockerized browser-acceptance layer to verify critical user workflows across the real Angular and Laravel boundaries before Version 2 expands the clinical surface.

The suite is intentionally separate from Laravel tests and Angular Vitest tests:

- Laravel unit/integration/feature tests remain the primary home for domain, persistence, authorization and GraphQL behavior;
- Angular Vitest remains the primary home for component/service/state behavior;
- browser E2E verifies a small number of high-value workflows through an actual browser, cookies, CSRF/session bootstrap, Angular routing, GraphQL proxying and Laravel authorization.

Browser E2E must not become the only place where a safety rule is tested.

## Runtime Baseline

The browser runner is pinned to:

- Python `3.14.7`;
- pytest `9.1.1`;
- Playwright `1.63.0`;
- Playwright-managed Chromium.

Playwright `1.63.0` explicitly supports Python 3.14. The project uses a custom Python 3.14 Bookworm image rather than the prebuilt Playwright Python image so the Python runtime is explicit and controlled.

The Docker image installs Chromium plus its Linux system dependencies with:

```text
python -m playwright install --with-deps chromium
```

## Architecture

`infrastructure/docker/compose.e2e.yml` is a standalone Docker Compose project named `aurevia-health-e2e`.

```text
pytest / Playwright / Chromium
            |
            v
 http://aurevia.test:4200
            |
            v
         Angular
       /graphql
       /sanctum
            |
            v
         Laravel
        /       \
 PostgreSQL     Redis
                  |
                Horizon
```

The test web service owns the internal network alias `aurevia.test`. Laravel session/Sanctum settings are pinned for that synthetic origin.

Angular uses the dedicated `serve:e2e` configuration from `apps/web/angular.json`. That configuration explicitly allows only the E2E/internal development hosts (`aurevia.test`, `web`, `localhost`, and `127.0.0.1`) instead of disabling host validation globally. The normal development serve configuration is unchanged.

The E2E stack does not publish PostgreSQL, Redis, Laravel or Angular ports to the host. Browser traffic stays inside the dedicated Docker network.

## Run on Windows

From the repository root:

```cmd
scripts\docker.cmd e2e
```

or directly:

```cmd
scripts\browser-e2e.cmd
```

The command cleans any prior E2E containers/volumes, runs the suite, preserves the pytest exit code, and tears down the isolated stack. When a dependency fails, it prints Compose service status and service logs before teardown so startup failures are diagnosable from the same command output.

No host Python, Playwright or Chromium installation is required.

## Deterministic Synthetic Data

The API E2E service starts with a dedicated `aurevia_health_e2e` PostgreSQL database. It runs:

```text
php artisan migrate --force
php artisan db:seed --force
```

before starting the Laravel server.

Composer and npm installation run as explicit `api-deps` and `web-deps` init services before Laravel or Angular health checks begin. The application services use a fixed synthetic `APP_KEY` supplied only by the E2E Compose environment and do not depend on or mutate the developer's host `.env` file. This keeps first-run dependency installation from being mistaken for an unhealthy application startup.

The Composer init service also treats remote package transport as an infrastructure dependency rather than an application failure. It limits parallel HTTP downloads, retries a failed dist installation up to three times, clears only the contents of the mounted `vendor` volume between attempts, and finally falls back to source installation. This is intended to tolerate transient GitHub/codeload failures such as incomplete HTTP/2 responses while keeping the lock file authoritative. Repeated failure of both dist and source installation remains a hard E2E failure.

The current smoke path uses the committed local-development synthetic account:

```text
owner@aurevia.local
```

and the seeded synthetic patient records. These credentials and records are test fixtures only.

Never point this Compose file at a production database, production Redis instance, real healthcare account or real patient dataset.

## Initial Standing Smoke Flow

`tests/browser/tests/test_workspace_smoke.py` verifies:

1. an unauthenticated browser sees the sign-in experience;
2. the synthetic owner can sign in through the real CSRF/session/GraphQL flow;
3. the authenticated clinical workspace renders;
4. the user can navigate through the sidebar to the patient directory;
5. seeded synthetic patients are returned through the live GraphQL path;
6. sign-out returns to the unauthenticated state.

This deliberately stays small. New Version 2 milestones should add critical workflow acceptance paths without recreating every backend test in the browser.

## Locator Rules

Prefer Playwright locators in this order:

1. role + accessible name;
2. form label;
3. stable visible semantic text;
4. a purpose-built `data-testid` only when no semantic locator exists.

Do not select elements by Tailwind classes, visual position or deeply nested CSS/XPath structure.

A browser test should survive harmless visual refactoring.

## Waiting Rules

Never use arbitrary sleeps such as:

```python
time.sleep(2)
```

Playwright assertions and locators auto-wait. Wait for meaningful application state such as:

- a role/label becoming visible;
- a URL transition;
- a loading indicator disappearing;
- expected text/data appearing;
- a specific network response when that response is itself the behavior under test.

## Failure Evidence

Each test starts a Playwright trace. On failure the fixture writes:

- a full-page PNG screenshot;
- a Playwright trace ZIP.

Artifacts are written under:

```text
artifacts/browser-e2e/
```

The directory is ignored by Git. GitHub Actions uploads failure artifacts for seven days.

Failure artifacts are allowed only because the E2E environment uses synthetic data. Do not create browser evidence from real patient environments.

## CI

`.github/workflows/quality.yml` contains a `Browser E2E` job.

CI uses the same Compose file as local Docker acceptance:

```bash
docker compose \
  -f infrastructure/docker/compose.e2e.yml \
  up \
  --build \
  --abort-on-container-exit \
  --exit-code-from browser-e2e \
  browser-e2e
```

The job fails when pytest fails, a dependency service cannot become healthy, migrations/seeding fail, or the browser container exits non-zero.

## Test Placement

```text
tests/browser/
├── conftest.py
├── pytest.ini
├── requirements.txt
└── tests/
    └── test_workspace_smoke.py
```

Keep reusable browser-only fixtures in `conftest.py`. As the suite grows, prefer small page/workflow helpers organized by domain instead of one large helper class.

Do not put Laravel database helpers or Angular implementation details into the Python suite.

## Version Upgrade Rule

Playwright requires browser binaries matched to the Playwright package version. When upgrading Playwright:

1. update the pinned Python dependency;
2. rebuild the browser Docker image so matching browser binaries are installed;
3. run `scripts\docker.cmd e2e`;
4. run the complete Quality workflow;
5. update this document if the runtime or operating-system baseline changes.

Python patch-version changes must also rebuild and rerun the Dockerized suite before merge.
