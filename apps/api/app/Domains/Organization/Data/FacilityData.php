<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class FacilityData
{
    /** @param list<DepartmentData> $departments */
    public function __construct(
        public string $id,
        public string $organizationId,
        public ?string $healthSystemId,
        public string $name,
        public string $code,
        public OperationalSettingsOverride $settings,
        public array $departments = [],
    ) {}
}
