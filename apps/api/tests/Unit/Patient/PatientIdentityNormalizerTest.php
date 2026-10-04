<?php

declare(strict_types=1);

namespace Tests\Unit\Patient;

use App\Application\Patient\PatientIdentityNormalizer;
use PHPUnit\Framework\TestCase;

final class PatientIdentityNormalizerTest extends TestCase
{
    public function test_names_are_trimmed_lowercased_and_whitespace_collapsed(): void
    {
        $normalizer = new PatientIdentityNormalizer();

        self::assertSame('asha mehta', $normalizer->normalizeName('  Asha   Mehta  '));
    }

    public function test_identifiers_are_normalized_for_mpi_matching(): void
    {
        $normalizer = new PatientIdentityNormalizer();

        self::assertSame('MRN0001', $normalizer->normalizeIdentifier(' mrn-0001 '));
    }
}
