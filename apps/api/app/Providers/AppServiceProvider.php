<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\System\SystemInfoProvider;
use App\Application\System\SystemInfoService;
use App\CountryProfiles\CountryProfileResolver;
use App\CountryProfiles\ServerCountryProfileResolver;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Infrastructure\Persistence\Organization\EloquentOrganizationRepo;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemInfoProvider::class, SystemInfoService::class);
        $this->app->bind(CountryProfileResolver::class, ServerCountryProfileResolver::class);
        $this->app->bind(OrganizationRepo::class, EloquentOrganizationRepo::class);
    }
}
