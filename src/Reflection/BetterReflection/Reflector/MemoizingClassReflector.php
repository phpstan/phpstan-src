<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\ClassReflector;

final class MemoizingClassReflector extends ClassReflector
{

	/** @var array<string, \Roave\BetterReflection\Reflection\ReflectionClass> */
	private $reflections = [];

	/**
	 * Create a ReflectionClass for the specified $className.
	 *
	 * @return \Roave\BetterReflection\Reflection\ReflectionClass
	 *
	 * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
	 */
	public function reflect(string $className): Reflection
	{
		if (isset($this->reflections[$className])) {
			return $this->reflections[$className];
		}
		return $this->reflections[$className] = parent::reflect($className);
	}

}
