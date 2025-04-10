<?php

namespace Bermuda\ParameterResolver\Resolver;

class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    public const string CONFIG_KEY_RESOLVERS = 'Bermuda\ParameterResolver:resolvers';

    protected function getFactories(): array
    {
        return [
            ParameterResolver::class => [ParameterResolver::class, 'createFromContainer'],
            ContainerResolver::class => [ContainerResolver::class, 'createFromContainer'],
            ResolverCollector::class => [ResolverCollector::class, 'createFromContainer'],
        ];
    }
}
