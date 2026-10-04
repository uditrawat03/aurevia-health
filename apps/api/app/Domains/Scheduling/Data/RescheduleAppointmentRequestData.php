<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class RescheduleAppointmentRequestData
{
    /** @param list<string> $resourceIds */
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $appointmentId,
        public array $resourceIds,
        public string $startsAtLocal,
        public string $timezone,
    ) {}
}
