<?php

declare(strict_types=1);

namespace App\Domains\Audit\Data;

final readonly class AuditEventCollectionData
{
    /** @param list<AuditEventData> $events */
    public function __construct(public array $events) {}
}
