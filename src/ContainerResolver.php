<?php

namespace Bermuda\ParameterResolver\Resolver;

use Bermuda\ParametrResolver\Attribute\Config;
use Bermuda\ParametrResolver\Attribute\Container;
use Bermuda\Reflection\TypeMatcher;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionParameter;

final class ContainerResolver implements ParameterResolverInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function resolve(ReflectionParameter $parameter, array $params = []):? array
    {
        $config = $this->getAttribute($parameter, Config::class);

        if ($config) {
            $configInstance = $config->newInstance();

            $path = $configInstance->path;
            $config = $this->container->get($configInstance->configKey);

            if (is_array($path)) {
                $entry = $config[$segment = array_shift($path)] ?? null;
                if (!$entry) {

                }
                while (($key = array_shift($path)) !== null) {
                    $entry = $entry[$key];
                }
            } else $entry = $config[$path];

            $this->checkParamType($parameter, $entry);

            return [$parameter->getName(), $entry];
        }

        $container = $this->getAttribute($parameter, Container::class);

        if ($container) {
            return $this->resolveFromContainer($parameter, $container->newInstance()->id);
        }

        if ($this->container->has($parameter->getName())) {
            return $this->resolveFromContainer($parameter, $parameter->getName());
        }

        return null;
    }

    private function getAttribute(ReflectionParameter $parameter, string $cls):? \ReflectionAttribute
    {
        return $parameter->getAttributes($cls)[0] ?? null;
    }

    private function checkParamType(ReflectionParameter $parameter, mixed $entry)
    {
        if ($parameter->getType() !== null) {
            $matcher = new TypeMatcher();
            if (!$matcher->match($parameter->getType(), $entry)) {
                throw ResolverException::createForParametrType($parameter, $entry);
            }
        }
    }

    private function resolveFromContainer(ReflectionParameter $parameter, string $id): array
    {
        try {
            $entry = $this->container->get($id);
        } catch (ContainerExceptionInterface $e) {
            throw ResolverException::createFromPrev($e);
        }

        $this->checkParamType($parameter, $entry);

        return [$parameter->getName(), $entry];
    }
}
