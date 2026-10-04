<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Audit\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class FlushPendingAuditEvents
{
    public function __construct(private AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } finally {
            $this->audit->flushDeferredDenials();
        }

        return $response;
    }
}
