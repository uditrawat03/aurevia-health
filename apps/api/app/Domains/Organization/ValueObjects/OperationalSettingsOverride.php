<?php

declare(strict_types=1);

namespace App\Domains\Organization\ValueObjects;

use App\Domains\Organization\Enums\OperationalSettingKey;
use App\Domains\Organization\Enums\WeekStart;
use DateTimeZone;
use InvalidArgumentException;

final readonly class OperationalSettingsOverride
{
    private const string LOCALE_PATTERN = '/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/';

    public function __construct(
        public ?string $locale,
        public ?string $timezone,
        public ?string $weekStartsOn,
    ) {
        $this->validate();
    }

    public function valueFor(OperationalSettingKey $key): ?string
    {
        return match ($key) {
            OperationalSettingKey::LOCALE => $this->locale,
            OperationalSettingKey::TIMEZONE => $this->timezone,
            OperationalSettingKey::WEEK_STARTS_ON => $this->weekStartsOn,
        };
    }

    private function validate(): void
    {
        $hasInvalidLocale = $this->locale !== null
            && preg_match(self::LOCALE_PATTERN, $this->locale) !== 1;
        if ($hasInvalidLocale) {
            throw new InvalidArgumentException('Locale must be a valid BCP 47-style language tag.');
        }

        $hasInvalidTimezone = $this->timezone !== null
            && ! in_array($this->timezone, DateTimeZone::listIdentifiers(), true);
        if ($hasInvalidTimezone) {
            throw new InvalidArgumentException('Timezone must be a valid IANA timezone identifier.');
        }

        $hasInvalidWeekStart = $this->weekStartsOn !== null
            && WeekStart::tryFrom($this->weekStartsOn) === null;
        if ($hasInvalidWeekStart) {
            throw new InvalidArgumentException('Week start must be a supported WeekStart value.');
        }
    }
}
