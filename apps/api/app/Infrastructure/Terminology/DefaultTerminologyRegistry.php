<?php

declare(strict_types=1);

namespace App\Infrastructure\Terminology;

use App\Domains\Terminology\Data\TerminologySystemData;
use App\Domains\Terminology\Registry\TerminologyRegistry;

final readonly class DefaultTerminologyRegistry implements TerminologyRegistry
{
    /** @return list<TerminologySystemData> */
    public function systems(): array
    {
        return [
            new TerminologySystemData(
                system: 'http://snomed.info/sct',
                name: 'SNOMED CT',
                versionRequired: true,
                scope: 'Clinical concepts',
            ),
            new TerminologySystemData(
                system: 'http://loinc.org',
                name: 'LOINC',
                versionRequired: true,
                scope: 'Observations and measurements',
            ),
            new TerminologySystemData(
                system: 'http://hl7.org/fhir/sid/icd-10',
                name: 'ICD-10 International',
                versionRequired: true,
                scope: 'Classification and reporting',
            ),
            new TerminologySystemData(
                system: 'urn:aurevia:local',
                name: 'Aurevia local / organization coding',
                versionRequired: true,
                scope: 'Organization-defined concepts',
            ),
        ];
    }

    public function isRecognized(string $system): bool
    {
        foreach ($this->systems() as $known) {
            if ($known->system === $system) {
                return true;
            }
        }

        return false;
    }
}
