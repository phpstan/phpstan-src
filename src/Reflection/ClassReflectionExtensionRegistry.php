<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Broker\Broker;

class ClassReflectionExtensionRegistry
{

	/** @var \PHPStan\Reflection\PropertiesClassReflectionExtension[] */
	private array $propertiesClassReflectionExtensions;

	/** @var \PHPStan\Reflection\MethodsClassReflectionExtension[] */
	private array $methodsClassReflectionExtensions;

	/**
	 * @param \PHPStan\Reflection\PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
	 * @param \PHPStan\Reflection\MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
	 */
	public function __construct(
		Broker $broker,
		array $propertiesClassReflectionExtensions,
		array $methodsClassReflectionExtensions
	)
	{
		foreach (array_merge($propertiesClassReflectionExtensions, $methodsClassReflectionExtensions) as $extension) {
			if (!($extension instanceof BrokerAwareExtension)) {
				continue;
			}

			$extension->setBroker($broker);
		}
		$this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
		$this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
	}

	/**
	 * @return \PHPStan\Reflection\PropertiesClassReflectionExtension[]
	 */
	public function getPropertiesClassReflectionExtensions(): array
	{
		return $this->propertiesClassReflectionExtensions;
	}

	/**
	 * @return \PHPStan\Reflection\MethodsClassReflectionExtension[]
	 */
	public function getMethodsClassReflectionExtensions(): array
	{
		return $this->methodsClassReflectionExtensions;
	}

}
