<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Support\CorrelationId;
use Tests\Support\InteractsWithGraphQL;
use Tests\TestCase;

final class SystemInfoQueryTest extends TestCase
{
    use InteractsWithGraphQL;

    private const string SYSTEM_INFO_QUERY = <<<'GRAPHQL'
        query SystemInfo {
            systemInfo {
                name
                version
                graphqlEndpoint
            }
        }
        GRAPHQL;

    public function test_system_info_is_exposed_through_graphql(): void
    {
        $correlationId = 'test-system-info';

        $response = $this->postGraphQL(self::SYSTEM_INFO_QUERY, $correlationId);

        $response
            ->assertOk()
            ->assertHeader(CorrelationId::HEADER, $correlationId)
            ->assertJson([
                'data' => [
                    'systemInfo' => [
                        'name' => 'Aurevia Health',
                        'version' => '0.1.0-dev',
                        'graphqlEndpoint' => '/graphql',
                    ],
                ],
            ]);
    }

    public function test_graphql_errors_include_the_request_correlation_id(): void
    {
        $correlationId = 'test-graphql-error';

        $response = $this->postGraphQL('query { fieldThatDoesNotExist }', $correlationId);

        $response
            ->assertOk()
            ->assertHeader(CorrelationId::HEADER, $correlationId)
            ->assertJsonPath('errors.0.extensions.correlationId', $correlationId);
    }

    public function test_malformed_inbound_correlation_id_is_replaced(): void
    {
        $response = $this->postGraphQL(
            self::SYSTEM_INFO_QUERY,
            'invalid correlation id with spaces',
        );

        $effectiveCorrelationId = $response->headers->get(CorrelationId::HEADER);

        self::assertIsString($effectiveCorrelationId);
        self::assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $effectiveCorrelationId);
    }

    public function test_graphql_post_requires_csrf_protection(): void
    {
        // Laravel bypasses request-forgery checks while the application environment is "testing".
        // Switch only this request to a non-test environment so the middleware itself is exercised.
        $this->app->detectEnvironment(static fn (): string => 'local');

        try {
            $response = $this
                ->withHeader(CorrelationId::HEADER, 'test-csrf-rejection')
                ->postJson('/graphql', ['query' => self::SYSTEM_INFO_QUERY]);

            $response
                ->assertStatus(419)
                ->assertHeader(CorrelationId::HEADER, 'test-csrf-rejection');
        } finally {
            $this->app->detectEnvironment(static fn (): string => 'testing');
        }
    }
}
