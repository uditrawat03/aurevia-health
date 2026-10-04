<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

final readonly class CreateHealthSystemData
{
    public function __construct(
        public string $organizationId,
        public string $name,
        public string $code,
    ) {}
}
