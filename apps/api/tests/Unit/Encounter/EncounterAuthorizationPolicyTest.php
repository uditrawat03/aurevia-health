<?php

declare(strict_types=1);

namespace Tests\Unit\Encounter;

use App\Domains\Identity\Authorization\RoleAndScopeOrganizationAccessPolicy;
use App\Domains\Identity\Data\OrganizationAccessContext;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;
use Tests\TestCase;

final class EncounterAuthorizationPolicyTest extends TestCase
{
    public function test_clinical_and_operational_roles_require_facility_scope_for_encounters(): void
    {
        $policy = new RoleAndScopeOrganizationAccessPolicy();

        self::assertTrue($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::CLINICIAN,
            permission: OrganizationPermission::MANAGE_ENCOUNTERS,
            allFacilities: false,
            facilityIds: ['facility-a'],
            requestedFacilityId: 'facility-a',
            requiresAllFacilities: false,
        )));
        self::assertFalse($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::STAFF,
            permission: OrganizationPermission::VIEW_ENCOUNTERS,
            allFacilities: false,
            facilityIds: ['facility-a'],
            requestedFacilityId: 'facility-b',
            requiresAllFacilities: false,
        )));
        self::assertFalse($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::VIEWER,
            permission: OrganizationPermission::VIEW_ENCOUNTERS,
            allFacilities: true,
            facilityIds: [],
            requestedFacilityId: 'facility-a',
            requiresAllFacilities: false,
        )));
    }
}
