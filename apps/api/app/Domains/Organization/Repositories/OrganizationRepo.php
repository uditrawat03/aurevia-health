<?php

declare(strict_types=1);

namespace App\Domains\Organization\Repositories;

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
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

interface OrganizationRepo
{
    public function createOrganization(
        CreateOrganizationData $data,
        CountryProfileDefinition $profile,
    ): OrganizationData;

    public function createHealthSystem(CreateHealthSystemData $data): HealthSystemData;

    public function createFacility(CreateFacilityData $data): FacilityData;

    public function createDepartment(CreateDepartmentData $data): DepartmentData;

    public function findOrganization(string $organizationId): ?OrganizationData;

    public function findHealthSystem(string $organizationId, string $healthSystemId): ?HealthSystemData;

    public function findFacility(string $organizationId, string $facilityId): ?FacilityData;

    public function findDepartment(
        string $organizationId,
        string $facilityId,
        string $departmentId,
    ): ?DepartmentData;

    public function hierarchy(string $organizationId): ?OrganizationData;

    public function findScopedSettings(
        string $organizationId,
        ConfigurationScope $scope,
        string $scopeId,
    ): ?ScopedSettingsData;

    public function replaceSettingsAndAudit(
        ScopedSettingsData $current,
        OperationalSettingsOverride $settings,
        ConfigurationChangeSet $changes,
        string $correlationId,
    ): ScopedSettingsData;
}
