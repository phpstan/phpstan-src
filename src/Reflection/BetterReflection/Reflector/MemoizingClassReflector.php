<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Throwable;

final class MemoizingClassReflector extends ClassReflector
{

	/** @var array<string, \Roave\BetterReflection\Reflection\ReflectionClass|\Throwable> */
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
			if ($this->reflections[$className] instanceof Throwable) {
				throw $this->reflections[$className];
			}
			return $this->reflections[$className];
		}

		try {
			return $this->reflections[$className] = parent::reflect($className);
		} catch (Throwable $e) {
			$this->reflections[$className] = $e;
			throw $e;
		}
	}

}
