<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;

/**
 * @api
 * @final
 */
class Broker implements ReflectionProvider
{

	private static ?Broker $instance = null;

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private array $universalObjectCratesClasses,
	)
	{
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
	public function hasFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->reflectionProvider->hasFunction($nameNode, $namespaceAnswerer);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection
	{
		return $this->reflectionProvider->getFunction($nameNode, $namespaceAnswerer);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->reflectionProvider->resolveFunctionName($nameNode, $namespaceAnswerer);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->reflectionProvider->hasConstant($nameNode, $namespaceAnswerer);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): GlobalConstantReflection
	{
		return $this->reflectionProvider->getConstant($nameNode, $namespaceAnswerer);
	}

	/**
	 * @deprecated Use PHPStan\Reflection\ReflectionProvider instead
	 */
	public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->reflectionProvider->resolveConstantName($nameNode, $namespaceAnswerer);
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
