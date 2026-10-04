<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Organization\OrganizationService;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Organization\Data\CreateOrganizationData;
use App\Domains\Organization\Data\OrganizationData;
use Illuminate\Database\DatabaseManager;

final readonly class OrganizationOnboardingService
{
    public function __construct(
        private OrganizationService $organizations,
        private OrganizationMembershipService $memberships,
        private OrganizationAuthorizationService $authorization,
        private DatabaseManager $database,
    ) {}

    public function createOrganization(CreateOrganizationData $data): OrganizationData
    {
        $userId = $this->authorization->authenticatedUserId();

        return $this->database->connection()->transaction(function () use ($data, $userId): OrganizationData {
            $organization = $this->organizations->createOrganization($data);

            $this->memberships->assign(
                userId: (string) $userId,
                organizationId: $organization->id,
                role: OrganizationRole::OWNER,
                allFacilities: true,
                facilityIds: [],
            );

            return $organization;
        });
    }
}
