<?php

declare(strict_types=1);

namespace App\Domains\Identity\Authorization;

use App\Domains\Identity\Data\OrganizationAccessContext;

interface OrganizationAccessPolicy
{
    public function allows(OrganizationAccessContext $context): bool;
}
