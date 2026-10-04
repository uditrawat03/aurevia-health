<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'facility_id',
    'patient_id',
    'appointment_type_id',
    'status',
    'preferred_from',
    'preferred_until',
    'timezone',
    'reason',
    'created_by_user_id',
    'idempotency_key',
    'request_fingerprint',
    'cancelled_at',
    'cancelled_by_user_id',
    'cancellation_reason',
])]
final class WaitlistEntry extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'preferred_from' => 'immutable_datetime',
            'preferred_until' => 'immutable_datetime',
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
}
