<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Generates concrete factory implementation based on the passed interface name.
 *
 * This looks at and compares constructor of the class with the attribute
 * against the "create" method of the passed interface.
 *
 * It subtracts "create" parameters from the constructor parameters.
 * And the rest is autowired from the dependency injection container.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class GenerateFactory
{

	/**
	 * @param class-string $interface
	 */
	public function __construct(public string $interface)
	{
	}

}
