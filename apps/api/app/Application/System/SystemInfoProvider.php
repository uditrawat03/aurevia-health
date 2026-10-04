<?php

declare(strict_types=1);

namespace App\Application\System;

interface SystemInfoProvider
{
    public function get(): SystemInfo;
}
