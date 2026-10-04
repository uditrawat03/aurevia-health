<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Data;

final readonly class PrivacyDecisionData
{
    public function __construct(
        public bool $allowed,
        public string $reason,
        public ?string $breakGlassAccessId = null,
    ) {}
}
