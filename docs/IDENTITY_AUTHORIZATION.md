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

| Role | Organization view | Organization manage | Settings view | Settings manage | Membership manage | Audit view |
|---|---:|---:|---:|---:|---:|---:|
| `OWNER` | yes | yes | yes | yes | yes | yes |
| `ADMIN` | yes | yes | yes | yes | yes | yes |
| `CLINICIAN` | yes | no | yes | no | no | no |
| `STAFF` | yes | no | yes | no | no | no |
| `VIEWER` | yes | no | yes | no | no | no |

The role matrix is only the RBAC portion of the decision. `OrganizationAccessPolicy` also evaluates facility scope and whether an operation requires all-facility authority. That contract is the Version 1 ABAC extension point.

Version 1 now extends this base policy with patient and encounter context plus explicit consent/privacy and break-glass decisions in the patient-facing domains. Department/care-team relationship, broader purpose-of-use and data-sensitivity ABAC remain extension points.

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
- role plus facility-scope policy behavior;
- durable structured audit evidence for allowed organization access and denied authorization decisions.

Authorization errors continue through the GraphQL correlation-ID error handler. V1-M3 also persists the actor, organization/resource scope, action, outcome, correlation ID, and timestamp. Denied mutation decisions are flushed after GraphQL transaction rollback so the security evidence is not lost with the rejected write.

## Current Limits

This is an authorization foundation, not the final enterprise identity program. It does not yet include:

- MFA/WebAuthn;
- OIDC or SAML enterprise federation;
- password recovery/user provisioning workflows;
- remote session inventory or revoke-other-session controls;
- enterprise-grade care-team/relationship ABAC and broader purpose/data-sensitivity policy attributes.

Consent and break-glass are no longer future-only concepts: V1-M5 applies them through the privacy boundary for relevant patient, scheduling, encounter and clinical workflows.

Membership revocation immediately removes organization authorization on the next request. V1-M3 now persists organization-scoped authorization evidence and exposes it only to all-facility owners and administrators. Authentication-specific login/logout event vocabulary and patient-scoped audit context can extend the same audit boundary as later milestones introduce those requirements.

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
