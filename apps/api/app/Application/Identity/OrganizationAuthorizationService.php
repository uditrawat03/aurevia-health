<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Audit\AuditService;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Identity\Authorization\OrganizationAccessPolicy;
use App\Domains\Identity\Data\AuthenticatedUserData;
use App\Domains\Identity\Data\OrganizationAccessContext;
use App\Domains\Identity\Data\OrganizationMembershipData;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Identity\Repositories\IdentityRepo;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use LogicException;

final readonly class OrganizationAuthorizationService
{
    public function __construct(
        private AuthFactory $auth,
        private IdentityRepo $identities,
        private OrganizationAccessPolicy $policy,
        private AuditService $audit,
    ) {}

    public function currentUser(): AuthenticatedUserData
    {
        $userId = $this->authenticatedUserId();
        $user = $this->identities->user($userId);

        if ($user === null) {
            throw new AuthenticationException('Authenticated user no longer exists.');
        }

        return $user;
    }

    public function authenticatedUserId(): int
    {
        $identifier = $this->auth->guard('web')->id();
        if (is_int($identifier)) {
            return $identifier;
        }

        $isNumericIdentifier = is_string($identifier) && ctype_digit($identifier);
        if ($isNumericIdentifier) {
            return (int) $identifier;
        }

        throw new AuthenticationException('Authentication is required.');
    }

    public function authorize(
        string $organizationId,
        OrganizationPermission $permission,
        ?string $facilityId = null,
        bool $requiresAllFacilities = false,
    ): OrganizationMembershipData {
        $actorUserId = $this->authenticatedUserId();
        $membership = $this->identities->membershipForUserOrganization(
            $actorUserId,
            $organizationId,
        );

        $hasActiveMembership = $membership !== null
            && $membership->status === MembershipStatus::ACTIVE->value;
        if (! $hasActiveMembership) {
            $this->audit->recordAuthorizationDecision(
                actorUserId: $actorUserId,
                organizationId: $organizationId,
                permission: $permission,
                facilityId: $facilityId,
                outcome: AuditOutcome::DENIED,
            );

            throw new AuthorizationException('This action is unauthorized.');
        }

        $role = OrganizationRole::tryFrom($membership->role);
        if ($role === null) {
            $this->audit->recordAuthorizationDecision(
                actorUserId: $actorUserId,
                organizationId: $organizationId,
                permission: $permission,
                facilityId: $facilityId,
                outcome: AuditOutcome::DENIED,
            );

            throw new LogicException('Organization membership contains an unsupported role.');
        }

        $allowed = $this->policy->allows(new OrganizationAccessContext(
            role: $role,
            permission: $permission,
            allFacilities: $membership->allFacilities,
            facilityIds: $membership->facilityIds,
            requestedFacilityId: $facilityId,
            requiresAllFacilities: $requiresAllFacilities,
        ));

        if (! $allowed) {
            $this->audit->recordAuthorizationDecision(
                actorUserId: $actorUserId,
                organizationId: $organizationId,
                permission: $permission,
                facilityId: $facilityId,
                outcome: AuditOutcome::DENIED,
            );

            throw new AuthorizationException('This action is unauthorized.');
        }

        $this->audit->recordAuthorizationDecision(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            permission: $permission,
            facilityId: $facilityId,
            outcome: AuditOutcome::ALLOWED,
        );

        return $membership;
    }
}
