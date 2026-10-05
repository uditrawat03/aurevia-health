<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\System\SystemInfoProvider;
use App\Application\System\SystemInfoService;
use App\CountryProfiles\CountryProfileResolver;
use App\CountryProfiles\ServerCountryProfileResolver;
use App\Domains\Audit\Repositories\AuditRepo;
use App\Domains\Clinical\Repositories\ClinicalRecordRepo;
use App\Domains\Encounter\Repositories\EncounterRepo;
use App\Domains\Identity\Authorization\OrganizationAccessPolicy;
use App\Domains\Identity\Authorization\RoleAndScopeOrganizationAccessPolicy;
use App\Domains\Identity\Repositories\IdentityRepo;
use App\Domains\Interoperability\Contracts\InteroperabilityMapper;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Domains\Privacy\Authorization\ConsentAndBreakGlassPrivacyPolicy;
use App\Domains\Privacy\Authorization\DefaultCountryProfilePrivacyPolicyRegistry;
use App\Domains\Privacy\Authorization\CountryProfilePrivacyPolicyRegistry;
use App\Domains\Privacy\Authorization\PrivacyPolicy;
use App\Domains\Privacy\Repositories\PrivacyRepo;
use App\Domains\Scheduling\Repositories\SchedulingRepo;
use App\Domains\Terminology\Mapping\TerminologyMappingContract;
use App\Domains\Terminology\Registry\TerminologyRegistry;
use App\Infrastructure\Interoperability\FhirR4InteroperabilityMapper;
use App\Infrastructure\Persistence\Audit\EloquentAuditRepo;
use App\Infrastructure\Persistence\Clinical\EloquentClinicalRecordRepo;
use App\Infrastructure\Persistence\Encounter\EloquentEncounterRepo;
use App\Infrastructure\Persistence\Identity\EloquentIdentityRepo;
use App\Infrastructure\Persistence\Organization\EloquentOrganizationRepo;
use App\Infrastructure\Persistence\Patient\EloquentPatientRepo;
use App\Infrastructure\Persistence\Privacy\EloquentPrivacyRepo;
use App\Infrastructure\Persistence\Scheduling\EloquentSchedulingRepo;
use App\Infrastructure\Terminology\DefaultTerminologyRegistry;
use App\Infrastructure\Terminology\ExistingCodingTerminologyMapping;
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
        $this->app->bind(EncounterRepo::class, EloquentEncounterRepo::class);
        $this->app->bind(ClinicalRecordRepo::class, EloquentClinicalRecordRepo::class);
        $this->app->bind(PatientRepo::class, EloquentPatientRepo::class);
        $this->app->bind(PrivacyRepo::class, EloquentPrivacyRepo::class);
        $this->app->bind(SchedulingRepo::class, EloquentSchedulingRepo::class);
        $this->app->bind(TerminologyRegistry::class, DefaultTerminologyRegistry::class);
        $this->app->bind(TerminologyMappingContract::class, ExistingCodingTerminologyMapping::class);
        $this->app->bind(InteroperabilityMapper::class, FhirR4InteroperabilityMapper::class);
        $this->app->bind(OrganizationAccessPolicy::class, RoleAndScopeOrganizationAccessPolicy::class);
        $this->app->bind(PrivacyPolicy::class, ConsentAndBreakGlassPrivacyPolicy::class);
        $this->app->bind(
            CountryProfilePrivacyPolicyRegistry::class,
            DefaultCountryProfilePrivacyPolicyRegistry::class,
        );
    }
}
