<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class WaitlistEntryData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $patientDisplayName,
        public AppointmentTypeData $appointmentType,
        public string $status,
        public string $preferredFrom,
        public string $preferredUntil,
        public string $timezone,
        public ?string $reason,
        public ?string $cancelledAt,
        public ?string $cancellationReason,
        public string $createdAt,
    ) {}
}
