<?php

declare(strict_types=1);

namespace App\Domains\Interoperability\Enums;

enum InteroperabilityClinicalConceptType: string
{
    case CONDITION = 'CONDITION';
    case ALLERGY_INTOLERANCE = 'ALLERGY_INTOLERANCE';
    case OBSERVATION = 'OBSERVATION';

    public function fhirResourceType(): string
    {
        return match ($this) {
            self::CONDITION => 'Condition',
            self::ALLERGY_INTOLERANCE => 'AllergyIntolerance',
            self::OBSERVATION => 'Observation',
        };
    }
}
