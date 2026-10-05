<?php

declare(strict_types=1);

namespace App\Domains\Terminology\Data;

final readonly class TerminologyCodingData
{
    public function __construct(
        public string $system,
        public string $version,
        public string $code,
        public string $display,
        public bool $userSelected = false,
    ) {}
}
