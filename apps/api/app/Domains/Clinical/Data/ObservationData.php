<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class ObservationData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $encounterId,
        public ?string $codeSystem,
        public string $code,
        public string $display,
        public ?float $valueNumeric,
        public ?string $valueText,
        public ?string $unit,
        public string $effectiveAt,
        public int $recordedByUserId,
        public string $recordedAt,
    ) {}
}
