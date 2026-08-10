<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;
use function class_implements;
use function class_parents;

final class ExtensionClassHelper
{

	/** @var array<string, array<string>> */
	private static array $extensionClassNames = [];

	/** @var array<class-string<Node>, array<class-string<Node>>> */
	private static array $extensionClassNamesRuntimeReflections = [];

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
	 * @param class-string<Node> $className
	 * @return array<class-string<Node>>
	 */
	public static function getExtensionClassNamesByRuntimeReflection(string $className): array
	{
		if (!array_key_exists($className, self::$extensionClassNamesRuntimeReflections)) {
			self::$extensionClassNamesRuntimeReflections[$className] = [$className] + class_parents($className) + class_implements($className);
		}

		return self::$extensionClassNamesRuntimeReflections[$className];
	}

}
