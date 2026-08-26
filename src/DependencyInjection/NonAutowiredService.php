<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a non-autowired named service in the DI container.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * Extensions and analysed projects can use this attribute on classes in directories
 * listed in the `attributeServicesDirectories` section of their configuration file.
 *
 * @api
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class NonAutowiredService
{

	public function __construct(
		public string $name,
		public ?string $factory = null,
	)
	{
	}

}
