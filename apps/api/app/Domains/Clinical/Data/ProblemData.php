<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class ProblemData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $encounterId,
        public ?string $codeSystem,
        public ?string $code,
        public string $display,
        public string $status,
        public ?string $onsetDate,
        public ?string $resolvedAt,
        public int $recordedByUserId,
        public string $recordedAt,
    ) {}
}
