<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a non-autowired named service in the DI container.
 *
 * The factory is a method of another service like '@App\MyFactory::create',
 * or a static method like 'App\MyFactory::create'.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * @api
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class NonAutowiredService
{

	/** @api */
	public function __construct(
		public string $name,
		public ?string $factory = null,
	)
	{
	}

}
