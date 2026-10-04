<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Repositories;

use App\Domains\Privacy\Data\BreakGlassAccessData;
use App\Domains\Privacy\Data\PatientConsentData;
use App\Domains\Privacy\Data\PersistBreakGlassAccessData;
use App\Domains\Privacy\Data\PersistPatientConsentData;
use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\RevokePatientConsentData;
use DateTimeInterface;

interface PrivacyRepo
{
    /** @return list<PatientConsentData> */
    public function patientConsents(string $organizationId, string $patientId): array;

    public function createConsent(PersistPatientConsentData $data): PatientConsentData;

    public function revokeConsent(RevokePatientConsentData $data): PatientConsentData;

    public function hasEffectiveConsent(
        PrivacyDecisionContext $context,
        DateTimeInterface $at,
    ): bool;

    public function createBreakGlass(PersistBreakGlassAccessData $data): BreakGlassAccessData;

    public function activeBreakGlass(
        PrivacyDecisionContext $context,
        DateTimeInterface $at,
    ): ?BreakGlassAccessData;
}
