<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\DependencyInjection\Container;

class LazyAlwaysUsedClassConstantsExtensionProvider implements AlwaysUsedClassConstantsExtensionProvider
{

	private Container $container;

	/** @var AlwaysUsedClassConstantsExtension[]|null */
	private ?array $extensions = null;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function getExtensions(): array
	{
		if ($this->extensions === null) {
			$this->extensions = $this->container->getServicesByTag(AlwaysUsedClassConstantsExtensionProvider::EXTENSION_TAG);
		}

		return $this->extensions;
	}

}
