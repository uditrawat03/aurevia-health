<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domains\Identity\Data\OrganizationMembershipData;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Identity\Repositories\IdentityRepo;
use App\Domains\Organization\Repositories\OrganizationRepo;
use DomainException;

final readonly class OrganizationMembershipService
{
    public function __construct(
        private IdentityRepo $identities,
        private OrganizationRepo $organizations,
    ) {}

    /** @param list<string> $facilityIds */
    public function assign(
        string $userId,
        string $organizationId,
        OrganizationRole $role,
        bool $allFacilities,
        array $facilityIds,
    ): OrganizationMembershipData {
        $normalizedUserId = $this->numericUserId($userId);
        if (! $this->identities->userExists($normalizedUserId)) {
            throw new DomainException('User was not found.');
        }

        if ($this->organizations->findOrganization($organizationId) === null) {
            throw new DomainException('Organization was not found.');
        }

        $uniqueFacilityIds = array_values(array_unique($facilityIds));
        $hasSelectedFacilities = $uniqueFacilityIds !== [];
        if ($allFacilities && $hasSelectedFacilities) {
            throw new DomainException('All-facility memberships cannot also select facilities.');
        }

        if (! $allFacilities && ! $hasSelectedFacilities) {
            throw new DomainException('A facility-scoped membership must select at least one facility.');
        }

        if ($role === OrganizationRole::OWNER && ! $allFacilities) {
            throw new DomainException('Organization owners must have all-facility scope.');
        }

        $currentMembership = $this->identities->membershipForUserOrganization(
            $normalizedUserId,
            $organizationId,
        );
        $removesActiveOwner = $currentMembership !== null
            && $currentMembership->status === MembershipStatus::ACTIVE->value
            && $currentMembership->role === OrganizationRole::OWNER->value
            && $role !== OrganizationRole::OWNER;
        if ($removesActiveOwner && $this->identities->activeOwnerCount($organizationId) <= 1) {
            throw new DomainException('An organization must retain at least one active owner.');
        }

        foreach ($uniqueFacilityIds as $facilityId) {
            if ($this->organizations->findFacility($organizationId, $facilityId) === null) {
                throw new DomainException('Selected facility does not belong to the organization.');
            }
        }

        return $this->identities->upsertMembership(
            userId: $normalizedUserId,
            organizationId: $organizationId,
            role: $role,
            allFacilities: $allFacilities,
            facilityIds: $uniqueFacilityIds,
        );
    }

    public function revoke(string $userId, string $organizationId): OrganizationMembershipData
    {
        $normalizedUserId = $this->numericUserId($userId);
        $currentMembership = $this->identities->membershipForUserOrganization(
            $normalizedUserId,
            $organizationId,
        );
        if ($currentMembership === null) {
            throw new DomainException('Organization membership was not found.');
        }

        $revokesActiveOwner = $currentMembership->status === MembershipStatus::ACTIVE->value
            && $currentMembership->role === OrganizationRole::OWNER->value;
        if ($revokesActiveOwner && $this->identities->activeOwnerCount($organizationId) <= 1) {
            throw new DomainException('An organization must retain at least one active owner.');
        }

        $membership = $this->identities->revokeMembership(
            $normalizedUserId,
            $organizationId,
        );
        if ($membership === null) {
            throw new DomainException('Organization membership disappeared during revocation.');
        }

        return $membership;
    }

    private function numericUserId(string $userId): int
    {
        if (! ctype_digit($userId)) {
            throw new DomainException('User ID is invalid.');
        }

        return (int) $userId;
    }
}
