<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class CreateFacilityData
{
    public function __construct(
        public string $organizationId,
        public ?string $healthSystemId,
        public string $name,
        public string $code,
        public OperationalSettingsOverride $settings,
    ) {}
}
