<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Data;

final readonly class BreakGlassAccessData
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $patientId,
        public string $facilityId,
        public int $actorUserId,
        public string $purpose,
        public string $reason,
        public string $activatedAt,
        public string $expiresAt,
    ) {}
}
