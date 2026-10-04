<?php

declare(strict_types=1);

namespace App\Domains\Identity\Data;

final readonly class OrganizationMembershipData
{
    /** @param list<string> $facilityIds */
    public function __construct(
        public string $id,
        public string $userId,
        public string $organizationId,
        public string $role,
        public string $status,
        public bool $allFacilities,
        public array $facilityIds,
    ) {}
}
