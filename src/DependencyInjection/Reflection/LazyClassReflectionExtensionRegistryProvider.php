<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension;
use PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\Php\Soap\SoapClientMethodsClassReflectionExtension;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use function array_merge;

#[AutowiredService(as: ClassReflectionExtensionRegistryProvider::class)]
final class LazyClassReflectionExtensionRegistryProvider implements ClassReflectionExtensionRegistryProvider
{

	private ?ClassReflectionExtensionRegistry $registry = null;

	public function __construct(private ?Container $container)
	{
	}

	public function getRegistry(): ClassReflectionExtensionRegistry
	{
		if ($this->registry === null) {
			$container = $this->container;
			if ($container === null) {
				throw new ShouldNotHappenException();
			}

			$annotationsMethodsClassReflectionExtension = $container->getByType(AnnotationsMethodsClassReflectionExtension::class);
			$annotationsPropertiesClassReflectionExtension = $container->getByType(AnnotationsPropertiesClassReflectionExtension::class);

			$mixinMethodsClassReflectionExtension = $container->getByType(MixinMethodsClassReflectionExtension::class);
			$mixinPropertiesClassReflectionExtension = $container->getByType(MixinPropertiesClassReflectionExtension::class);
			$soapClientMethodsClassReflectionExtension = $container->getByType(SoapClientMethodsClassReflectionExtension::class);
			$universalObjectCratesClassReflectionExtension = $container->getByType(UniversalObjectCratesClassReflectionExtension::class);

			$this->registry = new ClassReflectionExtensionRegistry(
				array_merge($container->getServicesByTag(BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsPropertiesClassReflectionExtension, $mixinPropertiesClassReflectionExtension, $universalObjectCratesClassReflectionExtension]),
				array_merge($container->getServicesByTag(BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsMethodsClassReflectionExtension, $mixinMethodsClassReflectionExtension, $soapClientMethodsClassReflectionExtension]),
				$container->getServicesByTag(BrokerFactory::ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG),
				$container->getByType(RequireExtendsPropertiesClassReflectionExtension::class),
				$container->getByType(RequireExtendsMethodsClassReflectionExtension::class),
				$container->getByType(PhpClassReflectionExtension::class),
			);

			// Every ClassReflection instance holds this provider; keeping the container
			// reference here would make each of them a transitive handle on the entire DI
			// container. After the registry is built the container is no longer needed.
			$this->container = null;
		}

		return $this->registry;
	}

}
