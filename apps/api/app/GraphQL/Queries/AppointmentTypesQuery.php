<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\SchedulingQueryService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\AppointmentTypeData;

final readonly class AppointmentTypesQuery
{
    public function __construct(
        private SchedulingQueryService $scheduling,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId?: string|null}} $args
     *  @return list<AppointmentTypeData>
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

        return $this->scheduling->appointmentTypes($input['organizationId'], $facilityId);
    }
}
