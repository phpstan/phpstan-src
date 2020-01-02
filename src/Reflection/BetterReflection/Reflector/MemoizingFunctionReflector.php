<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\FunctionReflector;

final class MemoizingFunctionReflector extends FunctionReflector
{

	/** @var array<string, \Roave\BetterReflection\Reflection\ReflectionFunction> */
	private $reflections = [];

	/**
	 * Create a ReflectionFunction for the specified $functionName.
	 *
	 * @return \Roave\BetterReflection\Reflection\ReflectionFunction
	 *
	 * @throws \Roave\BetterReflection\Reflector\Exception\IdentifierNotFound
	 */
	public function reflect(string $functionName): Reflection
	{
		if (isset($this->reflections[$functionName])) {
			return $this->reflections[$functionName];
		}
		return $this->reflections[$functionName] = parent::reflect($functionName);
	}

}
