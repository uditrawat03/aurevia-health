<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\CorrelationId;
use Illuminate\Testing\TestResponse;

trait InteractsWithGraphQL
{
    private const string GRAPHQL_CSRF_TOKEN = 'aurevia-test-csrf-token';

    /**
     * Test helpers intentionally accept GraphQL variables in their framework-native associative shape.
     *
     * @param array<string, mixed>|null $variables
     */
    protected function postGraphQL(
        string $query,
        string $correlationId,
        ?array $variables = null,
    ): TestResponse {
        $body = ['query' => $query];
        if ($variables !== null) {
            $body['variables'] = $variables;
        }

        return $this
            ->withSession(['_token' => self::GRAPHQL_CSRF_TOKEN])
            ->withHeaders([
                'X-CSRF-TOKEN' => self::GRAPHQL_CSRF_TOKEN,
                CorrelationId::HEADER => $correlationId,
            ])
            ->postJson('/graphql', $body);
    }
}
