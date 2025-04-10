<?php

namespace Bermuda\ParameterResolver\Resolver;

use Bermuda\MiddlewareFactory\Resolver\ParametrResolver;

class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    public const string CONFIG_KEY_RESOLVERS = 'Bermuda\ParameterResolver:resolvers';

    protected function getFactories(): array
    {
        return [
            ParametrResolver::class => [ParameterResolver::class, 'createFromContainer'],
            ContainerResolver::class => [ContainerResolver::class, 'createFromContainer'],
            ResolverCollector::class => [ResolverCollector::class, 'createFromContainer'],
        ];
    }
}