<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\DependencyInjection\Container;

class LazyReadWritePropertiesExtensionProvider implements ReadWritePropertiesExtensionProvider
{

	private Container $container;

	/** @var ReadWritePropertiesExtension[]|null */
	private ?array $extensions = null;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function getExtensions(): array
	{
		if ($this->extensions === null) {
			$this->extensions = $this->container->getServicesByTag(ReadWritePropertiesExtensionProvider::EXTENSION_TAG);
		}

		return $this->extensions;
	}

}
