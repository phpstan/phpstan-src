<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\DependencyInjection\Container;

final class LazyReadWritePropertiesExtensionProvider implements ReadWritePropertiesExtensionProvider
{

	/** @var ReadWritePropertiesExtension[]|null */
	private ?array $extensions = null;

	public function __construct(private Container $container)
	{
	}

	public function getExtensions(): array
	{
		if ($this->extensions === null) {
			$this->extensions = $this->container->getServicesByTag(ReadWritePropertiesExtensionProvider::EXTENSION_TAG);
		}

		return $this->extensions;
	}

}
