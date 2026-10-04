<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Clinical\ClinicalRecordService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Clinical\Data\ClinicalNoteData;
use App\Domains\Clinical\Enums\ClinicalNoteAmendmentType;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class AddClinicalNoteAmendmentMutation
{
    public function __construct(
        private ClinicalRecordService $clinicalRecords,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, facilityId: string, patientId: string, encounterId: string, noteId: string, type: string, body: string, reason?: string|null}} $args */
    public function __invoke(mixed $root, array $args): ClinicalNoteData
    {
        $input = $args['input'];
        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_CLINICAL_RECORD,
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
        );

        return $this->clinicalRecords->addNoteAmendment(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            facilityId: $input['facilityId'],
            patientId: $input['patientId'],
            encounterId: $input['encounterId'],
            noteId: $input['noteId'],
            type: ClinicalNoteAmendmentType::from($input['type']),
            body: $input['body'],
            reason: $input['reason'] ?? null,
        );
    }
}
