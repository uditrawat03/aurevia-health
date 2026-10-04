<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'facility_id',
    'patient_id',
    'appointment_type_id',
    'status',
    'starts_at',
    'ends_at',
    'timezone',
    'reason',
    'cancelled_at',
    'cancelled_by_user_id',
    'cancellation_reason',
    'created_by_user_id',
    'idempotency_key',
    'request_fingerprint',
])]
final class Appointment extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(
            SchedulingResource::class,
            'appointment_resource_assignments',
            'appointment_id',
            'scheduling_resource_id',
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class);
    }
}
