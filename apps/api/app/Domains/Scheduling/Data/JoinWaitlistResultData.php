<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Data;

final readonly class JoinWaitlistResultData
{
    public function __construct(
        public WaitlistEntryData $entry,
        public bool $replayed,
    ) {}
}
