<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class UpdatedOperationalSettingsData
{
    public function __construct(
        public string $scope,
        public string $scopeId,
        public OperationalSettingsOverride $settings,
    ) {}
}
