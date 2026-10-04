<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientMergeReviewData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $sourcePatientId,
        public string $targetPatientId,
        public string $status,
        public int $requestedByUserId,
        public ?int $reviewedByUserId,
        public ?string $reason,
        public string $createdAt,
    ) {}
}
