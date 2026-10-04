<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'clinical_note_id',
    'type',
    'body',
    'reason',
    'author_user_id',
])]
final class ClinicalNoteAmendment extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;
}
