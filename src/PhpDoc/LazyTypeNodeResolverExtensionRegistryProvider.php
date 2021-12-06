<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;

class LazyTypeNodeResolverExtensionRegistryProvider implements TypeNodeResolverExtensionRegistryProvider
{

	private Container $container;

	private ?TypeNodeResolverExtensionRegistry $registry = null;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function getRegistry(): TypeNodeResolverExtensionRegistry
	{
		if ($this->registry === null) {
			$this->registry = new TypeNodeResolverExtensionRegistry(
				$this->container->getByType(TypeNodeResolver::class),
				$this->container->getServicesByTag(TypeNodeResolverExtension::EXTENSION_TAG)
			);
		}

		return $this->registry;
	}

}
