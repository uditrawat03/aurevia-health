<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class BookAppointmentResultData
{
    public function __construct(
        public AppointmentData $appointment,
        public bool $replayed,
    ) {}
}
