<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Support\CorrelationId;
use Tests\Support\InteractsWithGraphQL;
use Tests\TestCase;

final class InteroperabilityContractTest extends TestCase
{
    use InteractsWithGraphQL;

    private const string CATALOG = <<<'GRAPHQL'
        query InteroperabilityCatalog {
            terminologySystems {
                system
                name
                versionRequired
                scope
            }
            interoperabilityProfiles {
                code
                standard
                version
                mediaType
            }
        }
        GRAPHQL;

    private const string PREVIEW = <<<'GRAPHQL'
        query InteroperabilityConceptPreview($input: InteroperabilityConceptPreviewInput!) {
            interoperabilityConceptPreview(input: $input) {
                correlationId
                occurredAt
                standard
                standardVersion
                resourceType
                concept {
                    text
                    codings {
                        system
                        version
                        code
                        display
                        userSelected
                    }
                }
                payloadJson
            }
        }
        GRAPHQL;

    public function test_catalog_exposes_versioned_interoperability_contracts(): void
    {
        $response = $this->postGraphQL(self::CATALOG, 'm9-catalog');

        $response
            ->assertOk()
            ->assertHeader(CorrelationId::HEADER, 'm9-catalog')
            ->assertJsonFragment([
                'system' => 'http://snomed.info/sct',
                'name' => 'SNOMED CT',
                'versionRequired' => true,
            ])
            ->assertJsonFragment([
                'code' => 'FHIR_R4',
                'standard' => 'HL7 FHIR',
                'version' => '4.0.1',
                'mediaType' => 'application/fhir+json',
            ]);
    }

    public function test_preview_preserves_multiple_explicit_codings_and_request_correlation(): void
    {
        $response = $this->postGraphQL(self::PREVIEW, 'm9-preview', [
            'input' => [
                'resourceType' => 'CONDITION',
                'concept' => [
                    'text' => 'Synthetic hypertension',
                    'codings' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'version' => '20260731',
                            'code' => '38341003',
                            'display' => 'Hypertensive disorder',
                            'userSelected' => true,
                        ],
                        [
                            'system' => 'http://hl7.org/fhir/sid/icd-10',
                            'version' => '2019',
                            'code' => 'I10',
                            'display' => 'Essential hypertension',
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.interoperabilityConceptPreview.correlationId', 'm9-preview')
            ->assertJsonStructure(['data' => ['interoperabilityConceptPreview' => ['occurredAt']]])
            ->assertJsonPath('data.interoperabilityConceptPreview.standardVersion', '4.0.1')
            ->assertJsonCount(2, 'data.interoperabilityConceptPreview.concept.codings');

        $payloadJson = $response->json('data.interoperabilityConceptPreview.payloadJson');
        self::assertIsString($payloadJson);
        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Condition', $payload['resourceType']);
        self::assertSame('20260731', $payload['code']['coding'][0]['version']);
        self::assertSame('2019', $payload['code']['coding'][1]['version']);
    }

    public function test_custom_absolute_terminology_system_remains_an_extension_point(): void
    {
        $response = $this->postGraphQL(self::PREVIEW, 'm9-custom-system', [
            'input' => [
                'resourceType' => 'OBSERVATION',
                'concept' => [
                    'text' => 'Synthetic partner measurement',
                    'codings' => [[
                        'system' => 'urn:partner:synthetic-terminology',
                        'version' => '2026.10',
                        'code' => 'SYN-001',
                        'display' => 'Synthetic partner measurement',
                        'userSelected' => true,
                    ]],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.interoperabilityConceptPreview.concept.codings.0.system', 'urn:partner:synthetic-terminology')
            ->assertJsonPath('data.interoperabilityConceptPreview.concept.codings.0.version', '2026.10');
    }

    public function test_target_system_selects_existing_mapping_without_silent_translation(): void
    {
        $response = $this->postGraphQL(self::PREVIEW, 'm9-map-existing', [
            'input' => [
                'resourceType' => 'CONDITION',
                'targetSystem' => 'http://hl7.org/fhir/sid/icd-10',
                'concept' => [
                    'text' => 'Synthetic hypertension',
                    'codings' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'version' => '20260731',
                            'code' => '38341003',
                            'display' => 'Hypertensive disorder',
                        ],
                        [
                            'system' => 'http://hl7.org/fhir/sid/icd-10',
                            'version' => '2019',
                            'code' => 'I10',
                            'display' => 'Essential hypertension',
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.interoperabilityConceptPreview.concept.codings')
            ->assertJsonPath('data.interoperabilityConceptPreview.concept.codings.0.code', 'I10');
    }

    public function test_blank_terminology_version_is_rejected_as_an_expected_business_rule(): void
    {
        $response = $this->postGraphQL(self::PREVIEW, 'm9-version-required', [
            'input' => [
                'resourceType' => 'OBSERVATION',
                'concept' => [
                    'text' => 'Synthetic heart rate',
                    'codings' => [[
                        'system' => 'http://loinc.org',
                        'version' => '   ',
                        'code' => '8867-4',
                        'display' => 'Heart rate',
                    ]],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'm9-version-required');
    }
}
