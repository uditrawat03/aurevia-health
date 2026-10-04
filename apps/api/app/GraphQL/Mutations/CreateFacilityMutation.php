<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Organization\OrganizationService;
use App\Domains\Organization\Data\CreateFacilityData;
use App\Domains\Organization\Data\FacilityData;
use App\GraphQL\Inputs\OperationalSettingsInputMapper;

final readonly class CreateFacilityMutation
{
    public function __construct(
        private OrganizationService $organizations,
        private OperationalSettingsInputMapper $settingsMapper,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, healthSystemId?: string|null, name: string, code: string, settings?: array<string, mixed>|null}} $args
     */
    public function __invoke(mixed $root, array $args): FacilityData
    {
        $input = $args['input'];

        return $this->organizations->createFacility(new CreateFacilityData(
            organizationId: $input['organizationId'],
            healthSystemId: $input['healthSystemId'] ?? null,
            name: $input['name'],
            code: $input['code'],
            settings: $this->settingsMapper->map($input['settings'] ?? null),
        ));
    }
}
