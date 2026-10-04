<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Organization\OrganizationQueryService;
use App\Domains\Organization\Data\ResolvedOperationalSettingsData;

final readonly class ResolvedOperationalSettingsQuery
{
    public function __construct(private OrganizationQueryService $organizations) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, facilityId?: string|null, departmentId?: string|null}} $args
     */
    public function __invoke(mixed $root, array $args): ResolvedOperationalSettingsData
    {
        $input = $args['input'];

        return $this->organizations->resolvedOperationalSettings(
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'] ?? null,
            departmentId: $input['departmentId'] ?? null,
        );
    }
}
