<?php

declare(strict_types=1);

namespace Tests\Unit\CountryProfiles;

use App\CountryProfiles\CoreCountryProfile;
use App\CountryProfiles\IndiaCountryProfile;
use App\CountryProfiles\ServerCountryProfileResolver;
use App\CountryProfiles\UnitedKingdomCountryProfile;
use App\CountryProfiles\UnitedStatesCountryProfile;
use App\Domains\Organization\Enums\WeekStart;
use App\Domains\Organization\ValueObjects\CountryCode;
use DomainException;
use PHPUnit\Framework\TestCase;

final class ServerCountryProfileResolverTest extends TestCase
{
    private ServerCountryProfileResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ServerCountryProfileResolver(
            new CoreCountryProfile(),
            new IndiaCountryProfile(),
            new UnitedKingdomCountryProfile(),
            new UnitedStatesCountryProfile(),
        );
    }

    public function test_it_selects_the_india_profile_server_side(): void
    {
        $profile = $this->resolver->current(CountryCode::fromString('in'));

        self::assertSame('IN', $profile->countryCode);
        self::assertSame(IndiaCountryProfile::CODE, $profile->profileCode);
        self::assertSame(IndiaCountryProfile::VERSION, $profile->version);
        self::assertSame('en-IN', $profile->defaults->locale);
        self::assertSame('Asia/Kolkata', $profile->defaults->timezone);
        self::assertSame(WeekStart::MONDAY->value, $profile->defaults->weekStartsOn);
    }

    public function test_it_exposes_country_specific_operational_defaults_without_changing_core_schema(): void
    {
        $uk = $this->resolver->current(CountryCode::fromString('GB'));
        $us = $this->resolver->current(CountryCode::fromString('US'));

        self::assertSame('en-GB', $uk->defaults->locale);
        self::assertSame('Europe/London', $uk->defaults->timezone);
        self::assertSame(WeekStart::MONDAY->value, $uk->defaults->weekStartsOn);
        self::assertSame('en-US', $us->defaults->locale);
        self::assertNull($us->defaults->timezone);
        self::assertSame(WeekStart::SUNDAY->value, $us->defaults->weekStartsOn);
    }

    public function test_unknown_country_keeps_its_country_code_and_uses_core_profile(): void
    {
        $profile = $this->resolver->current(CountryCode::fromString('CA'));

        self::assertSame('CA', $profile->countryCode);
        self::assertSame(CoreCountryProfile::CODE, $profile->profileCode);
        self::assertSame(CoreCountryProfile::VERSION, $profile->version);
        self::assertNull($profile->defaults->locale);
        self::assertNull($profile->defaults->timezone);
        self::assertNull($profile->defaults->weekStartsOn);
    }

    public function test_core_profile_can_remain_pinned_for_a_country_without_a_dedicated_profile(): void
    {
        $profile = $this->resolver->pinned(
            CountryCode::fromString('CA'),
            CoreCountryProfile::CODE,
            CoreCountryProfile::VERSION,
        );

        self::assertSame('CA', $profile->countryCode);
        self::assertSame(CoreCountryProfile::CODE, $profile->profileCode);
    }

    public function test_pinned_profile_must_still_be_available_at_the_pinned_version(): void
    {
        $this->expectException(DomainException::class);

        $this->resolver->pinned(
            CountryCode::fromString('IN'),
            IndiaCountryProfile::CODE,
            '0.9.0',
        );
    }
}
