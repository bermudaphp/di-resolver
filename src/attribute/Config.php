<?php

namespace Bermuda\ParameterResolver\Attribute;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]class Config
{
    public function __construct(
        public readonly string|array $path,
        public readonly string $configKey = 'config',
    ) {}
}
