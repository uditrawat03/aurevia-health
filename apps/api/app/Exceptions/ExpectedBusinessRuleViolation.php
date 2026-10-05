<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;

/**
 * An expected rejection caused by an application or domain business rule.
 *
 * The client still receives the normal GraphQL error response, while Laravel
 * does not report the rejection as an application/server error.
 */
final class ExpectedBusinessRuleViolation extends DomainException implements ShouldntReport {}
