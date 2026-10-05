<?php

declare(strict_types=1);

namespace Tests\Unit\Terminology;

use App\Domains\Terminology\Data\TerminologyCodingData;
use App\Domains\Terminology\Data\TerminologyConceptData;
use App\Exceptions\ExpectedBusinessRuleViolation;
use App\Infrastructure\Terminology\ExistingCodingTerminologyMapping;
use PHPUnit\Framework\TestCase;

final class ExistingCodingTerminologyMappingTest extends TestCase
{
    public function test_existing_target_coding_can_be_selected_without_inventing_a_translation(): void
    {
        $concept = new TerminologyConceptData('Synthetic hypertension', [
            new TerminologyCodingData('http://snomed.info/sct', '20260731', '38341003', 'Hypertensive disorder', true),
            new TerminologyCodingData('http://hl7.org/fhir/sid/icd-10', '2019', 'I10', 'Essential hypertension'),
        ]);

        $mapped = (new ExistingCodingTerminologyMapping())->map(
            $concept,
            'http://hl7.org/fhir/sid/icd-10',
        );

        self::assertCount(1, $mapped->codings);
        self::assertSame('I10', $mapped->codings[0]->code);
        self::assertSame('2019', $mapped->codings[0]->version);
    }

    public function test_missing_target_mapping_is_rejected_instead_of_guessed(): void
    {
        $concept = new TerminologyConceptData('Synthetic observation', [
            new TerminologyCodingData('http://loinc.org', '2.81', '8867-4', 'Heart rate', true),
        ]);

        $this->expectException(ExpectedBusinessRuleViolation::class);
        $this->expectExceptionMessage('No terminology mapping is configured');

        (new ExistingCodingTerminologyMapping())->map($concept, 'http://snomed.info/sct');
    }
}
