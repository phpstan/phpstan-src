<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\PhpStormMeta\MetaFileParser;
use PHPStan\PhpStormMeta\ReturnTypeExtensionGenerator;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use function array_push;

class LazyDynamicReturnTypeExtensionRegistryProvider implements DynamicReturnTypeExtensionRegistryProvider
{

	private ?DynamicReturnTypeExtensionRegistry $registry = null;

	public function __construct(private Container $container, private MetaFileParser $phpStormMetaParser, private ReturnTypeExtensionGenerator $phpStormMetaExtensionGenerator)
	{
	}

	public function getRegistry(): DynamicReturnTypeExtensionRegistry
	{
		if ($this->registry === null) {
			$dynamicMethodReturnTypeExtensions = $this->container->getServicesByTag(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG);
			$dynamicStaticMethodReturnTypeExtensions = $this->container->getServicesByTag(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG);
			$dynamicFunctionReturnTypeExtensions = $this->container->getServicesByTag(BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG);

			$phpStormMetaExtensions = $this->phpStormMetaExtensionGenerator->generateExtensionsBasedOnMeta($this->phpStormMetaParser->getMeta());

			array_push($dynamicMethodReturnTypeExtensions, ...$phpStormMetaExtensions->nonStaticMethodExtensions);
			array_push($dynamicStaticMethodReturnTypeExtensions, ...$phpStormMetaExtensions->staticMethodExtensions);
			array_push($dynamicFunctionReturnTypeExtensions, ...$phpStormMetaExtensions->functionExtensions);

			$this->registry = new DynamicReturnTypeExtensionRegistry(
				$this->container->getByType(Broker::class),
				$this->container->getByType(ReflectionProvider::class),
				$dynamicMethodReturnTypeExtensions,
				$dynamicStaticMethodReturnTypeExtensions,
				$dynamicFunctionReturnTypeExtensions,
			);
		}

		return $this->registry;
	}

}
