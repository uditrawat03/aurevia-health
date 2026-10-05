<?php

declare(strict_types=1);

namespace App\Infrastructure\Terminology;

use App\Domains\Terminology\Data\TerminologyCodingData;
use App\Domains\Terminology\Data\TerminologyConceptData;
use App\Domains\Terminology\Mapping\TerminologyMappingContract;
use App\Exceptions\ExpectedBusinessRuleViolation;

final readonly class ExistingCodingTerminologyMapping implements TerminologyMappingContract
{
    public function map(
        TerminologyConceptData $concept,
        string $targetSystem,
    ): TerminologyConceptData
    {
        $matches = array_values(array_filter(
            $concept->codings,
            static fn (TerminologyCodingData $coding): bool => $coding->system === $targetSystem,
        ));

        if ($matches === []) {
            throw new ExpectedBusinessRuleViolation(
                'No terminology mapping is configured for the requested target system.',
            );
        }

        return new TerminologyConceptData(
            text: $concept->text,
            codings: $matches,
        );
    }
}
