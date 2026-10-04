<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Data;

final readonly class ClinicalNoteAmendmentData
{
    public function __construct(
        public string $id,
        public string $type,
        public string $body,
        public ?string $reason,
        public int $authorUserId,
        public string $createdAt,
    ) {}
}
