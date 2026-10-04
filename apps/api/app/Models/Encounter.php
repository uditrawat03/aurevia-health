<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'facility_id',
    'patient_id',
    'department_id',
    'appointment_id',
    'type',
    'status',
    'arrived_at',
    'started_at',
    'ended_at',
    'cancelled_at',
    'cancellation_reason',
    'created_by_user_id',
])]
final class Encounter extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'arrived_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EncounterEvent::class);
    }
}
