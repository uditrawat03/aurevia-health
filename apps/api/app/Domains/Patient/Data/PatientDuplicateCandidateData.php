<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientDuplicateCandidateData
{
    public function __construct(
        public string $patientId,
        public string $displayName,
        public string $dateOfBirth,
        public int $confidence,
        public string $reason,
    ) {}
}
