<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Application\Interoperability\InteroperabilityContractService;
use App\Domains\Interoperability\Data\InteroperabilityConceptPreviewData;
use App\Domains\Interoperability\Enums\InteroperabilityClinicalConceptType;
use App\Support\CorrelationId;
use Illuminate\Http\Request;

final readonly class InteroperabilityConceptPreviewQuery
{
    public function __construct(
        private InteroperabilityContractService $contracts,
        private Request $request,
    ) {}

    /**
     * @param array{
     *   input: array{
     *     resourceType: string,
     *     concept: array{
     *       text: string,
     *       codings: list<array{system: string, version: string, code: string, display: string, userSelected?: bool|null}>
     *     },
     *     targetSystem?: string|null
     *   }
     * } $args
     */
    public function __invoke(mixed $root, array $args): InteroperabilityConceptPreviewData
    {
        $input = $args['input'];
        $correlationId = (string) $this->request->attributes->get(CorrelationId::REQUEST_ATTRIBUTE, '');

        return $this->contracts->previewConcept(
            resourceType: InteroperabilityClinicalConceptType::from($input['resourceType']),
            conceptInput: $input['concept'],
            targetSystem: $input['targetSystem'] ?? null,
            correlationId: $correlationId,
        );
    }
}
