<?php

declare(strict_types=1);

namespace App\Domains\Identity\Data;

final readonly class LoginCredentialsData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
