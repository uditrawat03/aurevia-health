<?php

declare(strict_types=1);

namespace App\Application\Clinical;

use App\Application\Audit\AuditService;
use App\Application\Privacy\PrivacyAuthorizationService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Clinical\Data\AllergyData;
use App\Domains\Clinical\Data\ClinicalNoteData;
use App\Domains\Clinical\Data\ClinicalRecordData;
use App\Domains\Clinical\Data\ObservationData;
use App\Domains\Clinical\Data\ProblemData;
use App\Domains\Clinical\Enums\AllergySeverity;
use App\Domains\Clinical\Enums\AllergyStatus;
use App\Domains\Clinical\Enums\AllergyVerificationStatus;
use App\Domains\Clinical\Enums\ClinicalNoteAmendmentType;
use App\Domains\Clinical\Enums\ProblemStatus;
use App\Domains\Clinical\Repositories\ClinicalRecordRepo;
use App\Domains\Encounter\Enums\EncounterStatus;
use App\Domains\Encounter\Repositories\EncounterRepo;
use App\Domains\Patient\Data\PatientData;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use Carbon\CarbonImmutable;
use DomainException;

final readonly class ClinicalRecordService
{
    public function __construct(
        private ClinicalRecordRepo $clinicalRecords,
        private EncounterRepo $encounters,
        private PatientRepo $patients,
        private PrivacyAuthorizationService $privacy,
        private AuditService $audit,
    ) {}

    public function forPatient(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
    ): ClinicalRecordData {
        $this->authorizePatient($actorUserId, $organizationId, $facilityId, $patientId);

        return $this->clinicalRecords->forPatient($organizationId, $facilityId, $patientId);
    }

    public function recordProblem(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        ?string $codeSystem,
        ?string $code,
        string $display,
        ProblemStatus $status,
        ?string $onsetDate,
    ): ProblemData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, true);
        $display = $this->requiredText($display, 'Problem display', 500);

        $problem = $this->clinicalRecords->recordProblem(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            codeSystem: $this->optionalText($codeSystem, 255),
            code: $this->optionalText($code, 128),
            display: $display,
            status: $status,
            onsetDate: $onsetDate === null ? null : CarbonImmutable::parse($onsetDate)->toDateString(),
            actorUserId: $actorUserId,
            recordedAt: CarbonImmutable::now('UTC')->toIso8601String(),
        );

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::RECORD_PROBLEM,
            resourceType: AuditResourceType::CLINICAL_PROBLEM,
            resourceId: $problem->id,
        );

        return $problem;
    }

    public function updateProblemStatus(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $problemId,
        ProblemStatus $status,
    ): ProblemData {
        $this->authorizePatient($actorUserId, $organizationId, $facilityId, $patientId);

        $problem = $this->clinicalRecords->updateProblemStatus(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            problemId: $problemId,
            status: $status,
            actorUserId: $actorUserId,
            occurredAt: CarbonImmutable::now('UTC')->toIso8601String(),
        );

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::UPDATE_PROBLEM_STATUS,
            resourceType: AuditResourceType::CLINICAL_PROBLEM,
            resourceId: $problem->id,
        );

        return $problem;
    }

    public function recordAllergy(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        ?string $codeSystem,
        ?string $code,
        string $substance,
        ?string $reaction,
        AllergySeverity $severity,
        AllergyStatus $status,
        AllergyVerificationStatus $verificationStatus,
    ): AllergyData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, true);

        $allergy = $this->clinicalRecords->recordAllergy(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            codeSystem: $this->optionalText($codeSystem, 255),
            code: $this->optionalText($code, 128),
            substance: $this->requiredText($substance, 'Allergy substance', 500),
            reaction: $this->optionalText($reaction, 500),
            severity: $severity,
            status: $status,
            verificationStatus: $verificationStatus,
            actorUserId: $actorUserId,
            recordedAt: CarbonImmutable::now('UTC')->toIso8601String(),
        );

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::RECORD_ALLERGY,
            resourceType: AuditResourceType::PATIENT_ALLERGY,
            resourceId: $allergy->id,
        );

        return $allergy;
    }

    public function recordObservation(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        ?string $codeSystem,
        string $code,
        string $display,
        ?float $valueNumeric,
        ?string $valueText,
        ?string $unit,
        ?string $effectiveAt,
    ): ObservationData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, true);

        $normalizedText = $this->optionalText($valueText, 4000);
        if (($valueNumeric === null) === ($normalizedText === null)) {
            throw new DomainException('Observation requires exactly one numeric or text value.');
        }

        $observation = $this->clinicalRecords->recordObservation(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            codeSystem: $this->optionalText($codeSystem, 255),
            code: $this->requiredText($code, 'Observation code', 128),
            display: $this->requiredText($display, 'Observation display', 255),
            valueNumeric: $valueNumeric,
            valueText: $normalizedText,
            unit: $this->optionalText($unit, 64),
            effectiveAt: $effectiveAt === null
                ? CarbonImmutable::now('UTC')->toIso8601String()
                : CarbonImmutable::parse($effectiveAt)->toIso8601String(),
            actorUserId: $actorUserId,
            recordedAt: CarbonImmutable::now('UTC')->toIso8601String(),
        );

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::RECORD_OBSERVATION,
            resourceType: AuditResourceType::CLINICAL_OBSERVATION,
            resourceId: $observation->id,
        );

        return $observation;
    }

    public function createNote(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteType,
        ?string $title,
        string $body,
    ): ClinicalNoteData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, true);

        $note = $this->clinicalRecords->createNote(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            noteType: $this->requiredText($noteType, 'Note type', 64),
            title: $this->optionalText($title, 255),
            body: $this->requiredText($body, 'Clinical note body', 100000),
            actorUserId: $actorUserId,
        );

        $this->recordNoteAudit($actorUserId, $organizationId, $facilityId, $patientId, $note->id, AuditAction::CREATE_CLINICAL_NOTE);

        return $note;
    }

    public function updateDraftNote(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        ?string $title,
        string $body,
    ): ClinicalNoteData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, true);

        $note = $this->clinicalRecords->updateDraftNote(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            noteId: $noteId,
            title: $this->optionalText($title, 255),
            body: $this->requiredText($body, 'Clinical note body', 100000),
            actorUserId: $actorUserId,
        );

        $this->recordNoteAudit($actorUserId, $organizationId, $facilityId, $patientId, $note->id, AuditAction::UPDATE_CLINICAL_NOTE_DRAFT);

        return $note;
    }

    public function signNote(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
    ): ClinicalNoteData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, true);

        $note = $this->clinicalRecords->signNote(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            noteId: $noteId,
            actorUserId: $actorUserId,
            signedAt: CarbonImmutable::now('UTC')->toIso8601String(),
        );

        $this->recordNoteAudit($actorUserId, $organizationId, $facilityId, $patientId, $note->id, AuditAction::SIGN_CLINICAL_NOTE);

        return $note;
    }

    public function addNoteAmendment(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        ClinicalNoteAmendmentType $type,
        string $body,
        ?string $reason,
    ): ClinicalNoteData {
        $this->authorizeEncounter($actorUserId, $organizationId, $facilityId, $patientId, $encounterId, false);
        $normalizedReason = $this->optionalText($reason, 4000);
        if ($type === ClinicalNoteAmendmentType::CORRECTION && $normalizedReason === null) {
            throw new DomainException('Clinical note corrections require a reason.');
        }

        $note = $this->clinicalRecords->addNoteAmendment(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            noteId: $noteId,
            type: $type,
            body: $this->requiredText($body, 'Clinical note amendment body', 100000),
            reason: $normalizedReason,
            actorUserId: $actorUserId,
        );

        $this->recordNoteAudit(
            $actorUserId,
            $organizationId,
            $facilityId,
            $patientId,
            $note->id,
            $type === ClinicalNoteAmendmentType::CORRECTION
                ? AuditAction::CORRECT_CLINICAL_NOTE
                : AuditAction::ADD_CLINICAL_NOTE_ADDENDUM,
        );

        return $note;
    }

    private function authorizeEncounter(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        bool $requireInProgress,
    ): void {
        $this->authorizePatient($actorUserId, $organizationId, $facilityId, $patientId);

        $encounter = $this->encounters->find($organizationId, $encounterId);
        if ($encounter === null
            || $encounter->facilityId !== $facilityId
            || $encounter->patientId !== $patientId
        ) {
            throw new DomainException('Encounter was not found for this patient and facility.');
        }

        if ($requireInProgress && $encounter->status !== EncounterStatus::IN_PROGRESS->value) {
            throw new DomainException('Clinical documentation requires an in-progress encounter.');
        }
    }

    private function authorizePatient(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
    ): PatientData {
        $patient = $this->patients->find($organizationId, $patientId);
        if ($patient === null || $patient->registrationFacilityId !== $facilityId) {
            throw new DomainException('Patient was not found for this organization and facility.');
        }

        $this->privacy->authorize(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: ConsentDataCategory::CLINICAL,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );

        return $patient;
    }

    private function recordNoteAudit(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $noteId,
        AuditAction $action,
    ): void {
        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: $action,
            resourceType: AuditResourceType::CLINICAL_NOTE,
            resourceId: $noteId,
        );
    }

    private function requiredText(string $value, string $field, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new DomainException($field.' is required.');
        }
        if (mb_strlen($value) > $maxLength) {
            throw new DomainException($field.' is too long.');
        }

        return $value;
    }

    private function optionalText(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            throw new DomainException('Clinical text value is too long.');
        }

        return $value;
    }
}
