<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\System\SystemInfoProvider;
use App\Application\System\SystemInfoService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemInfoProvider::class, SystemInfoService::class);
    }
}
