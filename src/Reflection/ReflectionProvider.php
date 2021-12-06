<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/** @api */
interface ReflectionProvider
{

	public function hasClass(string $className): bool;

	public function getClass(string $className): ClassReflection;

	public function getClassName(string $className): string;

	public function supportsAnonymousClasses(): bool;

	public function getAnonymousClassReflection(
		Node\Stmt\Class_ $classNode,
		Scope $scope
	): ClassReflection;

	public function hasFunction(Node\Name $nameNode, ?Scope $scope): bool;

	public function getFunction(Node\Name $nameNode, ?Scope $scope): FunctionReflection;

	public function resolveFunctionName(Node\Name $nameNode, ?Scope $scope): ?string;

	public function hasConstant(Node\Name $nameNode, ?Scope $scope): bool;

	public function getConstant(Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection;

	public function resolveConstantName(Node\Name $nameNode, ?Scope $scope): ?string;

}
