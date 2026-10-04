<?php

declare(strict_types=1);

namespace App\Domains\Identity\Authorization;

use App\Domains\Identity\Data\OrganizationAccessContext;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;

final readonly class RoleAndScopeOrganizationAccessPolicy implements OrganizationAccessPolicy
{
    public function allows(OrganizationAccessContext $context): bool
    {
        $hasPermission = in_array(
            $context->permission,
            $this->permissionsFor($context->role),
            true,
        );
        if (! $hasPermission) {
            return false;
        }

        if ($context->requiresAllFacilities) {
            return $context->allFacilities;
        }

        if ($context->requestedFacilityId === null) {
            return $context->allFacilities;
        }

        return $context->allFacilities
            || in_array($context->requestedFacilityId, $context->facilityIds, true);
    }

    /** @return list<OrganizationPermission> */
    private function permissionsFor(OrganizationRole $role): array
    {
        return match ($role) {
            OrganizationRole::OWNER, OrganizationRole::ADMIN => [
                OrganizationPermission::VIEW_ORGANIZATION,
                OrganizationPermission::MANAGE_ORGANIZATION,
                OrganizationPermission::VIEW_SETTINGS,
                OrganizationPermission::MANAGE_SETTINGS,
                OrganizationPermission::MANAGE_MEMBERSHIPS,
            ],
            OrganizationRole::CLINICIAN, OrganizationRole::STAFF, OrganizationRole::VIEWER => [
                OrganizationPermission::VIEW_ORGANIZATION,
                OrganizationPermission::VIEW_SETTINGS,
            ],
        };
    }
}
