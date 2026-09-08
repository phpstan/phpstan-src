<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Records how many times a class is looked up, so a test can assert that resolving exported
 * nodes does not reach for reflection when it does not have to.
 */
final class CountingReflectionProvider implements ReflectionProvider
{

	public int $hasClassCallCount = 0;

	public function __construct(private ReflectionProvider $inner)
	{
	}

	public function hasClass(string $className): bool
	{
		$this->hasClassCallCount++;

		return $this->inner->hasClass($className);
	}

	public function getClass(string $className): ClassReflection
	{
		return $this->inner->getClass($className);
	}

	public function getClassName(string $className): string
	{
		return $this->inner->getClassName($className);
	}

	public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		return $this->inner->getAnonymousClassReflection($classNode, $scope);
	}

	public function getUniversalObjectCratesClasses(): array
	{
		return $this->inner->getUniversalObjectCratesClasses();
	}

	public function hasFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->inner->hasFunction($nameNode, $namespaceAnswerer);
	}

	public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection
	{
		return $this->inner->getFunction($nameNode, $namespaceAnswerer);
	}

	public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->inner->resolveFunctionName($nameNode, $namespaceAnswerer);
	}

	public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->inner->hasConstant($nameNode, $namespaceAnswerer);
	}

	public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ConstantReflection
	{
		return $this->inner->getConstant($nameNode, $namespaceAnswerer);
	}

	public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->inner->resolveConstantName($nameNode, $namespaceAnswerer);
	}

}
