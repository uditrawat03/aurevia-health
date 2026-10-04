<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Repositories;

use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Encounter\Data\PersistEncounterData;
use App\Domains\Encounter\Data\TransitionEncounterData;

interface EncounterRepo
{
    public function find(string $organizationId, string $encounterId): ?EncounterData;

    /** @return list<EncounterData> */
    public function forPatient(string $organizationId, string $facilityId, string $patientId): array;

    public function findByAppointment(string $organizationId, string $appointmentId): ?EncounterData;

    public function create(PersistEncounterData $data): EncounterData;

    public function transition(TransitionEncounterData $data): EncounterData;
}
