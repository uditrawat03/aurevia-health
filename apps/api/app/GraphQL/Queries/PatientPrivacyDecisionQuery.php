<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Application\Privacy\PrivacyAuthorizationService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Privacy\Data\PrivacyDecisionData;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;

final readonly class PatientPrivacyDecisionQuery
{
    public function __construct(
        private PatientQueryService $patients,
        private PrivacyAuthorizationService $privacy,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /**
     * @param array{input: array{
     *     organizationId: string,
     *     patientId: string,
     *     dataCategory: string,
     *     purpose: string,
     *     recipientClass: string
     * }} $args
     */
    public function __invoke(mixed $root, array $args): PrivacyDecisionData
    {
        $input = $args['input'];
        $patient = $this->patients->patient($input['organizationId'], $input['patientId']);

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_CONSENTS,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
        );

        return $this->privacy->decision(
            actorUserId: $this->authorization->authenticatedUserId(),
            patient: $patient,
            dataCategory: ConsentDataCategory::from($input['dataCategory']),
            purpose: ConsentPurpose::from($input['purpose']),
            recipientClass: ConsentRecipientClass::from($input['recipientClass']),
        );
    }
}
