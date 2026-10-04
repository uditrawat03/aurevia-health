<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use Tests\TestCase;

final class GraphQLBoundaryTest extends TestCase
{
    public function test_legacy_default_api_user_route_is_not_registered(): void
    {
        $this->getJson('/api/user')->assertNotFound();
    }
}
