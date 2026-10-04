<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Privacy;

use App\Domains\Privacy\Data\BreakGlassAccessData;
use App\Domains\Privacy\Data\PatientConsentData;
use App\Domains\Privacy\Data\PersistBreakGlassAccessData;
use App\Domains\Privacy\Data\PersistPatientConsentData;
use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\RevokePatientConsentData;
use App\Domains\Privacy\Enums\ConsentStatus;
use App\Domains\Privacy\Repositories\PrivacyRepo;
use App\Models\BreakGlassAccess;
use App\Models\PatientConsent;
use Carbon\CarbonInterface;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final readonly class EloquentPrivacyRepo implements PrivacyRepo
{
    public function patientConsents(string $organizationId, string $patientId): array
    {
        $models = PatientConsent::query()
            ->where('organization_id', $organizationId)
            ->where('patient_id', $patientId)
            ->latest('created_at')
            ->get();

        $items = [];
        foreach ($models as $model) {
            if ($model instanceof PatientConsent) {
                $items[] = $this->mapConsent($model);
            }
        }

        return $items;
    }

    public function createConsent(PersistPatientConsentData $data): PatientConsentData
    {
        $model = PatientConsent::query()->create([
            'organization_id' => $data->organizationId,
            'patient_id' => $data->patientId,
            'facility_id' => $data->facilityId,
            'data_category' => $data->dataCategory->value,
            'purpose' => $data->purpose->value,
            'recipient_class' => $data->recipientClass->value,
            'status' => ConsentStatus::ACTIVE->value,
            'granted_by_user_id' => $data->grantedByUserId,
            'effective_from' => $data->effectiveFrom,
            'effective_until' => $data->effectiveUntil,
            'revoked_at' => null,
            'revoked_by_user_id' => null,
            'revocation_reason' => null,
        ]);

        return $this->mapConsent($model);
    }

    public function revokeConsent(RevokePatientConsentData $data): PatientConsentData
    {
        $model = PatientConsent::query()
            ->where('organization_id', $data->organizationId)
            ->where('patient_id', $data->patientId)
            ->whereKey($data->consentId)
            ->first();

        if (! $model instanceof PatientConsent) {
            throw new DomainException('Patient consent was not found.');
        }

        if ($model->getAttribute('status') === ConsentStatus::REVOKED->value) {
            throw new DomainException('Patient consent is already revoked.');
        }

        $model->fill([
            'status' => ConsentStatus::REVOKED->value,
            'revoked_at' => $data->revokedAt,
            'revoked_by_user_id' => $data->revokedByUserId,
            'revocation_reason' => $data->reason,
        ]);
        $model->save();

        $fresh = $model->fresh();
        if (! $fresh instanceof PatientConsent) {
            throw new RuntimeException('Revoked patient consent could not be reloaded.');
        }

        return $this->mapConsent($fresh);
    }

    public function hasEffectiveConsent(
        PrivacyDecisionContext $context,
        DateTimeInterface $at,
    ): bool {
        return PatientConsent::query()
            ->where('organization_id', $context->organizationId)
            ->where('patient_id', $context->patientId)
            ->where('status', ConsentStatus::ACTIVE->value)
            ->where('data_category', $context->dataCategory->value)
            ->where('purpose', $context->purpose->value)
            ->where('recipient_class', $context->recipientClass->value)
            ->where('effective_from', '<=', $at)
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>', $at);
            })
            ->where(function (Builder $query) use ($context): void {
                $query->whereNull('facility_id')
                    ->orWhere('facility_id', $context->facilityId);
            })
            ->exists();
    }

    public function createBreakGlass(PersistBreakGlassAccessData $data): BreakGlassAccessData
    {
        $model = BreakGlassAccess::query()->create([
            'organization_id' => $data->organizationId,
            'patient_id' => $data->patientId,
            'facility_id' => $data->facilityId,
            'actor_user_id' => $data->actorUserId,
            'purpose' => $data->purpose->value,
            'reason' => $data->reason,
            'activated_at' => $data->activatedAt,
            'expires_at' => $data->expiresAt,
        ]);

        return $this->mapBreakGlass($model);
    }

    public function activeBreakGlass(
        PrivacyDecisionContext $context,
        DateTimeInterface $at,
    ): ?BreakGlassAccessData {
        $model = BreakGlassAccess::query()
            ->where('organization_id', $context->organizationId)
            ->where('patient_id', $context->patientId)
            ->where('facility_id', $context->facilityId)
            ->where('actor_user_id', $context->actorUserId)
            ->where('purpose', $context->purpose->value)
            ->where('activated_at', '<=', $at)
            ->where('expires_at', '>', $at)
            ->latest('activated_at')
            ->first();

        return $model instanceof BreakGlassAccess ? $this->mapBreakGlass($model) : null;
    }

    private function mapConsent(PatientConsent $model): PatientConsentData
    {
        $effectiveFrom = $model->getAttribute('effective_from');
        $createdAt = $model->getAttribute('created_at');
        if (! $effectiveFrom instanceof CarbonInterface || ! $createdAt instanceof CarbonInterface) {
            throw new RuntimeException('Patient consent timestamps are invalid.');
        }

        $effectiveUntil = $model->getAttribute('effective_until');
        $revokedAt = $model->getAttribute('revoked_at');
        $revokedBy = $model->getAttribute('revoked_by_user_id');

        $isEffective = $model->getAttribute('status') === ConsentStatus::ACTIVE->value
            && $effectiveFrom->lessThanOrEqualTo(now())
            && (! $effectiveUntil instanceof CarbonInterface || $effectiveUntil->isFuture());

        return new PatientConsentData(
            id: (string) $model->getKey(),
            organizationId: (string) $model->getAttribute('organization_id'),
            patientId: (string) $model->getAttribute('patient_id'),
            facilityId: $this->nullableString($model->getAttribute('facility_id')),
            dataCategory: (string) $model->getAttribute('data_category'),
            purpose: (string) $model->getAttribute('purpose'),
            recipientClass: (string) $model->getAttribute('recipient_class'),
            status: (string) $model->getAttribute('status'),
            grantedByUserId: (int) $model->getAttribute('granted_by_user_id'),
            effectiveFrom: $effectiveFrom->toIso8601String(),
            effectiveUntil: $effectiveUntil instanceof CarbonInterface
                ? $effectiveUntil->toIso8601String()
                : null,
            revokedAt: $revokedAt instanceof CarbonInterface ? $revokedAt->toIso8601String() : null,
            revokedByUserId: is_int($revokedBy) ? $revokedBy : null,
            revocationReason: $this->nullableString($model->getAttribute('revocation_reason')),
            createdAt: $createdAt->toIso8601String(),
            isEffective: $isEffective,
        );
    }

    private function mapBreakGlass(BreakGlassAccess $model): BreakGlassAccessData
    {
        $activatedAt = $model->getAttribute('activated_at');
        $expiresAt = $model->getAttribute('expires_at');
        if (! $activatedAt instanceof CarbonInterface || ! $expiresAt instanceof CarbonInterface) {
            throw new RuntimeException('Break-glass timestamps are invalid.');
        }

        return new BreakGlassAccessData(
            id: (string) $model->getKey(),
            organizationId: (string) $model->getAttribute('organization_id'),
            patientId: (string) $model->getAttribute('patient_id'),
            facilityId: (string) $model->getAttribute('facility_id'),
            actorUserId: (int) $model->getAttribute('actor_user_id'),
            purpose: (string) $model->getAttribute('purpose'),
            reason: (string) $model->getAttribute('reason'),
            activatedAt: $activatedAt->toIso8601String(),
            expiresAt: $expiresAt->toIso8601String(),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
