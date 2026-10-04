<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Clinical\ClinicalRecordService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Clinical\Data\ObservationData;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class RecordObservationMutation
{
    public function __construct(
        private ClinicalRecordService $clinicalRecords,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, encounterId: string, codeSystem?: string|null, code: string, display: string, valueNumeric?: float|null, valueText?: string|null, unit?: string|null, effectiveAt?: string|null}} $args */
    public function __invoke(mixed $root, array $args): ObservationData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_CLINICAL_RECORD,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->clinicalRecords->recordObservation(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            encounterId: $input['encounterId'],
            codeSystem: $input['codeSystem'] ?? null,
            code: $input['code'],
            display: $input['display'],
            valueNumeric: isset($input['valueNumeric']) ? (float) $input['valueNumeric'] : null,
            valueText: $input['valueText'] ?? null,
            unit: $input['unit'] ?? null,
            effectiveAt: $input['effectiveAt'] ?? null,
        );
    }
}
