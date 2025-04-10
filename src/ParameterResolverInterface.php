<?php

namespace Bermuda\ParameterResolver;

interface ParameterResolverInterface
{
    /**
     * @param ReflectionParameter $parameter
     * @param array $params
     * The method returns an array with key “0” being the parameter name and key “1” being its value
     * or null if the parameter could not be resolved
     * @return null|array{0: string, 1: mixed}
     */
    public function resolve(\ReflectionParameter $parameter, array $params = []):? array ;
}
