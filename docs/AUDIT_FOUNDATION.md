# Audit Foundation

## Purpose

V1-M3 introduces structured, append-oriented audit evidence for the protected Aurevia Health organization boundary. It is deliberately separate from ordinary application logs and from the narrower `configuration_changes` evidence introduced in V1-M1.

The initial audit record captures:

- actor user ID when an authenticated actor exists;
- organization and optional facility scope;
- a nullable patient ID reserved for patient-scoped milestones;
- resource type and resource ID;
- stable action vocabulary;
- outcome (`ALLOWED` or `DENIED`);
- request correlation ID;
- occurrence timestamp.

Audit rows intentionally do not use cascading foreign keys to users, organizations, facilities, or future patients. Evidence must not disappear merely because an operational record is later removed. Database-administrator tamper resistance and external immutable retention remain hardening work beyond this foundation.

## Protected Operations

Every organization authorization decision now emits audit evidence. Existing protected GraphQL reads and writes already call `OrganizationAuthorizationService`, so the audit boundary covers organization hierarchy access, organization management, operational settings, and membership administration without moving persistence logic into resolvers.

Creating an organization is a special case because the organization does not yet exist when authorization begins. After the authenticated onboarding transaction creates the organization and owner membership, the mutation appends a `CREATE_ORGANIZATION` event.

Allowed events written during a transactional GraphQL mutation participate in that transaction. If the mutation fails later, the allowed event rolls back with it rather than claiming a successful protected operation.

## Denial Durability

Lighthouse wraps mutations in a transaction. Writing a denial inside the failing mutation would therefore be rolled back. V1-M3 avoids that failure mode by deferring denied audit records in request context and flushing them from `FlushPendingAuditEvents` after GraphQL execution has completed and the mutation transaction has rolled back.

This makes security-relevant authorization denials durable without disabling transactional mutations.

`AuditService` resolves the current HTTP request from Laravel at operation time instead of retaining a constructor-captured request. Lighthouse may reuse resolver instances across multiple GraphQL requests in the same application lifecycle, so deferred denial state and correlation IDs must always be read from the currently bound request.

## Restricted Viewer

The typed GraphQL query is:

```graphql
query AuditEvents($input: AuditEventsInput!) {
  auditEvents(input: $input) {
    id
    actorUserId
    organizationId
    facilityId
    patientId
    resourceType
    resourceId
    action
    outcome
    correlationId
    occurredAt
  }
}
```

Only organization-wide `OWNER` and `ADMIN` memberships receive `VIEW_AUDIT`. Facility-scoped memberships and the `CLINICIAN`, `STAFF`, and `VIEWER` roles cannot access the audit viewer. The viewer itself is audited.

The viewer returns newest events first and accepts a limit from 1 through 100, with a default of 50.

## Patient Context

`patient_id` is nullable in V1-M3 because the patient identity aggregate is introduced in V1-M4. The column and typed DTO field establish the audit contract now so patient-scoped modules can attach patient context without redesigning the audit schema.

No patient information or clinical payload is placed in generic audit metadata because this foundation intentionally avoids a schemaless JSON payload containing sensitive healthcare data.

## Verification

With the Docker application profile running:

```powershell
docker exec aurevia-health-api php artisan migrate
docker exec aurevia-health-api php artisan lighthouse:validate-schema
docker exec aurevia-health-api php artisan test
docker exec aurevia-health-api composer audit
git diff --check
```

Feature coverage must prove an allowed protected read, a denied protected mutation that survives transaction rollback, an owner/admin audit view, and denial of the viewer to an insufficient role.
