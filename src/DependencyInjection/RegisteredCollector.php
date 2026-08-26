<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a collector in the DI container on the set rule level.
 *
 * Pass enabledBy a `%parameter%` reference to make the registration depend on
 * configuration, the way a `conditionalTags` entry would. The collector is only
 * tagged when the parameter is truthy.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class RegisteredCollector
{

	public function __construct(public int $level, public ?string $enabledBy = null)
	{
	}

}
