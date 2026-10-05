<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ExpectedBusinessRuleViolation;
use DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;
use PHPUnit\Framework\TestCase;

final class ExpectedBusinessRuleViolationTest extends TestCase
{
    public function test_expected_business_rule_violation_preserves_domain_semantics_without_reporting(): void
    {
        $exception = new ExpectedBusinessRuleViolation('Expected rejection.');

        self::assertInstanceOf(DomainException::class, $exception);
        self::assertInstanceOf(ShouldntReport::class, $exception);
        self::assertSame('Expected rejection.', $exception->getMessage());
    }
}
