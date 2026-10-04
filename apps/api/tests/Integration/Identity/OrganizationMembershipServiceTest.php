<?php

declare(strict_types=1);

namespace Tests\Integration\Identity;

use App\Application\Identity\OrganizationMembershipService;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\User;
use DomainException;
use Tests\IntegrationTestCase;

final class OrganizationMembershipServiceTest extends IntegrationTestCase
{
    public function test_membership_persists_role_status_and_selected_facility_scope(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Synthetic Identity Organization',
            'slug' => 'synthetic-identity-organization',
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => 'Synthetic Facility',
            'code' => 'SYN-ID',
        ]);

        $service = $this->app->make(OrganizationMembershipService::class);
        $membership = $service->assign(
            userId: (string) $user->getKey(),
            organizationId: (string) $organization->getKey(),
            role: OrganizationRole::STAFF,
            allFacilities: false,
            facilityIds: [(string) $facility->getKey()],
        );

        self::assertSame(OrganizationRole::STAFF->value, $membership->role);
        self::assertSame(MembershipStatus::ACTIVE->value, $membership->status);
        self::assertFalse($membership->allFacilities);
        self::assertSame([(string) $facility->getKey()], $membership->facilityIds);

        $revoked = $service->revoke(
            userId: (string) $user->getKey(),
            organizationId: (string) $organization->getKey(),
        );

        self::assertSame(MembershipStatus::REVOKED->value, $revoked->status);
    }
    public function test_last_active_owner_cannot_be_revoked(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Synthetic Owner Guard',
            'slug' => 'synthetic-owner-guard',
            'country_code' => 'GB',
            'country_profile_code' => 'GB',
            'country_profile_version' => '1.0.0',
        ]);

        $service = $this->app->make(OrganizationMembershipService::class);
        $service->assign(
            userId: (string) $user->getKey(),
            organizationId: (string) $organization->getKey(),
            role: OrganizationRole::OWNER,
            allFacilities: true,
            facilityIds: [],
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('An organization must retain at least one active owner.');

        $service->revoke(
            userId: (string) $user->getKey(),
            organizationId: (string) $organization->getKey(),
        );
    }

}
