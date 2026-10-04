<?php

declare(strict_types=1);

namespace App\Application\System;

final readonly class SystemInfo
{
    public function __construct(
        public string $name,
        public string $version,
        public string $graphqlEndpoint,
    ) {}
}
