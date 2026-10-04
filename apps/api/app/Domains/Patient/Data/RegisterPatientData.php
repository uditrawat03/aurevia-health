<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

use App\Domains\Patient\Enums\SexAtBirth;

final readonly class RegisterPatientData
{
    /**
     * @param list<PatientIdentifierInputData> $identifiers
     * @param list<PatientContactInputData> $contacts
     * @param list<PatientAddressInputData> $addresses
     * @param list<PatientRelationshipInputData> $relationships
     */
    public function __construct(
        public string $organizationId,
        public string $registrationFacilityId,
        public string $givenName,
        public ?string $middleName,
        public string $familyName,
        public ?string $preferredName,
        public string $dateOfBirth,
        public SexAtBirth $sexAtBirth,
        public array $identifiers,
        public array $contacts,
        public array $addresses,
        public array $relationships,
    ) {}
}
