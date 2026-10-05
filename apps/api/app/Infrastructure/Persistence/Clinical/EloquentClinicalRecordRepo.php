<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Clinical;

use App\Domains\Clinical\Data\AllergyData;
use App\Domains\Clinical\Data\ClinicalNoteAmendmentData;
use App\Domains\Clinical\Data\ClinicalNoteData;
use App\Domains\Clinical\Data\ClinicalRecordData;
use App\Domains\Clinical\Data\ObservationData;
use App\Domains\Clinical\Data\PatientTimelineEventData;
use App\Domains\Clinical\Data\ProblemData;
use App\Domains\Clinical\Enums\AllergySeverity;
use App\Domains\Clinical\Enums\AllergyStatus;
use App\Domains\Clinical\Enums\AllergyVerificationStatus;
use App\Domains\Clinical\Enums\ClinicalNoteAmendmentType;
use App\Domains\Clinical\Enums\ClinicalNoteStatus;
use App\Domains\Clinical\Enums\ProblemStatus;
use App\Domains\Clinical\Repositories\ClinicalRecordRepo;
use App\Models\ClinicalNote;
use App\Models\ClinicalNoteAmendment;
use App\Models\ClinicalObservation;
use App\Models\ClinicalProblem;
use App\Models\PatientAllergy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use App\Exceptions\ExpectedBusinessRuleViolation as DomainException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

final readonly class EloquentClinicalRecordRepo implements ClinicalRecordRepo
{
    public function __construct(private DatabaseManager $database) {}

    public function forPatient(string $organizationId, string $facilityId, string $patientId): ClinicalRecordData
    {
        $problems = ClinicalProblem::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('patient_id', $patientId)
            ->orderByDesc('recorded_at')
            ->get()
            ->map(fn (ClinicalProblem $problem): ProblemData => $this->mapProblem($problem))
            ->all();

        $allergies = PatientAllergy::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('patient_id', $patientId)
            ->orderByDesc('recorded_at')
            ->get()
            ->map(fn (PatientAllergy $allergy): AllergyData => $this->mapAllergy($allergy))
            ->all();

        $observations = ClinicalObservation::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('patient_id', $patientId)
            ->orderByDesc('effective_at')
            ->get()
            ->map(fn (ClinicalObservation $observation): ObservationData => $this->mapObservation($observation))
            ->all();

        $notes = ClinicalNote::query()
            ->with('amendments')
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ClinicalNote $note): ClinicalNoteData => $this->mapNote($note))
            ->all();

        return new ClinicalRecordData(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            problems: $problems,
            allergies: $allergies,
            observations: $observations,
            notes: $notes,
            timeline: $this->timeline($organizationId, $facilityId, $patientId, $problems, $allergies, $observations, $notes),
        );
    }

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
    ): ProblemData {
        $problem = ClinicalProblem::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'patient_id' => $patientId,
            'encounter_id' => $encounterId,
            'code_system' => $codeSystem,
            'code' => $code,
            'display' => $display,
            'status' => $status->value,
            'onset_date' => $onsetDate,
            'resolved_at' => $status === ProblemStatus::RESOLVED ? CarbonImmutable::parse($recordedAt) : null,
            'recorded_by_user_id' => $actorUserId,
            'recorded_at' => CarbonImmutable::parse($recordedAt),
        ]);

        return $this->mapProblem($problem);
    }

    public function updateProblemStatus(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $problemId,
        ProblemStatus $status,
        int $actorUserId,
        string $occurredAt,
    ): ProblemData {
        return $this->database->connection()->transaction(function () use (
            $organizationId,
            $facilityId,
            $patientId,
            $problemId,
            $status,
            $occurredAt,
        ): ProblemData {
            $problem = ClinicalProblem::query()
                ->where('organization_id', $organizationId)
                ->where('facility_id', $facilityId)
                ->where('patient_id', $patientId)
                ->whereKey($problemId)
                ->lockForUpdate()
                ->first();

            if (! $problem instanceof ClinicalProblem) {
                throw new DomainException('Problem was not found for this patient and facility.');
            }

            $problem->fill([
                'status' => $status->value,
                'resolved_at' => $status === ProblemStatus::RESOLVED
                    ? CarbonImmutable::parse($occurredAt)
                    : null,
            ]);
            $problem->save();

            return $this->mapProblem($problem);
        }, 3);
    }

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
    ): AllergyData {
        $allergy = PatientAllergy::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'patient_id' => $patientId,
            'encounter_id' => $encounterId,
            'code_system' => $codeSystem,
            'code' => $code,
            'substance' => $substance,
            'reaction' => $reaction,
            'severity' => $severity->value,
            'status' => $status->value,
            'verification_status' => $verificationStatus->value,
            'recorded_by_user_id' => $actorUserId,
            'recorded_at' => CarbonImmutable::parse($recordedAt),
        ]);

        return $this->mapAllergy($allergy);
    }

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
    ): ObservationData {
        $observation = ClinicalObservation::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'patient_id' => $patientId,
            'encounter_id' => $encounterId,
            'code_system' => $codeSystem,
            'code' => $code,
            'display' => $display,
            'value_numeric' => $valueNumeric,
            'value_text' => $valueText,
            'unit' => $unit,
            'effective_at' => CarbonImmutable::parse($effectiveAt),
            'recorded_by_user_id' => $actorUserId,
            'recorded_at' => CarbonImmutable::parse($recordedAt),
        ]);

        return $this->mapObservation($observation);
    }

    public function createNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteType,
        ?string $title,
        string $body,
        int $actorUserId,
    ): ClinicalNoteData {
        $note = ClinicalNote::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'patient_id' => $patientId,
            'encounter_id' => $encounterId,
            'note_type' => $noteType,
            'title' => $title,
            'body' => $body,
            'status' => ClinicalNoteStatus::DRAFT->value,
            'author_user_id' => $actorUserId,
            'signed_by_user_id' => null,
            'signed_at' => null,
        ]);
        $note->setRelation('amendments', new Collection());

        return $this->mapNote($note);
    }

    public function updateDraftNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        ?string $title,
        string $body,
        int $actorUserId,
    ): ClinicalNoteData {
        return $this->database->connection()->transaction(function () use (
            $organizationId,
            $facilityId,
            $patientId,
            $encounterId,
            $noteId,
            $title,
            $body,
        ): ClinicalNoteData {
            $note = $this->lockNote($organizationId, $facilityId, $patientId, $encounterId, $noteId);
            if ((string) $note->getAttribute('status') !== ClinicalNoteStatus::DRAFT->value) {
                throw new DomainException('Signed clinical notes cannot be edited.');
            }

            $note->fill(['title' => $title, 'body' => $body]);
            $note->save();
            $note->load('amendments');

            return $this->mapNote($note);
        }, 3);
    }

    public function signNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
        int $actorUserId,
        string $signedAt,
    ): ClinicalNoteData {
        return $this->database->connection()->transaction(function () use (
            $organizationId,
            $facilityId,
            $patientId,
            $encounterId,
            $noteId,
            $actorUserId,
            $signedAt,
        ): ClinicalNoteData {
            $note = $this->lockNote($organizationId, $facilityId, $patientId, $encounterId, $noteId);
            if ((string) $note->getAttribute('status') !== ClinicalNoteStatus::DRAFT->value) {
                throw new DomainException('Only draft clinical notes can be signed.');
            }

            $note->fill([
                'status' => ClinicalNoteStatus::SIGNED->value,
                'signed_by_user_id' => $actorUserId,
                'signed_at' => CarbonImmutable::parse($signedAt),
            ]);
            $note->save();
            $note->load('amendments');

            return $this->mapNote($note);
        }, 3);
    }

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
    ): ClinicalNoteData {
        return $this->database->connection()->transaction(function () use (
            $organizationId,
            $facilityId,
            $patientId,
            $encounterId,
            $noteId,
            $type,
            $body,
            $reason,
            $actorUserId,
        ): ClinicalNoteData {
            $note = $this->lockNote($organizationId, $facilityId, $patientId, $encounterId, $noteId);
            if ((string) $note->getAttribute('status') !== ClinicalNoteStatus::SIGNED->value) {
                throw new DomainException('Addenda and corrections require a signed clinical note.');
            }

            ClinicalNoteAmendment::query()->create([
                'clinical_note_id' => $noteId,
                'type' => $type->value,
                'body' => $body,
                'reason' => $reason,
                'author_user_id' => $actorUserId,
            ]);

            $note->load('amendments');

            return $this->mapNote($note);
        }, 3);
    }

    private function lockNote(
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        string $noteId,
    ): ClinicalNote {
        $note = ClinicalNote::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('patient_id', $patientId)
            ->where('encounter_id', $encounterId)
            ->whereKey($noteId)
            ->lockForUpdate()
            ->first();

        if (! $note instanceof ClinicalNote) {
            throw new DomainException('Clinical note was not found for this patient and encounter.');
        }

        return $note;
    }

    private function mapProblem(ClinicalProblem $problem): ProblemData
    {
        return new ProblemData(
            id: (string) $problem->getKey(),
            organizationId: (string) $problem->getAttribute('organization_id'),
            facilityId: (string) $problem->getAttribute('facility_id'),
            patientId: (string) $problem->getAttribute('patient_id'),
            encounterId: (string) $problem->getAttribute('encounter_id'),
            codeSystem: $this->nullableString($problem->getAttribute('code_system')),
            code: $this->nullableString($problem->getAttribute('code')),
            display: (string) $problem->getAttribute('display'),
            status: (string) $problem->getAttribute('status'),
            onsetDate: $problem->getAttribute('onset_date') instanceof CarbonInterface
                ? $problem->getAttribute('onset_date')->toDateString()
                : $this->nullableString($problem->getAttribute('onset_date')),
            resolvedAt: $this->timestamp($problem->getAttribute('resolved_at')),
            recordedByUserId: (int) $problem->getAttribute('recorded_by_user_id'),
            recordedAt: $this->requiredTimestamp($problem->getAttribute('recorded_at')),
        );
    }

    private function mapAllergy(PatientAllergy $allergy): AllergyData
    {
        return new AllergyData(
            id: (string) $allergy->getKey(),
            organizationId: (string) $allergy->getAttribute('organization_id'),
            facilityId: (string) $allergy->getAttribute('facility_id'),
            patientId: (string) $allergy->getAttribute('patient_id'),
            encounterId: (string) $allergy->getAttribute('encounter_id'),
            codeSystem: $this->nullableString($allergy->getAttribute('code_system')),
            code: $this->nullableString($allergy->getAttribute('code')),
            substance: (string) $allergy->getAttribute('substance'),
            reaction: $this->nullableString($allergy->getAttribute('reaction')),
            severity: (string) $allergy->getAttribute('severity'),
            status: (string) $allergy->getAttribute('status'),
            verificationStatus: (string) $allergy->getAttribute('verification_status'),
            recordedByUserId: (int) $allergy->getAttribute('recorded_by_user_id'),
            recordedAt: $this->requiredTimestamp($allergy->getAttribute('recorded_at')),
        );
    }

    private function mapObservation(ClinicalObservation $observation): ObservationData
    {
        $numeric = $observation->getAttribute('value_numeric');

        return new ObservationData(
            id: (string) $observation->getKey(),
            organizationId: (string) $observation->getAttribute('organization_id'),
            facilityId: (string) $observation->getAttribute('facility_id'),
            patientId: (string) $observation->getAttribute('patient_id'),
            encounterId: (string) $observation->getAttribute('encounter_id'),
            codeSystem: $this->nullableString($observation->getAttribute('code_system')),
            code: (string) $observation->getAttribute('code'),
            display: (string) $observation->getAttribute('display'),
            valueNumeric: $numeric === null ? null : (float) $numeric,
            valueText: $this->nullableString($observation->getAttribute('value_text')),
            unit: $this->nullableString($observation->getAttribute('unit')),
            effectiveAt: $this->requiredTimestamp($observation->getAttribute('effective_at')),
            recordedByUserId: (int) $observation->getAttribute('recorded_by_user_id'),
            recordedAt: $this->requiredTimestamp($observation->getAttribute('recorded_at')),
        );
    }

    private function mapNote(ClinicalNote $note): ClinicalNoteData
    {
        $amendments = [];
        foreach ($note->getRelation('amendments')->sortBy('created_at') as $amendment) {
            if ($amendment instanceof ClinicalNoteAmendment) {
                $amendments[] = new ClinicalNoteAmendmentData(
                    id: (string) $amendment->getKey(),
                    type: (string) $amendment->getAttribute('type'),
                    body: (string) $amendment->getAttribute('body'),
                    reason: $this->nullableString($amendment->getAttribute('reason')),
                    authorUserId: (int) $amendment->getAttribute('author_user_id'),
                    createdAt: $this->requiredTimestamp($amendment->getAttribute('created_at')),
                );
            }
        }

        $signedBy = $note->getAttribute('signed_by_user_id');

        return new ClinicalNoteData(
            id: (string) $note->getKey(),
            organizationId: (string) $note->getAttribute('organization_id'),
            facilityId: (string) $note->getAttribute('facility_id'),
            patientId: (string) $note->getAttribute('patient_id'),
            encounterId: (string) $note->getAttribute('encounter_id'),
            noteType: (string) $note->getAttribute('note_type'),
            title: $this->nullableString($note->getAttribute('title')),
            body: (string) $note->getAttribute('body'),
            status: (string) $note->getAttribute('status'),
            authorUserId: (int) $note->getAttribute('author_user_id'),
            signedByUserId: $signedBy === null ? null : (int) $signedBy,
            signedAt: $this->timestamp($note->getAttribute('signed_at')),
            createdAt: $this->requiredTimestamp($note->getAttribute('created_at')),
            updatedAt: $this->requiredTimestamp($note->getAttribute('updated_at')),
            amendments: $amendments,
        );
    }

    /**
     * @param list<ProblemData> $problems
     * @param list<AllergyData> $allergies
     * @param list<ObservationData> $observations
     * @param list<ClinicalNoteData> $notes
     * @return list<PatientTimelineEventData>
     */
    private function timeline(
        string $organizationId,
        string $facilityId,
        string $patientId,
        array $problems,
        array $allergies,
        array $observations,
        array $notes,
    ): array {
        $events = [];

        $encounterEvents = $this->database->connection()->table('encounter_events')
            ->join('encounters', 'encounters.id', '=', 'encounter_events.encounter_id')
            ->where('encounters.organization_id', $organizationId)
            ->where('encounters.facility_id', $facilityId)
            ->where('encounters.patient_id', $patientId)
            ->select([
                'encounter_events.id',
                'encounter_events.encounter_id',
                'encounter_events.type',
                'encounter_events.actor_user_id',
                'encounter_events.occurred_at',
            ])
            ->get();

        foreach ($encounterEvents as $event) {
            $events[] = new PatientTimelineEventData(
                id: 'encounter:'.(string) $event->id,
                type: 'ENCOUNTER_'.(string) $event->type,
                label: 'Encounter '.strtolower(str_replace('_', ' ', (string) $event->type)),
                resourceType: 'ENCOUNTER',
                resourceId: (string) $event->encounter_id,
                encounterId: (string) $event->encounter_id,
                actorUserId: (int) $event->actor_user_id,
                occurredAt: CarbonImmutable::parse((string) $event->occurred_at)->toIso8601String(),
            );
        }

        foreach ($problems as $problem) {
            $events[] = new PatientTimelineEventData(
                id: 'problem:'.$problem->id,
                type: 'PROBLEM_RECORDED',
                label: 'Problem recorded: '.$problem->display,
                resourceType: 'CLINICAL_PROBLEM',
                resourceId: $problem->id,
                encounterId: $problem->encounterId,
                actorUserId: $problem->recordedByUserId,
                occurredAt: $problem->recordedAt,
            );
        }

        foreach ($allergies as $allergy) {
            $events[] = new PatientTimelineEventData(
                id: 'allergy:'.$allergy->id,
                type: 'ALLERGY_RECORDED',
                label: 'Allergy recorded: '.$allergy->substance,
                resourceType: 'PATIENT_ALLERGY',
                resourceId: $allergy->id,
                encounterId: $allergy->encounterId,
                actorUserId: $allergy->recordedByUserId,
                occurredAt: $allergy->recordedAt,
            );
        }

        foreach ($observations as $observation) {
            $events[] = new PatientTimelineEventData(
                id: 'observation:'.$observation->id,
                type: 'OBSERVATION_RECORDED',
                label: 'Observation recorded: '.$observation->display,
                resourceType: 'CLINICAL_OBSERVATION',
                resourceId: $observation->id,
                encounterId: $observation->encounterId,
                actorUserId: $observation->recordedByUserId,
                occurredAt: $observation->recordedAt,
            );
        }

        foreach ($notes as $note) {
            $events[] = new PatientTimelineEventData(
                id: 'note:'.$note->id,
                type: 'CLINICAL_NOTE_CREATED',
                label: 'Clinical note created',
                resourceType: 'CLINICAL_NOTE',
                resourceId: $note->id,
                encounterId: $note->encounterId,
                actorUserId: $note->authorUserId,
                occurredAt: $note->createdAt,
            );
            if ($note->signedAt !== null && $note->signedByUserId !== null) {
                $events[] = new PatientTimelineEventData(
                    id: 'note-signed:'.$note->id,
                    type: 'CLINICAL_NOTE_SIGNED',
                    label: 'Clinical note signed',
                    resourceType: 'CLINICAL_NOTE',
                    resourceId: $note->id,
                    encounterId: $note->encounterId,
                    actorUserId: $note->signedByUserId,
                    occurredAt: $note->signedAt,
                );
            }
            foreach ($note->amendments as $amendment) {
                $events[] = new PatientTimelineEventData(
                    id: 'note-amendment:'.$amendment->id,
                    type: 'CLINICAL_NOTE_'.$amendment->type,
                    label: $amendment->type === ClinicalNoteAmendmentType::CORRECTION->value
                        ? 'Clinical note correction added'
                        : 'Clinical note addendum added',
                    resourceType: 'CLINICAL_NOTE',
                    resourceId: $note->id,
                    encounterId: $note->encounterId,
                    actorUserId: $amendment->authorUserId,
                    occurredAt: $amendment->createdAt,
                );
            }
        }

        usort(
            $events,
            static fn (PatientTimelineEventData $left, PatientTimelineEventData $right): int =>
                strcmp($right->occurredAt, $left->occurredAt),
        );

        return $events;
    }

    private function timestamp(mixed $value): ?string
    {
        return $value === null ? null : $this->requiredTimestamp($value);
    }

    private function requiredTimestamp(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return CarbonImmutable::parse((string) $value)->toIso8601String();
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
