<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Data;

final readonly class RevokePatientConsentData
{
    public function __construct(
        public string $organizationId,
        public string $patientId,
        public string $consentId,
        public int $revokedByUserId,
        public string $reason,
        public string $revokedAt,
    ) {}
}
