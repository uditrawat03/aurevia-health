<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\SchedulingQueryService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\AppointmentData;
use App\Domains\Scheduling\Data\AppointmentWindowData;

final readonly class AppointmentsQuery
{
    public function __construct(
        private SchedulingQueryService $scheduling,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId?: string|null, from: string, to: string}} $args
     *  @return list<AppointmentData>
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

        return $this->scheduling->appointments(new AppointmentWindowData(
            organizationId: $input['organizationId'],
            facilityId: $facilityId,
            from: $input['from'],
            to: $input['to'],
        ));
    }
}
