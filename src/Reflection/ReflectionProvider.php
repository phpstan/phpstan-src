<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/** @api */
interface ReflectionProvider
{

	/** @phpstan-assert-if-true =class-string $className */
	public function hasClass(string $className): bool;

	public function getClass(string $className): ClassReflection;

	public function getClassName(string $className): string;

	public function supportsAnonymousClasses(): bool;

	public function getAnonymousClassReflection(
		Node\Stmt\Class_ $classNode,
		Scope $scope,
	): ClassReflection;

	public function hasFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool;

	public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection;

	public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string;

	public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool;

	public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): GlobalConstantReflection;

	public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string;

}
