<?php

declare(strict_types=1);

namespace App\Application\Privacy;

use App\Application\Audit\AuditService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Patient\Data\PatientData;
use App\Domains\Privacy\Authorization\CountryProfilePrivacyPolicyRegistry;
use App\Domains\Privacy\Authorization\PrivacyPolicy;
use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\PrivacyDecisionData;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use LogicException;

final readonly class PrivacyAuthorizationService
{
    public function __construct(
        private PrivacyPolicy $policy,
        private CountryProfilePrivacyPolicyRegistry $countryPolicies,
        private OrganizationRepo $organizations,
        private AuditService $audit,
    ) {}

    public function authorize(
        int $actorUserId,
        PatientData $patient,
        ConsentDataCategory $dataCategory,
        ConsentPurpose $purpose,
        ConsentRecipientClass $recipientClass,
    ): PrivacyDecisionData {
        $decision = $this->decision(
            actorUserId: $actorUserId,
            patient: $patient,
            dataCategory: $dataCategory,
            purpose: $purpose,
            recipientClass: $recipientClass,
        );

        if (! $decision->allowed) {
            throw new AuthorizationException('Patient privacy policy denied access.');
        }

        return $decision;
    }

    public function decision(
        int $actorUserId,
        PatientData $patient,
        ConsentDataCategory $dataCategory,
        ConsentPurpose $purpose,
        ConsentRecipientClass $recipientClass,
    ): PrivacyDecisionData {
        $context = new PrivacyDecisionContext(
            actorUserId: $actorUserId,
            organizationId: $patient->organizationId,
            patientId: $patient->id,
            facilityId: $patient->registrationFacilityId,
            dataCategory: $dataCategory,
            purpose: $purpose,
            recipientClass: $recipientClass,
        );

        $baseDecision = $this->policy->decide($context, CarbonImmutable::now());
        $organization = $this->organizations->findOrganization($patient->organizationId);
        if ($organization === null) {
            throw new DomainException('Patient organization was not found.');
        }

        $decision = $this->countryPolicies->tighten(
            profileCode: $organization->countryProfileCode,
            profileVersion: $organization->countryProfileVersion,
            context: $context,
            baseDecision: $baseDecision,
        );
        if (! $baseDecision->allowed && $decision->allowed) {
            throw new LogicException('Country privacy policy cannot loosen a core privacy denial.');
        }

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $patient->organizationId,
            facilityId: $patient->registrationFacilityId,
            patientId: $patient->id,
            action: AuditAction::PRIVACY_DECISION,
            resourceType: AuditResourceType::PRIVACY_DECISION,
            outcome: $decision->allowed ? AuditOutcome::ALLOWED : AuditOutcome::DENIED,
        );

        return $decision;
    }
}
