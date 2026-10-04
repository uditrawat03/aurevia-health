<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

use App\Domains\Patient\Enums\PatientContactType;

final readonly class PatientContactInputData
{
    public function __construct(
        public PatientContactType $type,
        public string $value,
        public bool $preferred,
    ) {}
}
