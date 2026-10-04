<?php

declare(strict_types=1);

namespace App\Domains\Identity\Data;

use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Identity\Enums\OrganizationRole;

final readonly class OrganizationAccessContext
{
    /** @param list<string> $facilityIds */
    public function __construct(
        public OrganizationRole $role,
        public OrganizationPermission $permission,
        public bool $allFacilities,
        public array $facilityIds,
        public ?string $requestedFacilityId,
        public bool $requiresAllFacilities,
    ) {}
}
