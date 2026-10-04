<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Organization\OrganizationQueryService;
use App\Domains\Organization\Data\OrganizationData;

final readonly class OrganizationQuery
{
    public function __construct(private OrganizationQueryService $organizations) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{id: string} $args
     */
    public function __invoke(mixed $root, array $args): OrganizationData
    {
        return $this->organizations->organization($args['id']);
    }
}
