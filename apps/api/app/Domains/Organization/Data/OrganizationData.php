<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class OrganizationData
{
    /**
     * @param list<HealthSystemData> $healthSystems
     * @param list<FacilityData> $facilities
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $countryCode,
        public string $countryProfileCode,
        public string $countryProfileVersion,
        public OperationalSettingsOverride $settings,
        public array $healthSystems = [],
        public array $facilities = [],
    ) {}
}
