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
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function strtolower;

#[AutowiredService(name: 'betterReflectionReflector', as: Reflector::class)]
final class MemoizingReflector implements Reflector
{

	/** @var array<string, ReflectionClass|null> */
	private array $classReflections = [];

	/** @var array<string, ReflectionConstant|null> */
	private array $constantReflections = [];

	/** @var array<lowercase-string, ReflectionFunction|null> */
	private array $functionReflections = [];

	/** @var list<ReflectionClass>|null */
	private ?array $allClasses = null;

	/** @var list<ReflectionFunction>|null */
	private ?array $allFunctions = null;

	/** @var list<ReflectionConstant>|null */
	private ?array $allConstants = null;

	public function __construct(
		#[AutowiredParameter(ref: '@betterReflectionSourceLocator')]
		private SourceLocator $sourceLocator,
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

		// located directly, without a DefaultReflector - created reflections capture
		// the reflector passed here and resolve e.g. parent classes and interfaces
		// through it, so passing $this routes those lookups through this cache too
		$identifier = new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
		$classReflection = $this->sourceLocator->locateIdentifier($this, $identifier);
		if ($classReflection === null) {
			$this->classReflections[$className] = null;

			throw IdentifierNotFound::fromIdentifier($identifier);
		}

		if (!$classReflection instanceof ReflectionClass) {
			throw new ShouldNotHappenException();
		}

		return $this->classReflections[$lowerClassName] = $classReflection;
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

		$identifier = new Identifier($constantName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));
		$constantReflection = $this->sourceLocator->locateIdentifier($this, $identifier);
		if ($constantReflection === null) {
			$this->constantReflections[$constantName] = null;

			throw IdentifierNotFound::fromIdentifier($identifier);
		}

		if (!$constantReflection instanceof ReflectionConstant) {
			throw new ShouldNotHappenException();
		}

		return $this->constantReflections[$constantName] = $constantReflection;
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

		$identifier = new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
		$functionReflection = $this->sourceLocator->locateIdentifier($this, $identifier);
		if ($functionReflection === null) {
			$this->functionReflections[$lowerFunctionName] = null;

			throw IdentifierNotFound::fromIdentifier($identifier);
		}

		if (!$functionReflection instanceof ReflectionFunction) {
			throw new ShouldNotHappenException();
		}

		return $this->functionReflections[$lowerFunctionName] = $functionReflection;
	}

	/**
	 * @return list<ReflectionClass>
	 */
	#[Override]
	public function reflectAllClasses(): iterable
	{
		/** @var list<ReflectionClass> */
		return $this->allClasses ??= $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
	}

	/**
	 * @return list<ReflectionFunction>
	 */
	#[Override]
	public function reflectAllFunctions(): iterable
	{
		/** @var list<ReflectionFunction> */
		return $this->allFunctions ??= $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
	}

	/**
	 * @return list<ReflectionConstant>
	 */
	#[Override]
	public function reflectAllConstants(): iterable
	{
		/** @var list<ReflectionConstant> */
		return $this->allConstants ??= $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));
	}

}
