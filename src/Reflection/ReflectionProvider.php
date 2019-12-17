<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Analyser\Scope;

interface ReflectionProvider
{

	public function hasClass(string $className): bool;

	public function getClass(string $className): ClassReflection;

	public function getClassName(string $className): string;

	public function getAnonymousClassReflection(
		\PhpParser\Node\Stmt\Class_ $classNode,
		Scope $scope
	): ClassReflection;

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool;

	public function getFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): FunctionReflection;

	public function resolveFunctionName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string;

	// helper functions that do not have to do anything with reflection

	/**
	 * @return string[]
	 */
	public function getUniversalObjectCratesClasses(): array;

}
