<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension;
use PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\Php\Soap\SoapClientMethodsClassReflectionExtension;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
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
				array_merge($container->getExtensions(PropertiesClassReflectionExtension::class), [$annotationsPropertiesClassReflectionExtension, $mixinPropertiesClassReflectionExtension, $universalObjectCratesClassReflectionExtension]),
				array_merge($container->getExtensions(MethodsClassReflectionExtension::class), [$annotationsMethodsClassReflectionExtension, $mixinMethodsClassReflectionExtension, $soapClientMethodsClassReflectionExtension]),
				$container->getExtensions(AllowedSubTypesClassReflectionExtension::class),
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
