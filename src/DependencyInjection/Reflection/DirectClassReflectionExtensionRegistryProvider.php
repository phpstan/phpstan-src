<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;

/**
 * @internal
 */
class DirectClassReflectionExtensionRegistryProvider implements ClassReflectionExtensionRegistryProvider
{

	/** @var PropertiesClassReflectionExtension[] */
	private array $propertiesClassReflectionExtensions;

	/** @var MethodsClassReflectionExtension[] */
	private array $methodsClassReflectionExtensions;

	private Broker $broker;

	/**
	 * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
	 * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
	 */
	public function __construct(
		array $propertiesClassReflectionExtensions,
		array $methodsClassReflectionExtensions,
	)
	{
		$this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
		$this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
	}

	public function setBroker(Broker $broker): void
	{
		$this->broker = $broker;
	}

	public function addPropertiesClassReflectionExtension(PropertiesClassReflectionExtension $extension): void
	{
		$this->propertiesClassReflectionExtensions[] = $extension;
	}

	public function addMethodsClassReflectionExtension(MethodsClassReflectionExtension $extension): void
	{
		$this->methodsClassReflectionExtensions[] = $extension;
	}

	public function getRegistry(): ClassReflectionExtensionRegistry
	{
		return new ClassReflectionExtensionRegistry(
			$this->broker,
			$this->propertiesClassReflectionExtensions,
			$this->methodsClassReflectionExtensions,
		);
	}

}
