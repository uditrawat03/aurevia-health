<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Encounter;

use App\Domains\Encounter\Data\EncounterData;
use App\Domains\Encounter\Data\EncounterEventData;
use App\Domains\Encounter\Data\PersistEncounterData;
use App\Domains\Encounter\Data\TransitionEncounterData;
use App\Domains\Encounter\Enums\EncounterEventType;
use App\Domains\Encounter\Enums\EncounterStatus;
use App\Domains\Encounter\Repositories\EncounterRepo;
use App\Models\Encounter;
use App\Models\EncounterEvent;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

final readonly class EloquentEncounterRepo implements EncounterRepo
{
    private const array RELATIONS = [
        'patient',
        'events',
    ];

    public function __construct(private DatabaseManager $database) {}

    public function find(string $organizationId, string $encounterId): ?EncounterData
    {
        $encounter = Encounter::query()
            ->with(self::RELATIONS)
            ->where('organization_id', $organizationId)
            ->whereKey($encounterId)
            ->first();

        return $encounter instanceof Encounter ? $this->mapEncounter($encounter) : null;
    }

    public function forPatient(string $organizationId, string $facilityId, string $patientId): array
    {
        $items = [];
        foreach (Encounter::query()
            ->with(self::RELATIONS)
            ->where('organization_id', $organizationId)
            ->where('facility_id', $facilityId)
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->get() as $encounter) {
            if ($encounter instanceof Encounter) {
                $items[] = $this->mapEncounter($encounter);
            }
        }

        return $items;
    }

    public function findByAppointment(string $organizationId, string $appointmentId): ?EncounterData
    {
        $encounter = Encounter::query()
            ->with(self::RELATIONS)
            ->where('organization_id', $organizationId)
            ->where('appointment_id', $appointmentId)
            ->first();

        return $encounter instanceof Encounter ? $this->mapEncounter($encounter) : null;
    }

    public function create(PersistEncounterData $data): EncounterData
    {
        return $this->database->connection()->transaction(function () use ($data): EncounterData {
            $patient = Patient::query()
                ->where('organization_id', $data->organizationId)
                ->whereKey($data->patientId)
                ->lockForUpdate()
                ->first();
            if (! $patient instanceof Patient) {
                throw new DomainException('Patient was not found.');
            }

            if ($data->appointmentId !== null) {
                $existing = Encounter::query()
                    ->where('organization_id', $data->organizationId)
                    ->where('appointment_id', $data->appointmentId)
                    ->lockForUpdate()
                    ->first();
                if ($existing instanceof Encounter) {
                    throw new DomainException('This appointment already has an encounter.');
                }
            }

            $encounter = Encounter::query()->create([
                'organization_id' => $data->organizationId,
                'facility_id' => $data->facilityId,
                'patient_id' => $data->patientId,
                'department_id' => $data->departmentId,
                'appointment_id' => $data->appointmentId,
                'type' => $data->type->value,
                'status' => EncounterStatus::PLANNED->value,
                'created_by_user_id' => $data->createdByUserId,
            ]);

            EncounterEvent::query()->create([
                'encounter_id' => $encounter->getKey(),
                'type' => EncounterEventType::CREATED->value,
                'from_status' => null,
                'to_status' => EncounterStatus::PLANNED->value,
                'reason' => null,
                'actor_user_id' => $data->createdByUserId,
                'occurred_at' => CarbonImmutable::parse($data->occurredAt),
            ]);

            $encounter->load(self::RELATIONS);

            return $this->mapEncounter($encounter);
        }, 3);
    }

    public function transition(TransitionEncounterData $data): EncounterData
    {
        return $this->database->connection()->transaction(function () use ($data): EncounterData {
            $encounter = Encounter::query()
                ->with(self::RELATIONS)
                ->where('organization_id', $data->organizationId)
                ->where('facility_id', $data->facilityId)
                ->where('patient_id', $data->patientId)
                ->whereKey($data->encounterId)
                ->lockForUpdate()
                ->first();
            if (! $encounter instanceof Encounter) {
                throw new DomainException('Encounter was not found.');
            }

            $current = EncounterStatus::tryFrom((string) $encounter->getAttribute('status'));
            if ($current !== $data->fromStatus) {
                throw new DomainException('Encounter status changed before this transition completed.');
            }

            $occurredAt = CarbonImmutable::parse($data->occurredAt);
            $changes = ['status' => $data->toStatus->value];
            if ($data->toStatus === EncounterStatus::ARRIVED) {
                $changes['arrived_at'] = $occurredAt;
            } elseif ($data->toStatus === EncounterStatus::IN_PROGRESS) {
                $changes['started_at'] = $occurredAt;
            } elseif ($data->toStatus === EncounterStatus::COMPLETED) {
                $changes['ended_at'] = $occurredAt;
            } elseif ($data->toStatus === EncounterStatus::CANCELLED) {
                $changes['cancelled_at'] = $occurredAt;
                $changes['cancellation_reason'] = $data->reason;
            }

            $encounter->fill($changes);
            $encounter->save();

            EncounterEvent::query()->create([
                'encounter_id' => $encounter->getKey(),
                'type' => $this->eventType($data->toStatus)->value,
                'from_status' => $data->fromStatus->value,
                'to_status' => $data->toStatus->value,
                'reason' => $data->reason,
                'actor_user_id' => $data->actorUserId,
                'occurred_at' => $occurredAt,
            ]);

            $encounter->load(self::RELATIONS);

            return $this->mapEncounter($encounter);
        }, 3);
    }

    private function eventType(EncounterStatus $status): EncounterEventType
    {
        return match ($status) {
            EncounterStatus::ARRIVED => EncounterEventType::ARRIVED,
            EncounterStatus::IN_PROGRESS => EncounterEventType::STARTED,
            EncounterStatus::COMPLETED => EncounterEventType::COMPLETED,
            EncounterStatus::CANCELLED => EncounterEventType::CANCELLED,
            EncounterStatus::PLANNED => throw new RuntimeException('PLANNED is not a transition event.'),
        };
    }

    private function mapEncounter(Encounter $encounter): EncounterData
    {
        $patient = $encounter->getRelation('patient');
        if (! $patient instanceof Patient) {
            throw new RuntimeException('Encounter patient relation was not loaded.');
        }

        $events = [];
        foreach ($encounter->getRelation('events')->sortBy('occurred_at') as $event) {
            if ($event instanceof EncounterEvent) {
                $events[] = $this->mapEvent($event);
            }
        }

        return new EncounterData(
            id: (string) $encounter->getKey(),
            organizationId: (string) $encounter->getAttribute('organization_id'),
            facilityId: (string) $encounter->getAttribute('facility_id'),
            patientId: (string) $encounter->getAttribute('patient_id'),
            patientDisplayName: trim((string) $patient->getAttribute('given_name').' '.(string) $patient->getAttribute('family_name')),
            departmentId: $this->nullableString($encounter->getAttribute('department_id')),
            appointmentId: $this->nullableString($encounter->getAttribute('appointment_id')),
            type: (string) $encounter->getAttribute('type'),
            status: (string) $encounter->getAttribute('status'),
            arrivedAt: $this->timestamp($encounter->getAttribute('arrived_at')),
            startedAt: $this->timestamp($encounter->getAttribute('started_at')),
            endedAt: $this->timestamp($encounter->getAttribute('ended_at')),
            cancelledAt: $this->timestamp($encounter->getAttribute('cancelled_at')),
            cancellationReason: $this->nullableString($encounter->getAttribute('cancellation_reason')),
            createdByUserId: (int) $encounter->getAttribute('created_by_user_id'),
            createdAt: $this->requiredTimestamp($encounter->getAttribute('created_at')),
            events: $events,
        );
    }

    private function mapEvent(EncounterEvent $event): EncounterEventData
    {
        return new EncounterEventData(
            id: (string) $event->getKey(),
            type: (string) $event->getAttribute('type'),
            fromStatus: $this->nullableString($event->getAttribute('from_status')),
            toStatus: (string) $event->getAttribute('to_status'),
            reason: $this->nullableString($event->getAttribute('reason')),
            actorUserId: (int) $event->getAttribute('actor_user_id'),
            occurredAt: $this->requiredTimestamp($event->getAttribute('occurred_at')),
        );
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->requiredTimestamp($value);
    }

    private function requiredTimestamp(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return CarbonImmutable::parse((string) $value)->toIso8601String();
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
