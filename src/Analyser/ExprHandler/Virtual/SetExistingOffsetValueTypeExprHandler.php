<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * @implements ExprHandler<SetExistingOffsetValueTypeExpr>
 */
#[AutowiredService]
final class SetExistingOffsetValueTypeExprHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof SetExistingOffsetValueTypeExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, $context->enterDeep());
		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->getDim(), $varResult->getScope(), $storage, $nodeCallback, $context->enterDeep());
		$valueResult = $nodeScopeResolver->processExprNode($stmt, $expr->getValue(), $dimResult->getScope(), $storage, $nodeCallback, $context->enterDeep());

		$propertyFetchResult = $expr->getVar() instanceof OriginalPropertyTypeExpr
			? $nodeScopeResolver->processExprNode($stmt, $expr->getVar()->getPropertyFetch(), $scope, $storage, $nodeCallback, $context->enterDeep())
			: null;

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: static function (Expr $uninteresting, MutatingScope $scope) use ($varResult, $dimResult, $valueResult, $propertyFetchResult): Type {
				$varType = $varResult->getTypeForScope($scope);
				if ($propertyFetchResult !== null) {
					$currentPropertyType = $propertyFetchResult->getTypeForScope($scope);
					if ($varType instanceof UnionType) {
						$varType = $varType->filterTypes(static fn (Type $innerType) => !$innerType->isSuperTypeOf($currentPropertyType)->no());
					}
				}

				return $varType->setExistingOffsetValueType(
					$dimResult->getTypeForScope($scope),
					$valueResult->getTypeForScope($scope),
				);
			},
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: [],
			impurePoints: [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$varNode = $expr->getVar();
		$varType = $scope->getType($varNode);
		if ($varNode instanceof OriginalPropertyTypeExpr) {
			$currentPropertyType = $scope->getType($varNode->getPropertyFetch());
			if ($varType instanceof UnionType) {
				$varType = $varType->filterTypes(static fn (Type $innerType) => !$innerType->isSuperTypeOf($currentPropertyType)->no());
			}
		}
		return $varType->setExistingOffsetValueType(
			$scope->getType($expr->getDim()),
			$scope->getType($expr->getValue()),
		);
	}

}
