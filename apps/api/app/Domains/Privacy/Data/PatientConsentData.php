<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Data;

final readonly class PatientConsentData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $patientId,
        public ?string $facilityId,
        public string $dataCategory,
        public string $purpose,
        public string $recipientClass,
        public string $status,
        public int $grantedByUserId,
        public string $effectiveFrom,
        public ?string $effectiveUntil,
        public ?string $revokedAt,
        public ?int $revokedByUserId,
        public ?string $revocationReason,
        public string $createdAt,
        public bool $isEffective,
    ) {}
}
