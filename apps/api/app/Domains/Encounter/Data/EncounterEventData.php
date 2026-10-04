<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Data;

final readonly class EncounterEventData
{
    public function __construct(
        public string $id,
        public string $type,
        public ?string $fromStatus,
        public string $toStatus,
        public ?string $reason,
        public int $actorUserId,
        public string $occurredAt,
    ) {}
}
