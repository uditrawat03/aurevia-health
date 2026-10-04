<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Scheduling\WaitlistService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Scheduling\Data\WaitlistEntryData;

final readonly class CancelWaitlistMutation
{
    public function __construct(
        private WaitlistService $waitlist,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, waitlistEntryId: string, reason: string}} $args */
    public function __invoke(mixed $root, array $args): WaitlistEntryData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_SCHEDULE,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->waitlist->cancel(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            waitlistEntryId: $input['waitlistEntryId'],
            reason: $input['reason'],
        );
    }
}
