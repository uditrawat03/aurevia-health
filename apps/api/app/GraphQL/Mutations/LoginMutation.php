<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Identity\SessionAuthenticationService;
use App\Domains\Identity\Data\AuthenticatedUserData;
use App\Domains\Identity\Data\LoginCredentialsData;

final readonly class LoginMutation
{
    public function __construct(private SessionAuthenticationService $authentication) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{email: string, password: string}} $args
     */
    public function __invoke(mixed $root, array $args): AuthenticatedUserData
    {
        $input = $args['input'];

        return $this->authentication->login(new LoginCredentialsData(
            email: $input['email'],
            password: $input['password'],
        ));
    }
}
