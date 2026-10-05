<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Interoperability\InteroperabilityContractService;
use App\Domains\Interoperability\Data\InteroperabilityProfileData;

final readonly class InteroperabilityProfilesQuery
{
    public function __construct(private InteroperabilityContractService $contracts) {}

    /** @return list<InteroperabilityProfileData> */
    public function __invoke(mixed $root, array $args): array
    {
        return $this->contracts->profiles();
    }
}
