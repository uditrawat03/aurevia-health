<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\WaitlistService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\JoinWaitlistRequestData;
use App\Domains\Scheduling\Data\JoinWaitlistResultData;

final readonly class JoinWaitlistMutation
{
    public function __construct(
        private WaitlistService $waitlist,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, appointmentTypeId: string, preferredFromLocal: string, preferredUntilLocal: string, timezone: string, reason?: string|null, idempotencyKey: string}} $args */
    public function __invoke(mixed $root, array $args): JoinWaitlistResultData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->waitlist->join(
            actorUserId: $this->authorization->authenticatedUserId(),
            request: new JoinWaitlistRequestData(
                organizationId: $input['organizationId'],
                facilityId: $input['facilityId'],
                patientId: $input['patientId'],
                appointmentTypeId: $input['appointmentTypeId'],
                preferredFromLocal: $input['preferredFromLocal'],
                preferredUntilLocal: $input['preferredUntilLocal'],
                timezone: $input['timezone'],
                reason: $input['reason'] ?? null,
                idempotencyKey: $input['idempotencyKey'],
            ),
        );
    }
}
