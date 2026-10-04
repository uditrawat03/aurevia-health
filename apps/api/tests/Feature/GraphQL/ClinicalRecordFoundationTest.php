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
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\User;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class ClinicalRecordFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string RECORD_PROBLEM = <<<'GRAPHQL'
        mutation RecordProblem($input: RecordProblemInput!) {
            recordProblem(input: $input) {
                id
                patientId
                encounterId
                display
                status
            }
        }
        GRAPHQL;

    private const string RECORD_ALLERGY = <<<'GRAPHQL'
        mutation RecordAllergy($input: RecordAllergyInput!) {
            recordAllergy(input: $input) {
                id
                substance
                reaction
                severity
                verificationStatus
            }
        }
        GRAPHQL;

    private const string RECORD_OBSERVATION = <<<'GRAPHQL'
        mutation RecordObservation($input: RecordObservationInput!) {
            recordObservation(input: $input) {
                id
                code
                display
                valueNumeric
                unit
                encounterId
            }
        }
        GRAPHQL;

    private const string CREATE_NOTE = <<<'GRAPHQL'
        mutation CreateClinicalNote($input: CreateClinicalNoteInput!) {
            createClinicalNote(input: $input) {
                id
                title
                body
                status
                signedAt
            }
        }
        GRAPHQL;

    private const string UPDATE_NOTE = <<<'GRAPHQL'
        mutation UpdateClinicalNoteDraft($input: UpdateClinicalNoteDraftInput!) {
            updateClinicalNoteDraft(input: $input) {
                id
                title
                body
                status
            }
        }
        GRAPHQL;

    private const string SIGN_NOTE = <<<'GRAPHQL'
        mutation SignClinicalNote($input: SignClinicalNoteInput!) {
            signClinicalNote(input: $input) {
                id
                body
                status
                signedByUserId
                signedAt
            }
        }
        GRAPHQL;

    private const string AMEND_NOTE = <<<'GRAPHQL'
        mutation AddClinicalNoteAmendment($input: AddClinicalNoteAmendmentInput!) {
            addClinicalNoteAmendment(input: $input) {
                id
                body
                status
                amendments {
                    type
                    body
                    reason
                    authorUserId
                    createdAt
                }
            }
        }
        GRAPHQL;

    private const string CLINICAL_RECORD = <<<'GRAPHQL'
        query ClinicalRecord($input: PatientClinicalRecordInput!) {
            clinicalRecord(input: $input) {
                patientId
                problems { id display status encounterId }
                allergies { id substance severity verificationStatus encounterId }
                observations { id code display valueNumeric unit encounterId }
                notes {
                    id
                    title
                    body
                    status
                    signedAt
                    amendments { type body reason }
                }
                timeline { type resourceType resourceId encounterId occurredAt }
            }
        }
        GRAPHQL;

    public function test_clinical_record_preserves_provenance_signed_note_integrity_and_timeline(): void
    {
        [$owner, $organization, $facility, $patient, $encounter] = $this->workspace();
        $this->actingAs($owner, 'web');

        $context = [
            'organizationId' => (string) $organization->getKey(),
            'facilityId' => (string) $facility->getKey(),
            'patientId' => (string) $patient->getKey(),
            'encounterId' => (string) $encounter->getKey(),
        ];

        $problem = $this->postGraphQL(self::RECORD_PROBLEM, 'clinical-problem-create', [
            'input' => $context + [
                'display' => 'Synthetic hypertension',
                'status' => 'ACTIVE',
                'onsetDate' => '2026-01-15',
            ],
        ]);
        $problem
            ->assertOk()
            ->assertJsonPath('data.recordProblem.display', 'Synthetic hypertension')
            ->assertJsonPath('data.recordProblem.status', 'ACTIVE');

        $allergy = $this->postGraphQL(self::RECORD_ALLERGY, 'clinical-allergy-create', [
            'input' => $context + [
                'substance' => 'Synthetic medication A',
                'reaction' => 'Synthetic rash',
                'severity' => 'MILD',
                'status' => 'ACTIVE',
                'verificationStatus' => 'CONFIRMED',
            ],
        ]);
        $allergy
            ->assertOk()
            ->assertJsonPath('data.recordAllergy.severity', 'MILD')
            ->assertJsonPath('data.recordAllergy.verificationStatus', 'CONFIRMED');

        $observation = $this->postGraphQL(self::RECORD_OBSERVATION, 'clinical-observation-create', [
            'input' => $context + [
                'codeSystem' => 'urn:aurevia:synthetic:vitals',
                'code' => 'HEART_RATE',
                'display' => 'Heart rate',
                'valueNumeric' => 72,
                'unit' => 'beats/min',
            ],
        ]);
        $observation
            ->assertOk()
            ->assertJsonPath('data.recordObservation.code', 'HEART_RATE')
            ->assertJsonPath('data.recordObservation.valueNumeric', 72);

        $created = $this->postGraphQL(self::CREATE_NOTE, 'clinical-note-create', [
            'input' => $context + [
                'noteType' => 'PROGRESS',
                'title' => 'Synthetic progress note',
                'body' => 'Initial synthetic assessment.',
            ],
        ]);
        $noteId = $created->json('data.createClinicalNote.id');
        self::assertIsString($noteId);
        $created->assertOk()->assertJsonPath('data.createClinicalNote.status', 'DRAFT');

        $updated = $this->postGraphQL(self::UPDATE_NOTE, 'clinical-note-update', [
            'input' => $context + [
                'noteId' => $noteId,
                'title' => 'Synthetic progress note',
                'body' => 'Final synthetic assessment before signing.',
            ],
        ]);
        $updated
            ->assertOk()
            ->assertJsonPath('data.updateClinicalNoteDraft.body', 'Final synthetic assessment before signing.')
            ->assertJsonPath('data.updateClinicalNoteDraft.status', 'DRAFT');

        $signed = $this->postGraphQL(self::SIGN_NOTE, 'clinical-note-sign', [
            'input' => $context + ['noteId' => $noteId],
        ]);
        $signed
            ->assertOk()
            ->assertJsonPath('data.signClinicalNote.status', 'SIGNED')
            ->assertJsonPath('data.signClinicalNote.body', 'Final synthetic assessment before signing.');
        self::assertIsString($signed->json('data.signClinicalNote.signedAt'));

        $rejectedEdit = $this->postGraphQL(self::UPDATE_NOTE, 'clinical-note-signed-edit', [
            'input' => $context + [
                'noteId' => $noteId,
                'title' => 'Unsafe overwrite attempt',
                'body' => 'This must never replace signed content.',
            ],
        ]);
        $rejectedEdit
            ->assertOk()
            ->assertJsonPath('data', null);

        $amended = $this->postGraphQL(self::AMEND_NOTE, 'clinical-note-correction', [
            'input' => $context + [
                'noteId' => $noteId,
                'type' => 'CORRECTION',
                'body' => 'Synthetic correction content.',
                'reason' => 'Correct synthetic documentation wording.',
            ],
        ]);
        $amended
            ->assertOk()
            ->assertJsonPath('data.addClinicalNoteAmendment.body', 'Final synthetic assessment before signing.')
            ->assertJsonPath('data.addClinicalNoteAmendment.amendments.0.type', 'CORRECTION')
            ->assertJsonPath('data.addClinicalNoteAmendment.amendments.0.reason', 'Correct synthetic documentation wording.');

        $record = $this->postGraphQL(self::CLINICAL_RECORD, 'clinical-record-view', [
            'input' => [
                'organizationId' => (string) $organization->getKey(),
                'facilityId' => (string) $facility->getKey(),
                'patientId' => (string) $patient->getKey(),
            ],
        ]);
        $record
            ->assertOk()
            ->assertJsonPath('data.clinicalRecord.patientId', $patient->getKey())
            ->assertJsonPath('data.clinicalRecord.notes.0.status', 'SIGNED')
            ->assertJsonPath('data.clinicalRecord.notes.0.body', 'Final synthetic assessment before signing.')
            ->assertJsonCount(1, 'data.clinicalRecord.problems')
            ->assertJsonCount(1, 'data.clinicalRecord.allergies')
            ->assertJsonCount(1, 'data.clinicalRecord.observations');
        $record->assertJsonFragment(['type' => 'CLINICAL_NOTE_SIGNED']);
        $record->assertJsonFragment(['type' => 'CLINICAL_NOTE_CORRECTION']);
        $record->assertJsonFragment(['type' => 'OBSERVATION_RECORDED']);

        $this->assertDatabaseHas('clinical_notes', [
            'id' => $noteId,
            'body' => 'Final synthetic assessment before signing.',
            'status' => 'SIGNED',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'patient_id' => $patient->getKey(),
            'resource_id' => $noteId,
            'action' => AuditAction::SIGN_CLINICAL_NOTE->value,
            'correlation_id' => 'clinical-note-sign',
        ]);
    }

    public function test_wrong_patient_encounter_context_is_rejected_before_clinical_write(): void
    {
        [$owner, $organization, $facility, $patient, $encounter] = $this->workspace('clinical-wrong-patient-org');
        $otherPatient = $this->patient($organization, $facility, 'Other', 'Patient');
        $this->consent($owner, $organization, $facility, $otherPatient);
        $this->actingAs($owner, 'web');

        $response = $this->postGraphQL(self::RECORD_OBSERVATION, 'clinical-wrong-patient', [
            'input' => [
                'organizationId' => (string) $organization->getKey(),
                'facilityId' => (string) $facility->getKey(),
                'patientId' => (string) $otherPatient->getKey(),
                'encounterId' => (string) $encounter->getKey(),
                'code' => 'SPO2',
                'display' => 'Oxygen saturation',
                'valueNumeric' => 98,
                'unit' => '%',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'clinical-wrong-patient');
        $this->assertDatabaseCount('clinical_observations', 0);
        $this->assertDatabaseCount('clinical_problems', 0);
        self::assertNotSame($patient->getKey(), $otherPatient->getKey());
    }

    public function test_staff_cannot_modify_clinical_record(): void
    {
        [$owner, $organization, $facility, $patient, $encounter] = $this->workspace('clinical-staff-denied-org');
        $staff = User::factory()->create();
        OrganizationMembership::query()->create([
            'user_id' => $staff->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => OrganizationRole::STAFF->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);
        $this->actingAs($staff, 'web');

        $response = $this->postGraphQL(self::RECORD_PROBLEM, 'clinical-staff-denied', [
            'input' => [
                'organizationId' => (string) $organization->getKey(),
                'facilityId' => (string) $facility->getKey(),
                'patientId' => (string) $patient->getKey(),
                'encounterId' => (string) $encounter->getKey(),
                'display' => 'Unauthorized synthetic problem',
                'status' => 'ACTIVE',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'clinical-staff-denied');
        $this->assertDatabaseCount('clinical_problems', 0);
        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $staff->getKey(),
            'patient_id' => $patient->getKey(),
            'action' => AuditAction::MANAGE_CLINICAL_RECORD->value,
            'outcome' => 'DENIED',
            'correlation_id' => 'clinical-staff-denied',
        ]);
        self::assertNotSame($owner->getKey(), $staff->getKey());
    }

    public function test_signed_note_requires_append_only_correction_reason(): void
    {
        [$owner, $organization, $facility, $patient, $encounter] = $this->workspace('clinical-correction-org');
        $this->actingAs($owner, 'web');
        $context = [
            'organizationId' => (string) $organization->getKey(),
            'facilityId' => (string) $facility->getKey(),
            'patientId' => (string) $patient->getKey(),
            'encounterId' => (string) $encounter->getKey(),
        ];

        $noteId = $this->postGraphQL(self::CREATE_NOTE, 'clinical-correction-create', [
            'input' => $context + [
                'noteType' => 'PROGRESS',
                'body' => 'Synthetic signed note.',
            ],
        ])->json('data.createClinicalNote.id');
        self::assertIsString($noteId);

        $this->postGraphQL(self::SIGN_NOTE, 'clinical-correction-sign', [
            'input' => $context + ['noteId' => $noteId],
        ])->assertOk()->assertJsonPath('data.signClinicalNote.status', 'SIGNED');

        $withoutReason = $this->postGraphQL(self::AMEND_NOTE, 'clinical-correction-no-reason', [
            'input' => $context + [
                'noteId' => $noteId,
                'type' => 'CORRECTION',
                'body' => 'Correction without required reason.',
            ],
        ]);
        $withoutReason->assertOk()->assertJsonPath('data', null);
        $this->assertDatabaseCount('clinical_note_amendments', 0);
    }

    /** @return array{User, Organization, Facility, Patient, Encounter} */
    private function workspace(string $slug = 'clinical-record-foundation-org'): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Clinical Record Foundation Organization',
            'slug' => $slug,
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Clinical Record Facility',
            'code' => strtoupper(substr(hash('sha256', $slug), 0, 8)),
        ]);
        OrganizationMembership::query()->create([
            'user_id' => $owner->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => OrganizationRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);

        $patient = $this->patient($organization, $facility, 'Clinical', 'Patient');
        $this->consent($owner, $organization, $facility, $patient);

        $encounter = Encounter::query()->create([
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'patient_id' => $patient->getKey(),
            'department_id' => null,
            'appointment_id' => null,
            'type' => 'OUTPATIENT',
            'status' => 'IN_PROGRESS',
            'arrived_at' => now()->subMinutes(10),
            'started_at' => now()->subMinutes(5),
            'ended_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'created_by_user_id' => $owner->getKey(),
        ]);

        return [$owner, $organization, $facility, $patient, $encounter];
    }

    private function patient(Organization $organization, Facility $facility, string $given, string $family): Patient
    {
        return Patient::query()->create([
            'organization_id' => $organization->getKey(),
            'registration_facility_id' => $facility->getKey(),
            'given_name' => $given,
            'normalized_given_name' => strtolower($given),
            'middle_name' => null,
            'family_name' => $family,
            'normalized_family_name' => strtolower($family),
            'preferred_name' => null,
            'date_of_birth' => '1990-04-12',
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
            'data_category' => ConsentDataCategory::CLINICAL->value,
            'purpose' => ConsentPurpose::TREATMENT->value,
            'recipient_class' => ConsentRecipientClass::CARE_TEAM->value,
            'status' => ConsentStatus::ACTIVE->value,
            'granted_by_user_id' => $owner->getKey(),
            'effective_from' => now()->subHour(),
            'effective_until' => null,
        ]);
    }
}
