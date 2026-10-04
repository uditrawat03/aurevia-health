<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Domains\Identity\Authorization\RoleAndScopeOrganizationAccessPolicy;
use App\Domains\Identity\Data\OrganizationAccessContext;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class RoleAndScopeOrganizationAccessPolicyTest extends TestCase
{
    #[DataProvider('accessCases')]
    public function test_role_and_facility_scope_are_both_required(
        OrganizationRole $role,
        OrganizationPermission $permission,
        bool $allFacilities,
        ?string $requestedFacilityId,
        bool $requiresAllFacilities,
        bool $expected,
    ): void {
        $policy = new RoleAndScopeOrganizationAccessPolicy();

        $allowed = $policy->allows(new OrganizationAccessContext(
            role: $role,
            permission: $permission,
            allFacilities: $allFacilities,
            facilityIds: ['facility-a'],
            requestedFacilityId: $requestedFacilityId,
            requiresAllFacilities: $requiresAllFacilities,
        ));

        self::assertSame($expected, $allowed);
    }

    /**
     * @return iterable<string, array{OrganizationRole, OrganizationPermission, bool, string|null, bool, bool}>
     */
    public static function accessCases(): iterable
    {
        yield 'owner may manage organization' => [
            OrganizationRole::OWNER,
            OrganizationPermission::MANAGE_ORGANIZATION,
            true,
            null,
            true,
            true,
        ];
        yield 'clinician may not manage organization' => [
            OrganizationRole::CLINICIAN,
            OrganizationPermission::MANAGE_ORGANIZATION,
            true,
            null,
            true,
            false,
        ];
        yield 'selected facility may be viewed' => [
            OrganizationRole::STAFF,
            OrganizationPermission::VIEW_SETTINGS,
            false,
            'facility-a',
            false,
            true,
        ];
        yield 'unselected facility is denied' => [
            OrganizationRole::STAFF,
            OrganizationPermission::VIEW_SETTINGS,
            false,
            'facility-b',
            false,
            false,
        ];
        yield 'selected scope cannot read full organization hierarchy' => [
            OrganizationRole::ADMIN,
            OrganizationPermission::VIEW_ORGANIZATION,
            false,
            null,
            true,
            false,
        ];
    }
}
