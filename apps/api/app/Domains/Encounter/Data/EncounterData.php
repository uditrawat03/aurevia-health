<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Data;

final readonly class EncounterData
{
    /** @param list<EncounterEventData> $events */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $patientDisplayName,
        public ?string $departmentId,
        public ?string $appointmentId,
        public string $type,
        public string $status,
        public ?string $arrivedAt,
        public ?string $startedAt,
        public ?string $endedAt,
        public ?string $cancelledAt,
        public ?string $cancellationReason,
        public int $createdByUserId,
        public string $createdAt,
        public array $events,
    ) {}
}
