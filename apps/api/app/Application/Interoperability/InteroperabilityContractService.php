<?php

declare(strict_types=1);

namespace App\Application\Interoperability;

use App\Domains\Interoperability\Contracts\InteroperabilityMapper;
use App\Domains\Interoperability\Data\InteroperabilityConceptPreviewData;
use App\Domains\Interoperability\Data\InteroperabilityCorrelationData;
use App\Domains\Interoperability\Data\InteroperabilityProfileData;
use App\Domains\Interoperability\Enums\InteroperabilityClinicalConceptType;
use App\Domains\Terminology\Data\TerminologyCodingData;
use App\Domains\Terminology\Data\TerminologyConceptData;
use App\Domains\Terminology\Data\TerminologySystemData;
use App\Domains\Terminology\Mapping\TerminologyMappingContract;
use App\Domains\Terminology\Registry\TerminologyRegistry;
use App\Exceptions\ExpectedBusinessRuleViolation;
use Carbon\CarbonImmutable;

final readonly class InteroperabilityContractService
{
    private const int MAX_CODINGS = 16;
    private const int MAX_TEXT_LENGTH = 1000;
    private const int MAX_SYSTEM_LENGTH = 512;
    private const int MAX_VERSION_LENGTH = 128;
    private const int MAX_CODE_LENGTH = 256;
    private const int MAX_DISPLAY_LENGTH = 1000;
    private const string URI_SCHEME_PATTERN = '/^[A-Za-z][A-Za-z0-9+.-]*:[^\\s]+$/';

    public function __construct(
        private TerminologyRegistry $terminology,
        private TerminologyMappingContract $mapping,
        private InteroperabilityMapper $mapper,
    ) {}

    /** @return list<TerminologySystemData> */
    public function terminologySystems(): array
    {
        return $this->terminology->systems();
    }

    /** @return list<InteroperabilityProfileData> */
    public function profiles(): array
    {
        return [$this->mapper->profile()];
    }

    /**
     * @param array{
     *   text: string,
     *   codings: list<array{system: string, version: string, code: string, display: string, userSelected?: bool|null}>
     * } $conceptInput
     */
    public function previewConcept(
        InteroperabilityClinicalConceptType $resourceType,
        array $conceptInput,
        ?string $targetSystem,
        string $correlationId,
    ): InteroperabilityConceptPreviewData
    {
        $concept = $this->concept($conceptInput);

        if ($targetSystem !== null) {
            $targetSystem = $this->requiredText($targetSystem, 'Target terminology system', self::MAX_SYSTEM_LENGTH);
            $this->assertSystemUri($targetSystem);
            $concept = $this->mapping->map($concept, $targetSystem);
        }

        return $this->mapper->previewConcept(
            resourceType: $resourceType,
            concept: $concept,
            correlation: new InteroperabilityCorrelationData(
                correlationId: $this->requiredText($correlationId, 'Correlation ID', 128),
                occurredAt: CarbonImmutable::now('UTC')->toIso8601String(),
            ),
        );
    }

    /**
     * @param array{
     *   text: string,
     *   codings: list<array{system: string, version: string, code: string, display: string, userSelected?: bool|null}>
     * } $input
     */
    private function concept(array $input): TerminologyConceptData
    {
        $text = $this->requiredText($input['text'], 'Concept text', self::MAX_TEXT_LENGTH);
        $codingInputs = $input['codings'];

        if ($codingInputs === []) {
            throw new ExpectedBusinessRuleViolation('At least one terminology coding is required for this interoperability contract.');
        }
        if (count($codingInputs) > self::MAX_CODINGS) {
            throw new ExpectedBusinessRuleViolation(sprintf(
                'A terminology concept may contain at most %d codings.',
                self::MAX_CODINGS,
            ));
        }

        $codings = [];
        $seen = [];
        $userSelectedCount = 0;

        foreach ($codingInputs as $inputCoding) {
            $system = $this->requiredText($inputCoding['system'], 'Coding system', self::MAX_SYSTEM_LENGTH);
            $version = $this->requiredText($inputCoding['version'], 'Coding version', self::MAX_VERSION_LENGTH);
            $code = $this->requiredText($inputCoding['code'], 'Coding code', self::MAX_CODE_LENGTH);
            $display = $this->requiredText($inputCoding['display'], 'Coding display', self::MAX_DISPLAY_LENGTH);
            $userSelected = (bool) ($inputCoding['userSelected'] ?? false);

            $this->assertSystemUri($system);

            $key = $system."\0".$version."\0".$code;
            if (isset($seen[$key])) {
                throw new ExpectedBusinessRuleViolation('Duplicate terminology codings are not allowed.');
            }
            $seen[$key] = true;

            if ($userSelected) {
                ++$userSelectedCount;
                if ($userSelectedCount > 1) {
                    throw new ExpectedBusinessRuleViolation('A terminology concept may have at most one user-selected coding.');
                }
            }

            $codings[] = new TerminologyCodingData(
                system: $system,
                version: $version,
                code: $code,
                display: $display,
                userSelected: $userSelected,
            );
        }

        return new TerminologyConceptData(text: $text, codings: $codings);
    }

    private function assertSystemUri(string $system): void
    {
        if (preg_match(self::URI_SCHEME_PATTERN, $system) !== 1) {
            throw new ExpectedBusinessRuleViolation('Coding systems must be identified by an absolute URI or URN.');
        }
    }

    private function requiredText(string $value, string $field, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new ExpectedBusinessRuleViolation($field.' is required.');
        }
        if (mb_strlen($value) > $maxLength) {
            throw new ExpectedBusinessRuleViolation($field.' is too long.');
        }

        return $value;
    }
}
