<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Application\Privacy\PatientConsentService;
use App\Domains\Identity\Enums\OrganizationPermission;

final readonly class PatientConsentsQuery
{
    public function __construct(
        private PatientQueryService $patients,
        private PatientConsentService $consents,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /**
     * @param array{input: array{organizationId: string, patientId: string}} $args
     * @return list<\App\Domains\Privacy\Data\PatientConsentData>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $input = $args['input'];
        $patient = $this->patients->patient($input['organizationId'], $input['patientId']);

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::VIEW_CONSENTS,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
        );

        return $this->consents->forPatient($input['organizationId'], $patient->id);
    }
}
