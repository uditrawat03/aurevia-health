<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Organization\OrganizationService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Organization\Data\CreateDepartmentData;
use App\Domains\Organization\Data\DepartmentData;
use App\GraphQL\Inputs\OperationalSettingsInputMapper;

final readonly class CreateDepartmentMutation
{
    public function __construct(
        private OrganizationService $organizations,
        private OperationalSettingsInputMapper $settingsMapper,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, facilityId: string, name: string, code: string, settings?: array<string, mixed>|null}} $args
     */
    public function __invoke(mixed $root, array $args): DepartmentData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_ORGANIZATION,
            facilityId: $input['facilityId'],
        );

        return $this->organizations->createDepartment(new CreateDepartmentData(
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            name: $input['name'],
            code: $input['code'],
            settings: $this->settingsMapper->map($input['settings'] ?? null),
        ));
    }
}
