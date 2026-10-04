<?php

declare(strict_types=1);

namespace App\Application\Patient;

final readonly class PatientIdentityNormalizer
{
    public function normalizeName(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return is_string($normalized) ? $normalized : '';
    }

    public function normalizeIdentifier(string $value): string
    {
        $upper = mb_strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9]/u', '', $upper);

        return is_string($normalized) ? $normalized : '';
    }
}
