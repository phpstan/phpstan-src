<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

/**
 * Autowires an ExtensionsCollection of all services tagged with the tag
 * that the given extension interface declares with #[ExtensionInterface].
 *
 * The parameter has to be typed as ExtensionsCollection. Repeat the interface
 * in the PHPDoc generic type of the parameter so that the extensions are typed
 * at the use site.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(flags: Attribute::TARGET_PARAMETER)]
final class AutowiredExtensions
{

	/**
	 * @param class-string $interface
	 */
	public function __construct(public string $interface)
	{
	}

}
