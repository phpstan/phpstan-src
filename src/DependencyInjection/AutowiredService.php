<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a service in the DI container.
 *
 * Auto-adds service extension tags based on implemented interfaces.
 * Pass autoTag: false for services whose implemented interfaces
 * must not lead to the associated tags.
 *
 * The factory is a method of another service like '@App\MyFactory::create',
 * or a static method like 'App\MyFactory::create'.
 *
 * A single type in `as` becomes the type of the service, like `class: Interface, factory: Implementation`
 * in NEON: the service is autowired only as that type and gets the tags of the interfaces that type implements.
 * A list of types restricts autowiring without changing the type of the service.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * @api
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class AutowiredService
{

	/**
	 * @api
	 * @param true|list<class-string>|class-string $as
	 */
	public function __construct(
		public ?string $name = null,
		public ?string $factory = null,
		public bool|array|string $as = true,
		public bool $autoTag = true,
	)
	{
	}

}
