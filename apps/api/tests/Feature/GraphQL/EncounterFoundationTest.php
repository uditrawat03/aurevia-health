<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use App\Domains\Privacy\Enums\ConsentStatus;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class EncounterFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string CREATE_MUTATION = <<<'GRAPHQL'
        mutation CreateEncounter($input: CreateEncounterInput!) {
            createEncounter(input: $input) {
                id
                patientId
                appointmentId
                type
                status
                arrivedAt
                startedAt
                endedAt
                events {
                    type
                    fromStatus
                    toStatus
                    reason
                }
            }
        }
        GRAPHQL;

    private const string TRANSITION_MUTATION = <<<'GRAPHQL'
        mutation TransitionEncounter($input: TransitionEncounterInput!) {
            transitionEncounter(input: $input) {
                id
                status
                arrivedAt
                startedAt
                endedAt
                cancelledAt
                cancellationReason
                events {
                    type
                    fromStatus
                    toStatus
                    reason
                }
            }
        }
        GRAPHQL;

    private const string ENCOUNTERS_QUERY = <<<'GRAPHQL'
        query Encounters($input: PatientEncountersInput!) {
            encounters(input: $input) {
                id
                patientId
                patientDisplayName
                status
                events {
                    type
                }
            }
        }
        GRAPHQL;

    public function test_scheduled_appointment_becomes_controlled_encounter_lifecycle(): void
    {
        [$owner, $organization, $facility, $patient, $appointment] = $this->workspace();
        $this->actingAs($owner, 'web');

        $created = $this->createEncounter(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentId: (string) $appointment->getKey(),
            correlationId: 'encounter-create-linked',
        );
        $encounterId = $created->json('data.createEncounter.id');
        self::assertIsString($encounterId);
        $created
            ->assertOk()
            ->assertJsonPath('data.createEncounter.status', 'PLANNED')
            ->assertJsonPath('data.createEncounter.appointmentId', $appointment->getKey())
            ->assertJsonPath('data.createEncounter.events.0.type', 'CREATED');

        $arrived = $this->transition(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            encounterId: $encounterId,
            toStatus: 'ARRIVED',
            correlationId: 'encounter-arrived',
        );
        $arrived
            ->assertOk()
            ->assertJsonPath('data.transitionEncounter.status', 'ARRIVED');
        self::assertIsString($arrived->json('data.transitionEncounter.arrivedAt'));

        $started = $this->transition(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            encounterId: $encounterId,
            toStatus: 'IN_PROGRESS',
            correlationId: 'encounter-started',
        );
        $started
            ->assertOk()
            ->assertJsonPath('data.transitionEncounter.status', 'IN_PROGRESS');
        self::assertIsString($started->json('data.transitionEncounter.startedAt'));

        $completed = $this->transition(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            encounterId: $encounterId,
            toStatus: 'COMPLETED',
            correlationId: 'encounter-completed',
        );
        $completed
            ->assertOk()
            ->assertJsonPath('data.transitionEncounter.status', 'COMPLETED')
            ->assertJsonCount(4, 'data.transitionEncounter.events');
        self::assertIsString($completed->json('data.transitionEncounter.endedAt'));

        $list = $this->postGraphQL(
            self::ENCOUNTERS_QUERY,
            'encounter-list',
            ['input' => [
                'organizationId' => (string) $organization->getKey(),
                'facilityId' => (string) $facility->getKey(),
                'patientId' => (string) $patient->getKey(),
            ]],
        );
        $list
            ->assertOk()
            ->assertJsonPath('data.encounters.0.id', $encounterId)
            ->assertJsonPath('data.encounters.0.status', 'COMPLETED');

        $this->assertDatabaseHas('audit_events', [
            'patient_id' => $patient->getKey(),
            'resource_id' => $encounterId,
            'action' => AuditAction::COMPLETE_ENCOUNTER->value,
            'correlation_id' => 'encounter-completed',
        ]);
    }

    public function test_duplicate_appointment_and_invalid_status_jump_are_rejected(): void
    {
        [$owner, $organization, $facility, $patient, $appointment] = $this->workspace(
            slug: 'encounter-transition-org',
        );
        $this->actingAs($owner, 'web');

        $encounterId = $this->createEncounter(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentId: (string) $appointment->getKey(),
            correlationId: 'encounter-transition-create',
        )->json('data.createEncounter.id');
        self::assertIsString($encounterId);

        $duplicate = $this->createEncounter(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentId: (string) $appointment->getKey(),
            correlationId: 'encounter-duplicate-appointment',
        );
        $duplicate->assertOk()->assertJsonPath('data', null);

        $invalid = $this->transition(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            encounterId: $encounterId,
            toStatus: 'COMPLETED',
            correlationId: 'encounter-invalid-jump',
        );
        $invalid->assertOk()->assertJsonPath('data', null);

        $this->assertDatabaseHas('encounters', [
            'id' => $encounterId,
            'status' => 'PLANNED',
        ]);
        $this->assertDatabaseCount('encounters', 1);
    }

    public function test_clinical_privacy_consent_is_required_for_encounter_creation(): void
    {
        [$owner, $organization, $facility, $patient, $appointment] = $this->workspace(
            slug: 'encounter-privacy-org',
            consent: false,
        );
        $this->actingAs($owner, 'web');

        $denied = $this->createEncounter(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentId: (string) $appointment->getKey(),
            correlationId: 'encounter-no-clinical-consent',
        );
        $denied
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'encounter-no-clinical-consent');
        $this->assertDatabaseCount('encounters', 0);
    }

    private function createEncounter(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $appointmentId,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::CREATE_MUTATION,
            $correlationId,
            ['input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'appointmentId' => $appointmentId,
                'type' => 'OUTPATIENT',
            ]],
        );
    }

    private function transition(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $toStatus,
        string $correlationId,
        ?string $reason = null,
    ): TestResponse {
        return $this->postGraphQL(
            self::TRANSITION_MUTATION,
            $correlationId,
            ['input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'encounterId' => $encounterId,
                'toStatus' => $toStatus,
                'reason' => $reason,
            ]],
        );
    }

    /** @return array{User, Organization, Facility, Patient, Appointment} */
    private function workspace(string $slug = 'encounter-foundation-org', bool $consent = true): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Encounter Foundation Organization',
            'slug' => $slug,
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Encounter Facility',
            'code' => 'ENC-FAC',
        ]);
        OrganizationMembership::query()->create([
            'user_id' => $owner->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => OrganizationRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);
        $patient = Patient::query()->create([
            'organization_id' => $organization->getKey(),
            'registration_facility_id' => $facility->getKey(),
            'given_name' => 'Encounter',
            'normalized_given_name' => 'encounter',
            'middle_name' => null,
            'family_name' => 'Patient',
            'normalized_family_name' => 'patient',
            'preferred_name' => null,
            'date_of_birth' => '1992-06-15',
            'sex_at_birth' => 'UNKNOWN',
        ]);

        if ($consent) {
            PatientConsent::query()->create([
                'organization_id' => $organization->getKey(),
                'patient_id' => $patient->getKey(),
                'facility_id' => $facility->getKey(),
                'data_category' => ConsentDataCategory::CLINICAL->value,
                'purpose' => ConsentPurpose::TREATMENT->value,
                'recipient_class' => ConsentRecipientClass::CARE_TEAM->value,
                'status' => ConsentStatus::ACTIVE->value,
                'granted_by_user_id' => $owner->getKey(),
                'effective_from' => now()->subHour(),
                'effective_until' => null,
            ]);
        }

        $appointmentType = AppointmentType::query()->create([
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'code' => 'ENC-CONSULT',
            'name' => 'Encounter consultation',
            'duration_minutes' => 30,
            'active' => true,
        ]);
        $startsAt = CarbonImmutable::now('UTC')->addDay();
        $appointment = Appointment::query()->create([
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'patient_id' => $patient->getKey(),
            'appointment_type_id' => $appointmentType->getKey(),
            'status' => 'SCHEDULED',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(30),
            'timezone' => 'UTC',
            'reason' => 'Encounter foundation test',
            'created_by_user_id' => $owner->getKey(),
            'idempotency_key' => 'encounter-appointment-'.$slug,
            'request_fingerprint' => hash('sha256', $slug),
        ]);

        return [$owner, $organization, $facility, $patient, $appointment];
    }
}
