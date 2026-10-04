<?php

declare(strict_types=1);

namespace App\GraphQL\Execution;

use App\Support\CorrelationId;
use Closure;
use GraphQL\Error\Error;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\Execution\ErrorHandler;

final readonly class CorrelationIdErrorHandler implements ErrorHandler
{
    public function __construct(private Request $request) {}

    /**
     * Lighthouse's ErrorHandler contract requires an associative error array at this framework boundary.
     *
     * @param Closure(?Error): (array<string, mixed>|null) $next
     * @return array<string, mixed>|null
     */
    public function __invoke(?Error $error, Closure $next): ?array
    {
        $formattedError = $next($error);
        if ($formattedError === null) {
            return null;
        }

        $correlationId = $this->request->attributes->get(CorrelationId::REQUEST_ATTRIBUTE);
        $hasCorrelationId = is_string($correlationId) && $correlationId !== '';
        if (! $hasCorrelationId) {
            return $formattedError;
        }

        $extensions = $formattedError['extensions'] ?? [];
        $extensions = is_array($extensions) ? $extensions : [];
        $extensions[CorrelationId::GRAPHQL_EXTENSION] = $correlationId;
        $formattedError['extensions'] = $extensions;

        return $formattedError;
    }
}
