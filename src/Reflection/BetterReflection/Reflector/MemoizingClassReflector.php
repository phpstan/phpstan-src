<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\ClassReflector;

final class MemoizingClassReflector extends ClassReflector
{

	/** @var array<string, \PHPStan\BetterReflection\Reflection\ReflectionClass> */
	private array $reflections = [];

	/**
	 * Create a ReflectionClass for the specified $className.
	 *
	 * @return \PHPStan\BetterReflection\Reflection\ReflectionClass
	 *
	 * @throws \PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound
	 */
	public function reflect(string $className): Reflection
	{
		$lowerClassName = strtolower($className);
		if (isset($this->reflections[$lowerClassName])) {
			return $this->reflections[$lowerClassName];
		}

		return $this->reflections[$lowerClassName] = parent::reflect($className);
	}

}
