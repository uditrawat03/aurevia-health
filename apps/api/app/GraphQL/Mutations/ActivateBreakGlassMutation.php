<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientQueryService;
use App\Application\Privacy\BreakGlassService;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Privacy\Data\BreakGlassAccessData;
use App\Domains\Privacy\Enums\ConsentPurpose;

final readonly class ActivateBreakGlassMutation
{
    public function __construct(
        private PatientQueryService $patients,
        private BreakGlassService $breakGlass,
        private OrganizationAuthorizationService $authorization,
    ) {}

    /** @param array{input: array{organizationId: string, patientId: string, purpose: string, reason: string}} $args */
    public function __invoke(mixed $root, array $args): BreakGlassAccessData
    {
        $input = $args['input'];
        $patient = $this->patients->patient($input['organizationId'], $input['patientId']);

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::BREAK_GLASS_PATIENT_ACCESS,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
        );

        return $this->breakGlass->activate(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $input['organizationId'],
            patientId: $patient->id,
            facilityId: $patient->registrationFacilityId,
            purpose: ConsentPurpose::from($input['purpose']),
            reason: $input['reason'],
        );
    }
}
