<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Organization\OrganizationService;
use App\Domains\Organization\Data\CreateOrganizationData;
use App\Domains\Organization\Data\OrganizationData;
use App\Domains\Organization\ValueObjects\CountryCode;
use App\GraphQL\Inputs\OperationalSettingsInputMapper;

final readonly class CreateOrganizationMutation
{
    public function __construct(
        private OrganizationService $organizations,
        private OperationalSettingsInputMapper $settingsMapper,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{name: string, slug: string, countryCode: string, settings?: array<string, mixed>|null}} $args
     */
    public function __invoke(mixed $root, array $args): OrganizationData
    {
        $input = $args['input'];

        return $this->organizations->createOrganization(new CreateOrganizationData(
            name: $input['name'],
            slug: $input['slug'],
            countryCode: CountryCode::fromString($input['countryCode']),
            settings: $this->settingsMapper->map($input['settings'] ?? null),
        ));
    }
}
