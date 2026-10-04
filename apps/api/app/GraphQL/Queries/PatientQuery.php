<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Patient\Data\PatientData;

final readonly class PatientQuery
{
    public function __construct(
        private PatientQueryService $patients,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{organizationId: string, id: string} $args */
    public function __invoke(mixed $root, array $args): PatientData
    {
        $patient = $this->patients->patient($args['organizationId'], $args['id']);

        $this->authorization->authorize(
            organizationId: $args['organizationId'],
            permission: OrganizationPermission::VIEW_PATIENTS,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
        );

        return $patient;
    }
}
