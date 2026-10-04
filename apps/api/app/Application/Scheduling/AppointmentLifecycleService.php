<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Application\Audit\AuditService;
use App\Application\Privacy\PrivacyAuthorizationService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use App\Domains\Scheduling\Data\AppointmentData;
use App\Domains\Scheduling\Data\CancelAppointmentData;
use App\Domains\Scheduling\Data\RescheduleAppointmentData;
use App\Domains\Scheduling\Data\RescheduleAppointmentRequestData;
use App\Domains\Scheduling\Enums\AppointmentStatus;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use Carbon\CarbonImmutable;
use DateTimeZone;
use DomainException;
use Throwable;

final readonly class AppointmentLifecycleService
{
    public function __construct(
        private SchedulingRepo $scheduling,
        private PatientRepo $patients,
        private PrivacyAuthorizationService $privacy,
        private AuditService $audit,
    ) {}

    public function reschedule(int $actorUserId, RescheduleAppointmentRequestData $request): AppointmentData
    {
        $appointment = $this->appointment($request->organizationId, $request->appointmentId);
        $this->assertScope($appointment, $request->facilityId, $request->patientId);
        $this->assertScheduled($appointment);
        $this->authorizePatient($actorUserId, $request->organizationId, $request->patientId, $request->facilityId);

        $resourceIds = array_values(array_unique(array_map('strval', $request->resourceIds)));
        sort($resourceIds);
        if ($resourceIds === []) {
            throw new DomainException('At least one scheduling resource is required.');
        }

        $timezone = $this->timezone(trim($request->timezone));
        $startsAt = $this->localTimestamp($request->startsAtLocal, $timezone);
        if ($startsAt->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw new DomainException('Rescheduled appointments must start in the future.');
        }
        $endsAt = $startsAt->addMinutes($appointment->appointmentType->durationMinutes);

        $updated = $this->scheduling->reschedule(new RescheduleAppointmentData(
            organizationId: $request->organizationId,
            facilityId: $request->facilityId,
            patientId: $request->patientId,
            appointmentId: $request->appointmentId,
            startsAt: $startsAt->toIso8601String(),
            endsAt: $endsAt->toIso8601String(),
            timezone: $timezone->getName(),
            actorUserId: $actorUserId,
        ), $resourceIds);

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $request->organizationId,
            facilityId: $request->facilityId,
            patientId: $request->patientId,
            action: AuditAction::RESCHEDULE_APPOINTMENT,
            resourceType: AuditResourceType::APPOINTMENT,
            resourceId: $updated->id,
        );

        return $updated;
    }

    public function cancel(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $appointmentId,
        string $reason,
    ): AppointmentData {
        $appointment = $this->appointment($organizationId, $appointmentId);
        $this->assertScope($appointment, $facilityId, $patientId);
        $this->assertScheduled($appointment);
        $this->authorizePatient($actorUserId, $organizationId, $patientId, $facilityId);

        $normalizedReason = trim($reason);
        if (mb_strlen($normalizedReason) < 3) {
            throw new DomainException('Appointment cancellation requires a reason.');
        }

        $cancelled = $this->scheduling->cancel(new CancelAppointmentData(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            appointmentId: $appointmentId,
            actorUserId: $actorUserId,
            reason: $normalizedReason,
            cancelledAt: CarbonImmutable::now('UTC')->toIso8601String(),
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::CANCEL_APPOINTMENT,
            resourceType: AuditResourceType::APPOINTMENT,
            resourceId: $cancelled->id,
        );

        return $cancelled;
    }

    private function appointment(string $organizationId, string $appointmentId): AppointmentData
    {
        $appointment = $this->scheduling->appointment($organizationId, $appointmentId);
        if ($appointment === null) {
            throw new DomainException('Appointment was not found.');
        }

        return $appointment;
    }

    private function assertScope(AppointmentData $appointment, string $facilityId, string $patientId): void
    {
        if ($appointment->facilityId !== $facilityId || $appointment->patientId !== $patientId) {
            throw new DomainException('Appointment does not belong to the requested patient and facility.');
        }
    }

    private function assertScheduled(AppointmentData $appointment): void
    {
        if ($appointment->status !== AppointmentStatus::SCHEDULED->value) {
            throw new DomainException('Only scheduled appointments may be changed.');
        }
    }

    private function authorizePatient(
        int $actorUserId,
        string $organizationId,
        string $patientId,
        string $facilityId,
    ): void {
        $patient = $this->patients->find($organizationId, $patientId);
        if ($patient === null || $patient->registrationFacilityId !== $facilityId) {
            throw new DomainException('Patient was not found for the scheduling facility.');
        }

        $this->privacy->authorize(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: ConsentDataCategory::DEMOGRAPHICS,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );
    }

    private function timezone(string $value): DateTimeZone
    {
        if ($value === '') {
            throw new DomainException('Appointment timezone is required.');
        }

        try {
            return new DateTimeZone($value);
        } catch (Throwable) {
            throw new DomainException('Appointment timezone is invalid.');
        }
    }

    private function localTimestamp(string $value, DateTimeZone $timezone): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value, $timezone)->utc();
        } catch (Throwable) {
            throw new DomainException('Appointment start time is invalid.');
        }
    }
}
