<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Broker\Broker;
use function array_merge;

class ClassReflectionExtensionRegistry
{

	/**
	 * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
	 * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
	 */
	public function __construct(
		Broker $broker,
		private array $propertiesClassReflectionExtensions,
		private array $methodsClassReflectionExtensions,
	)
	{
		foreach (array_merge($propertiesClassReflectionExtensions, $methodsClassReflectionExtensions) as $extension) {
			if (!($extension instanceof BrokerAwareExtension)) {
				continue;
			}

			$extension->setBroker($broker);
		}
	}

	/**
	 * @return PropertiesClassReflectionExtension[]
	 */
	public function getPropertiesClassReflectionExtensions(): array
	{
		return $this->propertiesClassReflectionExtensions;
	}

	/**
	 * @return MethodsClassReflectionExtension[]
	 */
	public function getMethodsClassReflectionExtensions(): array
	{
		return $this->methodsClassReflectionExtensions;
	}

}
