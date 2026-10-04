<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientSearchResultData
{
    /** @param list<PatientData> $items */
    public function __construct(
        public array $items,
        public int $total,
    ) {}
}
