<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

final readonly class ConfigurationChangeSet
{
    /** @param list<ConfigurationChangeEntry> $entries */
    public function __construct(public array $entries) {}
}
