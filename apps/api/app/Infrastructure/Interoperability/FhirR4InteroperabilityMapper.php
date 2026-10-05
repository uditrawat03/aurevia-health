<?php

declare(strict_types=1);

namespace App\Infrastructure\Interoperability;

use App\Domains\Interoperability\Contracts\InteroperabilityMapper;
use App\Domains\Interoperability\Data\InteroperabilityConceptPreviewData;
use App\Domains\Interoperability\Data\InteroperabilityCorrelationData;
use App\Domains\Interoperability\Data\InteroperabilityProfileData;
use App\Domains\Interoperability\Enums\InteroperabilityClinicalConceptType;
use App\Domains\Terminology\Data\TerminologyCodingData;
use App\Domains\Terminology\Data\TerminologyConceptData;
use JsonException;

final readonly class FhirR4InteroperabilityMapper implements InteroperabilityMapper
{
    public const string PROFILE_CODE = 'FHIR_R4';
    public const string FHIR_VERSION = '4.0.1';

    public function profile(): InteroperabilityProfileData
    {
        return new InteroperabilityProfileData(
            code: self::PROFILE_CODE,
            standard: 'HL7 FHIR',
            version: self::FHIR_VERSION,
            mediaType: 'application/fhir+json',
        );
    }

    /** @throws JsonException */
    public function previewConcept(
        InteroperabilityClinicalConceptType $resourceType,
        TerminologyConceptData $concept,
        InteroperabilityCorrelationData $correlation,
    ): InteroperabilityConceptPreviewData
    {
        $payload = [
            'resourceType' => $resourceType->fhirResourceType(),
            'code' => [
                'coding' => array_map(
                    static fn (TerminologyCodingData $coding): array => [
                        'system' => $coding->system,
                        'version' => $coding->version,
                        'code' => $coding->code,
                        'display' => $coding->display,
                        'userSelected' => $coding->userSelected,
                    ],
                    $concept->codings,
                ),
                'text' => $concept->text,
            ],
        ];

        return new InteroperabilityConceptPreviewData(
            correlationId: $correlation->correlationId,
            occurredAt: $correlation->occurredAt,
            standard: $this->profile()->standard,
            standardVersion: $this->profile()->version,
            resourceType: $resourceType->value,
            concept: $concept,
            payloadJson: json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }
}
