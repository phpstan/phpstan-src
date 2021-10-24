<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\FunctionReflector;

final class MemoizingFunctionReflector extends FunctionReflector
{

	/** @var array<string, \PHPStan\BetterReflection\Reflection\ReflectionFunction> */
	private array $reflections = [];

	/**
	 * Create a ReflectionFunction for the specified $functionName.
	 *
	 * @return \PHPStan\BetterReflection\Reflection\ReflectionFunction
	 *
	 * @throws \PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound
	 */
	public function reflect(string $functionName): Reflection
	{
		$lowerFunctionName = strtolower($functionName);
		if (isset($this->reflections[$lowerFunctionName])) {
			return $this->reflections[$lowerFunctionName];
		}

		return $this->reflections[$lowerFunctionName] = parent::reflect($functionName);
	}

}
