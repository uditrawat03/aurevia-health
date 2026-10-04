<?php

declare(strict_types=1);

namespace App\Application\Audit;

use App\Domains\Audit\Data\AuditEventCollectionData;
use App\Domains\Audit\Data\AuditRecordData;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Audit\Repositories\AuditRepo;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Support\CorrelationId;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use RuntimeException;

final readonly class AuditService
{
    public const int DEFAULT_VIEWER_LIMIT = 50;
    public const int MAX_VIEWER_LIMIT = 100;

    private const string PENDING_DENIALS_ATTRIBUTE = 'aurevia.audit.pending_denials';

    public function __construct(private AuditRepo $audits) {}

    public function recordAuthorizationDecision(
        int $actorUserId,
        string $organizationId,
        OrganizationPermission $permission,
        ?string $facilityId,
        AuditOutcome $outcome,
        ?string $patientId = null,
    ): void {
        $record = new AuditRecordData(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            resourceType: $this->resourceTypeFor($permission, $facilityId),
            resourceId: $this->resourceIdFor(
                permission: $permission,
                organizationId: $organizationId,
                facilityId: $facilityId,
                patientId: $patientId,
            ),
            action: AuditAction::fromOrganizationPermission($permission),
            outcome: $outcome,
            correlationId: $this->correlationId(),
            occurredAt: now()->toIso8601String(),
        );

        if ($outcome === AuditOutcome::DENIED) {
            $this->deferDenied($record);

            return;
        }

        $this->audits->append($record);
    }

    public function recordOrganizationCreated(int $actorUserId, string $organizationId): void
    {
        $this->audits->append(new AuditRecordData(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: null,
            patientId: null,
            resourceType: AuditResourceType::ORGANIZATION,
            resourceId: $organizationId,
            action: AuditAction::CREATE_ORGANIZATION,
            outcome: AuditOutcome::ALLOWED,
            correlationId: $this->correlationId(),
            occurredAt: now()->toIso8601String(),
        ));
    }

    public function recordPatientOperation(
        int $actorUserId,
        string $organizationId,
        ?string $facilityId,
        string $patientId,
        AuditAction $action,
        AuditResourceType $resourceType = AuditResourceType::PATIENT,
        ?string $resourceId = null,
        AuditOutcome $outcome = AuditOutcome::ALLOWED,
    ): void {
        $record = new AuditRecordData(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            resourceType: $resourceType,
            resourceId: $resourceId ?? $patientId,
            action: $action,
            outcome: $outcome,
            correlationId: $this->correlationId(),
            occurredAt: now()->toIso8601String(),
        );

        if ($outcome === AuditOutcome::DENIED) {
            $this->deferDenied($record);

            return;
        }

        $this->audits->append($record);
    }

    public function organizationEvents(string $organizationId, int $limit): AuditEventCollectionData
    {
        $isInvalidLimit = $limit < 1 || $limit > self::MAX_VIEWER_LIMIT;
        if ($isInvalidLimit) {
            throw new DomainException('Audit event limit must be between 1 and 100.');
        }

        return $this->audits->latestForOrganization($organizationId, $limit);
    }

    public function flushDeferredDenials(): void
    {
        $request = $this->currentRequest();
        $pending = $request->attributes->get(self::PENDING_DENIALS_ATTRIBUTE, []);
        $request->attributes->remove(self::PENDING_DENIALS_ATTRIBUTE);

        if (! is_array($pending)) {
            throw new RuntimeException('Pending audit denials have an invalid request shape.');
        }

        foreach ($pending as $record) {
            if (! $record instanceof AuditRecordData) {
                throw new RuntimeException('Pending audit denial has an invalid record type.');
            }

            $this->audits->append($record);
        }
    }

    private function deferDenied(AuditRecordData $record): void
    {
        $request = $this->currentRequest();
        $pending = $request->attributes->get(self::PENDING_DENIALS_ATTRIBUTE, []);
        if (! is_array($pending)) {
            throw new RuntimeException('Pending audit denials have an invalid request shape.');
        }

        $pending[] = $record;
        $request->attributes->set(self::PENDING_DENIALS_ATTRIBUTE, $pending);
    }

    private function correlationId(): string
    {
        $correlationId = $this->currentRequest()->attributes->get(CorrelationId::REQUEST_ATTRIBUTE);
        $hasCorrelationId = is_string($correlationId) && $correlationId !== '';
        if (! $hasCorrelationId) {
            throw new RuntimeException('A correlation ID is required for audit evidence.');
        }

        return $correlationId;
    }

    private function currentRequest(): Request
    {
        return App::make(Request::class);
    }

    private function resourceTypeFor(
        OrganizationPermission $permission,
        ?string $facilityId,
    ): AuditResourceType {
        return match ($permission) {
            OrganizationPermission::VIEW_SETTINGS,
            OrganizationPermission::MANAGE_SETTINGS => AuditResourceType::OPERATIONAL_SETTINGS,
            OrganizationPermission::MANAGE_MEMBERSHIPS => AuditResourceType::ORGANIZATION_MEMBERSHIP,
            OrganizationPermission::VIEW_AUDIT => AuditResourceType::AUDIT_EVENT,
            OrganizationPermission::VIEW_PATIENTS,
            OrganizationPermission::MANAGE_PATIENTS => AuditResourceType::PATIENT,
            OrganizationPermission::REVIEW_PATIENT_MERGES => AuditResourceType::PATIENT_MERGE_REVIEW,
            OrganizationPermission::VIEW_CONSENTS,
            OrganizationPermission::MANAGE_CONSENTS => AuditResourceType::PATIENT_CONSENT,
            OrganizationPermission::BREAK_GLASS_PATIENT_ACCESS => AuditResourceType::BREAK_GLASS_ACCESS,
            OrganizationPermission::VIEW_SCHEDULE,
            OrganizationPermission::MANAGE_SCHEDULE => AuditResourceType::APPOINTMENT,
            OrganizationPermission::MANAGE_SCHEDULING_CONFIGURATION => AuditResourceType::SCHEDULING_RESOURCE,
            OrganizationPermission::VIEW_ENCOUNTERS,
            OrganizationPermission::MANAGE_ENCOUNTERS => AuditResourceType::ENCOUNTER,
            OrganizationPermission::VIEW_ORGANIZATION,
            OrganizationPermission::MANAGE_ORGANIZATION => $facilityId === null
                ? AuditResourceType::ORGANIZATION
                : AuditResourceType::FACILITY,
        };
    }

    private function resourceIdFor(
        OrganizationPermission $permission,
        string $organizationId,
        ?string $facilityId,
        ?string $patientId,
    ): ?string {
        if (in_array($permission, [
            OrganizationPermission::VIEW_AUDIT,
            OrganizationPermission::REVIEW_PATIENT_MERGES,
            OrganizationPermission::VIEW_SCHEDULE,
            OrganizationPermission::MANAGE_SCHEDULE,
            OrganizationPermission::VIEW_ENCOUNTERS,
            OrganizationPermission::MANAGE_ENCOUNTERS,
        ], true)) {
            return null;
        }

        if (in_array($permission, [
            OrganizationPermission::VIEW_PATIENTS,
            OrganizationPermission::MANAGE_PATIENTS,
            OrganizationPermission::VIEW_CONSENTS,
            OrganizationPermission::MANAGE_CONSENTS,
            OrganizationPermission::BREAK_GLASS_PATIENT_ACCESS,
        ], true)) {
            return $patientId;
        }

        return $facilityId ?? $organizationId;
    }
}
