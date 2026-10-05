<?php

declare(strict_types=1);

namespace Tests\Unit\Interoperability;

use App\Domains\Interoperability\Data\InteroperabilityCorrelationData;
use App\Domains\Interoperability\Enums\InteroperabilityClinicalConceptType;
use App\Domains\Terminology\Data\TerminologyCodingData;
use App\Domains\Terminology\Data\TerminologyConceptData;
use App\Infrastructure\Interoperability\FhirR4InteroperabilityMapper;
use PHPUnit\Framework\TestCase;

final class FhirR4InteroperabilityMapperTest extends TestCase
{
    public function test_mapper_preserves_multiple_codings_versions_and_text_at_the_fhir_boundary(): void
    {
        $concept = new TerminologyConceptData('Synthetic hypertension', [
            new TerminologyCodingData('http://snomed.info/sct', '20260731', '38341003', 'Hypertensive disorder', true),
            new TerminologyCodingData('http://hl7.org/fhir/sid/icd-10', '2019', 'I10', 'Essential hypertension'),
        ]);
        $correlation = new InteroperabilityCorrelationData('m9-unit-correlation', '2026-10-05T00:00:00+00:00');

        $preview = (new FhirR4InteroperabilityMapper())->previewConcept(
            InteroperabilityClinicalConceptType::CONDITION,
            $concept,
            $correlation,
        );
        $payload = json_decode($preview->payloadJson, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('4.0.1', $preview->standardVersion);
        self::assertSame('m9-unit-correlation', $preview->correlationId);
        self::assertSame('2026-10-05T00:00:00+00:00', $preview->occurredAt);
        self::assertSame('Condition', $payload['resourceType']);
        self::assertSame('Synthetic hypertension', $payload['code']['text']);
        self::assertCount(2, $payload['code']['coding']);
        self::assertSame('20260731', $payload['code']['coding'][0]['version']);
        self::assertSame('2019', $payload['code']['coding'][1]['version']);
    }
}
