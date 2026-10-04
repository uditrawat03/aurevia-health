<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\AppointmentLifecycleService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\AppointmentData;

final readonly class CancelAppointmentMutation
{
    public function __construct(
        private AppointmentLifecycleService $appointments,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, appointmentId: string, reason: string}} $args */
    public function __invoke(mixed $root, array $args): AppointmentData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->appointments->cancel(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            appointmentId: $input['appointmentId'],
            reason: $input['reason'],
        );
    }
}
