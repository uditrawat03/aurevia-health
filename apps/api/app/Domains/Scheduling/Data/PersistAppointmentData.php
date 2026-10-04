<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

use App\Domains\Scheduling\Enums\AppointmentStatus;

final readonly class PersistAppointmentData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $appointmentTypeId,
        public AppointmentStatus $status,
        public string $startsAt,
        public string $endsAt,
        public string $timezone,
        public ?string $reason,
        public int $createdByUserId,
        public string $idempotencyKey,
        public string $requestFingerprint,
    ) {}
}
