<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;
use function class_implements;
use function class_parents;

final class ExtensionClassHelper
{

	/** @var array<string, array<string>> */
	private static array $extensionClassNames = [];

	/**
	 * @return string[]
	 */
	public static function getExtensionClassNames(ReflectionProvider $reflectionProvider, string $className): array
	{
		if (!array_key_exists($className, self::$extensionClassNames)) {
			$class = $reflectionProvider->getClass($className);
			self::$extensionClassNames[$className] = [$className] + $class->getParentClassesNames() + $class->getNativeReflection()->getInterfaceNames();
		}

		return self::$extensionClassNames[$className];
	}

	/**
	 * @return string[]
	 */
	public static function getExtensionClassNamesByRuntimeReflection(string $className): array
	{
		if (!array_key_exists($className, self::$extensionClassNames)) {
			self::$extensionClassNames[$className] = [$className] + class_parents($className) + class_implements($className);
		}

		return self::$extensionClassNames[$className];
	}

}
