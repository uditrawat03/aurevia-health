<?php

declare(strict_types=1);

namespace App\Domains\Terminology\Registry;

use App\Domains\Terminology\Data\TerminologySystemData;

interface TerminologyRegistry
{
    /** @return list<TerminologySystemData> */
    public function systems(): array;

    public function isRecognized(string $system): bool;
}
