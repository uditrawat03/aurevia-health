<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Application\Privacy\PatientConsentService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Privacy\Data\PatientConsentData;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use DomainException;

final readonly class GrantPatientConsentMutation
{
    public function __construct(
        private PatientQueryService $patients,
        private PatientConsentService $consents,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array<string, mixed>} $args */
    public function __invoke(mixed $root, array $args): PatientConsentData
    {
        $input = $args['input'];
        $organizationId = (string) $input['organizationId'];
        $patientId = (string) $input['patientId'];
        $patient = $this->patients->patient($organizationId, $patientId);

        $facilityId = isset($input['facilityId']) && is_string($input['facilityId'])
            ? $input['facilityId']
            : null;
        if ($facilityId !== null && $facilityId !== $patient->registrationFacilityId) {
            throw new DomainException('Consent facility must match the patient registration facility in this foundation.');
        }

        $this->authorization->authorize(
            organizationId: $organizationId,
            permission: OrganizationPermission::MANAGE_CONSENTS,
            facilityId: $patient->registrationFacilityId,
            requiresAllFacilities: $facilityId === null,
            patientId: $patientId,
        );

        return $this->consents->grant(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $organizationId,
            patientId: $patientId,
            facilityId: $facilityId,
            dataCategory: ConsentDataCategory::from((string) $input['dataCategory']),
            purpose: ConsentPurpose::from((string) $input['purpose']),
            recipientClass: ConsentRecipientClass::from((string) $input['recipientClass']),
            effectiveFrom: isset($input['effectiveFrom']) && is_string($input['effectiveFrom'])
                ? $input['effectiveFrom']
                : null,
            effectiveUntil: isset($input['effectiveUntil']) && is_string($input['effectiveUntil'])
                ? $input['effectiveUntil']
                : null,
        );
    }
}
