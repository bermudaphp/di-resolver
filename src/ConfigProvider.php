<?php

namespace Bermuda\ParameterResolver;

class ConfigProvider extends \Bermuda\Config\ConfigProvider
{
    protected function getFactories(): array
    {
        return [
            ParameterResolver::class => [ParameterResolver::class, 'createDefaults'],
            ContainerResolver::class => [ContainerResolver::class, 'createFromContainer'],
        ];
    }
}
