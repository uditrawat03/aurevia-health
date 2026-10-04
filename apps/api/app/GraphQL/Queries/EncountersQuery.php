<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Encounter\EncounterQueryService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class EncountersQuery
{
    public function __construct(
        private EncounterQueryService $encounters,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /**
     * @param array{input: array{organizationId: string, facilityId: string, patientId: string}} $args
     * @return list<EncounterData>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_ENCOUNTERS,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->encounters->forPatient(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );
    }
}
