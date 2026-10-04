<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

use App\Domains\Patient\Enums\PatientRelationshipType;

final readonly class PatientRelationshipInputData
{
    public function __construct(
        public PatientRelationshipType $type,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public bool $legalGuardian,
        public bool $emergencyContact,
    ) {}
}
