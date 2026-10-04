<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class CancelWaitlistEntryData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $waitlistEntryId,
        public int $actorUserId,
        public string $reason,
        public string $cancelledAt,
    ) {}
}
