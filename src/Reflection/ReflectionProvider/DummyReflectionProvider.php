<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;

class DummyReflectionProvider implements ReflectionProvider
{

	public function hasClass(string $className): bool
	{
		return false;
	}

	public function getClass(string $className): ClassReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getClassName(string $className): string
	{
		return $className;
	}

	public function supportsAnonymousClasses(): bool
	{
		return false;
	}

	public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		throw new ShouldNotHappenException();
	}

	public function hasFunction(Node\Name $nameNode, ?Scope $scope): bool
	{
		return false;
	}

	public function getFunction(Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		throw new ShouldNotHappenException();
	}

	public function resolveFunctionName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return null;
	}

	public function hasConstant(Node\Name $nameNode, ?Scope $scope): bool
	{
		return false;
	}

	public function getConstant(Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		throw new ShouldNotHappenException();
	}

	public function resolveConstantName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return null;
	}

}
