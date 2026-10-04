<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Clinical\ClinicalRecordService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Clinical\Data\ProblemData;
use App\Domains\Clinical\Enums\ProblemStatus;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class RecordProblemMutation
{
    public function __construct(
        private ClinicalRecordService $clinicalRecords,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, encounterId: string, codeSystem?: string|null, code?: string|null, display: string, status: string, onsetDate?: string|null}} $args */
    public function __invoke(mixed $root, array $args): ProblemData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_CLINICAL_RECORD,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->clinicalRecords->recordProblem(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            encounterId: $input['encounterId'],
            codeSystem: $input['codeSystem'] ?? null,
            code: $input['code'] ?? null,
            display: $input['display'],
            status: ProblemStatus::from($input['status']),
            onsetDate: $input['onsetDate'] ?? null,
        );
    }
}
