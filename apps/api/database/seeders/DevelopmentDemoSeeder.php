<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Patient\Enums\PatientContactType;
use App\Domains\Patient\Enums\PatientIdentifierType;
use App\Domains\Patient\Enums\SexAtBirth;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Patient;
use App\Models\PatientContact;
use App\Models\PatientIdentifier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DevelopmentDemoSeeder extends Seeder
{
    public const string OWNER_EMAIL = 'owner@aurevia.local';
    public const string OWNER_PASSWORD = 'AureviaLocal123!';

    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Aurevia Demo Owner',
                'email_verified_at' => now(),
                'password' => Hash::make(self::OWNER_PASSWORD),
            ],
        );

        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'aurevia-demo-health'],
            [
                'name' => 'Aurevia Demo Health',
                'country_code' => 'IN',
                'country_profile_code' => 'IN',
                'country_profile_version' => '1.0.0',
                'locale_override' => null,
                'timezone_override' => null,
                'week_starts_on_override' => null,
            ],
        );

        $facility = Facility::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'code' => 'DEMO-GH',
            ],
            [
                'health_system_id' => null,
                'name' => 'Demo General Hospital',
                'locale_override' => null,
                'timezone_override' => null,
                'week_starts_on_override' => null,
            ],
        );

        OrganizationMembership::query()->updateOrCreate(
            [
                'user_id' => $owner->getKey(),
                'organization_id' => $organization->getKey(),
            ],
            [
                'role' => OrganizationRole::OWNER->value,
                'status' => MembershipStatus::ACTIVE->value,
                'all_facilities' => true,
            ],
        );

        $this->seedPatient(
            organization: $organization,
            facility: $facility,
            givenName: 'Asha',
            familyName: 'Mehta',
            dateOfBirth: '1988-04-18',
            sexAtBirth: SexAtBirth::FEMALE,
            mrn: 'DEMO-0001',
            phone: '+910000000001',
        );
        $this->seedPatient(
            organization: $organization,
            facility: $facility,
            givenName: 'Rahil',
            familyName: 'Khan',
            dateOfBirth: '1979-11-02',
            sexAtBirth: SexAtBirth::MALE,
            mrn: 'DEMO-0002',
            phone: '+910000000002',
        );
    }

    private function seedPatient(
        Organization $organization,
        Facility $facility,
        string $givenName,
        string $familyName,
        string $dateOfBirth,
        SexAtBirth $sexAtBirth,
        string $mrn,
        string $phone,
    ): void {
        $patient = Patient::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'registration_facility_id' => $facility->getKey(),
                'normalized_given_name' => mb_strtolower($givenName),
                'normalized_family_name' => mb_strtolower($familyName),
                'date_of_birth' => $dateOfBirth,
            ],
            [
                'given_name' => $givenName,
                'middle_name' => null,
                'family_name' => $familyName,
                'preferred_name' => null,
                'sex_at_birth' => $sexAtBirth->value,
            ],
        );

        PatientIdentifier::query()->firstOrCreate(
            [
                'organization_id' => $organization->getKey(),
                'patient_id' => $patient->getKey(),
                'type' => PatientIdentifierType::MRN->value,
                'system' => 'aurevia-demo-mrn',
                'normalized_value' => str_replace('-', '', $mrn),
            ],
            ['value' => $mrn],
        );

        PatientContact::query()->firstOrCreate(
            [
                'patient_id' => $patient->getKey(),
                'type' => PatientContactType::PHONE->value,
                'value' => $phone,
            ],
            ['preferred' => true],
        );
    }
}
