<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Workflow;

use App\Domains\Encounter\Enums\EncounterStatus;

final readonly class EncounterTransitionPolicy
{
    public function allows(EncounterStatus $from, EncounterStatus $to): bool
    {
        return match ($from) {
            EncounterStatus::PLANNED => in_array($to, [
                EncounterStatus::ARRIVED,
                EncounterStatus::CANCELLED,
            ], true),
            EncounterStatus::ARRIVED => in_array($to, [
                EncounterStatus::IN_PROGRESS,
                EncounterStatus::CANCELLED,
            ], true),
            EncounterStatus::IN_PROGRESS => $to === EncounterStatus::COMPLETED,
            EncounterStatus::COMPLETED,
            EncounterStatus::CANCELLED => false,
        };
    }
}
