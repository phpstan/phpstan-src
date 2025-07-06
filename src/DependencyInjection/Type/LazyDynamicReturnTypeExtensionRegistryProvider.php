<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;

#[AutowiredService(as: DynamicReturnTypeExtensionRegistryProvider::class)]
final class LazyDynamicReturnTypeExtensionRegistryProvider implements DynamicReturnTypeExtensionRegistryProvider
{

	private ?DynamicReturnTypeExtensionRegistry $registry = null;

	public function __construct(private Container $container)
	{
	}

	public function getRegistry(): DynamicReturnTypeExtensionRegistry
	{
		return $this->registry ??= new DynamicReturnTypeExtensionRegistry(
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getServicesByTag(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG),
			$this->container->getServicesByTag(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG),
			$this->container->getServicesByTag(BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG),
		);
	}

}
