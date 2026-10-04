# Identity, Authentication and Authorization Foundation

## Purpose

V1-M2 establishes the first authenticated and authorized first-party application boundary for Aurevia Health. It combines Laravel Sanctum's stateful browser session model with organization membership, role permissions, facility scope, and a policy contract that can grow into richer ABAC decisions as patient, encounter, consent, purpose, and emergency context are introduced.

Angular remains a user-experience client. Laravel is authoritative for authentication and authorization.

## First-Party Session Flow

The browser flow is:

```text
Angular
  ↓ GET /sanctum/csrf-cookie
Laravel issues XSRF/session cookies
  ↓ POST /graphql login mutation
web guard authenticates + session ID regenerates
  ↓
me query restores authenticated shell state
  ↓
protected GraphQL queries/mutations
  ↓
logout mutation invalidates session + regenerates CSRF token
```

No long-lived first-party bearer token is written to `localStorage` or `sessionStorage`.

All GraphQL POST requests still use the `web` middleware and CSRF protection established by the GraphQL foundation. Authentication does not bypass CSRF.

## Identity Model

`users` remains Laravel's local identity table for this foundation. Organization access is modeled separately:

```text
User
  ↓
OrganizationMembership
  ├── organization
  ├── role
  ├── ACTIVE / REVOKED status
  ├── all-facilities flag
  └── selected facilities (when not all-facilities)
```

A user may have one membership per organization. A membership may grant all-facility access or an explicit set of facilities. Revoked memberships retain their row and scope metadata but no longer authorize access.

## Roles and Permissions

Initial roles:

| Role | Organization view | Organization manage | Settings view | Settings manage | Membership manage |
|---|---:|---:|---:|---:|---:|
| `OWNER` | yes | yes | yes | yes | yes |
| `ADMIN` | yes | yes | yes | yes | yes |
| `CLINICIAN` | yes | no | yes | no | no |
| `STAFF` | yes | no | yes | no | no |
| `VIEWER` | yes | no | yes | no | no |

The role matrix is only the RBAC portion of the decision. `OrganizationAccessPolicy` also evaluates facility scope and whether an operation requires all-facility authority. That contract is the Version 1 ABAC extension point.

Future attributes may include department, patient relationship, encounter, purpose of use, consent, jurisdiction, data sensitivity, and break-glass state.

## Facility Scope

A membership is either:

```text
allFacilities = true
```

or:

```text
allFacilities = false
facilityIds = [explicit allowed facilities]
```

A selected-facility membership cannot read the full organization hierarchy, because that would disclose sibling facilities. It may read resolved operational settings for an explicitly allowed facility when its role has `VIEW_SETTINGS`.

Creating health systems/facilities and organization-wide configuration changes require all-facility authority. Department creation may be performed by a role with organization-management permission when the target facility is inside that membership's facility scope. Department-level setting updates remain all-facility administration in this foundation until a department-to-facility authorization lookup is promoted into the policy boundary.

## Organization Creation

`createOrganization` is authenticated but has no pre-existing tenant to authorize against. The operation therefore runs as an onboarding transaction:

1. authenticate the actor;
2. create the country-neutral organization;
3. assign the actor an active `OWNER` membership with all-facility scope;
4. commit both changes together.

A partially provisioned organization without its initial owner membership is not committed.

## GraphQL Operations

Identity operations:

```graphql
query {
  me {
    id
    name
    email
    memberships {
      organizationId
      role
      status
      allFacilities
      facilityIds
    }
  }
}
```

```graphql
mutation Login($input: LoginInput!) {
  login(input: $input) {
    id
    name
    email
  }
}
```

```graphql
mutation {
  logout {
    loggedOut
  }
}
```

Organization owners/administrators may assign or revoke membership through the typed `assignOrganizationMembership` and `revokeOrganizationMembership` mutations. These operations require `MANAGE_MEMBERSHIPS` and all-facility scope.

The V1-M1 organization fields now also use Lighthouse `@guard` plus explicit organization authorization in their thin resolver adapters.

## Angular Boundary

`GraphqlClient` is the centralized first-party `/graphql` transport. `AuthService` owns the approved Sanctum CSRF bootstrap exception and typed identity operations. Feature components do not make ad hoc Laravel domain REST calls.

The root application renders the operational shell only when an authenticated user has been restored. An unauthenticated browser receives the sign-in surface instead.

## Security Behavior

The boundary explicitly tests:

- stateful session login;
- current-session logout/invalidation;
- automatic owner membership on organization creation;
- cross-organization denial;
- allowed selected-facility access;
- unselected-facility denial;
- revoked-membership denial;
- role plus facility-scope policy behavior.

Authorization errors continue through the GraphQL correlation-ID error handler, so denials remain traceable without exposing internal persistence details.

## Current Limits

This is an authorization foundation, not the final enterprise identity program. It does not yet include:

- MFA/WebAuthn;
- OIDC or SAML enterprise federation;
- password recovery/user provisioning workflows;
- remote session inventory or revoke-other-session controls;
- purpose-of-use, consent, patient relationship, care-team, or break-glass attributes;
- V1-M3 structured security/audit event persistence.

Membership revocation immediately removes organization authorization on the next request. The broader audit evidence for authentication, protected reads/writes, and denied access is introduced in V1-M3.

## Verification

With the Docker application profile running:

```powershell
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit

docker exec aurevia-health-web npm test -- --watch=false
docker exec aurevia-health-web npm run build
```

Also run `git diff --check` before commit. Only synthetic identities and organizations belong in tests.
