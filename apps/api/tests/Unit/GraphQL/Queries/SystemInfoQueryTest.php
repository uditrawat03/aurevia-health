<?php

declare(strict_types=1);

namespace Tests\Unit\GraphQL\Queries;

use App\Application\System\SystemInfo;
use App\Application\System\SystemInfoProvider;
use App\GraphQL\Queries\SystemInfoQuery;
use PHPUnit\Framework\TestCase;

final class SystemInfoQueryTest extends TestCase
{
    public function test_it_delegates_to_the_application_provider(): void
    {
        $expected = new SystemInfo('Aurevia Health', '1.2.3-test', '/graphql');
        $provider = $this->createMock(SystemInfoProvider::class);
        $provider->expects(self::once())->method('get')->willReturn($expected);

        $result = (new SystemInfoQuery($provider))();

        self::assertSame($expected, $result);
    }
}
