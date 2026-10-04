<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class AppointmentData
{
    /** @param list<SchedulingResourceData> $resources */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $patientDisplayName,
        public AppointmentTypeData $appointmentType,
        public array $resources,
        public string $status,
        public string $startsAt,
        public string $endsAt,
        public string $timezone,
        public ?string $reason,
        public ?string $cancelledAt,
        public ?string $cancellationReason,
        public string $createdAt,
    ) {}
}
