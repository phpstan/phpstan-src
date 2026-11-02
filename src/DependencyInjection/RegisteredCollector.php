<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a collector in the DI container on the set rule level.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 *
 * @api
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class RegisteredCollector
{

	/** @api */
	public function __construct(public int $level)
	{
	}

}
