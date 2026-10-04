<?php

declare(strict_types=1);

namespace App\Application\Patient;

use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Patient\Data\PatientDuplicateCriteriaData;
use App\Domains\Patient\Data\PersistPatientData;
use App\Domains\Patient\Data\PersistPatientIdentifierData;
use App\Domains\Patient\Data\RegisterPatientData;
use App\Domains\Patient\Data\RegisterPatientResultData;
use App\Domains\Patient\Repositories\PatientRepo;
use DateTimeImmutable;
use DomainException;

final readonly class PatientRegistrationService
{
    public function __construct(
        private PatientRepo $patients,
        private OrganizationRepo $organizations,
        private PatientIdentityNormalizer $normalizer,
        private PatientDuplicateService $duplicates,
    ) {}

    public function register(RegisterPatientData $data): RegisterPatientResultData
    {
        $facility = $this->organizations->findFacility(
            $data->organizationId,
            $data->registrationFacilityId,
        );
        if ($facility === null) {
            throw new DomainException('Registration facility does not belong to the organization.');
        }

        $this->assertDateOfBirth($data->dateOfBirth);
        $normalizedGivenName = $this->normalizer->normalizeName($data->givenName);
        $normalizedFamilyName = $this->normalizer->normalizeName($data->familyName);

        $hasRequiredName = $normalizedGivenName !== '' && $normalizedFamilyName !== '';
        if (! $hasRequiredName) {
            throw new DomainException('Given name and family name are required.');
        }

        foreach ($data->addresses as $address) {
            $hasValidCountryCode = preg_match('/^[A-Za-z]{2}$/', trim($address->countryCode)) === 1;
            if (! $hasValidCountryCode) {
                throw new DomainException('Patient address country code must be ISO alpha-2 format.');
            }
        }

        $normalizedIdentifiers = [];
        $identifierKeys = [];
        $persistIdentifiers = [];
        foreach ($data->identifiers as $identifier) {
            $normalized = $this->normalizer->normalizeIdentifier($identifier->value);
            if ($normalized === '') {
                throw new DomainException('Patient identifiers may not be empty after normalization.');
            }

            $system = trim($identifier->system);
            if ($system === '') {
                throw new DomainException('Patient identifier system is required.');
            }

            $normalizedIdentifiers[] = $normalized;
            $identifierKeys[] = $identifier->type->value.'|'.mb_strtolower($system).'|'.$normalized;
            $persistIdentifiers[] = new PersistPatientIdentifierData(
                type: $identifier->type,
                system: $system,
                value: trim($identifier->value),
                normalizedValue: $normalized,
            );
        }

        $criteria = new PatientDuplicateCriteriaData(
            organizationId: $data->organizationId,
            normalizedGivenName: $normalizedGivenName,
            normalizedFamilyName: $normalizedFamilyName,
            dateOfBirth: $data->dateOfBirth,
            normalizedIdentifiers: $normalizedIdentifiers,
            identifierKeys: $identifierKeys,
        );

        $duplicateCandidates = $this->duplicates->candidates($criteria);

        $patient = $this->patients->create(new PersistPatientData(
            organizationId: $data->organizationId,
            registrationFacilityId: $data->registrationFacilityId,
            givenName: trim($data->givenName),
            normalizedGivenName: $normalizedGivenName,
            middleName: $data->middleName === null ? null : trim($data->middleName),
            familyName: trim($data->familyName),
            normalizedFamilyName: $normalizedFamilyName,
            preferredName: $data->preferredName === null ? null : trim($data->preferredName),
            dateOfBirth: $data->dateOfBirth,
            sexAtBirth: $data->sexAtBirth,
            identifiers: $persistIdentifiers,
            contacts: $data->contacts,
            addresses: $data->addresses,
            relationships: $data->relationships,
        ));

        return new RegisterPatientResultData(
            patient: $patient,
            duplicateCandidates: $duplicateCandidates,
        );
    }

    private function assertDateOfBirth(string $dateOfBirth): void
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateOfBirth);
        $isValid = $date !== false && $date->format('Y-m-d') === $dateOfBirth;
        if (! $isValid) {
            throw new DomainException('Date of birth must use YYYY-MM-DD.');
        }

        if ($date > new DateTimeImmutable('today')) {
            throw new DomainException('Date of birth may not be in the future.');
        }
    }
}
