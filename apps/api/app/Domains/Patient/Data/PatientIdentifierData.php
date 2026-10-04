<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientIdentifierData
{
    public function __construct(
        public string $id,
        public string $type,
        public string $system,
        public string $value,
        public string $normalizedValue,
    ) {}
}
