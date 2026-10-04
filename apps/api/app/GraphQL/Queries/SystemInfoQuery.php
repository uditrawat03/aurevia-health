<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\System\SystemInfo;
use App\Application\System\SystemInfoProvider;

final readonly class SystemInfoQuery
{
    public function __construct(private SystemInfoProvider $systemInfoProvider) {}

    public function __invoke(): SystemInfo
    {
        return $this->systemInfoProvider->get();
    }
}
