<?php

declare(strict_types=1);

namespace App\Domains\Identity\Data;

final readonly class AuthenticatedUserData
{
    /** @param list<OrganizationMembershipData> $memberships */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public array $memberships,
    ) {}
}
