# Organization and Country Profile Foundation

## Purpose

This foundation models healthcare organizations without embedding jurisdiction-specific fields into the shared schema. It introduces the Version 1 organization hierarchy, server-controlled country-profile selection, typed operational configuration inheritance, and auditable configuration changes.

The core rule remains:

> Healthcare concepts belong to the core. Jurisdiction-specific regulation, reimbursement, terminology, and national infrastructure belong to country profiles.

## Organization Hierarchy

The first hierarchy is:

```text
Organization
├── Health System (optional grouping)
└── Facility
    └── Department
```

An organization may own multiple health systems and facilities. A facility may optionally belong to a health system, while every department belongs to one facility. Health systems are an organizational grouping; they are intentionally not a configuration-inheritance layer in Version 1.

Every organization-domain primary key is an internal ULID. Country identifiers, payer identifiers, national health identifiers, and local facility codes are never database primary keys.

## Country Profile Selection

Clients provide an ISO 3166-1 alpha-2 country code when creating an organization. The client does **not** choose a profile implementation or profile version.

The server resolves and persists both the profile code and profile version on the organization so later profile additions cannot silently change an existing tenant's behavior.

Initial profiles are:

| Country | Profile | Version | Locale default | Timezone default | Week start |
|---|---|---:|---|---|---|
| India (`IN`) | `IN` | `1.0.0` | `en-IN` | `Asia/Kolkata` | Monday |
| United Kingdom (`GB`) | `GB` | `1.0.0` | `en-GB` | `Europe/London` | Monday |
| United States (`US`) | `US` | `1.0.0` | `en-US` | inherited | Sunday |
| Other country codes | `CORE` | `1.0.0` | inherited | inherited | inherited |

The United States profile intentionally does not define one timezone because the country spans multiple IANA timezones. Organization or facility configuration selects the operational timezone.

`CORE` is the country-neutral fallback. Supporting a new country therefore does not require a fork of the organization schema.

## Operational Configuration

Version 1 begins with three country-neutral operational settings:

- locale;
- IANA timezone;
- week-start day.

Resolution follows the platform architecture:

```text
Global defaults
      ↓
Country profile
      ↓
Organization
      ↓
Facility
      ↓
Department
```

A narrower scope overrides only the values that it defines. Null means inherit. The resolved GraphQL result reports both the effective value and the layer that supplied it.

Global defaults are configured in `apps/api/config/organization.php` and currently resolve to `en`, `UTC`, and Monday.

## Configuration Change Evidence

Replacing organization, facility, or department overrides runs in one database transaction with append-only `configuration_changes` records for every value that changed. Each record contains:

- organization ID;
- scope type and scope ID;
- setting key;
- previous and new values;
- request correlation ID;
- change timestamp.

This configuration-change ledger remains narrowly scoped evidence. The completed V1 audit boundary additionally records protected authorization decisions, durable security-relevant denials and restricted audit-view access with stable action/resource vocabulary.

## GraphQL Boundary

The domain remains GraphQL-first. V1-M1 adds:

Queries:

```graphql
organization(id: ID!): Organization!
resolvedOperationalSettings(input: ResolveOperationalSettingsInput!): ResolvedOperationalSettings!
```

Mutations:

```graphql
createOrganization(input: CreateOrganizationInput!): Organization!
createHealthSystem(input: CreateHealthSystemInput!): HealthSystem!
createFacility(input: CreateFacilityInput!): Facility!
createDepartment(input: CreateDepartmentInput!): Department!
updateOperationalSettings(input: UpdateOperationalSettingsInput!): UpdatedOperationalSettings!
```

Inputs and outputs are explicitly typed. The configuration boundary does not expose a generic JSON settings blob. Resolvers only map GraphQL input, read request context such as the correlation ID, and delegate to application services. Persistence remains in `OrganizationRepo`.

## Isolation Rules

Organization IDs are scope identifiers, never authorization evidence. Repository lookups for child resources continue to include the owning organization, and V1-M2 now adds authenticated membership checks before GraphQL organization data reaches those application services.

Full organization hierarchy reads require all-facility scope so a facility-restricted user cannot discover sibling facilities. Resolved operational settings may be read only for a facility included in the actor's membership scope. Organization-wide administration remains restricted to memberships with the required role permission and all-facility scope.

## Authorization Boundary

The organization GraphQL queries and mutations now use the V1-M2 identity/authorization foundation. Organization creation requires authentication and creates an owner membership atomically. Reads, hierarchy changes, configuration changes, membership changes, and facility-scoped settings reads are authorized server-side before delegation to organization application services.

Structural tenant isolation remains in `OrganizationRepo`; user authorization is an additional boundary and does not replace repository ownership constraints.

## Migration and Verification

With the Docker application profile running:

```powershell
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit
```

Integration and database-backed GraphQL feature tests use `Tests\IntegrationTestCase`, `DatabaseTransactions`, and ordinary migrations. `RefreshDatabase` is not used.

These rules remain regression requirements for Version 1. Later identity, privacy, scheduling, encounter and clinical modules build on this organization/facility scope rather than replacing it.
