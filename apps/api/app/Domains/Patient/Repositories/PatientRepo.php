<?php

declare(strict_types=1);

namespace App\Domains\Patient\Repositories;

use App\Domains\Patient\Data\PatientData;
use App\Domains\Patient\Data\PatientDuplicateCriteriaData;
use App\Domains\Patient\Data\PatientMergeReviewData;
use App\Domains\Patient\Data\PatientSearchCriteriaData;
use App\Domains\Patient\Data\PatientSearchResultData;
use App\Domains\Patient\Data\PersistPatientData;
use App\Domains\Patient\Data\RequestPatientMergeReviewData;

interface PatientRepo
{
    public function create(PersistPatientData $data): PatientData;

    public function find(string $organizationId, string $patientId): ?PatientData;

    public function search(PatientSearchCriteriaData $criteria): PatientSearchResultData;

    /** @return list<PatientData> */
    public function possibleDuplicates(PatientDuplicateCriteriaData $criteria): array;

    public function createMergeReview(RequestPatientMergeReviewData $data): PatientMergeReviewData;
}
