<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\ConstantReflector;

final class MemoizingConstantReflector extends ConstantReflector
{

	/** @var array<string, \Roave\BetterReflection\Reflection\ReflectionConstant> */
	private $reflections = [];

	/**
	 * Create a ReflectionConstant for the specified $constantName.
	 *
	 * @return \Roave\BetterReflection\Reflection\ReflectionConstant
	 *
	 * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
	 */
	public function reflect(string $constantName): Reflection
	{
		if (isset($this->reflections[$constantName])) {
			return $this->reflections[$constantName];
		}
		return $this->reflections[$constantName] = parent::reflect($constantName);
	}

}
