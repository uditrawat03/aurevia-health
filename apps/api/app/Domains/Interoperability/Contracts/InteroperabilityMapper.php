<?php

declare(strict_types=1);

namespace App\Domains\Interoperability\Contracts;

use App\Domains\Interoperability\Data\InteroperabilityConceptPreviewData;
use App\Domains\Interoperability\Data\InteroperabilityCorrelationData;
use App\Domains\Interoperability\Data\InteroperabilityProfileData;
use App\Domains\Interoperability\Enums\InteroperabilityClinicalConceptType;
use App\Domains\Terminology\Data\TerminologyConceptData;

interface InteroperabilityMapper
{
    public function profile(): InteroperabilityProfileData;

    public function previewConcept(
        InteroperabilityClinicalConceptType $resourceType,
        TerminologyConceptData $concept,
        InteroperabilityCorrelationData $correlation,
    ): InteroperabilityConceptPreviewData;
}
