<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\AppointmentLifecycleService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\AppointmentData;
use App\Domains\Scheduling\Data\RescheduleAppointmentRequestData;

final readonly class RescheduleAppointmentMutation
{
    public function __construct(
        private AppointmentLifecycleService $appointments,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, appointmentId: string, resourceIds: list<string>, startsAtLocal: string, timezone: string}} $args */
    public function __invoke(mixed $root, array $args): AppointmentData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->appointments->reschedule(
            actorUserId: $this->authorization->authenticatedUserId(),
            request: new RescheduleAppointmentRequestData(
                organizationId: $input['organizationId'],
                facilityId: $input['facilityId'],
                patientId: $input['patientId'],
                appointmentId: $input['appointmentId'],
                resourceIds: $input['resourceIds'],
                startsAtLocal: $input['startsAtLocal'],
                timezone: $input['timezone'],
            ),
        );
    }
}
