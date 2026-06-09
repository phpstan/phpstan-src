<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<PostDec>
 */
#[AutowiredService]
final class PostDecHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof PostDec;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());

		$scope = $nodeScopeResolver->processVirtualAssign(
			$varResult->getScope(),
			$storage,
			$stmt,
			$expr->var,
			new PreDec($expr->var),
			$nodeCallback,
		)->getScope();

		return new ExpressionResult(
			$scope,
			hasYield: $varResult->hasYield(),
			isAlwaysTerminating: $varResult->isAlwaysTerminating(),
			throwPoints: $varResult->getThrowPoints(),
			impurePoints: $varResult->getImpurePoints(),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->getType($expr->var);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
