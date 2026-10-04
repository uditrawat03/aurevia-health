<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class AllergyData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $encounterId,
        public ?string $codeSystem,
        public ?string $code,
        public string $substance,
        public ?string $reaction,
        public string $severity,
        public string $status,
        public string $verificationStatus,
        public int $recordedByUserId,
        public string $recordedAt,
    ) {}
}
