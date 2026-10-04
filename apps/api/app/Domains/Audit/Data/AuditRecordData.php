<?php

declare(strict_types=1);

namespace App\Domains\Audit\Data;

use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Audit\Enums\AuditResourceType;

final readonly class AuditRecordData
{
    public function __construct(
        public ?int $actorUserId,
        public ?string $organizationId,
        public ?string $facilityId,
        public ?string $patientId,
        public AuditResourceType $resourceType,
        public ?string $resourceId,
        public AuditAction $action,
        public AuditOutcome $outcome,
        public string $correlationId,
        public string $occurredAt,
    ) {}
}
