<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\SchedulingQueryService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\SchedulingResourceData;

final readonly class SchedulingResourcesQuery
{
    public function __construct(
        private SchedulingQueryService $scheduling,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId?: string|null}} $args
     *  @return list<SchedulingResourceData>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $input = $args['input'];
        $facilityId = $input['facilityId'] ?? null;
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_SCHEDULE,
            facilityId: $facilityId,
            requiresAllFacilities: $facilityId === null,
        );

        return $this->scheduling->resources($input['organizationId'], $facilityId);
    }
}
