<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Data;

use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;

final readonly class PersistPatientConsentData
{
    public function __construct(
        public string $organizationId,
        public string $patientId,
        public ?string $facilityId,
        public ConsentDataCategory $dataCategory,
        public ConsentPurpose $purpose,
        public ConsentRecipientClass $recipientClass,
        public int $grantedByUserId,
        public string $effectiveFrom,
        public ?string $effectiveUntil,
    ) {}
}
