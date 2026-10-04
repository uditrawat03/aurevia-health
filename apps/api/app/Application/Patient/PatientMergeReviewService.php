<?php

declare(strict_types=1);

namespace App\Application\Patient;

use App\Domains\Patient\Data\PatientMergeReviewData;
use App\Domains\Patient\Data\RequestPatientMergeReviewData;
use App\Domains\Patient\Repositories\PatientRepo;
use DomainException;

final readonly class PatientMergeReviewService
{
    public function __construct(private PatientRepo $patients) {}

    public function request(RequestPatientMergeReviewData $data): PatientMergeReviewData
    {
        if ($data->sourcePatientId === $data->targetPatientId) {
            throw new DomainException('A patient cannot be reviewed for merge with itself.');
        }

        $source = $this->patients->find($data->organizationId, $data->sourcePatientId);
        $target = $this->patients->find($data->organizationId, $data->targetPatientId);
        if ($source === null || $target === null) {
            throw new DomainException('Both patients must belong to the organization.');
        }

        return $this->patients->createMergeReview($data);
    }
}
