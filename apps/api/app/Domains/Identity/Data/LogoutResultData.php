<?php

declare(strict_types=1);

namespace App\Domains\Identity\Data;

final readonly class LogoutResultData
{
    public function __construct(public bool $loggedOut) {}
}
