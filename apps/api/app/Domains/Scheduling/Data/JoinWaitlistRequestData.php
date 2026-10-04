<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class JoinWaitlistRequestData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $appointmentTypeId,
        public string $preferredFromLocal,
        public string $preferredUntilLocal,
        public string $timezone,
        public ?string $reason,
        public string $idempotencyKey,
    ) {}
}
