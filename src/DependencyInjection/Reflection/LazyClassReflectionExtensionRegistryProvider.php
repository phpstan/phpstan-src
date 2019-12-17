<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\PhpDefect\PhpDefectClassReflectionExtension;

class LazyClassReflectionExtensionRegistryProvider implements ClassReflectionExtensionRegistryProvider
{

	/** @var \PHPStan\DependencyInjection\Container */
	private $container;

	/** @var \PHPStan\Reflection\ClassReflectionExtensionRegistry|null */
	private $registry;

	public function __construct(\PHPStan\DependencyInjection\Container $container)
	{
		$this->container = $container;
	}

	public function getRegistry(): ClassReflectionExtensionRegistry
	{
		if ($this->registry === null) {
			$phpClassReflectionExtension = $this->container->getByType(PhpClassReflectionExtension::class);
			$annotationsMethodsClassReflectionExtension = $this->container->getByType(AnnotationsMethodsClassReflectionExtension::class);
			$annotationsPropertiesClassReflectionExtension = $this->container->getByType(AnnotationsPropertiesClassReflectionExtension::class);
			$phpDefectClassReflectionExtension = $this->container->getByType(PhpDefectClassReflectionExtension::class);

			$this->registry = new ClassReflectionExtensionRegistry(
				$this->container->getByType(Broker::class),
				array_merge([$phpClassReflectionExtension, $phpDefectClassReflectionExtension], $this->container->getServicesByTag(BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsPropertiesClassReflectionExtension]),
				array_merge([$phpClassReflectionExtension], $this->container->getServicesByTag(BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsMethodsClassReflectionExtension])
			);
		}

		return $this->registry;
	}

}
