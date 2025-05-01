<?php

namespace Bermuda\Di\Attribute;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * The Config attribute is used to inject configuration values into parameters or properties.
 *
 * This attribute allows you to specify a configuration key (or path) that should be used
 * to retrieve a value from a configuration container.
 *
 * Properties:
 * - path: The configuration path used to locate the desired value. This may be a dot-separated
 *   string (e.g., "database.connections.mysql") if the configuration array is nested.
 * - default: An optional default value that will be used if the configuration key is not found.
 * - explodeDots: A flag that indicates whether to split the 'path' string by dots into an array of keys.
 *
 * The static property $key holds the identifier ("config") used to locate the overall configuration
 * resource within the container.
 *
 * Example usage:
 * public function __construct(#[Config("app.settings.debug", default: false, explodeDots: true)] $debugConfig) { ... }
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Config
{
    /**
     * Constructor.
     *
     * @param string $path The configuration path to retrieve the value from.
     *                      If explodeDots is true, the path will be split into parts using '.' as a delimiter.
     * @param mixed $default The default value to use if the configuration value is not found.
     * @param bool $explodeDots Determines whether dots in the path should act as delimiters to split the configuration key.
     */
    public function __construct(
        public readonly string $path,
        public readonly mixed $default = null,
        public readonly bool $explodeDots = true,
    ) {}

    /**
     * Static key used to retrieve the root configuration from the container.
     *
     * This key is used by the dependency injection mechanism to locate the configuration data.
     *
     * @var string
     */
    public static string $key = 'config';
}
