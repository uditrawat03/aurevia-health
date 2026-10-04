<?php

declare(strict_types=1);

namespace App\Domains\Identity\Repositories;

use App\Domains\Identity\Data\AuthenticatedUserData;
use App\Domains\Identity\Data\OrganizationMembershipData;
use App\Domains\Identity\Enums\OrganizationRole;

interface IdentityRepo
{
    public function user(int $userId): ?AuthenticatedUserData;

    public function userExists(int $userId): bool;

    public function membershipForUserOrganization(
        int $userId,
        string $organizationId,
    ): ?OrganizationMembershipData;

    /** @return list<OrganizationMembershipData> */
    public function membershipsForUser(int $userId): array;

    public function activeOwnerCount(string $organizationId): int;

    /** @param list<string> $facilityIds */
    public function upsertMembership(
        int $userId,
        string $organizationId,
        OrganizationRole $role,
        bool $allFacilities,
        array $facilityIds,
    ): OrganizationMembershipData;

    public function revokeMembership(
        int $userId,
        string $organizationId,
    ): ?OrganizationMembershipData;
}
