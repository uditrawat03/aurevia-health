<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Audit\AuditService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Audit\Data\AuditEventData;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class AuditEventsQuery
{
    public function __construct(
        private OrganizationAuthorizationService $authorization,
        private AuditService $audit,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, limit?: int|null}} $args
     * @return list<AuditEventData>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $input = $args['input'];
        $organizationId = $input['organizationId'];
        $limit = $input['limit'] ?? AuditService::DEFAULT_VIEWER_LIMIT;

        $this->authorization->authorize(
            organizationId: $organizationId,
            permission: OrganizationPermission::VIEW_AUDIT,
            requiresAllFacilities: true,
        );

        return $this->audit->organizationEvents($organizationId, $limit)->events;
    }
}
