<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Audit\AuditService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientRegistrationService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Patient\Data\RegisterPatientResultData;
use App\GraphQL\Inputs\PatientInputMapper;

final readonly class RegisterPatientMutation
{
    public function __construct(
        private PatientRegistrationService $patients,
        private PatientInputMapper $mapper,
        private OrganizationAuthorizationService $authorization,
        private AuditService $audit,
    ) {}

    /** @param array{input: array<string, mixed>} $args */
    public function __invoke(mixed $root, array $args): RegisterPatientResultData
    {
        $input = $args['input'];
        $organizationId = (string) $input['organizationId'];
        $facilityId = (string) $input['registrationFacilityId'];

        $this->authorization->authorize(
            organizationId: $organizationId,
            permission: OrganizationPermission::MANAGE_PATIENTS,
            facilityId: $facilityId,
        );

        $result = $this->patients->register($this->mapper->mapRegister($input));
        $this->audit->recordPatientOperation(
            actorUserId: $this->authorization->authenticatedUserId(),
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $result->patient->id,
            action: AuditAction::REGISTER_PATIENT,
        );

        return $result;
    }
}
