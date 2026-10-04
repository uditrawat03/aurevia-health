<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Repositories;

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

interface ClinicalRecordRepo
{
    public function forPatient(string $organizationId, string $facilityId, string $patientId): ClinicalRecordData;

    public function recordProblem(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        ?string $codeSystem,
        ?string $code,
        string $display,
        ProblemStatus $status,
        ?string $onsetDate,
        int $actorUserId,
        string $recordedAt,
    ): ProblemData;

    public function updateProblemStatus(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $problemId,
        ProblemStatus $status,
        int $actorUserId,
        string $occurredAt,
    ): ProblemData;

    public function recordAllergy(
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
        int $actorUserId,
        string $recordedAt,
    ): AllergyData;

    public function recordObservation(
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
        string $effectiveAt,
        int $actorUserId,
        string $recordedAt,
    ): ObservationData;

    public function createNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteType,
        ?string $title,
        string $body,
        int $actorUserId,
    ): ClinicalNoteData;

    public function updateDraftNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        ?string $title,
        string $body,
        int $actorUserId,
    ): ClinicalNoteData;

    public function signNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        int $actorUserId,
        string $signedAt,
    ): ClinicalNoteData;

    public function addNoteAmendment(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        ClinicalNoteAmendmentType $type,
        string $body,
        ?string $reason,
        int $actorUserId,
    ): ClinicalNoteData;
}
