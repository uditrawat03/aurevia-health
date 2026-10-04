<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Organization;

use App\CountryProfiles\CountryProfileDefinition;
use App\Domains\Organization\Data\ConfigurationChangeSet;
use App\Domains\Organization\Data\CreateDepartmentData;
use App\Domains\Organization\Data\CreateFacilityData;
use App\Domains\Organization\Data\CreateHealthSystemData;
use App\Domains\Organization\Data\CreateOrganizationData;
use App\Domains\Organization\Data\DepartmentData;
use App\Domains\Organization\Data\FacilityData;
use App\Domains\Organization\Data\HealthSystemData;
use App\Domains\Organization\Data\OrganizationData;
use App\Domains\Organization\Data\ScopedSettingsData;
use App\Domains\Organization\Enums\ConfigurationScope;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;
use App\Models\ConfigurationChange;
use App\Models\Department;
use App\Models\Facility;
use App\Models\HealthSystem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class EloquentOrganizationRepo implements OrganizationRepo
{
    public function createOrganization(
        CreateOrganizationData $data,
        CountryProfileDefinition $profile,
    ): OrganizationData {
        $organization = Organization::query()->create([
            'name' => $data->name,
            'slug' => $data->slug,
            'country_code' => $data->countryCode->value,
            'country_profile_code' => $profile->profileCode,
            'country_profile_version' => $profile->version,
            'locale_override' => $data->settings->locale,
            'timezone_override' => $data->settings->timezone,
            'week_starts_on_override' => $data->settings->weekStartsOn,
        ]);

        return $this->mapOrganization($organization);
    }

    public function createHealthSystem(CreateHealthSystemData $data): HealthSystemData
    {
        $healthSystem = HealthSystem::query()->create([
            'organization_id' => $data->organizationId,
            'name' => $data->name,
            'code' => $data->code,
        ]);

        return $this->mapHealthSystem($healthSystem);
    }

    public function createFacility(CreateFacilityData $data): FacilityData
    {
        $facility = Facility::query()->create([
            'organization_id' => $data->organizationId,
            'health_system_id' => $data->healthSystemId,
            'name' => $data->name,
            'code' => $data->code,
            'locale_override' => $data->settings->locale,
            'timezone_override' => $data->settings->timezone,
            'week_starts_on_override' => $data->settings->weekStartsOn,
        ]);

        return $this->mapFacility($facility);
    }

    public function createDepartment(CreateDepartmentData $data): DepartmentData
    {
        $department = Department::query()->create([
            'organization_id' => $data->organizationId,
            'facility_id' => $data->facilityId,
            'name' => $data->name,
            'code' => $data->code,
            'locale_override' => $data->settings->locale,
            'timezone_override' => $data->settings->timezone,
            'week_starts_on_override' => $data->settings->weekStartsOn,
        ]);

        return $this->mapDepartment($department);
    }

    public function findOrganization(string $organizationId): ?OrganizationData
    {
        $organization = Organization::query()->find($organizationId);

        return $organization instanceof Organization
            ? $this->mapOrganization($organization)
            : null;
    }

    public function findHealthSystem(string $organizationId, string $healthSystemId): ?HealthSystemData
    {
        $healthSystem = HealthSystem::query()
            ->where('organization_id', $organizationId)
            ->find($healthSystemId);

        return $healthSystem instanceof HealthSystem
            ? $this->mapHealthSystem($healthSystem)
            : null;
    }

    public function findFacility(string $organizationId, string $facilityId): ?FacilityData
    {
        $facility = Facility::query()
            ->where('organization_id', $organizationId)
            ->find($facilityId);

        return $facility instanceof Facility
            ? $this->mapFacility($facility)
            : null;
    }

    public function findDepartment(
        string $organizationId,
        string $facilityId,
        string $departmentId,
    ): ?DepartmentData {
        $department = Department::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->find($departmentId);

        return $department instanceof Department
            ? $this->mapDepartment($department)
            : null;
    }

    public function hierarchy(string $organizationId): ?OrganizationData
    {
        $organization = Organization::query()
            ->with(['healthSystems', 'facilities.departments'])
            ->find($organizationId);

        if (! $organization instanceof Organization) {
            return null;
        }

        $healthSystems = [];
        foreach ($organization->healthSystems as $healthSystem) {
            $healthSystems[] = $this->mapHealthSystem($healthSystem);
        }

        $facilities = [];
        foreach ($organization->facilities as $facility) {
            $departments = [];
            foreach ($facility->departments as $department) {
                $departments[] = $this->mapDepartment($department);
            }

            $facilities[] = $this->mapFacility($facility, $departments);
        }

        return $this->mapOrganization($organization, $healthSystems, $facilities);
    }

    public function findScopedSettings(
        string $organizationId,
        ConfigurationScope $scope,
        string $scopeId,
    ): ?ScopedSettingsData {
        $model = $this->findScopedModel($organizationId, $scope, $scopeId);

        if (! $model instanceof Model) {
            return null;
        }

        return new ScopedSettingsData(
            organizationId: $organizationId,
            scope: $scope,
            scopeId: $scopeId,
            settings: $this->settingsFromModel($model),
        );
    }

    public function replaceSettingsAndAudit(
        ScopedSettingsData $current,
        OperationalSettingsOverride $settings,
        ConfigurationChangeSet $changes,
        string $correlationId,
    ): ScopedSettingsData {
        return DB::transaction(function () use ($current, $settings, $changes, $correlationId): ScopedSettingsData {
            $model = $this->findScopedModel(
                $current->organizationId,
                $current->scope,
                $current->scopeId,
            );

            if (! $model instanceof Model) {
                throw new RuntimeException('Configuration scope disappeared during update.');
            }

            $model->forceFill([
                'locale_override' => $settings->locale,
                'timezone_override' => $settings->timezone,
                'week_starts_on_override' => $settings->weekStartsOn,
            ])->save();

            foreach ($changes->entries as $change) {
                ConfigurationChange::query()->create([
                    'organization_id' => $current->organizationId,
                    'scope_type' => $current->scope->value,
                    'scope_id' => $current->scopeId,
                    'setting_key' => $change->key->value,
                    'previous_value' => $change->previousValue,
                    'new_value' => $change->newValue,
                    'correlation_id' => $correlationId,
                    'changed_at' => now(),
                ]);
            }

            return new ScopedSettingsData(
                organizationId: $current->organizationId,
                scope: $current->scope,
                scopeId: $current->scopeId,
                settings: $settings,
            );
        });
    }

    private function findScopedModel(
        string $organizationId,
        ConfigurationScope $scope,
        string $scopeId,
    ): ?Model {
        return match ($scope) {
            ConfigurationScope::ORGANIZATION => $organizationId === $scopeId
                ? Organization::query()->find($scopeId)
                : null,
            ConfigurationScope::FACILITY => Facility::query()
                ->where('organization_id', $organizationId)
                ->find($scopeId),
            ConfigurationScope::DEPARTMENT => Department::query()
                ->where('organization_id', $organizationId)
                ->find($scopeId),
        };
    }

    /**
     * @param list<HealthSystemData> $healthSystems
     * @param list<FacilityData> $facilities
     */
    private function mapOrganization(
        Organization $organization,
        array $healthSystems = [],
        array $facilities = [],
    ): OrganizationData {
        return new OrganizationData(
            id: (string) $organization->getKey(),
            name: (string) $organization->getAttribute('name'),
            slug: (string) $organization->getAttribute('slug'),
            countryCode: (string) $organization->getAttribute('country_code'),
            countryProfileCode: (string) $organization->getAttribute('country_profile_code'),
            countryProfileVersion: (string) $organization->getAttribute('country_profile_version'),
            settings: $this->settingsFromModel($organization),
            healthSystems: $healthSystems,
            facilities: $facilities,
        );
    }

    private function mapHealthSystem(HealthSystem $healthSystem): HealthSystemData
    {
        return new HealthSystemData(
            id: (string) $healthSystem->getKey(),
            organizationId: (string) $healthSystem->getAttribute('organization_id'),
            name: (string) $healthSystem->getAttribute('name'),
            code: (string) $healthSystem->getAttribute('code'),
        );
    }

    /** @param list<DepartmentData> $departments */
    private function mapFacility(Facility $facility, array $departments = []): FacilityData
    {
        $healthSystemId = $facility->getAttribute('health_system_id');

        return new FacilityData(
            id: (string) $facility->getKey(),
            organizationId: (string) $facility->getAttribute('organization_id'),
            healthSystemId: is_string($healthSystemId) ? $healthSystemId : null,
            name: (string) $facility->getAttribute('name'),
            code: (string) $facility->getAttribute('code'),
            settings: $this->settingsFromModel($facility),
            departments: $departments,
        );
    }

    private function mapDepartment(Department $department): DepartmentData
    {
        return new DepartmentData(
            id: (string) $department->getKey(),
            organizationId: (string) $department->getAttribute('organization_id'),
            facilityId: (string) $department->getAttribute('facility_id'),
            name: (string) $department->getAttribute('name'),
            code: (string) $department->getAttribute('code'),
            settings: $this->settingsFromModel($department),
        );
    }

    private function settingsFromModel(Model $model): OperationalSettingsOverride
    {
        $locale = $model->getAttribute('locale_override');
        $timezone = $model->getAttribute('timezone_override');
        $weekStartsOn = $model->getAttribute('week_starts_on_override');

        return new OperationalSettingsOverride(
            locale: is_string($locale) ? $locale : null,
            timezone: is_string($timezone) ? $timezone : null,
            weekStartsOn: is_string($weekStartsOn) ? $weekStartsOn : null,
        );
    }
}
