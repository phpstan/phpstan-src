<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use function array_key_exists;
use function sprintf;

/**
 * The #[ExtensionInterface] mapping baked into the compiled DI container
 * by AutowiredAttributeServicesExtension.
 *
 * @internal
 */
final class ExtensionInterfaceTags
{

	/**
	 * @param array<class-string, string> $tags
	 */
	public function __construct(private array $tags)
	{
	}

	/**
	 * @param class-string $interfaceName
	 * @throws MissingServiceException
	 */
	public function getTag(string $interfaceName): string
	{
		if (!array_key_exists($interfaceName, $this->tags)) {
			throw new MissingServiceException(sprintf(
				'Interface %s is not an extension interface. Mark it with the #[%s] attribute.',
				$interfaceName,
				ExtensionInterface::class,
			));
		}

		return $this->tags[$interfaceName];
	}

}
