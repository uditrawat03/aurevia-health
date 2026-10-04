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
                OrganizationPermission::VIEW_AUDIT,
                OrganizationPermission::VIEW_PATIENTS,
                OrganizationPermission::MANAGE_PATIENTS,
                OrganizationPermission::REVIEW_PATIENT_MERGES,
                OrganizationPermission::VIEW_CONSENTS,
                OrganizationPermission::MANAGE_CONSENTS,
                OrganizationPermission::BREAK_GLASS_PATIENT_ACCESS,
                OrganizationPermission::VIEW_SCHEDULE,
                OrganizationPermission::MANAGE_SCHEDULE,
                OrganizationPermission::MANAGE_SCHEDULING_CONFIGURATION,
                OrganizationPermission::VIEW_ENCOUNTERS,
                OrganizationPermission::MANAGE_ENCOUNTERS,
                OrganizationPermission::VIEW_CLINICAL_RECORD,
                OrganizationPermission::MANAGE_CLINICAL_RECORD,
            ],
            OrganizationRole::CLINICIAN => [
                OrganizationPermission::VIEW_ORGANIZATION,
                OrganizationPermission::VIEW_SETTINGS,
                OrganizationPermission::VIEW_PATIENTS,
                OrganizationPermission::MANAGE_PATIENTS,
                OrganizationPermission::VIEW_CONSENTS,
                OrganizationPermission::BREAK_GLASS_PATIENT_ACCESS,
                OrganizationPermission::VIEW_SCHEDULE,
                OrganizationPermission::VIEW_ENCOUNTERS,
                OrganizationPermission::MANAGE_ENCOUNTERS,
                OrganizationPermission::VIEW_CLINICAL_RECORD,
                OrganizationPermission::MANAGE_CLINICAL_RECORD,
            ],
            OrganizationRole::STAFF => [
                OrganizationPermission::VIEW_ORGANIZATION,
                OrganizationPermission::VIEW_SETTINGS,
                OrganizationPermission::VIEW_PATIENTS,
                OrganizationPermission::MANAGE_PATIENTS,
                OrganizationPermission::VIEW_CONSENTS,
                OrganizationPermission::MANAGE_CONSENTS,
                OrganizationPermission::VIEW_SCHEDULE,
                OrganizationPermission::MANAGE_SCHEDULE,
                OrganizationPermission::VIEW_ENCOUNTERS,
                OrganizationPermission::MANAGE_ENCOUNTERS,
                OrganizationPermission::VIEW_CLINICAL_RECORD,
            ],
            OrganizationRole::VIEWER => [
                OrganizationPermission::VIEW_ORGANIZATION,
                OrganizationPermission::VIEW_SETTINGS,
            ],
        };
    }
}
