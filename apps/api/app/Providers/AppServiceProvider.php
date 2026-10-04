<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\System\SystemInfoProvider;
use App\Application\System\SystemInfoService;
use App\CountryProfiles\CountryProfileResolver;
use App\CountryProfiles\ServerCountryProfileResolver;
use App\Domains\Audit\Repositories\AuditRepo;
use App\Domains\Identity\Authorization\OrganizationAccessPolicy;
use App\Domains\Identity\Authorization\RoleAndScopeOrganizationAccessPolicy;
use App\Domains\Identity\Repositories\IdentityRepo;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Infrastructure\Persistence\Audit\EloquentAuditRepo;
use App\Infrastructure\Persistence\Identity\EloquentIdentityRepo;
use App\Infrastructure\Persistence\Organization\EloquentOrganizationRepo;
use App\Infrastructure\Persistence\Patient\EloquentPatientRepo;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemInfoProvider::class, SystemInfoService::class);
        $this->app->bind(CountryProfileResolver::class, ServerCountryProfileResolver::class);
        $this->app->bind(OrganizationRepo::class, EloquentOrganizationRepo::class);
        $this->app->bind(IdentityRepo::class, EloquentIdentityRepo::class);
        $this->app->bind(AuditRepo::class, EloquentAuditRepo::class);
        $this->app->bind(PatientRepo::class, EloquentPatientRepo::class);
        $this->app->bind(OrganizationAccessPolicy::class, RoleAndScopeOrganizationAccessPolicy::class);
    }
}
