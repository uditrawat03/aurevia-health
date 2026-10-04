<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Organization\OrganizationQueryService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Organization\Data\ResolvedOperationalSettingsData;

final readonly class ResolvedOperationalSettingsQuery
{
    public function __construct(
        private OrganizationQueryService $organizations,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, facilityId?: string|null, departmentId?: string|null}} $args
     */
    public function __invoke(mixed $root, array $args): ResolvedOperationalSettingsData
    {
        $input = $args['input'];
        $facilityId = $input['facilityId'] ?? null;

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_SETTINGS,
            facilityId: $facilityId,
            requiresAllFacilities: $facilityId === null,
        );

        return $this->organizations->resolvedOperationalSettings(
            organizationId: $input['organizationId'],
            facilityId: $facilityId,
            departmentId: $input['departmentId'] ?? null,
        );
    }
}
