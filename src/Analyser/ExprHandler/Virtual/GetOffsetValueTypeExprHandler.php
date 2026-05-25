<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @implements ExprHandler<GetOffsetValueTypeExpr>
 */
#[AutowiredService]
final class GetOffsetValueTypeExprHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof GetOffsetValueTypeExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// because this is a virtual node handler, the caller will only be interested in the type
		// we don't need to process the inner expr

		return new ExpressionResult(
			$scope,
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$varType = $scope->getType($expr->getVar());
		$dimType = $scope->getType($expr->getDim());
		$offsetValueType = $varType->getOffsetValueType($dimType);
		if (!$varType->isArray()->no() && !$varType->hasOffsetValueType($dimType)->yes()) {
			$offsetValueType = TypeCombinator::union($offsetValueType, new NullType());
		}

		return $offsetValueType;
	}

}
