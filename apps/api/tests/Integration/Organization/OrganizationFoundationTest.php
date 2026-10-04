<?php

declare(strict_types=1);

namespace Tests\Integration\Organization;

use App\Application\Organization\OrganizationConfigurationService;
use App\Application\Organization\OrganizationQueryService;
use App\Application\Organization\OrganizationService;
use App\CountryProfiles\IndiaCountryProfile;
use App\Domains\Organization\Data\CreateDepartmentData;
use App\Domains\Organization\Data\CreateFacilityData;
use App\Domains\Organization\Data\CreateHealthSystemData;
use App\Domains\Organization\Data\CreateOrganizationData;
use App\Domains\Organization\Data\OrganizationData;
use App\Domains\Organization\Enums\ConfigurationScope;
use App\Domains\Organization\Enums\ConfigurationSource;
use App\Domains\Organization\Enums\WeekStart;
use App\Domains\Organization\ValueObjects\CountryCode;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;
use DomainException;
use Tests\IntegrationTestCase;

final class OrganizationFoundationTest extends IntegrationTestCase
{
    private OrganizationService $organizations;
    private OrganizationQueryService $queries;
    private OrganizationConfigurationService $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizations = $this->app->make(OrganizationService::class);
        $this->queries = $this->app->make(OrganizationQueryService::class);
        $this->configuration = $this->app->make(OrganizationConfigurationService::class);
    }

    public function test_organization_owns_health_systems_facilities_and_departments(): void
    {
        $organization = $this->createOrganization('Aurevia North', 'aurevia-north', 'IN');
        $healthSystem = $this->organizations->createHealthSystem(new CreateHealthSystemData(
            organizationId: $organization->id,
            name: 'North Health System',
            code: 'NHSYS',
        ));

        $facilityOne = $this->organizations->createFacility(new CreateFacilityData(
            organizationId: $organization->id,
            healthSystemId: $healthSystem->id,
            name: 'Central Hospital',
            code: 'CENTRAL',
            settings: $this->emptySettings(),
        ));
        $this->organizations->createFacility(new CreateFacilityData(
            organizationId: $organization->id,
            healthSystemId: null,
            name: 'West Clinic',
            code: 'WEST',
            settings: $this->emptySettings(),
        ));
        $this->organizations->createDepartment(new CreateDepartmentData(
            organizationId: $organization->id,
            facilityId: $facilityOne->id,
            name: 'Cardiology',
            code: 'CARD',
            settings: $this->emptySettings(),
        ));

        $hierarchy = $this->queries->organization($organization->id);

        self::assertCount(1, $hierarchy->healthSystems);
        self::assertCount(2, $hierarchy->facilities);

        $centralFacility = null;
        foreach ($hierarchy->facilities as $facility) {
            if ($facility->id === $facilityOne->id) {
                $centralFacility = $facility;
                break;
            }
        }

        self::assertNotNull($centralFacility);
        self::assertSame($organization->id, $centralFacility->organizationId);
        self::assertCount(1, $centralFacility->departments);
        self::assertSame($facilityOne->id, $centralFacility->departments[0]->facilityId);
    }

    public function test_health_system_from_another_organization_cannot_own_a_facility(): void
    {
        $organizationA = $this->createOrganization('Organization A', 'organization-a', 'IN');
        $organizationB = $this->createOrganization('Organization B', 'organization-b', 'GB');
        $healthSystemB = $this->organizations->createHealthSystem(new CreateHealthSystemData(
            organizationId: $organizationB->id,
            name: 'System B',
            code: 'SYS-B',
        ));

        $this->expectException(DomainException::class);

        $this->organizations->createFacility(new CreateFacilityData(
            organizationId: $organizationA->id,
            healthSystemId: $healthSystemB->id,
            name: 'Wrong Tenant Facility',
            code: 'WRONG',
            settings: $this->emptySettings(),
        ));
    }

    public function test_configuration_is_resolved_from_global_country_organization_facility_and_department_layers(): void
    {
        $organization = $this->organizations->createOrganization(new CreateOrganizationData(
            name: 'Configuration Test',
            slug: 'configuration-test',
            countryCode: CountryCode::fromString('IN'),
            settings: new OperationalSettingsOverride('hi-IN', null, null),
        ));
        $facility = $this->organizations->createFacility(new CreateFacilityData(
            organizationId: $organization->id,
            healthSystemId: null,
            name: 'Configuration Hospital',
            code: 'CFG-HOSP',
            settings: new OperationalSettingsOverride(null, 'Asia/Dubai', null),
        ));
        $department = $this->organizations->createDepartment(new CreateDepartmentData(
            organizationId: $organization->id,
            facilityId: $facility->id,
            name: 'Configuration Department',
            code: 'CFG-DEPT',
            settings: new OperationalSettingsOverride(null, null, WeekStart::SUNDAY->value),
        ));

        $resolved = $this->queries->resolvedOperationalSettings(
            $organization->id,
            $facility->id,
            $department->id,
        );

        self::assertSame('hi-IN', $resolved->locale);
        self::assertSame(ConfigurationSource::ORGANIZATION->value, $resolved->localeSource);
        self::assertSame('Asia/Dubai', $resolved->timezone);
        self::assertSame(ConfigurationSource::FACILITY->value, $resolved->timezoneSource);
        self::assertSame(WeekStart::SUNDAY->value, $resolved->weekStartsOn);
        self::assertSame(ConfigurationSource::DEPARTMENT->value, $resolved->weekStartsOnSource);
        self::assertSame(IndiaCountryProfile::CODE, $resolved->countryProfile->profileCode);
        self::assertSame(IndiaCountryProfile::VERSION, $resolved->countryProfile->version);
    }

    public function test_configuration_change_is_persisted_with_correlation_id(): void
    {
        $organization = $this->createOrganization('Audit Test', 'audit-test', 'US');
        $correlationId = 'test-organization-configuration-change';

        $updated = $this->configuration->replaceOperationalSettings(
            organizationId: $organization->id,
            scope: ConfigurationScope::ORGANIZATION,
            scopeId: $organization->id,
            settings: new OperationalSettingsOverride('es-US', 'America/New_York', WeekStart::MONDAY->value),
            correlationId: $correlationId,
        );

        self::assertSame('es-US', $updated->settings->locale);
        self::assertSame('America/New_York', $updated->settings->timezone);
        self::assertSame(WeekStart::MONDAY->value, $updated->settings->weekStartsOn);

        $this->assertDatabaseHas('configuration_changes', [
            'organization_id' => $organization->id,
            'scope_type' => ConfigurationScope::ORGANIZATION->value,
            'setting_key' => 'locale',
            'new_value' => 'es-US',
            'correlation_id' => $correlationId,
        ]);
        $this->assertDatabaseHas('configuration_changes', [
            'organization_id' => $organization->id,
            'scope_type' => ConfigurationScope::ORGANIZATION->value,
            'setting_key' => 'timezone',
            'new_value' => 'America/New_York',
            'correlation_id' => $correlationId,
        ]);
    }

    private function createOrganization(string $name, string $slug, string $countryCode): OrganizationData
    {
        return $this->organizations->createOrganization(new CreateOrganizationData(
            name: $name,
            slug: $slug,
            countryCode: CountryCode::fromString($countryCode),
            settings: $this->emptySettings(),
        ));
    }

    private function emptySettings(): OperationalSettingsOverride
    {
        return new OperationalSettingsOverride(null, null, null);
    }
}
