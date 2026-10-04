<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\SessionAuthenticationService;
use App\Domains\Identity\Data\LogoutResultData;

final readonly class LogoutMutation
{
    public function __construct(private SessionAuthenticationService $authentication) {}

    public function __invoke(): LogoutResultData
    {
        return $this->authentication->logout();
    }
}
