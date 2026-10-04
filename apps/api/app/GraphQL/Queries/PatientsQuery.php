<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Patient\Data\PatientSearchResultData;

final readonly class PatientsQuery
{
    public function __construct(
        private PatientQueryService $patients,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId?: string|null, query: string, limit?: int}} $args */
    public function __invoke(mixed $root, array $args): PatientSearchResultData
    {
        $input = $args['input'];
        $facilityId = $input['facilityId'] ?? null;

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_PATIENTS,
            facilityId: $facilityId,
            requiresAllFacilities: $facilityId === null,
        );

        return $this->patients->search(
            organizationId: $input['organizationId'],
            facilityId: $facilityId,
            query: $input['query'],
            limit: $input['limit'] ?? PatientQueryService::DEFAULT_SEARCH_LIMIT,
        );
    }
}
