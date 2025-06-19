<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a rule in the DI container on the set rule level.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class RegisteredRule
{

	public function __construct(public int $level)
	{
	}

}
