<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientRelationshipData
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public bool $legalGuardian,
        public bool $emergencyContact,
    ) {}
}
