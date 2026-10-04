<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Enums;

enum ClinicalNoteStatus: string
{
    case DRAFT = 'DRAFT';
    case SIGNED = 'SIGNED';
}
