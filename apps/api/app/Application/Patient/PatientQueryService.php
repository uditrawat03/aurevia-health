<?php

declare(strict_types=1);

namespace App\Application\Patient;

use App\Domains\Patient\Data\PatientData;
use App\Domains\Patient\Data\PatientSearchCriteriaData;
use App\Domains\Patient\Data\PatientSearchResultData;
use App\Domains\Patient\Repositories\PatientRepo;
use DomainException;

final readonly class PatientQueryService
{
    public const int DEFAULT_SEARCH_LIMIT = 20;
    public const int MAX_SEARCH_LIMIT = 50;

    public function __construct(
        private PatientRepo $patients,
        private PatientIdentityNormalizer $normalizer,
    ) {}

    public function patient(string $organizationId, string $patientId): PatientData
    {
        $patient = $this->patients->find($organizationId, $patientId);
        if ($patient === null) {
            throw new DomainException('Patient was not found.');
        }

        return $patient;
    }

    public function search(
        string $organizationId,
        ?string $facilityId,
        string $query,
        int $limit,
    ): PatientSearchResultData {
        $normalizedText = $this->normalizer->normalizeName($query);
        $normalizedIdentifier = $this->normalizer->normalizeIdentifier($query);
        $isInvalidLength = mb_strlen($normalizedText) < 2 && mb_strlen($normalizedIdentifier) < 2;
        if ($isInvalidLength) {
            throw new DomainException('Patient search requires at least two characters.');
        }

        $isInvalidLimit = $limit < 1 || $limit > self::MAX_SEARCH_LIMIT;
        if ($isInvalidLimit) {
            throw new DomainException('Patient search limit must be between 1 and 50.');
        }

        return $this->patients->search(new PatientSearchCriteriaData(
            organizationId: $organizationId,
            facilityId: $facilityId,
            normalizedText: $normalizedText,
            normalizedIdentifier: $normalizedIdentifier,
            limit: $limit,
        ));
    }
}
