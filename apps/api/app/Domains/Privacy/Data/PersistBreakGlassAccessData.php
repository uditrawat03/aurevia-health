<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Data;

use App\Domains\Privacy\Enums\ConsentPurpose;

final readonly class PersistBreakGlassAccessData
{
    public function __construct(
        public string $organizationId,
        public string $patientId,
        public string $facilityId,
        public int $actorUserId,
        public ConsentPurpose $purpose,
        public string $reason,
        public string $activatedAt,
        public string $expiresAt,
    ) {}
}
