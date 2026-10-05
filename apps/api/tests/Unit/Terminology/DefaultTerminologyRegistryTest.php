<?php

declare(strict_types=1);

namespace Tests\Unit\Terminology;

use App\Infrastructure\Terminology\DefaultTerminologyRegistry;
use PHPUnit\Framework\TestCase;

final class DefaultTerminologyRegistryTest extends TestCase
{
    public function test_registry_exposes_recommended_systems_without_becoming_a_closed_whitelist(): void
    {
        $registry = new DefaultTerminologyRegistry();

        self::assertTrue($registry->isRecognized('http://snomed.info/sct'));
        self::assertTrue($registry->isRecognized('http://loinc.org'));
        self::assertFalse($registry->isRecognized('urn:partner:custom-terminology'));
        self::assertTrue($registry->systems()[0]->versionRequired);
    }
}
