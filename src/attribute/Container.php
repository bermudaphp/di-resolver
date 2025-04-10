<?php

namespace Bermuda\Di\Attribute;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)] class Container
{
    public function __construct(
        public readonly string $id,
    ) {
    }
}
