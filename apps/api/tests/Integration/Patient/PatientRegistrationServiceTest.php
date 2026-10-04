<?php

declare(strict_types=1);

namespace Tests\Integration\Patient;

use App\Application\Patient\PatientRegistrationService;
use App\Domains\Patient\Data\PatientContactInputData;
use App\Domains\Patient\Data\PatientIdentifierInputData;
use App\Domains\Patient\Data\RegisterPatientData;
use App\Domains\Patient\Enums\DuplicateMatchReason;
use App\Domains\Patient\Enums\PatientContactType;
use App\Domains\Patient\Enums\PatientIdentifierType;
use App\Domains\Patient\Enums\SexAtBirth;
use App\Models\Facility;
use App\Models\Organization;
use Tests\IntegrationTestCase;

final class PatientRegistrationServiceTest extends IntegrationTestCase
{
    public function test_registration_persists_patient_and_returns_duplicate_candidates_without_auto_merge(): void
    {
        [$organization, $facility] = $this->organizationAndFacility();

        $service = app(PatientRegistrationService::class);
        $first = $service->register($this->registration(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            mrn: 'MRN-100',
        ));
        self::assertSame([], $first->duplicateCandidates);

        $second = $service->register($this->registration(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            mrn: 'MRN-101',
        ));

        self::assertNotSame($first->patient->id, $second->patient->id);
        self::assertCount(1, $second->duplicateCandidates);
        self::assertSame($first->patient->id, $second->duplicateCandidates[0]->patientId);
        self::assertSame(
            DuplicateMatchReason::NAME_AND_DOB->value,
            $second->duplicateCandidates[0]->reason,
        );

        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseHas('patient_identifiers', [
            'patient_id' => $second->patient->id,
            'normalized_value' => 'MRN101',
        ]);
    }

    /** @return array{Organization, Facility} */
    private function organizationAndFacility(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Patient Integration Org',
            'slug' => 'patient-integration-org',
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Integration Facility',
            'code' => 'INT',
        ]);

        return [$organization, $facility];
    }

    private function registration(
        string $organizationId,
        string $facilityId,
        string $mrn,
    ): RegisterPatientData {
        return new RegisterPatientData(
            organizationId: $organizationId,
            registrationFacilityId: $facilityId,
            givenName: 'Asha',
            middleName: null,
            familyName: 'Mehta',
            preferredName: null,
            dateOfBirth: '1988-04-18',
            sexAtBirth: SexAtBirth::FEMALE,
            identifiers: [
                new PatientIdentifierInputData(
                    type: PatientIdentifierType::MRN,
                    system: 'integration-mrn',
                    value: $mrn,
                ),
            ],
            contacts: [
                new PatientContactInputData(
                    type: PatientContactType::PHONE,
                    value: '+910000000001',
                    preferred: true,
                ),
            ],
            addresses: [],
            relationships: [],
        );
    }
}
