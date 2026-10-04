<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

abstract class IntegrationTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $exitCode = Artisan::call('migrate', ['--force' => true]);
        if ($exitCode !== 0) {
            throw new RuntimeException('Integration test migrations failed: '.Artisan::output());
        }
    }
}
