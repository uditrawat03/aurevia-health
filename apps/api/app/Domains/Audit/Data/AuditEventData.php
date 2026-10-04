<?php

declare(strict_types=1);

namespace App\Domains\Audit\Data;

final readonly class AuditEventData
{
    public function __construct(
        public string $id,
        public ?int $actorUserId,
        public ?string $organizationId,
        public ?string $facilityId,
        public ?string $patientId,
        public string $resourceType,
        public ?string $resourceId,
        public string $action,
        public string $outcome,
        public string $correlationId,
        public string $occurredAt,
    ) {}
}
