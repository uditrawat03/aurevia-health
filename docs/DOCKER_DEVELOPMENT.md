# Docker Development Environment

## Purpose

Aurevia Health uses Docker Compose for a reproducible local runtime covering PostgreSQL, Redis, Laravel, Horizon, and Angular. These are development images, not production deployment images.

## Compose Profiles

The default profile starts persistent infrastructure only:

```text
postgres
redis
```

The `app` profile adds:

```text
api
horizon
web
```

The `tools` profile exposes PHP/Composer and Node tooling containers for framework commands.

## Ports

| Service | Host | Container |
|---|---:|---:|
| Angular | 4200 | 4200 |
| Laravel | 8000 | 8000 |
| PostgreSQL | 5432 | 5432 |
| Redis | 6379 | 6379 |

Override host ports with `WEB_PORT`, `API_PORT`, `POSTGRES_PORT`, or `REDIS_PORT`.

## Volumes

PostgreSQL and Redis use named data volumes. Laravel `vendor` and Angular `node_modules` also use named volumes so Windows host dependencies are not mixed with Linux container dependencies. Application source is bind-mounted for fast editing.

The Docker application profile explicitly uses Redis for Laravel cache, queues, and sessions. This is also important for Lighthouse because its parsed-query cache uses Laravel's default cache store. Do not leave `CACHE_STORE=database` in the Docker runtime unless the database cache tables are intentionally provisioned and the architecture decision is changed.

## Docker-first Application Bootstrap

On Windows, the Docker bootstrap avoids PowerShell execution-policy requirements and does not require host PHP/Composer/Node installations:

```cmd
scripts\bootstrap-docker.cmd
```

On macOS/Linux:

```bash
scripts/bootstrap-docker.sh
```

The command uses the `tools` profile to generate Laravel 13, install Sanctum, Lighthouse, and Horizon, create the initial domain directories, and generate Angular 22 with routing, SCSS, strict mode, standalone APIs, zoneless mode, and Vitest.

It refuses to overwrite an existing `apps/api` or `apps/web` application.

## Start Infrastructure Only

This works before Laravel and Angular are generated:

```cmd
scripts\docker.cmd infra
```

Equivalent command:

```powershell
docker compose -f .\infrastructure\docker\compose.yml up -d postgres redis
```

## Start the Full Stack

After `apps/api` and `apps/web` exist:

```cmd
scripts\docker.cmd up
```

Open:

- Angular: `http://localhost:4200`
- Laravel health endpoint: `http://localhost:8000/up`
- GraphQL endpoint: `http://localhost:8000/graphql`

The API container deliberately starts Laravel with `php artisan serve --no-reload`. Laravel 13 otherwise filters most inherited environment variables before spawning the PHP development-server child process when a `.env` file exists. Because Docker Compose supplies the PostgreSQL, Redis, cache, queue, and session settings as container environment variables, removing `--no-reload` can make HTTP requests fall back to values from `apps/api/.env` while `docker exec ... php artisan` still uses the Compose values. Keep `--no-reload` in the Docker command and restart the API container after changing runtime environment variables.

## Angular GraphQL Proxy

The Angular dev server proxies `/graphql` and `/sanctum` to `http://api:8000`. First-party feature code uses the typed GraphQL client boundary; generic `/api` domain traffic is intentionally not proxied.

## Migrations

Migrations are intentionally not automatic on container start.

```cmd
scripts\docker.cmd migrate
```

## Tests

Backend and Angular unit/integration suites:

```cmd
scripts\docker.cmd test
```

Full browser acceptance:

```cmd
scripts\docker.cmd e2e
```

The E2E command uses `infrastructure/docker/compose.e2e.yml`, a separate `aurevia-health-e2e` Docker project with isolated PostgreSQL/Redis data, Laravel/Horizon/Angular services and a Python 3.14 Playwright runner. It migrates and seeds only synthetic data, runs Chromium inside the Docker network, writes failure screenshots/traces under `artifacts/browser-e2e/`, and removes E2E volumes after the run.

This stack must remain isolated from production data and from the normal development database. See [BROWSER_E2E_TESTING.md](BROWSER_E2E_TESTING.md).

## Verify Runtime Stores

After changing Docker or Laravel environment configuration, verify the effective stores:

```powershell
docker exec aurevia-health-api php artisan tinker --execute="dump(config('cache.default'), config('queue.default'), config('session.driver'));"
```

The Docker application profile should report `redis` for all three values. If the API or Horizon logs show `relation "cache" does not exist`, the runtime is still using Laravel's database cache driver and the application containers should be recreated after applying the current Compose configuration.

## Status and Logs

```cmd
scripts\docker.cmd status
scripts\docker.cmd logs
```

## Stop

```cmd
scripts\docker.cmd down
```

The helper does not remove named volumes. Avoid `docker compose down -v` unless you intentionally want to delete the local PostgreSQL and Redis data.

## Dependency Changes

After Composer dependency changes:

```powershell
docker compose -f .\infrastructure\docker\compose.yml --profile app exec api composer install
```

For the GraphQL foundation dependency update:

```cmd
scripts\graphql.cmd
```

After npm dependency changes:

```powershell
docker compose -f .\infrastructure\docker\compose.yml --profile app exec web npm ci
```

## Release-Candidate Local Gates

The development stack also supports repeatable synthetic release rehearsals:

```powershell
pwsh ./scripts/release-candidate.ps1
pwsh ./scripts/migration-rehearsal.ps1
pwsh ./scripts/backup-restore-test.ps1
pwsh ./scripts/performance-baseline.ps1
```

The migration, restore and performance scripts use isolated temporary PostgreSQL databases and must not be pointed at production data.

## Security Boundary

The local database password is deliberately marked as development-only. Production secrets must not be placed in Dockerfiles, Compose files, committed `.env` files, Angular source, or image layers. Production deployment starts from `apps/api/.env.production.example` and still requires deployment-specific secret management, TLS/proxy, storage and operational hardening.
