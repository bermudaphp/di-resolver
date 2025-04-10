<?php

namespace Bermuda\ParameterResolver\Resolver;

use Psr\Http\Message\ServerRequestInterface;

interface ParameterResolverInterface
{
    public function resolve(\ReflectionParameter $parameter, array $params = []):? array ;
}
