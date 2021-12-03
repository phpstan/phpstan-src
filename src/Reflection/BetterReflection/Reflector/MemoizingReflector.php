<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;

final class MemoizingReflector implements Reflector
{

	private Reflector $reflector;

	/** @var array<string, \PHPStan\BetterReflection\Reflection\ReflectionClass> */
	private array $classReflections = [];

	/** @var array<string, \PHPStan\BetterReflection\Reflection\ReflectionConstant> */
	private array $constantReflections = [];

	/** @var array<string, \PHPStan\BetterReflection\Reflection\ReflectionFunction> */
	private array $functionReflections = [];

	public function __construct(Reflector $reflector)
	{
		$this->reflector = $reflector;
	}

	public function reflectClass(string $className): ReflectionClass
	{
		$lowerClassName = strtolower($className);
		if (isset($this->classReflections[$lowerClassName])) {
			return $this->classReflections[$lowerClassName];
		}

		return $this->classReflections[$lowerClassName] = $this->reflector->reflectClass($className);
	}

	public function reflectConstant(string $constantName): ReflectionConstant
	{
		if (isset($this->constantReflections[$constantName])) {
			return $this->constantReflections[$constantName];
		}

		return $this->constantReflections[$constantName] = $this->reflector->reflectConstant($constantName);
	}

	public function reflectFunction(string $functionName): ReflectionFunction
	{
		$lowerFunctionName = strtolower($functionName);
		if (isset($this->functionReflections[$lowerFunctionName])) {
			return $this->functionReflections[$lowerFunctionName];
		}

		return $this->functionReflections[$lowerFunctionName] = $this->reflector->reflectFunction($functionName);
	}

	public function reflectAllClasses(): iterable
	{
		return $this->reflector->reflectAllClasses();
	}

	public function reflectAllFunctions(): iterable
	{
		return $this->reflector->reflectAllFunctions();
	}

	public function reflectAllConstants(): iterable
	{
		return $this->reflector->reflectAllConstants();
	}

}
