<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;

/** @api */
class Broker implements ReflectionProvider
{

	private ReflectionProvider $reflectionProvider;

	/** @var string[] */
	private array $universalObjectCratesClasses;

	private static ?Broker $instance = null;

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		array $universalObjectCratesClasses
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
	}

	public static function registerInstance(Broker $broker): void
	{
		self::$instance = $broker;
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProviderStaticAccessor instead
	 */
	public static function getInstance(): Broker
	{
		if (self::$instance === null) {
			throw new ShouldNotHappenException();
		}
		return self::$instance;
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function hasClass(string $className): bool
	{
		return $this->reflectionProvider->hasClass($className);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getClass(string $className): ClassReflection
	{
		return $this->reflectionProvider->getClass($className);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getClassName(string $className): string
	{
		return $this->reflectionProvider->getClassName($className);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function supportsAnonymousClasses(): bool
	{
		return $this->reflectionProvider->supportsAnonymousClasses();
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		return $this->reflectionProvider->getAnonymousClassReflection($classNode, $scope);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function hasFunction(Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->reflectionProvider->hasFunction($nameNode, $scope);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getFunction(Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		return $this->reflectionProvider->getFunction($nameNode, $scope);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function resolveFunctionName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->reflectionProvider->resolveFunctionName($nameNode, $scope);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function hasConstant(Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->reflectionProvider->hasConstant($nameNode, $scope);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getConstant(Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		return $this->reflectionProvider->getConstant($nameNode, $scope);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function resolveConstantName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->reflectionProvider->resolveConstantName($nameNode, $scope);
	}

	/**
	 * @deprecated Inject %universalObjectCratesClasses% parameter instead.
	 *
	 * @return string[]
	 */
	public function getUniversalObjectCratesClasses(): array
	{
		return $this->universalObjectCratesClasses;
	}

}
