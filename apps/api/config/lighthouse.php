<?php

declare(strict_types=1);

use App\GraphQL\Execution\CorrelationIdErrorHandler;
use App\Http\Middleware\EnsureCorrelationId;
use App\Http\Middleware\FlushPendingAuditEvents;
use GraphQL\Validator\Rules\DisableIntrospection;
use Nuwave\Lighthouse\Execution\AuthenticationErrorHandler;
use Nuwave\Lighthouse\Execution\AuthorizationErrorHandler;
use Nuwave\Lighthouse\Execution\ReportingErrorHandler;
use Nuwave\Lighthouse\Execution\ValidationErrorHandler;
use Nuwave\Lighthouse\Http\Middleware\AcceptJson;
use Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication;

return [
    'route' => [
        'uri' => '/graphql',
        'name' => 'graphql',
        'middleware' => [
            EnsureCorrelationId::class,
            FlushPendingAuditEvents::class,
            'web',
            AcceptJson::class,
            AttemptAuthentication::class,
        ],
    ],
    'guards' => ['web'],
    'schema_path' => base_path('graphql/schema.graphql'),
    'query_cache' => [
        'enable' => env(
            'LIGHTHOUSE_QUERY_CACHE_ENABLE',
            ! in_array((string) env('APP_ENV', 'production'), ['local', 'testing'], true),
        ),
        'mode' => env('LIGHTHOUSE_QUERY_CACHE_MODE', 'opcache'),
        'opcache_path' => env('LIGHTHOUSE_QUERY_CACHE_OPCACHE_PATH', base_path('bootstrap/cache')),
        'store' => env('LIGHTHOUSE_QUERY_CACHE_STORE'),
        'ttl' => env('LIGHTHOUSE_QUERY_CACHE_TTL', 24 * 60 * 60),
    ],
    'security' => [
        'max_query_complexity' => (int) env('LIGHTHOUSE_MAX_QUERY_COMPLEXITY', 500),
        'max_query_depth' => (int) env('LIGHTHOUSE_MAX_QUERY_DEPTH', 12),
        'disable_introspection' => (bool) env('LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION', false)
            ? DisableIntrospection::ENABLED
            : DisableIntrospection::DISABLED,
    ],
    'pagination' => [
        'default_count' => 25,
        'max_count' => 100,
    ],
    'error_handlers' => [
        CorrelationIdErrorHandler::class,
        AuthenticationErrorHandler::class,
        AuthorizationErrorHandler::class,
        ValidationErrorHandler::class,
        ReportingErrorHandler::class,
    ],
    'transactional_mutations' => true,
    'force_fill' => false,
    'batchload_relations' => true,
];
