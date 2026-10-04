<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'organization_id',
    'source_patient_id',
    'target_patient_id',
    'status',
    'requested_by_user_id',
    'reviewed_by_user_id',
    'reason',
])]
final class PatientMergeReview extends Model
{
    use HasUlids;
}
