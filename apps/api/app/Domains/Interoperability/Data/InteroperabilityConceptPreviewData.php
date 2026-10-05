<?php

declare(strict_types=1);

namespace App\Domains\Interoperability\Data;

use App\Domains\Terminology\Data\TerminologyConceptData;

final readonly class InteroperabilityConceptPreviewData
{
    public function __construct(
        public string $correlationId,
        public string $occurredAt,
        public string $standard,
        public string $standardVersion,
        public string $resourceType,
        public TerminologyConceptData $concept,
        public string $payloadJson,
    ) {}
}
