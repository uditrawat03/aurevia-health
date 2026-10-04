<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientData
{
    /**
     * @param list<PatientIdentifierData> $identifiers
     * @param list<PatientContactData> $contacts
     * @param list<PatientAddressData> $addresses
     * @param list<PatientRelationshipData> $relationships
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $registrationFacilityId,
        public string $givenName,
        public string $normalizedGivenName,
        public ?string $middleName,
        public string $familyName,
        public string $normalizedFamilyName,
        public ?string $preferredName,
        public string $dateOfBirth,
        public string $sexAtBirth,
        public array $identifiers,
        public array $contacts,
        public array $addresses,
        public array $relationships,
    ) {}
}
