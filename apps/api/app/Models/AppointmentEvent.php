<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'appointment_id',
    'organization_id',
    'facility_id',
    'patient_id',
    'actor_user_id',
    'type',
    'from_starts_at',
    'from_ends_at',
    'to_starts_at',
    'to_ends_at',
    'reason',
    'occurred_at',
])]
final class AppointmentEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'from_starts_at' => 'immutable_datetime',
            'from_ends_at' => 'immutable_datetime',
            'to_starts_at' => 'immutable_datetime',
            'to_ends_at' => 'immutable_datetime',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
