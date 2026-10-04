<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Identity\OrganizationMembershipService;
use App\Domains\Identity\Data\OrganizationMembershipData;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;

final readonly class AssignOrganizationMembershipMutation
{
    public function __construct(
        private OrganizationAuthorizationService $authorization,
        private OrganizationMembershipService $memberships,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, userId: string, role: string, allFacilities: bool, facilityIds: list<string>}} $args
     */
    public function __invoke(mixed $root, array $args): OrganizationMembershipData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_MEMBERSHIPS,
            requiresAllFacilities: true,
        );

        return $this->memberships->assign(
            userId: $input['userId'],
            organizationId: $input['organizationId'],
            role: OrganizationRole::from($input['role']),
            allFacilities: $input['allFacilities'],
            facilityIds: $input['facilityIds'],
        );
    }
}
