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
use App\Domains\Scheduling\Enums\SchedulingResourceType;
use App\Models\AppointmentType;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\SchedulingResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class SchedulingFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string BOOK_MUTATION = <<<'GRAPHQL'
        mutation BookAppointment($input: BookAppointmentInput!) {
            bookAppointment(input: $input) {
                replayed
                appointment {
                    id
                    patientId
                    patientDisplayName
                    status
                    startsAt
                    endsAt
                    timezone
                    appointmentType {
                        id
                        name
                        durationMinutes
                    }
                    resources {
                        id
                        name
                        type
                    }
                }
            }
        }
        GRAPHQL;

    private const string APPOINTMENTS_QUERY = <<<'GRAPHQL'
        query Appointments($input: AppointmentWindowInput!) {
            appointments(input: $input) {
                id
                patientId
                status
            }
        }
        GRAPHQL;

    private const string RESCHEDULE_MUTATION = <<<'GRAPHQL'
        mutation RescheduleAppointment($input: RescheduleAppointmentInput!) {
            rescheduleAppointment(input: $input) {
                id
                status
                startsAt
                endsAt
                timezone
                resources {
                    id
                }
            }
        }
        GRAPHQL;

    private const string CANCEL_MUTATION = <<<'GRAPHQL'
        mutation CancelAppointment($input: CancelAppointmentInput!) {
            cancelAppointment(input: $input) {
                id
                status
                cancelledAt
                cancellationReason
            }
        }
        GRAPHQL;

    private const string JOIN_WAITLIST_MUTATION = <<<'GRAPHQL'
        mutation JoinWaitlist($input: JoinWaitlistInput!) {
            joinWaitlist(input: $input) {
                replayed
                entry {
                    id
                    patientId
                    status
                    preferredFrom
                    preferredUntil
                }
            }
        }
        GRAPHQL;

    private const string CANCEL_WAITLIST_MUTATION = <<<'GRAPHQL'
        mutation CancelWaitlist($input: CancelWaitlistInput!) {
            cancelWaitlist(input: $input) {
                id
                status
                cancellationReason
            }
        }
        GRAPHQL;

    public function test_booking_is_idempotent_and_creates_one_audited_appointment(): void
    {
        [$owner, $organization, $facility, $patient, $type, $resource] = $this->workspace();
        $this->actingAs($owner, 'web');
        $start = CarbonImmutable::now('UTC')->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');

        $first = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $start,
            idempotencyKey: 'schedule-idempotency-0001',
            correlationId: 'schedule-book-first',
        );
        $appointmentId = $first->json('data.bookAppointment.appointment.id');
        self::assertIsString($appointmentId);
        $first
            ->assertOk()
            ->assertJsonPath('data.bookAppointment.replayed', false)
            ->assertJsonPath('data.bookAppointment.appointment.status', 'SCHEDULED')
            ->assertJsonPath('data.bookAppointment.appointment.appointmentType.durationMinutes', 30)
            ->assertJsonPath('data.bookAppointment.appointment.resources.0.id', $resource->getKey());

        $retry = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $start,
            idempotencyKey: 'schedule-idempotency-0001',
            correlationId: 'schedule-book-retry',
        );
        $retry
            ->assertOk()
            ->assertJsonPath('data.bookAppointment.replayed', true)
            ->assertJsonPath('data.bookAppointment.appointment.id', $appointmentId);

        $this->assertDatabaseCount('appointments', 1);
        self::assertSame(1, DB::table('audit_events')
            ->where('action', AuditAction::BOOK_APPOINTMENT->value)
            ->where('resource_id', $appointmentId)
            ->count());
    }

    public function test_resource_conflict_rejects_overlapping_booking(): void
    {
        [$owner, $organization, $facility, $patientA, $type, $resource] = $this->workspace(
            slug: 'schedule-conflict-org',
        );
        $patientB = $this->patient($organization, $facility, 'Second', 'Patient');
        $this->consent($owner, $organization, $facility, $patientB);
        $this->actingAs($owner, 'web');
        $start = CarbonImmutable::now('UTC')->addDay()->setTime(11, 0)->format('Y-m-d\TH:i');

        $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $start,
            idempotencyKey: 'schedule-conflict-first',
            correlationId: 'schedule-conflict-first',
        )->assertOk();

        $conflict = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientB->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $start,
            idempotencyKey: 'schedule-conflict-second',
            correlationId: 'schedule-conflict-second',
        );
        $conflict
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'schedule-conflict-second');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_requires_privacy_consent_and_schedule_query_is_facility_authorized(): void
    {
        [$owner, $organization, $facility, , $type, $resource] = $this->workspace(
            slug: 'schedule-privacy-org',
            consent: false,
        );
        $patient = $this->patient($organization, $facility, 'No', 'Consent');
        $this->actingAs($owner, 'web');
        $startAt = CarbonImmutable::now('UTC')->addDay()->setTime(12, 0);

        $denied = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $startAt->format('Y-m-d\TH:i'),
            idempotencyKey: 'schedule-privacy-denied',
            correlationId: 'schedule-privacy-denied',
        );
        $denied
            ->assertOk()
            ->assertJsonPath('data', null);
        $this->assertDatabaseCount('appointments', 0);

        $query = $this->postGraphQL(
            self::APPOINTMENTS_QUERY,
            'schedule-window-owner',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'facilityId' => (string) $facility->getKey(),
                    'from' => $startAt->subDay()->toIso8601String(),
                    'to' => $startAt->addDay()->toIso8601String(),
                ],
            ],
        );
        $query
            ->assertOk()
            ->assertJsonCount(0, 'data.appointments');
    }

    public function test_reschedule_rechecks_conflicts_and_records_lifecycle_evidence(): void
    {
        [$owner, $organization, $facility, $patientA, $type, $resource] = $this->workspace(
            slug: 'schedule-reschedule-org',
        );
        $patientB = $this->patient($organization, $facility, 'Conflict', 'Patient');
        $this->consent($owner, $organization, $facility, $patientB);
        $this->actingAs($owner, 'web');
        $firstStart = CarbonImmutable::now('UTC')->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $occupiedStart = CarbonImmutable::now('UTC')->addDay()->setTime(12, 0)->format('Y-m-d\TH:i');
        $newStart = CarbonImmutable::now('UTC')->addDay()->setTime(14, 0)->format('Y-m-d\TH:i');

        $appointmentId = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $firstStart,
            idempotencyKey: 'schedule-reschedule-first',
            correlationId: 'schedule-reschedule-first',
        )->json('data.bookAppointment.appointment.id');
        self::assertIsString($appointmentId);

        $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientB->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $occupiedStart,
            idempotencyKey: 'schedule-reschedule-conflict',
            correlationId: 'schedule-reschedule-conflict',
        )->assertOk();

        $conflict = $this->reschedule(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentId: $appointmentId,
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $occupiedStart,
            correlationId: 'schedule-reschedule-denied',
        );
        $conflict
            ->assertOk()
            ->assertJsonPath('data', null);

        $updated = $this->reschedule(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentId: $appointmentId,
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $newStart,
            correlationId: 'schedule-reschedule-success',
        );
        $updated
            ->assertOk()
            ->assertJsonPath('data.rescheduleAppointment.id', $appointmentId)
            ->assertJsonPath('data.rescheduleAppointment.status', 'SCHEDULED');

        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointmentId,
            'type' => 'RESCHEDULED',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_id' => $appointmentId,
            'action' => AuditAction::RESCHEDULE_APPOINTMENT->value,
            'correlation_id' => 'schedule-reschedule-success',
        ]);
    }

    public function test_cancellation_requires_reason_releases_slot_and_preserves_history(): void
    {
        [$owner, $organization, $facility, $patientA, $type, $resource] = $this->workspace(
            slug: 'schedule-cancel-org',
        );
        $patientB = $this->patient($organization, $facility, 'Replacement', 'Patient');
        $this->consent($owner, $organization, $facility, $patientB);
        $this->actingAs($owner, 'web');
        $start = CarbonImmutable::now('UTC')->addDay()->setTime(15, 0)->format('Y-m-d\TH:i');

        $appointmentId = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $start,
            idempotencyKey: 'schedule-cancel-first',
            correlationId: 'schedule-cancel-first',
        )->json('data.bookAppointment.appointment.id');
        self::assertIsString($appointmentId);

        $missingReason = $this->cancelAppointment(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentId: $appointmentId,
            reason: ' ',
            correlationId: 'schedule-cancel-no-reason',
        );
        $missingReason->assertOk()->assertJsonPath('data', null);

        $cancelled = $this->cancelAppointment(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientA->getKey(),
            appointmentId: $appointmentId,
            reason: 'Patient requested cancellation',
            correlationId: 'schedule-cancel-success',
        );
        $cancelled
            ->assertOk()
            ->assertJsonPath('data.cancelAppointment.status', 'CANCELLED')
            ->assertJsonPath('data.cancelAppointment.cancellationReason', 'Patient requested cancellation');

        $replacement = $this->book(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patientB->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            resourceId: (string) $resource->getKey(),
            startsAtLocal: $start,
            idempotencyKey: 'schedule-cancel-replacement',
            correlationId: 'schedule-cancel-replacement',
        );
        $replacement->assertOk()->assertJsonPath('data.bookAppointment.replayed', false);

        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointmentId,
            'type' => 'CANCELLED',
            'reason' => 'Patient requested cancellation',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_id' => $appointmentId,
            'action' => AuditAction::CANCEL_APPOINTMENT->value,
            'correlation_id' => 'schedule-cancel-success',
        ]);
    }

    public function test_waitlist_join_is_idempotent_and_can_be_cancelled(): void
    {
        [$owner, $organization, $facility, $patient, $type] = $this->workspace(
            slug: 'schedule-waitlist-org',
        );
        $this->actingAs($owner, 'web');
        $from = CarbonImmutable::now('UTC')->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i');
        $until = CarbonImmutable::now('UTC')->addDays(5)->setTime(17, 0)->format('Y-m-d\TH:i');

        $first = $this->joinWaitlist(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            from: $from,
            until: $until,
            idempotencyKey: 'waitlist-idempotency-0001',
            correlationId: 'schedule-waitlist-first',
        );
        $entryId = $first->json('data.joinWaitlist.entry.id');
        self::assertIsString($entryId);
        $first
            ->assertOk()
            ->assertJsonPath('data.joinWaitlist.replayed', false)
            ->assertJsonPath('data.joinWaitlist.entry.status', 'WAITING');

        $retry = $this->joinWaitlist(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            patientId: (string) $patient->getKey(),
            appointmentTypeId: (string) $type->getKey(),
            from: $from,
            until: $until,
            idempotencyKey: 'waitlist-idempotency-0001',
            correlationId: 'schedule-waitlist-retry',
        );
        $retry
            ->assertOk()
            ->assertJsonPath('data.joinWaitlist.replayed', true)
            ->assertJsonPath('data.joinWaitlist.entry.id', $entryId);

        $cancelled = $this->postGraphQL(
            self::CANCEL_WAITLIST_MUTATION,
            'schedule-waitlist-cancel',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'facilityId' => (string) $facility->getKey(),
                    'patientId' => (string) $patient->getKey(),
                    'waitlistEntryId' => $entryId,
                    'reason' => 'Patient no longer needs waitlist',
                ],
            ],
        );
        $cancelled
            ->assertOk()
            ->assertJsonPath('data.cancelWaitlist.status', 'CANCELLED')
            ->assertJsonPath('data.cancelWaitlist.cancellationReason', 'Patient no longer needs waitlist');

        $this->assertDatabaseCount('waitlist_entries', 1);
        self::assertSame(1, DB::table('audit_events')
            ->where('action', AuditAction::JOIN_WAITLIST->value)
            ->where('resource_id', $entryId)
            ->count());
        $this->assertDatabaseHas('audit_events', [
            'resource_id' => $entryId,
            'action' => AuditAction::CANCEL_WAITLIST->value,
            'correlation_id' => 'schedule-waitlist-cancel',
        ]);
    }

    private function book(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $appointmentTypeId,
        string $resourceId,
        string $startsAtLocal,
        string $idempotencyKey,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::BOOK_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityId,
                    'patientId' => $patientId,
                    'appointmentTypeId' => $appointmentTypeId,
                    'resourceIds' => [$resourceId],
                    'startsAtLocal' => $startsAtLocal,
                    'timezone' => 'UTC',
                    'reason' => 'Synthetic scheduling test',
                    'idempotencyKey' => $idempotencyKey,
                ],
            ],
        );
    }

    private function reschedule(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $appointmentId,
        string $resourceId,
        string $startsAtLocal,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::RESCHEDULE_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityId,
                    'patientId' => $patientId,
                    'appointmentId' => $appointmentId,
                    'resourceIds' => [$resourceId],
                    'startsAtLocal' => $startsAtLocal,
                    'timezone' => 'UTC',
                ],
            ],
        );
    }

    private function cancelAppointment(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $appointmentId,
        string $reason,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::CANCEL_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityId,
                    'patientId' => $patientId,
                    'appointmentId' => $appointmentId,
                    'reason' => $reason,
                ],
            ],
        );
    }

    private function joinWaitlist(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $appointmentTypeId,
        string $from,
        string $until,
        string $idempotencyKey,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::JOIN_WAITLIST_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityId,
                    'patientId' => $patientId,
                    'appointmentTypeId' => $appointmentTypeId,
                    'preferredFromLocal' => $from,
                    'preferredUntilLocal' => $until,
                    'timezone' => 'UTC',
                    'reason' => 'Flexible scheduling preference',
                    'idempotencyKey' => $idempotencyKey,
                ],
            ],
        );
    }

    /** @return array{User, Organization, Facility, Patient, AppointmentType, SchedulingResource} */
    private function workspace(string $slug = 'scheduling-foundation-org', bool $consent = true): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Scheduling Foundation Organization',
            'slug' => $slug,
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Scheduling Facility',
            'code' => 'SCHED-FAC',
        ]);
        OrganizationMembership::query()->create([
            'user_id' => $owner->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => OrganizationRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);
        $patient = $this->patient($organization, $facility, 'Schedule', 'Patient');
        if ($consent) {
            $this->consent($owner, $organization, $facility, $patient);
        }
        $type = AppointmentType::query()->create([
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'code' => 'GENERAL',
            'name' => 'General consultation',
            'duration_minutes' => 30,
            'active' => true,
        ]);
        $resource = SchedulingResource::query()->create([
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'type' => SchedulingResourceType::PROVIDER->value,
            'name' => 'Dr. Synthetic',
            'code' => 'PROVIDER-1',
            'active' => true,
        ]);

        return [$owner, $organization, $facility, $patient, $type, $resource];
    }

    private function patient(
        Organization $organization,
        Facility $facility,
        string $givenName,
        string $familyName,
    ): Patient {
        return Patient::query()->create([
            'organization_id' => $organization->getKey(),
            'registration_facility_id' => $facility->getKey(),
            'given_name' => $givenName,
            'normalized_given_name' => mb_strtolower($givenName),
            'middle_name' => null,
            'family_name' => $familyName,
            'normalized_family_name' => mb_strtolower($familyName),
            'preferred_name' => null,
            'date_of_birth' => '1990-01-01',
            'sex_at_birth' => 'UNKNOWN',
        ]);
    }

    private function consent(
        User $owner,
        Organization $organization,
        Facility $facility,
        Patient $patient,
    ): void {
        PatientConsent::query()->create([
            'organization_id' => $organization->getKey(),
            'patient_id' => $patient->getKey(),
            'facility_id' => $facility->getKey(),
            'data_category' => ConsentDataCategory::DEMOGRAPHICS->value,
            'purpose' => ConsentPurpose::TREATMENT->value,
            'recipient_class' => ConsentRecipientClass::CARE_TEAM->value,
            'status' => ConsentStatus::ACTIVE->value,
            'granted_by_user_id' => $owner->getKey(),
            'effective_from' => now()->subHour(),
            'effective_until' => null,
        ]);
    }
}
