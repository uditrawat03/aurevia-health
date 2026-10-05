<?php

declare(strict_types=1);

namespace App\Domains\Terminology\Data;

final readonly class TerminologySystemData
{
    public function __construct(
        public string $system,
        public string $name,
        public bool $versionRequired,
        public string $scope,
    ) {}
}
