<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\Enums\ConfigurationScope;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class ScopedSettingsData
{
    public function __construct(
        public string $organizationId,
        public ConfigurationScope $scope,
        public string $scopeId,
        public OperationalSettingsOverride $settings,
    ) {}
}
