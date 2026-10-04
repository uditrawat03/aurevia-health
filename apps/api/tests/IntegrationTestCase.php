<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class IntegrationTestCase extends TestCase
{
    use DatabaseTransactions;
}
