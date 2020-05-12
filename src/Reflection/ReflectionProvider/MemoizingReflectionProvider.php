<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;

class MemoizingReflectionProvider implements ReflectionProvider
{

	private \PHPStan\Reflection\ReflectionProvider $provider;

	/** @var array<string, bool> */
	private array $hasClasses = [];

	/** @var array<string, \PHPStan\Reflection\ClassReflection> */
	private array $classes = [];

	/** @var array<string, string> */
	private array $classNames = [];

	public function __construct(ReflectionProvider $provider)
	{
		$this->provider = $provider;
	}

	public function hasClass(string $className): bool
	{
		if (isset($this->hasClasses[$className])) {
			return $this->hasClasses[$className];
		}

		return $this->hasClasses[$className] = $this->provider->hasClass($className);
	}

	public function getClass(string $className): ClassReflection
	{
		if (isset($this->classes[$className])) {
			return $this->classes[$className];
		}

		return $this->classes[$className] = $this->provider->getClass($className);
	}

	public function getClassName(string $className): string
	{
		if (isset($this->classNames[$className])) {
			return $this->classNames[$className];
		}

		return $this->classNames[$className] = $this->provider->getClassName($className);
	}

	public function supportsAnonymousClasses(): bool
	{
		return $this->provider->supportsAnonymousClasses();
	}

	public function getAnonymousClassReflection(\PhpParser\Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		return $this->provider->getAnonymousClassReflection($classNode, $scope);
	}

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->provider->hasFunction($nameNode, $scope);
	}

	public function getFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		return $this->provider->getFunction($nameNode, $scope);
	}

	public function resolveFunctionName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->provider->resolveFunctionName($nameNode, $scope);
	}

	public function hasConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->provider->hasConstant($nameNode, $scope);
	}

	public function getConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		return $this->provider->getConstant($nameNode, $scope);
	}

	public function resolveConstantName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->provider->resolveConstantName($nameNode, $scope);
	}

}
