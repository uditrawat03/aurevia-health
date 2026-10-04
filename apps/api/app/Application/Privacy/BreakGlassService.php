<?php

declare(strict_types=1);

namespace App\Application\Privacy;

use App\Application\Audit\AuditService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Privacy\Data\BreakGlassAccessData;
use App\Domains\Privacy\Data\PersistBreakGlassAccessData;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Repositories\PrivacyRepo;
use Carbon\CarbonImmutable;
use DomainException;

final readonly class BreakGlassService
{
    public const int ACCESS_MINUTES = 15;

    public function __construct(
        private PrivacyRepo $privacy,
        private AuditService $audit,
    ) {}

    public function activate(
        int $actorUserId,
        string $organizationId,
        string $patientId,
        string $facilityId,
        ConsentPurpose $purpose,
        string $reason,
    ): BreakGlassAccessData {
        if ($purpose !== ConsentPurpose::TREATMENT) {
            throw new DomainException('Break-glass access is restricted to treatment purpose.');
        }

        $normalizedReason = trim($reason);
        if (mb_strlen($normalizedReason) < 12) {
            throw new DomainException('Break-glass access requires a specific reason of at least 12 characters.');
        }

        $activatedAt = CarbonImmutable::now();
        $access = $this->privacy->createBreakGlass(new PersistBreakGlassAccessData(
            organizationId: $organizationId,
            patientId: $patientId,
            facilityId: $facilityId,
            actorUserId: $actorUserId,
            purpose: $purpose,
            reason: $normalizedReason,
            activatedAt: $activatedAt->toIso8601String(),
            expiresAt: $activatedAt->addMinutes(self::ACCESS_MINUTES)->toIso8601String(),
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $organizationId,
            facilityId: $facilityId,
            patientId: $patientId,
            action: AuditAction::ACTIVATE_BREAK_GLASS,
            resourceType: AuditResourceType::BREAK_GLASS_ACCESS,
            resourceId: $access->id,
        );

        return $access;
    }
}
