<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientContactData
{
    public function __construct(
        public string $id,
        public string $type,
        public string $value,
        public bool $preferred,
    ) {}
}
