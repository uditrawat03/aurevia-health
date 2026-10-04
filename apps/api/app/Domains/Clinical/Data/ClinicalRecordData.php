<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class ClinicalRecordData
{
    /**
     * @param list<ProblemData> $problems
     * @param list<AllergyData> $allergies
     * @param list<ObservationData> $observations
     * @param list<ClinicalNoteData> $notes
     * @param list<PatientTimelineEventData> $timeline
     */
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public array $problems,
        public array $allergies,
        public array $observations,
        public array $notes,
        public array $timeline,
    ) {}
}
