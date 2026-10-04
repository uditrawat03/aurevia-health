<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Domains\Organization\Enums\ConfigurationSource;
use App\Domains\Organization\Enums\WeekStart;
use App\Models\User;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class OrganizationFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private const string CREATE_ORGANIZATION_MUTATION = <<<'GRAPHQL'
        mutation CreateOrganization($input: CreateOrganizationInput!) {
            createOrganization(input: $input) {
                id
                name
                slug
                countryCode
                countryProfileCode
                countryProfileVersion
            }
        }
        GRAPHQL;

    private const string CREATE_FACILITY_MUTATION = <<<'GRAPHQL'
        mutation CreateFacility($input: CreateFacilityInput!) {
            createFacility(input: $input) {
                id
                organizationId
                name
                code
                settings {
                    timezone
                }
            }
        }
        GRAPHQL;

    private const string RESOLVED_SETTINGS_QUERY = <<<'GRAPHQL'
        query ResolvedOperationalSettings($input: ResolveOperationalSettingsInput!) {
            resolvedOperationalSettings(input: $input) {
                locale
                timezone
                weekStartsOn
                localeSource
                timezoneSource
                weekStartsOnSource
                countryProfile {
                    countryCode
                    profileCode
                    version
                }
            }
        }
        GRAPHQL;

    private const string UPDATE_SETTINGS_MUTATION = <<<'GRAPHQL'
        mutation UpdateOperationalSettings($input: UpdateOperationalSettingsInput!) {
            updateOperationalSettings(input: $input) {
                scope
                scopeId
                settings {
                    locale
                    timezone
                    weekStartsOn
                }
            }
        }
        GRAPHQL;

    public function test_graphql_creates_country_neutral_hierarchy_and_resolves_profile_settings(): void
    {
        $createOrganization = $this->postGraphQL(
            self::CREATE_ORGANIZATION_MUTATION,
            'test-create-organization',
            [
                'input' => [
                    'name' => 'Aurevia India',
                    'slug' => 'aurevia-india',
                    'countryCode' => 'IN',
                ],
            ],
        );

        $createOrganization
            ->assertOk()
            ->assertJsonPath('data.createOrganization.name', 'Aurevia India')
            ->assertJsonPath('data.createOrganization.countryCode', 'IN')
            ->assertJsonPath('data.createOrganization.countryProfileCode', 'IN')
            ->assertJsonPath('data.createOrganization.countryProfileVersion', '1.0.0');

        $organizationId = $createOrganization->json('data.createOrganization.id');
        self::assertIsString($organizationId);

        $createFacility = $this->postGraphQL(
            self::CREATE_FACILITY_MUTATION,
            'test-create-facility',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'name' => 'Ujjain Synthetic Hospital',
                    'code' => 'UJN-SYNTH',
                    'settings' => [
                        'timezone' => 'Asia/Kolkata',
                    ],
                ],
            ],
        );

        $createFacility
            ->assertOk()
            ->assertJsonPath('data.createFacility.organizationId', $organizationId)
            ->assertJsonPath('data.createFacility.settings.timezone', 'Asia/Kolkata');

        $facilityId = $createFacility->json('data.createFacility.id');
        self::assertIsString($facilityId);

        $resolved = $this->postGraphQL(
            self::RESOLVED_SETTINGS_QUERY,
            'test-resolve-settings',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityId,
                ],
            ],
        );

        $resolved
            ->assertOk()
            ->assertJsonPath('data.resolvedOperationalSettings.locale', 'en-IN')
            ->assertJsonPath('data.resolvedOperationalSettings.localeSource', ConfigurationSource::COUNTRY_PROFILE->value)
            ->assertJsonPath('data.resolvedOperationalSettings.timezone', 'Asia/Kolkata')
            ->assertJsonPath('data.resolvedOperationalSettings.timezoneSource', ConfigurationSource::FACILITY->value)
            ->assertJsonPath('data.resolvedOperationalSettings.weekStartsOn', WeekStart::MONDAY->value)
            ->assertJsonPath('data.resolvedOperationalSettings.countryProfile.profileCode', 'IN');
    }

    public function test_graphql_configuration_mutation_records_auditable_change(): void
    {
        $createOrganization = $this->postGraphQL(
            self::CREATE_ORGANIZATION_MUTATION,
            'test-create-organization-for-settings',
            [
                'input' => [
                    'name' => 'Aurevia Configuration',
                    'slug' => 'aurevia-configuration',
                    'countryCode' => 'GB',
                ],
            ],
        );

        $organizationId = $createOrganization->json('data.createOrganization.id');
        self::assertIsString($organizationId);
        $correlationId = 'test-update-operational-settings';

        $updated = $this->postGraphQL(
            self::UPDATE_SETTINGS_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'scope' => 'ORGANIZATION',
                    'scopeId' => $organizationId,
                    'settings' => [
                        'locale' => 'cy-GB',
                        'timezone' => 'Europe/London',
                        'weekStartsOn' => 'MONDAY',
                    ],
                ],
            ],
        );

        $updated
            ->assertOk()
            ->assertJsonPath('data.updateOperationalSettings.scope', 'ORGANIZATION')
            ->assertJsonPath('data.updateOperationalSettings.scopeId', $organizationId)
            ->assertJsonPath('data.updateOperationalSettings.settings.locale', 'cy-GB');

        $this->assertDatabaseHas('configuration_changes', [
            'organization_id' => $organizationId,
            'scope_type' => 'ORGANIZATION',
            'setting_key' => 'locale',
            'new_value' => 'cy-GB',
            'correlation_id' => $correlationId,
        ]);
    }
}
