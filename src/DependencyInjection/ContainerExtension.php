<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Registers a Nette\DI\CompilerExtension under the given name, the same way an entry in the
 * `extensions:` section of a configuration file would. The name is also the key of the section
 * holding the extension's own configuration.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and ContainerExtensionsExtension.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
final class ContainerExtension
{

	public function __construct(public string $name)
	{
	}

}
