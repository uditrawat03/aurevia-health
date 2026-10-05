# GraphQL Application Boundary

Aurevia Health uses GraphQL as the first-party application boundary between Angular and Laravel. Ordinary product reads and writes must not introduce parallel REST CRUD endpoints. The Version 1 schema now composes organization, identity, audit, patient, privacy, scheduling, encounter, clinical and interoperability domains.

## Server

The Laravel application uses Nuwave Lighthouse 6.x. The canonical endpoint is:

```text
/graphql
```

The Angular development server proxies `/graphql` to the Laravel container, so browser code can use the same relative endpoint in local development.

## Request Path

```text
Angular feature
    ↓
typed GraphQL client layer
    ↓
/graphql
    ↓
Lighthouse resolver
    ↓
application service/query
    ↓
model or repository
    ↓
PostgreSQL
```

Resolvers are boundary adapters only. They may authorize, marshal typed GraphQL input, call application behavior, and map results to the schema contract. Business logic and persistence queries do not belong in resolvers.

## Initial Security Defaults

The initial server configuration establishes:

- the Laravel `web` middleware group for session cookies and CSRF protection;
- Lighthouse authentication attempts using the `web` guard;
- query complexity limit `500`;
- query depth limit `12`;
- maximum page size `100` with a default of `25`;
- relationship batch loading enabled;
- transactional Lighthouse mutations enabled;
- model mass-assignment bypass disabled;
- optional production introspection disabling through configuration;
- correlation IDs on GraphQL responses and GraphQL error extensions, including requests rejected before GraphQL execution.

These limits are engineering defaults. A later load/security review may tighten them based on measured clinical workflows.

Laravel 13 blocks unserializing arbitrary PHP classes from generic cache storage by default through `cache.serializable_classes = false`. Lighthouse parsed-query cache entries contain GraphQL AST objects, so Aurevia does not persist those objects through the Redis application cache.

The parsed-query cache is disabled by default in `local` and `testing` environments. Non-local environments use Lighthouse's `opcache` query-cache mode by default, which keeps parsed queries out of Redis while retaining production query-cache performance. `CACHE_STORE=redis` remains the application cache baseline for sessions, queues, and ordinary cache values.

Deployments may override this behavior with `LIGHTHOUSE_QUERY_CACHE_ENABLE` and `LIGHTHOUSE_QUERY_CACHE_MODE`. Do not switch the query cache back to `store` or `hybrid` merely by broadly enabling arbitrary cache-class unserialization; any allow-list change requires a separate security review.

When changing Lighthouse query-cache configuration, clear existing Lighthouse/Laravel caches before serving traffic: `php artisan lighthouse:clear-cache` followed by `php artisan optimize:clear`.

## Correlation IDs

Clients may send:

```text
X-Correlation-ID: <safe-client-id>
```

When absent or malformed, Laravel generates a ULID. Correlation-ID middleware runs before the `web` middleware group so even CSRF-rejected GraphQL requests receive the effective ID in the response header. GraphQL execution errors also include:

```json
{
  "extensions": {
    "correlationId": "..."
  }
}
```

Do not put patient identifiers or other sensitive healthcare data in correlation IDs.

## Schema Layout

```text
apps/api/graphql/
├── schema.graphql
├── system/
├── organization/
├── identity/
├── audit/
├── patient/
├── privacy/
├── scheduling/
├── encounter/
├── clinical/
└── interoperability/
```

`schema.graphql` composes domain-owned schema files. New domains continue this pattern rather than turning the root schema into one large file.

The base system contract remains:

```graphql
query SystemInfo {
  systemInfo {
    name
    version
    graphqlEndpoint
  }
}
```

## Maintain the GraphQL Dependency

For an intentional Lighthouse dependency update with the Docker stack running:

```cmd
scripts\graphql.cmd
```

macOS/Linux:

```bash
scripts/graphql.sh
```

The script updates the Composer lockfile for Lighthouse, clears Laravel caches, and validates the schema. The changed `apps/api/composer.lock` must be committed with the milestone.

Equivalent manual commands:

```powershell
docker exec aurevia-health-api composer update nuwave/lighthouse --with-all-dependencies --no-interaction --no-progress
docker exec aurevia-health-api php artisan optimize:clear
docker exec aurevia-health-api php artisan lighthouse:validate-schema
```

## Verification

Run:

```powershell
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit
```

The public foundation query can be tested with an HTTP GET without bypassing CSRF protection:

```powershell
$query = [uri]::EscapeDataString('{ systemInfo { name version graphqlEndpoint } }')
Invoke-RestMethod -Method Get -Uri "http://localhost:8000/graphql?query=$query"
```

All browser POST requests to `/graphql` pass through Laravel's `web`/CSRF middleware, including query operations sent via POST. The public foundation query may use GET for a simple read check. Browser-authenticated GraphQL clients must obtain the Sanctum CSRF cookie and send the XSRF token before POST traffic. Feature tests that exercise POST requests provide a valid test CSRF token rather than disabling the middleware.

### Test-environment isolation

The PHPUnit configuration pins the test environment in both the process environment and `$_SERVER`. Docker injects runtime values such as `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, and `SESSION_DRIVER=redis` into both scopes, while PHPUnit's `<env>` entries only replace the process environment and `$_ENV`. Laravel's environment repository reads `$_SERVER` first, so the matching `<server>` entries are required to prevent Docker's Redis-backed runtime stores from leaking into tests. Tests therefore run deterministically with `APP_ENV=testing`, the in-memory array cache/session stores, the synchronous queue, and the SQLite test database. The negative CSRF feature test temporarily switches the application environment only for that request because Laravel intentionally bypasses request-forgery validation while `APP_ENV=testing`.

## HTTP Exceptions

GraphQL is not used for protocols that require a different HTTP contract. Approved exceptions include health/readiness, Sanctum CSRF/session bootstrap, OAuth/OIDC/SAML callbacks, inbound webhooks, signed object-storage handoffs, operational metrics, and FHIR/HL7/DICOM/EDI/partner adapters.

An exception must remain explicit and must not become a second application CRUD API.
