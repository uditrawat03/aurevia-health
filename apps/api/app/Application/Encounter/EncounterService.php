<?php

declare(strict_types=1);

namespace App\Application\Encounter;

use App\Application\Audit\AuditService;
use App\Application\Privacy\PrivacyAuthorizationService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Encounter\Data\PersistEncounterData;
use App\Domains\Encounter\Data\TransitionEncounterData;
use App\Domains\Encounter\Enums\EncounterStatus;
use App\Domains\Encounter\Enums\EncounterType;
use App\Domains\Encounter\Repositories\EncounterRepo;
use App\Domains\Encounter\Workflow\EncounterTransitionPolicy;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use App\Domains\Scheduling\Enums\AppointmentStatus;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use Carbon\CarbonImmutable;
use App\Exceptions\ExpectedBusinessRuleViolation as DomainException;

final readonly class EncounterService
{
    public function __construct(
        private EncounterRepo $encounters,
        private PatientRepo $patients,
        private SchedulingRepo $scheduling,
        private OrganizationRepo $organizations,
        private PrivacyAuthorizationService $privacy,
        private EncounterTransitionPolicy $transitions,
        private AuditService $audit,
    ) {}

    public function create(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        EncounterType $type,
        ?string $departmentId,
        ?string $appointmentId,
    ): EncounterData {
        $patient = $this->patients->find($organizationId, $patientId);
        if ($patient === null) {
            throw new DomainException('Patient was not found.');
        }
        if ($patient->registrationFacilityId !== $facilityId) {
            throw new DomainException('Encounter care at another facility is not enabled in this foundation.');
        }

        $this->privacy->authorize(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: ConsentDataCategory::CLINICAL,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );

        if ($departmentId !== null) {
            $department = $this->organizations->findDepartment($organizationId, $facilityId, $departmentId);
            if ($department === null) {
                throw new DomainException('Department was not found in this facility.');
            }
        }

        if ($appointmentId !== null) {
            $appointment = $this->scheduling->appointment($organizationId, $appointmentId);
            if ($appointment === null
                || $appointment->facilityId !== $facilityId
                || $appointment->patientId !== $patientId
            ) {
                throw new DomainException('Appointment does not belong to this patient and facility.');
            }
            if ($appointment->status !== AppointmentStatus::SCHEDULED->value) {
                throw new DomainException('Only scheduled appointments can create an encounter.');
            }
            if ($this->encounters->findByAppointment($organizationId, $appointmentId) !== null) {
                throw new DomainException('This appointment already has an encounter.');
            }
        }

        $encounter = $this->encounters->create(new PersistEncounterData(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            departmentId: $departmentId,
            appointmentId: $appointmentId,
            type: $type,
            createdByUserId: $actorUserId,
            occurredAt: CarbonImmutable::now('UTC')->toIso8601String(),
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::CREATE_ENCOUNTER,
            resourceType: AuditResourceType::ENCOUNTER,
            resourceId: $encounter->id,
        );

        return $encounter;
    }

    public function transition(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $encounterId,
        EncounterStatus $toStatus,
        ?string $reason,
    ): EncounterData {
        $encounter = $this->encounters->find($organizationId, $encounterId);
        if ($encounter === null
            || $encounter->facilityId !== $facilityId
            || $encounter->patientId !== $patientId
        ) {
            throw new DomainException('Encounter was not found for this patient and facility.');
        }

        $patient = $this->patients->find($organizationId, $patientId);
        if ($patient === null) {
            throw new DomainException('Patient was not found.');
        }
        $this->privacy->authorize(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: ConsentDataCategory::CLINICAL,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );

        $fromStatus = EncounterStatus::tryFrom($encounter->status);
        if ($fromStatus === null || ! $this->transitions->allows($fromStatus, $toStatus)) {
            throw new DomainException(sprintf(
                'Encounter cannot transition from %s to %s.',
                $encounter->status,
                $toStatus->value,
            ));
        }

        $normalizedReason = $reason === null ? null : trim($reason);
        if ($toStatus === EncounterStatus::CANCELLED
            && ($normalizedReason === null || mb_strlen($normalizedReason) < 3)
        ) {
            throw new DomainException('Encounter cancellation requires a reason.');
        }
        if ($normalizedReason === '') {
            $normalizedReason = null;
        }

        $updated = $this->encounters->transition(new TransitionEncounterData(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            encounterId: $encounterId,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            actorUserId: $actorUserId,
            occurredAt: CarbonImmutable::now('UTC')->toIso8601String(),
            reason: $normalizedReason,
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: $this->auditAction($toStatus),
            resourceType: AuditResourceType::ENCOUNTER,
            resourceId: $encounterId,
        );

        return $updated;
    }

    private function auditAction(EncounterStatus $status): AuditAction
    {
        return match ($status) {
            EncounterStatus::ARRIVED => AuditAction::ARRIVE_ENCOUNTER,
            EncounterStatus::IN_PROGRESS => AuditAction::START_ENCOUNTER,
            EncounterStatus::COMPLETED => AuditAction::COMPLETE_ENCOUNTER,
            EncounterStatus::CANCELLED => AuditAction::CANCEL_ENCOUNTER,
            EncounterStatus::PLANNED => throw new DomainException('PLANNED is not a transition target.'),
        };
    }
}
