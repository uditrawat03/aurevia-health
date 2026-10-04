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
use App\Domains\Scheduling\Data\BookAppointmentRequestData;
use App\Domains\Scheduling\Data\BookAppointmentResultData;
use App\Domains\Scheduling\Data\PersistAppointmentData;
use App\Domains\Scheduling\Enums\AppointmentStatus;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use Carbon\CarbonImmutable;
use DateTimeZone;
use DomainException;
use Throwable;

final readonly class AppointmentBookingService
{
    public function __construct(
        private SchedulingRepo $scheduling,
        private PatientRepo $patients,
        private PrivacyAuthorizationService $privacy,
        private AuditService $audit,
    ) {}

    public function book(int $actorUserId, BookAppointmentRequestData $request): BookAppointmentResultData
    {
        $patient = $this->patients->find($request->organizationId, $request->patientId);
        if ($patient === null) {
            throw new DomainException('Patient was not found.');
        }

        if ($patient->registrationFacilityId !== $request->facilityId) {
            throw new DomainException('Scheduling at another facility is not enabled in this foundation.');
        }

        $this->privacy->authorize(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: ConsentDataCategory::DEMOGRAPHICS,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );

        $appointmentType = $this->scheduling->appointmentType(
            $request->organizationId,
            $request->appointmentTypeId,
        );
        if ($appointmentType === null
            || $appointmentType->facilityId !== $request->facilityId
            || ! $appointmentType->active
        ) {
            throw new DomainException('Appointment type is not available for this facility.');
        }

        $resourceIds = array_values(array_unique(array_map('strval', $request->resourceIds)));
        sort($resourceIds);
        if ($resourceIds === []) {
            throw new DomainException('At least one scheduling resource is required.');
        }

        $timezone = $this->timezone(trim($request->timezone));
        $startsAt = $this->localTimestamp($request->startsAtLocal, $timezone);
        if ($startsAt->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw new DomainException('Appointments must start in the future.');
        }
        $endsAt = $startsAt->addMinutes($appointmentType->durationMinutes);

        $idempotencyKey = trim($request->idempotencyKey);
        if (mb_strlen($idempotencyKey) < 8 || mb_strlen($idempotencyKey) > 120) {
            throw new DomainException('Appointment idempotency key must be between 8 and 120 characters.');
        }

        $reason = $request->reason === null ? null : trim($request->reason);
        if ($reason === '') {
            $reason = null;
        }

        $fingerprint = hash('sha256', json_encode([
            'organizationId' => $request->organizationId,
            'facilityId' => $request->facilityId,
            'patientId' => $request->patientId,
            'appointmentTypeId' => $request->appointmentTypeId,
            'resourceIds' => $resourceIds,
            'startsAt' => $startsAt->toIso8601String(),
            'timezone' => $timezone->getName(),
            'reason' => $reason,
        ], JSON_THROW_ON_ERROR));

        $result = $this->scheduling->book(new PersistAppointmentData(
            organizationId: $request->organizationId,
            facilityId: $request->facilityId,
            patientId: $request->patientId,
            appointmentTypeId: $request->appointmentTypeId,
            status: AppointmentStatus::SCHEDULED,
            startsAt: $startsAt->toIso8601String(),
            endsAt: $endsAt->toIso8601String(),
            timezone: $timezone->getName(),
            reason: $reason,
            createdByUserId: $actorUserId,
            idempotencyKey: $idempotencyKey,
            requestFingerprint: $fingerprint,
        ), $resourceIds);

        if (! $result->replayed) {
            $this->audit->recordPatientOperation(
                actorUserId: $actorUserId,
                organizationId: $request->organizationId,
                facilityId: $request->facilityId,
                patientId: $request->patientId,
                action: AuditAction::BOOK_APPOINTMENT,
                resourceType: AuditResourceType::APPOINTMENT,
                resourceId: $result->appointment->id,
            );
        }

        return $result;
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
