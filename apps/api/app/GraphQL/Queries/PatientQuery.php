<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Application\Privacy\PrivacyAuthorizationService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Patient\Data\PatientData;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;

final readonly class PatientQuery
{
    public function __construct(
        private PatientQueryService $patients,
        private OrganizationAuthorizationService $authorization,
        private PrivacyAuthorizationService $privacy,
    ) {}

    /** @param array{organizationId: string, id: string} $args */
    public function __invoke(mixed $root, array $args): PatientData
    {
        $patient = $this->patients->patient($args['organizationId'], $args['id']);

        $this->authorization->authorize(
            organizationId: $args['organizationId'],
            permission: OrganizationPermission::VIEW_PATIENTS,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
        );

        $this->privacy->authorize(
            actorUserId: $this->authorization->authenticatedUserId(),
            patient: $patient,
            dataCategory: ConsentDataCategory::DEMOGRAPHICS,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );

        return $patient;
    }
}
