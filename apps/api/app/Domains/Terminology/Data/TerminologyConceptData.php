<?php

declare(strict_types=1);

namespace App\Domains\Terminology\Data;

final readonly class TerminologyConceptData
{
    /** @param list<TerminologyCodingData> $codings */
    public function __construct(
        public string $text,
        public array $codings,
    ) {}
}
