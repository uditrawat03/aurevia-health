<?php

declare(strict_types=1);

namespace App\GraphQL\Inputs;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class OperationalSettingsInputMapper
{
    /**
     * GraphQL framework input arrives as an associative array before it can be mapped to a typed value object.
     *
     * @param array{locale?: mixed, timezone?: mixed, weekStartsOn?: mixed}|null $input
     */
    public function map(?array $input): OperationalSettingsOverride
    {
        $input ??= [];

        return new OperationalSettingsOverride(
            locale: $this->nullableString($input, 'locale'),
            timezone: $this->nullableString($input, 'timezone'),
            weekStartsOn: $this->nullableString($input, 'weekStartsOn'),
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    private function nullableString(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
