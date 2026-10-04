<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'name', 'code'])]
final class HealthSystem extends Model
{
    use HasUlids;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
