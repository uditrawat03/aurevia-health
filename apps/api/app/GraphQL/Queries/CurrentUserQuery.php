<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Identity\OrganizationAuthorizationService;
use App\Domains\Identity\Data\AuthenticatedUserData;

final readonly class CurrentUserQuery
{
    public function __construct(private OrganizationAuthorizationService $authorization) {}

    public function __invoke(): AuthenticatedUserData
    {
        return $this->authorization->currentUser();
    }
}
