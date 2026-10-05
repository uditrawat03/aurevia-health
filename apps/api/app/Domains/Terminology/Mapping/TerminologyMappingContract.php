<?php

declare(strict_types=1);

namespace App\Domains\Terminology\Mapping;

use App\Domains\Terminology\Data\TerminologyConceptData;

interface TerminologyMappingContract
{
    public function map(
        TerminologyConceptData $concept,
        string $targetSystem,
    ): TerminologyConceptData;
}
