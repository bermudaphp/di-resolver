<?php

namespace Bermuda\ParameterResolver;

use ReflectionParameter;
use Bermuda\Reflection\TypeMatcher;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Bermuda\Di\Attribute\Config;
use Bermuda\Di\Attribute\Container;

final class ContainerResolver implements ParameterResolverInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(ReflectionParameter $parameter, array $params = []):? array
    {
        $config = $this->getAttribute($parameter, Config::class);

        if ($config) {
            $configInstance = $config->newInstance();

            $path = $configInstance->path;

            try {
                $config = $this->container->get($configInstance->configKey);
            } catch (ContainerExceptionInterface $prev) {
                return null;
            }

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

        /*
        if ($this->container->has($parameter->getName())) {
            return $this->resolveFromContainer($parameter, $parameter->getName());
        }*/

        $type = $parameter->getType();

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $type) {
                if ($type instanceof \ReflectionNamedType) goto named;
            }
        }

        if ($type instanceof \ReflectionNamedType) {
            named:
            if ($this->container->has($type->getName())) {
                try {
                    $entry = $this->container->get($id);
                } catch (ContainerExceptionInterface $e) {
                    return null;
                }

                if ($this->checkParamType($parameter, $entry)) return [$parameter->getName(), $entry];
            }
        }

        return null;
    }

    private function getAttribute(ReflectionParameter $parameter, string $cls):? \ReflectionAttribute
    {
        return $parameter->getAttributes($cls)[0] ?? null;
    }

    private function checkParamType(ReflectionParameter $parameter, mixed $entry): bool
    {
        static $matcher = new TypeMatcher;
        if ($parameter->getType() !== null) {
            if (!$matcher->match($parameter->getType(), $entry)) {
                return false;
            }
        }
        
        return true;
    }

    public static function createFromContainer(ContainerInterface $container): ContainerResolver
    {
        return new self($container);
    }
}
