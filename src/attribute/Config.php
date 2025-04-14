<?php

namespace Bermuda\Di\Attribute;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]class Config
{
    public function __construct(
        public readonly string $path,
        public readonly bool $explodeDots = true,
    ) {}

    public static string $key = 'config';
}
