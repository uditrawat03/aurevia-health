<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class SchedulingResourceData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $type,
        public string $name,
        public string $code,
        public bool $active,
    ) {}
}
