<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class RescheduleAppointmentData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $appointmentId,
        public string $startsAt,
        public string $endsAt,
        public string $timezone,
        public int $actorUserId,
    ) {}
}
