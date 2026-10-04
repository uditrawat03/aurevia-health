<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'facility_id',
    'patient_id',
    'encounter_id',
    'note_type',
    'title',
    'body',
    'status',
    'author_user_id',
    'signed_by_user_id',
    'signed_at',
])]
final class ClinicalNote extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'signed_at' => 'immutable_datetime',
        ];
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(ClinicalNoteAmendment::class);
    }
}
