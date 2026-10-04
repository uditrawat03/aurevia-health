<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Repositories;

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

interface SchedulingRepo
{
    /** @return list<SchedulingResourceData> */
    public function resources(string $organizationId, ?string $facilityId): array;

    /** @return list<AppointmentTypeData> */
    public function appointmentTypes(string $organizationId, ?string $facilityId): array;

    public function appointmentType(string $organizationId, string $appointmentTypeId): ?AppointmentTypeData;

    public function appointment(string $organizationId, string $appointmentId): ?AppointmentData;

    /** @return list<AppointmentData> */
    public function appointments(AppointmentWindowData $criteria): array;

    /** @param list<string> $resourceIds */
    public function book(PersistAppointmentData $data, array $resourceIds): BookAppointmentResultData;

    /** @param list<string> $resourceIds */
    public function reschedule(RescheduleAppointmentData $data, array $resourceIds): AppointmentData;

    public function cancel(CancelAppointmentData $data): AppointmentData;

    /** @return list<WaitlistEntryData> */
    public function waitlistEntries(string $organizationId, ?string $facilityId): array;

    public function joinWaitlist(PersistWaitlistEntryData $data): JoinWaitlistResultData;

    public function cancelWaitlist(CancelWaitlistEntryData $data): WaitlistEntryData;
}
