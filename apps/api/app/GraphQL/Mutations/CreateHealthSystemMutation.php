<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Organization\OrganizationService;
use App\Domains\Organization\Data\CreateHealthSystemData;
use App\Domains\Organization\Data\HealthSystemData;

final readonly class CreateHealthSystemMutation
{
    public function __construct(private OrganizationService $organizations) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, name: string, code: string}} $args
     */
    public function __invoke(mixed $root, array $args): HealthSystemData
    {
        $input = $args['input'];

        return $this->organizations->createHealthSystem(new CreateHealthSystemData(
            organizationId: $input['organizationId'],
            name: $input['name'],
            code: $input['code'],
        ));
    }
}
