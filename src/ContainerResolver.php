<?php

namespace Bermuda\ParameterResolver;

use ReflectionParameter;
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
        $attribute = $this->getAttribute($parameter, Config::class);

        if ($attribute) {
            $configInstance = $attribute->newInstance();

            $path = $configInstance->path;

            try {
                $attribute = $this->container->get($configInstance->configKey);
            } catch (ContainerExceptionInterface) {
                return null;
            }

            if (is_array($path)) {
                $entry = $attribute[$segment = array_shift($path)] ?? null;
                if (!$entry) {

                }
                while (($key = array_shift($path)) !== null) {
                    $entry = $entry[$key];
                }
            } else $entry = $attribute[$path];

            return [$parameter->getName(), $entry];
        }

        $attribute = $this->getAttribute($parameter, Container::class);

        if ($attribute) {
            return [$parameter->getName(), $this->container->get($attribute->newInstance()->id)];
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
                    $entry = $this->container->get($type->getName());
                } catch (ContainerExceptionInterface $e) {
                    return null;
                }

                return [$parameter->getName(), $entry];
            }
        }

        return null;
    }

    private function getAttribute(ReflectionParameter $parameter, string $cls):? \ReflectionAttribute
    {
        return $parameter->getAttributes($cls)[0] ?? null;
    }

    public static function createFromContainer(ContainerInterface $container): ContainerResolver
    {
        return new self($container);
    }
}
