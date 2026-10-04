<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum PatientMergeReviewStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
