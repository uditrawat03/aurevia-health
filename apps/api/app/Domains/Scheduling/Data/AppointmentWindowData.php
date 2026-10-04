<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class AppointmentWindowData
{
    public function __construct(
        public string $organizationId,
        public ?string $facilityId,
        public string $from,
        public string $to,
    ) {}
}
