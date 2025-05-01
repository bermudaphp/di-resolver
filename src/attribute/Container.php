<?php

namespace Bermuda\Di\Attribute;

/**
 * The Inject attribute is used to mark parameters or properties for dependency injection.
 *
 * When applied, this attribute signals that the annotated element should be automatically
 * populated with a dependency from a container. If an identifier (id) is provided, the container
 * will use it to locate the dependency; otherwise, the container may fall back to the type hint
 * of the parameter or property.
 *
 * Example usage:
 *
 * // Injects the service with the given service id.
 * public function __construct(#[Inject("service.id")] ServiceInterface $service) { ... }
 *
 * // If no id is provided, the container might use the type to resolve the dependency.
 * #[Inject]
 * private LoggerInterface $logger;
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Inject
{
    /**
     * Constructor.
     *
     * @param string|null $id An optional service identifier to be used for dependency resolution.
     *                        If null, the container may use the parameter or property type as the service id.
     */
    public function __construct(
        public readonly ?string $id = null,
    ) {
    }
}
