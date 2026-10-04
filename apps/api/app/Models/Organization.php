<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'country_code',
    'country_profile_code',
    'country_profile_version',
    'locale_override',
    'timezone_override',
    'week_starts_on_override',
])]
final class Organization extends Model
{
    use HasUlids;

    public function healthSystems(): HasMany
    {
        return $this->hasMany(HealthSystem::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
