<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;

class LazyDynamicReturnTypeExtensionRegistryProvider implements DynamicReturnTypeExtensionRegistryProvider
{

	private Container $container;

	private ?DynamicReturnTypeExtensionRegistry $registry = null;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function getRegistry(): DynamicReturnTypeExtensionRegistry
	{
		if ($this->registry === null) {
			$this->registry = new DynamicReturnTypeExtensionRegistry(
				$this->container->getByType(Broker::class),
				$this->container->getByType(ReflectionProvider::class),
				$this->container->getServicesByTag(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG),
				$this->container->getServicesByTag(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG),
				$this->container->getServicesByTag(BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG),
			);
		}

		return $this->registry;
	}

}
