<?php

namespace Bermuda\Reflection\Resolver;

use Psr\Http\Message\ServerRequestInterface;

interface ParametrResolverInterface
{
    public function resolve(\ReflectionParameter $parameter, array $params):? array ;
}
