<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Type\Type;

/**
 * @template T of Expr
 */
interface ExprHandler
{

	public const EXTENSION_TAG = 'phpstan.exprHandler';

	/** @phpstan-assert-if-true T $expr */
	public function supports(Expr $expr): bool;

	/**
	 * @param T $expr
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processExpr(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		Expr $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
	): ExpressionResult;

	/**
	 * @param T $expr
	 */
	public function resolveType(MutatingScope $scope, Expr $expr): Type;

	/**
	 * @param T $expr
	 */
	public function specifyTypes(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes;

}
