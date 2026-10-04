<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'organization_id',
    'scope_type',
    'scope_id',
    'setting_key',
    'previous_value',
    'new_value',
    'correlation_id',
    'changed_at',
])]
final class ConfigurationChange extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'changed_at' => 'immutable_datetime',
        ];
    }
}
