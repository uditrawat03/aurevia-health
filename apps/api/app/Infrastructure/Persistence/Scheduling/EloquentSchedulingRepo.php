<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Scheduling;

use App\Domains\Scheduling\Data\AppointmentData;
use App\Domains\Scheduling\Data\AppointmentTypeData;
use App\Domains\Scheduling\Data\AppointmentWindowData;
use App\Domains\Scheduling\Data\BookAppointmentResultData;
use App\Domains\Scheduling\Data\CancelAppointmentData;
use App\Domains\Scheduling\Data\CancelWaitlistEntryData;
use App\Domains\Scheduling\Data\JoinWaitlistResultData;
use App\Domains\Scheduling\Data\PersistAppointmentData;
use App\Domains\Scheduling\Data\PersistWaitlistEntryData;
use App\Domains\Scheduling\Data\RescheduleAppointmentData;
use App\Domains\Scheduling\Data\SchedulingResourceData;
use App\Domains\Scheduling\Data\WaitlistEntryData;
use App\Domains\Scheduling\Enums\AppointmentEventType;
use App\Domains\Scheduling\Enums\AppointmentStatus;
use App\Domains\Scheduling\Enums\WaitlistStatus;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\SchedulingResource;
use App\Models\WaitlistEntry;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final readonly class EloquentSchedulingRepo implements SchedulingRepo
{
    private const array APPOINTMENT_RELATIONS = [
        'patient',
        'appointmentType',
        'resources',
    ];

    private const array WAITLIST_RELATIONS = [
        'patient',
        'appointmentType',
    ];

    public function __construct(private DatabaseManager $database) {}

    public function resources(string $organizationId, ?string $facilityId): array
    {
        $query = SchedulingResource::query()
            ->where('organization_id', $organizationId)
            ->where('active', true);
        if ($facilityId !== null) {
            $query->where('facility_id', $facilityId);
        }

        $resources = [];
        foreach ($query->orderBy('type')->orderBy('name')->get() as $resource) {
            if ($resource instanceof SchedulingResource) {
                $resources[] = $this->mapResource($resource);
            }
        }

        return $resources;
    }

    public function appointmentTypes(string $organizationId, ?string $facilityId): array
    {
        $query = AppointmentType::query()
            ->where('organization_id', $organizationId)
            ->where('active', true);
        if ($facilityId !== null) {
            $query->where('facility_id', $facilityId);
        }

        $types = [];
        foreach ($query->orderBy('name')->get() as $type) {
            if ($type instanceof AppointmentType) {
                $types[] = $this->mapAppointmentType($type);
            }
        }

        return $types;
    }

    public function appointmentType(string $organizationId, string $appointmentTypeId): ?AppointmentTypeData
    {
        $type = AppointmentType::query()
            ->where('organization_id', $organizationId)
            ->whereKey($appointmentTypeId)
            ->first();

        return $type instanceof AppointmentType ? $this->mapAppointmentType($type) : null;
    }

    public function appointment(string $organizationId, string $appointmentId): ?AppointmentData
    {
        $appointment = Appointment::query()
            ->with(self::APPOINTMENT_RELATIONS)
            ->where('organization_id', $organizationId)
            ->whereKey($appointmentId)
            ->first();

        return $appointment instanceof Appointment ? $this->mapAppointment($appointment) : null;
    }

    public function appointments(AppointmentWindowData $criteria): array
    {
        $from = CarbonImmutable::parse($criteria->from)->utc();
        $to = CarbonImmutable::parse($criteria->to)->utc();

        $query = Appointment::query()
            ->with(self::APPOINTMENT_RELATIONS)
            ->where('organization_id', $criteria->organizationId)
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from);
        if ($criteria->facilityId !== null) {
            $query->where('facility_id', $criteria->facilityId);
        }

        $appointments = [];
        foreach ($query->orderBy('starts_at')->get() as $appointment) {
            if ($appointment instanceof Appointment) {
                $appointments[] = $this->mapAppointment($appointment);
            }
        }

        return $appointments;
    }

    public function book(PersistAppointmentData $data, array $resourceIds): BookAppointmentResultData
    {
        return $this->database->connection()->transaction(function () use ($data, $resourceIds): BookAppointmentResultData {
            $patient = Patient::query()
                ->where('organization_id', $data->organizationId)
                ->whereKey($data->patientId)
                ->lockForUpdate()
                ->first();
            if (! $patient instanceof Patient) {
                throw new DomainException('Patient was not found.');
            }

            $existing = Appointment::query()
                ->with(self::APPOINTMENT_RELATIONS)
                ->where('organization_id', $data->organizationId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();
            if ($existing instanceof Appointment) {
                if ($existing->getAttribute('request_fingerprint') !== $data->requestFingerprint) {
                    throw new DomainException('Appointment idempotency key was already used with different input.');
                }

                return new BookAppointmentResultData(
                    appointment: $this->mapAppointment($existing),
                    replayed: true,
                );
            }

            $startsAt = CarbonImmutable::parse($data->startsAt)->utc();
            $endsAt = CarbonImmutable::parse($data->endsAt)->utc();
            $this->lockResourcesAndAssertAvailable(
                organizationId: $data->organizationId,
                facilityId: $data->facilityId,
                resourceIds: $resourceIds,
            );
            $this->assertNoConflicts(
                organizationId: $data->organizationId,
                facilityId: $data->facilityId,
                patientId: $data->patientId,
                resourceIds: $resourceIds,
                startsAt: $startsAt,
                endsAt: $endsAt,
            );

            $appointment = Appointment::query()->create([
                'organization_id' => $data->organizationId,
                'facility_id' => $data->facilityId,
                'patient_id' => $data->patientId,
                'appointment_type_id' => $data->appointmentTypeId,
                'status' => $data->status->value,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'timezone' => $data->timezone,
                'reason' => $data->reason,
                'created_by_user_id' => $data->createdByUserId,
                'idempotency_key' => $data->idempotencyKey,
                'request_fingerprint' => $data->requestFingerprint,
            ]);
            $appointment->resources()->attach($resourceIds);
            $this->appendAppointmentEvent(
                appointment: $appointment,
                actorUserId: $data->createdByUserId,
                type: AppointmentEventType::BOOKED,
                fromStartsAt: null,
                fromEndsAt: null,
                toStartsAt: $startsAt,
                toEndsAt: $endsAt,
                reason: $data->reason,
            );
            $appointment->load(self::APPOINTMENT_RELATIONS);

            return new BookAppointmentResultData(
                appointment: $this->mapAppointment($appointment),
                replayed: false,
            );
        }, 3);
    }

    public function reschedule(RescheduleAppointmentData $data, array $resourceIds): AppointmentData
    {
        return $this->database->connection()->transaction(function () use ($data, $resourceIds): AppointmentData {
            $appointment = Appointment::query()
                ->with(self::APPOINTMENT_RELATIONS)
                ->where('organization_id', $data->organizationId)
                ->where('facility_id', $data->facilityId)
                ->where('patient_id', $data->patientId)
                ->whereKey($data->appointmentId)
                ->lockForUpdate()
                ->first();
            if (! $appointment instanceof Appointment) {
                throw new DomainException('Appointment was not found.');
            }
            if ($appointment->getAttribute('status') !== AppointmentStatus::SCHEDULED->value) {
                throw new DomainException('Only scheduled appointments may be rescheduled.');
            }

            $patient = Patient::query()->whereKey($data->patientId)->lockForUpdate()->first();
            if (! $patient instanceof Patient) {
                throw new DomainException('Patient was not found.');
            }

            $startsAt = CarbonImmutable::parse($data->startsAt)->utc();
            $endsAt = CarbonImmutable::parse($data->endsAt)->utc();
            $this->lockResourcesAndAssertAvailable(
                organizationId: $data->organizationId,
                facilityId: $data->facilityId,
                resourceIds: $resourceIds,
            );
            $this->assertNoConflicts(
                organizationId: $data->organizationId,
                facilityId: $data->facilityId,
                patientId: $data->patientId,
                resourceIds: $resourceIds,
                startsAt: $startsAt,
                endsAt: $endsAt,
                excludeAppointmentId: $data->appointmentId,
            );

            $oldStartsAt = $appointment->getAttribute('starts_at');
            $oldEndsAt = $appointment->getAttribute('ends_at');
            if (! $oldStartsAt instanceof CarbonInterface || ! $oldEndsAt instanceof CarbonInterface) {
                throw new RuntimeException('Appointment persistence state is invalid.');
            }

            $appointment->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'timezone' => $data->timezone,
            ]);
            $appointment->resources()->sync($resourceIds);
            $this->appendAppointmentEvent(
                appointment: $appointment,
                actorUserId: $data->actorUserId,
                type: AppointmentEventType::RESCHEDULED,
                fromStartsAt: $oldStartsAt,
                fromEndsAt: $oldEndsAt,
                toStartsAt: $startsAt,
                toEndsAt: $endsAt,
                reason: null,
            );
            $appointment->load(self::APPOINTMENT_RELATIONS);

            return $this->mapAppointment($appointment);
        }, 3);
    }

    public function cancel(CancelAppointmentData $data): AppointmentData
    {
        return $this->database->connection()->transaction(function () use ($data): AppointmentData {
            $appointment = Appointment::query()
                ->with(self::APPOINTMENT_RELATIONS)
                ->where('organization_id', $data->organizationId)
                ->where('facility_id', $data->facilityId)
                ->where('patient_id', $data->patientId)
                ->whereKey($data->appointmentId)
                ->lockForUpdate()
                ->first();
            if (! $appointment instanceof Appointment) {
                throw new DomainException('Appointment was not found.');
            }
            if ($appointment->getAttribute('status') !== AppointmentStatus::SCHEDULED->value) {
                throw new DomainException('Only scheduled appointments may be cancelled.');
            }

            $startsAt = $appointment->getAttribute('starts_at');
            $endsAt = $appointment->getAttribute('ends_at');
            if (! $startsAt instanceof CarbonInterface || ! $endsAt instanceof CarbonInterface) {
                throw new RuntimeException('Appointment persistence state is invalid.');
            }
            $cancelledAt = CarbonImmutable::parse($data->cancelledAt)->utc();

            $appointment->update([
                'status' => AppointmentStatus::CANCELLED->value,
                'cancelled_at' => $cancelledAt,
                'cancelled_by_user_id' => $data->actorUserId,
                'cancellation_reason' => $data->reason,
            ]);
            $this->appendAppointmentEvent(
                appointment: $appointment,
                actorUserId: $data->actorUserId,
                type: AppointmentEventType::CANCELLED,
                fromStartsAt: $startsAt,
                fromEndsAt: $endsAt,
                toStartsAt: null,
                toEndsAt: null,
                reason: $data->reason,
            );
            $appointment->load(self::APPOINTMENT_RELATIONS);

            return $this->mapAppointment($appointment);
        }, 3);
    }

    public function waitlistEntries(string $organizationId, ?string $facilityId): array
    {
        $query = WaitlistEntry::query()
            ->with(self::WAITLIST_RELATIONS)
            ->where('organization_id', $organizationId);
        if ($facilityId !== null) {
            $query->where('facility_id', $facilityId);
        }

        $entries = [];
        foreach ($query->orderByDesc('created_at')->limit(100)->get() as $entry) {
            if ($entry instanceof WaitlistEntry) {
                $entries[] = $this->mapWaitlistEntry($entry);
            }
        }

        return $entries;
    }

    public function joinWaitlist(PersistWaitlistEntryData $data): JoinWaitlistResultData
    {
        return $this->database->connection()->transaction(function () use ($data): JoinWaitlistResultData {
            $patient = Patient::query()
                ->where('organization_id', $data->organizationId)
                ->whereKey($data->patientId)
                ->lockForUpdate()
                ->first();
            if (! $patient instanceof Patient) {
                throw new DomainException('Patient was not found.');
            }

            $existing = WaitlistEntry::query()
                ->with(self::WAITLIST_RELATIONS)
                ->where('organization_id', $data->organizationId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();
            if ($existing instanceof WaitlistEntry) {
                if ($existing->getAttribute('request_fingerprint') !== $data->requestFingerprint) {
                    throw new DomainException('Waitlist idempotency key was already used with different input.');
                }

                return new JoinWaitlistResultData(
                    entry: $this->mapWaitlistEntry($existing),
                    replayed: true,
                );
            }

            $entry = WaitlistEntry::query()->create([
                'organization_id' => $data->organizationId,
                'facility_id' => $data->facilityId,
                'patient_id' => $data->patientId,
                'appointment_type_id' => $data->appointmentTypeId,
                'status' => WaitlistStatus::WAITING->value,
                'preferred_from' => CarbonImmutable::parse($data->preferredFrom)->utc(),
                'preferred_until' => CarbonImmutable::parse($data->preferredUntil)->utc(),
                'timezone' => $data->timezone,
                'reason' => $data->reason,
                'created_by_user_id' => $data->createdByUserId,
                'idempotency_key' => $data->idempotencyKey,
                'request_fingerprint' => $data->requestFingerprint,
            ]);
            $entry->load(self::WAITLIST_RELATIONS);

            return new JoinWaitlistResultData(
                entry: $this->mapWaitlistEntry($entry),
                replayed: false,
            );
        }, 3);
    }

    public function cancelWaitlist(CancelWaitlistEntryData $data): WaitlistEntryData
    {
        return $this->database->connection()->transaction(function () use ($data): WaitlistEntryData {
            $entry = WaitlistEntry::query()
                ->with(self::WAITLIST_RELATIONS)
                ->where('organization_id', $data->organizationId)
                ->where('facility_id', $data->facilityId)
                ->where('patient_id', $data->patientId)
                ->whereKey($data->waitlistEntryId)
                ->lockForUpdate()
                ->first();
            if (! $entry instanceof WaitlistEntry) {
                throw new DomainException('Waitlist entry was not found.');
            }
            if ($entry->getAttribute('status') !== WaitlistStatus::WAITING->value) {
                throw new DomainException('Only waiting entries may be cancelled.');
            }

            $entry->update([
                'status' => WaitlistStatus::CANCELLED->value,
                'cancelled_at' => CarbonImmutable::parse($data->cancelledAt)->utc(),
                'cancelled_by_user_id' => $data->actorUserId,
                'cancellation_reason' => $data->reason,
            ]);
            $entry->load(self::WAITLIST_RELATIONS);

            return $this->mapWaitlistEntry($entry);
        }, 3);
    }

    /** @param list<string> $resourceIds */
    private function lockResourcesAndAssertAvailable(
        string $organizationId,
        string $facilityId,
        array $resourceIds,
    ): void {
        $resourceModels = SchedulingResource::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('active', true)
            ->whereIn('id', $resourceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        if ($resourceModels->count() !== count($resourceIds)) {
            throw new DomainException('One or more scheduling resources are unavailable for this facility.');
        }
    }

    /** @param list<string> $resourceIds */
    private function assertNoConflicts(
        string $organizationId,
        string $facilityId,
        string $patientId,
        array $resourceIds,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?string $excludeAppointmentId = null,
    ): void {
        $resourceQuery = Appointment::query()
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->whereHas('resources', static function (Builder $query) use ($resourceIds): void {
                $query->whereIn('scheduling_resources.id', $resourceIds);
            });
        if ($excludeAppointmentId !== null) {
            $resourceQuery->where('id', '!=', $excludeAppointmentId);
        }
        if ($resourceQuery->exists()) {
            throw new DomainException('A selected scheduling resource is already booked for that time.');
        }

        $patientQuery = Appointment::query()
            ->where('organization_id', $organizationId)
            ->where('patient_id', $patientId)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
        if ($excludeAppointmentId !== null) {
            $patientQuery->where('id', '!=', $excludeAppointmentId);
        }
        if ($patientQuery->exists()) {
            throw new DomainException('The patient already has an overlapping appointment.');
        }
    }

    private function appendAppointmentEvent(
        Appointment $appointment,
        int $actorUserId,
        AppointmentEventType $type,
        ?CarbonInterface $fromStartsAt,
        ?CarbonInterface $fromEndsAt,
        ?CarbonInterface $toStartsAt,
        ?CarbonInterface $toEndsAt,
        ?string $reason,
    ): void {
        AppointmentEvent::query()->create([
            'appointment_id' => $appointment->getKey(),
            'organization_id' => $appointment->getAttribute('organization_id'),
            'facility_id' => $appointment->getAttribute('facility_id'),
            'patient_id' => $appointment->getAttribute('patient_id'),
            'actor_user_id' => $actorUserId,
            'type' => $type->value,
            'from_starts_at' => $fromStartsAt,
            'from_ends_at' => $fromEndsAt,
            'to_starts_at' => $toStartsAt,
            'to_ends_at' => $toEndsAt,
            'reason' => $reason,
            'occurred_at' => CarbonImmutable::now('UTC'),
        ]);
    }

    private function mapResource(SchedulingResource $resource): SchedulingResourceData
    {
        return new SchedulingResourceData(
            id: (string) $resource->getKey(),
            organizationId: (string) $resource->getAttribute('organization_id'),
            facilityId: (string) $resource->getAttribute('facility_id'),
            type: (string) $resource->getAttribute('type'),
            name: (string) $resource->getAttribute('name'),
            code: (string) $resource->getAttribute('code'),
            active: (bool) $resource->getAttribute('active'),
        );
    }

    private function mapAppointmentType(AppointmentType $type): AppointmentTypeData
    {
        return new AppointmentTypeData(
            id: (string) $type->getKey(),
            organizationId: (string) $type->getAttribute('organization_id'),
            facilityId: (string) $type->getAttribute('facility_id'),
            code: (string) $type->getAttribute('code'),
            name: (string) $type->getAttribute('name'),
            durationMinutes: (int) $type->getAttribute('duration_minutes'),
            active: (bool) $type->getAttribute('active'),
        );
    }

    private function mapAppointment(Appointment $appointment): AppointmentData
    {
        $patient = $appointment->patient;
        $type = $appointment->appointmentType;
        $startsAt = $appointment->getAttribute('starts_at');
        $endsAt = $appointment->getAttribute('ends_at');
        $createdAt = $appointment->getAttribute('created_at');
        $cancelledAt = $appointment->getAttribute('cancelled_at');
        if (! $patient instanceof Patient
            || ! $type instanceof AppointmentType
            || ! $startsAt instanceof CarbonInterface
            || ! $endsAt instanceof CarbonInterface
            || ! $createdAt instanceof CarbonInterface
        ) {
            throw new RuntimeException('Appointment persistence state is invalid.');
        }

        $resources = [];
        foreach ($appointment->resources as $resource) {
            if ($resource instanceof SchedulingResource) {
                $resources[] = $this->mapResource($resource);
            }
        }

        return new AppointmentData(
            id: (string) $appointment->getKey(),
            organizationId: (string) $appointment->getAttribute('organization_id'),
            facilityId: (string) $appointment->getAttribute('facility_id'),
            patientId: (string) $appointment->getAttribute('patient_id'),
            patientDisplayName: $this->patientDisplayName($patient),
            appointmentType: $this->mapAppointmentType($type),
            resources: $resources,
            status: (string) $appointment->getAttribute('status'),
            startsAt: $startsAt->toIso8601String(),
            endsAt: $endsAt->toIso8601String(),
            timezone: (string) $appointment->getAttribute('timezone'),
            reason: $this->nullableString($appointment->getAttribute('reason')),
            cancelledAt: $cancelledAt instanceof CarbonInterface ? $cancelledAt->toIso8601String() : null,
            cancellationReason: $this->nullableString($appointment->getAttribute('cancellation_reason')),
            createdAt: $createdAt->toIso8601String(),
        );
    }

    private function mapWaitlistEntry(WaitlistEntry $entry): WaitlistEntryData
    {
        $patient = $entry->patient;
        $type = $entry->appointmentType;
        $preferredFrom = $entry->getAttribute('preferred_from');
        $preferredUntil = $entry->getAttribute('preferred_until');
        $cancelledAt = $entry->getAttribute('cancelled_at');
        $createdAt = $entry->getAttribute('created_at');
        if (! $patient instanceof Patient
            || ! $type instanceof AppointmentType
            || ! $preferredFrom instanceof CarbonInterface
            || ! $preferredUntil instanceof CarbonInterface
            || ! $createdAt instanceof CarbonInterface
        ) {
            throw new RuntimeException('Waitlist persistence state is invalid.');
        }

        return new WaitlistEntryData(
            id: (string) $entry->getKey(),
            organizationId: (string) $entry->getAttribute('organization_id'),
            facilityId: (string) $entry->getAttribute('facility_id'),
            patientId: (string) $entry->getAttribute('patient_id'),
            patientDisplayName: $this->patientDisplayName($patient),
            appointmentType: $this->mapAppointmentType($type),
            status: (string) $entry->getAttribute('status'),
            preferredFrom: $preferredFrom->toIso8601String(),
            preferredUntil: $preferredUntil->toIso8601String(),
            timezone: (string) $entry->getAttribute('timezone'),
            reason: $this->nullableString($entry->getAttribute('reason')),
            cancelledAt: $cancelledAt instanceof CarbonInterface ? $cancelledAt->toIso8601String() : null,
            cancellationReason: $this->nullableString($entry->getAttribute('cancellation_reason')),
            createdAt: $createdAt->toIso8601String(),
        );
    }

    private function patientDisplayName(Patient $patient): string
    {
        $preferredName = $this->nullableString($patient->getAttribute('preferred_name'));

        return $preferredName ?? trim(
            (string) $patient->getAttribute('given_name').' '.(string) $patient->getAttribute('family_name'),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
