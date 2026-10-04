<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Domains\Scheduling\Data\AppointmentData;
use App\Domains\Scheduling\Data\AppointmentTypeData;
use App\Domains\Scheduling\Data\AppointmentWindowData;
use App\Domains\Scheduling\Data\SchedulingResourceData;
use App\Domains\Scheduling\Data\WaitlistEntryData;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use Carbon\CarbonImmutable;
use DomainException;
use Throwable;

final readonly class SchedulingQueryService
{
    public const int MAX_WINDOW_DAYS = 31;

    public function __construct(private SchedulingRepo $scheduling) {}

    /** @return list<SchedulingResourceData> */
    public function resources(string $organizationId, ?string $facilityId): array
    {
        return $this->scheduling->resources($organizationId, $facilityId);
    }

    /** @return list<AppointmentTypeData> */
    public function appointmentTypes(string $organizationId, ?string $facilityId): array
    {
        return $this->scheduling->appointmentTypes($organizationId, $facilityId);
    }


    /** @return list<WaitlistEntryData> */
    public function waitlistEntries(string $organizationId, ?string $facilityId): array
    {
        return $this->scheduling->waitlistEntries($organizationId, $facilityId);
    }

    /** @return list<AppointmentData> */
    public function appointments(AppointmentWindowData $criteria): array
    {
        $from = $this->timestamp($criteria->from, 'Appointment window start is invalid.');
        $to = $this->timestamp($criteria->to, 'Appointment window end is invalid.');
        if ($to->lessThanOrEqualTo($from)) {
            throw new DomainException('Appointment window end must be after its start.');
        }

        if ($from->diffInDays($to) > self::MAX_WINDOW_DAYS) {
            throw new DomainException('Appointment windows may not exceed 31 days.');
        }

        return $this->scheduling->appointments(new AppointmentWindowData(
            organizationId: $criteria->organizationId,
            facilityId: $criteria->facilityId,
            from: $from->utc()->toIso8601String(),
            to: $to->utc()->toIso8601String(),
        ));
    }

    private function timestamp(string $value, string $message): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            throw new DomainException($message);
        }
    }
}
