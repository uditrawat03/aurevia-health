<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class AppointmentTypeData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $code,
        public string $name,
        public int $durationMinutes,
        public bool $active,
    ) {}
}
