<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\AppointmentBookingService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\BookAppointmentRequestData;
use App\Domains\Scheduling\Data\BookAppointmentResultData;

final readonly class BookAppointmentMutation
{
    public function __construct(
        private AppointmentBookingService $appointments,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, appointmentTypeId: string, resourceIds: list<string>, startsAtLocal: string, timezone: string, reason?: string|null, idempotencyKey: string}} $args */
    public function __invoke(mixed $root, array $args): BookAppointmentResultData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->appointments->book(
            actorUserId: $this->authorization->authenticatedUserId(),
            request: new BookAppointmentRequestData(
                organizationId: $input['organizationId'],
                facilityId: $input['facilityId'],
                patientId: $input['patientId'],
                appointmentTypeId: $input['appointmentTypeId'],
                resourceIds: $input['resourceIds'],
                startsAtLocal: $input['startsAtLocal'],
                timezone: $input['timezone'],
                reason: $input['reason'] ?? null,
                idempotencyKey: $input['idempotencyKey'],
            ),
        );
    }
}
