<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Clinical\ClinicalRecordService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Clinical\Data\AllergyData;
use App\Domains\Clinical\Enums\AllergySeverity;
use App\Domains\Clinical\Enums\AllergyStatus;
use App\Domains\Clinical\Enums\AllergyVerificationStatus;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class RecordAllergyMutation
{
    public function __construct(
        private ClinicalRecordService $clinicalRecords,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, encounterId: string, codeSystem?: string|null, code?: string|null, substance: string, reaction?: string|null, severity: string, status: string, verificationStatus: string}} $args */
    public function __invoke(mixed $root, array $args): AllergyData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_CLINICAL_RECORD,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->clinicalRecords->recordAllergy(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            encounterId: $input['encounterId'],
            codeSystem: $input['codeSystem'] ?? null,
            code: $input['code'] ?? null,
            substance: $input['substance'],
            reaction: $input['reaction'] ?? null,
            severity: AllergySeverity::from($input['severity']),
            status: AllergyStatus::from($input['status']),
            verificationStatus: AllergyVerificationStatus::from($input['verificationStatus']),
        );
    }
}
