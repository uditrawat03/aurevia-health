<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientDuplicateCriteriaData
{
    /**
     * @param list<string> $normalizedIdentifiers
     * @param list<string> $identifierKeys
     */
    public function __construct(
        public string $organizationId,
        public string $normalizedGivenName,
        public string $normalizedFamilyName,
        public string $dateOfBirth,
        public array $normalizedIdentifiers,
        public array $identifierKeys,
    ) {}
}
