<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CorrelationId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCorrelationId
{
    private const int MAX_INBOUND_LENGTH = 128;
    private const string VALID_INBOUND_PATTERN = '/^[A-Za-z0-9._:-]+$/';

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $this->resolveCorrelationId($request);
        $request->attributes->set(CorrelationId::REQUEST_ATTRIBUTE, $correlationId);

        $response = $next($request);
        $response->headers->set(CorrelationId::HEADER, $correlationId);

        return $response;
    }

    private function resolveCorrelationId(Request $request): string
    {
        $candidate = trim((string) $request->headers->get(CorrelationId::HEADER, ''));
        $hasAllowedLength = strlen($candidate) <= self::MAX_INBOUND_LENGTH;
        $hasAllowedCharacters = preg_match(self::VALID_INBOUND_PATTERN, $candidate) === 1;
        $isUsableInboundId = $candidate !== '' && $hasAllowedLength && $hasAllowedCharacters;

        return $isUsableInboundId ? $candidate : (string) Str::ulid();
    }
}
