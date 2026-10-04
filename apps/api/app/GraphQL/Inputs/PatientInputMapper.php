<?php

declare(strict_types=1);

namespace App\GraphQL\Inputs;

use App\Domains\Patient\Data\PatientAddressInputData;
use App\Domains\Patient\Data\PatientContactInputData;
use App\Domains\Patient\Data\PatientIdentifierInputData;
use App\Domains\Patient\Data\PatientRelationshipInputData;
use App\Domains\Patient\Data\RegisterPatientData;
use App\Domains\Patient\Enums\PatientAddressUse;
use App\Domains\Patient\Enums\PatientContactType;
use App\Domains\Patient\Enums\PatientIdentifierType;
use App\Domains\Patient\Enums\PatientRelationshipType;
use App\Domains\Patient\Enums\SexAtBirth;

final readonly class PatientInputMapper
{
    /**
     * Lighthouse supplies nested GraphQL input as associative arrays at this boundary.
     *
     * @param array{
     *   organizationId: string,
     *   registrationFacilityId: string,
     *   givenName: string,
     *   middleName?: string|null,
     *   familyName: string,
     *   preferredName?: string|null,
     *   dateOfBirth: string,
     *   sexAtBirth: string,
     *   identifiers: list<array{type: string, system: string, value: string}>,
     *   contacts?: list<array{type: string, value: string, preferred?: bool}>,
     *   addresses?: list<array{use: string, line1: string, line2?: string|null, city: string, region?: string|null, postalCode?: string|null, countryCode: string, preferred?: bool}>,
     *   relationships?: list<array{type: string, name: string, phone?: string|null, email?: string|null, legalGuardian?: bool, emergencyContact?: bool}>
     * } $input
     */
    public function mapRegister(array $input): RegisterPatientData
    {
        $identifiers = [];
        foreach ($input['identifiers'] as $identifier) {
            $identifiers[] = new PatientIdentifierInputData(
                type: PatientIdentifierType::from($identifier['type']),
                system: $identifier['system'],
                value: $identifier['value'],
            );
        }

        $contacts = [];
        foreach ($input['contacts'] ?? [] as $contact) {
            $contacts[] = new PatientContactInputData(
                type: PatientContactType::from($contact['type']),
                value: $contact['value'],
                preferred: $contact['preferred'] ?? false,
            );
        }

        $addresses = [];
        foreach ($input['addresses'] ?? [] as $address) {
            $addresses[] = new PatientAddressInputData(
                use: PatientAddressUse::from($address['use']),
                line1: $address['line1'],
                line2: $address['line2'] ?? null,
                city: $address['city'],
                region: $address['region'] ?? null,
                postalCode: $address['postalCode'] ?? null,
                countryCode: $address['countryCode'],
                preferred: $address['preferred'] ?? false,
            );
        }

        $relationships = [];
        foreach ($input['relationships'] ?? [] as $relationship) {
            $relationships[] = new PatientRelationshipInputData(
                type: PatientRelationshipType::from($relationship['type']),
                name: $relationship['name'],
                phone: $relationship['phone'] ?? null,
                email: $relationship['email'] ?? null,
                legalGuardian: $relationship['legalGuardian'] ?? false,
                emergencyContact: $relationship['emergencyContact'] ?? false,
            );
        }

        return new RegisterPatientData(
            organizationId: $input['organizationId'],
            registrationFacilityId: $input['registrationFacilityId'],
            givenName: $input['givenName'],
            middleName: $input['middleName'] ?? null,
            familyName: $input['familyName'],
            preferredName: $input['preferredName'] ?? null,
            dateOfBirth: $input['dateOfBirth'],
            sexAtBirth: SexAtBirth::from($input['sexAtBirth']),
            identifiers: $identifiers,
            contacts: $contacts,
            addresses: $addresses,
            relationships: $relationships,
        );
    }
}
