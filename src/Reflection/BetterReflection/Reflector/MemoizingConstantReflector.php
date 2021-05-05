<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\ConstantReflector;

final class MemoizingConstantReflector extends ConstantReflector
{

	/** @var array<string, \PHPStan\BetterReflection\Reflection\ReflectionConstant|\Throwable> */
	private array $reflections = [];

	/**
	 * Create a ReflectionConstant for the specified $constantName.
	 *
	 * @return \PHPStan\BetterReflection\Reflection\ReflectionConstant
	 *
	 * @throws \PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound
	 */
	public function reflect(string $constantName): Reflection
	{
		if (isset($this->reflections[$constantName])) {
			if ($this->reflections[$constantName] instanceof \Throwable) {
				throw $this->reflections[$constantName];
			}
			return $this->reflections[$constantName];
		}

		try {
			return $this->reflections[$constantName] = parent::reflect($constantName);
		} catch (\Throwable $e) {
			$this->reflections[$constantName] = $e;
			throw $e;
		}
	}

}
