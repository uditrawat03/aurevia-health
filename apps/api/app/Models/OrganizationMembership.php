<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'user_id',
    'organization_id',
    'role',
    'status',
    'all_facilities',
])]
final class OrganizationMembership extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'all_facilities' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Facility::class,
            'organization_membership_facilities',
            'organization_membership_id',
            'facility_id',
        );
    }
}
