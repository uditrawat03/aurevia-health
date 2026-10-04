<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

final readonly class HealthSystemData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public string $code,
    ) {}
}
