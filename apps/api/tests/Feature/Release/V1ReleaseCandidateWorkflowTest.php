<?php

declare(strict_types=1);

namespace Tests\Feature\Release;

use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Scheduling\Enums\SchedulingResourceType;
use App\Models\AppointmentType;
use App\Models\SchedulingResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class V1ReleaseCandidateWorkflowTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string CREATE_ORGANIZATION = <<<'GRAPHQL'
        mutation CreateOrganization($input: CreateOrganizationInput!) {
            createOrganization(input: $input) { id }
        }
        GRAPHQL;

    private const string CREATE_FACILITY = <<<'GRAPHQL'
        mutation CreateFacility($input: CreateFacilityInput!) {
            createFacility(input: $input) { id organizationId }
        }
        GRAPHQL;

    private const string ASSIGN_MEMBERSHIP = <<<'GRAPHQL'
        mutation AssignOrganizationMembership($input: AssignOrganizationMembershipInput!) {
            assignOrganizationMembership(input: $input) { userId role status facilityIds }
        }
        GRAPHQL;

    private const string REGISTER_PATIENT = <<<'GRAPHQL'
        mutation RegisterPatient($input: RegisterPatientInput!) {
            registerPatient(input: $input) { patient { id givenName familyName } }
        }
        GRAPHQL;

    private const string GRANT_CONSENT = <<<'GRAPHQL'
        mutation GrantPatientConsent($input: GrantPatientConsentInput!) {
            grantPatientConsent(input: $input) { id dataCategory status }
        }
        GRAPHQL;

    private const string BOOK_APPOINTMENT = <<<'GRAPHQL'
        mutation BookAppointment($input: BookAppointmentInput!) {
            bookAppointment(input: $input) { replayed appointment { id status patientId } }
        }
        GRAPHQL;

    private const string CREATE_ENCOUNTER = <<<'GRAPHQL'
        mutation CreateEncounter($input: CreateEncounterInput!) {
            createEncounter(input: $input) { id status appointmentId patientId }
        }
        GRAPHQL;

    private const string TRANSITION_ENCOUNTER = <<<'GRAPHQL'
        mutation TransitionEncounter($input: TransitionEncounterInput!) {
            transitionEncounter(input: $input) { id status }
        }
        GRAPHQL;

    private const string RECORD_PROBLEM = <<<'GRAPHQL'
        mutation RecordProblem($input: RecordProblemInput!) {
            recordProblem(input: $input) { id display status encounterId }
        }
        GRAPHQL;

    private const string RECORD_ALLERGY = <<<'GRAPHQL'
        mutation RecordAllergy($input: RecordAllergyInput!) {
            recordAllergy(input: $input) { id substance severity encounterId }
        }
        GRAPHQL;

    private const string RECORD_OBSERVATION = <<<'GRAPHQL'
        mutation RecordObservation($input: RecordObservationInput!) {
            recordObservation(input: $input) { id code valueNumeric unit encounterId }
        }
        GRAPHQL;

    private const string CREATE_NOTE = <<<'GRAPHQL'
        mutation CreateClinicalNote($input: CreateClinicalNoteInput!) {
            createClinicalNote(input: $input) { id status body }
        }
        GRAPHQL;

    private const string SIGN_NOTE = <<<'GRAPHQL'
        mutation SignClinicalNote($input: SignClinicalNoteInput!) {
            signClinicalNote(input: $input) { id status signedAt body }
        }
        GRAPHQL;

    private const string CLINICAL_RECORD = <<<'GRAPHQL'
        query ClinicalRecord($input: PatientClinicalRecordInput!) {
            clinicalRecord(input: $input) {
                patientId
                problems { id }
                allergies { id }
                observations { id }
                notes { id status }
                timeline { type resourceId encounterId }
            }
        }
        GRAPHQL;

    private const string AUDIT_EVENTS = <<<'GRAPHQL'
        query AuditEvents($input: AuditEventsInput!) {
            auditEvents(input: $input) {
                action
                outcome
                patientId
                resourceId
                correlationId
            }
        }
        GRAPHQL;

    public function test_complete_v1_synthetic_workflow_reaches_signed_completed_and_audited_state(): void
    {
        $owner = User::factory()->create();
        $scheduler = User::factory()->create();
        $clinician = User::factory()->create();
        $this->actingAs($owner, 'web');

        $organizationId = $this->postGraphQL(self::CREATE_ORGANIZATION, 'rc-create-organization', [
            'input' => [
                'name' => 'Aurevia RC Synthetic Health',
                'slug' => 'aurevia-rc-synthetic-health',
                'countryCode' => 'IN',
            ],
        ])->assertOk()->json('data.createOrganization.id');
        self::assertIsString($organizationId);

        $facilityId = $this->postGraphQL(self::CREATE_FACILITY, 'rc-create-facility', [
            'input' => [
                'organizationId' => $organizationId,
                'name' => 'Aurevia RC Synthetic Hospital',
                'code' => 'RC-SYNTH',
                'settings' => ['timezone' => 'Asia/Kolkata'],
            ],
        ])->assertOk()->json('data.createFacility.id');
        self::assertIsString($facilityId);

        $this->assignMembership($organizationId, $facilityId, $scheduler, OrganizationRole::STAFF, 'rc-assign-scheduler');
        $this->assignMembership($organizationId, $facilityId, $clinician, OrganizationRole::CLINICIAN, 'rc-assign-clinician');

        $appointmentType = AppointmentType::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'code' => 'RC-GENERAL',
            'name' => 'RC general consultation',
            'duration_minutes' => 30,
            'active' => true,
        ]);
        $resource = SchedulingResource::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'type' => SchedulingResourceType::PROVIDER->value,
            'name' => 'Dr. Release Candidate',
            'code' => 'RC-PROVIDER',
            'active' => true,
        ]);

        $this->actingAs($scheduler, 'web');
        $patientId = $this->postGraphQL(self::REGISTER_PATIENT, 'rc-register-patient', [
            'input' => [
                'organizationId' => $organizationId,
                'registrationFacilityId' => $facilityId,
                'givenName' => 'Release',
                'familyName' => 'Candidate',
                'dateOfBirth' => '1992-04-18',
                'sexAtBirth' => 'UNKNOWN',
                'identifiers' => [[
                    'type' => 'MRN',
                    'system' => 'urn:aurevia:synthetic:mrn',
                    'value' => 'RC-0001',
                ]],
            ],
        ])->assertOk()->json('data.registerPatient.patient.id');
        self::assertIsString($patientId);

        foreach (['DEMOGRAPHICS', 'CLINICAL'] as $category) {
            $this->postGraphQL(self::GRANT_CONSENT, 'rc-consent-'.strtolower($category), [
                'input' => [
                    'organizationId' => $organizationId,
                    'patientId' => $patientId,
                    'facilityId' => $facilityId,
                    'dataCategory' => $category,
                    'purpose' => 'TREATMENT',
                    'recipientClass' => 'CARE_TEAM',
                ],
            ])->assertOk()->assertJsonPath('data.grantPatientConsent.status', 'ACTIVE');
        }

        $startsAt = CarbonImmutable::now('Asia/Kolkata')->addDay()->setTime(10, 30)->format('Y-m-d\\TH:i');
        $appointmentId = $this->postGraphQL(self::BOOK_APPOINTMENT, 'rc-book-appointment', [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'appointmentTypeId' => (string) $appointmentType->getKey(),
                'resourceIds' => [(string) $resource->getKey()],
                'startsAtLocal' => $startsAt,
                'timezone' => 'Asia/Kolkata',
                'reason' => 'Synthetic RC consultation',
                'idempotencyKey' => 'rc-booking-0001',
            ],
        ])->assertOk()->assertJsonPath('data.bookAppointment.replayed', false)->json('data.bookAppointment.appointment.id');
        self::assertIsString($appointmentId);

        $this->actingAs($clinician, 'web');
        $encounterId = $this->postGraphQL(self::CREATE_ENCOUNTER, 'rc-create-encounter', [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'appointmentId' => $appointmentId,
                'type' => 'OUTPATIENT',
            ],
        ])->assertOk()->assertJsonPath('data.createEncounter.status', 'PLANNED')->json('data.createEncounter.id');
        self::assertIsString($encounterId);

        $this->transition($organizationId, $facilityId, $patientId, $encounterId, 'ARRIVED', 'rc-arrive-encounter');
        $this->transition($organizationId, $facilityId, $patientId, $encounterId, 'IN_PROGRESS', 'rc-start-encounter');

        $clinicalContext = [
            'organizationId' => $organizationId,
            'facilityId' => $facilityId,
            'patientId' => $patientId,
            'encounterId' => $encounterId,
        ];
        $this->postGraphQL(self::RECORD_PROBLEM, 'rc-record-problem', [
            'input' => $clinicalContext + [
                'codeSystem' => 'urn:aurevia:synthetic:conditions',
                'code' => 'RC-PROBLEM',
                'display' => 'Synthetic release-candidate problem',
                'status' => 'ACTIVE',
            ],
        ])->assertOk()->assertJsonPath('data.recordProblem.encounterId', $encounterId);

        $this->postGraphQL(self::RECORD_ALLERGY, 'rc-record-allergy', [
            'input' => $clinicalContext + [
                'codeSystem' => 'urn:aurevia:synthetic:allergies',
                'code' => 'RC-ALLERGY',
                'substance' => 'Synthetic allergen',
                'reaction' => 'Synthetic rash',
                'severity' => 'MILD',
                'status' => 'ACTIVE',
                'verificationStatus' => 'CONFIRMED',
            ],
        ])->assertOk()->assertJsonPath('data.recordAllergy.encounterId', $encounterId);

        $this->postGraphQL(self::RECORD_OBSERVATION, 'rc-record-observation', [
            'input' => $clinicalContext + [
                'codeSystem' => 'http://loinc.org',
                'code' => '8867-4',
                'display' => 'Heart rate',
                'valueNumeric' => 72,
                'unit' => 'beats/min',
            ],
        ])->assertOk()->assertJsonPath('data.recordObservation.valueNumeric', 72);

        $noteId = $this->postGraphQL(self::CREATE_NOTE, 'rc-create-note', [
            'input' => $clinicalContext + [
                'noteType' => 'PROGRESS',
                'title' => 'Synthetic RC progress note',
                'body' => 'Synthetic release-candidate clinical documentation.',
            ],
        ])->assertOk()->assertJsonPath('data.createClinicalNote.status', 'DRAFT')->json('data.createClinicalNote.id');
        self::assertIsString($noteId);

        $this->postGraphQL(self::SIGN_NOTE, 'rc-sign-note', [
            'input' => $clinicalContext + ['noteId' => $noteId],
        ])->assertOk()->assertJsonPath('data.signClinicalNote.status', 'SIGNED');

        $this->transition($organizationId, $facilityId, $patientId, $encounterId, 'COMPLETED', 'rc-complete-encounter')
            ->assertJsonPath('data.transitionEncounter.status', 'COMPLETED');

        $record = $this->postGraphQL(self::CLINICAL_RECORD, 'rc-review-timeline', [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
            ],
        ]);
        $record
            ->assertOk()
            ->assertJsonPath('data.clinicalRecord.patientId', $patientId)
            ->assertJsonPath('data.clinicalRecord.notes.0.status', 'SIGNED')
            ->assertJsonCount(1, 'data.clinicalRecord.problems')
            ->assertJsonCount(1, 'data.clinicalRecord.allergies')
            ->assertJsonCount(1, 'data.clinicalRecord.observations');
        $record->assertJsonFragment(['type' => 'ENCOUNTER_COMPLETED']);
        $record->assertJsonFragment(['type' => 'CLINICAL_NOTE_SIGNED']);

        $this->actingAs($owner, 'web');
        $audit = $this->postGraphQL(self::AUDIT_EVENTS, 'rc-review-audit', [
            'input' => ['organizationId' => $organizationId, 'limit' => 100],
        ]);
        $audit->assertOk();
        $audit->assertJsonFragment(['action' => 'REGISTER_PATIENT', 'outcome' => 'ALLOWED']);
        $audit->assertJsonFragment(['action' => 'BOOK_APPOINTMENT', 'outcome' => 'ALLOWED']);
        $audit->assertJsonFragment(['action' => 'SIGN_CLINICAL_NOTE', 'outcome' => 'ALLOWED']);
        $audit->assertJsonFragment(['action' => 'COMPLETE_ENCOUNTER', 'outcome' => 'ALLOWED']);

        $this->assertDatabaseHas('clinical_notes', ['id' => $noteId, 'status' => 'SIGNED']);
        $this->assertDatabaseHas('encounters', ['id' => $encounterId, 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('audit_events', ['correlation_id' => 'rc-sign-note', 'patient_id' => $patientId]);
    }

    private function assignMembership(
        string $organizationId,
        string $facilityId,
        User $user,
        OrganizationRole $role,
        string $correlationId,
    ): void {
        $this->postGraphQL(self::ASSIGN_MEMBERSHIP, $correlationId, [
            'input' => [
                'organizationId' => $organizationId,
                'userId' => (string) $user->getKey(),
                'role' => $role->value,
                'allFacilities' => false,
                'facilityIds' => [$facilityId],
            ],
        ])->assertOk()->assertJsonPath('data.assignOrganizationMembership.role', $role->value);
    }

    private function transition(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $status,
        string $correlationId,
    ): \Illuminate\Testing\TestResponse {
        return $this->postGraphQL(self::TRANSITION_ENCOUNTER, $correlationId, [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'encounterId' => $encounterId,
                'toStatus' => $status,
            ],
        ])->assertOk();
    }
}
