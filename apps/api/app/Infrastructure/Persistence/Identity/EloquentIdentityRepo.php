<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Identity;

use App\Domains\Identity\Data\AuthenticatedUserData;
use App\Domains\Identity\Data\OrganizationMembershipData;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Identity\Repositories\IdentityRepo;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\DatabaseManager;

final readonly class EloquentIdentityRepo implements IdentityRepo
{
    public function __construct(private DatabaseManager $database) {}

    public function user(int $userId): ?AuthenticatedUserData
    {
        $user = User::query()->find($userId);
        if (! $user instanceof User) {
            return null;
        }

        return new AuthenticatedUserData(
            id: (string) $user->getAuthIdentifier(),
            name: (string) $user->getAttribute('name'),
            email: (string) $user->getAttribute('email'),
            memberships: $this->membershipsForUser($userId),
        );
    }

    public function userExists(int $userId): bool
    {
        return User::query()->whereKey($userId)->exists();
    }

    public function membershipForUserOrganization(
        int $userId,
        string $organizationId,
    ): ?OrganizationMembershipData {
        $membership = OrganizationMembership::query()
            ->with('facilities:id')
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->first();

        return $membership instanceof OrganizationMembership
            ? $this->mapMembership($membership)
            : null;
    }

    public function membershipsForUser(int $userId): array
    {
        $memberships = OrganizationMembership::query()
            ->with('facilities:id')
            ->where('user_id', $userId)
            ->orderBy('organization_id')
            ->get();

        $result = [];
        foreach ($memberships as $membership) {
            $result[] = $this->mapMembership($membership);
        }

        return $result;
    }

    public function activeOwnerCount(string $organizationId): int
    {
        return OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('role', OrganizationRole::OWNER->value)
            ->where('status', MembershipStatus::ACTIVE->value)
            ->count();
    }

    public function upsertMembership(
        int $userId,
        string $organizationId,
        OrganizationRole $role,
        bool $allFacilities,
        array $facilityIds,
    ): OrganizationMembershipData {
        return $this->database->connection()->transaction(function () use (
            $userId,
            $organizationId,
            $role,
            $allFacilities,
            $facilityIds,
        ): OrganizationMembershipData {
            $membership = OrganizationMembership::query()->firstOrNew([
                'user_id' => $userId,
                'organization_id' => $organizationId,
            ]);

            $membership->forceFill([
                'role' => $role->value,
                'status' => MembershipStatus::ACTIVE->value,
                'all_facilities' => $allFacilities,
            ])->save();

            $membership->facilities()->sync($allFacilities ? [] : $facilityIds);
            $membership->load('facilities:id');

            return $this->mapMembership($membership);
        });
    }

    public function revokeMembership(
        int $userId,
        string $organizationId,
    ): ?OrganizationMembershipData {
        $membership = OrganizationMembership::query()
            ->with('facilities:id')
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->first();

        if (! $membership instanceof OrganizationMembership) {
            return null;
        }

        $membership->forceFill([
            'status' => MembershipStatus::REVOKED->value,
        ])->save();

        return $this->mapMembership($membership);
    }

    private function mapMembership(OrganizationMembership $membership): OrganizationMembershipData
    {
        $facilityIds = [];
        foreach ($membership->facilities as $facility) {
            $facilityIds[] = (string) $facility->getKey();
        }

        return new OrganizationMembershipData(
            id: (string) $membership->getKey(),
            userId: (string) $membership->getAttribute('user_id'),
            organizationId: (string) $membership->getAttribute('organization_id'),
            role: (string) $membership->getAttribute('role'),
            status: (string) $membership->getAttribute('status'),
            allFacilities: (bool) $membership->getAttribute('all_facilities'),
            facilityIds: $facilityIds,
        );
    }
}
