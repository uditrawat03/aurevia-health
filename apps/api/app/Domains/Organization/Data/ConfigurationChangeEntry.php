<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

use App\Domains\Organization\Enums\OperationalSettingKey;

final readonly class ConfigurationChangeEntry
{
    public function __construct(
        public OperationalSettingKey $key,
        public ?string $previousValue,
        public ?string $newValue,
    ) {}
}
