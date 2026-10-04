<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientSearchCriteriaData
{
    public function __construct(
        public string $organizationId,
        public ?string $facilityId,
        public string $normalizedText,
        public string $normalizedIdentifier,
        public int $limit,
    ) {}
}
