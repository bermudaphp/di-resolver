<?php

namespace Bermuda\ParameterResolver\Resolver;

use Psr\Http\Message\ServerRequestInterface;

interface ParameterResolverInterface
{
    /**
     * @param \ReflectionParameter $parameters
     * @param array $params
     * @return array<string, mixed>
     * The method returns an array of function parameters, 
     * where the keys will be the names of parameters
     */
    public function resolve(array $parameters, array $params = []): array;
    
    /**
     * @param ReflectionParameter $parameter
     * @param array $params
     * The method returns an array where the key “0” will be the name of the parameter
     * and the key “1” will be its value
     * @return array{0: string, 1: mixed}
     * @throws ResolverException
     */
    public function resolveParameter(\ReflectionParameter $parameter, array $params = []):? array ;
}
