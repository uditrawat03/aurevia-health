<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class ClinicalNoteData
{
    /** @param list<ClinicalNoteAmendmentData> $amendments */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $facilityId,
        public string $patientId,
        public string $encounterId,
        public string $noteType,
        public ?string $title,
        public string $body,
        public string $status,
        public int $authorUserId,
        public ?int $signedByUserId,
        public ?string $signedAt,
        public string $createdAt,
        public string $updatedAt,
        public array $amendments,
    ) {}
}
