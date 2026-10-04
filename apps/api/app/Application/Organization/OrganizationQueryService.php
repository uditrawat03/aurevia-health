<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\CountryProfiles\CountryProfileResolver;
use App\Domains\Organization\Data\CountryProfileMetadataData;
use App\Domains\Organization\Data\OrganizationData;
use App\Domains\Organization\Data\ResolvedOperationalSettingsData;
use App\Domains\Organization\Enums\ConfigurationSource;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Organization\ValueObjects\CountryCode;
use App\Domains\Organization\ValueObjects\OperationalSettings;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;
use DomainException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LogicException;

final readonly class OrganizationQueryService
{
    public function __construct(
        private OrganizationRepo $organizations,
        private CountryProfileResolver $countryProfiles,
        private ConfigRepository $config,
    ) {}

    public function organization(string $organizationId): OrganizationData
    {
        $organization = $this->organizations->hierarchy($organizationId);

        if ($organization === null) {
            throw new DomainException('Organization was not found.');
        }

        return $organization;
    }

    public function resolvedOperationalSettings(
        string $organizationId,
        ?string $facilityId,
        ?string $departmentId,
    ): ResolvedOperationalSettingsData {
        $organization = $this->organizations->findOrganization($organizationId);
        if ($organization === null) {
            throw new DomainException('Organization was not found.');
        }

        $profile = $this->countryProfiles->pinned(
            CountryCode::fromString($organization->countryCode),
            $organization->countryProfileCode,
            $organization->countryProfileVersion,
        );

        $metadata = new CountryProfileMetadataData(
            countryCode: $profile->countryCode,
            profileCode: $profile->profileCode,
            version: $profile->version,
        );

        $resolved = $this->resolvedFromGlobal($this->globalDefaults(), $metadata);
        $resolved = $this->applyOverride(
            $resolved,
            $profile->defaults,
            ConfigurationSource::COUNTRY_PROFILE,
        );
        $resolved = $this->applyOverride(
            $resolved,
            $organization->settings,
            ConfigurationSource::ORGANIZATION,
        );

        if ($facilityId !== null) {
            $facility = $this->organizations->findFacility($organizationId, $facilityId);
            if ($facility === null) {
                throw new DomainException('Facility does not belong to the organization.');
            }

            $resolved = $this->applyOverride(
                $resolved,
                $facility->settings,
                ConfigurationSource::FACILITY,
            );
        }

        if ($departmentId !== null) {
            if ($facilityId === null) {
                throw new DomainException('Facility is required when resolving department settings.');
            }

            $department = $this->organizations->findDepartment(
                $organizationId,
                $facilityId,
                $departmentId,
            );
            if ($department === null) {
                throw new DomainException('Department does not belong to the selected facility and organization.');
            }

            $resolved = $this->applyOverride(
                $resolved,
                $department->settings,
                ConfigurationSource::DEPARTMENT,
            );
        }

        return $resolved;
    }

    private function globalDefaults(): OperationalSettings
    {
        $locale = $this->config->get('organization.defaults.locale');
        $timezone = $this->config->get('organization.defaults.timezone');
        $weekStartsOn = $this->config->get('organization.defaults.week_starts_on');

        $hasInvalidConfiguration = ! is_string($locale)
            || ! is_string($timezone)
            || ! is_string($weekStartsOn);
        if ($hasInvalidConfiguration) {
            throw new LogicException('Global organization settings are not configured correctly.');
        }

        return new OperationalSettings($locale, $timezone, $weekStartsOn);
    }

    private function resolvedFromGlobal(
        OperationalSettings $settings,
        CountryProfileMetadataData $countryProfile,
    ): ResolvedOperationalSettingsData {
        return new ResolvedOperationalSettingsData(
            locale: $settings->locale,
            timezone: $settings->timezone,
            weekStartsOn: $settings->weekStartsOn,
            localeSource: ConfigurationSource::GLOBAL_DEFAULT->value,
            timezoneSource: ConfigurationSource::GLOBAL_DEFAULT->value,
            weekStartsOnSource: ConfigurationSource::GLOBAL_DEFAULT->value,
            countryProfile: $countryProfile,
        );
    }

    private function applyOverride(
        ResolvedOperationalSettingsData $current,
        OperationalSettingsOverride $override,
        ConfigurationSource $source,
    ): ResolvedOperationalSettingsData {
        return new ResolvedOperationalSettingsData(
            locale: $override->locale ?? $current->locale,
            timezone: $override->timezone ?? $current->timezone,
            weekStartsOn: $override->weekStartsOn ?? $current->weekStartsOn,
            localeSource: $override->locale !== null ? $source->value : $current->localeSource,
            timezoneSource: $override->timezone !== null ? $source->value : $current->timezoneSource,
            weekStartsOnSource: $override->weekStartsOn !== null ? $source->value : $current->weekStartsOnSource,
            countryProfile: $current->countryProfile,
        );
    }
}
