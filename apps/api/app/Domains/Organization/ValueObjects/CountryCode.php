<?php

declare(strict_types=1);

namespace App\Domains\Organization\ValueObjects;

use InvalidArgumentException;

final readonly class CountryCode
{
    public const string INDIA = 'IN';
    public const string UNITED_KINGDOM = 'GB';
    public const string UNITED_STATES = 'US';

    private const string PATTERN = '/^[A-Z]{2}$/';

    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));
        $isValid = preg_match(self::PATTERN, $normalized) === 1;

        if (! $isValid) {
            throw new InvalidArgumentException('Country code must be an ISO 3166-1 alpha-2 code.');
        }

        return new self($normalized);
    }
}
