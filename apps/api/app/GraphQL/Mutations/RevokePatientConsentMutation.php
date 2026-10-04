<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Application\Privacy\PatientConsentService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Privacy\Data\PatientConsentData;

final readonly class RevokePatientConsentMutation
{
    public function __construct(
        private PatientQueryService $patients,
        private PatientConsentService $consents,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, patientId: string, consentId: string, reason: string}} $args */
    public function __invoke(mixed $root, array $args): PatientConsentData
    {
        $input = $args['input'];
        $patient = $this->patients->patient($input['organizationId'], $input['patientId']);

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::MANAGE_CONSENTS,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
        );

        return $this->consents->revoke(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            patientId: $patient->id,
            consentId: $input['consentId'],
            reason: $input['reason'],
            facilityId: $patient->registrationFacilityId,
        );
    }
}
