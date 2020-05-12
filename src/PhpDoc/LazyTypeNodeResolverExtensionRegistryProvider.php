<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

class LazyTypeNodeResolverExtensionRegistryProvider implements TypeNodeResolverExtensionRegistryProvider
{

	private \PHPStan\DependencyInjection\Container $container;

	private ?TypeNodeResolverExtensionRegistry $registry = null;

	public function __construct(\PHPStan\DependencyInjection\Container $container)
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
