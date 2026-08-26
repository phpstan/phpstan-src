<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a collector in the DI container on the set rule level.
 *
 * The collector is active when the analysis runs on this level or higher.
 * Level 0 means the collector is always active.
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
final class RegisteredCollector
{

	public function __construct(public int $level)
	{
	}

}
