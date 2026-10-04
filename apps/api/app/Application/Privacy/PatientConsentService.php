<?php

declare(strict_types=1);

namespace App\Application\Privacy;

use App\Application\Audit\AuditService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Privacy\Data\PatientConsentData;
use App\Domains\Privacy\Data\PersistPatientConsentData;
use App\Domains\Privacy\Data\RevokePatientConsentData;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use App\Domains\Privacy\Repositories\PrivacyRepo;
use Carbon\CarbonImmutable;
use DomainException;
use Throwable;

final readonly class PatientConsentService
{
    public function __construct(
        private PrivacyRepo $privacy,
        private AuditService $audit,
    ) {}

    /** @return list<PatientConsentData> */
    public function forPatient(string $organizationId, string $patientId): array
    {
        return $this->privacy->patientConsents($organizationId, $patientId);
    }

    public function grant(
        int $actorUserId,
        string $organizationId,
        string $patientId,
        ?string $facilityId,
        ConsentDataCategory $dataCategory,
        ConsentPurpose $purpose,
        ConsentRecipientClass $recipientClass,
        ?string $effectiveFrom,
        ?string $effectiveUntil,
    ): PatientConsentData {
        $startsAt = $effectiveFrom === null
            ? CarbonImmutable::now()
            : $this->timestamp($effectiveFrom, 'Consent effective-from timestamp is invalid.');
        $endsAt = $effectiveUntil === null
            ? null
            : $this->timestamp($effectiveUntil, 'Consent effective-until timestamp is invalid.');

        if ($endsAt !== null && $endsAt->lessThanOrEqualTo($startsAt)) {
            throw new DomainException('Consent effective-until must be after effective-from.');
        }

        $consent = $this->privacy->createConsent(new PersistPatientConsentData(
            organizationId: $organizationId,
            patientId: $patientId,
            facilityId: $facilityId,
            dataCategory: $dataCategory,
            purpose: $purpose,
            recipientClass: $recipientClass,
            grantedByUserId: $actorUserId,
            effectiveFrom: $startsAt->toIso8601String(),
            effectiveUntil: $endsAt?->toIso8601String(),
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::GRANT_PATIENT_CONSENT,
            resourceType: AuditResourceType::PATIENT_CONSENT,
            resourceId: $consent->id,
        );

        return $consent;
    }

    public function revoke(
        int $actorUserId,
        string $organizationId,
        string $patientId,
        string $consentId,
        string $reason,
        ?string $facilityId,
    ): PatientConsentData {
        $normalizedReason = trim($reason);
        if (mb_strlen($normalizedReason) < 3) {
            throw new DomainException('Consent revocation requires a reason.');
        }

        $consent = $this->privacy->revokeConsent(new RevokePatientConsentData(
            organizationId: $organizationId,
            patientId: $patientId,
            consentId: $consentId,
            revokedByUserId: $actorUserId,
            reason: $normalizedReason,
            revokedAt: CarbonImmutable::now()->toIso8601String(),
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::REVOKE_PATIENT_CONSENT,
            resourceType: AuditResourceType::PATIENT_CONSENT,
            resourceId: $consent->id,
        );

        return $consent;
    }

    private function timestamp(string $value, string $message): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            throw new DomainException($message);
        }
    }
}
