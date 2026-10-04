<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Data;

use App\Domains\Encounter\Enums\EncounterStatus;

final readonly class TransitionEncounterData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $encounterId,
        public EncounterStatus $fromStatus,
        public EncounterStatus $toStatus,
        public int $actorUserId,
        public string $occurredAt,
        public ?string $reason,
    ) {}
}
