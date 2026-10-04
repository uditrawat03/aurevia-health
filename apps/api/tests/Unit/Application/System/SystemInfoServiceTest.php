<?php

declare(strict_types=1);

namespace Tests\Unit\Application\System;

use App\Application\System\SystemInfoService;
use Illuminate\Contracts\Config\Repository;
use PHPUnit\Framework\TestCase;

final class SystemInfoServiceTest extends TestCase
{
    public function test_it_builds_typed_system_info_from_configuration(): void
    {
        $config = $this->createStub(Repository::class);
        $config->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => match ($key) {
                'aurevia.product_name' => 'Aurevia Health',
                'aurevia.version' => '1.2.3-test',
                'aurevia.graphql_endpoint' => '/graphql',
                default => $default,
            },
        );

        $systemInfo = (new SystemInfoService($config))->get();

        self::assertSame('Aurevia Health', $systemInfo->name);
        self::assertSame('1.2.3-test', $systemInfo->version);
        self::assertSame('/graphql', $systemInfo->graphqlEndpoint);
    }
}
