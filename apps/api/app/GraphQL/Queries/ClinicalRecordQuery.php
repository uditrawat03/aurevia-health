<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Clinical\ClinicalRecordService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Clinical\Data\ClinicalRecordData;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class ClinicalRecordQuery
{
    public function __construct(
        private ClinicalRecordService $clinicalRecords,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string}} $args */
    public function __invoke(mixed $root, array $args): ClinicalRecordData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_CLINICAL_RECORD,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->clinicalRecords->forPatient(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );
    }
}
