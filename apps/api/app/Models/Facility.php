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
    'health_system_id',
    'name',
    'code',
    'locale_override',
    'timezone_override',
    'week_starts_on_override',
])]
final class Facility extends Model
{
    use HasUlids;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function healthSystem(): BelongsTo
    {
        return $this->belongsTo(HealthSystem::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
