<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class PersistWaitlistEntryData
{
    public function __construct(
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $appointmentTypeId,
        public string $preferredFrom,
        public string $preferredUntil,
        public string $timezone,
        public ?string $reason,
        public int $createdByUserId,
        public string $idempotencyKey,
        public string $requestFingerprint,
    ) {}
}
