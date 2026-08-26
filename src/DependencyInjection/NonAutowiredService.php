<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a non-autowired named service in the DI container.

 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * Extensions distributed outside phpstan-src list the directories to look for
 * this attribute in through the `autowiredServiceDirectories` parameter.
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
