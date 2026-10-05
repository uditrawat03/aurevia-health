<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Interoperability\InteroperabilityContractService;
use App\Domains\Terminology\Data\TerminologySystemData;

final readonly class TerminologySystemsQuery
{
    public function __construct(private InteroperabilityContractService $contracts) {}

    /** @return list<TerminologySystemData> */
    public function __invoke(mixed $root, array $args): array
    {
        return $this->contracts->terminologySystems();
    }
}
