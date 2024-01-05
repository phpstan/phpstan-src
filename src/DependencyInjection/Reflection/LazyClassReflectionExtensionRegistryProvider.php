<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension;
use function array_merge;

class LazyClassReflectionExtensionRegistryProvider implements ClassReflectionExtensionRegistryProvider
{

	private ?ClassReflectionExtensionRegistry $registry = null;

	public function __construct(private Container $container)
	{
	}

	public function getRegistry(): ClassReflectionExtensionRegistry
	{
		if ($this->registry === null) {
			$phpClassReflectionExtension = $this->container->getByType(PhpClassReflectionExtension::class);
			$annotationsMethodsClassReflectionExtension = $this->container->getByType(AnnotationsMethodsClassReflectionExtension::class);
			$annotationsPropertiesClassReflectionExtension = $this->container->getByType(AnnotationsPropertiesClassReflectionExtension::class);

			$this->registry = new ClassReflectionExtensionRegistry(
				$this->container->getByType(Broker::class),
				array_merge([$phpClassReflectionExtension], $this->container->getServicesByTag(BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsPropertiesClassReflectionExtension]),
				array_merge([$phpClassReflectionExtension], $this->container->getServicesByTag(BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsMethodsClassReflectionExtension]),
				$this->container->getServicesByTag(BrokerFactory::ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG),
				$this->container->getByType(RequireExtendsPropertiesClassReflectionExtension::class),
				$this->container->getByType(RequireExtendsMethodsClassReflectionExtension::class),
			);
		}

		return $this->registry;
	}

}
