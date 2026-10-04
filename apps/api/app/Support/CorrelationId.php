<?php

declare(strict_types=1);

namespace App\Support;

final class CorrelationId
{
    public const string HEADER = 'X-Correlation-ID';
    public const string REQUEST_ATTRIBUTE = 'aurevia.correlation_id';
    public const string GRAPHQL_EXTENSION = 'correlationId';
}
