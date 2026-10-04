<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Encounter\EncounterService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Encounter\Enums\EncounterType;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class CreateEncounterMutation
{
    public function __construct(
        private EncounterService $encounters,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, departmentId?: string|null, appointmentId?: string|null, type: string}} $args */
    public function __invoke(mixed $root, array $args): EncounterData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_ENCOUNTERS,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->encounters->create(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            type: EncounterType::from($input['type']),
            departmentId: $input['departmentId'] ?? null,
            appointmentId: $input['appointmentId'] ?? null,
        );
    }
}
