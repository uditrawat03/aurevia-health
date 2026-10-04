<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Encounter\EncounterService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Encounter\Enums\EncounterStatus;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class TransitionEncounterMutation
{
    public function __construct(
        private EncounterService $encounters,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, encounterId: string, toStatus: string, reason?: string|null}} $args */
    public function __invoke(mixed $root, array $args): EncounterData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_ENCOUNTERS,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->encounters->transition(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            encounterId: $input['encounterId'],
            toStatus: EncounterStatus::from($input['toStatus']),
            reason: $input['reason'] ?? null,
        );
    }
}
