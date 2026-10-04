<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\ValueObjects\CountryCode;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class CreateOrganizationData
{
    public function __construct(
        public string $name,
        public string $slug,
        public CountryCode $countryCode,
        public OperationalSettingsOverride $settings,
    ) {}
}
