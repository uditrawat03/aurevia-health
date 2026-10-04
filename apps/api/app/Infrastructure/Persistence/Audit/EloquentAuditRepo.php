<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Audit;

use App\Domains\Audit\Data\AuditEventCollectionData;
use App\Domains\Audit\Data\AuditEventData;
use App\Domains\Audit\Data\AuditRecordData;
use App\Domains\Audit\Repositories\AuditRepo;
use App\Models\AuditEvent;
use Carbon\CarbonInterface;
use RuntimeException;

final readonly class EloquentAuditRepo implements AuditRepo
{
    public function append(AuditRecordData $record): AuditEventData
    {
        $event = AuditEvent::query()->create([
            'actor_user_id' => $record->actorUserId,
            'organization_id' => $record->organizationId,
            'facility_id' => $record->facilityId,
            'patient_id' => $record->patientId,
            'resource_type' => $record->resourceType->value,
            'resource_id' => $record->resourceId,
            'action' => $record->action->value,
            'outcome' => $record->outcome->value,
            'correlation_id' => $record->correlationId,
            'occurred_at' => $record->occurredAt,
        ]);

        return $this->map($event);
    }

    public function latestForOrganization(string $organizationId, int $limit): AuditEventCollectionData
    {
        $models = AuditEvent::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $events = [];
        foreach ($models as $model) {
            if ($model instanceof AuditEvent) {
                $events[] = $this->map($model);
            }
        }

        return new AuditEventCollectionData($events);
    }

    private function map(AuditEvent $event): AuditEventData
    {
        $occurredAt = $event->getAttribute('occurred_at');
        if (! $occurredAt instanceof CarbonInterface) {
            throw new RuntimeException('Audit event timestamp is invalid.');
        }

        $actorUserId = $event->getAttribute('actor_user_id');
        $organizationId = $event->getAttribute('organization_id');
        $facilityId = $event->getAttribute('facility_id');
        $patientId = $event->getAttribute('patient_id');
        $resourceId = $event->getAttribute('resource_id');

        return new AuditEventData(
            id: (string) $event->getKey(),
            actorUserId: is_int($actorUserId) ? $actorUserId : null,
            organizationId: is_string($organizationId) ? $organizationId : null,
            facilityId: is_string($facilityId) ? $facilityId : null,
            patientId: is_string($patientId) ? $patientId : null,
            resourceType: (string) $event->getAttribute('resource_type'),
            resourceId: is_string($resourceId) ? $resourceId : null,
            action: (string) $event->getAttribute('action'),
            outcome: (string) $event->getAttribute('outcome'),
            correlationId: (string) $event->getAttribute('correlation_id'),
            occurredAt: $occurredAt->toIso8601String(),
        );
    }
}
