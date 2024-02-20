<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\DependencyInjection\Container;

class LazyAlwaysUsedMethodExtensionProvider implements AlwaysUsedMethodExtensionProvider
{

	/** @var AlwaysUsedMethodExtension[]|null */
	private ?array $extensions = null;

	public function __construct(private Container $container)
	{
	}

	public function getExtensions(): array
	{
		return $this->extensions ??= $this->container->getServicesByTag(static::EXTENSION_TAG);
	}

}
