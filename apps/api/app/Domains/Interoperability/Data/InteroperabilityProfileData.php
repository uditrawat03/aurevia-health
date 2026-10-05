<?php

declare(strict_types=1);

namespace App\Domains\Interoperability\Data;

final readonly class InteroperabilityProfileData
{
    public function __construct(
        public string $code,
        public string $standard,
        public string $version,
        public string $mediaType,
    ) {}
}
