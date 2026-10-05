# Aurevia Health API

`apps/api` is the Laravel 13 application that owns Aurevia Health's Version 1 domain, authorization, privacy, audit and GraphQL boundaries.

## Responsibilities

The API is authoritative for:

- organization/facility scope and country-profile resolution;
- first-party session authentication and membership/role/facility authorization;
- patient identity and duplicate-candidate review;
- consent/privacy and break-glass decisions;
- scheduling and encounter lifecycle rules;
- core clinical record provenance and signed-note integrity;
- audit evidence;
- terminology and interoperability contracts.

The first-party application API is GraphQL at `/graphql`. Do not add ordinary Aurevia domain CRUD under `/api/...`; protocol/infrastructure exceptions must remain explicit.

## Local Runtime

From the repository root:

```powershell
scripts\docker.cmd up
scripts\docker.cmd migrate
```

Useful checks:

```powershell
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit
```

The local Docker profile uses PostgreSQL and Redis. Development `.env`/Compose defaults are not production configuration.

## Production Configuration

Start deployment configuration from `.env.production.example`, replace all example hosts, inject secrets through the deployment secret store, and review trusted hosts/proxies, TLS termination, session cookies, Sanctum stateful domains and operational access before serving traffic.

## Tests

- `tests/Unit` — isolated domain/application behavior without database access.
- `tests/Integration` — database-backed model/repository/service behavior.
- `tests/Feature` — GraphQL and approved HTTP-boundary behavior.
- `tests/Performance/V1PerformanceBaseline.php` — explicit local RC benchmark, not part of normal timing assertions.

Use synthetic healthcare data only.

## Further Documentation

- [`../../ARCHITECTURE.md`](../../ARCHITECTURE.md)
- [`../../CODING_GUIDELINES.md`](../../CODING_GUIDELINES.md)
- [`../../docs/GRAPHQL_BACKEND.md`](../../docs/GRAPHQL_BACKEND.md)
- [`../../docs/RELEASE_HARDENING.md`](../../docs/RELEASE_HARDENING.md)
- [`../../docs/README.md`](../../docs/README.md)
