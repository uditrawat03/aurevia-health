<?php

declare(strict_types=1);

namespace App\Application\Encounter;

use App\Application\Privacy\PrivacyAuthorizationService;
use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Encounter\Repositories\EncounterRepo;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use DomainException;

final readonly class EncounterQueryService
{
    public function __construct(
        private EncounterRepo $encounters,
        private PatientRepo $patients,
        private PrivacyAuthorizationService $privacy,
    ) {}

    /** @return list<EncounterData> */
    public function forPatient(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
    ): array {
        $patient = $this->patients->find($organizationId, $patientId);
        if ($patient === null) {
            throw new DomainException('Patient was not found.');
        }
        if ($patient->registrationFacilityId !== $facilityId) {
            throw new DomainException('Patient is not registered at this facility.');
        }

        $this->privacy->authorize(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: ConsentDataCategory::CLINICAL,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );

        return $this->encounters->forPatient($organizationId, $facilityId, $patientId);
    }
}
