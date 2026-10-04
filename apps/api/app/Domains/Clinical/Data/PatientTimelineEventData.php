<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class PatientTimelineEventData
{
    public function __construct(
        public string $id,
        public string $type,
        public string $label,
        public string $resourceType,
        public string $resourceId,
        public ?string $encounterId,
        public int $actorUserId,
        public string $occurredAt,
    ) {}
}
