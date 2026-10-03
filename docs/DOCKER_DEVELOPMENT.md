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

## Docker-first Application Bootstrap

On Windows, the Docker bootstrap avoids PowerShell execution-policy requirements and does not require host PHP/Composer/Node installations:

```cmd
scripts\bootstrap-docker.cmd
```

On macOS/Linux:

```bash
scripts/bootstrap-docker.sh
```

The command uses the `tools` profile to generate Laravel 13, install Sanctum/API support and Horizon, create the initial domain directories, and generate Angular 22 with routing, SCSS, strict mode, standalone APIs, zoneless mode, and Vitest.

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

## Angular API Proxy

The Angular dev server proxies `/api` and `/sanctum` to `http://api:8000`. Browser code can use relative API URLs without knowing Docker service hostnames.

## Migrations

Migrations are intentionally not automatic on container start.

```cmd
scripts\docker.cmd migrate
```

## Tests

```cmd
scripts\docker.cmd test
```

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

After npm dependency changes:

```powershell
docker compose -f .\infrastructure\docker\compose.yml --profile app exec web npm ci
```

## Security Boundary

The local database password is deliberately marked as development-only. Production secrets must not be placed in Dockerfiles, Compose files, committed `.env` files, Angular source, or image layers. Production images require a separate hardening and deployment milestone.
