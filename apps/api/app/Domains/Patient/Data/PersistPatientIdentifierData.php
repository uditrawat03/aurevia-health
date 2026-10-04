<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

use App\Domains\Patient\Enums\PatientIdentifierType;

final readonly class PersistPatientIdentifierData
{
    public function __construct(
        public PatientIdentifierType $type,
        public string $system,
        public string $value,
        public string $normalizedValue,
    ) {}
}
