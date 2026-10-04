<?php

declare(strict_types=1);

namespace Tests\Unit\Patient;

use App\Domains\Identity\Authorization\RoleAndScopeOrganizationAccessPolicy;
use App\Domains\Identity\Data\OrganizationAccessContext;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;
use PHPUnit\Framework\TestCase;

final class PatientAuthorizationPolicyTest extends TestCase
{
    public function test_staff_can_manage_patients_only_inside_selected_facility_scope(): void
    {
        $policy = new RoleAndScopeOrganizationAccessPolicy();

        self::assertTrue($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::STAFF,
            permission: OrganizationPermission::MANAGE_PATIENTS,
            allFacilities: false,
            facilityIds: ['facility-a'],
            requestedFacilityId: 'facility-a',
            requiresAllFacilities: false,
        )));

        self::assertFalse($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::STAFF,
            permission: OrganizationPermission::MANAGE_PATIENTS,
            allFacilities: false,
            facilityIds: ['facility-a'],
            requestedFacilityId: 'facility-b',
            requiresAllFacilities: false,
        )));
    }

    public function test_viewer_has_no_patient_permission_and_merge_review_requires_owner_or_admin(): void
    {
        $policy = new RoleAndScopeOrganizationAccessPolicy();

        self::assertFalse($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::VIEWER,
            permission: OrganizationPermission::VIEW_PATIENTS,
            allFacilities: true,
            facilityIds: [],
            requestedFacilityId: null,
            requiresAllFacilities: true,
        )));

        self::assertTrue($policy->allows(new OrganizationAccessContext(
            role: OrganizationRole::OWNER,
            permission: OrganizationPermission::REVIEW_PATIENT_MERGES,
            allFacilities: true,
            facilityIds: [],
            requestedFacilityId: null,
            requiresAllFacilities: true,
        )));
    }
}
