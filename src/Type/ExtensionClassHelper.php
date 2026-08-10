<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;
use function array_merge;

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
			self::$extensionClassNames[$className] = array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames());
		}

		return self::$extensionClassNames[$className];
	}

}
