<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class RequestPatientMergeReviewData
{
    public function __construct(
        public string $organizationId,
        public string $sourcePatientId,
        public string $targetPatientId,
        public int $requestedByUserId,
        public ?string $reason,
    ) {}
}
