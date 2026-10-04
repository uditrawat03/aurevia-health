<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\CountryProfiles\CountryProfileResolver;
use App\Domains\Organization\Data\CreateDepartmentData;
use App\Domains\Organization\Data\CreateFacilityData;
use App\Domains\Organization\Data\CreateHealthSystemData;
use App\Domains\Organization\Data\CreateOrganizationData;
use App\Domains\Organization\Data\DepartmentData;
use App\Domains\Organization\Data\FacilityData;
use App\Domains\Organization\Data\HealthSystemData;
use App\Domains\Organization\Data\OrganizationData;
use App\Domains\Organization\Repositories\OrganizationRepo;
use DomainException;

final readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepo $organizations,
        private CountryProfileResolver $countryProfiles,
    ) {}

    public function createOrganization(CreateOrganizationData $data): OrganizationData
    {
        $profile = $this->countryProfiles->current($data->countryCode);

        return $this->organizations->createOrganization($data, $profile);
    }

    public function createHealthSystem(CreateHealthSystemData $data): HealthSystemData
    {
        $organization = $this->organizations->findOrganization($data->organizationId);
        if ($organization === null) {
            throw new DomainException('Organization was not found.');
        }

        return $this->organizations->createHealthSystem($data);
    }

    public function createFacility(CreateFacilityData $data): FacilityData
    {
        $organization = $this->organizations->findOrganization($data->organizationId);
        if ($organization === null) {
            throw new DomainException('Organization was not found.');
        }

        $hasHealthSystem = $data->healthSystemId !== null;
        if ($hasHealthSystem) {
            $healthSystem = $this->organizations->findHealthSystem(
                $data->organizationId,
                $data->healthSystemId,
            );

            if ($healthSystem === null) {
                throw new DomainException('Health system does not belong to the organization.');
            }
        }

        return $this->organizations->createFacility($data);
    }

    public function createDepartment(CreateDepartmentData $data): DepartmentData
    {
        $organization = $this->organizations->findOrganization($data->organizationId);
        if ($organization === null) {
            throw new DomainException('Organization was not found.');
        }

        $facility = $this->organizations->findFacility($data->organizationId, $data->facilityId);
        if ($facility === null) {
            throw new DomainException('Facility does not belong to the organization.');
        }

        return $this->organizations->createDepartment($data);
    }
}
