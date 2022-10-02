<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use ReflectionException;
use function array_key_exists;
use function explode;

final class ConstructorsHelper
{

	/** @var array<string, list<string>> */
	private array $additionalConstructorsCache = [];

	/**
	 * @param list<string> $additionalConstructors
	 */
	public function __construct(
		private array $additionalConstructors,
	)
	{
	}

	/**
	 * @return list<string>
	 */
	public function getConstructors(ClassReflection $classReflection): array
	{
		if (array_key_exists($classReflection->getName(), $this->additionalConstructorsCache)) {
			return $this->additionalConstructorsCache[$classReflection->getName()];
		}
		$constructors = [];
		if ($classReflection->hasConstructor()) {
			$constructors[] = $classReflection->getConstructor()->getName();
		}

		$nativeReflection = $classReflection->getNativeReflection();
		foreach ($this->additionalConstructors as $additionalConstructor) {
			[$className, $methodName] = explode('::', $additionalConstructor);
			if (!$nativeReflection->hasMethod($methodName)) {
				continue;
			}
			$nativeMethod = $nativeReflection->getMethod($methodName);
			if ($nativeMethod->getDeclaringClass()->getName() !== $nativeReflection->getName()) {
				continue;
			}

			try {
				$prototype = $nativeMethod->getPrototype();
			} catch (ReflectionException) {
				$prototype = $nativeMethod;
			}

			if ($prototype->getDeclaringClass()->getName() !== $className) {
				continue;
			}

			$constructors[] = $methodName;
		}

		$this->additionalConstructorsCache[$classReflection->getName()] = $constructors;

		return $constructors;
	}

}
