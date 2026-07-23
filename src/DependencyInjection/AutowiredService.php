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
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class AutowiredService
{

	/**
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
