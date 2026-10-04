<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

final readonly class CountryProfileMetadataData
{
    public function __construct(
        public string $countryCode,
        public string $profileCode,
        public string $version,
    ) {}
}
