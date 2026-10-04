<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Domains\Organization\Data\ConfigurationChangeEntry;
use App\Domains\Organization\Data\ConfigurationChangeSet;
use App\Domains\Organization\Data\UpdatedOperationalSettingsData;
use App\Domains\Organization\Enums\ConfigurationScope;
use App\Domains\Organization\Enums\OperationalSettingKey;
use App\Domains\Organization\Repositories\OrganizationRepo;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;
use DomainException;

final readonly class OrganizationConfigurationService
{
    public function __construct(private OrganizationRepo $organizations) {}

    public function replaceOperationalSettings(
        string $organizationId,
        ConfigurationScope $scope,
        string $scopeId,
        OperationalSettingsOverride $settings,
        string $correlationId,
    ): UpdatedOperationalSettingsData {
        $current = $this->organizations->findScopedSettings($organizationId, $scope, $scopeId);
        if ($current === null) {
            throw new DomainException('Configuration scope does not belong to the organization.');
        }

        $changes = $this->changesBetween($current->settings, $settings);
        $hasChanges = $changes->entries !== [];
        if (! $hasChanges) {
            return new UpdatedOperationalSettingsData(
                scope: $scope->value,
                scopeId: $scopeId,
                settings: $current->settings,
            );
        }

        $updated = $this->organizations->replaceSettingsAndAudit(
            $current,
            $settings,
            $changes,
            $correlationId,
        );

        return new UpdatedOperationalSettingsData(
            scope: $updated->scope->value,
            scopeId: $updated->scopeId,
            settings: $updated->settings,
        );
    }

    private function changesBetween(
        OperationalSettingsOverride $before,
        OperationalSettingsOverride $after,
    ): ConfigurationChangeSet {
        $entries = [];

        foreach (OperationalSettingKey::cases() as $key) {
            $previousValue = $before->valueFor($key);
            $newValue = $after->valueFor($key);
            $hasChanged = $previousValue !== $newValue;

            if ($hasChanged) {
                $entries[] = new ConfigurationChangeEntry($key, $previousValue, $newValue);
            }
        }

        return new ConfigurationChangeSet($entries);
    }
}
