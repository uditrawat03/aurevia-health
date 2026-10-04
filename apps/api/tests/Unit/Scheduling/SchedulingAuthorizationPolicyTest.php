<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Domains\Identity\Authorization\RoleAndScopeOrganizationAccessPolicy;
use App\Domains\Identity\Data\OrganizationAccessContext;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;
use Tests\TestCase;

final class SchedulingAuthorizationPolicyTest extends TestCase
{
    public function test_scheduling_permissions_respect_role_and_facility_scope(): void
    {
        $policy = new RoleAndScopeOrganizationAccessPolicy();

        self::assertTrue($policy->allows($this->context(
            role: OrganizationRole::STAFF,
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            requestedFacilityId: 'facility-a',
        )));
        self::assertFalse($policy->allows($this->context(
            role: OrganizationRole::STAFF,
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            requestedFacilityId: 'facility-b',
        )));
        self::assertTrue($policy->allows($this->context(
            role: OrganizationRole::CLINICIAN,
            permission: OrganizationPermission::VIEW_SCHEDULE,
            requestedFacilityId: 'facility-a',
        )));
        self::assertFalse($policy->allows($this->context(
            role: OrganizationRole::CLINICIAN,
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            requestedFacilityId: 'facility-a',
        )));
        self::assertFalse($policy->allows($this->context(
            role: OrganizationRole::VIEWER,
            permission: OrganizationPermission::VIEW_SCHEDULE,
            requestedFacilityId: 'facility-a',
        )));
    }

    private function context(
        OrganizationRole $role,
        OrganizationPermission $permission,
        string $requestedFacilityId,
    ): OrganizationAccessContext {
        return new OrganizationAccessContext(
            role: $role,
            permission: $permission,
            allFacilities: false,
            facilityIds: ['facility-a'],
            requestedFacilityId: $requestedFacilityId,
            requiresAllFacilities: false,
        );
    }
}
