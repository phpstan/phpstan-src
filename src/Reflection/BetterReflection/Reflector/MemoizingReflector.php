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
use function array_key_first;
use function count;
use function strtolower;

#[AutowiredService(name: 'betterReflectionReflector', as: Reflector::class)]
final class MemoizingReflector implements Reflector
{

	private const REFLECTIONS_MAX = 2048;

	/** @var array<string, ReflectionClass|null> LRU; first entry = least recently used */
	private array $classReflections = [];

	/** @var array<string, ReflectionConstant|null> */
	private array $constantReflections = [];

	/** @var array<lowercase-string, ReflectionFunction|null> LRU; first entry = least recently used */
	private array $functionReflections = [];

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
			// LRU: move to the most-recently-used position
			$classReflection = $this->classReflections[$lowerClassName];
			unset($this->classReflections[$lowerClassName]);

			return $this->classReflections[$lowerClassName] = $classReflection;
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
			if (count($this->classReflections) > self::REFLECTIONS_MAX) {
				unset($this->classReflections[array_key_first($this->classReflections)]);
			}

			throw IdentifierNotFound::fromIdentifier($identifier);
		}

		if (!$classReflection instanceof ReflectionClass) {
			throw new ShouldNotHappenException();
		}

		$this->classReflections[$lowerClassName] = $classReflection;
		if (count($this->classReflections) > self::REFLECTIONS_MAX) {
			unset($this->classReflections[array_key_first($this->classReflections)]);
		}

		return $classReflection;
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

			// LRU: move to the most-recently-used position
			unset($this->functionReflections[$lowerFunctionName]);

			return $this->functionReflections[$lowerFunctionName] = $functionReflection;
		}

		$identifier = new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
		$functionReflection = $this->sourceLocator->locateIdentifier($this, $identifier);
		if ($functionReflection === null) {
			$this->functionReflections[$lowerFunctionName] = null;
			if (count($this->functionReflections) > self::REFLECTIONS_MAX) {
				unset($this->functionReflections[array_key_first($this->functionReflections)]);
			}

			throw IdentifierNotFound::fromIdentifier($identifier);
		}

		if (!$functionReflection instanceof ReflectionFunction) {
			throw new ShouldNotHappenException();
		}

		$this->functionReflections[$lowerFunctionName] = $functionReflection;
		if (count($this->functionReflections) > self::REFLECTIONS_MAX) {
			unset($this->functionReflections[array_key_first($this->functionReflections)]);
		}

		return $functionReflection;
	}

	/**
	 * @return list<ReflectionClass>
	 */
	#[Override]
	public function reflectAllClasses(): iterable
	{
		/** @var list<ReflectionClass> */
		return $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
	}

	/**
	 * @return list<ReflectionFunction>
	 */
	#[Override]
	public function reflectAllFunctions(): iterable
	{
		/** @var list<ReflectionFunction> */
		return $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));
	}

	/**
	 * @return list<ReflectionConstant>
	 */
	#[Override]
	public function reflectAllConstants(): iterable
	{
		/** @var list<ReflectionConstant> */
		return $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));
	}

}
