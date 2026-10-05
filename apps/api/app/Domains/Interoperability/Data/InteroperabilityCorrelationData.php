<?php

declare(strict_types=1);

namespace App\Domains\Interoperability\Data;

final readonly class InteroperabilityCorrelationData
{
    public function __construct(
        public string $correlationId,
        public string $occurredAt,
    ) {}
}
