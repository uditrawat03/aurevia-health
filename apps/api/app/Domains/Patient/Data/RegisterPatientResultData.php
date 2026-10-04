<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class RegisterPatientResultData
{
    /** @param list<PatientDuplicateCandidateData> $duplicateCandidates */
    public function __construct(
        public PatientData $patient,
        public array $duplicateCandidates,
    ) {}
}
