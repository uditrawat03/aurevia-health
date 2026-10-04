<?php

declare(strict_types=1);

namespace Tests\Unit\Encounter;

use App\Domains\Encounter\Enums\EncounterStatus;
use App\Domains\Encounter\Workflow\EncounterTransitionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class EncounterTransitionPolicyTest extends TestCase
{
    #[DataProvider('transitions')]
    public function test_encounter_transitions_are_explicit(
        EncounterStatus $from,
        EncounterStatus $to,
        bool $expected,
    ): void {
        self::assertSame($expected, (new EncounterTransitionPolicy())->allows($from, $to));
    }

    /** @return iterable<string, array{EncounterStatus, EncounterStatus, bool}> */
    public static function transitions(): iterable
    {
        yield 'planned to arrived' => [EncounterStatus::PLANNED, EncounterStatus::ARRIVED, true];
        yield 'planned to cancelled' => [EncounterStatus::PLANNED, EncounterStatus::CANCELLED, true];
        yield 'planned cannot skip to complete' => [EncounterStatus::PLANNED, EncounterStatus::COMPLETED, false];
        yield 'arrived to in progress' => [EncounterStatus::ARRIVED, EncounterStatus::IN_PROGRESS, true];
        yield 'arrived to cancelled' => [EncounterStatus::ARRIVED, EncounterStatus::CANCELLED, true];
        yield 'in progress to complete' => [EncounterStatus::IN_PROGRESS, EncounterStatus::COMPLETED, true];
        yield 'completed is terminal' => [EncounterStatus::COMPLETED, EncounterStatus::ARRIVED, false];
        yield 'cancelled is terminal' => [EncounterStatus::CANCELLED, EncounterStatus::PLANNED, false];
    }
}
