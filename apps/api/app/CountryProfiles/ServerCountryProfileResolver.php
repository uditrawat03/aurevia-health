<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\ValueObjects\CountryCode;
use DomainException;

final readonly class ServerCountryProfileResolver implements CountryProfileResolver
{
    public function __construct(
        private CoreCountryProfile $coreProfile,
        private IndiaCountryProfile $indiaProfile,
        private UnitedKingdomCountryProfile $unitedKingdomProfile,
        private UnitedStatesCountryProfile $unitedStatesProfile,
    ) {}

    public function current(CountryCode $countryCode): CountryProfileDefinition
    {
        $profile = match ($countryCode->value) {
            CountryCode::INDIA => $this->indiaProfile,
            CountryCode::UNITED_KINGDOM => $this->unitedKingdomProfile,
            CountryCode::UNITED_STATES => $this->unitedStatesProfile,
            default => $this->coreProfile,
        };

        return $this->definition($countryCode, $profile);
    }

    public function pinned(
        CountryCode $countryCode,
        string $profileCode,
        string $version,
    ): CountryProfileDefinition {
        $profile = match ($profileCode) {
            CoreCountryProfile::CODE => $this->coreProfile,
            IndiaCountryProfile::CODE => $this->indiaProfile,
            UnitedKingdomCountryProfile::CODE => $this->unitedKingdomProfile,
            UnitedStatesCountryProfile::CODE => $this->unitedStatesProfile,
            default => null,
        };

        if (! $profile instanceof CountryHealthcareProfile) {
            throw new DomainException('The organization country profile is not available.');
        }

        $usesCoreProfile = $profile->code() === CoreCountryProfile::CODE;
        $matchesCountry = $usesCoreProfile || $profile->code() === $countryCode->value;
        $matchesVersion = $profile->version() === $version;
        if (! $matchesCountry || ! $matchesVersion) {
            throw new DomainException('The organization country profile is not available at its pinned version.');
        }

        return $this->definition($countryCode, $profile);
    }

    private function definition(
        CountryCode $countryCode,
        CountryHealthcareProfile $profile,
    ): CountryProfileDefinition {
        return new CountryProfileDefinition(
            countryCode: $countryCode->value,
            profileCode: $profile->code(),
            version: $profile->version(),
            defaults: $profile->defaults(),
        );
    }
}
