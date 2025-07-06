<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;

#[AutowiredService]
final class LazyReadWritePropertiesExtensionProvider implements ReadWritePropertiesExtensionProvider
{

	/** @var ReadWritePropertiesExtension[]|null */
	private ?array $extensions = null;

	public function __construct(private Container $container)
	{
	}

	public function getExtensions(): array
	{
		return $this->extensions ??= $this->container->getServicesByTag(ReadWritePropertiesExtensionProvider::EXTENSION_TAG);
	}

}
