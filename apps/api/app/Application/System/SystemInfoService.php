<?php

declare(strict_types=1);

namespace App\Application\System;

use Illuminate\Contracts\Config\Repository;

final readonly class SystemInfoService implements SystemInfoProvider
{
    private const string PRODUCT_NAME_CONFIG = 'aurevia.product_name';
    private const string VERSION_CONFIG = 'aurevia.version';
    private const string GRAPHQL_ENDPOINT_CONFIG = 'aurevia.graphql_endpoint';
    private const string DEFAULT_PRODUCT_NAME = 'Aurevia Health';
    private const string DEFAULT_VERSION = '0.1.0-dev';
    private const string DEFAULT_GRAPHQL_ENDPOINT = '/graphql';

    public function __construct(private Repository $config) {}

    public function get(): SystemInfo
    {
        return new SystemInfo(
            name: $this->stringConfig(self::PRODUCT_NAME_CONFIG, self::DEFAULT_PRODUCT_NAME),
            version: $this->stringConfig(self::VERSION_CONFIG, self::DEFAULT_VERSION),
            graphqlEndpoint: $this->stringConfig(self::GRAPHQL_ENDPOINT_CONFIG, self::DEFAULT_GRAPHQL_ENDPOINT),
        );
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = $this->config->get($key, $default);
        $isUsableString = is_string($value) && $value !== '';

        return $isUsableString ? $value : $default;
    }
}
