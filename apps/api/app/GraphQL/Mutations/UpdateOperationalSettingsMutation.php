<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Organization\OrganizationConfigurationService;
use App\Domains\Organization\Data\UpdatedOperationalSettingsData;
use App\Domains\Organization\Enums\ConfigurationScope;
use App\GraphQL\Inputs\OperationalSettingsInputMapper;
use App\Support\CorrelationId;
use Illuminate\Http\Request;
use RuntimeException;

final readonly class UpdateOperationalSettingsMutation
{
    public function __construct(
        private OrganizationConfigurationService $configuration,
        private OperationalSettingsInputMapper $settingsMapper,
        private Request $request,
    ) {}

    /**
     * Lighthouse supplies GraphQL arguments as an associative array at the application boundary.
     *
     * @param array{input: array{organizationId: string, scope: string, scopeId: string, settings: array<string, mixed>}} $args
     */
    public function __invoke(mixed $root, array $args): UpdatedOperationalSettingsData
    {
        $input = $args['input'];
        $correlationId = $this->request->attributes->get(CorrelationId::REQUEST_ATTRIBUTE);
        if (! is_string($correlationId) || $correlationId === '') {
            throw new RuntimeException('A correlation ID is required for configuration changes.');
        }

        return $this->configuration->replaceOperationalSettings(
            organizationId: $input['organizationId'],
            scope: ConfigurationScope::from($input['scope']),
            scopeId: $input['scopeId'],
            settings: $this->settingsMapper->map($input['settings']),
            correlationId: $correlationId,
        );
    }
}
