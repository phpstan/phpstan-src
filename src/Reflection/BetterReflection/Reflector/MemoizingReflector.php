<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\Reflector;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;
use function strtolower;

#[AutowiredService(name: 'betterReflectionReflector', as: Reflector::class)]
final class MemoizingReflector implements Reflector
{

	/** @var array<string, ReflectionClass|null> */
	private array $classReflections = [];

	/** @var array<string, ReflectionConstant|null> */
	private array $constantReflections = [];

	/** @var array<string, ReflectionFunction|null> */
	private array $functionReflections = [];

	public function __construct(
		#[AutowiredParameter(ref: '@originalBetterReflectionReflector')]
		private Reflector $reflector,
	)
	{
	}

	#[Override]
	public function reflectClass(string $className): ReflectionClass
	{
		$lowerClassName = strtolower($className);
		if (array_key_exists($lowerClassName, $this->classReflections) && $this->classReflections[$lowerClassName] !== null) {
			return $this->classReflections[$lowerClassName];
		}
		if (array_key_exists($className, $this->classReflections)) {
			$classReflection = $this->classReflections[$className];
			if ($classReflection === null) {
				throw IdentifierNotFound::fromIdentifier(new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
			}

			return $classReflection;
		}

		try {
			return $this->classReflections[$lowerClassName] = $this->reflector->reflectClass($className);
		} catch (IdentifierNotFound $e) {
			$this->classReflections[$className] = null;

			throw $e;
		}
	}

	#[Override]
	public function reflectConstant(string $constantName): ReflectionConstant
	{
		if (array_key_exists($constantName, $this->constantReflections)) {
			$constantReflection = $this->constantReflections[$constantName];
			if ($constantReflection === null) {
				throw IdentifierNotFound::fromIdentifier(new Identifier($constantName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT)));
			}

			return $constantReflection;
		}

		try {
			return $this->constantReflections[$constantName] = $this->reflector->reflectConstant($constantName);
		} catch (IdentifierNotFound $e) {
			$this->constantReflections[$constantName] = null;

			throw $e;
		}
	}

	#[Override]
	public function reflectFunction(string $functionName): ReflectionFunction
	{
		$lowerFunctionName = strtolower($functionName);
		if (array_key_exists($lowerFunctionName, $this->functionReflections)) {
			$functionReflection = $this->functionReflections[$lowerFunctionName];
			if ($functionReflection === null) {
				throw IdentifierNotFound::fromIdentifier(new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)));
			}

			return $functionReflection;
		}

		try {
			return $this->functionReflections[$lowerFunctionName] = $this->reflector->reflectFunction($functionName);
		} catch (IdentifierNotFound $e) {
			$this->functionReflections[$lowerFunctionName] = null;

			throw $e;
		}
	}

	#[Override]
	public function reflectAllClasses(): iterable
	{
		return $this->reflector->reflectAllClasses();
	}

	#[Override]
	public function reflectAllFunctions(): iterable
	{
		return $this->reflector->reflectAllFunctions();
	}

	#[Override]
	public function reflectAllConstants(): iterable
	{
		return $this->reflector->reflectAllConstants();
	}

}
