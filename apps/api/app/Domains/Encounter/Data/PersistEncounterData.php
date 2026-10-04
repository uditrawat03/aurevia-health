<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Data;

use App\Domains\Encounter\Enums\EncounterType;

final readonly class PersistEncounterData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public ?string $departmentId,
        public ?string $appointmentId,
        public EncounterType $type,
        public int $createdByUserId,
        public string $occurredAt,
    ) {}
}
