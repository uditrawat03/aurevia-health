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
use App\Domains\Scheduling\Data\CancelWaitlistEntryData;
use App\Domains\Scheduling\Data\JoinWaitlistRequestData;
use App\Domains\Scheduling\Data\JoinWaitlistResultData;
use App\Domains\Scheduling\Data\PersistWaitlistEntryData;
use App\Domains\Scheduling\Data\WaitlistEntryData;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use Carbon\CarbonImmutable;
use DateTimeZone;
use DomainException;
use Throwable;

final readonly class WaitlistService
{
    public function __construct(
        private SchedulingRepo $scheduling,
        private PatientRepo $patients,
        private PrivacyAuthorizationService $privacy,
        private AuditService $audit,
    ) {}

    public function join(int $actorUserId, JoinWaitlistRequestData $request): JoinWaitlistResultData
    {
        $this->authorizePatient(
            actorUserId: $actorUserId,
            organizationId: $request->organizationId,
            patientId: $request->patientId,
            facilityId: $request->facilityId,
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

        $timezone = $this->timezone(trim($request->timezone));
        $preferredFrom = $this->localTimestamp($request->preferredFromLocal, $timezone, 'Waitlist preferred-from time is invalid.');
        $preferredUntil = $this->localTimestamp($request->preferredUntilLocal, $timezone, 'Waitlist preferred-until time is invalid.');
        if ($preferredUntil->lessThanOrEqualTo($preferredFrom)) {
            throw new DomainException('Waitlist preferred-until must be after preferred-from.');
        }
        if ($preferredUntil->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw new DomainException('Waitlist preference must include a future time.');
        }
        if ($preferredFrom->diffInDays($preferredUntil) > 90) {
            throw new DomainException('Waitlist preference windows may not exceed 90 days.');
        }

        $idempotencyKey = trim($request->idempotencyKey);
        if (mb_strlen($idempotencyKey) < 8 || mb_strlen($idempotencyKey) > 120) {
            throw new DomainException('Waitlist idempotency key must be between 8 and 120 characters.');
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
            'preferredFrom' => $preferredFrom->toIso8601String(),
            'preferredUntil' => $preferredUntil->toIso8601String(),
            'timezone' => $timezone->getName(),
            'reason' => $reason,
        ], JSON_THROW_ON_ERROR));

        $result = $this->scheduling->joinWaitlist(new PersistWaitlistEntryData(
            organizationId: $request->organizationId,
            facilityId: $request->facilityId,
            patientId: $request->patientId,
            appointmentTypeId: $request->appointmentTypeId,
            preferredFrom: $preferredFrom->toIso8601String(),
            preferredUntil: $preferredUntil->toIso8601String(),
            timezone: $timezone->getName(),
            reason: $reason,
            createdByUserId: $actorUserId,
            idempotencyKey: $idempotencyKey,
            requestFingerprint: $fingerprint,
        ));

        if (! $result->replayed) {
            $this->audit->recordPatientOperation(
                actorUserId: $actorUserId,
                organizationId: $request->organizationId,
                facilityId: $request->facilityId,
                patientId: $request->patientId,
                action: AuditAction::JOIN_WAITLIST,
                resourceType: AuditResourceType::WAITLIST_ENTRY,
                resourceId: $result->entry->id,
            );
        }

        return $result;
    }

    public function cancel(
        int $actorUserId,
        string $organizationId,
        string $facilityId,
        string $patientId,
        string $waitlistEntryId,
        string $reason,
    ): WaitlistEntryData {
        $this->authorizePatient($actorUserId, $organizationId, $patientId, $facilityId);
        $normalizedReason = trim($reason);
        if (mb_strlen($normalizedReason) < 3) {
            throw new DomainException('Waitlist cancellation requires a reason.');
        }

        $entry = $this->scheduling->cancelWaitlist(new CancelWaitlistEntryData(
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            waitlistEntryId: $waitlistEntryId,
            actorUserId: $actorUserId,
            reason: $normalizedReason,
            cancelledAt: CarbonImmutable::now('UTC')->toIso8601String(),
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::CANCEL_WAITLIST,
            resourceType: AuditResourceType::WAITLIST_ENTRY,
            resourceId: $entry->id,
        );

        return $entry;
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
            throw new DomainException('Waitlist timezone is required.');
        }

        try {
            return new DateTimeZone($value);
        } catch (Throwable) {
            throw new DomainException('Waitlist timezone is invalid.');
        }
    }

    private function localTimestamp(string $value, DateTimeZone $timezone, string $message): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value, $timezone)->utc();
        } catch (Throwable) {
            throw new DomainException($message);
        }
    }
}
